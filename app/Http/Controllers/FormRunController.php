<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFormRunRequest;
use App\Models\{Form, FormRun, FormField};
use App\Services\Forms\{DataSourceResolver, FormulaEngine, FormPersistenceService};
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class FormRunController extends Controller
{
    public function index(Request $r) {
        $runs = FormRun::with('form')->orderByDesc('id')->paginate(20);
        return view('admin.form_runs.index', compact('runs'));
    }

    public function create(Request $r) {
        $form = Form::with([
            'fields.source', 'fields.formula',
            'groups.fields.source', 'groups.fields.formula',
        ])->findOrFail($r->input('form_id'));

        // TODO: autorización por empresa si aplica
        // $this->authorize('run', $form);

        // Orden visual consistente
        $form->groups = $form->groups->sortBy('orden')->values();
        $form->fields = $form->fields->sortBy('orden')->values();
        foreach ($form->groups as $g) {
            $g->fields = $g->fields->sortBy('orden')->values();
        }

        // Opciones normalizadas por ID de campo
        $resolver = app(DataSourceResolver::class);
        $ctx = ['id_emp' => $form->id_emp];

        $options = [];
        $normalize = function ($list): array {
            // convierte cualquier cosa a [['value'=>..,'label'=>..], ...]
            $out = [];
            foreach ($list ?? [] as $row) {
                if (is_array($row)) {
                    $value = $row['value'] ?? $row['id'] ?? Arr::first($row) ?? null;
                    $label = $row['label'] ?? $row['nombre'] ?? $row['text'] ?? (string)$value;
                } else {
                    $value = $row->value ?? $row->id ?? null;
                    $label = $row->label ?? $row->nombre ?? $row->text ?? (string)$value;
                }
                if ($value !== null) {
                    $out[] = ['value' => $value, 'label' => (string)$label];
                }
            }
            return $out;
        };

        foreach ($form->fields as $f) {
            $options[$f->id] = $f->source ? $normalize($resolver->options($f, $ctx)) : [];
        }
        foreach ($form->groups as $g) {
            foreach ($g->fields as $f) {
                $options[$f->id] = $f->source ? $normalize($resolver->options($f, $ctx)) : [];
            }
        }

        return view('admin.form_runs.create', compact('form','options'));
    }

    public function store(StoreFormRunRequest $r) {
        $form = Form::with([
            'fields.formula',
            'groups.fields.formula',
        ])->findOrFail($r->input('form_id'));

        // TODO: autorización por empresa si aplica
        // $this->authorize('run', $form);

        $payload = $r->validated();

        // ===== Validación dinámica =====
        $rules = [];
        // 1) Cabecera
        foreach ($form->fields as $f) {
            if ($f->kind === 'output') continue;

            $key = "fields.{$f->codigo}";
            $fieldRules = [];
            if ($f->requerido) $fieldRules[] = 'required';

            // Tipos especiales
            if (in_array($f->datatype, ['select','multiselect'])) {
                // El create() ya normalizó opciones por id de campo --> cúmplelo aquí
                $opts = $this->optionsForField($form, $f->id);
                $values = array_column($opts, 'value');

                if ($f->datatype === 'multiselect') {
                    $fieldRules[] = 'array';
                    $rules["{$key}.*"] = [Rule::in($values)];
                } else {
                    $fieldRules[] = Rule::in($values);
                }
            }
            $rules[$key] = $fieldRules;
        }

        // 2) Grupos (si llegan)
        foreach ($form->groups as $g) {
            foreach ($g->fields as $f) {
                if ($f->kind === 'output') continue;

                $base = "groups.{$g->nombre}.*.{$f->codigo}";
                $fieldRules = [];

                if ($f->requerido) $fieldRules[] = 'required';

                if (in_array($f->datatype, ['select','multiselect'])) {
                    $opts = $this->optionsForField($form, $f->id);
                    $values = array_column($opts, 'value');

                    if ($f->datatype === 'multiselect') {
                        $fieldRules[] = 'array';
                        $rules["{$base}.*"] = [Rule::in($values)];
                    } else {
                        $fieldRules[] = Rule::in($values);
                    }
                }
                $rules[$base] = $fieldRules;
            }
        }

        $this->validate($r, $rules);

        // ===== Cálculo de outputs =====
        $engine = app(FormulaEngine::class);

        // Cabecera
        $vals = $payload['fields'] ?? [];
        foreach ($form->fields as $f) {
            if ($f->kind === 'output') {
                $vals[$f->codigo] = $engine->evaluate($f, $vals);
            }
        }
        $payload['fields'] = $vals;

        // Grupos: por cada fila, contexto propio
        if (!empty($payload['groups'])) {
            foreach ($form->groups as $g) {
                $rows = $payload['groups'][$g->nombre] ?? [];
                foreach ($rows as $i => $rowVals) {
                    // ➜ contexto = fila + cabecera
                    $ctx = array_merge($payload['fields'] ?? [], $rowVals ?? []);
                    foreach ($g->fields as $f) {
                        if ($f->kind === 'output') {
                            $rowVals[$f->codigo] = $engine->evaluate($f, $ctx);
                        }
                    }
                    $rows[$i] = $rowVals;
                }
                $payload['groups'][$g->nombre] = $rows;
            }
        }

        // Persistencia (maneja correlativo, answers, rows, etc.)
        $run = app(FormPersistenceService::class)->saveRun(
            $form,
            $payload,
            $form->id_emp,
            $payload['id_detalle_flujo'] ?? null,
            auth()->id()
        );

        return redirect()->route('form-runs.edit', $run)->with('ok','Guardado');
    }

    public function edit(FormRun $form_run) {
        $form_run->load('form','answers.field','rows.values.field');
        return view('admin.form_runs.edit', ['run'=>$form_run]);
    }

    public function update(StoreFormRunRequest $r, FormRun $form_run) {
        $form = $form_run->form()->with([
            'fields.formula',
            'groups.fields.formula',
        ])->first();

        $payload = $r->validated();

        $engine = app(FormulaEngine::class);

        // Cabecera
        $vals = $payload['fields'] ?? [];
        foreach ($form->fields as $f) {
            if ($f->kind === 'output') {
                $vals[$f->codigo] = $engine->evaluate($f, $vals);
            }
        }
        $payload['fields'] = $vals;

        // Grupos
        if (!empty($payload['groups'])) {
            foreach ($form->groups as $g) {
                $rows = $payload['groups'][$g->nombre] ?? [];
                foreach ($rows as $i => $rowVals) {
                    // ➜ contexto = fila + cabecera
                    $ctx = array_merge($payload['fields'] ?? [], $rowVals ?? []);
                    foreach ($g->fields as $f) {
                        if ($f->kind === 'output') {
                            $rowVals[$f->codigo] = $engine->evaluate($f, $ctx);
                        }
                    }
                    $rows[$i] = $rowVals;
                }
                $payload['groups'][$g->nombre] = $rows;
            }
        }

        app(FormPersistenceService::class)->saveRun(
            $form,
            $payload,
            $form_run->id_emp,
            $payload['id_detalle_flujo'] ?? $form_run->id_detalle_flujo,
            auth()->id()
        );

        return back()->with('ok','Actualizado');
    }

    public function submit(FormRun $run) {
        $run->update(['estado'=>'submitted','updated_by'=>auth()->id()]);
        return back()->with('ok','Enviado');
    }

    public function approve(FormRun $run) {
        $run->update(['estado'=>'approved','updated_by'=>auth()->id()]);
        return back()->with('ok','Aprobado');
    }

    public function destroy(FormRun $run) {
        $run->delete();
        return back()->with('ok','Eliminado');
    }

    // ===== helpers =====

    private function optionsForField(Form $form, int $fieldId): array
    {
        // Si en create() armaste $options y lo guardaste en sesión podrías leerlo acá.
        // Para mantenerlo simple, regeneramos rápido:
        $field = $form->fields->firstWhere('id', $fieldId)
              ?? $form->groups->flatMap->fields->firstWhere('id', $fieldId);

        if (!$field || !$field->source) return [];

        $resolver = app(DataSourceResolver::class);
        $raw = $resolver->options($field, ['id_emp'=>$form->id_emp]);

        // normaliza igual que en create()
        $out = [];
        foreach ($raw ?? [] as $row) {
            if (is_array($row)) {
                $value = $row['value'] ?? $row['id'] ?? Arr::first($row) ?? null;
                $label = $row['label'] ?? $row['nombre'] ?? $row['text'] ?? (string)$value;
            } else {
                $value = $row->value ?? $row->id ?? null;
                $label = $row->label ?? $row->nombre ?? $row->text ?? (string)$value;
            }
            if ($value !== null) $out[] = ['value'=>$value,'label'=>(string)$label];
        }
        return $out;
    }
}
