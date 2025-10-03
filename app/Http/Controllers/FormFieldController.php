<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\FormField;
use App\Models\FormFieldSource;
use App\Models\FormFieldFormula;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;


class FormFieldController extends Controller
{
    public function store(Request $r, Form $form)
    {
        $data = $this->validateField($form->id, $r);
        
        // 1) Campo
        $data = $r->validate([
            'id_group'   => ['nullable','integer'],
            'codigo'     => ['required','string','max:60'],
            'etiqueta'   => ['required','string','max:200'],
            'descripcion'=> ['nullable','string'],
            'kind'       => ['required','in:input,output'],
            'datatype'   => ['required','string','max:30'],
            'requerido'  => ['boolean'],
            'unico'      => ['boolean'],
            'orden'      => ['nullable','integer'],
            'visible'    => ['boolean'],
            'config_json'=> ['nullable'],
        ]);

        $data['id_form'] = $form->id;
        $field = FormField::create($data);

        // 2) Fuente (solo para select/multiselect/fk y kind = input)
        $this->upsertSource($r, $field);

        // 3) Fórmula (solo para kind = output)
        $this->upsertFormula($r, $field);

        return redirect()->route('forms.edit', $form)->with('ok','Campo creado');
    }

    public function update(Request $r, Form $form, FormField $field)
    {
        // 1) Campo
        $field->update($r->validate([
            'id_group'   => ['nullable','integer'],
            'codigo'     => ['required','string','max:60'],
            'etiqueta'   => ['required','string','max:200'],
            'descripcion'=> ['nullable','string'],
            'kind'       => ['required','in:input,output'],
            'datatype'   => ['required','string','max:30'],
            'requerido'  => ['boolean'],
            'unico'      => ['boolean'],
            'orden'      => ['nullable','integer'],
            'visible'    => ['boolean'],
            'config_json'=> ['nullable'],
        ]));

        // 2) Fuente (solo para select/multiselect/fk y kind = input)
        $this->upsertSource($r, $field);

        // 3) Fórmula (solo para kind = output)
        $this->upsertFormula($r, $field);

        return redirect()->route('forms.edit', $form)->with('ok','Campo actualizado');
    }

    public function destroy(Form $form, FormField $field)
    {
        // elimina también fuente y fórmula ligadas (por si tu modelo no tiene cascade)
        FormFieldSource::where('id_field', $field->id)->delete();
        FormFieldFormula::where('id_field', $field->id)->delete();

        $field->delete();
        return redirect()->route('forms.edit', $form)->with('ok','Campo eliminado');
    }

    // =================== helpers ===================


    private function upsertSource(Request $r, FormField $field): void
    {
        $isSelectLike = in_array($field->datatype, ['select','multiselect','fk'], true);
        $isInputKind  = $field->kind === 'input';

        if (!$isSelectLike || !$isInputKind) {
            \App\Models\FormFieldSource::where('id_field', $field->id)->delete();
            return;
        }

        $srcKindUi   = $r->input('source_kind'); // 'table' | 'table_table' | 'static_options' | 'form'
        $multi       = (int) $r->boolean('multi_select', false);

        // Campos comunes de UI
        $table       = $r->input('table_name');
        $column      = $r->input('column_name');

        $optionsJson = $r->input('options_json');
        $ttRoot      = $r->input('tt_root');       // para table_table
        $ttRelated   = $r->input('tt_related');    // para table_table

        $formId      = $r->input('source_form_id');    // para form
        $fieldCode   = $r->input('source_field_code'); // para form

        $payload = [
            'id_field'     => $field->id,
            'source_kind'  => null,
            'table_name'   => null,
            'column_name'  => null,
            'atributo_id'  => null,
            'query_sql'    => null,
            'options_json' => null,
            'multi_select' => $multi,
        ];

        if ($srcKindUi === 'table') {
            $payload['source_kind'] = 'table';
            $payload['table_name']  = $table ?: null;
            $payload['column_name'] = $column ?: null;

        } elseif ($srcKindUi === 'table_table') {
            $table = $r->input('tt_root_table');
            if($table=='proveedor'){
                $table='proveedores';
            }elseif($table=='cliente'){
                $table='clientes';
            }elseif($table=='producto'){
                $table='productos';
            }
            
            $payload['source_kind'] = 'table_table';
            $meta = [
                'root_code' => $r->input('tt_root'),         // la tabla base elegida (clientes, proveedores…)
                'root_table'=> $table, // tabla asociada al root
                'related' => $r->input('tt_related'), // el código elegido en el select "Relacionado con"
            ];
            $payload['options_json'] = json_encode($meta, JSON_UNESCAPED_UNICODE);
        } elseif ($srcKindUi === 'static_options') {
            $payload['source_kind'] = 'static_options';
            $decoded = json_decode((string)$optionsJson, true);
            $payload['options_json'] = is_array($decoded) ? json_encode($decoded, JSON_UNESCAPED_UNICODE) : '[]';

        } elseif ($srcKindUi === 'form') {
            $payload['source_kind'] = 'form';
            $meta = array_filter([
                'form_id'    => $formId ? (int)$formId : null,
                'field_code' => $fieldCode ?: null,
            ]);
            $payload['options_json'] = json_encode($meta, JSON_UNESCAPED_UNICODE);
        } elseif ($srcKindUi === 'form_actual') {
            $payload['source_kind'] = 'form_actual';
            $meta = array_filter([
                'group_id'   => $r->input('fa_group_id'),
                'field_code' => $r->input('fa_field_code'),
            ]);
            $payload['options_json'] = json_encode($meta, JSON_UNESCAPED_UNICODE);
        } else {
            // sin fuente válida => borrar
            \App\Models\FormFieldSource::where('id_field', $field->id)->delete();
            return;
        }

        \App\Models\FormFieldSource::updateOrCreate(
            ['id_field' => $field->id],
            $payload
        );
    }

    private function upsertFormula(Request $r, FormField $field): void
    {
        if ($field->kind !== 'output') {
            FormFieldFormula::where('id_field', $field->id)->delete();
            return;
        }

        $expr = trim((string)$r->input('formula_expression', ''));
        $outT = (string)$r->input('formula_output_type', 'text');

        if ($expr === '') {
            // si no mandaron nada, borra fórmula previa
            FormFieldFormula::where('id_field', $field->id)->delete();
            return;
        }

        FormFieldFormula::updateOrCreate(
            ['id_field' => $field->id],
            [
                'expression'  => $expr,
                'output_type' => $outT,
            ]
        );
    }

        /** Validación común */
    private function validateField(int $formId, Request $r, ?int $fieldId = null): array
    {
        $rules = [
            'id_group'    => ['nullable','integer','exists:form_groups,id'],
            'codigo'      => [
                'required','string','alpha_dash','max:100',
                Rule::unique('form_fields','codigo')
                    ->where(fn($q)=>$q->where('id_form',$formId))
                    ->ignore($fieldId),
            ],
            'etiqueta'    => ['required','string','max:200'],
            'descripcion' => ['nullable','string','max:500'],
            'kind'        => ['required', Rule::in(['input','output'])],
            'datatype'    => ['required', Rule::in([
                'text','textarea','int','decimal','date','datetime',
                'bool','select','multiselect','fk'
            ])],
            'requerido'   => ['nullable','boolean'],
            'unico'       => ['nullable','boolean'],
            'visible'     => ['nullable','boolean'],
            'orden'       => ['nullable','integer','min:0'],
            'config_json' => ['nullable','string'],

            // Origen plano (no como array)
            'source_kind'    => ['nullable', Rule::in(['table','table_table','static_options','form','form_actual'])],
            'table_name'     => ['nullable','string','max:100'],
            'column_name'    => ['nullable','string','max:100'],
            'options_json'   => ['nullable','string'], // se guarda como JSON string
            'tt_root'        => ['nullable','string','max:100'],
            'tt_related'     => ['nullable','string','max:100'],
            'source_form_id' => ['nullable','integer'],
            'source_field_code' => ['nullable','string','max:100'],
            'multi_select'   => ['nullable','boolean'],

            // Fórmula plana también
            'formula_expression'  => ['nullable','string','max:5000'],
            'formula_output_type' => ['nullable', Rule::in([
                'text','textarea','int','decimal','date','datetime','bool','json'
            ])],
        ];

        $data = $r->validate($rules);

        // Si datatype es select/multiselect/fk => debe venir algún ORIGEN válido
        if (
            in_array(($data['datatype'] ?? ''), ['select','multiselect','fk'], true) &&
            empty($data['source_kind'])
        ) {
            abort(422, 'Debes configurar el ORIGEN para selects/multiselect/fk.');
        }

        // Si es output => fórmula obligatoria
        if (
            ($data['kind'] ?? '') === 'output' &&
            empty($data['formula_expression'])
        ) {
            abort(422, 'Los campos OUTPUT requieren una fórmula.');
        }

        return $data;
    }

    public function formulaContext(Form $form)
    {
        $fields = $form->fields()
            ->with(['group','source'])
            ->get();

        $data = $fields->map(function($f) use ($form){
            $field = [
                'id'       => $f->id,
                'codigo'   => $f->codigo,
                'etiqueta' => $f->etiqueta,
                'grupo'    => optional($f->group)->nombre,
                'tipo'     => $f->datatype,
                'kind'     => $f->kind,
                'source'   => $f->source,
                'atributos'=> [],
            ];

            // helper para juntar columnas y atributos de ficha
            $getTableAttrs = function($table, $table_singular) use ($form) {
                $attrs = [];
                // columnas físicas
                try {
                    $cols = \Schema::getColumnListing($table);
                    foreach ($cols as $c) {
                        $attrs[] = ['value'=>$c, 'label'=>$c];
                    }
                } catch (\Exception $e) { }

                // atributos de ficha asociados
                $ficha = \App\Models\Ficha::where('tipo', $table_singular)
                            ->where('id_emp',$form->id_emp)
                            ->first();
                if ($ficha) {
                    foreach ($ficha->atributos()->get(['id','titulo']) as $a) {
                        $attrs[] = [
                            'value'=>'ficha_'.$a->id,
                            'label'=>$a->titulo
                        ];
                    }
                }
                return $attrs;
            };

            // ==== si es TABLE ====
            if ($f->source && $f->source->source_kind === 'table') {
                $table = $f->source->table_name;
                $table_singular = $f->source->table_name;

                // normalización de nombres
                if($table=='proveedor'){
                    $table='proveedores';
                }elseif($table=='cliente'){
                    $table='clientes';
                }elseif($table=='producto'){
                    $table='productos';
                }

                $field['atributos'] = $getTableAttrs($table, $table_singular);
            }

            // ==== si es TABLE-TABLE ====
            if ($f->source && $f->source->source_kind === 'table_table') {
                
                $meta = json_decode($f->source->options_json, true) ?? [];
                $root = $meta['root_table'] ?? null;
                $related = $meta['related'] ?? null;
                $attrs = [];

                // normalización de nombres
                if($root=='proveedores'){
                    $root='proveedor';
                }elseif($root=='clientes'){
                    $table='cliente';
                }elseif($root=='productos'){
                    $root='producto';
                }

                // 🔹 Atributos de ficha_group_defs (listas o relaciones)
                $rootFicha = \App\Models\Ficha::where('tipo', $root)
                    ->where('id_emp', $form->id_emp)
                    ->first();

                if ($rootFicha) {
                    $groupDef = \DB::table('ficha_group_defs')
                        ->where('id_ficha', $rootFicha->id)
                        ->where('code', $related)
                        ->first();

                    if ($groupDef) {
                        // Decodificar los item_fields (atributos definidos en la lista o relación)
                        $items = json_decode($groupDef->item_fields_json, true) ?? [];

                        foreach ($items as $it) {
                            $attrs[] = [
                                'value' => $it['code'],
                                'label' => $it['label'] ?? $it['code']
                            ];
                        }
                    }
                }

                
                $field['atributos'] = $attrs;
                
            }

            // ==== static options ====
            if ($f->source && $f->source->source_kind === 'static_options') {
                $opts = json_decode($f->source->options_json, true) ?? [];
                $field['atributos'] = collect($opts)
                    ->map(fn($o)=>['value'=>$o['value']??$o['label'], 'label'=>$o['label']??$o['value']])
                    ->all();
            }

            return $field;
        });

        return response()->json($data);
    }

}
