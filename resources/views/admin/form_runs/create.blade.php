@extends('layouts.dashboard')

@section('title')
  {{ $form->nombre }}
@endsection

@section('page-title')
  {{ $form->nombre }}
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
      </label>

      @php
        $name = "fields[{$f->codigo}]";
        $dataAttrs = 'data-field-id="'.$f->id.'" data-field-code="'.$f->codigo.'"';
        if ($f->kind==='output' && $f->formulas) {
          $dataAttrs .= ' data-formula="'.e($f->formulas->expression).'"';
          $dataAttrs .= ' data-output-type="'.$f->formulas->output_type.'"';
        }
      @endphp

      @switch($f->datatype)
        @case('textarea')
          <textarea 
          class="form-control js-field" 
          name="{{ $name }}" 
          {!! $dataAttrs !!} 
          {{ $f->kind==='output'?'readonly':'' }}>
        </textarea>
        @break

        @case('int') @case('decimal')  @case('date') @case('datetime')
          @php
            $typeMap = [
              'int' => 'number',
              'decimal' => 'number',
              'date' => 'date',
              'datetime' => 'datetime-local',
            ];
            $inputType = $typeMap[$f->datatype] ?? 'text';
            $stepAttr = $f->datatype === 'decimal' ? '0.01' : ($f->datatype === 'int' ? '1' : null);
          @endphp
          <input
            class="form-control js-field"
            name="fields[{{ $f->codigo }}]"
            type="{{ $inputType }}"
            @if($stepAttr) step="{{ $stepAttr }}" @endif
            data-field-id="{{ $f->id }}"
            data-field-code="{{ $f->codigo }}"
            data-kind="{{ $f->kind }}"
            data-datatype="{{ $f->datatype }}"
            data-group-id="{{ $f->id_group ?? '' }}"
            data-row-index="{{ $rowIndex ?? 0 }}"
            @if($f->formulas)
              data-formula="{{ $f->formulas->expression }}"
              data-output-type="{{ $f->formulas->output_type }}"
              readonly
            @endif
          >
        @break

        @case('boolean')
          <select class="form-select" name="{{ $name }}" {!! $dataAttrs !!} {{ $f->kind==='output'?'disabled':'' }}>
            <option value="0">No</option><option value="1">Sí</option>
          </select>
        @break

        
        @case('select') @case('multiselect') @case('fk')
          @php
            
            $isMulti = $f->datatype==='multiselect';
            $cfg = $f->config_json ?? [];
            $onSelect = $cfg['on_select'] ?? null;
            $src = $f->source;
            $opts = $src && $src->options_json ? json_decode($src->options_json, true) : [];
          @endphp

          <select
            class="form-select js-field"
            name="fields[{{ $f->codigo }}]"
            data-field-id="{{ $f->id }}"
            data-field-code="{{ $f->codigo }}"
            data-kind="{{ $f->kind }}"                 {{-- input|output --}}
            data-datatype="{{ $f->datatype }}"         {{-- select|multiselect|... --}}
            data-group-id="{{ $f->id_group ?? '' }}"   {{-- vacío si es cabecera --}}
            data-row-index="{{ $rowIndex ?? 0 }}"      {{-- 0 si cabecera; en grupos pon el índice de fila --}}
            @if($src)
              data-source-kind="{{ $src->source_kind }}"               {{-- table|form|static_options --}}
              data-source-table="{{ $src->table_name ?? '' }}"
              data-source-column="{{ $src->column_name ?? '' }}"
              data-multi="{{ $src->multi_select ? 1 : 0 }}"
              @if($src->source_kind === 'table_table')
                data-tt-root-code="{{ $opts['root_code'] ?? '' }}"
                data-tt-root-table="{{ $opts['root_table'] ?? '' }}"
                data-tt-related="{{ $opts['related'] ?? '' }}"
              @endif
            @endif
            @if($f->formulas)
              data-formula="{{ $f->formulas->expression }}"                   {{-- ej: {{cantidad}} * {{precio}} --}}
              data-output-type="{{ $f->formulas->output_type }}"              {{-- decimal|int|... --}}
            @endif
          >
            {{-- Pintar opciones estáticas si las hay --}}
            @if(($src && $src->source_kind==='static_options') && !empty($opts))
              <option value="">-- seleccionar --</option>
              @foreach($opts as $opt)
                <option value="{{ $opt['value'] }}" 
                        data-meta='@json($opt['meta'] ?? [])'>
                  {{ $opt['label'] ?? $opt['value'] }}
                </option>
              @endforeach
            @endif
          </select>
        @break

        @case('file')
          <input type="file" class="form-control" name="{{ $name }}" {!! $dataAttrs !!} {{ $f->kind==='output'?'disabled':'' }}>
        @break

        @default
          <input type="text" data-kind="{{ $f->kind }}" class="form-control" name="{{ $name }}" {!! $dataAttrs !!} {{ $f->kind==='output'?'readonly':'' }} 
          @if($f->formulas)
            data-formula="{{ $f->formulas->expression }}"                   {{-- ej: {{cantidad}} * {{precio}} --}}
            data-output-type="{{ $f->formulas->output_type }}"              {{-- decimal|int|... --}}
          @endif
          >
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
          <button type="button" class="btn btn-sm btn-outline-secondary js-add-row-global" data-group="{{ $g->nombre }}">Añadir</button>
        @endif
      </div>

      @php $rows = $g->repetible ? 1 : 1; @endphp
      <div class="group-rows border rounded" data-group="{{ $g->nombre }}">
        @for($i=0; $i<$rows; $i++)
          <div class=" p-3 mb-2 form-group-row js-group-row" data-group-name="{{ $g->id }}" data-row-index="{{ $i }}">
            <div class="row g-2">
              @if($g->repetible)
                <button type="button" class="col-1 btn btn-outline-danger js-del-row">Eliminar</button>
              @endif
              @foreach($g->fields->sortBy('orden') as $f)
                @continue(!$f->visible)
                @php
                  $name = "groups[{$g->id}][{$i}][{$f->codigo}]";
                  $tpl  = "groups[{$g->id}][__i__][{$f->codigo}]";
                  $attrs = 'data-field-id="'.$f->id.'" data-field-code="'.$f->codigo.'"';
                  if ($f->kind==='output' && $f->formulas) {
                    $attrs .= ' data-formula="'.e($f->formulas->expression).'"';
                    $attrs .= ' data-output-type="'.$f->formulas->output_type.'"';
                  }
                @endphp
                <div class="col-md-2">
                  <label class="form-label">
                    {{ $f->etiqueta }} <span class="text-muted"></span>
                    @if($f->requerido) <span class="text-danger">*</span> @endif
                  </label>

                  @switch($f->datatype)
                    @case('textarea')
                      <textarea class="form-control" name="{{ $name }}" data-name-tpl="{{ $tpl }}" {!! $attrs !!} {{ $f->kind==='output'?'readonly':'' }}></textarea>
                    @break

                    @case('int') @case('decimal') @case('date') @case('datetime')
                      @php
                        $typeMap = [
                          'int' => 'number',
                          'decimal' => 'number',
                          'date' => 'date',
                          'datetime' => 'datetime-local',
                        ];
                        $inputType = $typeMap[$f->datatype] ?? 'text';
                        $stepAttr = $f->datatype === 'decimal' ? '0.01' : ($f->datatype === 'int' ? '1' : null);
                      @endphp
                      <input
                        class="form-control js-field"
                        name="{{$name}}"
                        data-name-tpl="{{ $tpl }}"
                        type="{{ $inputType }}"
                        @if($stepAttr) step="{{ $stepAttr }}" @endif
                        data-field-id="{{ $f->id }}"
                        data-field-code="{{ $f->codigo }}"
                        data-kind="{{ $f->kind }}"
                        data-datatype="{{ $f->datatype }}"
                        data-group-id="{{ $g->id ?? '' }}"
                        data-row-index="{{ $i ?? 0 }}"
                        @if($f->formulas)
                          data-formula="{{ $f->formulas->expression }}"
                          data-output-type="{{ $f->formulas->output_type }}"
                          readonly
                        @endif
                      >
                    @break

                    @case('boolean')
                      <select class="form-select" name="{{ $name }}" {!! $attrs !!} {{ $f->kind==='output'?'disabled':'' }}>
                        <option value="0">No</option><option value="1">Sí</option>
                      </select>
                    @break

                    @case('select') @case('multiselect') @case('fk')
                      @php
                        $isMulti = $f->datatype==='multiselect';
                        $cfg = $f->config_json ?? [];
                        $onSelect = $cfg['on_select'] ?? null;
                        $src = $f->source;
                        $opts = $src && $src->options_json ? json_decode($src->options_json, true) : [];
                      @endphp

                      <select
                        class="form-select js-field"
                        name="{{$name}}"
                        data-name-tpl="{{ $tpl }}"
                        data-field-id="{{ $f->id }}"
                        data-field-code="{{ $f->codigo }}"
                        data-kind="{{ $f->kind }}"                 {{-- input|output --}}
                        data-datatype="{{ $f->datatype }}"         {{-- select|multiselect|... --}}
                        data-group-id="{{ $g->id ?? '' }}"   {{-- vacío si es cabecera --}}
                        data-row-index="{{ $i ?? 0 }}"      {{-- 0 si cabecera; en grupos pon el índice de fila --}}
                        @if($src)
                          data-source-kind="{{ $src->source_kind }}"               {{-- table|form|static_options --}}
                          data-source-table="{{ $src->table_name ?? '' }}"
                          data-source-column="{{ $src->column_name ?? '' }}"
                          data-multi="{{ $src->multi_select ? 1 : 0 }}"
                          {{-- 👇 extras solo para table_table --}}
                          @if($src->source_kind === 'table_table')
                            data-tt-root-code="{{ $opts['root_code'] ?? '' }}"
                            data-tt-root-table="{{ $opts['root_table'] ?? '' }}"
                            data-tt-related="{{ $opts['related'] ?? '' }}"
                            
                          @endif
                        @endif
                        @if($f->formulas)
                          data-formula="{{ $f->formulas->expression }}"                   {{-- ej: {{cantidad}} * {{precio}} --}}
                          data-output-type="{{ $f->formulas->output_type }}"              {{-- decimal|int|... --}}
                        @endif
                      >
                        {{-- Pintar opciones estáticas si las hay --}}
                        @if(($src && $src->source_kind==='static_options') && !empty($opts))
                          <option value="">-- seleccionar --</option>
                          @foreach($opts as $opt)
                            <option value="{{ $opt['value'] }}" 
                                    data-meta='@json($opt['meta'] ?? [])'>
                              {{ $opt['label'] ?? $opt['value'] }}
                            </option>
                          @endforeach
                        @endif
                      </select>
                    @break



                    @case('file')
                      <input type="file" class="form-control" name="{{ $name }}" {!! $attrs !!} {{ $f->kind==='output'?'disabled':'' }}>
                    @break

                    @default
                      <input type="text" data-kind="{{ $f->kind }}" class="form-control" name="{{ $name }}" {!! $attrs !!} {{ $f->kind==='output'?'readonly':'' }} 
                        @if($f->formulas)
                          data-formula="{{ $f->formulas->expression }}"                   {{-- ej: {{cantidad}} * {{precio}} --}}
                          data-output-type="{{ $f->formulas->output_type }}"              {{-- decimal|int|... --}}
                        @endif
                        >
                  @endswitch
                </div>
              @endforeach
            </div>
            
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
  const FORM_ID_EMP = {{ $form->id_emp }};
</script>
@verbatim
<script>
  (function(){
    const $  = (s, c=document) => c.querySelector(s);
    const $$ = (s, c=document) => Array.from(c.querySelectorAll(s));
    const form = $('form[action*="form-runs"]') || $('form');
    if (!form) return;

    // ===== Helpers generales =====
    function readValue(el){
      if (el.tagName === 'SELECT' && el.multiple){
        return Array.from(el.selectedOptions).map(o=>o.value);
      }
      if (el.type === 'number'){
        return el.value==='' ? null : Number(el.value);
      }
      return el.value;
    }

    function getValue(el){
      if (el.tagName === 'SELECT' && el.multiple){
        return Array.from(el.selectedOptions).map(o=>o.value);
      }
      if (el.type === 'number'){
        const v = el.value.trim();
        return v==='' ? '' : Number(v);
      }
      return el.value;
    }

    function setValue(el, v){
      if (el.tagName === 'SELECT'){
        el.value = v;
      } else {
        el.value = v ?? '';
      }
    }

    // ===== Cache meta en cada SELECT =====
    function captureMeta(select){
      const opt = select.selectedOptions[0];
      if (!opt){ select._meta=null; return; }
      const metaStr = opt.getAttribute('data-meta');
      try{ select._meta = metaStr ? JSON.parse(metaStr) : {}; }
      catch{ select._meta = {}; }
      console.log('📦 Meta capturada para', select.dataset.fieldCode, select._meta);
    }

    // ===== lookup(meta) =====
    function lookupFn(scopeEl, fieldCode, path){
      const target = scopeEl.querySelector(`[data-field-code="${fieldCode}"]`);
      if (!target) return null;
      if (!('_meta' in target)) captureMeta(target);
      const meta = target._meta;
      if (!meta) return null;
      if (!path) return meta;
      return path.split('.').reduce((o,k)=> (o && k in o) ? o[k] : null, meta);
    }

    // ===== Eval cliente =====
    function evalFormula(scopeEl, expr, formRoot=document){
      if (!expr) return null;
      console.log('🧮 Eval fórmula', expr);

      // === Funciones especiales (ejemplo SUMTOTAL) ===
      expr = expr.replace(/SUMTOTAL\(\s*([a-zA-Z0-9_]+)(?:\.([a-zA-Z0-9_]+))?\s*\)/g, (_, code, attr)=>{
        const rows = formRoot.querySelectorAll(`.form-group-row [data-field-code="${code}"]`);
        let sum = 0;
        rows.forEach(el=>{
          let v = null;
          if (attr){ 
            // obtener atributo de meta
            if (!('_meta' in el)) captureMeta(el);
            const meta = el._meta || {};
            v = meta[attr];
          } else {
            // valor simple del input
            v = getValue(el);
          }

          if (v!=='' && v!=null && !isNaN(v)) sum += Number(v);
        });
        console.log('Σ SUMTOTAL', code, attr, '=', sum);
        return sum;
      });

      // === Reemplazar tokens {{codigo}} o {{codigo.attr}} ===
      const replaced = expr.replace(/{{\s*([a-zA-Z0-9_]+)(?:\.([a-zA-Z0-9_]+))?\s*}}/g,
        (_, code, attr)=>{
          let el = null;

          // 1. Buscar en la fila actual
          if (scopeEl instanceof HTMLElement && scopeEl.classList.contains('form-group-row')) {
            el = scopeEl.querySelector(`[data-field-code="${code}"]`);
          }

          // 2. Si no está en la fila → buscar en cabecera/global
          if (!el) {
            el = formRoot.querySelector(
              `.col-md-3 [data-field-code="${code}"], [data-field-code="${code}"]:not(.form-group-row [data-field-code])`
            );
          }

          if (!el) {
            console.warn('⚠️ Campo no encontrado para', code);
            return '0';
          }

          // Si pide atributo del select (ej: {{producto.precio}})
          if (attr){
            if (!('_meta' in el)) captureMeta(el);
            const meta = el._meta || {};
            return (meta[attr]!=null) ? String(meta[attr]) : '0';
          }

          // Valor simple
          const v = getValue(el);
          return (v===''||v==null||v==='-- seleccionar --') ? '0' : String(v);
        });

      console.log('📄 Expr final:', replaced);

      try{
        return Function(`"use strict"; return (${replaced});`)();
      }catch(e){
        console.error("❌ Error evaluando", expr, "->", replaced, e);
        return null;
      }
    }

    // ===== Eval vía API opcional =====
    async function evalOutputWithApi(outEl, root){
      const fieldId = parseInt(outEl.dataset.fieldId||'0',10);
      if (!fieldId) return;
      const outRow = outEl.closest('.form-group-row');
      const local  = outRow ? getScopeValues(outRow) : {};
      const global = getGlobalScope(root);
      const expr   = outEl.dataset.formula;

      console.log('🌐 EvalOutputWithApi', {fieldId, expr, local, global});

      try{
        const token = document.querySelector('meta[name="csrf-token"]').content;
        const rsp = await fetch('/formulas/eval',{
          method:'POST',
          headers:{'Content-Type':'application/json','X-CSRF-TOKEN':token},
          body:JSON.stringify({field_id:fieldId, local, global})
        });
        const data = await rsp.json();
        console.log('🌐 Respuesta backend', data);
        if (data && data.ok){
          const outType=outEl.dataset.outputType||'text';
          outEl.value = castOutput(data.value,outType);
        }
      }catch(e){
        console.error("❌ Error en evalOutputWithApi",e);
      }
    }

    function castOutput(value,type){
      switch(type){
        case 'int': return (value===''?'':parseInt(value,10)||0);
        case 'decimal': return (value===''?'':Number(value)||0);
        case 'boolean': return (!!value)?1:0;
        default: return value==null?'':value;
      }
    }

    function getScopeValues(container){
      const vals={};
      container.querySelectorAll('[data-field-code]').forEach(el=>{
        vals[el.dataset.fieldCode]=readValue(el);
      });
      console.log('📥 Scope values', vals);
      return vals;
    }

    function getGlobalScope(root){
      const g={};
      root.querySelectorAll('[data-field-code]:not(.form-group-row [data-field-code])').forEach(el=>{
        g[el.dataset.fieldCode]=readValue(el);
      });
      console.log('🌍 Global scope', g);
      return g;
    }

    // ===== Recalcular outputs de un scope =====
    function recalcScope(scope, formRoot=document){

      // recalcular outputs de esta fila o del documento completo
      $$('.js-field[data-formula]', scope).forEach(out=>{
        const expr = out.dataset.formula;
        const type = out.dataset.outputType || 'text';
        const value = evalFormula(scope, expr, formRoot);
        if (value == null){ out.value=''; return; }
        if (type==='decimal'){ out.value = Number(value).toFixed(2); }
        else if (type==='int'){ out.value = parseInt(value,10); }
        else { out.value = value; }
      });

      // Si scope es fila, recalcular también todos los outputs globales (fuera de esa fila)
      if (scope.classList && scope.classList.contains('form-group-row')){
        $$('.js-field[data-formula]', formRoot).forEach(out=>{
          // evita recalcular el mismo output de la fila actual (ya se hizo arriba)
          if (scope.contains(out)) return;

          const expr = out.dataset.formula;
          const type = out.dataset.outputType || 'text';
          const value = evalFormula(out.closest('.form-group-row') || formRoot, expr, formRoot);
          if (value == null){ out.value=''; return; }
          if (type==='decimal'){ out.value = Number(value).toFixed(2); }
          else if (type==='int'){ out.value = parseInt(value,10); }
          else { out.value = value; }
        });
      }
    }

    // ===== Eventos =====
    document.addEventListener('change', (e)=>{
      const el=e.target.closest('.js-field');
      if(!el) return;
      if(el.tagName==='SELECT') captureMeta(el);
      const scope=el.closest('.form-group-row')||document;
      recalcScope(scope);
    });

    document.addEventListener('input', (e)=>{
      const el=e.target.closest('.js-field');
      if(!el) return;
      const scope=el.closest('.form-group-row')||document;
      recalcScope(scope);
    });

    // ===== Boot inicial =====
    document.addEventListener('DOMContentLoaded', async ()=>{
      for(const s of $$('select.js-field[data-source-kind]')) await loadSelectOptions(s);
      recalcScope(document);
      $$('.form-group-row').forEach(r=>recalcScope(r));
    });

    // ===== Cargar opciones de selects dinámicos =====
    async function loadSelectOptions(select){
      const kind   = select.dataset.sourceKind;
      const table  = select.dataset.sourceTable;
      const column = select.dataset.sourceColumn;
      const multi  = select.dataset.multi==='1';
      if (!kind || kind==='static_options') return;
      const scopeEl = select.closest('.form-group-row') || form;

      // helper para siempre agregar el "-- seleccionar --"
      function addDefaultOption(select){
        const defaultOpt = document.createElement('option');
        defaultOpt.value = '';
        defaultOpt.textContent = '-- seleccionar --';
        select.appendChild(defaultOpt);
      }

      // Helper: busca un campo por data-field-code dentro del scope primero, si no en global
      function findFieldByCode(code){
        if (!code) return null;
        // 1) dentro de la fila (si existe)
        const inRow = scopeEl.querySelector(`[data-field-code="${code}"]`);
        if (inRow) return inRow;
        // 2) fallback: cabecera/global
        return form.querySelector(`.col-md-3 [data-field-code="${code}"], [data-field-code="${code}"]:not(.form-group-row [data-field-code])`);
      }

      if (kind==='table' && table && column){
        const url=`/api/datasource/table-options?table=${encodeURIComponent(table)}&label=${encodeURIComponent(column)}&id_emp=${FORM_ID_EMP}`;
        try{
          const res=await fetch(url,{headers:{'Accept':'application/json'}});
          if(!res.ok) return;
          const items=await res.json();
          // SIEMPRE poner la opción inicial
          select.innerHTML = '';
          addDefaultOption(select);
          items.forEach(it=>{
            const opt=document.createElement('option');
            opt.value=it.value;
            opt.textContent=it.label??it.value;
            opt.dataset.meta=JSON.stringify(it.meta||{});
            select.appendChild(opt);
          });
        }catch(err){ console.error("⚡ Error cargando options",err); }
        return;
      }
      // === NUEVO: TABLE_TABLE
      if (kind === 'table_table') {
        const rootCode  = select.dataset.ttRootCode;   // código del campo padre (ej: cliente)
        const rootTable = select.dataset.ttRootTable;  // tabla base (ej: cliente)
        const related   = select.dataset.ttRelated;    // tabla hija (ej: contacto)
        if (!rootCode || !related) return;

        // Buscar el select padre primero en la misma fila (si existe)
        const parentSelect = findFieldByCode(rootCode);
        if (!parentSelect) {
          console.warn('⚠️ No se encontró el select padre', {rootCode, scope: scopeEl});
          return;
        }

        // Al inicio: vacío
        select.innerHTML = '<option value="">-- seleccionar --</option>';

        const loadChildren = async () => {
          const parentVal = parentSelect.value;
          select.innerHTML = '';
          addDefaultOption(select);
          if (!parentVal) return;

          const url = `/api/datasource/table-table-options?root_table=${encodeURIComponent(rootTable)}&related=${encodeURIComponent(related)}&parent_id=${parentVal}&id_emp=${FORM_ID_EMP}`;
          try {
            const res = await fetch(url, { headers: { 'Accept':'application/json' } });
            if (!res.ok) return;
            const items = await res.json();
            //console.log('📦 Opciones cargadas (table_table)', { rootCode, parentVal, items });

            items.forEach(it => {
              const opt = document.createElement('option');
              opt.value = it.value;
              opt.textContent = it.label ?? it.value;
              opt.dataset.meta = JSON.stringify(it.meta || {});
              select.appendChild(opt);
            });
          } catch (err) {
            console.error("⚡ Error cargando table_table options", err);
          }
        };

        // Evita duplicar listeners si se vuelve a llamar loadSelectOptions sobre el mismo select
        if (!select._ttBound) {
          parentSelect.addEventListener('change', loadChildren);
          select._ttBound = true;
        }

        // ⚡ Carga inicial por si la fila ya trae un valor en el padre
        await loadChildren();
      }
    }


    // ===== Botones dinámicos de grupos =====
    form.addEventListener('click', async (e)=>{
      // Añadir fila
      if (e.target.classList.contains('js-add-row-global')) {
        const groupName = e.target.dataset.group;
        const wrapper = form.querySelector(`.group-rows[data-group="${groupName}"]`);
        if (!wrapper) return;
        const last = wrapper.querySelector('.form-group-row:last-child');
        if (!last) return;

        const clone = last.cloneNode(true);
        // limpiar inputs de la fila clonada
        clone.querySelectorAll('[name]').forEach(inp=>{
          if (inp.type === 'checkbox' || inp.type === 'radio') {
            inp.checked = false;
          } else if (inp.tagName === 'SELECT' && inp.multiple) {
            Array.from(inp.options).forEach(o=>o.selected=false);
          } else {
            inp.value = '';
            if (inp.tagName === 'SELECT') {
              inp.innerHTML = '<option value="">-- seleccionar --</option>';
            }
          }
        });

        wrapper.appendChild(clone);
        // 🔧 inicializar selects de la nueva fila
        for (const s of clone.querySelectorAll('select.js-field[data-source-kind]')) {
          await loadSelectOptions(s);
        }
        renumberRows(wrapper);

        // recalcular solo la fila recién clonada
        recalcScope(clone, form);

        console.log('➕ Fila añadida al grupo', groupName);
      }

      // Eliminar fila
      if (e.target.classList.contains('js-del-row')) {
        const row = e.target.closest('.form-group-row');
        const wrapper = row.parentElement;

        // Si hay más de una fila, eliminar
        if (wrapper.querySelectorAll('.form-group-row').length > 1) {
          row.remove();
          renumberRows(wrapper);
          console.log('🗑️ Fila eliminada');
          // 🔄 Recalcular TODO el formulario
          recalcScope(form, form);
        } else {
          console.warn('⚠️ No se puede eliminar la única fila');
        }
      }
    });

    // ===== Renumerar filas =====
    function renumberRows(wrapper){
      wrapper.querySelectorAll('.js-group-row').forEach((row,idx)=>{
        row.dataset.rowIndex = idx;
        row.querySelectorAll('[name][data-name-tpl]').forEach(inp=>{
          inp.name = inp.dataset.nameTpl.replace('__i__', idx);
        });
        row.querySelectorAll('[data-row-index]').forEach(el=>{
          el.dataset.rowIndex = idx;
        });
      });
    }

  })();
</script>
@endverbatim

@endsection
