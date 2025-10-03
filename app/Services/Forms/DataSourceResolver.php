<?php
namespace App\Services\Forms;

use App\Models\FormField;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DataSourceResolver {
    public function options(FormField $field, array $context = []): array
    {
        $src = $field->source;

        // ==== ACEPTAR ALIAS DESDE EL BUILDER ====
        $kind = $src->source_kind ?? null;
        switch ($kind) {
            case 'tabla':        $kind = 'table'; break;
            case 'manual':       $kind = 'static_options'; break;
            case 'formulario':   $kind = 'form_head'; break; // si algún día usas esta modalidad
            // case 'fichas':    $kind = 'ficha_attr'; break;
        }
        $src->source_kind = $kind;

        if (!$src) return [];

        $colExists = function (string $table, string $col): bool {
            try {
                return DB::getSchemaBuilder()->hasColumn($table, $col);
            } catch (\Throwable $e) { return false; }
        };

        // Normaliza a [value,label,meta]
        $normalize = function ($rows, ?string $labelCol = null): array {
            $out = [];
            foreach ($rows ?? [] as $r) {
                $row = is_array($r) ? $r : (array)$r;

                $value = $row['value'] ?? $row['id'] ?? null;
                $label = $row['label']
                    ?? ($labelCol ? ($row[$labelCol] ?? null) : null)
                    ?? (string)$value;

                // meta base = row sin claves de value/label
                $meta = $row;
                unset($meta['value'], $meta['label'], $meta['meta_json']);
                if ($labelCol) unset($meta[$labelCol]);

                // decodificar si viene json de extras
                if (!empty($row['meta_json'])) {
                    $decoded = json_decode($row['meta_json'], true);
                    if (is_array($decoded)) {
                        foreach ($decoded as $k => $v) {
                            $meta['extra_'.$k] = $v;   // 👈 prefijo
                        }
                    }
                }

                // también si el row tiene clave 'meta' como array
                if (isset($row['meta']) && is_array($row['meta'])) {
                    foreach ($row['meta'] as $k => $v) {
                        $meta['extra_'.$k] = $v;
                    }
                }

                if ($value !== null) {
                    $out[] = [
                        'value' => $value,
                        'label' => (string)$label,
                        'meta'  => $meta, // 👈 plano con extras prefijados
                    ];
                }
            }
            return $out;
        };

        switch ($src->source_kind) {
            case 'table': {
                $table = $src->table_name;               // usa el nombre real: 'productos', 'clientes', 'proveedores'
                $labelCol = $src->column_name ?? null;

                if ($table=='proveedor') {
                    $table='proveedores';
                } elseif ($table=='cliente') {
                    $table='clientes';
                } elseif ($table=='producto') {
                    $table='productos';
                }

                // label de respaldo
                if (!$labelCol) {
                    foreach (['nombre','razon_social','descripcion','codigo','name','title'] as $c) {
                        if (DB::getSchemaBuilder()->hasColumn($table, $c)) { $labelCol = $c; break; }
                    }
                    $labelCol = $labelCol ?: 'id';
                }

                // base
                $baseQ = DB::table($table)->select("$table.*");
                if (DB::getSchemaBuilder()->hasColumn($table, 'id_emp') && isset($context['id_emp'])) {
                    $baseQ->where("$table.id_emp", $context['id_emp']);
                }

                $base = $baseQ->orderBy($labelCol)->limit(500)->get();
                if ($base->isEmpty()) return [];

                $ids      = $base->pluck('id')->all();
                $idFichas = $base->pluck('id_ficha')->filter()->unique()->values()->all();

                // extras por lote
                $extrasMap = [];
                if (!empty($idFichas)) {
                    $extraRows = DB::table('datos_atributos_fichas as d')
                        ->join('atributo_ficha as a', 'a.id', '=', 'd.id_atributo')
                        ->whereIn('d.id_relacion', $ids)
                        ->whereIn('a.id_ficha', $idFichas)
                        ->select('d.id_relacion as entity_id', 'a.titulo', 'd.dato', 'd.json')
                        ->get();

                    foreach ($extraRows as $er) {
                        $key = 'extra_'.\Illuminate\Support\Str::slug($er->titulo, '_'); // evita espacios
                        $val = $er->dato;
                        if ($val === null || $val === '') {
                            $dec = json_decode($er->json, true);
                            // si tu JSON tiene estructura fija, ajústalo aquí
                            $val = is_array($dec) && array_key_exists('valor',$dec) ? $dec['valor'] : ($dec ?? null);
                        }
                        $extrasMap[$er->entity_id][$key] = $val;
                    }
                }

                // arma filas con extras
                $rows = [];
                foreach ($base as $r) {
                    $row = (array)$r;
                    $row['value'] = $row['id'] ?? null;
                    $row['label'] = $row[$labelCol] ?? $row['value'];
                    if (!empty($extrasMap[$row['id']])) {
                        $row = array_merge($row, $extrasMap[$row['id']]);
                    }
                    $rows[] = $row;
                }

                return $normalize($rows, $labelCol);
            }

            case 'ficha_attr': {
                // datos_atributos_fichas: id_relacion (value), dato/label, y meta desde columnas comunes
                // NOTA: si quieres usar el label de la tabla base, aquí podrías join a esa tabla según el atributo pertenezca a productos/clientes/etc.
                $rows = DB::table('datos_atributos_fichas as d')
                    ->join('atributo_ficha as a', 'a.id', '=', 'd.id_atributo')
                    ->where('a.id', $src->atributo_id)
                    ->selectRaw('d.id_relacion as value, d.dato as label, d.json as meta_json')
                    ->limit(500)->get();

                return $normalize($rows);
            }

            case 'static_options': {
                $raw = $src->options_json;
                if (is_string($raw)) {
                    $decoded = json_decode($raw, true);
                    return is_array($decoded) ? $normalize($decoded) : [];
                }
                return $normalize($raw);
            }

            // Opcional: si usarás otras ejecuciones como fuente
            case 'form_header': {
                // TODO: listar ejecuciones o respuestas de cabecera como opciones
                return [];
            }

            case 'form_group': {
                // TODO: listar filas de grupo como opciones
                return [];
            }
        }

        return [];
    }


    // === Extras útiles para endpoints en vivo ===
    public function recordMetaFromTable(string $table, int $id, array $context = []): ?array
    {
        $row = (array) DB::table($table)->where('id', $id)->first();
        if (!$row) return null;

        $labelCol = null;
        foreach (['nombre','razon_social','descripcion','codigo','name','title'] as $c) {
            if (array_key_exists($c, $row)) { $labelCol = $c; break; }
        }

        // extras del registro
        $extras = [];
        if (!empty($row['id_ficha'])) {
            $extraRows = DB::table('datos_atributos_fichas as d')
                ->join('atributo_ficha as a', 'a.id', '=', 'd.id_atributo')
                ->where('d.id_relacion', $row['id'])
                ->where('a.id_ficha', $row['id_ficha'])
                ->select('a.titulo','d.dato','d.json')
                ->get();

            foreach ($extraRows as $er) {
                $key = 'extra_'.\Illuminate\Support\Str::slug($er->titulo, '_');
                $val = $er->dato;
                if ($val === null || $val === '') {
                    $dec = json_decode($er->json, true);
                    $val = is_array($dec) && array_key_exists('valor',$dec) ? $dec['valor'] : ($dec ?? null);
                }
                $extras[$key] = $val;
            }
        }

        $value = $row['id'] ?? null;
        $label = $labelCol ? ($row[$labelCol] ?? $value) : $value;

        // meta: columnas base (sin id y label) + extras
        $meta = $row;
        unset($meta['id']);
        if ($labelCol) unset($meta[$labelCol]);
        $meta = array_merge($meta, $extras);

        return ['value'=>$value,'label'=>(string)$label,'meta'=>$meta];
    }

    public function getFichaAttr(string $entityKey, int $attrId, int $entityId): mixed
    {
        $ficha = \App\Models\Ficha::where('tipo',$entityKey)->first();
        if (!$ficha || !$entityId) return null;

        $valor = \DB::table('ficha_valores')
            ->where('id_ficha',$ficha->id)
            ->where('id_attr',$attrId)
            ->where('id_entity',$entityId)
            ->value('valor');

        return $valor;
    }

    private function colExists(string $table, string $column): bool {
        return \Schema::hasColumn($table, $column);
    }

    public function getLabelById(string $table, int $id, string $column = 'nombre'): ?string
    {
        return \DB::table($table)->where('id', $id)->value($column);
    }

    public function getValue(string $table, int $id, string $column): mixed
    {
        return \DB::table($table)->where('id', $id)->value($column);
    }

    public function empresaValue(int $empId, string $column): mixed
    {
        return \DB::table('empresa')->where('id', $empId)->value($column);
    }

    public function fichaAttrValue(int $idFicha, int $idRelacion, int $atributoId): mixed
    {
        // si lo necesitas: lee valor desde datos_atributos_fichas
        return \DB::table('datos_atributos_fichas')
            ->where('id_relacion', $idRelacion)
            ->where('id_atributo', $atributoId)
            ->value('dato');
    }
}
