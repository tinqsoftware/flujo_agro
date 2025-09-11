<div class="card h-100">
  <div class="card-header d-flex justify-content-between align-items-center">
    <strong>Constructor de Etapas</strong>
    <div class="d-flex gap-2">
      <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddStage">
        <i class="fas fa-plus me-1"></i> Etapa
      </button>
    </div>
  </div>

  <div class="card-body" id="builderRoot" data-edit-mode="{{ isset($isEditMode) && $isEditMode ? 'true' : 'false' }}">
    <div id="stagesList" class="d-grid gap-3">
      @php $tree = json_decode($treeJson ?? '{"stages": []}', true) ?: ['stages'=>[]]; @endphp
      @foreach(($tree['stages'] ?? []) as $stage)
        <div class="stage-item border rounded p-3 {{ isset($stage['estado']) && !$stage['estado'] ? 'disabled-item' : '' }}" 
             data-stage-id="{{ $stage['id'] ?? uniqid('stg_') }}" 
             data-stage-db-id="{{ isset($stage['id']) && is_numeric($stage['id']) ? $stage['id'] : '' }}">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="d-flex align-items-center gap-2">
              <span class="stage-drag text-muted" style="cursor:grab"><i class="fas fa-grip-vertical"></i></span>
              <strong class="stage-title">{{ $stage['nro'] ?? 1 }}. {{ $stage['name'] ?? 'Etapa' }}</strong>
              <span class="badge bg-light text-dark ms-2">Nro: <span class="stage-nro">{{ $stage['nro'] ?? 1 }}</span></span>
              <span class="badge bg-light text-dark ms-1">Paralelo: <span class="stage-paralelo">{{ !empty($stage['paralelo']) ? 1 : 0 }}</span></span>
              @if(isset($stage['estado']))
                <span class="badge {{ $stage['estado'] ? 'bg-success' : 'bg-secondary' }} ms-1">
                  {{ $stage['estado'] ? 'Activo' : 'Inactivo' }}
                </span>
              @endif
            </div>
            <div class="btn-group">
              <button type="button" class="btn btn-sm btn-outline-secondary btnEditStage">Editar</button>
              @if(isset($isEditMode) && $isEditMode && isset($stage['id']) && is_numeric($stage['id']))
                <div class="form-check form-switch ms-2">
                  <input class="form-check-input stage-estado-switch" type="checkbox" 
                         data-stage-id="{{ $stage['id'] }}"
                         {{ isset($stage['estado']) && $stage['estado'] ? 'checked' : '' }}>
                </div>
              @else
                <button type="button" class="btn btn-sm btn-outline-danger btnDelStage"><i class="fas fa-trash"></i></button>
              @endif
            </div>
          </div>

          <div class="row g-3">
            <div class="col-12">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="fw-semibold">Tareas</div>
                <div class="btn-group">
                  <button type="button" class="btn btn-sm btn-outline-primary btnAddTask"><i class="fas fa-plus me-1"></i> Tarea</button>
                  <button type="button" class="btn btn-sm btn-outline-success btnAddForm" data-stage-id="{{ $stage['id'] ?? '' }}"><i class="fas fa-plus me-1"></i> Form</button>
                </div>
              </div>
              <div class="tasks-list d-grid gap-2" data-stage-id="{{ $stage['id'] ?? '' }}">
                @foreach(($stage['tasks'] ?? []) as $t)
                  <div class="task-item list-group-item border rounded p-3 mb-2 {{ isset($t['estado']) && !$t['estado'] ? 'disabled-item' : '' }} {{ isset($t['has_details']) && $t['has_details'] ? 'has-details' : '' }}" 
                       data-task-id="{{ $t['id'] ?? uniqid('tsk_') }}"
                       data-task-db-id="{{ isset($t['id']) && is_numeric($t['id']) ? $t['id'] : '' }}" 
                       data-task-rol-cambios="{{ $t['rol_cambios'] ?? '' }}"
                       data-has-details="{{ isset($t['has_details']) && $t['has_details'] ? 'true' : 'false' }}"
                       tabindex="0">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <div class="task-drag text-muted" style="cursor:grab"><i class="fas fa-grip-vertical me-2"></i></div>
                      <div class="flex-grow-1">
                        <div class="task-title fw-semibold">{{ $t['name'] ?? 'Tarea' }}</div>
                        <div class="small text-muted task-desc">{{ $t['description'] ?? '' }}</div>
                        @if(isset($t['estado']))
                          <span class="badge {{ $t['estado'] ? 'bg-success' : 'bg-secondary' }} badge-sm">
                            {{ $t['estado'] ? 'Activo' : 'Inactivo' }}
                          </span>
                        @endif
                        @if(isset($t['rol_cambios']) && $t['rol_cambios'])
                          @php
                            $rolNombre = isset($roles) ? $roles->firstWhere('id', $t['rol_cambios'])?->nombre : 'Rol ID: '.$t['rol_cambios'];
                          @endphp
                          <span class="badge bg-primary badge-sm badge-rol">
                            <i class="fas fa-user-tag"></i> {{ $rolNombre }}
                          </span>
                        @else
                          <span class="badge bg-warning badge-sm badge-rol">
                            <i class="fas fa-users"></i> Todos los roles
                          </span>
                        @endif
                        @if(isset($t['has_details']) && $t['has_details'])
                          <span class="badge bg-info badge-sm" title="Tiene registros asociados">
                            <i class="fas fa-link"></i> En uso
                          </span>
                        @endif
                      </div>
                      <div class="ms-2 d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-sm btn-light btnEditTask">✎</button>
                        @if(isset($isEditMode) && $isEditMode && isset($t['id']) && is_numeric($t['id']))
                          @if(isset($t['has_details']) && $t['has_details'])
                            {{-- Si tiene detalles, solo mostrar indicador de que no se puede modificar --}}
                            <span class="badge bg-warning text-dark ms-1" title="No se puede desactivar - tiene registros asociados">
                              <i class="fas fa-lock"></i>
                            </span>
                          @else
                            {{-- Si no tiene detalles, mostrar switch --}}
                            <div class="form-check form-switch">
                              <input class="form-check-input task-estado-switch" type="checkbox" 
                                     data-task-id="{{ $t['id'] }}"
                                     {{ isset($t['estado']) && $t['estado'] ? 'checked' : '' }}>
                            </div>
                          @endif
                        @else
                          <button type="button" class="btn btn-sm btn-light btnDelTask">🗑</button>
                        @endif
                      </div>
                    </div>
                    
                    {{-- Documentos de esta tarea --}}
                    <div class="mt-2">
                      <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="fw-semibold small text-muted">Documentos de esta tarea</div>
                        <button type="button" class="btn btn-sm btn-outline-info btnAddTaskDoc"><i class="fas fa-plus me-1"></i> Documento</button>
                      </div>
                      <div class="task-docs-list d-grid gap-1" data-task-id="{{ $t['id'] ?? '' }}">
                        @foreach(($t['documents'] ?? []) as $d)
                          <div class="doc-item list-group-item border rounded p-2 {{ isset($d['estado']) && !$d['estado'] ? 'disabled-item' : '' }} {{ isset($d['has_details']) && $d['has_details'] ? 'has-details' : '' }}" 
                               data-doc-id="{{ $d['id'] ?? uniqid('doc_') }}"
                               data-doc-db-id="{{ isset($d['id']) && is_numeric($d['id']) ? $d['id'] : '' }}" 
                               data-doc-rol-cambios="{{ $d['rol_cambios'] ?? '' }}"
                               data-has-details="{{ isset($d['has_details']) && $d['has_details'] ? 'true' : 'false' }}"
                               tabindex="0">
                            <div class="d-flex justify-content-between align-items-center">
                              <div class="doc-drag text-muted" style="cursor:grab"><i class="fas fa-grip-vertical me-2"></i></div>
                              <div class="flex-grow-1">
                                <div class="doc-title fw-semibold">{{ $d['name'] ?? 'Documento' }}</div>
                                <div class="small text-muted doc-desc">{{ $d['description'] ?? '' }}</div>
                                @if(isset($d['estado']))
                                  <span class="badge {{ $d['estado'] ? 'bg-success' : 'bg-secondary' }} badge-sm">
                                    {{ $d['estado'] ? 'Activo' : 'Inactivo' }}
                                  </span>
                                @endif
                                @if(isset($d['rol_cambios']) && $d['rol_cambios'])
                                  @php
                                    $rolNombre = isset($roles) ? $roles->firstWhere('id', $d['rol_cambios'])?->nombre : 'Rol ID: '.$d['rol_cambios'];
                                  @endphp
                                  <span class="badge bg-primary badge-sm badge-rol">
                                    <i class="fas fa-user-tag"></i> {{ $rolNombre }}
                                  </span>
                                @else
                                  <span class="badge bg-warning badge-sm badge-rol">
                                    <i class="fas fa-users"></i> Todos los roles
                                  </span>
                                @endif
                                @if(isset($d['has_details']) && $d['has_details'])
                                  <span class="badge bg-info badge-sm" title="Tiene registros asociados">
                                    <i class="fas fa-link"></i> En uso
                                  </span>
                                @endif
                              </div>
                              <div class="ms-2 d-flex align-items-center gap-1">
                                <button type="button" class="btn btn-sm btn-light btnEditDoc">✎</button>
                                @if(isset($isEditMode) && $isEditMode && isset($d['id']) && is_numeric($d['id']))
                                  @if(isset($d['has_details']) && $d['has_details'])
                                    {{-- Si tiene detalles, solo mostrar indicador de que no se puede modificar --}}
                                    <span class="badge bg-warning text-dark ms-1" title="No se puede desactivar - tiene registros asociados">
                                      <i class="fas fa-lock"></i>
                                    </span>
                                  @else
                                    {{-- Si no tiene detalles, mostrar switch --}}
                                    <div class="form-check form-switch">
                                      <input class="form-check-input doc-estado-switch" type="checkbox" 
                                             data-doc-id="{{ $d['id'] }}"
                                             {{ isset($d['estado']) && $d['estado'] ? 'checked' : '' }}>
                                    </div>
                                  @endif
                                @else
                                  <button type="button" class="btn btn-sm btn-light btnDelDoc">🗑</button>
                                @endif
                              </div>
                            </div>
                          </div>
                        @endforeach
                      </div>
                    </div>
                  </div>
                @endforeach
              </div>
              
              {{-- Formularios asociados a esta etapa --}}
              <div class="mt-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <div class="fw-semibold small text-muted">Formularios asociados</div>
                </div>
                <div class="stage-forms-list d-grid gap-1" data-stage-id="{{ $stage['id'] ?? '' }}">
                  @foreach(($stage['forms'] ?? []) as $f)
                    <div class="form-item list-group-item border rounded p-2 bg-light" 
                         data-form-id="{{ $f['id'] ?? uniqid('form_') }}"
                         data-form-db-id="{{ isset($f['id']) && is_numeric($f['id']) ? $f['id'] : '' }}">
                      <div class="d-flex justify-content-between align-items-center">
                        <div class="flex-grow-1">
                          <div class="form-title fw-semibold text-success">
                            <i class="fas fa-clipboard-list me-1"></i>{{ $f['name'] ?? 'Formulario' }}
                          </div>
                          <div class="small text-muted form-desc">{{ $f['description'] ?? '' }}</div>
                          <span class="badge bg-success badge-sm">
                            <i class="fas fa-check"></i> Asociado
                          </span>
                        </div>
                        <div class="ms-2">
                          <button type="button" class="btn btn-sm btn-outline-danger btnRemoveForm" data-form-id="{{ $f['id'] ?? '' }}">
                            <i class="fas fa-unlink"></i>
                          </button>
                        </div>
                      </div>
                    </div>
                  @endforeach
                </div>
              </div>
            </div>
          </div>

          <input type="hidden" class="stage-name" value="{{ $stage['name'] ?? 'Etapa' }}">
          <input type="hidden" class="stage-desc" value="{{ $stage['description'] ?? '' }}">
          <input type="hidden" class="stage-paralelo-input" value="{{ !empty($stage['paralelo']) ? 1 : 0 }}">
          <input type="hidden" class="stage-estado-input" value="{{ isset($stage['estado']) ? $stage['estado'] : 1 }}">
        </div>
      @endforeach
    </div>

    {{-- Modal reutilizable --}}
    <div class="modal fade" id="builderModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header"><h5 class="modal-title"></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Nombre</label>
              <input type="text" class="form-control" id="bm-name">
            </div>
            <div class="mb-3">
              <label class="form-label">Descripción</label>
              <textarea class="form-control" id="bm-desc" rows="3"></textarea>
            </div>
            <div class="mb-3 d-none" id="bm-rol-wrap">
              <label class="form-label">Rol para completar tarea</label>
              <select class="form-select" id="bm-rol">
                <option value="">Todos los roles</option>
                @if(isset($roles))
                  @foreach($roles as $rol)
                    <option value="{{ $rol->id }}">{{ $rol->nombre }}</option>
                  @endforeach
                @endif
              </select>
              <div class="form-text">Si selecciona "Todos los roles", cualquier usuario con rol diferente a SUPERADMIN podrá completar esta tarea.</div>
            </div>
            <div class="mb-3 d-none" id="bm-paralelo-wrap">
              <label class="form-label">Paralelo</label>
              <select class="form-select" id="bm-paralelo">
                <option value="0">No</option>
                <option value="1">Sí</option>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="button" class="btn btn-primary" id="bm-save">Guardar</button>
          </div>
        </div>
      </div>
    </div>

    {{-- Modal para seleccionar formularios --}}
    <div class="modal fade" id="formSelectorModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-xl">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Seleccionar Formulario para la Etapa</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="col-md-4">
                <h6>Formularios Disponibles</h6>
                <div id="formsList" class="list-group">
                  <div class="text-center py-3">
                    <div class="spinner-border spinner-border-sm" role="status">
                      <span class="visually-hidden">Cargando...</span>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-8">
                <h6>Vista Previa del Formulario</h6>
                <div id="formPreview" class="border rounded p-3" style="min-height: 400px;">
                  <div class="text-center text-muted py-5">
                    <i class="fas fa-eye fa-3x mb-3"></i>
                    <p>Selecciona un formulario para ver la vista previa</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="button" class="btn btn-success" id="confirmFormSelection" disabled>Agregar Formulario</button>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>
