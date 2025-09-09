<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\FormField;
use Illuminate\Http\Request;

class FormFieldController extends Controller
{
    public function store(Request $r, Form $form) {
        $data = $r->validate([
            'id_group'=>'nullable|integer',
            'codigo'=>'required|string|max:60',
            'etiqueta'=>'required|string|max:200',
            'descripcion'=>'nullable|string',
            'kind'=>'required|in:input,output',
            'datatype'=>'required|string|max:30',
            'requerido'=>'boolean',
            'unico'=>'boolean',
            'orden'=>'nullable|integer',
            'visible'=>'boolean',
            'config_json'=>'nullable'
        ]);
        $data['id_form'] = $form->id;
        $field = FormField::create($data);
        // return response()->json($field, 201);
        return redirect()->route('forms.edit', $form)->with('ok','Campo creado');
    }

    public function update(Request $r, Form $form, FormField $field) {
        $field->update($r->validate([
            'id_group'=>'nullable|integer',
            'codigo'=>'required|string|max:60',
            'etiqueta'=>'required|string|max:200',
            'descripcion'=>'nullable|string',
            'kind'=>'required|in:input,output',
            'datatype'=>'required|string|max:30',
            'requerido'=>'boolean',
            'unico'=>'boolean',
            'orden'=>'nullable|integer',
            'visible'=>'boolean',
            'config_json'=>'nullable'
        ]));
        // return response()->json($field);
        return redirect()->route('forms.edit', $form)->with('ok','Campo actualizado');
    }

    public function destroy(Form $form, FormField $field) {
        $field->delete();
        // return response()->noContent();
        return redirect()->route('forms.edit', $form)->with('ok','Campo eliminado');
    }
}
