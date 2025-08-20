<script>
(function() {
  const builderInput = document.getElementById('builderInput');
  const stagesList   = document.getElementById('stagesList');
  if (!builderInput || !stagesList) return;

  const uid = (p) => p + '_' + Math.random().toString(36).slice(2,8);

  function clearSiblingsSelected(el, selector){
    el.parentElement.querySelectorAll(selector + '.is-selected')
      .forEach(n => n.classList.remove('is-selected'));
  }

  function rebuildJSON() {
    const out = { stages: [] };
    stagesList.querySelectorAll('.stage-item').forEach((st, idx) => {
      const stageId   = st.getAttribute('data-stage-id') || uid('stg');
      const name      = st.querySelector('.stage-name')?.value || 'Etapa';
      const desc      = st.querySelector('.stage-desc')?.value || '';
      const paralelo  = Number(st.querySelector('.stage-paralelo-input')?.value || 0);
      const nro       = idx + 1;

      const tasks = [];
      st.querySelectorAll('.tasks-list .task-item').forEach((ti) => {
        tasks.push({
          id: ti.getAttribute('data-task-id') || uid('tsk'),
          name: ti.querySelector('.task-title')?.textContent?.trim() || 'Tarea',
          description: ti.querySelector('.task-desc')?.textContent?.trim() || ''
        });
      });

      const documents = [];
      st.querySelectorAll('.docs-list .doc-item').forEach((di) => {
        documents.push({
          id: di.getAttribute('data-doc-id') || uid('doc'),
          name: di.querySelector('.doc-title')?.textContent?.trim() || 'Documento',
          description: di.querySelector('.doc-desc')?.textContent?.trim() || ''
        });
      });

      st.querySelector('.stage-nro').textContent = nro;

      out.stages.push({ id: stageId, name, description: desc, nro, paralelo, tasks, documents });
    });

    builderInput.value = JSON.stringify(out);
  }

  function openModal({title, showParalelo=false, values={}, onSave}) {
    const modalEl = document.getElementById('builderModal');
    const modal = new bootstrap.Modal(modalEl);
    modalEl.querySelector('.modal-title').textContent = title;

    const nameI = document.getElementById('bm-name');
    const descI = document.getElementById('bm-desc');
    const parW  = document.getElementById('bm-paralelo-wrap');
    const parI  = document.getElementById('bm-paralelo');

    nameI.value = values.name || '';
    descI.value = values.description || '';
    parW.classList.toggle('d-none', !showParalelo);
    parI.value = String(values.paralelo ?? 0);

    const saveBtn = document.getElementById('bm-save');
    const handler = () => {
      onSave({
        name: nameI.value.trim(),
        description: descI.value.trim(),
        paralelo: Number(parI.value || 0),
      });
      modal.hide();
      saveBtn.removeEventListener('click', handler);
    };
    saveBtn.addEventListener('click', handler);

    modal.show();
  }

  // Add Stage
  document.getElementById('btnAddStage')?.addEventListener('click', () => {
    const id = uid('stg');
    const html = `
      <div class="stage-item border rounded p-3" data-stage-id="${id}">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <div class="d-flex align-items-center gap-2">
            <span class="stage-drag text-muted" style="cursor:grab"><i class="fas fa-grip-vertical"></i></span>
            <strong class="stage-title">1. Nueva etapa</strong>
            <span class="badge bg-light text-dark ms-2">Nro: <span class="stage-nro">1</span></span>
            <span class="badge bg-light text-dark ms-1">Paralelo: <span class="stage-paralelo">0</span></span>
          </div>
          <div class="btn-group">
            <button type="button" class="btn btn-sm btn-outline-secondary btnEditStage">Editar</button>
            <button type="button" class="btn btn-sm btn-outline-danger btnDelStage"><i class="fas fa-trash"></i></button>
          </div>
        </div>
        <div class="row g-3">
          <div class="col-md-6">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <div class="fw-semibold">Tareas</div>
              <button type="button" class="btn btn-sm btn-outline-primary btnAddTask"><i class="fas fa-plus me-1"></i> Tarea</button>
            </div>
            <div class="tasks-list d-grid gap-2" data-stage-id="${id}"></div>
          </div>
          <div class="col-md-6">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <div class="fw-semibold">Documentos</div>
              <button type="button" class="btn btn-sm btn-outline-primary btnAddDoc"><i class="fas fa-plus me-1"></i> Documento</button>
            </div>
            <div class="docs-list d-grid gap-2" data-stage-id="${id}"></div>
          </div>
        </div>
        <input type="hidden" class="stage-name" value="Nueva etapa">
        <input type="hidden" class="stage-desc" value="">
        <input type="hidden" class="stage-paralelo-input" value="0">
      </div>`;
    stagesList.insertAdjacentHTML('beforeend', html);
    initStageSortables();
    rebuildJSON();
  });

  // Delegated actions
  stagesList.addEventListener('click', function(e){
    const btn = e.target.closest('button'); 
    const stage = e.target.closest('.stage-item');

    if (btn?.classList.contains('btnEditStage') && stage) {
      openModal({
        title: 'Editar etapa',
        showParalelo: true,
        values: {
          name: stage.querySelector('.stage-name').value,
          description: stage.querySelector('.stage-desc').value,
          paralelo: Number(stage.querySelector('.stage-paralelo-input').value || 0),
        },
        onSave: ({name, description, paralelo}) => {
          stage.querySelector('.stage-name').value = name;
          stage.querySelector('.stage-desc').value = description;
          stage.querySelector('.stage-paralelo-input').value = paralelo;
          stage.querySelector('.stage-title').textContent = `${stage.querySelector('.stage-nro').textContent}. ${name}`;
          stage.querySelector('.stage-paralelo').textContent = paralelo;
          rebuildJSON();
        }
      });
    }

    if (btn?.classList.contains('btnDelStage') && stage) {
      stage.remove(); rebuildJSON();
    }

    if (btn?.classList.contains('btnAddTask') && stage) {
      const tl = stage.querySelector('.tasks-list');
      const id = uid('tsk');
      tl.insertAdjacentHTML('beforeend', `
        <div class="task-item list-group-item border rounded p-2" data-task-id="${id}" tabindex="0">
          <div class="d-flex justify-content-between align-items-center">
            <div class="task-drag text-muted" style="cursor:grab"><i class="fas fa-grip-vertical me-2"></i></div>
            <div class="flex-grow-1">
              <div class="task-title fw-semibold">Nueva tarea</div>
              <div class="small text-muted task-desc"></div>
            </div>
            <div class="ms-2">
              <button type="button" class="btn btn-sm btn-light btnEditTask">✎</button>
              <button type="button" class="btn btn-sm btn-light btnDelTask">🗑</button>
            </div>
          </div>
        </div>`);
      initOneList(tl, '.task-item', '.task-drag'); rebuildJSON();
    }

    if (btn?.classList.contains('btnAddDoc') && stage) {
      const dl = stage.querySelector('.docs-list');
      const id = uid('doc');
      dl.insertAdjacentHTML('beforeend', `
        <div class="doc-item list-group-item border rounded p-2" data-doc-id="${id}" tabindex="0">
          <div class="d-flex justify-content-between align-items-center">
            <div class="doc-drag text-muted" style="cursor:grab"><i class="fas fa-grip-vertical me-2"></i></div>
            <div class="flex-grow-1">
              <div class="doc-title fw-semibold">Nuevo documento</div>
              <div class="small text-muted doc-desc"></div>
            </div>
            <div class="ms-2">
              <button type="button" class="btn btn-sm btn-light btnEditDoc">✎</button>
              <button type="button" class="btn btn-sm btn-light btnDelDoc">🗑</button>
            </div>
          </div>
        </div>`);
      initOneList(dl, '.doc-item', '.doc-drag'); rebuildJSON();
    }

    const task = e.target.closest('.task-item');
    if (btn?.classList.contains('btnEditTask') && task) {
      openModal({
        title: 'Editar tarea',
        values: {
          name: task.querySelector('.task-title').textContent.trim(),
          description: task.querySelector('.task-desc').textContent.trim(),
        },
        onSave: ({name, description}) => {
          task.querySelector('.task-title').textContent = name || 'Tarea';
          task.querySelector('.task-desc').textContent  = description || '';
          rebuildJSON();
        }
      });
    }
    if (btn?.classList.contains('btnDelTask') && task) { task.remove(); rebuildJSON(); }

    const doc = e.target.closest('.doc-item');
    if (btn?.classList.contains('btnEditDoc') && doc) {
      openModal({
        title: 'Editar documento',
        values: {
          name: doc.querySelector('.doc-title').textContent.trim(),
          description: doc.querySelector('.doc-desc').textContent.trim(),
        },
        onSave: ({name, description}) => {
          doc.querySelector('.doc-title').textContent = name || 'Documento';
          doc.querySelector('.doc-desc').textContent  = description || '';
          rebuildJSON();
        }
      });
    }
    if (btn?.classList.contains('btnDelDoc') && doc) { doc.remove(); rebuildJSON(); }
  });

  // selección por contenedor
  stagesList.addEventListener('click', function(e){
    const ti = e.target.closest('.task-item');
    if (ti) { clearSiblingsSelected(ti, '.task-item'); ti.classList.add('is-selected'); return; }
    const di = e.target.closest('.doc-item');
    if (di) { clearSiblingsSelected(di, '.doc-item'); di.classList.add('is-selected'); return; }
  });

  function initOneList(container, draggableSel, handleSel) {
    if (!container || container._sortable) return;
    container._sortable = new Sortable(container, {
      animation: 150,
      handle: handleSel,
      draggable: draggableSel,
      ghostClass: 'drag-ghost',
      chosenClass: 'drag-chosen',
      dragClass: 'drag-dragging',
      filter: 'input,textarea,button,select',
      preventOnFilter: true,
      onEnd: rebuildJSON,
    });
  }

  function initStageSortables() {
    if (!stagesList._sortable) {
      stagesList._sortable = new Sortable(stagesList, {
        animation: 150,
        handle: '.stage-drag',
        draggable: '.stage-item',
        onEnd: function() {
          stagesList.querySelectorAll('.stage-item').forEach((st, i) => {
            const name = st.querySelector('.stage-name').value || 'Etapa';
            st.querySelector('.stage-nro').textContent = (i+1);
            st.querySelector('.stage-title').textContent = `${i+1}. ${name}`;
          });
          rebuildJSON();
        }
      });
    }
    stagesList.querySelectorAll('.tasks-list').forEach(el => initOneList(el, '.task-item', '.task-drag'));
    stagesList.querySelectorAll('.docs-list').forEach(el => initOneList(el, '.doc-item', '.doc-drag'));
  }

  initStageSortables();
  rebuildJSON();
})();
</script>

<style>
  .is-selected{ outline: 2px solid #0d6efd; border-color:#0d6efd !important; background: rgba(13,110,253,.05); }
  .drag-ghost{ opacity:.4; }
</style>
