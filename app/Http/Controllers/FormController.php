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
    

    public function edit($id)
    {
        $user = Auth::user();

        // 1) Traer el form
        $form = \App\Models\Form::with([
            'groups.fields.source',
            'groups.fields.formulas'
        ])->findOrFail($id);

        // 2) Verificar permisos (solo la empresa dueña o superadmin)
        if ($user->rol->nombre !== 'SUPERADMIN' && $form->id_emp !== $user->id_emp) {
            abort(403, 'No tienes permisos para editar este formulario.');
        }

        // 3) Pasar data al builder
        return view('admin.forms.builder', [
            'form'     => $form,
            'groups'   => $form->groups,
            'fields'   => $form->groups->flatMap->fields,
        ]);
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
