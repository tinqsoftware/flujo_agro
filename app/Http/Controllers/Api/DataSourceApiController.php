<?php

namespace App\Http\Controllers\Api;

use App\Services\Forms\DataSourceResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DataSourceApiController extends Controller
{
    /**
     * Devuelve meta/label/value de un registro seleccionado.
     * Espera:
     *  - source_kind: 'table'
     *  - table_name:  nombre de la tabla (si kind=table)
     *  - id:          id del registro seleccionado
     */
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

        $table  = $r->query('table');
        $label  = $r->query('label','nombre');

        // Filtrar por empresa si aplica (ej. id_emp del usuario o del formulario)
        // $empId = auth()->user()->id_emp ?? null;

        $rows = \DB::table($table)
        // ->where('id_emp', $empId)  // descomenta si corresponde
        ->orderBy($label)
        ->limit(500)
        ->get();

        return $rows->map(function($row) use ($label){
            $arr = (array)$row;
            return [
            'value' => $row->id,
            'label' => $row->{$label} ?? $row->id,
            'meta'  => $arr, // devolvemos TODO para usarlo en fórmulas en vivo
            ];
        });
    }
}
