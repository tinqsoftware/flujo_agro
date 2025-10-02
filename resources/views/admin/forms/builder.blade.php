@extends('layouts.dashboard')

@section('title')
  Creador: {{ $form->nombre }} (ID {{ $form->id }})
@endsection

@section('page-title')
  Creando: {{ $form->nombre }} (ID {{ $form->id }})
@endsection

@section('content-area')

{{-- ====== Barra superior: meta del Form + botones ====== --}}
<div class="mb-3">
  <form method="post" action="{{ route('forms.update',$form) }}" class="row g-2">
    @csrf @method('put')

    <div class="col-md-5">
      <label class="form-label">Nombre</label>
      <input name="nombre" class="form-control" value="{{ $form->nombre }}" required>
    </div>

    <div class="col-md-1">
      <label class="form-label">Correlativo</label>
      <select name="usa_correlativo" class="form-select">
        <option value="0" @selected(!$form->usa_correlativo)>No</option>
        <option value="1" @selected($form->usa_correlativo)>Sí</option>
      </select>
    </div>
    <div class="col-md-1">
      <label class="form-label">Prefijo</label>
      <input name="correlativo_prefijo" class="form-control" value="{{ $form->correlativo_prefijo }}">
    </div>
    <div class="col-md-1">
      <label class="form-label">Sufijo</label>
      <input name="correlativo_sufijo" class="form-control" value="{{ $form->correlativo_sufijo }}">
    </div>
    <div class="col-md-1">
      <label class="form-label">Padding</label>
      <input name="correlativo_padding" type="number" class="form-control" value="{{ $form->correlativo_padding }}">
    </div>

    <div class="col-md-1 d-flex align-items-end">
      <button class="btn btn-primary w-100">Guardar</button>
    </div>

    <div class="col-md-1 d-flex align-items-end">
      <button type="button" class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#modalNewGroup">
        + Grupo
      </button>
    </div>

    <div class="col-md-1 d-flex align-items-end">
      <button type="button" class="btn btn-success w-100"
              data-bs-toggle="modal" data-bs-target="#modalNewField"
              data-mode="create">
        + Campo
      </button>
    </div>
  </form>
</div>


<div class="row">
  <div class="col-md-5">
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <span>Estructura</span>
        <a href="{{ route('form-runs.create',['form_id'=>$form->id]) }}" class="btn btn-sm btn-outline-success">Probar ejecución</a>
      </div>
      <div class="card-body">

        {{-- ======= Cabeceras (sin grupo) ======= --}}
        <h6 class="text-muted">Campos sin grupo</h6>
        <ul class="list-group mb-3 js-fields-sortable" data-group-id="">
          @foreach($form->fields->whereNull('id_group') as $f)
            @php
              $jsonField   = \Illuminate\Support\Arr::only($f->getAttributes(), [
                'id','id_group','codigo','etiqueta','descripcion','kind','datatype','requerido','unico','orden','visible'
              ]);
              $jsonSource  = optional($f->source)?->only([
                'source_kind','table_name','column_name','options_json','multi_select'
              ]);
              $jsonFormula = optional($f->formula)?->only(['expression','output_type']);
            @endphp
            <li class="list-group-item d-flex justify-content-between align-items-start js-field"
                data-field-id="{{ $f->id }}">
              <div class="me-2">
                <div class="small text-muted">ID #{{ $f->id }}</div>
                <strong>{{ $f->codigo }}</strong> — {{ $f->etiqueta }}
                <small class="text-muted">
                  ({{ $f->datatype }}, {{ $f->kind }})
                  @if($f->source)
                    <span class="badge bg-secondary ms-2">{{ $f->source->source_kind }}</span>
                  @endif
                  @if($f->formula)
                    <span class="badge bg-info ms-2">Fórmula</span>
                  @endif
                </small>

                @if($f->source && $f->source->source_kind==='table')
                  <div class="small text-muted">
                    <strong>Tabla:</strong> {{ $f->source->table_name }} —
                    <strong>Columna:</strong> {{ $f->source->column_name }}
                  </div>
                @endif
                @if($f->source && $f->source->source_kind==='table_table')
                  @php $meta = json_decode($f->source->options_json ?: '{}', true) ?: []; @endphp
                  <div class="small text-muted">
                    <strong>Base:</strong> {{ $meta['root'] ?? '-' }} —
                    <strong>Relacionado:</strong> {{ $meta['related'] ?? '-' }}
                  </div>
                @endif
              </div>

              <div class="d-flex gap-2">
                <button type="button"
                        class="btn btn-sm btn-outline-primary js-open-edit"
                        data-bs-toggle="modal" data-bs-target="#modalNewField"
                        data-mode="edit"
                        data-field='@json($jsonField)'
                        data-source='@json($jsonSource)'
                        data-formula='@json($jsonFormula)'>
                  Editar
                </button>

                <form method="post" action="{{ route('forms.fields.destroy',[$form,$f]) }}"
                      onsubmit="return confirm('¿Eliminar campo?')">
                  @csrf @method('delete')
                  <button class="btn btn-sm btn-outline-danger">Eliminar</button>
                </form>
              </div>
            </li>
          @endforeach
        </ul>

        {{-- ======= Grupos ======= --}}
        <div id="js-groups-sortable">
          @foreach($form->groups as $g)
            <div class="border rounded p-3 mb-3 js-group" data-group-id="{{ $g->id }}">
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

              <ul class="list-group mt-3 js-fields-sortable" data-group-id="{{ $g->id }}">
                @foreach($g->fields as $f)
                  @php
                    $jsonField   = \Illuminate\Support\Arr::only($f->getAttributes(), [
                      'id','id_group','codigo','etiqueta','descripcion','kind','datatype','requerido','unico','orden','visible'
                    ]);
                    $jsonSource  = optional($f->source)?->only([
                      'source_kind','table_name','column_name','options_json','multi_select'
                    ]);
                    $jsonFormula = optional($f->formula)?->only(['expression','output_type']);
                  @endphp

                  <li class="list-group-item js-field" data-field-id="{{ $f->id }}">
                    <div class="d-flex justify-content-between align-items-start">
                      <div>
                        <div class="small text-muted">ID #{{ $f->id }}</div>
                        <strong>{{ $f->codigo }}</strong> — {{ $f->etiqueta }}
                        <small class="text-muted">
                          ({{ $f->datatype }}, {{ $f->kind }})
                          @if($f->source)
                            <span class="badge bg-secondary ms-2">{{ $f->source->source_kind }}</span>
                          @endif
                          @if($f->formula)
                            <span class="badge bg-info ms-2">Fórmula</span>
                          @endif
                        </small>

                        @if($f->source && $f->source->source_kind==='table')
                          <div class="small text-muted">
                            <strong>Tabla:</strong> {{ $f->source->table_name }} —
                            <strong>Columna:</strong> {{ $f->source->column_name }}
                          </div>
                        @endif
                        @if($f->source && $f->source->source_kind==='table_table')
                          @php $meta = json_decode($f->source->options_json ?: '{}', true) ?: []; @endphp
                          <div class="small text-muted">
                            <strong>Base:</strong> {{ $meta['root'] ?? '-' }} —
                            <strong>Relacionado:</strong> {{ $meta['related'] ?? '-' }}
                          </div>
                        @endif
                      </div>

                      <div class="d-flex gap-2">
                        <button type="button"
                                class="btn btn-sm btn-outline-primary js-open-edit"
                                data-bs-toggle="modal" data-bs-target="#modalNewField"
                                data-mode="edit"
                                data-field='@json($jsonField)'
                                data-source='@json($jsonSource)'
                                data-formula='@json($jsonFormula)'>
                          Editar
                        </button>

                        <form method="post" action="{{ route('forms.fields.destroy',[$form,$f]) }}"
                              onsubmit="return confirm('¿Eliminar campo?')">
                          @csrf @method('delete')
                          <button class="btn btn-sm btn-outline-danger">Eliminar</button>
                        </form>
                      </div>
                    </div>
                  </li>
                @endforeach
              </ul>

            </div>
          @endforeach
        </div>

      </div>
    </div>
  </div>
</div>


{{-- ======================= Modal: Nuevo Grupo ======================= --}}
<div class="modal fade" id="modalNewGroup" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="{{ route('forms.groups.store',$form) }}" class="modal-content">
      @csrf
      <div class="modal-header">
        <h5 class="modal-title">Nuevo Grupo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
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
        <input name="orden" type="number" class="form-control" value="0">
      </div>
      <div class="modal-footer">
        <button class="btn btn-success">Agregar Grupo</button>
      </div>
    </form>
  </div>
</div>


{{-- ======================= Modal: Campo (crear/editar) ======================= --}}
<div class="modal fade" id="modalNewField" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Campo</h5></div>
      <div class="modal-body">

        <form id="js-new-field-form" method="POST" action="{{ route('forms.fields.store',$form) }}">
          @csrf
          <input type="hidden" name="id_form" value="{{ $form->id }}">

          <div class="row g-3">
            <div class="col-md-3">
              <label>Grupo</label>
              <select name="id_group" class="form-select">
                <option value="">(sin grupo)</option>
                @foreach($form->groups as $g)
                  <option value="{{ $g->id }}">{{ $g->nombre }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-2">
              <label>Código</label>
              <input type="text" name="codigo" class="form-control" required>
            </div>
            <div class="col-md-3">
              <label>Etiqueta</label>
              <input type="text" name="etiqueta" class="form-control" required>
            </div>
            <div class="col-md-2">
              <label>Kind</label>
              <select name="kind" class="form-select js-kind" required>
                <option value="input">Input</option>
                <option value="output">Output</option>
              </select>
            </div>
            <div class="col-md-2">
              <label>Tipo</label>
              <select name="datatype" class="form-select js-datatype" required>
                <option value="text">Texto</option>
                <option value="textarea">Textarea</option>
                <option value="int">Entero</option>
                <option value="decimal">Decimal</option>
                <option value="date">Fecha</option>
                <option value="datetime">Datetime</option>
                <option value="bool">Boolean</option>
                <option value="select">Select</option>
                <option value="multiselect">MultiSelect</option>
                <option value="fk">FK</option>
              </select>
            </div>
          </div>

          {{-- ======= Sección: Origen/Fuente ======= --}}
          <div class="row g-3 mt-3 js-section-source d-none">
            <div class="col-md-3">
              <label>Origen (Source)</label>
              <select name="source_kind" class="form-select js-source-kind">
                <option value="">--</option>
                <option value="table">Table</option>
                <option value="table_table">Table-Table</option>
                <option value="form">Form</option>
                <option value="static_options">Static Options</option>
                <option value="form_actual">FormActual</option> <!-- 👈 NUEVO -->
              </select>
            </div>

            {{-- ---- SOURCE: TABLE ---- --}}
            <div class="col-md-9 js-src-table d-none">
              <div class="row g-3">
                <div class="col-md-5">
                  <label>Tabla</label>
                  <select name="table_name" class="form-select" id="js-table-name"></select>
                </div>
                <div class="col-md-5">
                  <label>Columna (etiqueta)</label>
                  <select name="column_name" class="form-select" id="js-column-name"></select>
                </div>
              </div>
            </div>

            {{-- ---- SOURCE: TABLE-TABLE ---- --}}
            <div class="col-md-9 js-src-tt d-none">
              <div class="row g-3">
                <div class="col-md-5">
                  <label>Tabla base</label>
                  <select class="form-select" id="js-tt-root"></select>
                </div>
                <div class="col-md-5">
                  <label>Relacionado con</label>
                  <select class="form-select" id="js-tt-related"></select>
                </div>
              </div>
              {{-- guardaremos estas 2 selecciones en options_json desde el JS al enviar --}}
              <input type="hidden" name="tt_root">
              <input type="hidden" name="tt_root_table">
              <input type="hidden" name="tt_related">
            </div>

            <div class="col-md-9" id="source-extra-box">
              {{-- dinámico --}}
            </div>

            {{-- ---- SOURCE: FORM ---- --}}
            <div class="col-md-9 js-src-form d-none">
              <div class="row g-3">
                <div class="col-md-6">
                  <label>Formulario</label>
                  <select class="form-select" name="source_form_id" id="js-src-form-id"></select>
                </div>
                <div class="col-md-6">
                  <label>Campo (del formulario)</label>
                  <select class="form-select" name="source_field_code" id="js-src-form-field"></select>
                </div>
              </div>
            </div>

            {{-- ---- SOURCE: FORMACTUAL  ---- --}}
            <div class="col-md-9 js-src-formactual d-none">
              <div class="row g-3">
                <div class="col-md-6">
                  <label>Grupo del formulario actual</label>
                  <select class="form-select" name="fa_group_id" id="js-fa-group"></select>
                </div>
                <div class="col-md-6">
                  <label>Campo (del grupo)</label>
                  <select class="form-select" name="fa_field_code" id="js-fa-field"></select>
                </div>
              </div>
            </div>

            {{-- ---- SOURCE: STATIC OPTIONS ---- --}}
            <div class="col-md-9 js-src-static d-none" id="js-static-options">
              <div class="d-flex gap-2 mb-2">
                <input class="form-control form-control-sm" placeholder="Value">
                <input class="form-control form-control-sm" placeholder="Label">
                <button type="button" class="btn btn-sm btn-outline-primary js-add-opt">+ Añadir</button>
              </div>
              <div class="table-responsive">
                <table class="table table-sm align-middle mb-2">
                  <thead><tr><th>Value</th><th>Label</th><th></th></tr></thead>
                  <tbody></tbody>
                </table>
              </div>
              <textarea name="options_json" class="form-control" rows="3" placeholder="[]"></textarea>
            </div>

            {{-- Multi-select switch (cuando hay fuente de selección) --}}
            <div class="col-md-3 js-section-multi d-none">
              <label>Múltiple</label>
              <select name="multi_select" class="form-select">
                <option value="0">No</option>
                <option value="1">Sí</option>
              </select>
            </div>
          </div>

          {{-- ======= Sección: Fórmula ======= --}}
          <div class="row g-3 mt-3 js-section-formula d-none">
              <div class="col-md-4">
                <label>Campo del formulario</label>
                <select id="js-formula-fields" class="form-select"></select>
              </div>
              <div class="col-md-4">
                <label>Atributo</label>
                <select id="js-formula-attrs" class="form-select"></select>
              </div>
              <div class="col-md-4">
                <label>Insertar</label><br>
                <button type="button" class="btn btn-sm btn-outline-primary" id="js-insert-formula">
                  Insertar en fórmula
                </button>
              </div>
            <div class="col-md-9">
              <label>Fórmula</label>
              <textarea name="formula_expression" id="js-formula-expression" class="form-control" rows="3" placeholder="expresión..."></textarea>
            </div>
            <div class="col-md-3">
              <label>Tipo resultado</label>
              <select name="formula_output_type" class="form-select">
                <option value="text">text</option>
                <option value="textarea">textarea</option>
                <option value="int">int</option>
                <option value="decimal">decimal</option>
                <option value="date">date</option>
                <option value="datetime">datetime</option>
                <option value="bool">bool</option>
                <option value="json">json</option>
              </select>
            </div>
          </div>

          {{-- ======= Propiedades varias ======= --}}
          <div class="row g-3 mt-3">
            <div class="col-md-2">
              <label>Orden</label>
              <input type="number" name="orden" class="form-control" value="0">
            </div>
            <div class="col-md-2">
              <label>Requerido</label>
              <select name="requerido" class="form-select">
                <option value="0">No</option>
                <option value="1">Sí</option>
              </select>
            </div>
            <div class="col-md-2">
              <label>Único</label>
              <select name="unico" class="form-select">
                <option value="0">No</option>
                <option value="1">Sí</option>
              </select>
            </div>
            <div class="col-md-2">
              <label>Visible</label>
              <select name="visible" class="form-select">
                <option value="1">Sí</option>
                <option value="0">No</option>
              </select>
            </div>
            <div class="col-md-4 d-flex align-items-end justify-content-end">
              <button class="btn btn-success px-4">Guardar</button>
            </div>
          </div>
        </form>

      </div>
    </div>
  </div>
</div>


{{-- ===== Sortable ===== --}}
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>

<script>
(function(){
  const $ = (sel, ctx=document) => ctx.querySelector(sel);
  const $$ = (sel, ctx=document) => Array.from(ctx.querySelectorAll(sel));
  const token = '{{ csrf_token() }}';
  

  // ---------- Drag & drop ----------
  const groupsWrap = $('#js-groups-sortable');
  if (groupsWrap) {
    new Sortable(groupsWrap, {
      handle: '.js-group',
      draggable: '.js-group',
      animation: 150,
      onEnd: saveOrder
    });

    $$('.js-fields-sortable').forEach(list => {
      new Sortable(list, {
        group: 'fields',
        draggable: '.js-field',
        animation: 150,
        onEnd: saveOrder
      });
    });

    function saveOrder(){
      const groups = $$('.js-group').map((g, idx)=>({ id: +g.dataset.groupId, orden: idx }));
      const fields = [];
      $$('.js-fields-sortable').forEach(ul=>{
        const gid = ul.dataset.groupId || null;
        $$('.js-field', ul).forEach((li, i)=>{
          fields.push({ id:+li.dataset.fieldId, orden:i, id_group: gid? +gid : null });
        });
      });

      fetch('{{ route('forms.reorder',$form) }}', {
        method:'POST',
        headers:{'X-CSRF-TOKEN':token,'Content-Type':'application/json'},
        body: JSON.stringify({groups, fields})
      }).catch(()=>{});
    }
  }

  // ---------- Builder (modal Campo) ----------
  const form = $('#js-new-field-form');
  if (!form) return;

  // toggles
  const selKind = $('.js-kind', form);
  const selType = $('.js-datatype', form);
  const boxSource = $('.js-section-source', form);
  const boxFormula= $('.js-section-formula', form);
  const boxMulti  = $('.js-section-multi', form);
  const selSrcKind= $('.js-source-kind', form);

  const partTable = $('.js-src-table', form);
  const partTT    = $('.js-src-tt', form);
  const partForm  = $('.js-src-form', form);
  const partStatic= $('.js-src-static', form);

  function toggleSections(){
    const kind = selKind.value;
    const dt   = selType.value;
    const isSelectLike = ['select','multiselect','fk'].includes(dt);

    // Fuente visible solo si es input + select-like
    boxSource.classList.toggle('d-none', !(isSelectLike && kind==='input'));
    // Multi solo cuando hay fuente visible
    boxMulti.classList.toggle('d-none', !(isSelectLike && kind==='input'));
    // Fórmula visible para outputs NO select-like
    boxFormula.classList.toggle('d-none', !(kind==='output' && !isSelectLike));
  }
  selKind.addEventListener('change', async ()=>{
    toggleSections();
    if (selKind.value==='output') {
      await loadFormulaContext();
    }
  });
  selType.addEventListener('change', toggleSections);
  toggleSections();

  function toggleSourceBlocks(){
    const partFormActual = $('.js-src-formactual', form);
    const k = selSrcKind.value;
    [partTable, partTT, partForm, partStatic].forEach(el => el.classList.add('d-none'));
    if (k==='table') partTable.classList.remove('d-none');
    if (k==='table_table') partTT.classList.remove('d-none');
    if (k==='form') partForm.classList.remove('d-none');
    if (k==='static_options') partStatic.classList.remove('d-none');
    if (k==='form_actual') {
      partFormActual.classList.remove('d-none');
      // Llenar select de grupos con los del form actual
      const grupos = @json($form->groups->map(fn($g)=>['id'=>$g->id,'nombre'=>$g->nombre]));
      $('#js-fa-group').innerHTML = '<option value="">--</option>' +
        grupos.map(g=>`<option value="${g.id}">${g.nombre}</option>`).join('');
    }
  }

  $('#js-fa-group').addEventListener('change', e=>{
    const gid = e.target.value;
    if (!gid){ $('#js-fa-field').innerHTML='<option value="">--</option>'; return; }
    // Buscar los fields del grupo
    const fields = @json(
      $form->groups->mapWithKeys(fn($g)=>[$g->id => $g->fields->map(fn($f)=>['codigo'=>$f->codigo,'etiqueta'=>$f->etiqueta])])
    );
    const rows = fields[gid] || [];
    $('#js-fa-field').innerHTML = '<option value="">--</option>' +
      rows.map(r=>`<option value="${r.codigo}">${r.codigo} — ${r.etiqueta}</option>`).join('');
  });


  selSrcKind.addEventListener('change', toggleSourceBlocks);

  // ---- AJAX helpers ----
  async function getJSON(url){ const r = await fetch(url); return r.json(); }

  // TABLE => cargar tablas y columnas
  const selTable = $('#js-table-name');
  const selCol   = $('#js-column-name');

  async function loadTables(){
    selTable.innerHTML = '<option value="">Cargando...</option>';
    const rows = await getJSON('{{ route('forms.sources.tables',$form) }}');
    selTable.innerHTML = '<option value="">--</option>' + rows.map(r=>`<option value="${r.value}">${r.label}</option>`).join('');
  }
  async function loadColumns(table){
    if (!table) { selCol.innerHTML = '<option value="">--</option>'; return; }
    selCol.innerHTML = '<option value="">Cargando...</option>';
    const rows = await getJSON('{{ route('forms.sources.columns',$form) }}' + '?table='+encodeURIComponent(table));
    selCol.innerHTML = rows.map(r=>`<option value="${r.value}">${r.label}</option>`).join('');
  }
  selTable && selTable.addEventListener('change', e=>loadColumns(e.target.value));

  // TABLE-TABLE => base y relacionado
  const selRoot = $('#js-tt-root');
  const selRel  = $('#js-tt-related');

  async function loadRootTables(){
    selRoot.innerHTML = '<option value="">Cargando...</option>';
    // nuevo endpoint que devuelve campos del form actual
    const rows = await getJSON('{{ route('forms.sources.table_table_root',$form) }}');
    selRoot.innerHTML = '<option value="">--</option>' + rows.map(r=>
      `<option value="${r.value}" data-table="${r.table}">${r.label}</option>`
    ).join('');
    selRel.innerHTML = '<option value="">--</option>';
  }

  async function loadRelated(rootCode){
    if (!rootCode) { selRel.innerHTML = '<option value="">--</option>'; return; }
    const opt = selRoot.querySelector(`option[value="${rootCode}"]`);
    const rootTable = opt ? opt.dataset.table : null;

    selRel.innerHTML = '<option value="">Cargando...</option>';
    const rows = await getJSON('{{ route('forms.sources.table_table',$form) }}' + '?root='+encodeURIComponent(rootTable));
    selRel.innerHTML = '<option value="">--</option>' + rows.map(r=>
      `<option value="${r.value}">${r.label}</option>`
    ).join('');
  }

  // al cambiar root, recargar related y setear hiddens
  selRoot && selRoot.addEventListener('change', e=>{
    const opt = e.target.selectedOptions[0];
    form.querySelector('[name="tt_root"]').value = e.target.value || '';
    form.querySelector('[name="tt_root_table"]').value = opt ? (opt.dataset.table||'') : '';
    loadRelated(e.target.value);
  });

  selRel && selRel.addEventListener('change', e=>{
    form.querySelector('[name="tt_related"]').value = e.target.value || '';
  });
  // FORM => listado de formularios + campos
  const selFormId = $('#js-src-form-id');
  const selFormFld= $('#js-src-form-field');

  async function loadForms(){
    selFormId.innerHTML = '<option value="">Cargando...</option>';
    const rows = await getJSON('{{ route('forms.sources.forms',$form) }}');
    selFormId.innerHTML = '<option value="">--</option>' + rows.map(r=>`<option value="${r.value}">${r.label}</option>`).join('');
    selFormFld.innerHTML = '<option value="">--</option>';
  }
  async function loadFormFields(fid){
    if (!fid) { selFormFld.innerHTML = '<option value="">--</option>'; return; }
    selFormFld.innerHTML = '<option value="">Cargando...</option>';
    const rows = await getJSON('{{ route('forms.sources.form_fields',$form) }}' + '?form_id='+encodeURIComponent(fid));
    selFormFld.innerHTML = '<option value="">--</option>' + rows.map(r=>`<option value="${r.value}">${r.label}</option>`).join('');
  }
  selFormId && selFormId.addEventListener('change', e=>loadFormFields(e.target.value));

  // Static Options -> serializar a textarea
  const staticRoot = $('#js-static-options');
  if (staticRoot){
    const btnAdd = staticRoot.querySelector('.js-add-opt');
    const tbody  = staticRoot.querySelector('tbody');
    const ta     = staticRoot.querySelector('textarea[name="options_json"]');

    btnAdd.addEventListener('click', ()=>{
      const inputs = staticRoot.querySelectorAll('.d-flex input');
      const v = inputs[0].value.trim(), l = inputs[1].value.trim();
      if (!v) return;
      const tr = document.createElement('tr');
      tr.innerHTML = `<td>${v}</td><td>${l||v}</td><td class="text-end">
        <button type="button" class="btn btn-sm btn-outline-danger js-del">×</button>
      </td>`;
      tbody.appendChild(tr);
      inputs[0].value = ''; inputs[1].value = '';
      serialize();
    });
    tbody.addEventListener('click', (e)=>{ if (e.target.classList.contains('js-del')) { e.target.closest('tr').remove(); serialize(); } });
    function serialize(){
      const arr = [];
      tbody.querySelectorAll('tr').forEach(tr=>{
        const tds = tr.querySelectorAll('td');
        const v = tds[0].textContent.trim(); const l = tds[1].textContent.trim();
        if (v) arr.push({value:v, label:l||v});
      });
      ta.value = JSON.stringify(arr);
    }
    form.addEventListener('submit', serialize);
  }

  // Al abrir modal (create/edit)
  document.addEventListener('click', async (e)=>{
    const btn = e.target.closest('.js-open-edit');
    if (!btn) return;

    const mode    = btn.dataset.mode || 'edit';
    const field   = btn.dataset.field ? JSON.parse(btn.dataset.field)   : null;
    const source  = btn.dataset.source ? JSON.parse(btn.dataset.source) : null;
    const formula = btn.dataset.formula ? JSON.parse(btn.dataset.formula) : null;

    // action + method spoof
    form.action = mode==='edit'
      ? `{{ route('forms.fields.update', [$form, 0]) }}`.replace('/0', `/${field.id}`)
      : `{{ route('forms.fields.store', $form) }}`;
    let m = form.querySelector('input[name="_method"]');
    if (mode==='edit') { if (!m){ m=document.createElement('input'); m.type='hidden'; m.name='_method'; form.appendChild(m);} m.value='PUT'; }
    else if (m) { m.remove(); }

    // limpiar
    form.reset();
    $('#js-tt-root').innerHTML = '<option value="">--</option>';
    $('#js-tt-related').innerHTML = '<option value="">--</option>';
    $('#js-table-name').innerHTML = '<option value="">--</option>';
    $('#js-column-name').innerHTML = '<option value="">--</option>';
    $('#js-src-form-id').innerHTML = '<option value="">--</option>';
    $('#js-src-form-field').innerHTML = '<option value="">--</option>';
    const ta = form.querySelector('textarea[name="options_json"]'); if (ta) ta.value='';

    // volcar field
    if (field){
      form.querySelector('[name="id_group"]').value  = field.id_group ?? '';
      form.querySelector('[name="codigo"]').value    = field.codigo ?? '';
      form.querySelector('[name="etiqueta"]').value  = field.etiqueta ?? '';
      form.querySelector('[name="descripcion"]') && (form.querySelector('[name="descripcion"]').value = field.descripcion ?? '');
      form.querySelector('[name="kind"]').value      = field.kind ?? 'input';
      form.querySelector('[name="datatype"]').value  = field.datatype ?? 'text';
      form.querySelector('[name="requerido"]').value = field.requerido ?? 0;
      form.querySelector('[name="unico"]').value     = field.unico ?? 0;
      form.querySelector('[name="visible"]').value   = field.visible ?? 1;
      form.querySelector('[name="orden"]').value     = field.orden ?? 0;
    }

    // SOURCE
    if (source){
      form.querySelector('[name="source_kind"]').value = source.source_kind ?? '';

      if (source.source_kind === 'table') {
        await loadTables();
        $('#js-table-name').value = source.table_name ?? '';
        await loadColumns(source.table_name ?? '');
        $('#js-column-name').value = source.column_name ?? '';
      }

      if (source.source_kind === 'table_table') {
        await loadRootTables();
        const meta = source.options_json ? (JSON.parse(source.options_json||'{}')||{}) : {};
        $('#js-tt-root').value = meta.root ?? '';
        await loadRelated(meta.root ?? '');
        $('#js-tt-related').value = meta.related ?? '';
      }

      if (source.source_kind === 'form') {
        await loadForms();
        const meta = source.options_json ? (JSON.parse(source.options_json||'{}')||{}) : {};
        $('#js-src-form-id').value = meta.form_id ?? '';
        await loadFormFields(meta.form_id ?? '');
        $('#js-src-form-field').value = meta.field_code ?? '';
      }

      if (source.source_kind === 'form_actual') {
        const meta = source.options_json ? (JSON.parse(source.options_json||'{}')||{}) : {};
        // cargar grupos
        const grupos = @json($form->groups->map(fn($g)=>['id'=>$g->id,'nombre'=>$g->nombre]));
        $('#js-fa-group').innerHTML = '<option value="">--</option>' +
          grupos.map(g=>`<option value="${g.id}">${g.nombre}</option>`).join('');
        $('#js-fa-group').value = meta.group_id || '';

        // cargar campos del grupo elegido
        const fields = @json(
          $form->groups->mapWithKeys(fn($g)=>[$g->id => $g->fields->map(fn($f)=>['codigo'=>$f->codigo,'etiqueta'=>$f->etiqueta])])
        );
        const rows = fields[meta.group_id] || [];
        $('#js-fa-field').innerHTML = '<option value="">--</option>' +
          rows.map(r=>`<option value="${r.codigo}">${r.codigo} — ${r.etiqueta}</option>`).join('');
        $('#js-fa-field').value = meta.field_code || '';
      }

      if (source.source_kind === 'static_options') {
        const ta = form.querySelector('[name="options_json"]');
        ta.value = typeof source.options_json === 'string' ? source.options_json : JSON.stringify(source.options_json||[]);
      }

      // multi
      form.querySelector('[name="multi_select"]').value = source.multi_select ? 1 : 0;
    }

    // Fórmula
    if (formula){
      form.querySelector('[name="formula_expression"]').value  = formula.expression ?? '';
      form.querySelector('[name="formula_output_type"]').value = formula.output_type ?? 'text';

      // 👇 Aquí fuerza la carga del contexto al abrir en edición
      await loadFormulaContext();

      // si ya tenía un campo seleccionado en la fórmula → volver a marcarlo
      const exprField = formula.expression?.match(/@\{\{\s*([a-zA-Z0-9_]+)/);
      if (exprField) {
        const sel = document.querySelector('#js-formula-fields');
        sel.value = exprField[1];
        const opt = sel.selectedOptions[0];
        const attrs = opt ? JSON.parse(opt.dataset.attrs||'[]') : [];
        const selAttr = document.querySelector('#js-formula-attrs');
        selAttr.innerHTML = '<option value="">--</option>'
          + attrs.map(a=>`<option value="${a.value}">${a.label}</option>`).join('');
      }
    }

    // toggles visibles
    toggleSections();
    toggleSourceBlocks();
  });

  // al abrir modal en modo CREATE (botón “+ Campo”)
  document.getElementById('modalNewField').addEventListener('show.bs.modal', async (ev) => {
    const btn = ev.relatedTarget;
    form.reset();
    toggleSections();
    toggleSourceBlocks();

    // precargar catálogos comunes
    await Promise.all([loadTables(), loadRootTables(), loadForms()]);

    // si es CREATE y selecciona OUTPUT -> carga contexto de fórmulas
    if (btn && btn.dataset.mode==='create') {
      if (form.querySelector('[name="kind"]').value === 'output') {
        await loadFormulaContext();
      }
    }
  });

  // antes de enviar, si source_kind == table_table -> volcar root/related a inputs ocultos
  form.addEventListener('submit', ()=> {
    if (selSrcKind.value === 'table_table') {
      form.querySelector('[name="tt_root"]').value    = selRoot.value || '';
      form.querySelector('[name="tt_related"]').value = selRel.value  || '';
    }
    if (selSrcKind.value === 'form_actual') {
      const gid   = $('#js-fa-group').value;
      const fcode = $('#js-fa-field').value;
      const ta    = form.querySelector('textarea[name="options_json"]') 
                || (()=>{ const t=document.createElement('textarea');t.name='options_json';t.classList.add('d-none');form.appendChild(t);return t;})();
      ta.value = JSON.stringify({ group_id: gid, field_code: fcode });
    }
  });

  let formulaRows = [];
  
  async function loadFormulaContext(){
    formulaRows = await getJSON('{{ route('forms.formula.context',$form) }}');
    console.log("📦 Contexto de fórmulas:", formulaRows);

    const sel = document.querySelector('#js-formula-fields');
    sel.innerHTML = '<option value="">--</option>';

    // agrupar por grupo
    const grupos = {};
    formulaRows.forEach(r=>{
      const g = r.grupo || 'sin grupo';
      if (!grupos[g]) grupos[g] = [];
      grupos[g].push(r);
    });

    // pintar optgroups
    Object.keys(grupos).forEach(g=>{
      const og = document.createElement('optgroup');
      og.label = `[${g}]`;
      grupos[g].forEach(r=>{
        const opt = document.createElement('option');
        opt.value = r.codigo;
        opt.textContent = `${r.codigo} — ${r.etiqueta}`;
        opt.dataset.attrs = JSON.stringify(r.atributos||[]);
        og.appendChild(opt);
      });
      sel.appendChild(og);
    });
  }

  document.querySelector('#js-formula-fields').addEventListener('change', e=>{
    const opt = e.target.selectedOptions[0];
    const attrs = opt ? JSON.parse(opt.dataset.attrs||'[]') : [];
    const selAttr = document.querySelector('#js-formula-attrs');
    selAttr.innerHTML = '<option value="">--</option>'
      + attrs.map(a=>`<option value="${a.value}">${a.label}</option>`).join('');
  });

  document.querySelector('#js-insert-formula').addEventListener('click', ()=>{
    const f = document.querySelector('#js-formula-fields').value;
    const a = document.querySelector('#js-formula-attrs').value;
    if (!f) return;
    const ta = document.querySelector('#js-formula-expression');

    // armar token con campo.atributo (si hay atributo)
    let token = f;
    if (a) token += '.'+a;


    ta.value += `@{{${token}}} `;
  });


})();
</script>
@endsection