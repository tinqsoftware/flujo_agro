@extends('layouts.dashboard')
@section('title','Ejecutar: ' . $flujo->nombre)
@section('page-title','Ejecución de Flujo')
@section('page-subtitle', $flujo->nombre)

@section('header-actions')
    <a href="{{ route('ejecucion.index') }}" class="btn btn-light">
        <i class="fas fa-arrow-left me-1"></i> Volver a Flujos
    </a>
@endsection

@section('content-area')
<!-- Header del flujo -->
<div class="header-section mb-4">
    <div class="row align-items-center">
        <div class="col">
            <h2 class="h4 mb-1 text-white">{{ $flujo->nombre }}</h2>
            <div class="d-flex align-items-center text-white-50">
                <span class="badge bg-white text-primary me-2">{{ $flujo->tipo->nombre ?? 'Sin tipo' }}</span>
                @if($isSuper)
                    <span class="badge bg-light text-dark">{{ $flujo->empresa->nombre ?? 'Sin empresa' }}</span>
                @endif
            </div>
        </div>
        <div class="col-auto">
            <div class="text-white text-center">
                <div class="h5 mb-0">{{ $flujo->etapas->count() }}</div>
                <small>Etapas</small>
            </div>
        </div>
    </div>
    
    @if($flujo->descripcion)
        <div class="mt-3">
            <p class="text-white-75 mb-0">{{ $flujo->descripcion }}</p>
        </div>
    @endif
</div>

<!-- Progreso del flujo -->
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0">Progreso del Flujo</h6>
            <span class="text-muted">0 de {{ $flujo->etapas->count() }} etapas completadas</span>
        </div>
        <div class="progress" style="height: 8px;">
            <div class="progress-bar bg-success" role="progressbar" style="width: 0%"></div>
        </div>
    </div>
</div>

<!-- Etapas del flujo -->
<div class="row g-4">
    @forelse($flujo->etapas as $index => $etapa)
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <div class="row align-items-center">
                        <div class="col">
                            <h5 class="mb-0">
                                <span class="badge bg-primary me-2">{{ $etapa->nro }}</span>
                                {{ $etapa->nombre }}
                                @if($etapa->paralelo)
                                    <span class="badge bg-warning ms-2">
                                        <i class="fas fa-code-branch me-1"></i>Paralelo
                                    </span>
                                @endif
                            </h5>
                            @if($etapa->descripcion)
                                <p class="text-muted mb-0 mt-1">{{ $etapa->descripcion }}</p>
                            @endif
                        </div>
                        <div class="col-auto">
                            <div class="d-flex align-items-center gap-3">
                                @if($etapa->tareas->count() > 0)
                                    <div class="text-center">
                                        <div class="text-primary h6 mb-0">{{ $etapa->tareas->count() }}</div>
                                        <small class="text-muted">Tareas</small>
                                    </div>
                                @endif
                                @if($etapa->documentos->count() > 0)
                                    <div class="text-center">
                                        <div class="text-info h6 mb-0">{{ $etapa->documentos->count() }}</div>
                                        <small class="text-muted">Documentos</small>
                                    </div>
                                @endif
                                <button class="btn btn-outline-primary btn-sm" type="button" 
                                        data-bs-toggle="collapse" 
                                        data-bs-target="#etapa-{{ $etapa->id }}" 
                                        aria-expanded="false">
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="collapse" id="etapa-{{ $etapa->id }}">
                    <div class="card-body">
                        <div class="row">
                            <!-- Tareas -->
                            @if($etapa->tareas->count() > 0)
                                <div class="{{ $etapa->documentos->count() > 0 ? 'col-md-6' : 'col-12' }}">
                                    <h6 class="text-primary mb-3">
                                        <i class="fas fa-tasks me-2"></i>Tareas
                                    </h6>
                                    <div class="list-group list-group-flush">
                                        @foreach($etapa->tareas as $tarea)
                                            <div class="list-group-item border-0 px-0">
                                                <div class="d-flex align-items-center">
                                                    <div class="form-check me-3">
                                                        <input class="form-check-input" type="checkbox" 
                                                               id="tarea-{{ $tarea->id }}" 
                                                               data-tarea-id="{{ $tarea->id }}">
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <label class="form-check-label fw-medium" for="tarea-{{ $tarea->id }}">
                                                            {{ $tarea->nombre }}
                                                        </label>
                                                        @if($tarea->descripcion)
                                                            <div class="text-muted small">{{ $tarea->descripcion }}</div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <!-- Documentos -->
                            @if($etapa->documentos->count() > 0)
                                <div class="{{ $etapa->tareas->count() > 0 ? 'col-md-6' : 'col-12' }}">
                                    <h6 class="text-info mb-3">
                                        <i class="fas fa-file-alt me-2"></i>Documentos
                                    </h6>
                                    <div class="list-group list-group-flush">
                                        @foreach($etapa->documentos as $documento)
                                            <div class="list-group-item border-0 px-0">
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <div>
                                                        <div class="fw-medium">{{ $documento->nombre }}</div>
                                                        @if($documento->descripcion)
                                                            <div class="text-muted small">{{ $documento->descripcion }}</div>
                                                        @endif
                                                    </div>
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-outline-secondary" 
                                                                title="Subir documento"
                                                                data-documento-id="{{ $documento->id }}">
                                                            <i class="fas fa-upload"></i>
                                                        </button>
                                                        <button class="btn btn-outline-primary" 
                                                                title="Ver documento"
                                                                data-documento-id="{{ $documento->id }}">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Botón de completar etapa -->
                        <div class="mt-4 text-end">
                            <button class="btn btn-success" data-etapa-id="{{ $etapa->id }}">
                                <i class="fas fa-check me-2"></i>Completar Etapa
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                    <h5 class="text-muted">Sin etapas configuradas</h5>
                    <p class="text-muted mb-0">Este flujo no tiene etapas configuradas para ejecutar.</p>
                </div>
            </div>
        </div>
    @endforelse
</div>

<!-- Modal para subir documentos -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Subir Documento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="uploadForm">
                    <div class="mb-3">
                        <label class="form-label">Seleccionar archivo</label>
                        <input type="file" class="form-control" id="documentFile" multiple>
                        <div class="form-text">Puedes seleccionar múltiples archivos</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Comentarios (opcional)</label>
                        <textarea class="form-control" rows="3" id="documentComments" placeholder="Agregar comentarios sobre el documento..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="saveDocument">
                    <i class="fas fa-upload me-2"></i>Subir Documento
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('styles')
<style>
.header-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    margin: -1.5rem -1.5rem 0 -1.5rem;
    padding: 2rem 1.5rem;
    color: white;
    border-radius: 0.5rem 0.5rem 0 0;
}

.text-white-75 {
    color: rgba(255,255,255,0.75) !important;
}

.text-white-50 {
    color: rgba(255,255,255,0.5) !important;
}

.card-header {
    border-bottom: 1px solid rgba(0,0,0,0.125);
}

.list-group-item {
    transition: background-color 0.15s ease-in-out;
}

.list-group-item:hover {
    background-color: rgba(0,0,0,0.025);
}

.form-check-input:checked {
    background-color: #28a745;
    border-color: #28a745;
}

.btn-group-sm > .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}
</style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Manejar clics en botones de subir documento
    document.querySelectorAll('[data-documento-id]').forEach(btn => {
        if (btn.querySelector('.fa-upload')) {
            btn.addEventListener('click', function() {
                const documentoId = this.dataset.documentoId;
                const modal = new bootstrap.Modal(document.getElementById('uploadModal'));
                modal.show();
                
                // Guardar ID del documento en el modal
                document.getElementById('uploadModal').dataset.documentoId = documentoId;
            });
        }
    });

    // Manejar guardado de documento
    document.getElementById('saveDocument').addEventListener('click', function() {
        const documentoId = document.getElementById('uploadModal').dataset.documentoId;
        const files = document.getElementById('documentFile').files;
        const comments = document.getElementById('documentComments').value;

        if (files.length === 0) {
            alert('Por favor selecciona al menos un archivo');
            return;
        }

        // Aquí iría la lógica para subir el archivo
        console.log('Subir documento:', {documentoId, files, comments});
        
        // Cerrar modal y limpiar
        bootstrap.Modal.getInstance(document.getElementById('uploadModal')).hide();
        document.getElementById('uploadForm').reset();
        
        // Mostrar mensaje de éxito
        alert('Documento subido correctamente');
    });

    // Manejar check de tareas
    document.querySelectorAll('[data-tarea-id]').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const tareaId = this.dataset.tareaId;
            const completed = this.checked;
            
            // Aquí iría la lógica para marcar la tarea como completada
            console.log('Tarea completada:', {tareaId, completed});
            
            // Actualizar progreso
            updateProgress();
        });
    });

    // Manejar completar etapa
    document.querySelectorAll('[data-etapa-id]').forEach(btn => {
        btn.addEventListener('click', function() {
            const etapaId = this.dataset.etapaId;
            
            if (confirm('¿Estás seguro de que quieres completar esta etapa?')) {
                // Aquí iría la lógica para completar la etapa
                console.log('Etapa completada:', etapaId);
                
                this.innerHTML = '<i class="fas fa-check me-2"></i>Etapa Completada';
                this.classList.remove('btn-success');
                this.classList.add('btn-secondary');
                this.disabled = true;
                
                updateProgress();
            }
        });
    });

    function updateProgress() {
        // Aquí calcularías el progreso real basado en las etapas/tareas completadas
        const totalEtapas = {!! $flujo->etapas->count() !!};
        const completedEtapas = document.querySelectorAll('[data-etapa-id]:disabled').length;
        const progress = totalEtapas > 0 ? (completedEtapas / totalEtapas) * 100 : 0;
        
        const progressBar = document.querySelector('.progress-bar');
        const progressText = document.querySelector('.card-body span.text-muted');
        
        progressBar.style.width = progress + '%';
        progressText.textContent = `${completedEtapas} de ${totalEtapas} etapas completadas`;
    }
});
</script>
@endsection
