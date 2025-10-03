<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\Ficha;
use App\Models\FichaGroupDef;
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


    // GET /forms/{form}/sources/tables
    public function tables(Form $form)
    {
        // Catálogo base (puedes ajustar si algún día agregas más)
        $rows = [
            ['value'=>'cliente',   'label'=>'clientes'],
            ['value'=>'proveedor', 'label'=>'proveedores'],
            ['value'=>'producto',  'label'=>'productos'],
        ];
        return response()->json($rows);
    }

    // GET /forms/{form}/sources/columns?table=clientes
    public function columns(Request $r, Form $form)
    {
        $t = (string) $r->query('table', '');

        // columnas “sanas” por tabla (puedes afinar más adelante)
        $by = [
            'cliente'   => [['id','ID'],['nombre','Nombre'],['codigo','Código']],
            'proveedor' => [['id','ID'],['nombre','Nombre'],['codigo','Código']],
            'producto'  => [['id','ID'],['nombre','Nombre'],['codigo','Código']],
        ];

        $rows = collect($by[$t] ?? [['id','ID'],['nombre','Nombre']])
            ->map(fn($p)=>['value'=>$p[0],'label'=>$p[1]])->values();

        return response()->json($rows);
    }

    // GET /forms/{form}/sources/table-table?root=cliente
    public function tableTable(Request $r, Form $form)
    {
        $table = $r->query('root'); // ej. "clientes"
        $empresaId = $form->id_emp; // usamos empresa del formulario

        // Buscar la ficha que corresponde a esa tabla
        $ficha = \App\Models\Ficha::where('tipo', $table)
                    ->where('id_emp', $empresaId)
                    ->first();

        if (!$ficha) {
            return response()->json([]);
        }

        // Traer grupos definidos de esa ficha: relaciones o listas
        $items = \App\Models\FichaGroupDef::where('id_ficha', $ficha->id)
            ->whereIn('group_type', ['relation','list'])
            ->get(['code','label','group_type'])
            ->map(fn($g) => [
                'value' => $g->code,
                'label' => $g->code.' - '.$g->label.' - '.' ('.$g->group_type.')'
            ]);

        return response()->json($items);
    }

    public function tableTableRoot(Form $form)
    {
        // campos del form actual que sean select con source_kind=table
        $fields = $form->fields()
            ->with('source')
            ->whereIn('datatype',['select','multiselect','fk'])
            ->get()
            ->filter(fn($f)=> $f->source && $f->source->source_kind==='table');

        $rows = $fields->map(fn($f)=>[
            'value' => $f->codigo,
            'label' => $f->codigo.' — '.$f->etiqueta.' ('.$f->source->table_name.')',
            'table' => $f->source->table_name,
        ])->values();

        return response()->json($rows);
    }

    // GET /forms/{form}/sources/forms
    public function forms(Form $form)
    {
        $rows = Form::where('id_emp',$form->id_emp)
            ->where('estado',1)
            ->orderBy('nombre')
            ->get(['id','nombre'])
            ->map(fn($f)=>['value'=>$f->id,'label'=>$f->nombre])
            ->values();

        return response()->json($rows);
    }

    // GET /forms/{form}/sources/form-fields?form_id=...
    public function formFields(Request $r, Form $form)
    {
        $fid = (int) $r->query('form_id', 0);
        if (!$fid) return response()->json([]);

        $rows = FormField::where('id_form',$fid)
            ->orderBy('orden')->orderBy('id')
            ->get(['codigo','etiqueta'])
            ->map(fn($f)=>['value'=>$f->codigo,'label'=>($f->etiqueta ?: $f->codigo)])
            ->values();

        return response()->json($rows);
    }
}
