<?php

namespace App\Http\Controllers;

use App\Models\FormField;
use App\Models\FormFieldFormula;
use Illuminate\Http\Request;

class FormFieldFormulaController extends Controller
{
    public function upsert(Request $r, $formId, FormField $field) {
        $data = $r->validate([
            'expression'=>'required|string',
            'output_type'=>'required|in:decimal,int,text,date,boolean'
        ]);
        $data['id_field'] = $field->id;
        $f = FormFieldFormula::updateOrCreate(['id_field'=>$field->id], $data);
        //return response()->json($f);
        return redirect()->route('forms.edit', $formId)->with('ok','Fuente guardada');
    }

    public function destroy($formId, FormField $field) {
        optional($field->formula)->delete();
        //return response()->noContent();
        return redirect()->route('forms.edit', $formId)->with('ok','Fuente eliminada');
    }
}
