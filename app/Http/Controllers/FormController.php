<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFormRequest;
use App\Models\Form;
use App\Models\FormType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FormController extends Controller
{
    public function index() {
        $forms = Form::with('empresa','type')->orderByDesc('id')->paginate(20);
        return view('admin.forms.index', compact('forms'));
    }

    public function create() {
        $types = FormType::orderBy('nombre')->get();
        $empresas = \App\Models\Empresa::where('estado', true)->orderBy('nombre')->get();
        return view('admin.forms.create', compact('types', 'empresas'));
    }
    public function store(StoreFormRequest $r) {
        $data = $r->validated();
        $data['created_by'] = Auth::id();
        $form = Form::create($data);
        return redirect()->route('forms.edit', $form)->with('ok','Formulario creado');
    }
    

    public function edit(Form $form) {
        $form->load(['groups.fields.source','fields.formula','fields.source']);
        return view('admin.forms.builder', compact('form'));
    }
    public function update(StoreFormRequest $r, Form $form) {
        $form->update(array_merge($r->validated(), ['updated_by'=>Auth::id()]));
        return back()->with('ok','Formulario actualizado');
    }
    

    public function destroy(Form $form) {
        $form->delete();
        return redirect()->route('forms.index')->with('ok','Eliminado');
    }
}
