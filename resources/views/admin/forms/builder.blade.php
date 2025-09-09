@extends('layouts.dashboard')
@section('title')
  Creador: {{ $form->nombre }} (ID {{ $form->id }})
@endsection

@section('page-title')
  Creando: {{ $form->nombre }} (ID {{ $form->id }})
@endsection

@section('content-area')
<div class="mb-3">
  <form method="post" action="{{ route('forms.update',$form) }}" class="row g-2">
    @csrf @method('put')
    <div class="col-md-3">
      <label class="form-label">Nombre</label>
      <input name="nombre" class="form-control" value="{{ $form->nombre }}" required>
    </div>
    <div class="col-md-2">
      <label class="form-label">Correlativo</label>
      <select name="usa_correlativo" class="form-select">
        <option value="0" @selected(!$form->usa_correlativo)>No</option>
        <option value="1" @selected($form->usa_correlativo)>Sí</option>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label">Prefijo</label>
      <input name="correlativo_prefijo" class="form-control" value="{{ $form->correlativo_prefijo }}">
    </div>
    <div class="col-md-2">
      <label class="form-label">Sufijo</label>
      <input name="correlativo_sufijo" class="form-control" value="{{ $form->correlativo_sufijo }}">
    </div>
    <div class="col-md-2">
      <label class="form-label">Padding</label>
      <input name="correlativo_padding" type="number" class="form-control" value="{{ $form->correlativo_padding }}">
    </div>
    <div class="col-md-1 d-flex align-items-end">
      <button class="btn btn-primary w-100">Guardar</button>
    </div>
  </form>
</div>

<div class="row">
  <div class="col-md-4">
    <div class="card mb-3">
      <div class="card-header">Nuevo Grupo</div>
      <div class="card-body">
        <form method="post" action="{{ route('forms.groups.store',$form) }}">
          @csrf
          <label class="form-label">Nombre</label>
          <input name="nombre" class="form-control mb-2" required>
          <label class="form-label">Descripción</label>
          <input name="descripcion" class="form-control mb-2">
          <label class="form-label">Repetible</label>
          <select name="repetible" class="form-select mb-2">
            <option value="0">No</option>
            <option value="1">Sí</option>
          </select>
          <label class="form-label">Orden</label>
          <input name="orden" type="number" class="form-control mb-3" value="0">
          <button class="btn btn-success">Agregar Grupo</button>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-header">Nuevo Campo</div>
      <div class="card-body">
        <form method="post" action="{{ route('forms.fields.store',$form) }}">
          @csrf
          <label class="form-label">Grupo (opcional)</label>
          <select name="id_group" class="form-select mb-2">
            <option value="">— Sin grupo —</option>
            @foreach($form->groups as $g)
              <option value="{{ $g->id }}">{{ $g->nombre }}</option>
            @endforeach
          </select>

          <label class="form-label">Código</label>
          <input name="codigo" class="form-control mb-2" placeholder="ej. cantidad" required>

          <label class="form-label">Etiqueta</label>
          <input name="etiqueta" class="form-control mb-2" required>

          <label class="form-label">Tipo</label>
          <select name="datatype" class="form-select mb-2">
            <option>text</option><option>textarea</option><option>int</option><option>decimal</option>
            <option>date</option><option>datetime</option><option>boolean</option>
            <option>select</option><option>multiselect</option><option>fk</option><option>file</option>
          </select>

          <label class="form-label">Kind</label>
          <select name="kind" class="form-select mb-2">
            <option value="input">input</option>
            <option value="output">output</option>
          </select>

          <div class="row">
            <div class="col">
              <label class="form-label">Requerido</label>
              <select name="requerido" class="form-select mb-2"><option value="0">No</option><option value="1">Sí</option></select>
            </div>
            <div class="col">
              <label class="form-label">Visible</label>
              <select name="visible" class="form-select mb-2"><option value="1">Sí</option><option value="0">No</option></select>
            </div>
          </div>

          <label class="form-label">Orden</label>
          <input name="orden" type="number" class="form-control mb-3" value="0">
          <button class="btn btn-success">Agregar Campo</button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-md-8">
    <div class="card">
      <div class="card-header">Estructura</div>
      <div class="card-body">
        <h5>Campos sin grupo</h5>
        <ul class="list-group mb-3">
          @foreach($form->fields->whereNull('id_group') as $f)
            <li class="list-group-item">
              <strong>{{ $f->codigo }}</strong> — {{ $f->etiqueta }} ({{ $f->datatype }}, {{ $f->kind }})
              <div class="small text-muted">ID #{{ $f->id }}</div>

              @if($f->formula)
                <div class="mt-2">
                  <span class="badge bg-info">Fórmula:</span>
                  {{ $f->formula->expression }} → {{ $f->formula->output_type }}
              </div>
              @endif

              @if($f->source)
                <div class="mt-2">
                  <span class="badge bg-secondary">Fuente:</span>
                  {{ $f->source->source_kind }}
                </div>
              @endif

              <div class="mt-2">
                {{-- === Fórmula (solo para outputs) === --}}
                @if($f->kind === 'output')
                  <form method="post" action="{{ route('forms.fields.formula.upsert',[$form,$f]) }}" class="row g-1">
                    @csrf
                    <div class="col-8">
                      {{-- usa @ para que Blade no procese {{ }} del placeholder --}}
                      <input name="expression" class="form-control"
                            placeholder='@{{cantidad}} * @{{precio}}'
                            value="{{ optional($f->formula)->expression }}">
                    </div>
                    <div class="col-2">
                      <select name="output_type" class="form-select">
                        @foreach(['decimal','int','text','date','boolean'] as $t)
                          <option value="{{ $t }}" @selected(optional($f->formula)->output_type===$t)>{{ $t }}</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="col-2">
                      <button class="btn btn-sm btn-outline-primary w-100">Fórmula</button>
                    </div>
                  </form>
                @endif

                {{-- === Fuente (solo para selects/fk) === --}}
                @if(in_array($f->datatype, ['select','multiselect','fk']))
                  <form method="post" action="{{ route('forms.fields.source.upsert',[$form,$f]) }}" class="row g-1 mt-1">
                    @csrf
                    <div class="col-3">
                      <select name="source_kind" class="form-select">
                        @foreach(['table_column','ficha_attr','query','static_options'] as $k)
                          <option value="{{ $k }}" @selected(optional($f->source)->source_kind===$k)>{{ $k }}</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="col-3">
                      <input name="table_name" class="form-control" placeholder="tabla"
                            value="{{ optional($f->source)->table_name }}">
                    </div>
                    <div class="col-3">
                      <input name="column_name" class="form-control" placeholder="columna"
                            value="{{ optional($f->source)->column_name }}">
                    </div>
                    <div class="col-2">
                      <select name="multi_select" class="form-select">
                        <option value="0" @selected(!optional($f->source)->multi_select)>Único</option>
                        <option value="1" @selected(optional($f->source)->multi_select)>Múltiple</option>
                      </select>
                    </div>
                    <div class="col-1">
                      <button class="btn btn-sm btn-outline-secondary w-100">Fuente</button>
                    </div>

                    {{-- Extras para static_options / query --}}
                    <div class="col-12 mt-2">
                      <label class="form-label small">Opciones (JSON) – para static_options</label>
                      @php
                        $opts = optional($f->source)->options_json;
                        if (is_array($opts)) {
                            $opts = json_encode($opts, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
                        }
                      @endphp
                      <textarea name="options_json" class="form-control" rows="2"
                        placeholder='[{"value":"EXW","label":"EXW"}]'>{{ old('options_json', $opts) }}</textarea>
                    </div>
                    <div class="col-12 mt-2">
                      <label class="form-label small">Query SQL (SELECT value,label) – para query</label>
                      <textarea name="query_sql" class="form-control" rows="2"
                        placeholder="SELECT id AS value, nombre AS label FROM productos ORDER BY nombre">{{ optional($f->source)->query_sql }}</textarea>
                    </div>
                  </form>
                @endif

                {{-- Eliminar campo --}}
                <form method="post" action="{{ route('forms.fields.destroy',[$form,$f]) }}"
                      onsubmit="return confirm('Eliminar campo?')" class="mt-1">
                  @csrf @method('delete')
                  <button class="btn btn-sm btn-outline-danger">Eliminar</button>
                </form>
              </div>
            </li>
          @endforeach
        </ul>

        @foreach($form->groups as $g)
          <div class="border rounded p-3 mb-3">
            <div class="d-flex justify-content-between align-items-center">
              <h5 class="mb-0">
                {{ $g->nombre }}
                @if($g->repetible)
                  <span class="badge bg-warning text-dark ms-2">repetible</span>
                @endif
              </h5>
              <form method="post" action="{{ route('forms.groups.destroy',[$form,$g]) }}"
                    onsubmit="return confirm('¿Eliminar grupo?')">
                @csrf @method('delete')
                <button class="btn btn-sm btn-outline-danger">Eliminar grupo</button>
              </form>
            </div>

            <ul class="list-group mt-3">
              @foreach($g->fields as $f)
                <li class="list-group-item">
                  <div class="d-flex justify-content-between">
                    <div>
                      <strong>{{ $f->codigo }}</strong> — {{ $f->etiqueta }}
                      ({{ $f->datatype }}, {{ $f->kind }}) <span class="text-muted">ID #{{ $f->id }}</span>
                      @if($f->formula)
                        <span class="badge bg-info ms-2">Fórmula:</span>
                        <span class="ms-1">{{ $f->formula->expression }} → {{ $f->formula->output_type }}</span>
                      @endif
                      @if($f->source)
                        <span class="badge bg-secondary ms-2">Fuente:</span>
                        <span class="ms-1">{{ $f->source->source_kind }}</span>
                      @endif
                    </div>

                    {{-- Eliminar campo --}}
                    <form method="post" action="{{ route('forms.fields.destroy',[$form,$f]) }}"
                          onsubmit="return confirm('Eliminar campo?')">
                      @csrf @method('delete')
                      <button class="btn btn-sm btn-outline-danger">Eliminar</button>
                    </form>
                  </div>

                  {{-- Upsert fórmula (igual que para los campos sin grupo) --}}
                  <form method="post" action="{{ route('forms.fields.formula.upsert',[$form,$f]) }}"
                        class="row g-1 mt-2">
                    @csrf
                    <div class="col-md-8">
                      {{-- usa @ para que Blade no procese las llaves del placeholder --}}
                      <input name="expression" class="form-control"
                            placeholder='@{{cantidad}} * @{{precio}}'
                            value="{{ optional($f->formula)->expression }}">
                    </div>
                    <div class="col-md-2">
                      <select name="output_type" class="form-select">
                        @foreach(['decimal','int','text','date','boolean'] as $t)
                          <option value="{{ $t }}" @selected(optional($f->formula)->output_type===$t)>{{ $t }}</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="col-md-2">
                      <button class="btn btn-sm btn-outline-primary w-100">Fórmula</button>
                    </div>
                  </form>

                  {{-- Upsert fuente (igual que arriba) --}}
                  <form method="post" action="{{ route('forms.fields.source.upsert',[$form,$f]) }}"
                        class="row g-1 mt-1">
                    @csrf
                    <div class="col-md-3">
                      <select name="source_kind" class="form-select">
                        @foreach(['table_column','ficha_attr','query','static_options'] as $k)
                          <option value="{{ $k }}" @selected(optional($f->source)->source_kind===$k)>{{ $k }}</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="col-md-3">
                      <input name="table_name" class="form-control" placeholder="tabla"
                            value="{{ optional($f->source)->table_name }}">
                    </div>
                    <div class="col-md-3">
                      <input name="column_name" class="form-control" placeholder="columna"
                            value="{{ optional($f->source)->column_name }}">
                    </div>
                    <div class="col-md-2">
                      <select name="multi_select" class="form-select">
                        <option value="0" @selected(!optional($f->source)->multi_select)>Único</option>
                        <option value="1" @selected(optional($f->source)->multi_select)>Múltiple</option>
                      </select>
                    </div>
                    <div class="col-md-1">
                      <button class="btn btn-sm btn-outline-secondary w-100">Fuente</button>
                    </div>

                    {{-- (opcional) pega JSON de opciones o un SELECT si usas "query" --}}
                    <div class="col-12 mt-2">
                      <label class="form-label small">Opciones (JSON) – para static_options</label>
                      @php
                        $opts = optional($f->source)->options_json;
                        if (is_array($opts)) {
                            $opts = json_encode($opts, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
                        }
                      @endphp
                      <textarea name="options_json" class="form-control" rows="2"
                        placeholder='[{"value":"EXW","label":"EXW"}]'>{{ old('options_json', $opts) }}</textarea>
                    </div>
                    <div class="col-12 mt-2">
                      <label class="form-label small">Query SQL (SELECT value,label) – para query</label>
                      <textarea name="query_sql" class="form-control" rows="2"
                        placeholder="SELECT id AS value, nombre AS label FROM productos ORDER BY nombre">{{ optional($f->source)->query_sql }}</textarea>
                    </div>
                  </form>
                </li>
              @endforeach
            </ul>
          </div>
        @endforeach


        <div class="mt-3">
          <a href="{{ route('form-runs.create',['form_id'=>$form->id]) }}" class="btn btn-success">Probar Ejecución</a>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
