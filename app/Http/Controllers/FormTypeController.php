<?php

namespace App\Http\Controllers;

use App\Models\FormType;
use Illuminate\Http\Request;

class FormTypeController extends Controller
{
    public function index() {
        $types = FormType::with('empresa')->orderBy('id_emp')->orderBy('nombre')->paginate(20);
        return view('admin.forms.types.index', compact('types'));
    }

    public function create() { 
        $empresas = \App\Models\Empresa::where('estado', true)->orderBy('nombre')->get();
        return view('admin.forms.types.create', compact('empresas')); 
    }

    public function store(Request $r) {
        $data = $r->validate([
            'id_emp'=>'required|integer',
            'nombre'=>'required|string|max:150',
            'descripcion'=>'nullable|string',
            'estado'=>'boolean'
        ]);
        FormType::create($data);
        return redirect()->route('form-types.index')->with('ok','Tipo creado');
    }

    public function edit(FormType $form_type) {
        $form_type->load('empresa');
        return view('admin.forms.types.edit', ['type'=>$form_type]);
    }

    public function update(Request $r, FormType $form_type) {
        $form_type->update($r->validate([
            'nombre'=>'required|string|max:150',
            'descripcion'=>'nullable|string',
            'estado'=>'boolean'
        ]));
        return redirect()->route('form-types.index')->with('ok','Tipo actualizado');
    }

    public function destroy(FormType $form_type) {
        $form_type->delete();
        return back()->with('ok','Eliminado');
    }
}
