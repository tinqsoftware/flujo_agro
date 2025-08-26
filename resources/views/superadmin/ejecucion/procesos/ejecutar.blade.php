@extends('layouts.dashboard')
@section('title','Ejecución: ' . $flujo->nombre)
@section('page-title','Ejecución de Flujo')
@section('page-subtitle', $flujo->nombre)

@section('header-actions')
    <a href="{{ route('ejecucion.index') }}" class="btn btn-light">
        <i class="fas fa-arrow-left me-1"></i> Volver a Flujos
    </a>
@endsection

@section('content-area')
<!-- Header del flujo con progreso -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card border-0 bg-primary text-white">
            <div class="card-body">
                <h4 class="card-title mb-1">Ejecución de Flujos</h4>
                <div class="d-flex align-items-center mb-2">
                    <span class="me-3">Flujo: <strong>{{ $flujo->nombre }}</strong></span>
                    <span class="me-3">Tipo: <strong>{{ $flujo->tipo->nombre ?? 'Sin tipo' }}</strong></span>
                    @if(!$flujo->proceso_iniciado)
                        <span class="badge bg-warning text-dark">Sin Iniciar</span>
                    @else
                        <span class="badge bg-success">En Progreso</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 text-center">
            <div class="card-body">
                <h5 class="text-primary mb-1">Progreso</h5>
                <h2 class="mb-0" id="progreso-general">0%</h2>
            </div>
        </div>
    </div>
</div>

<!-- Control de Ejecución -->
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-1">Control de Ejecución</h5>
                <p class="text-muted mb-0">Flujo: {{ $flujo->nombre }} • {{ $flujo->etapas->count() }} etapas</p>
            </div>
            <div>
                @if(!$flujo->proceso_iniciado)
                    <button class="btn btn-primary" id="iniciar-ejecucion" data-flujo-id="{{ $flujo->id }}">
                        <i class="fas fa-play me-2"></i>Iniciar Ejecución
                    </button>
                @else
                    <button class="btn btn-success" disabled>
                        <i class="fas fa-clock me-2"></i>En Progreso
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Etapas del flujo -->
@foreach($flujo->etapas as $index => $etapa)
<div class="card mb-3 etapa-card" data-etapa-id="{{ $etapa->id }}">
    <div class="card-header bg-light">
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <div class="estado-etapa me-3">
                    <i class="fas fa-circle text-secondary" id="estado-etapa-{{ $etapa->id }}"></i>
                </div>
                <div>
                    <h6 class="mb-0">{{ $etapa->nro }}. {{ $etapa->nombre }}</h6>
                    <small class="text-muted">
                        Pendiente • Progreso: <span class="progreso-etapa" data-etapa="{{ $etapa->id }}">0%</span>
                    </small>
                </div>
            </div>
            <div>
                <button class="btn btn-sm btn-outline-primary" type="button" 
                        data-bs-toggle="collapse" 
                        data-bs-target="#etapa-content-{{ $etapa->id }}" 
                        aria-expanded="false">
                    <i class="fas fa-chevron-down"></i>
                </button>
            </div>
        </div>
    </div>
    
    <div class="collapse" id="etapa-content-{{ $etapa->id }}">
        <div class="card-body">
            <div class="row">
                <!-- Tareas -->
                @if($etapa->tareas->count() > 0)
                <div class="col-md-6">
                    <div class="d-flex align-items-center mb-3">
                        <i class="fas fa-tasks text-primary me-2"></i>
                        <h6 class="mb-0">Tareas ({{ $etapa->tareas->where('completada', true)->count() }}/{{ $etapa->tareas->count() }})</h6>
                    </div>
                    
                    <div class="tareas-list">
                        @foreach($etapa->tareas as $tarea)
                        <div class="d-flex align-items-center mb-2 tarea-item" data-tarea-id="{{ $tarea->id }}">
                            <div class="form-check me-3">
                                <input class="form-check-input tarea-checkbox" 
                                       type="checkbox" 
                                       id="tarea-{{ $tarea->id }}" 
                                       data-tarea-id="{{ $tarea->id }}"
                                       {{ $tarea->completada ? 'checked' : '' }}>
                            </div>
                            <div class="flex-grow-1">
                                <label class="form-check-label {{ $tarea->completada ? 'text-decoration-line-through text-muted' : '' }}" 
                                       for="tarea-{{ $tarea->id }}">
                                    {{ $tarea->nombre }}
                                </label>
                                @if($tarea->descripcion)
                                    <div class="small text-muted">{{ $tarea->descripcion }}</div>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Documentos -->
                @if($etapa->documentos->count() > 0)
                <div class="col-md-6">
                    <div class="d-flex align-items-center mb-3">
                        <i class="fas fa-file-pdf text-danger me-2"></i>
                        <h6 class="mb-0">Documentos ({{ $etapa->documentos->where('subido', true)->count() }}/{{ $etapa->documentos->count() }})</h6>
                    </div>
                    
                    <div class="documentos-list">
                        @foreach($etapa->documentos as $documento)
                        <div class="documento-item mb-3 p-3 border rounded" data-documento-id="{{ $documento->id }}">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">{{ $documento->nombre }}</h6>
                                    @if($documento->descripcion)
                                        <p class="text-muted small mb-2">{{ $documento->descripcion }}</p>
                                    @endif
                                    
                                    <!-- Estado del documento -->
                                    <div class="document-status" id="status-{{ $documento->id }}">
                                        @if($documento->archivo_url)
                                            <span class="badge bg-success">
                                                <i class="fas fa-check me-1"></i>Subido
                                            </span>
                                        @else
                                            <span class="badge bg-warning text-dark">
                                                <i class="fas fa-clock me-1"></i>Pendiente
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="btn-group-vertical btn-group-sm">
                                    @if($documento->archivo_url)
                                        <!-- Botón para ver PDF -->
                                        <button class="btn btn-outline-primary btn-sm ver-pdf" 
                                                data-documento-id="{{ $documento->id }}"
                                                data-url="{{ $documento->archivo_url }}"
                                                title="Ver PDF">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <!-- Botón para descargar -->
                                        <a href="{{ $documento->archivo_url }}" 
                                           class="btn btn-outline-secondary btn-sm" 
                                           download
                                           title="Descargar">
                                            <i class="fas fa-download"></i>
                                        </a>
                                    @endif
                                    
                                    <!-- Botón para subir/cambiar archivo -->
                                    <button class="btn btn-outline-primary btn-sm subir-documento" 
                                            data-documento-id="{{ $documento->id }}"
                                            title="{{ $documento->archivo_url ? 'Cambiar archivo' : 'Subir archivo' }}">
                                        <i class="fas fa-upload"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endforeach

<!-- Modal para subir documentos -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Subir Documento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="uploadForm" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Documento: <span id="documento-nombre"></span></label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Seleccionar archivo PDF</label>
                        <input type="file" class="form-control" id="documentFile" accept=".pdf" required>
                        <div class="form-text">Solo se permiten archivos PDF (máximo 10MB)</div>
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

<!-- Modal para visualizar PDF -->
<div class="modal fade" id="pdfModal" tabindex="-1">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Visor de PDF - <span id="pdf-title"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <iframe id="pdf-viewer" src="" width="100%" height="600px" frameborder="0"></iframe>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <a id="pdf-download" href="" class="btn btn-primary" download>
                    <i class="fas fa-download me-2"></i>Descargar
                </a>
            </div>
        </div>
    </div>
</div>

@endsection

@section('styles')
<style>
.etapa-card {
    border: 1px solid #e9ecef;
    transition: all 0.3s ease;
}

.etapa-card.activa {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.etapa-card.completada {
    border-color: #28a745;
    background-color: #f8fff9;
}

.estado-etapa i.text-warning {
    color: #ffc107 !important;
}

.estado-etapa i.text-success {
    color: #28a745 !important;
}

.estado-etapa i.text-primary {
    color: #007bff !important;
}

.tarea-item {
    transition: all 0.2s ease;
}

.tarea-item:hover {
    background-color: #f8f9fa;
    border-radius: 0.25rem;
    padding: 0.25rem;
}

.documento-item {
    transition: all 0.2s ease;
}

.documento-item:hover {
    background-color: #f8f9fa;
}

.btn-group-vertical > .btn {
    margin-bottom: 2px;
}

.btn-group-vertical > .btn:last-child {
    margin-bottom: 0;
}

#pdf-viewer {
    height: calc(100vh - 200px);
    min-height: 500px;
}

.progress-ring {
    transform: rotate(-90deg);
}

.progress-ring__circle {
    stroke: #e9ecef;
    stroke-width: 4;
    fill: transparent;
    stroke-dasharray: 283;
    stroke-dashoffset: 283;
    transition: stroke-dashoffset 0.5s ease-in-out;
}

.progress-ring__circle.active {
    stroke: #007bff;
}
</style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let procesoIniciado = {!! $flujo->proceso_iniciado ? 'true' : 'false' !!};
    const flujoId = {{ $flujo->id }};
    
    // Inicializar estado del flujo
    if (procesoIniciado) {
        actualizarProgreso();
    }

    // Iniciar ejecución
    document.getElementById('iniciar-ejecucion')?.addEventListener('click', function() {
        const btn = this;
        
        if (confirm('¿Estás seguro de que quieres iniciar la ejecución de este flujo?')) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Iniciando...';
            
            fetch(`{{ route('ejecucion.iniciar', ':flujo') }}`.replace(':flujo', flujoId), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    procesoIniciado = true;
                    btn.innerHTML = '<i class="fas fa-clock me-2"></i>En Progreso';
                    btn.classList.remove('btn-primary');
                    btn.classList.add('btn-success');
                    
                    // Actualizar badge de estado
                    const badge = document.querySelector('.badge.bg-warning');
                    if (badge) {
                        badge.classList.remove('bg-warning', 'text-dark');
                        badge.classList.add('bg-success');
                        badge.innerHTML = 'En Progreso';
                    }
                    
                    // Activar primera etapa
                    const primeraEtapa = document.querySelector('.etapa-card');
                    if (primeraEtapa) {
                        primeraEtapa.classList.add('activa');
                        const estadoIcon = primeraEtapa.querySelector('.estado-etapa i');
                        estadoIcon.classList.remove('text-secondary');
                        estadoIcon.classList.add('text-primary');
                    }
                    
                    actualizarProgreso();
                } else {
                    alert('Error al iniciar el proceso: ' + data.message);
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-play me-2"></i>Iniciar Ejecución';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al iniciar el proceso');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-play me-2"></i>Iniciar Ejecución';
            });
        }
    });

    // Manejar checkboxes de tareas
    document.querySelectorAll('.tarea-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            if (!procesoIniciado) {
                this.checked = false;
                alert('Debes iniciar la ejecución del flujo primero');
                return;
            }

            const tareaId = this.dataset.tareaId;
            const completada = this.checked;
            const label = this.parentElement.nextElementSibling.querySelector('label');
            
            // Deshabilitar checkbox mientras se procesa
            this.disabled = true;
            
            fetch('{{ route('ejecucion.tarea.actualizar') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    tarea_id: tareaId,
                    completada: completada
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Actualizar UI
                    if (data.completada) {
                        label.classList.add('text-decoration-line-through', 'text-muted');
                    } else {
                        label.classList.remove('text-decoration-line-through', 'text-muted');
                    }
                    
                    // Actualizar progreso de la etapa
                    const etapaCard = this.closest('.etapa-card');
                    actualizarProgresoEtapa(etapaCard);
                    actualizarProgreso();
                } else {
                    alert('Error al actualizar la tarea: ' + data.message);
                    this.checked = !completada; // Revertir estado
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al actualizar la tarea');
                this.checked = !completada; // Revertir estado
            })
            .finally(() => {
                this.disabled = false;
            });
        });
    });

    // Manejar subida de documentos
    document.querySelectorAll('.subir-documento').forEach(btn => {
        btn.addEventListener('click', function() {
            if (!procesoIniciado) {
                alert('Debes iniciar la ejecución del flujo primero');
                return;
            }

            const documentoId = this.dataset.documentoId;
            const documentoItem = this.closest('.documento-item');
            const documentoNombre = documentoItem.querySelector('h6').textContent;
            
            document.getElementById('documento-nombre').textContent = documentoNombre;
            document.getElementById('uploadModal').dataset.documentoId = documentoId;
            
            const modal = new bootstrap.Modal(document.getElementById('uploadModal'));
            modal.show();
        });
    });

    // Guardar documento
    document.getElementById('saveDocument').addEventListener('click', function() {
        const documentoId = document.getElementById('uploadModal').dataset.documentoId;
        const fileInput = document.getElementById('documentFile');
        const file = fileInput.files[0];
        const comments = document.getElementById('documentComments').value;

        if (!file) {
            alert('Por favor selecciona un archivo PDF');
            return;
        }

        if (file.type !== 'application/pdf') {
            alert('Solo se permiten archivos PDF');
            return;
        }

        if (file.size > 10 * 1024 * 1024) { // 10MB
            alert('El archivo es demasiado grande. Máximo 10MB');
            return;
        }

        // Preparar FormData
        const formData = new FormData();
        formData.append('documento_id', documentoId);
        formData.append('archivo', file);
        formData.append('comentarios', comments);

        // Deshabilitar botón mientras se sube
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Subiendo...';

        fetch('{{ route('ejecucion.documento.subir') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const documentoItem = document.querySelector(`[data-documento-id="${documentoId}"]`);
                const statusElement = documentoItem.querySelector('.document-status');
                const btnGroup = documentoItem.querySelector('.btn-group-vertical');
                
                // Actualizar estado
                statusElement.innerHTML = '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Subido</span>';
                
                // Agregar botones de ver y descargar
                btnGroup.innerHTML = `
                    <button class="btn btn-outline-primary btn-sm ver-pdf" 
                            data-documento-id="${documentoId}"
                            data-url="${data.archivo_url}"
                            title="Ver PDF">
                        <i class="fas fa-eye"></i>
                    </button>
                    <a href="${data.archivo_url}" 
                       class="btn btn-outline-secondary btn-sm" 
                       download="${data.nombre_archivo}"
                       title="Descargar">
                        <i class="fas fa-download"></i>
                    </a>
                    <button class="btn btn-outline-primary btn-sm subir-documento" 
                            data-documento-id="${documentoId}"
                            title="Cambiar archivo">
                        <i class="fas fa-upload"></i>
                    </button>
                `;
                
                // Reagregar event listeners
                btnGroup.querySelector('.ver-pdf').addEventListener('click', verPDF);
                btnGroup.querySelector('.subir-documento').addEventListener('click', arguments.callee.parentNode);
                
                // Cerrar modal
                bootstrap.Modal.getInstance(document.getElementById('uploadModal')).hide();
                document.getElementById('uploadForm').reset();
                
                // Actualizar progreso
                const etapaCard = documentoItem.closest('.etapa-card');
                actualizarProgresoEtapa(etapaCard);
                actualizarProgreso();
                
                console.log('Documento subido:', data);
            } else {
                alert('Error al subir el documento: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al subir el documento');
        })
        .finally(() => {
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-upload me-2"></i>Subir Documento';
        });
    });

    // Ver PDF
    function verPDF() {
        const documentoId = this.dataset.documentoId;
        const url = this.dataset.url;
        const documentoNombre = this.closest('.documento-item').querySelector('h6').textContent;
        
        document.getElementById('pdf-title').textContent = documentoNombre;
        document.getElementById('pdf-viewer').src = url;
        document.getElementById('pdf-download').href = url;
        
        const modal = new bootstrap.Modal(document.getElementById('pdfModal'));
        modal.show();
    }

    document.querySelectorAll('.ver-pdf').forEach(btn => {
        btn.addEventListener('click', verPDF);
    });

    function actualizarProgresoEtapa(etapaCard) {
        // Esta función ahora obtiene datos reales del servidor
        fetch(`{{ route('ejecucion.progreso', ':flujo') }}`.replace(':flujo', flujoId))
        .then(response => response.json())
        .then(data => {
            if (data.progreso_general !== undefined) {
                document.getElementById('progreso-general').textContent = data.progreso_general + '%';
                
                // Actualizar progreso de cada etapa
                data.etapas.forEach(etapaData => {
                    const etapaElement = document.querySelector(`[data-etapa-id="${etapaData.id}"]`);
                    if (etapaElement) {
                        const progresoElement = etapaElement.querySelector('.progreso-etapa');
                        const estadoIcon = etapaElement.querySelector('.estado-etapa i');
                        const statusText = etapaElement.querySelector('.card-header small');
                        
                        progresoElement.textContent = etapaData.progreso + '%';
                        
                        // Actualizar contadores de tareas y documentos
                        const tareasHeader = etapaElement.querySelector('.tareas-list')?.parentElement.querySelector('h6');
                        const documentosHeader = etapaElement.querySelector('.documentos-list')?.parentElement.querySelector('h6');
                        
                        if (tareasHeader) {
                            tareasHeader.textContent = `Tareas (${etapaData.tareas_completadas}/${etapaData.total_tareas})`;
                        }
                        if (documentosHeader) {
                            documentosHeader.textContent = `Documentos (${etapaData.documentos_subidos}/${etapaData.total_documentos})`;
                        }
                        
                        // Actualizar estado visual de la etapa
                        if (etapaData.progreso === 100) {
                            etapaElement.classList.remove('activa');
                            etapaElement.classList.add('completada');
                            estadoIcon.classList.remove('text-primary', 'text-warning');
                            estadoIcon.classList.add('text-success');
                            statusText.innerHTML = `Completada • Progreso: <span class="progreso-etapa">${etapaData.progreso}%</span>`;
                            
                            // Activar siguiente etapa
                            const siguienteEtapa = etapaElement.nextElementSibling;
                            if (siguienteEtapa && siguienteEtapa.classList.contains('etapa-card') && !siguienteEtapa.classList.contains('activa') && !siguienteEtapa.classList.contains('completada')) {
                                siguienteEtapa.classList.add('activa');
                                const siguienteIcon = siguienteEtapa.querySelector('.estado-etapa i');
                                siguienteIcon.classList.remove('text-secondary');
                                siguienteIcon.classList.add('text-primary');
                            }
                        } else if (etapaData.progreso > 0) {
                            estadoIcon.classList.remove('text-secondary');
                            estadoIcon.classList.add('text-warning');
                            statusText.innerHTML = `En progreso • Progreso: <span class="progreso-etapa">${etapaData.progreso}%</span>`;
                        }
                    }
                });
            }
        })
        .catch(error => {
            console.error('Error al obtener progreso:', error);
        });
    }

    function actualizarProgreso() {
        actualizarProgresoEtapa(null); // Usar null ya que la función ahora obtiene datos del servidor
    }
});
</script>
@endsection
