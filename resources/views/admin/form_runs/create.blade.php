@extends('layouts.dashboard')

@section('title')
  Ejecutar: {{ $form->nombre }}
@endsection

@section('page-title')
  Ejecutar: {{ $form->nombre }}
@endsection

@section('content-area')
<form method="post" action="{{ route('form-runs.store') }}" class="row g-3" enctype="multipart/form-data">
  @csrf
  <input type="hidden" name="form_id" value="{{ $form->id }}">

  @if ($errors->any())
    <div class="col-12">
      <div class="alert alert-danger">
        <ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
      </div>
    </div>
  @endif

  {{-- ================= CABECERA ================= --}}
  <div class="col-12"><h5>Datos</h5></div>

  @foreach($form->fields->whereNull('id_group')->sortBy('orden') as $f)
    @continue(!$f->visible) {{-- si visible=0 no renderiza, pero el back igual calculará si es output --}}
    <div class="col-md-3">
      <label class="form-label">
        {{ $f->etiqueta }} <span class="text-muted"></span>
        @if($f->requerido) <span class="text-danger">*</span> @endif
        @if($f->kind==='output' && $f->formula)
          <span class="badge bg-info ms-1">calc</span>
        @endif
      </label>

      @php
        $name = "fields[{$f->codigo}]";
        $dataAttrs = 'data-field-code="'.$f->codigo.'"';
        if ($f->kind==='output' && $f->formula) {
          $dataAttrs .= ' data-expression="'.e($f->formula->expression).'" data-output-type="'.$f->formula->output_type.'"';
        }
      @endphp

      @switch($f->datatype)
        @case('textarea')
          <textarea class="form-control" name="{{ $name }}" {!! $dataAttrs !!} {{ $f->kind==='output'?'readonly':'' }}></textarea>
        @break

        @case('int') @case('decimal')
          <input type="number" step="{{ $f->datatype==='decimal' ? '0.000001' : '1' }}"
                 class="form-control" name="{{ $name }}" {!! $dataAttrs !!}
                 {{ $f->kind==='output'?'readonly':'' }}>
        @break

        @case('date')
          <input type="date" class="form-control" name="{{ $name }}" {!! $dataAttrs !!} {{ $f->kind==='output'?'readonly':'' }}>
        @break

        @case('datetime')
          <input type="datetime-local" class="form-control" name="{{ $name }}" {!! $dataAttrs !!} {{ $f->kind==='output'?'readonly':'' }}>
        @break

        @case('boolean')
          <select class="form-select" name="{{ $name }}" {!! $dataAttrs !!} {{ $f->kind==='output'?'disabled':'' }}>
            <option value="0">No</option><option value="1">Sí</option>
          </select>
        @break

        
        @case('select') @case('multiselect') @case('fk')
          @php
            $opts = $options[$f->id] ?? [];
            $isMulti = $f->datatype==='multiselect';
            $cfg = $f->config_json ?? [];
            $onSelect = $cfg['on_select'] ?? null;   // <<-- mapping de autofill desde constructor
          @endphp

          <select class="form-select"
                  name="{{ $name }}{{ $isMulti ? '[]' : '' }}"
                  {!! $dataAttrs !!}
                  data-on-select='@json($onSelect)'   {{-- <<-- NUEVO --}}
                  {{ $isMulti ? 'multiple':'' }}
                  {{ $f->kind==='output'?'disabled':'' }}>
            @foreach($opts as $opt)
              <option value="{{ $opt['value'] }}"
                      data-meta='@json($opt['meta'] ?? [])'>  {{-- <<-- NUEVO --}}
                {{ $opt['label'] }}
              </option>
            @endforeach
          </select>
        @break

        @case('file')
          <input type="file" class="form-control" name="{{ $name }}" {!! $dataAttrs !!} {{ $f->kind==='output'?'disabled':'' }}>
        @break

        @default
          <input type="text" class="form-control" name="{{ $name }}" {!! $dataAttrs !!} {{ $f->kind==='output'?'readonly':'' }}>
      @endswitch
    </div>
  @endforeach

  {{-- ================= GRUPOS ================= --}}
  @foreach($form->groups->sortBy('orden') as $g)
    <div class="col-12 mt-4">
      <div class="d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
          {{ $g->nombre }}
          @if($g->repetible) <span class="badge bg-warning text-dark">repetible</span> @endif
        </h5>
        @if($g->repetible)
          <button type="button" class="btn btn-sm btn-outline-secondary js-add-row-global" data-group="{{ $g->nombre }}">Añadir fila</button>
        @endif
      </div>

      @php $rows = $g->repetible ? 1 : 1; @endphp
      <div class="group-rows" data-group="{{ $g->nombre }}">
        @for($i=0; $i<$rows; $i++)
          <div class="border rounded p-3 mb-2 form-group-row" data-group-name="{{ $g->nombre }}" data-row-index="{{ $i }}">
            <div class="row g-2">
              @foreach($g->fields->sortBy('orden') as $f)
                @continue(!$f->visible)
                @php
                  $base = "groups[{$g->nombre}][{$i}][{$f->codigo}]";
                  $attrs = 'data-field-code="'.$f->codigo.'"';
                  if ($f->kind==='output' && $f->formula) {
                    $attrs .= ' data-expression="'.e($f->formula->expression).'" data-output-type="'.$f->formula->output_type.'"';
                  }
                @endphp
                <div class="col-md-3">
                  <label class="form-label">
                    {{ $f->etiqueta }} <span class="text-muted"></span>
                    @if($f->requerido) <span class="text-danger">*</span> @endif
                    @if($f->kind==='output' && $f->formula) <span class="badge bg-info ms-1">calc</span> @endif
                  </label>

                  @switch($f->datatype)
                    @case('textarea')
                      <textarea class="form-control" name="{{ $base }}" {!! $attrs !!} {{ $f->kind==='output'?'readonly':'' }}></textarea>
                    @break

                    @case('int') @case('decimal')
                      <input type="number" step="{{ $f->datatype==='decimal' ? '0.000001' : '1' }}"
                             class="form-control" name="{{ $base }}" {!! $attrs !!}
                             {{ $f->kind==='output'?'readonly':'' }}>{{$f->formula}}
                    @break

                    @case('date')
                      <input type="date" class="form-control" name="{{ $base }}" {!! $attrs !!} {{ $f->kind==='output'?'readonly':'' }}>
                    @break

                    @case('datetime')
                      <input type="datetime-local" class="form-control" name="{{ $base }}" {!! $attrs !!} {{ $f->kind==='output'?'readonly':'' }}>
                    @break

                    @case('boolean')
                      <select class="form-select" name="{{ $base }}" {!! $attrs !!} {{ $f->kind==='output'?'disabled':'' }}>
                        <option value="0">No</option><option value="1">Sí</option>
                      </select>
                    @break

                    @case('select') @case('multiselect') @case('fk')
                      @php
                        $opts = $options[$f->id] ?? [];
                        $isMulti = $f->datatype==='multiselect';
                        $cfg = $f->config_json ?? [];
                        $onSelect = $cfg['on_select'] ?? null;
                      @endphp

                      <select class="form-select"
                              name="{{ $base }}{{ $isMulti ? '[]' : '' }}"
                              {!! $attrs !!}
                              data-on-select='@json($onSelect)'   {{-- <<-- NUEVO --}}
                              {{ $isMulti ? 'multiple':'' }}
                              {{ $f->kind==='output'?'disabled':'' }}>
                        @foreach($opts as $opt)
                          <option value="{{ $opt['value'] }}"
                                  data-meta='@json($opt['meta'] ?? [])'>  {{-- <<-- NUEVO --}}
                            {{ $opt['label'] }}
                          </option>
                        @endforeach
                      </select>
                    @break

                    @case('file')
                      <input type="file" class="form-control" name="{{ $base }}" {!! $attrs !!} {{ $f->kind==='output'?'disabled':'' }}>
                    @break

                    @default
                      <input type="text" class="form-control" name="{{ $base }}" {!! $attrs !!} {{ $f->kind==='output'?'readonly':'' }}>
                  @endswitch
                </div>
              @endforeach
            </div>

            @if($g->repetible)
              <div class="mt-2 d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary js-add-row">Añadir fila</button>
                <button type="button" class="btn btn-sm btn-outline-danger js-del-row">Eliminar fila</button>
              </div>
            @endif
          </div>
        @endfor
      </div>
    </div>
  @endforeach

  <div class="col-12 mt-3">
    <button class="btn btn-primary">Guardar</button>
  </div>
</form>

{{-- ============ JS: cálculo en vivo + filas dinámicas ============ --}}
<script>

  function dotGet(obj, path) {
    if (!path) return undefined;
    return path.split('.').reduce((o,k)=> (o && k in o) ? o[k] : undefined, obj);
  }

  function wireOnSelectAutofill(root) {
    root.addEventListener('change', (e) => {
      const sel = e.target;
      if (sel.tagName !== 'SELECT') return;

      const map = sel.dataset.onSelect ? JSON.parse(sel.dataset.onSelect) : null;
      if (!map) return;

      const opt = sel.options[sel.selectedIndex];
      if (!opt) return;

      const meta = opt.dataset.meta ? JSON.parse(opt.dataset.meta) : {};
      const scope = sel.closest('.form-group-row') || root;

      Object.entries(map).forEach(([destCode, metaPath]) => {
        const val = dotGet({meta}, metaPath); // ej. 'meta.precio'
        const target = scope.querySelector(`[data-field-code="${destCode}"]`);
        if (!target) return;

        if (target.tagName === 'SELECT' && target.multiple && Array.isArray(val)) {
          Array.from(target.options).forEach(o => o.selected = val.includes(o.value));
        } else {
          target.value = (val != null ? val : '');
        }
        target.dispatchEvent(new Event('input', { bubbles:true }));
        target.dispatchEvent(new Event('change', { bubbles:true }));
      });

      // Recalcular: cabecera y/o fila
      const row = sel.closest('.form-group-row');
      if (row) {
        recalcContainer(row, root);
      } else {
        recalcHeader(root);
        root.querySelectorAll('.form-group-row').forEach(r => recalcContainer(r, root));
      }
    });
  }


function getScopeValues(container) {
  const vals = {};
  container.querySelectorAll('[data-field-code]').forEach(el => {
    vals[el.dataset.fieldCode] = readValue(el);
  });
  return vals;
}

function evalExpression(expr, scope, fallbackScope = {}) {
  // si no existe la key en scope, intenta fallbackScope (cabecera)
  const replaced = expr.replace(/\{\{\s*([^}]+)\s*\}\}/g, (_, code) => {
    const key = String(code).trim();
    const has = Object.prototype.hasOwnProperty.call(scope, key);
    const val = has ? scope[key] : fallbackScope[key];
    if (Array.isArray(val)) return JSON.stringify(val);
    if (val === null || val === undefined || val === '') return '0';
    return (typeof val === 'number') ? String(val) : JSON.stringify(String(val));
  });
  const helpers = {
    concat: (...args) => args.join(''),
    round: (x, d=0) => Number.parseFloat(x).toFixed(d),
    min: Math.min, max: Math.max, abs: Math.abs,
  };
  try {
    const fn = new Function('helpers', `with(helpers){ return (${replaced}); }`);
    return fn(helpers);
  } catch { return ''; }
}


function castOutput(value, type) {
  switch (type) {
    case 'int':     return (value === '' ? '' : parseInt(value, 10) || 0);
    case 'decimal': return (value === '' ? '' : Number(value) || 0);
    case 'boolean': return (!!value) ? 1 : 0;
    default:        return value == null ? '' : value;
  }
}

function recalcContainer(container, root) {
  const scopeLocal = getScopeValues(container);
  const scopeGlobal = getGlobalScope(root);
  container.querySelectorAll('[data-expression]').forEach(outEl => {
    const expr = outEl.dataset.expression;
    const outType = outEl.dataset.outputType || 'text';
    const newVal = castOutput(evalExpression(expr, scopeLocal, scopeGlobal), outType);

    // escribe solo si cambió
    const oldVal = outEl.value;
    // Para selects/checkbox no tenemos outputs; si agregas, normaliza aquí
    if (String(oldVal) !== String(newVal)) {
      outEl.value = newVal;
      // NO dispares input/change aquí: eso causaba el loop
    }
  });
}

// === también recalcular outputs de cabecera (no están dentro de .form-group-row)
function recalcHeader(root) {
  const scopeGlobal = getGlobalScope(root);
  // Solo outputs que NO están dentro de una fila de grupo
  root.querySelectorAll('[data-expression]:not(.form-group-row [data-expression])').forEach(outEl => {
    const expr = outEl.dataset.expression;
    const outType = outEl.dataset.outputType || 'text';
    const newVal = castOutput(evalExpression(expr, scopeGlobal, scopeGlobal), outType);

    const oldVal = outEl.value;
    if (String(oldVal) !== String(newVal)) {
      outEl.value = newVal;
      // NO dispares input/change
    }
  });
}

function wireRealtimeCalc(root) {
  // 1) Recalcula al escribir/cambiar inputs del usuario
  const handler = (e) => {
    // si en algún momento quieres marcar eventos sintéticos:
    // if (e.detail && e.detail.synthetic) return;

    const row = e.target.closest('.form-group-row');
    if (row) {
      recalcContainer(row, root);
    } else {
      recalcHeader(root);
      // si algo de cabecera afecta las filas, recalcula filas también
      root.querySelectorAll('.form-group-row').forEach(r => recalcContainer(r, root));
    }
  };
  root.addEventListener('input', handler, { passive: true });
  root.addEventListener('change', handler, { passive: true });

  // 2) Recalculo inicial
  recalcHeader(root);
  root.querySelectorAll('.form-group-row').forEach(row => recalcContainer(row, root));
}

function wireRowButtons(root) {
  // Botones dentro de cada fila
  root.addEventListener('click', (e) => {
    // add row (dentro de la fila)
    if (e.target.classList.contains('js-add-row')) {
      const row = e.target.closest('.form-group-row');
      const wrapper = row.parentElement;
      addRow(wrapper, row);
    }
    // delete row
    if (e.target.classList.contains('js-del-row')) {
      const row = e.target.closest('.form-group-row');
      const wrapper = row.parentElement;
      const rows = wrapper.querySelectorAll('.form-group-row');
      if (rows.length > 1) {
        row.remove();
        renumberRows(wrapper);
      }
    }
    // add row (botón de la cabecera del grupo)
    if (e.target.classList.contains('js-add-row-global')) {
      const groupName = e.target.dataset.group;
      const wrapper = root.querySelector(`.group-rows[data-group="${groupName}"]`);
      if (!wrapper) return;
      const last = wrapper.querySelector('.form-group-row:last-child');
      addRow(wrapper, last);
    }
  });
}

function addRow(wrapper, rowToClone) {
  const clone = rowToClone.cloneNode(true);
  // limpia valores
  clone.querySelectorAll('[name]').forEach(inp => {
    if (inp.type === 'checkbox' || inp.type === 'radio') {
      inp.checked = false;
    } else if (inp.tagName === 'SELECT' && inp.multiple) {
      Array.from(inp.options).forEach(o => o.selected = false);
    } else {
      inp.value = '';
    }
  });
  wrapper.appendChild(clone);
  renumberRows(wrapper);
  recalcContainer(clone);
}

function renumberRows(wrapper) {
  wrapper.querySelectorAll('.form-group-row').forEach((r, idx) => {
    r.dataset.rowIndex = idx;
    r.querySelectorAll('[name]').forEach(inp => {
      inp.name = inp.name.replace(/\[\d+\]/, `[${idx}]`);
    });
  });
}

// === NUEVO: scope global (cabecera completa)
function getGlobalScope(root) {
  const g = {};
  root.querySelectorAll(':scope > .col-12 ~ [class^="col-"], :scope > [class^="col-"] [data-field-code]').forEach(()=>{});
  // Tomamos todos los elementos con data-field-code que NO estén dentro de .form-group-row
  root.querySelectorAll('[data-field-code]:not(.form-group-row [data-field-code])').forEach(el => {
    const code = el.dataset.fieldCode;
    g[code] = readValue(el);
  });
  return g;
}

function readValue(el){
  if (el.tagName === 'SELECT' && el.multiple) {
    return Array.from(el.selectedOptions).map(o => o.value);
  }
  if (el.type === 'number') return (el.value === '' ? null : Number(el.value));
  return el.value;
}



document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('form[action*="form-runs"]') || document.querySelector('form');
  if (!form) return;
  wireRealtimeCalc(form);
  wireRowButtons(form);
  wireOnSelectAutofill(form); // <<-- importante
});
</script>
@endsection
