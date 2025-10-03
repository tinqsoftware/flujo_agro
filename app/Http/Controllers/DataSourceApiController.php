<?php

namespace App\Http\Controllers;

use App\Models\FormField;
use App\Services\Forms\DataSourceResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class DataSourceApiController extends Controller
{
    public function options(Request $r, DataSourceResolver $resolver) {
        $field = FormField::with('source')->findOrFail($r->query('field_id'));
        $ctx = ['id_emp'=>$field->id_emp];
        return response()->json($resolver->options($field, $ctx));
    }

    
    public function recordMeta(Request $r, DataSourceResolver $resolver)
    {
        $kind = $r->input('source_kind');
        $id   = (int) $r->input('id');

        if ($kind === 'table') {
            $table = $r->input('table_name');
            if (!$table || !$id) {
                return response()->json(['ok'=>false,'msg'=>'params'], 422);
            }

            $metaRow = $resolver->recordMetaFromTable($table, $id);
            if (!$metaRow) {
                return response()->json(['ok'=>false,'msg'=>'not_found'], 404);
            }

            return response()->json([
                'ok'    => true,
                'meta'  => $metaRow['meta'] ?? [],
                'label' => $metaRow['label'] ?? '',
                'value' => $metaRow['value'] ?? $id,
            ]);
        }

        // Extender aquí para otros kinds (ficha_attr, form_header, form_group) si los activas
        return response()->json(['ok'=>false,'msg'=>'unsupported'], 422);
    }


    public function tableOptions(Request $r)
    {
        $table  = $r->query('table');                  // p.ej. 'productos', 'clientes', 'proveedores'
        $label  = $r->query('label');                  // p.ej. 'nombre'
        $idEmp  = $r->query('id_emp');

        if (!$table) return response()->json([]);

        $schema = \DB::getSchemaBuilder();

        if($table=='proveedor'){
            $table='proveedores';
        }elseif($table=='cliente'){
            $table='clientes';
        }elseif($table=='producto'){
            $table='productos';
        }

        // Elegir columna label válida
        $labelCol = $label && $schema->hasColumn($table, $label) ? $label : null;
        if (!$labelCol) {
            foreach (['nombre','razon_social','descripcion','codigo','name','title'] as $c) {
                if ($schema->hasColumn($table, $c)) { $labelCol = $c; break; }
            }
            $labelCol = $labelCol ?: 'id';
        }

        // Registros base
        $q = \DB::table($table)->select("$table.*");
        if ($schema->hasColumn($table, 'estado'))  $q->where("$table.estado", 1);
        if ($schema->hasColumn($table, 'id_emp') && $idEmp) $q->where("$table.id_emp", $idEmp);

        $base = $q->orderBy($labelCol)->limit(500)->get();
        if ($base->isEmpty()) return response()->json([]);

        $ids      = $base->pluck('id')->all();
        $idFichas = $base->pluck('id_ficha')->filter()->unique()->values()->all();

        // Extras por lote (evitar N+1)
        $extrasByEntity = [];
        if (!empty($idFichas)) {
            $extraRows = \DB::table('datos_atributos_fichas as d')
                ->join('atributo_ficha as a', 'a.id', '=', 'd.id_atributo')
                ->whereIn('d.id_relacion', $ids)
                ->whereIn('a.id_ficha', $idFichas)
                ->select('d.id_relacion as entity_id', 'a.titulo', 'd.dato', 'd.json','a.id')
                ->get();

            foreach ($extraRows as $er) {
                $key = 'ficha_'.Str::slug($er->id, '_');  // ej. "Color Presentación" -> "extra_color_presentacion"
                $val = $er->dato;
                if ($val === null || $val === '') {
                    $dec = json_decode($er->json, true);
                    // si tu JSON tiene otra estructura, ajusta esta línea:
                    $val = is_array($dec) && array_key_exists('valor', $dec) ? $dec['valor'] : ($dec ?? null);
                }
                $extrasByEntity[$er->entity_id][$key] = $val;
            }
        }

        // Armar respuesta [{value,label,meta}]
        $out = $base->map(function($row) use ($labelCol, $extrasByEntity) {
            $arr  = (array)$row;           // columnas base
            $meta = $arr;                  // queremos TODO en meta
            if (isset($extrasByEntity[$row->id])) {
                $meta = array_merge($meta, $extrasByEntity[$row->id]); // + extras prefijados
            }
            return [
                'value' => $row->id,
                'label' => $arr[$labelCol] ?? $row->id,
                'meta'  => $meta,
            ];
        });

        return response()->json($out);
    }

    public function tabletableOptions(Request $r)
    {
        $rootTable = $r->input('root_table');   // ej. cliente
        $related   = $r->input('related');      // ej. contacto o proveedor
        $parentId  = $r->input('parent_id');    // id del cliente seleccionado
        $idEmp     = $r->input('id_emp');

        if (!$rootTable || !$related || !$parentId) {
            return response()->json([]);
        }

        $out = [];

        $table=$rootTable;

        if($table=='Proveedores'){
            $table='proveedor';
        }elseif($table=='Clientes'){
            $table='cliente';
        }elseif($table=='Productos'){
            $table='producto';
        }

         // 1. Detectar la ficha raíz (ej: cliente → ficha.id con tipo = Cliente y empresa = idEmp)
        $rootFicha = \DB::table('ficha')
            ->where('tipo', $table)   // Cliente / Proveedor / Producto
            ->where('id_emp', $idEmp)
            ->first();
        
        if (!$rootFicha) {
            return response()->json([]);
        }

        // 2. Buscar definición de grupo dentro de esa ficha
        $groupDef = \DB::table('ficha_group_defs')
            ->where('id_ficha', $rootFicha->id)
            ->where('code', $related)
            ->first();

        if (!$groupDef) {
            return response()->json([]);
        }

        // === CASO 1: Grupo de tipo relación ===
        if ($groupDef->group_type === 'relation') {

            // 1. Traer links de relación para el parent
            $rels = \DB::table('ficha_relation_links as frl')
                ->where('frl.entity_type', $table)      // ej: cliente
                ->where('frl.entity_id', $parentId)     // ej: cliente 25
                ->where('frl.group_code', $related)     // ej: relacionproveedorclinica
                ->get();

            foreach ($rels as $rel) {
                $entityTable = $rel->related_entity_type; // ej: proveedor
                $entityId    = $rel->related_entity_id;   // ej: 7

                if($entityTable=='proveedor'){
                    $table_relacion='proveedores';
                }elseif($entityTable=='cliente'){
                    $table_relacion='clientes';
                }elseif($entityTable=='producto'){
                    $table_relacion='productos';
                }

                // 2. Buscar el registro real en la tabla correspondiente
                $reg = \DB::table($table_relacion)
                    ->where('id', $entityId)
                    ->where('id_emp', $idEmp)
                    ->select('id', 'nombre') // puedes ajustar si tu tabla usa otra columna
                    ->first();

                if ($reg) {
                    $out[] = [
                        'value' => $reg->id,
                        'label' => $reg->nombre,
                        'meta'  => [
                            'from' => 'relation',
                            'entity_type' => $table_relacion
                        ]
                    ];
                }
            }
        }

        // === CASO 2: Grupo de tipo lista ===
        if ($groupDef->group_type === 'list') {
            
            $lists = \DB::table('ficha_list_items as fli')
                ->where('fli.entity_type', $table)
                ->where('fli.entity_id', $parentId)
                ->where('fli.group_code', $related)
                ->select('fli.id as value', 'fli.value_json')
                ->get();

            foreach ($lists as $li) {
                $val = json_decode($li->value_json, true) ?: [];

                $label = $val['nombre'] ?? $val['label'] ?? ('Item '.$li->id);
                $out[] = [
                    'value' => $li->value,
                    'label' => $label,
                    'meta'  => $val + ['from' => 'list']
                ];
            }
        }


        return response()->json($out);
    }

}
