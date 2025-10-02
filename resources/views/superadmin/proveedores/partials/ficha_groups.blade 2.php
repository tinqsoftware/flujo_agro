@php
  // Espera: $groupDefs, $relOptions
  // Opcionales (edit): $listValues, $relValues
@endphp

@if($groupDefs->isNotEmpty())
  <div class=" d-flex flex-column gap-3">
  @foreach($groupDefs as $def)
    <div class="">
      <div class=" mb-1">{{ $def->label }}</div>

      {{-- ===================== LISTA (ítems) ===================== --}}
      @if($def->group_type === 'list')
        @php
          $rows = collect();
          if(isset($listValues[$def->code])) $rows = $listValues[$def->code]->sortBy('sort_order')->values();
          $fields = $def->item_fields_json ?? [];
        @endphp

        <div class="list-items" data-code="{{ $def->code }}">
          <div class="vf-rows d-flex flex-column gap-2">
            @forelse($rows as $i => $row)
              @php $val = $row->value_json ?? []; @endphp
              <div class="row g-2 align-items-end" data-row-index="{{ $i }}">
                <div class="col-auto">
                  <div class="badge bg-secondary"># <span class="row-idx">{{ $i+1 }}</span></div>
                </div>
                @foreach($fields as $f)
                  @php $c = $f['code'] ?? ''; @endphp
                  <div class="col-md-3">
                    <label class="form-label">{{ $f['label'] ?? $c }}</label>
                    <input class="form-control"
                           name="groups[{{ $def->code }}][items][{{ $i }}][{{ $c }}]"
                           value="{{ $val[$c] ?? '' }}">
                  </div>
                @endforeach
                <div class="col-md-2 text-end">
                  <button type="button" class="btn btn-outline-danger" onclick="removeRepeaterRow(this)">Quitar</button>
                </div>
              </div>
            @empty
              {{-- starter (índice 0) --}}
              <div class="row g-2 align-items-end" data-row-index="0">
                <div class="col-auto">
                  <div class="badge bg-secondary"># <span class="row-idx">1</span></div>
                </div>
                @foreach($fields as $f)
                  @php $c = $f['code'] ?? ''; @endphp
                  <div class="col-md-3">
                    <label class="form-label">{{ $f['label'] ?? $c }}</label>
                    <input class="form-control" name="groups[{{ $def->code }}][items][0][{{ $c }}]">
                  </div>
                @endforeach
                <div class="col-md-2 text-end">
                  <button type="button" class="btn btn-outline-danger" onclick="removeRepeaterRow(this)">Quitar</button>
                </div>
              </div>
            @endforelse
          </div>
          <button type="button" class="btn btn-sm btn-outline-primary mt-2"
                  onclick="addListRow('{{ $def->code }}')">+ Añadir fila</button>
        </div>

      {{-- ===================== RELACIÓN ===================== --}}
      @else
        @php
          $opts = $relOptions[$def->related_entity_type] ?? collect();
          $selected = [];
          if(isset($relValues[$def->code])) $selected = $relValues[$def->code]->pluck('related_entity_id')->values()->all();
        @endphp

        @if($def->allow_multiple)
          <div class="relation-multi" data-code="{{ $def->code }}">
            <div class="vf-rows d-flex flex-column gap-2">
              @forelse($selected as $i => $rid)
                <div class="row g-2 align-items-end" data-row-index="{{ $i }}">
                  <div class="col-auto">
                    <div class="badge bg-secondary"># <span class="row-idx">{{ $i+1 }}</span></div>
                  </div>
                  <div class="col-md-6">
                    <select class="form-select" name="groups[{{ $def->code }}][related_ids][{{ $i }}]">
                      <option value="">--</option>
                      @foreach($opts as $o)
                        <option value="{{ $o->id }}" @selected($o->id == $rid)>{{ $o->nombre }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-md-2 text-end">
                    <button type="button" class="btn btn-outline-danger" onclick="removeRepeaterRow(this)">Quitar</button>
                  </div>
                </div>
              @empty
                {{-- starter (índice 0) --}}
                <div class="row g-2 align-items-end" data-row-index="0">
                  <div class="col-auto">
                    <div class="badge bg-secondary"># <span class="row-idx">1</span></div>
                  </div>
                  <div class="col-md-6">
                    <select class="form-select" name="groups[{{ $def->code }}][related_ids][0]">
                      <option value="">--</option>
                      @foreach($opts as $o)
                        <option value="{{ $o->id }}">{{ $o->nombre }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-md-2 text-end">
                    <button type="button" class="btn btn-outline-danger" onclick="removeRepeaterRow(this)">Quitar</button>
                  </div>
                </div>
              @endforelse
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary mt-2"
                    onclick="addRelationRow('{{ $def->code }}')">+ Añadir</button>
          </div>
        @else
          <label class="form-label">Selecciona uno</label>
          <select class="form-select" name="groups[{{ $def->code }}][related_id]">
            <option value="">--</option>
            @foreach($opts as $o)
              <option value="{{ $o->id }}" @selected(in_array($o->id, $selected))>{{ $o->nombre }}</option>
            @endforeach
          </select>
        @endif
      @endif
    </div>
  @endforeach
  </div>

@push('scripts')
<script>
(function(){

  function renumerarIndicesVisibles(wrap){
    const filas = wrap.querySelectorAll('[data-row-index]');
    filas.forEach((row, i) => {
      row.setAttribute('data-row-index', i);
      const b = row.querySelector('.row-idx');
      if (b) b.textContent = i + 1;
    });
  }

  // ===== Utilidades comunes =====
  function setIdxBadge(row, i){
    const badge = row.querySelector('.row-idx');
    if (badge) badge.textContent = (i+1);
  }
  function reindexRows(rowsWrap, type){
    // type: 'list' | 'relation'
    const rows = Array.from(rowsWrap.querySelectorAll('[data-row-index]'));
    rows.forEach((row, i) => {
      row.setAttribute('data-row-index', i);
      setIdxBadge(row, i);
      row.querySelectorAll('input,select,textarea').forEach(el => {
        let n = el.name || '';

        if (type === 'list') {
          // ...[items][N]...  (N puede ser 0..n o venir vacío si alguien dejó [])
          n = n.replace(/\[items]\[(?:\d+)?]/, `[items][${i}]`);
        } else {
          // ...[related_ids][N]...  (ídem)
          n = n.replace(/\[related_ids]\[(?:\d+)?]/, `[related_ids][${i}]`);
        }
        el.name = n;
      });
    });
  }
  function buildTemplateRow(html){
    const tpl = document.createElement('template');
    tpl.innerHTML = html.trim();
    return tpl.content.firstElementChild;
  }

  // Clona la primera fila como plantilla limpia
  function cloneCleanRow(firstRow){
    const tpl = firstRow.cloneNode(true);
    tpl.querySelectorAll('input,textarea').forEach(el => el.value = '');
    tpl.querySelectorAll('select').forEach(sel => sel.value = '');
    return tpl;
  }

  // ====== ÍTEMS (LISTA) ======
  window.addListRow = function(code){
    const box  = document.querySelector(`.list-items[data-code="${code}"]`);
    const wrap = box.querySelector('.vf-rows');
    const first = wrap.querySelector('[data-row-index]');
    const tpl = cloneCleanRow(first);
    wrap.appendChild(tpl);
    reindexRows(wrap, 'list');
  };

  // ====== RELACIÓN MÚLTIPLE ======
  window.addRelationRow = function(code){
    const box  = document.querySelector(`.relation-multi[data-code="${code}"]`);
    const wrap = box.querySelector('.vf-rows');
    const first = wrap.querySelector('[data-row-index]');
    const tpl = cloneCleanRow(first);
    wrap.appendChild(tpl);
    reindexRows(wrap, 'relation');
  };

  // ====== Quitar fila (ambos tipos) ======
  window.removeRepeaterRow = function(btn){
    const block = btn.closest('.relation-multi, .list-items');
    const wrap  = block.querySelector('.vf-rows');
    const type  = block.classList.contains('relation-multi') ? 'relation' : 'list';

    btn.closest('[data-row-index]').remove();
    reindexRows(wrap, type);
  };
})();
</script>
@endpush

@endif
