<div class="card h-100">
  <div class="card-header d-flex justify-content-between align-items-center">
    <strong>Constructor de Etapas</strong>
    <div class="d-flex gap-2">
      <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddStage">
        <i class="fas fa-plus me-1"></i> Etapa
      </button>
    </div>
  </div>

  <div class="card-body" id="builderRoot">
    <div id="stagesList" class="d-grid gap-3">
      @php $tree = json_decode($treeJson ?? '{"stages": []}', true) ?: ['stages'=>[]]; @endphp
      @foreach(($tree['stages'] ?? []) as $stage)
        <div class="stage-item border rounded p-3" data-stage-id="{{ $stage['id'] ?? uniqid('stg_') }}">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="d-flex align-items-center gap-2">
              <span class="stage-drag text-muted" style="cursor:grab"><i class="fas fa-grip-vertical"></i></span>
              <strong class="stage-title">{{ $stage['nro'] ?? 1 }}. {{ $stage['name'] ?? 'Etapa' }}</strong>
              <span class="badge bg-light text-dark ms-2">Nro: <span class="stage-nro">{{ $stage['nro'] ?? 1 }}</span></span>
              <span class="badge bg-light text-dark ms-1">Paralelo: <span class="stage-paralelo">{{ !empty($stage['paralelo']) ? 1 : 0 }}</span></span>
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
              <div class="tasks-list d-grid gap-2" data-stage-id="{{ $stage['id'] ?? '' }}">
                @foreach(($stage['tasks'] ?? []) as $t)
                  <div class="task-item list-group-item border rounded p-2" data-task-id="{{ $t['id'] ?? uniqid('tsk_') }}" tabindex="0">
                    <div class="d-flex justify-content-between align-items-center">
                      <div class="task-drag text-muted" style="cursor:grab"><i class="fas fa-grip-vertical me-2"></i></div>
                      <div class="flex-grow-1">
                        <div class="task-title fw-semibold">{{ $t['name'] ?? 'Tarea' }}</div>
                        <div class="small text-muted task-desc">{{ $t['description'] ?? '' }}</div>
                      </div>
                      <div class="ms-2">
                        <button type="button" class="btn btn-sm btn-light btnEditTask">✎</button>
                        <button type="button" class="btn btn-sm btn-light btnDelTask">🗑</button>
                      </div>
                    </div>
                  </div>
                @endforeach
              </div>
            </div>
            <div class="col-md-6">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="fw-semibold">Documentos</div>
                <button type="button" class="btn btn-sm btn-outline-primary btnAddDoc"><i class="fas fa-plus me-1"></i> Documento</button>
              </div>
              <div class="docs-list d-grid gap-2" data-stage-id="{{ $stage['id'] ?? '' }}">
                @foreach(($stage['documents'] ?? []) as $d)
                  <div class="doc-item list-group-item border rounded p-2" data-doc-id="{{ $d['id'] ?? uniqid('doc_') }}" tabindex="0">
                    <div class="d-flex justify-content-between align-items-center">
                      <div class="doc-drag text-muted" style="cursor:grab"><i class="fas fa-grip-vertical me-2"></i></div>
                      <div class="flex-grow-1">
                        <div class="doc-title fw-semibold">{{ $d['name'] ?? 'Documento' }}</div>
                        <div class="small text-muted doc-desc">{{ $d['description'] ?? '' }}</div>
                      </div>
                      <div class="ms-2">
                        <button type="button" class="btn btn-sm btn-light btnEditDoc">✎</button>
                        <button type="button" class="btn btn-sm btn-light btnDelDoc">🗑</button>
                      </div>
                    </div>
                  </div>
                @endforeach
              </div>
            </div>
          </div>

          <input type="hidden" class="stage-name" value="{{ $stage['name'] ?? 'Etapa' }}">
          <input type="hidden" class="stage-desc" value="{{ $stage['description'] ?? '' }}">
          <input type="hidden" class="stage-paralelo-input" value="{{ !empty($stage['paralelo']) ? 1 : 0 }}">
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

  </div>
</div>
