<script>
(function() {
  const builderInput = document.getElementById('builderInput');
  const stagesList   = document.getElementById('stagesList');
  const builderRoot  = document.getElementById('builderRoot');
  if (!builderInput || !stagesList) return;

  const isEditMode = builderRoot?.getAttribute('data-edit-mode') === 'true';
  const uid = (p) => p + '_' + Math.random().toString(36).slice(2,8);

  // Roles disponibles
  @php
    $rolesJson = isset($roles) ? json_encode($roles) : '[]';
  @endphp
  const rolesData = {!! $rolesJson !!};

  function getRoleName(rolId) {
    if (!rolId) return 'Todos los roles';
    const rol = rolesData.find(r => r.id == rolId);
    return rol ? rol.nombre : `Rol ID: ${rolId}`;
  }

  function updateTaskRolBadge(task, rolId) {
    const flexGrow = task.querySelector('.flex-grow-1');
    // Remover badge de rol existente
    const existingRolBadge = flexGrow.querySelector('.badge-rol');
    if (existingRolBadge) {
      existingRolBadge.remove();
    }
    
    // Crear nuevo badge
    const badgeClass = rolId ? 'bg-primary' : 'bg-warning';
    const badgeIcon = rolId ? 'fa-user-tag' : 'fa-users';
    const badgeText = getRoleName(rolId);
    
    const badge = document.createElement('span');
    badge.className = `badge ${badgeClass} badge-sm badge-rol`;
    badge.innerHTML = `<i class="fas ${badgeIcon}"></i> ${badgeText}`;
    
    // Insertar después de la descripción
    const desc = flexGrow.querySelector('.task-desc');
    desc.parentNode.insertBefore(badge, desc.nextSibling);
  }

  function updateDocRolBadge(doc, rolId) {
    const flexGrow = doc.querySelector('.flex-grow-1');
    // Remover badge de rol existente
    const existingRolBadge = flexGrow.querySelector('.badge-rol');
    if (existingRolBadge) {
      existingRolBadge.remove();
    }
    
    // Crear nuevo badge
    const badgeClass = rolId ? 'bg-primary' : 'bg-warning';
    const badgeIcon = rolId ? 'fa-user-tag' : 'fa-users';
    const badgeText = getRoleName(rolId);
    
    const badge = document.createElement('span');
    badge.className = `badge ${badgeClass} badge-sm badge-rol`;
    badge.innerHTML = `<i class="fas ${badgeIcon}"></i> ${badgeText}`;
    
    // Insertar después de la descripción
    const desc = flexGrow.querySelector('.doc-desc');
    desc.parentNode.insertBefore(badge, desc.nextSibling);
  }

  function clearSiblingsSelected(el, selector){
    el.parentElement.querySelectorAll(selector + '.is-selected')
      .forEach(n => n.classList.remove('is-selected'));
  }

  // Función para toggle de estado via AJAX
  function toggleEstado(url, elemento, switchEl) {
    fetch(url, {
      method: 'PATCH',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      }
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Actualizar el estado visual
        const badge = elemento.querySelector('.badge');
        if (badge && badge.textContent.includes('Activo') || badge.textContent.includes('Inactivo')) {
          badge.className = data.estado ? 'badge bg-success badge-sm' : 'badge bg-secondary badge-sm';
          badge.textContent = data.estado ? 'Activo' : 'Inactivo';
        }
        
        // Actualizar la clase del elemento
        if (data.estado) {
          elemento.classList.remove('disabled-item');
        } else {
          elemento.classList.add('disabled-item');
        }
        
        // Actualizar campos ocultos si es etapa
        const estadoInput = elemento.querySelector('.stage-estado-input');
        if (estadoInput) {
          estadoInput.value = data.estado ? 1 : 0;
        }
        
        rebuildJSON();
      } else {
        // Revertir el switch si hay error
        switchEl.checked = !switchEl.checked;
        
        // Mostrar mensaje de error más específico
        if (data.error) {
          // Crear un toast o alerta más elegante
          showErrorMessage(data.error);
        } else {
          alert('Error al cambiar el estado');
        }
      }
    })
    .catch(error => {
      console.error('Error:', error);
      switchEl.checked = !switchEl.checked;
      showErrorMessage('Error de conexión');
    });
  }

  // Función para mostrar mensajes de error
  function showErrorMessage(message) {
    // Verificar si ya existe un alert activo
    const existingAlert = document.querySelector('.alert-warning.temporary-alert');
    if (existingAlert) {
      existingAlert.remove();
    }

    // Crear el alert
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-warning alert-dismissible temporary-alert';
    alertDiv.innerHTML = `
      <i class="fas fa-exclamation-triangle me-2"></i>
      ${message}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    // Insertar en la parte superior del builderRoot
    const builderRoot = document.getElementById('builderRoot');
    builderRoot.insertBefore(alertDiv, builderRoot.firstChild);

    // Auto-remover después de 5 segundos
    setTimeout(() => {
      if (alertDiv.parentNode) {
        alertDiv.remove();
      }
    }, 5000);
  }

  function rebuildJSON() {
    const out = { stages: [] };
    stagesList.querySelectorAll('.stage-item').forEach((st, idx) => {
      const stageId   = st.getAttribute('data-stage-id') || uid('stg');
      const name      = st.querySelector('.stage-name')?.value || 'Etapa';
      const desc      = st.querySelector('.stage-desc')?.value || '';
      const paralelo  = Number(st.querySelector('.stage-paralelo-input')?.value || 0);
      const estado    = Number(st.querySelector('.stage-estado-input')?.value || 1);
      const nro       = idx + 1;

      const tasks = [];
      st.querySelectorAll('.tasks-list .task-item').forEach((ti) => {
        const taskEstado = isEditMode && ti.getAttribute('data-task-db-id') ? 
          (ti.querySelector('.task-estado-switch')?.checked ? 1 : 0) : 1;
        const taskRolCambios = ti.getAttribute('data-task-rol-cambios') || null;
        
        // Obtener documentos de esta tarea específica
        const documents = [];
        ti.querySelectorAll('.task-docs-list .doc-item').forEach((di) => {
          const docEstado = isEditMode && di.getAttribute('data-doc-db-id') ? 
            (di.querySelector('.doc-estado-switch')?.checked ? 1 : 0) : 1;
          const docRolCambios = di.getAttribute('data-doc-rol-cambios') || null;
          documents.push({
            id: di.getAttribute('data-doc-id') || uid('doc'),
            name: di.querySelector('.doc-title')?.textContent?.trim() || 'Documento',
            description: di.querySelector('.doc-desc')?.textContent?.trim() || '',
            estado: docEstado,
            rol_cambios: docRolCambios
          });
        });

        tasks.push({
          id: ti.getAttribute('data-task-id') || uid('tsk'),
          name: ti.querySelector('.task-title')?.textContent?.trim() || 'Tarea',
          description: ti.querySelector('.task-desc')?.textContent?.trim() || '',
          estado: taskEstado,
          rol_cambios: taskRolCambios,
          documents: documents
        });
      });

      st.querySelector('.stage-nro').textContent = nro;

      // Obtener formularios asociados a esta etapa
      const forms = [];
      st.querySelectorAll('.stage-forms-list .form-item').forEach((fi) => {
        forms.push({
          id: fi.getAttribute('data-form-db-id') || fi.getAttribute('data-form-id'),
          name: fi.querySelector('.form-title')?.textContent?.trim().replace(/^.*?\s/, '') || 'Formulario',
          description: fi.querySelector('.form-desc')?.textContent?.trim() || ''
        });
      });

      out.stages.push({ id: stageId, name, description: desc, nro, paralelo, estado, tasks, forms });
    });

    builderInput.value = JSON.stringify(out);
  }

  function openModal({title, showParalelo=false, showRol=false, values={}, onSave}) {
    const modalEl = document.getElementById('builderModal');
    const modal = new bootstrap.Modal(modalEl);
    modalEl.querySelector('.modal-title').textContent = title;

    const nameI = document.getElementById('bm-name');
    const descI = document.getElementById('bm-desc');
    const parW  = document.getElementById('bm-paralelo-wrap');
    const parI  = document.getElementById('bm-paralelo');
    const rolW  = document.getElementById('bm-rol-wrap');
    const rolI  = document.getElementById('bm-rol');

    nameI.value = values.name || '';
    descI.value = values.description || '';
    parW.classList.toggle('d-none', !showParalelo);
    parI.value = String(values.paralelo ?? 0);
    rolW.classList.toggle('d-none', !showRol);
    rolI.value = String(values.rol_cambios ?? '');

    const saveBtn = document.getElementById('bm-save');
    const handler = () => {
      onSave({
        name: nameI.value.trim(),
        description: descI.value.trim(),
        paralelo: Number(parI.value || 0),
        rol_cambios: rolI.value || null,
      });
      modal.hide();
      saveBtn.removeEventListener('click', handler);
    };
    saveBtn.addEventListener('click', handler);

    modal.show();
  }

  // Nueva función para abrir modal de tarea con gestión de documentos
  function openTaskWithDocsModal(task) {
    const modalEl = document.getElementById('taskDocsModal') || createTaskDocsModal();
    const modal = new bootstrap.Modal(modalEl);
    
    // Cargar datos de la tarea
    const taskName = task.querySelector('.task-title').textContent.trim();
    const taskDesc = task.querySelector('.task-desc').textContent.trim();
    const taskRol = task.getAttribute('data-task-rol-cambios') || '';
    
    modalEl.querySelector('#tdm-task-name').value = taskName;
    modalEl.querySelector('#tdm-task-desc').value = taskDesc;
    modalEl.querySelector('#tdm-task-rol').value = taskRol;
    modalEl.querySelector('.modal-title').textContent = `Gestionar Tarea: ${taskName}`;
    
    // Limpiar lista de documentos del modal
    const docsContainer = modalEl.querySelector('#tdm-docs-list');
    docsContainer.innerHTML = '';
    
    // Cargar documentos existentes
    const existingDocs = task.querySelectorAll('.task-docs-list .doc-item');
    existingDocs.forEach(doc => {
      addDocToModal(docsContainer, {
        name: doc.querySelector('.doc-title').textContent.trim(),
        description: doc.querySelector('.doc-desc').textContent.trim(),
        rol_cambios: doc.getAttribute('data-doc-rol-cambios') || ''
      });
    });
    
    // Event handler para guardar
    const saveBtn = modalEl.querySelector('#tdm-save');
    const handler = () => {
      saveTaskWithDocs(task, modalEl);
      modal.hide();
      saveBtn.removeEventListener('click', handler);
    };
    saveBtn.addEventListener('click', handler);
    
    modal.show();
  }

  function createTaskDocsModal() {
    const modalHTML = `
      <div class="modal fade" id="taskDocsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Gestionar Tarea y Documentos</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <div class="row">
                <div class="col-md-6">
                  <h6>Información de la Tarea</h6>
                  <div class="mb-3">
                    <label for="tdm-task-name" class="form-label">Nombre</label>
                    <input type="text" class="form-control" id="tdm-task-name" required>
                  </div>
                  <div class="mb-3">
                    <label for="tdm-task-desc" class="form-label">Descripción</label>
                    <textarea class="form-control" id="tdm-task-desc" rows="3"></textarea>
                  </div>
                  <div class="mb-3">
                    <label for="tdm-task-rol" class="form-label">Rol para cambios</label>
                    <select class="form-select" id="tdm-task-rol">
                      <option value="">Todos los roles</option>
                      ${rolesData.map(rol => `<option value="${rol.id}">${rol.nombre}</option>`).join('')}
                    </select>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6>Documentos</h6>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="tdm-add-doc">
                      <i class="fas fa-plus me-1"></i> Agregar Documento
                    </button>
                  </div>
                  <div id="tdm-docs-list" class="d-grid gap-2"></div>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
              <button type="button" class="btn btn-primary" id="tdm-save">Guardar Cambios</button>
            </div>
          </div>
        </div>
      </div>`;
    
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    const modalEl = document.getElementById('taskDocsModal');
    
    // Event listener para agregar documentos
    modalEl.querySelector('#tdm-add-doc').addEventListener('click', () => {
      addDocToModal(modalEl.querySelector('#tdm-docs-list'));
    });
    
    return modalEl;
  }

  function addDocToModal(container, values = {}) {
    const docId = uid('modal-doc');
    const docHTML = `
      <div class="card card-body p-2 doc-modal-item" data-doc-modal-id="${docId}">
        <div class="row g-2">
          <div class="col-md-4">
            <input type="text" class="form-control form-control-sm doc-modal-name" 
                   placeholder="Nombre del documento" value="${values.name || ''}" required>
          </div>
          <div class="col-md-4">
            <input type="text" class="form-control form-control-sm doc-modal-desc" 
                   placeholder="Descripción" value="${values.description || ''}">
          </div>
          <div class="col-md-3">
            <select class="form-select form-select-sm doc-modal-rol">
              <option value="">Todos los roles</option>
              ${rolesData.map(rol => `<option value="${rol.id}" ${rol.id == values.rol_cambios ? 'selected' : ''}>${rol.nombre}</option>`).join('')}
            </select>
          </div>
          <div class="col-md-1">
            <button type="button" class="btn btn-sm btn-outline-danger w-100 remove-doc-modal">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </div>
      </div>`;
    
    container.insertAdjacentHTML('beforeend', docHTML);
    
    // Event listener para remover documento
    const newDoc = container.lastElementChild;
    newDoc.querySelector('.remove-doc-modal').addEventListener('click', () => {
      newDoc.remove();
    });
  }

  function saveTaskWithDocs(task, modalEl) {
    // Actualizar información de la tarea
    const taskName = modalEl.querySelector('#tdm-task-name').value.trim();
    const taskDesc = modalEl.querySelector('#tdm-task-desc').value.trim();
    const taskRol = modalEl.querySelector('#tdm-task-rol').value;
    
    task.querySelector('.task-title').textContent = taskName || 'Tarea';
    task.querySelector('.task-desc').textContent = taskDesc || '';
    task.setAttribute('data-task-rol-cambios', taskRol || '');
    updateTaskRolBadge(task, taskRol);
    
    // Limpiar documentos existentes
    const taskDocsList = task.querySelector('.task-docs-list');
    taskDocsList.innerHTML = '';
    
    // Agregar nuevos documentos
    const docsFromModal = modalEl.querySelectorAll('.doc-modal-item');
    docsFromModal.forEach(docModal => {
      const docName = docModal.querySelector('.doc-modal-name').value.trim();
      const docDesc = docModal.querySelector('.doc-modal-desc').value.trim();
      const docRol = docModal.querySelector('.doc-modal-rol').value;
      
      if (docName) {
        const docId = uid('doc');
        const docHTML = `
          <div class="doc-item list-group-item border rounded p-2" data-doc-id="${docId}" data-doc-rol-cambios="${docRol || ''}" tabindex="0">
            <div class="d-flex justify-content-between align-items-center">
              <div class="doc-drag text-muted" style="cursor:grab"><i class="fas fa-grip-vertical me-2"></i></div>
              <div class="flex-grow-1">
                <div class="doc-title fw-semibold">${docName}</div>
                <div class="small text-muted doc-desc">${docDesc}</div>
                <span class="badge ${docRol ? 'bg-primary' : 'bg-warning'} badge-sm badge-rol">
                  <i class="fas ${docRol ? 'fa-user-tag' : 'fa-users'}"></i> ${getRoleName(docRol)}
                </span>
              </div>
              <div class="ms-2 d-flex align-items-center gap-1">
                <button type="button" class="btn btn-sm btn-light btnEditDoc">✎</button>
                <button type="button" class="btn btn-sm btn-light btnDelDoc">🗑</button>
              </div>
            </div>
          </div>`;
        
        taskDocsList.insertAdjacentHTML('beforeend', docHTML);
      }
    });
    
    // Reinicializar sortable para los nuevos documentos
    initOneList(taskDocsList, '.doc-item', '.doc-drag');
    rebuildJSON();
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
          <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <div class="fw-semibold">Tareas</div>
              <div class="btn-group">
                <button type="button" class="btn btn-sm btn-outline-primary btnAddTask"><i class="fas fa-plus me-1"></i> Tarea</button>
                <button type="button" class="btn btn-sm btn-outline-success btnAddForm" data-stage-id="${id}"><i class="fas fa-plus me-1"></i> Form</button>
              </div>
            </div>
            <div class="tasks-list d-grid gap-2" data-stage-id="${id}"></div>
            <div class="mt-3">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="fw-semibold small text-muted">Formularios asociados</div>
              </div>
              <div class="stage-forms-list d-grid gap-1" data-stage-id="${id}"></div>
            </div>
          </div>
        </div>
        <input type="hidden" class="stage-name" value="Nueva etapa">
        <input type="hidden" class="stage-desc" value="">
        <input type="hidden" class="stage-paralelo-input" value="0">
        <input type="hidden" class="stage-estado-input" value="1">
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
        <div class="task-item list-group-item border rounded p-3 mb-2" data-task-id="${id}" data-task-rol-cambios="" tabindex="0">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="task-drag text-muted" style="cursor:grab"><i class="fas fa-grip-vertical me-2"></i></div>
            <div class="flex-grow-1">
              <div class="task-title fw-semibold">Nueva tarea</div>
              <div class="small text-muted task-desc"></div>
              <span class="badge bg-warning badge-sm badge-rol">
                <i class="fas fa-users"></i> Todos los roles
              </span>
            </div>
            <div class="ms-2 d-flex align-items-center gap-1">
              <button type="button" class="btn btn-sm btn-light btnEditTask">✎</button>
              <button type="button" class="btn btn-sm btn-light btnDelTask">🗑</button>
            </div>
          </div>
          <div class="mt-2">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <div class="fw-semibold small text-muted">Documentos de esta tarea</div>
              <button type="button" class="btn btn-sm btn-outline-info btnAddTaskDoc"><i class="fas fa-plus me-1"></i> Documento</button>
            </div>
            <div class="task-docs-list d-grid gap-1" data-task-id="${id}"></div>
          </div>
        </div>`);
      initOneList(tl, '.task-item', '.task-drag'); 
      // Inicializar la lista de documentos de la nueva tarea
      const newTask = tl.lastElementChild;
      const newTaskDocsList = newTask.querySelector('.task-docs-list');
      initOneList(newTaskDocsList, '.doc-item', '.doc-drag');
      rebuildJSON();
    }

    if (btn?.classList.contains('btnAddForm') && stage) {
      openFormSelectorModal(stage);
    }

    if (btn?.classList.contains('btnAddTaskDoc')) {
      const task = e.target.closest('.task-item');
      if (task) {
        // Abrir modal de editar tarea con gestión de documentos
        openTaskWithDocsModal(task);
      }
    }

    const task = e.target.closest('.task-item');
    if (btn?.classList.contains('btnEditTask') && task) {
      openModal({
        title: 'Editar tarea',
        showRol: true,
        values: {
          name: task.querySelector('.task-title').textContent.trim(),
          description: task.querySelector('.task-desc').textContent.trim(),
          rol_cambios: task.getAttribute('data-task-rol-cambios') || '',
        },
        onSave: ({name, description, rol_cambios}) => {
          task.querySelector('.task-title').textContent = name || 'Tarea';
          task.querySelector('.task-desc').textContent  = description || '';
          task.setAttribute('data-task-rol-cambios', rol_cambios || '');
          
          // Actualizar badge de rol
          updateTaskRolBadge(task, rol_cambios);
          
          rebuildJSON();
        }
      });
    }
    if (btn?.classList.contains('btnDelTask') && task) { task.remove(); rebuildJSON(); }

    const doc = e.target.closest('.doc-item');
    if (btn?.classList.contains('btnEditDoc') && doc) {
      openModal({
        title: 'Editar documento',
        showRol: true,
        values: {
          name: doc.querySelector('.doc-title').textContent.trim(),
          description: doc.querySelector('.doc-desc').textContent.trim(),
          rol_cambios: doc.getAttribute('data-doc-rol-cambios') || '',
        },
        onSave: ({name, description, rol_cambios}) => {
          doc.querySelector('.doc-title').textContent = name || 'Documento';
          doc.querySelector('.doc-desc').textContent  = description || '';
          doc.setAttribute('data-doc-rol-cambios', rol_cambios || '');
          
          // Actualizar badge de rol
          updateDocRolBadge(doc, rol_cambios);
          
          rebuildJSON();
        }
      });
    }
    if (btn?.classList.contains('btnDelDoc') && doc) { doc.remove(); rebuildJSON(); }
    
    // Gestión de formularios
    if (btn?.classList.contains('btnRemoveForm')) {
      const formItem = e.target.closest('.form-item');
      const stage = e.target.closest('.stage-item');
      
      if (formItem && stage) {
        const formId = btn.getAttribute('data-form-id');
        const stageId = stage.getAttribute('data-stage-db-id');
        
        if (isEditMode && stageId && formId) {
          // En modo edición, hacer petición AJAX para desasociar
          removeFormFromStage(stageId, formId, formItem);
        } else {
          // En creación, solo remover del DOM
          formItem.remove();
          rebuildJSON();
        }
      }
    }
  });

  // Event listeners para switches de estado (solo en modo edición)
  if (isEditMode) {
    stagesList.addEventListener('change', function(e) {
      if (e.target.classList.contains('stage-estado-switch')) {
        const stageId = e.target.getAttribute('data-stage-id');
        const stage = e.target.closest('.stage-item');
        toggleEstado(`/flujos/etapas/${stageId}/toggle-estado`, stage, e.target);
      }
      
      if (e.target.classList.contains('task-estado-switch')) {
        const taskId = e.target.getAttribute('data-task-id');
        const task = e.target.closest('.task-item');
        toggleEstado(`/flujos/tareas/${taskId}/toggle-estado`, task, e.target);
      }
      
      if (e.target.classList.contains('doc-estado-switch')) {
        const docId = e.target.getAttribute('data-doc-id');
        const doc = e.target.closest('.doc-item');
        toggleEstado(`/flujos/documentos/${docId}/toggle-estado`, doc, e.target);
      }
    });
  }

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
    stagesList.querySelectorAll('.task-docs-list').forEach(el => initOneList(el, '.doc-item', '.doc-drag'));
  }

  initStageSortables();
  rebuildJSON();

  // Funciones para gestión de formularios
  let currentStageForForm = null;
  let availableForms = [];
  let selectedFormId = null;

  function openFormSelectorModal(stage) {
    currentStageForForm = stage;
    const modal = new bootstrap.Modal(document.getElementById('formSelectorModal'));
    
    // Obtener empresa ID para cargar formularios
    let empresaId = null;
    
    // Verificar si tenemos configuración global
    if (window.flujoConfig) {
      if (window.flujoConfig.isSuper) {
        // Para SUPERADMIN, detectar empresa desde el select
        const empresaSelect = document.getElementById('empresaSelect');
        if (empresaSelect) {
          empresaId = empresaSelect.value;
        } else if (window.flujoConfig.flujoEmpresa) {
          // En edición, usar la empresa del flujo
          empresaId = window.flujoConfig.flujoEmpresa;
        }
        
        if (!empresaId) {
          alert('Debe seleccionar una empresa primero');
          return;
        }
      } else {
        // Para usuarios normales, usar su empresa
        empresaId = window.flujoConfig.userEmpresa;
      }
    } else {
      // Fallback al método anterior
      const empresaSelect = document.getElementById('empresaSelect');
      if (empresaSelect) {
        empresaId = empresaSelect.value;
      } else {
        const empresaInput = document.querySelector('select[name="id_emp"], input[name="id_emp"]');
        if (empresaInput) {
          empresaId = empresaInput.value;
        }
      }
      
      if (!empresaId) {
        alert('Debe seleccionar una empresa primero');
        return;
      }
    }
    
    loadFormsByEmpresa(empresaId);
    modal.show();
  }

  function loadFormsByEmpresa(empresaId) {
    const formsList = document.getElementById('formsList');
    const formPreview = document.getElementById('formPreview');
    
    formsList.innerHTML = `
      <div class="text-center py-3">
        <div class="spinner-border spinner-border-sm" role="status">
          <span class="visually-hidden">Cargando...</span>
        </div>
      </div>
    `;
    
    // Construir URL con o sin parámetro empresa_id dependiendo del tipo de usuario
    let url = `{{ route('flujos.forms.byEmpresa') }}`;
    if (window.flujoConfig && window.flujoConfig.isSuper && empresaId) {
      url += `?empresa_id=${empresaId}`;
    }
    
    fetch(url)
      .then(response => response.json())
      .then(data => {
        availableForms = data.forms || [];
        renderFormsList();
      })
      .catch(error => {
        console.error('Error:', error);
        formsList.innerHTML = '<div class="alert alert-danger">Error al cargar formularios</div>';
      });
  }

  function renderFormsList() {
    const formsList = document.getElementById('formsList');
    
    if (availableForms.length === 0) {
      formsList.innerHTML = '<div class="text-muted text-center py-3">No hay formularios disponibles</div>';
      return;
    }
    
    formsList.innerHTML = availableForms.map(form => `
      <div class="list-group-item list-group-item-action form-list-item" 
           data-form-id="${form.id}" 
           style="cursor: pointer;">
        <div class="d-flex w-100 justify-content-between">
          <h6 class="mb-1">${form.nombre}</h6>
          <small class="text-muted">${form.type ? form.type.nombre : 'Sin tipo'}</small>
        </div>
        <p class="mb-1 small text-muted">${form.descripcion || 'Sin descripción'}</p>
      </div>
    `).join('');
    
    // Agregar event listeners a los items
    formsList.querySelectorAll('.form-list-item').forEach(item => {
      item.addEventListener('click', () => {
        selectForm(parseInt(item.dataset.formId));
      });
    });
  }

  function selectForm(formId) {
    selectedFormId = formId;
    
    // Marcar como seleccionado visualmente
    document.querySelectorAll('.form-list-item').forEach(item => {
      item.classList.remove('active');
    });
    document.querySelector(`[data-form-id="${formId}"]`).classList.add('active');
    
    // Habilitar botón de confirmación
    document.getElementById('confirmFormSelection').disabled = false;
    
    // Cargar vista previa
    loadFormPreview(formId);
  }

  function loadFormPreview(formId) {
    const formPreview = document.getElementById('formPreview');
    
    formPreview.innerHTML = `
      <div class="text-center py-3">
        <div class="spinner-border spinner-border-sm" role="status">
          <span class="visually-hidden">Cargando vista previa...</span>
        </div>
      </div>
    `;
    
    fetch(`{{ url('/flujos/form-preview') }}/${formId}`)
      .then(response => response.json())
      .then(data => {
        if (data.form) {
          renderFormPreview(data.form);
        }
      })
      .catch(error => {
        console.error('Error:', error);
        formPreview.innerHTML = '<div class="alert alert-danger">Error al cargar vista previa</div>';
      });
  }

  function renderFormPreview(form) {
    const formPreview = document.getElementById('formPreview');
    
    let html = `
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0">${form.nombre}</h5>
          <small class="text-muted">${form.descripcion || ''}</small>
        </div>
        <div class="card-body">
          <div class="row mb-3">
            <div class="col-sm-6">
              <strong>Tipo:</strong> ${form.type}
            </div>
            <div class="col-sm-6">
              <strong>Grupos:</strong> ${form.total_groups}
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-sm-6">
              <strong>Total Campos:</strong> ${form.total_fields}
            </div>
          </div>
    `;
    
    if (form.groups && form.groups.length > 0) {
      html += '<h6>Grupos y Campos:</h6>';
      form.groups.forEach(group => {
        html += `
          <div class="border rounded p-2 mb-2">
            <strong>${group.nombre}</strong>
            ${group.descripcion ? `<br><small class="text-muted">${group.descripcion}</small>` : ''}
            <div class="mt-2">
        `;
        
        if (group.fields && group.fields.length > 0) {
          group.fields.forEach(field => {
            html += `
              <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                <span>${field.nombre}</span>
                <div>
                  <span class="badge bg-secondary">${field.tipo}</span>
                  ${field.required ? '<span class="badge bg-danger">Requerido</span>' : ''}
                </div>
              </div>
            `;
          });
        } else {
          html += '<small class="text-muted">Sin campos</small>';
        }
        
        html += '</div></div>';
      });
    }
    
    html += '</div></div>';
    formPreview.innerHTML = html;
  }

  function addFormToStage(formId, formData) {
    if (!currentStageForForm) return;
    
    const formsList = currentStageForForm.querySelector('.stage-forms-list');
    const formId_str = uid('form');
    
    const formHtml = `
      <div class="form-item list-group-item border rounded p-2 bg-light" 
           data-form-id="${formId_str}" 
           data-form-db-id="${formId}">
        <div class="d-flex justify-content-between align-items-center">
          <div class="flex-grow-1">
            <div class="form-title fw-semibold text-success">
              <i class="fas fa-clipboard-list me-1"></i>${formData.name}
            </div>
            <div class="small text-muted form-desc">${formData.description || ''}</div>
            <span class="badge bg-success badge-sm">
              <i class="fas fa-check"></i> Asociado
            </span>
          </div>
          <div class="ms-2">
            <button type="button" class="btn btn-sm btn-outline-danger btnRemoveForm" data-form-id="${formId}">
              <i class="fas fa-unlink"></i>
            </button>
          </div>
        </div>
      </div>
    `;
    
    formsList.insertAdjacentHTML('beforeend', formHtml);
    rebuildJSON();
  }

  function removeFormFromStage(stageId, formId, formItem) {
    if (!stageId || !formId) {
      formItem.remove();
      rebuildJSON();
      return;
    }
    
    fetch(`{{ url('/flujos/etapas') }}/${stageId}/remove-form/${formId}`, {
      method: 'DELETE',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      }
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        formItem.remove();
        rebuildJSON();
      } else {
        alert(data.error || 'Error al desasociar formulario');
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('Error al desasociar formulario');
    });
  }

  // Event listener para confirmar selección de formulario
  document.getElementById('confirmFormSelection')?.addEventListener('click', () => {
    if (!selectedFormId || !currentStageForForm) return;
    
    const stageId = currentStageForForm.getAttribute('data-stage-db-id');
    
    if (isEditMode && stageId) {
      // En modo edición, hacer petición AJAX para asociar
      fetch(`{{ url('/flujos/etapas') }}/${stageId}/associate-form`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ form_id: selectedFormId })
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          addFormToStage(selectedFormId, data.form);
          bootstrap.Modal.getInstance(document.getElementById('formSelectorModal')).hide();
        } else {
          alert(data.error || 'Error al asociar formulario');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Error al asociar formulario');
      });
    } else {
      // En modo creación, solo agregar al DOM
      const selectedForm = availableForms.find(f => f.id === selectedFormId);
      if (selectedForm) {
        addFormToStage(selectedFormId, {
          name: selectedForm.nombre,
          description: selectedForm.descripcion
        });
        bootstrap.Modal.getInstance(document.getElementById('formSelectorModal')).hide();
      }
    }
  });

  // Limpiar estado cuando se cierra el modal
  document.getElementById('formSelectorModal')?.addEventListener('hidden.bs.modal', () => {
    currentStageForForm = null;
    selectedFormId = null;
    document.getElementById('confirmFormSelection').disabled = true;
    document.getElementById('formPreview').innerHTML = `
      <div class="text-center text-muted py-5">
        <i class="fas fa-eye fa-3x mb-3"></i>
        <p>Selecciona un formulario para ver la vista previa</p>
      </div>
    `;
  });

  // Fin funciones gestión de formularios

})();
</script>

<style>
  .is-selected{ outline: 2px solid #0d6efd; border-color:#0d6efd !important; background: rgba(13,110,253,.05); }
  .drag-ghost{ opacity:.4; }
  .disabled-item { 
    opacity: 0.6; 
    background-color: #f8f9fa !important;
    border-color: #dee2e6 !important;
  }
  .disabled-item .fw-semibold {
    color: #6c757d !important;
  }
  .badge-sm {
    font-size: 0.75em;
  }
  .form-check-input:focus {
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
  }
  .temporary-alert { 
    margin-bottom: 1rem; 
    animation: slideDown 0.3s ease-out;
  }
  @keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
  }
  
  /* Estilos para elementos con detalles */
  .task-item.has-details, 
  .doc-item.has-details {
    border-left: 4px solid #17a2b8;
    background: linear-gradient(90deg, rgba(23,162,184,0.05) 0%, transparent 100%);
  }
  
  /* Tooltip para elementos bloqueados */
  .badge[title] {
    cursor: help;
  }
  
  /* Estilos para documentos anidados en tareas */
  .task-docs-list {
    background-color: #f8f9fa;
    border-radius: 0.375rem;
    padding: 0.5rem;
    min-height: 2rem;
  }
  
  .task-docs-list:empty::before {
    content: "No hay documentos asignados a esta tarea";
    color: #6c757d;
    font-style: italic;
    font-size: 0.875rem;
    display: block;
    text-align: center;
    padding: 0.5rem;
  }
  
  .task-item .doc-item {
    background-color: #ffffff;
    border: 1px solid #e9ecef;
    margin-bottom: 0.25rem;
  }
  
  .task-item .doc-item:hover {
    border-color: #17a2b8;
    box-shadow: 0 0 0 0.1rem rgba(23, 162, 184, 0.25);
  }
  
  /* Estilos para el modal de gestión de tareas y documentos */
  .doc-modal-item {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
  }
  
  .doc-modal-item:hover {
    background-color: #e9ecef;
  }
  
  #tdm-docs-list:empty::before {
    content: "No hay documentos. Haz clic en 'Agregar Documento' para crear uno.";
    color: #6c757d;
    font-style: italic;
    font-size: 0.875rem;
    display: block;
    text-align: center;
    padding: 1rem;
    border: 2px dashed #dee2e6;
    border-radius: 0.375rem;
  }
  
  .modal-lg {
    max-width: 900px;
  }
</style>
