<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\FormGroup;
use Illuminate\Http\Request;

class FormGroupController extends Controller
{
    public function store(Request $r, Form $form) {
        $data = $r->validate([
            'nombre'=>'required|string|max:150',
            'descripcion'=>'nullable|string',
            'repetible'=>'boolean',
            'orden'=>'nullable|integer'
        ]);
        $data['id_form'] = $form->id;
        $g = FormGroup::create($data);
        // return response()->json($g, 201);
        return redirect()->route('forms.edit', $form)->with('ok','Grupo creado');
    }

    public function update(Request $r, Form $form, FormGroup $group) {
        $group->update($r->validate([
            'nombre'=>'required|string|max:150',
            'descripcion'=>'nullable|string',
            'repetible'=>'boolean',
            'orden'=>'nullable|integer'
        ]));
        // return response()->json($group);
        return redirect()->route('forms.edit', $form)->with('ok','Grupo actualizado');
    }

    public function destroy(Form $form, FormGroup $group) {
        $group->delete();
       // return response()->noContent();
        return redirect()->route('forms.edit', $form)->with('ok','Grupo eliminado');
    }
}
