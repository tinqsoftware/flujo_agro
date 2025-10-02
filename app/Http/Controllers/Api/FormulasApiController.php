<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FormField;
use App\Services\Forms\FormulaEngine;
use Illuminate\Http\Request;

class FormulasApiController extends Controller
{
    /**
     * Evalúa un output en vivo: usa FormulaEngine con los scopes local+global enviados.
     * params:
     *  - field_id (id del FormField con fórmula)
     *  - local   (valores de la fila/row si aplica)
     *  - global  (valores de cabecera)
     */
    public function evaluate(Request $r, FormulaEngine $engine)
    {
        $fieldId = (int) $r->input('field_id');
        $local   = (array) $r->input('local', []);
        $global  = (array) $r->input('global', []);

        /** @var \App\Models\FormField|null $field */
        $field = FormField::with('formula')->find($fieldId);
        if (!$field || !$field->formula) {
            return response()->json(['ok'=>false, 'msg'=>'field_without_formula'], 422);
        }

        // Para el engine, el contexto contiene ambos scopes (local tiene prioridad)
        $ctx = array_merge($global, $local);
        $value = $engine->evaluate($field, $ctx);

        return response()->json(['ok'=>true, 'value'=>$value]);
    }
}
