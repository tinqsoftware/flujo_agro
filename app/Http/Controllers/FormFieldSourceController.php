<?php

namespace App\Http\Controllers;

use App\Models\FormField;
use App\Models\FormFieldSource;
use Illuminate\Http\Request;

class FormFieldSourceController extends Controller
{
    public function upsert(Request $r, $formId, FormField $field) {
        $data = $r->validate([
            'source_kind'=>'required|in:table_column,ficha_attr,query,static_options',
            'table_name'=>'nullable|string|max:100',
            'column_name'=>'nullable|string|max:100',
            'ficha_id'=>'nullable|integer',
            'atributo_id'=>'nullable|integer',
            'query_sql'=>'nullable|string',
            'options_json'=>'nullable',
            'multi_select'=>'boolean'
        ]);
        $data['id_field'] = $field->id;
        $src = FormFieldSource::updateOrCreate(['id_field'=>$field->id], $data);
        // return response()->json($src);
        return redirect()->route('forms.edit', $formId)->with('ok','Fuente guardada');
    }

    public function destroy($formId, FormField $field) {
        optional($field->source)->delete();
        // return response()->noContent();
        return redirect()->route('forms.edit', $formId)->with('ok','Fuente eliminada');
    }
}
