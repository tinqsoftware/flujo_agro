<?php
namespace App\Services\Forms;

use App\Models\FormRun;

class RunDataAssembler
{
    /**
     * Devuelve un arreglo con:
     * - 'run': info básica (id, correlativo, estado, fechas)
     * - 'form': nombre del formulario
     * - 'header': valores de cabecera (codigo => valor)
     * - 'groups': [ nombre_grupo => [ [codigo=>valor,...], ... ] ]
     */
    public function build(FormRun $run): array
    {
        $run->loadMissing([
            'form',
            'answers.field',        // cabecera
            'rows.group',           // grupo de cada fila
            'rows.values.field',    // campos por fila
        ]);

        // Cabecera
        $header = [];
        foreach ($run->answers as $ans) {
            $f = $ans->field;
            if (!$f || $f->id_group) continue;
            $header[$f->codigo] = $this->extractAnswerValue($ans);
        }

        // Filas por grupo
        $groups = [];
        foreach ($run->rows as $row) {
            $g = $row->group; // requires relation in model
            if (!$g) continue;

            $rowData = [];
            foreach ($row->values as $v) {
                $f = $v->field;
                if (!$f) continue;
                $rowData[$f->codigo] = $this->extractRowValue($v);
            }
            $groups[$g->nombre] ??= [];
            $groups[$g->nombre][] = $rowData;
        }

        return [
            'run' => [
                'id'          => $run->id,
                'correlativo' => $run->correlativo,
                'estado'      => $run->estado,
                'created_at'  => optional($run->created_at)?->format('Y-m-d H:i'),
                'updated_at'  => optional($run->updated_at)?->format('Y-m-d H:i'),
            ],
            'form'   => ['nombre' => $run->form?->nombre],
            'header' => $header,
            'groups' => $groups,
        ];
    }

    private function extractAnswerValue($ans)
    {
        return $ans->value_text
            ?? $ans->value_number
            ?? $ans->value_int
            ?? $ans->value_date
            ?? $ans->value_datetime
            ?? (isset($ans->value_bool) ? (int)$ans->value_bool : null)
            ?? (is_array($ans->value_json) ? json_encode($ans->value_json, JSON_UNESCAPED_UNICODE) : $ans->value_json);
    }

    private function extractRowValue($v)
    {
        return $v->value_text
            ?? $v->value_number
            ?? $v->value_int
            ?? $v->value_date
            ?? $v->value_datetime
            ?? (isset($v->value_bool) ? (int)$v->value_bool : null)
            ?? (is_array($v->value_json) ? json_encode($v->value_json, JSON_UNESCAPED_UNICODE) : $v->value_json);
    }
}
