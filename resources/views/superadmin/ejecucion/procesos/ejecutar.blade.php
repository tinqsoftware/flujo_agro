@extends('layouts.dashboard')
@section('title', $flujo->nombre)
@section('page-title','Ejecución de Flujo')
@section('page-subtitle', $flujo->nombre)

@section('header-actions')
    <a href="{{ route('ejecucion.index') }}" class="btn btn-light">
        <i class="fas fa-arrow-left me-1"></i> Volver a Flujos
    </a>
@endsection

@push('styles')
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

/* Estilos para el botón de collapse personalizado */
.collapse-toggle {
    transition: all 0.3s ease;
    cursor: pointer;
}

.collapse-toggle i {
    transition: transform 0.3s ease-in-out;
    display: inline-block;
}

.collapse-toggle.collapsed i {
    transform: rotate(0deg);
}

.collapse-toggle.expanded i {
    transform: rotate(180deg);
}

.collapse-toggle:hover {
    background-color: #007bff !important;
    border-color: #007bff !important;
    color: white !important;
}

/* Estilos para el contenido colapsable */
.etapa-content {
    overflow: hidden;
    transition: all 0.3s ease-in-out;
    max-height: 0;
    opacity: 0;
}

.etapa-content.show {
    max-height: 2000px; /* Altura máxima generosa */
    opacity: 1;
    padding-top: 0;
    padding-bottom: 0;
}

.etapa-content .card-body {
    transition: padding 0.3s ease-in-out;
}

.etapa-content.show .card-body {
    padding: 1.25rem;
}
</style>
@endpush

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
                <button class="btn btn-sm btn-outline-primary collapse-toggle collapsed" type="button" 
                        data-target="etapa-content-{{ $etapa->id }}">
                    <i class="fas fa-chevron-down"></i>
                </button>
            </div>
        </div>
    </div>
    
    <div class="etapa-content" id="etapa-content-{{ $etapa->id }}">
        <div class="card-body">
            <form class="etapa-form" data-etapa-id="{{ $etapa->id }}">
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
                                    @if($tarea->completada && $tarea->detalle && $tarea->detalle->userCreate)
                                        <div class="small text-success mt-1">
                                            <i class="fas fa-user me-1"></i>
                                            Completada por: <strong>{{ $tarea->detalle->userCreate->name }}</strong>
                                            <span class="text-muted ms-2">
                                                <i class="fas fa-clock me-1"></i>
                                                {{ $tarea->detalle->updated_at->format('d/m/Y') }}
                                            </span>
                                        </div>
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
                                                <div class="d-flex align-items-center mb-2">
                                                    <div class="form-check me-3">
                                                        <input class="form-check-input documento-checkbox" 
                                                               type="checkbox" 
                                                               id="documento-{{ $documento->id }}" 
                                                               data-documento-id="{{ $documento->id }}"
                                                               {{ $documento->subido ? 'checked' : '' }}>
                                                    </div>
                                                    <span class="badge {{ $documento->subido ? 'bg-success' : 'bg-warning' }}">
                                                        <i class="fas fa-{{ $documento->subido ? 'check' : 'clock' }} me-1"></i>
                                                        {{ $documento->subido ? 'Validado' : 'Pendiente Validación' }}
                                                    </span>
                                                </div>
                                                @if($documento->detalle && $documento->detalle->userCreate)
                                                    <div class="small text-success mt-1">
                                                        <i class="fas fa-user me-1"></i>
                                                        {{ $documento->subido ? 'Validado' : 'Subido' }} por: <strong>{{ $documento->detalle->userCreate->name }}</strong>
                                                        <span class="text-muted ms-2">
                                                            <i class="fas fa-clock me-1"></i>
                                                            {{ $documento->detalle->updated_at->format('d/m/Y') }}
                                                        </span>
                                                    </div>
                                                @endif
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
                                            <button type="button" class="btn btn-outline-primary btn-sm ver-pdf" 
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
                                            <!-- Botón para eliminar documento -->
                                            <button type="button" class="btn btn-outline-danger btn-sm eliminar-documento" 
                                                    data-documento-id="{{ $documento->id }}"
                                                    data-documento-nombre="{{ $documento->nombre }}"
                                                    data-url="{{ $documento->archivo_url }}"
                                                    title="Eliminar documento">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        @endif
                                        
                                        <!-- Botón para subir/cambiar archivo -->
                                        <button type="button" class="btn btn-outline-primary btn-sm subir-documento" 
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
                
                <!-- Botón de Grabar -->
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-success grabar-etapa" data-etapa-id="{{ $etapa->id }}">
                                <i class="fas fa-save me-2"></i>Grabar Cambios
                            </button>
                        </div>
                    </div>
                </div>
            </form>
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

<!-- Modal para confirmar desmarcar tarea -->
<div class="modal fade" id="confirmarDesmarcarTarea" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Acción</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i class="fas fa-exclamation-triangle text-warning" style="font-size: 3rem;"></i>
                </div>
                <h6 class="text-center mb-3">¿Desmarcar tarea completada?</h6>
                <p class="text-muted text-center">
                    Estás a punto de cambiar el estado de la tarea "<strong id="nombre-tarea-desmarcar"></strong>" 
                    de <span class="badge bg-success">Completada</span> a <span class="badge bg-secondary">Pendiente</span>.
                </p>
                <div class="alert alert-warning">
                    <small><i class="fas fa-info-circle me-2"></i>Esta acción cambiará el progreso de la etapa.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning" id="confirmar-desmarcar-tarea">
                    <i class="fas fa-check me-2"></i>Sí, desmarcar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para confirmar eliminar documento -->
<div class="modal fade" id="confirmarEliminarDocumento" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Eliminación de Documento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-8">
                        <div class="text-center mb-3">
                            <i class="fas fa-file-pdf text-danger" style="font-size: 3rem;"></i>
                        </div>
                        <h6 class="text-center mb-3">Vista previa del documento</h6>
                        <div class="border rounded p-2" style="height: 300px; overflow-y: auto;">
                            <iframe id="preview-documento-eliminar" src="" width="100%" height="280px" frameborder="0"></iframe>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <h6>Información del documento</h6>
                        <p><strong>Nombre:</strong><br><span id="info-nombre-documento"></span></p>
                        <p><strong>Estado actual:</strong><br><span class="badge bg-success">Subido</span></p>
                        <p><strong>Nueva acción:</strong><br><span class="badge bg-danger">Eliminar archivo</span></p>
                        
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>¡Atención!</strong><br>
                            <small>Esta acción eliminará el archivo del servidor y cambiará el estado del documento a pendiente.</small>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Motivo de eliminación:</label>
                            <textarea class="form-control" id="motivo-eliminacion" rows="3" 
                                    placeholder="Describe por qué eliminas este documento..."></textarea>
                            <small class="text-muted">Este campo es opcional pero recomendado.</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmar-eliminar-documento">
                    <i class="fas fa-trash me-2"></i>Sí, eliminar documento
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Variables globales desde PHP
const flujoId = {{ $flujo->id }};
let detalleFlujoId = {{ $flujo->detalle_flujo_id ?? 'null' }};
let procesoIniciado = {{ $flujo->proceso_iniciado ? 'true' : 'false' }};

document.addEventListener('DOMContentLoaded', function() {
    console.log('Flujo ID:', flujoId);
    console.log('Proceso iniciado:', procesoIniciado);
    
    // Inicializar estado del flujo
    if (procesoIniciado) {
        actualizarProgreso();
        
        // Activar primera etapa si el proceso ya está iniciado
        const primeraEtapa = document.querySelector('.etapa-card');
        if (primeraEtapa) {
            primeraEtapa.classList.add('activa');
            const estadoIcon = primeraEtapa.querySelector('.estado-etapa i');
            estadoIcon.classList.remove('text-secondary');
            estadoIcon.classList.add('text-primary');
        }
    }

    // Inicializar progreso de las etapas al cargar la página
    actualizarProgreso();

    // Función personalizada para manejar collapse/expand
    function initializeCustomCollapse() {
        document.querySelectorAll('.collapse-toggle').forEach(function(button) {
            button.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const targetElement = document.getElementById(targetId);
                
                if (!targetElement) return;
                
                const isExpanded = this.classList.contains('expanded');
                
                if (isExpanded) {
                    // Cerrar
                    console.log('Cerrando:', targetId);
                    this.classList.remove('expanded');
                    this.classList.add('collapsed');
                    targetElement.classList.remove('show');
                } else {
                    // Abrir
                    console.log('Abriendo:', targetId);
                    this.classList.remove('collapsed');
                    this.classList.add('expanded');
                    targetElement.classList.add('show');
                }
            });
        });
    }

    // Inicializar el sistema de collapse personalizado
    initializeCustomCollapse();

    // Iniciar ejecución
    document.getElementById('iniciar-ejecucion')?.addEventListener('click', function() {
        const btn = this;
        
        if (confirm('¿Estás seguro de que quieres iniciar la ejecución de este flujo?')) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Iniciando...';
            
            fetch(`{{ route('ejecucion.crear', $flujo->id) }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                console.log('Respuesta de iniciar proceso:', data);
                if (data.success) {
                    procesoIniciado = true;
                    detalleFlujoId = data.detalle_flujo_id; // Actualizar variable global
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

    // Manejar formularios de etapas (botón Grabar)
    document.querySelectorAll('.etapa-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!procesoIniciado) {
                alert('Debes iniciar la ejecución del flujo primero');
                return;
            }

            if (!detalleFlujoId) {
                alert('Error: No se encontró ID de ejecución');
                return;
            }

            const etapaId = this.dataset.etapaId;
            const etapaActual = this.closest('.etapa-card');
            
            // Validar que las etapas anteriores estén completadas
            if (!validarEtapasAnteriores(etapaActual)) {
                return;
            }
            const submitBtn = this.querySelector('.grabar-etapa');
            
            // Recopilar datos de tareas
            const tareas = [];
            this.querySelectorAll('.tarea-checkbox').forEach(checkbox => {
                tareas.push({
                    tarea_id: checkbox.dataset.tareaId,
                    completada: checkbox.checked
                });
            });
            
            // Recopilar datos de documentos
            const documentos = [];
            this.querySelectorAll('.documento-checkbox').forEach(checkbox => {
                documentos.push({
                    documento_id: checkbox.dataset.documentoId,
                    validado: checkbox.checked
                });
            });
            
            // Deshabilitar botón mientras se procesa
            submitBtn.disabled = true;
            const originalHtml = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Grabando...';
            
            // Enviar datos al servidor
            fetch('{{ route('ejecucion.detalle.etapa.grabar') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    etapa_id: etapaId,
                    detalle_flujo_id: detalleFlujoId,
                    tareas: tareas,
                    documentos: documentos
                })
            })
            .then(response => response.json())
            .then(data => {
                console.log('Respuesta grabar etapa:', data);
                if (data.success) {
                    const formElement = this; // Guardar referencia al formulario
                    
                    // Actualizar UI de las tareas
                    tareas.forEach(tareaData => {
                        const checkbox = formElement.querySelector(`[data-tarea-id="${tareaData.tarea_id}"]`);
                        if (checkbox) {
                            const label = checkbox.parentElement.nextElementSibling.querySelector('label');
                            if (label) {
                                if (tareaData.completada) {
                                    label.classList.add('text-decoration-line-through', 'text-muted');
                                } else {
                                    label.classList.remove('text-decoration-line-through', 'text-muted');
                                }
                            }
                        }
                    });
                    
                    // Restablecer el botón a su estado original después de grabar exitosamente
                    restablecerBotonGrabar(formElement.closest('.etapa-card'));
                    
                    // Verificar si se completó etapa o flujo
                    if (data.estados) {
                        if (typeof data.estados === 'object' && data.estados.flujo_completado) {
                            // Flujo completado - mostrar animación y redirigir
                            mostrarAnimacionComplecion(data.estados.flujo_nombre);
                        } else if (data.estados === true) {
                            // Solo etapa completada
                            const etapaCard = formElement.closest('.etapa-card');
                            marcarEtapaComoCompletada(etapaCard);
                        }
                    }
                    
                    // Actualizar progreso
                    actualizarProgreso();
                    
                    // Mostrar mensaje de éxito
                    mostrarMensajeExito('Cambios guardados correctamente');
                } else {
                    alert('Error al grabar los cambios: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al grabar los cambios');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalHtml;
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

            if (!detalleFlujoId) {
                alert('Error: No se encontró ID de ejecución');
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
        formData.append('detalle_flujo_id', detalleFlujoId);

        // Deshabilitar botón mientras se sube
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Subiendo...';

        fetch('{{ route('ejecucion.detalle.documento.subir') }}', {
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
                statusElement.innerHTML = `
                    <div class="d-flex align-items-center mb-2">
                        <div class="form-check me-3">
                            <input class="form-check-input documento-checkbox" 
                                   type="checkbox" 
                                   id="documento-${documentoId}" 
                                   data-documento-id="${documentoId}">
                        </div>
                        <span class="badge bg-warning">
                            <i class="fas fa-clock me-1"></i>Pendiente Validación
                        </span>
                    </div>
                    <div class="small text-success mt-1">
                        <i class="fas fa-user me-1"></i>
                        Subido por: <strong>${data.usuario.name}</strong>
                        <span class="text-muted ms-2">
                            <i class="fas fa-clock me-1"></i>
                            ${data.fecha_subida}
                        </span>
                    </div>
                `;
                
                // Agregar botones de ver y descargar
                btnGroup.innerHTML = `
                    <button type="button" class="btn btn-outline-primary btn-sm ver-pdf" 
                            data-documento-id="${documentoId}"
                            data-url="${data.archivo_url}"
                            title="Ver PDF">
                        <i class="fas fa-eye"></i>
                    </button>
                    <a href="${data.archivo_url}" 
                       class="btn btn-outline-secondary btn-sm" 
                       download="${data.nombre_archivo || 'documento.pdf'}"
                       title="Descargar">
                        <i class="fas fa-download"></i>
                    </a>
                    <button type="button" class="btn btn-outline-danger btn-sm eliminar-documento" 
                            data-documento-id="${documentoId}"
                            data-documento-nombre="${document.getElementById('documento-nombre').textContent}"
                            data-url="${data.archivo_url}"
                            title="Eliminar documento">
                        <i class="fas fa-trash"></i>
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-sm subir-documento" 
                            data-documento-id="${documentoId}"
                            title="Cambiar archivo">
                        <i class="fas fa-upload"></i>
                    </button>
                `;
                
                // Reagregar event listeners
                btnGroup.querySelector('.ver-pdf').addEventListener('click', verPDF);
                btnGroup.querySelector('.eliminar-documento').addEventListener('click', function() {
                    // Lógica de eliminar documento existente
                    const documentoId = this.dataset.documentoId;
                    const documentoNombre = this.dataset.documentoNombre;
                    const documentoUrl = this.dataset.url;
                    
                    // Configurar modal
                    document.getElementById('info-nombre-documento').textContent = documentoNombre;
                    document.getElementById('preview-documento-eliminar').src = documentoUrl;
                    document.getElementById('motivo-eliminacion').value = '';
                    
                    // Guardar datos para la confirmación
                    const modal = document.getElementById('confirmarEliminarDocumento');
                    modal.dataset.documentoId = documentoId;
                    modal.dataset.documentoNombre = documentoNombre;
                    
                    const modalInstance = new bootstrap.Modal(modal);
                    modalInstance.show();
                });
                btnGroup.querySelector('.subir-documento').addEventListener('click', function() {
                    if (!procesoIniciado) {
                        alert('Debes iniciar la ejecución del flujo primero');
                        return;
                    }

                    if (!detalleFlujoId) {
                        alert('Error: No se encontró ID de ejecución');
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
                
                // Marcar que hay cambios pendientes de grabar en la etapa
                const etapaCard = documentoItem.closest('.etapa-card');
                marcarCambiosPendientes(etapaCard);
                
                // Agregar event listener al nuevo checkbox
                const newCheckbox = statusElement.querySelector('.documento-checkbox');
                if (newCheckbox) {
                    newCheckbox.addEventListener('change', function() {
                        const etapaCard = this.closest('.etapa-card');
                        marcarCambiosPendientes(etapaCard);
                    });
                }
                
                // Cerrar modal
                bootstrap.Modal.getInstance(document.getElementById('uploadModal')).hide();
                document.getElementById('uploadForm').reset();
                
                // Mostrar mensaje de éxito
                mostrarMensajeExito('Documento subido correctamente. Marca el checkbox y presiona "Grabar Cambios" para validar.');
                
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

    // Función para validar que las etapas anteriores estén completadas
    function validarEtapasAnteriores(etapaActual) {
        if (!etapaActual) return false;
        
        // Obtener todas las etapas
        const todasLasEtapas = document.querySelectorAll('.etapa-card');
        const indiceActual = Array.from(todasLasEtapas).indexOf(etapaActual);
        
        // La primera etapa siempre puede completarse
        if (indiceActual === 0) {
            return true;
        }
        
        // Verificar que todas las etapas anteriores estén completadas
        for (let i = 0; i < indiceActual; i++) {
            const etapaAnterior = todasLasEtapas[i];
            if (!etapaAnterior.classList.contains('completada')) {
                // Obtener el nombre/número de la etapa anterior
                const numeroEtapaAnterior = etapaAnterior.querySelector('h6').textContent.split('.')[0];
                const nombreEtapaAnterior = etapaAnterior.querySelector('h6').textContent;
                
                // Mostrar modal de error personalizado
                mostrarErrorEtapaAnterior(numeroEtapaAnterior, nombreEtapaAnterior);
                return false;
            }
        }
        
        return true;
    }

    // Función para mostrar error cuando se intenta completar una etapa sin completar las anteriores
    function mostrarErrorEtapaAnterior(numeroEtapa, nombreEtapa) {
        // Crear modal dinámico
        const modalHtml = `
            <div class="modal fade" id="errorEtapaAnterior" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-warning text-dark">
                            <h5 class="modal-title">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Etapa Anterior Pendiente
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="text-center mb-3">
                                <i class="fas fa-lock text-warning" style="font-size: 3rem;"></i>
                            </div>
                            <h6 class="text-center mb-3">No puedes completar esta etapa</h6>
                            <p class="text-center mb-3">
                                Debes completar primero la <strong>Etapa ${numeroEtapa}</strong>:
                            </p>
                            <div class="alert alert-warning text-center">
                                <strong>${nombreEtapa}</strong>
                            </div>
                            
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-check me-2"></i>Entendido
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Remover modal anterior si existe
        const modalAnterior = document.getElementById('errorEtapaAnterior');
        if (modalAnterior) {
            modalAnterior.remove();
        }
        
        // Agregar el nuevo modal al DOM
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        // Mostrar el modal
        const modal = new bootstrap.Modal(document.getElementById('errorEtapaAnterior'));
        modal.show();
        
        // Eliminar el modal del DOM cuando se cierre
        document.getElementById('errorEtapaAnterior').addEventListener('hidden.bs.modal', function() {
            this.remove();
        });
    }

    function actualizarProgresoEtapa(etapaCard) {
        if (!detalleFlujoId) {
            console.log('No hay detalleFlujoId, omitiendo actualización de progreso');
            return;
        }
        
        // Esta función ahora obtiene datos reales del servidor
        fetch(`{{ route('ejecucion.detalle.progreso', ':detalleFlujoId') }}`.replace(':detalleFlujoId', detalleFlujoId))
        .then(response => response.json())
        .then(data => {
            if (data && data.progreso_general !== undefined) {
                const progresoGeneralElement = document.getElementById('progreso-general');
                if (progresoGeneralElement) {
                    progresoGeneralElement.textContent = data.progreso_general + '%';
                }
                
                // Validar que existan etapas en la respuesta
                if (data.etapas && Array.isArray(data.etapas)) {
                    // Actualizar progreso de cada etapa
                data.etapas.forEach(etapaData => {
                    const etapaElement = document.querySelector(`[data-etapa-id="${etapaData.id}"]`);
                    if (etapaElement) {
                        const progresoElement = etapaElement.querySelector('.progreso-etapa');
                        const estadoIcon = etapaElement.querySelector('.estado-etapa i');
                        const statusText = etapaElement.querySelector('.card-header small');
                        
                        // Validar que los elementos existan antes de usarlos
                        if (progresoElement) {
                            progresoElement.textContent = etapaData.progreso + '%';
                        }
                        
                        // Actualizar contadores de tareas y documentos con validaciones
                        const tareasListElement = etapaElement.querySelector('.tareas-list');
                        const documentosListElement = etapaElement.querySelector('.documentos-list');
                        
                        if (tareasListElement) {
                            const tareasHeader = tareasListElement.parentElement.querySelector('h6');
                            if (tareasHeader) {
                                tareasHeader.textContent = `Tareas (${etapaData.tareas_completadas}/${etapaData.total_tareas})`;
                            }
                        }
                        
                        if (documentosListElement) {
                            const documentosHeader = documentosListElement.parentElement.querySelector('h6');
                            if (documentosHeader) {
                                documentosHeader.textContent = `Documentos (${etapaData.documentos_subidos}/${etapaData.total_documentos})`;
                            }
                        }
                        
                        // Actualizar estado visual de la etapa
                        if (etapaData.progreso === 100) {
                            etapaElement.classList.remove('activa');
                            etapaElement.classList.add('completada');
                            if (estadoIcon) {
                                estadoIcon.classList.remove('text-primary', 'text-warning');
                                estadoIcon.classList.add('text-success');
                            }
                            if (statusText) {
                                statusText.innerHTML = `Completada • Progreso: <span class="progreso-etapa">${etapaData.progreso}%</span>`;
                            }
                            
                            // Activar siguiente etapa
                            const siguienteEtapa = etapaElement.nextElementSibling;
                            if (siguienteEtapa && siguienteEtapa.classList.contains('etapa-card') && !siguienteEtapa.classList.contains('activa') && !siguienteEtapa.classList.contains('completada')) {
                                siguienteEtapa.classList.add('activa');
                                const siguienteIcon = siguienteEtapa.querySelector('.estado-etapa i');
                                if (siguienteIcon) {
                                    siguienteIcon.classList.remove('text-secondary');
                                    siguienteIcon.classList.add('text-primary');
                                }
                            }
                        } else if (etapaData.progreso > 0) {
                            if (estadoIcon) {
                                estadoIcon.classList.remove('text-secondary');
                                estadoIcon.classList.add('text-warning');
                            }
                            if (statusText) {
                                statusText.innerHTML = `En progreso • Progreso: <span class="progreso-etapa">${etapaData.progreso}%</span>`;
                            }
                        }
                    } else {
                        console.warn(`No se encontró elemento para etapa ID: ${etapaData.id}`);
                    }
                });
                } else {
                    console.warn('No se recibieron datos de etapas en la respuesta del servidor');
                }
            } else {
                console.warn('Respuesta del servidor inválida para actualización de progreso');
            }
        })
        .catch(error => {
            console.error('Error al obtener progreso:', error);
            // Mostrar mensaje de error menos intrusivo
            console.warn('No se pudo actualizar el progreso automáticamente. La página se recargará para mostrar los cambios.');
        });
    }

    function actualizarProgreso() {
        actualizarProgresoEtapa(null); // Usar null ya que la función ahora obtiene datos del servidor
    }

    // Función para marcar etapa como completada
    function marcarEtapaComoCompletada(etapaCard) {
        if (!etapaCard) return;
        
        etapaCard.classList.remove('activa');
        etapaCard.classList.add('completada');
        
        const estadoIcon = etapaCard.querySelector('.estado-etapa i');
        const statusText = etapaCard.querySelector('.card-header small');
        
        estadoIcon.classList.remove('text-primary', 'text-warning', 'text-secondary');
        estadoIcon.classList.add('text-success');
        statusText.innerHTML = 'Completada • Progreso: <span class="progreso-etapa">100%</span>';
        
        // Activar siguiente etapa si existe
        const siguienteEtapa = etapaCard.nextElementSibling;
        if (siguienteEtapa && siguienteEtapa.classList.contains('etapa-card') && 
            !siguienteEtapa.classList.contains('activa') && 
            !siguienteEtapa.classList.contains('completada')) {
            siguienteEtapa.classList.add('activa');
            const siguienteIcon = siguienteEtapa.querySelector('.estado-etapa i');
            siguienteIcon.classList.remove('text-secondary');
            siguienteIcon.classList.add('text-primary');
            
            // Expandir automáticamente la siguiente etapa
            const collapseElement = siguienteEtapa.querySelector('.etapa-content');
            const collapseButton = siguienteEtapa.querySelector('.collapse-toggle');
            if (collapseElement && collapseButton && !collapseElement.classList.contains('show')) {
                collapseElement.classList.add('show');
                collapseButton.classList.remove('collapsed');
                collapseButton.classList.add('expanded');
            }
        }
    }

    // Función para mostrar animación de flujo completado
    function mostrarAnimacionComplecion(flujoNombre) {
        // Crear overlay para la animación
        const overlay = document.createElement('div');
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(40, 167, 69, 0.95);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            color: white;
            text-align: center;
            animation: fadeIn 0.5s ease-in;
        `;
        
        overlay.innerHTML = `
            <div style="max-width: 500px; padding: 2rem;">
                <div style="font-size: 4rem; margin-bottom: 1rem; animation: bounceIn 1s ease-out;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2 style="margin-bottom: 1rem; animation: slideInUp 0.8s ease-out 0.2s both;">
                    ¡Flujo Completado!
                </h2>
                <h4 style="margin-bottom: 2rem; opacity: 0.9; animation: slideInUp 0.8s ease-out 0.4s both;">
                    "${flujoNombre}"
                </h4>
                <p style="font-size: 1.1rem; margin-bottom: 2rem; animation: slideInUp 0.8s ease-out 0.6s both;">
                    Todas las etapas han sido completadas exitosamente.<br>
                    Serás redirigido automáticamente en <span id="countdown">5</span> segundos.
                </p>
                <button id="redirect-now" class="btn btn-light btn-lg" style="animation: slideInUp 0.8s ease-out 0.8s both;">
                    <i class="fas fa-arrow-left me-2"></i>Ir a Flujos Ahora
                </button>
            </div>
        `;
        
        // Agregar estilos de animación
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            @keyframes bounceIn {
                0%, 20%, 40%, 60%, 80% {
                    animation-timing-function: cubic-bezier(0.215, 0.610, 0.355, 1.000);
                }
                0% {
                    opacity: 0;
                    transform: scale3d(.3, .3, .3);
                }
                20% {
                    transform: scale3d(1.1, 1.1, 1.1);
                }
                40% {
                    transform: scale3d(.9, .9, .9);
                }
                60% {
                    opacity: 1;
                    transform: scale3d(1.03, 1.03, 1.03);
                }
                80% {
                    transform: scale3d(.97, .97, .97);
                }
                100% {
                    opacity: 1;
                    transform: scale3d(1, 1, 1);
                }
            }
            @keyframes slideInUp {
                from {
                    transform: translate3d(0, 100%, 0);
                    visibility: visible;
                    opacity: 0;
                }
                to {
                    transform: translate3d(0, 0, 0);
                    opacity: 1;
                }
            }
        `;
        
        document.head.appendChild(style);
        document.body.appendChild(overlay);
        
        // Countdown
        let countdown = 5;
        const countdownElement = overlay.querySelector('#countdown');
        const countdownInterval = setInterval(() => {
            countdown--;
            countdownElement.textContent = countdown;
            if (countdown <= 0) {
                clearInterval(countdownInterval);
                window.location.href = '{{ route('ejecucion.index') }}';
            }
        }, 1000);
        
        // Botón para redirigir inmediatamente
        overlay.querySelector('#redirect-now').addEventListener('click', () => {
            clearInterval(countdownInterval);
            window.location.href = '{{ route('ejecucion.index') }}';
        });
    }

    // Función para mostrar mensajes de éxito
    function mostrarMensajeExito(mensaje) {
        // Crear toast
        const toast = document.createElement('div');
        toast.className = 'toast align-items-center text-white bg-success border-0';
        toast.style.position = 'fixed';
        toast.style.top = '20px';
        toast.style.right = '20px';
        toast.style.zIndex = '9999';
        toast.style.minWidth = '300px';
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        
        const flexDiv = document.createElement('div');
        flexDiv.className = 'd-flex';
        
        const bodyDiv = document.createElement('div');
        bodyDiv.className = 'toast-body';
        bodyDiv.innerHTML = '<i class="fas fa-check-circle me-2"></i>' + mensaje;
        
        const closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'btn-close btn-close-white me-2 m-auto';
        closeBtn.setAttribute('data-bs-dismiss', 'toast');
        
        flexDiv.appendChild(bodyDiv);
        flexDiv.appendChild(closeBtn);
        toast.appendChild(flexDiv);
        
        document.body.appendChild(toast);
        
        const toastInstance = new bootstrap.Toast(toast, { delay: 4000 });
        toastInstance.show();
        
        // Eliminar del DOM después de que se oculte
        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    }

    // Función para marcar cambios pendientes en una etapa
    function marcarCambiosPendientes(etapaCard) {
        if (!etapaCard) return;
        
        const submitBtn = etapaCard.querySelector('.grabar-etapa');
        if (submitBtn && !submitBtn.classList.contains('btn-warning') && !submitBtn.disabled) {
            submitBtn.classList.remove('btn-success');
            submitBtn.classList.add('btn-warning');
            submitBtn.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Hay cambios por grabar';
        }
    }

    // Función para restablecer botón después de grabar
    function restablecerBotonGrabar(etapaCard) {
        if (!etapaCard) return;
        
        const submitBtn = etapaCard.querySelector('.grabar-etapa');
        if (submitBtn) {
            submitBtn.classList.remove('btn-warning');
            submitBtn.classList.add('btn-success');
            submitBtn.innerHTML = '<i class="fas fa-save me-2"></i>Grabar Cambios';
        }
    }

    // Función para agregar event listeners a los checkboxes para marcar cambios pendientes
    function agregarListenersCambiosPendientes() {
        // Event listeners para checkboxes de tareas
        document.querySelectorAll('.tarea-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function(e) {
                const wasChecked = e.target.checked;
                const previouslyChecked = this.dataset.previouslyChecked === 'true';
                
                // Si se está desmarcando una tarea que estaba marcada, mostrar modal de confirmación
                if (!wasChecked && previouslyChecked) {
                    e.preventDefault();
                    this.checked = true; // Revertir temporalmente
                    
                    const tareaItem = this.closest('.tarea-item');
                    const tareaLabel = tareaItem.querySelector('label');
                    const tareaNombre = tareaLabel.textContent.trim();
                    
                    // Configurar modal
                    document.getElementById('nombre-tarea-desmarcar').textContent = tareaNombre;
                    const modal = new bootstrap.Modal(document.getElementById('confirmarDesmarcarTarea'));
                    
                    // Guardar referencia para la confirmación
                    document.getElementById('confirmar-desmarcar-tarea').dataset.tareaCheckbox = this.id;
                    
                    modal.show();
                } else {
                    // Comportamiento normal para marcar o cambios que no requieren confirmación
                    this.dataset.previouslyChecked = wasChecked;
                    const etapaCard = this.closest('.etapa-card');
                    marcarCambiosPendientes(etapaCard);
                }
            });
            
            // Establecer estado inicial
            checkbox.dataset.previouslyChecked = checkbox.checked;
        });
        
        // Event listeners para checkboxes de documentos
        document.querySelectorAll('.documento-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const etapaCard = this.closest('.etapa-card');
                marcarCambiosPendientes(etapaCard);
            });
        });
    }

    // Event listener para confirmar desmarcar tarea
    document.getElementById('confirmar-desmarcar-tarea').addEventListener('click', function() {
        const checkboxId = this.dataset.tareaCheckbox;
        const checkbox = document.getElementById(checkboxId);
        
        if (checkbox) {
            checkbox.checked = false;
            checkbox.dataset.previouslyChecked = 'false';
            
            const etapaCard = checkbox.closest('.etapa-card');
            marcarCambiosPendientes(etapaCard);
        }
        
        // Cerrar modal
        bootstrap.Modal.getInstance(document.getElementById('confirmarDesmarcarTarea')).hide();
    });

    // Event listeners para eliminar documentos
    function agregarListenersEliminarDocumento() {
        document.querySelectorAll('.eliminar-documento').forEach(btn => {
            btn.addEventListener('click', function() {
                if (!procesoIniciado) {
                    alert('Debes iniciar la ejecución del flujo primero');
                    return;
                }

                if (!detalleFlujoId) {
                    alert('Error: No se encontró ID de ejecución');
                    return;
                }

                const documentoId = this.dataset.documentoId;
                const documentoNombre = this.dataset.documentoNombre;
                const documentoUrl = this.dataset.url;
                
                // Configurar modal
                document.getElementById('info-nombre-documento').textContent = documentoNombre;
                document.getElementById('preview-documento-eliminar').src = documentoUrl;
                document.getElementById('motivo-eliminacion').value = '';
                
                // Guardar datos para la confirmación
                const modal = document.getElementById('confirmarEliminarDocumento');
                modal.dataset.documentoId = documentoId;
                modal.dataset.documentoNombre = documentoNombre;
                
                const modalInstance = new bootstrap.Modal(modal);
                modalInstance.show();
            });
        });
    }

    // Event listener para confirmar eliminar documento
    document.getElementById('confirmar-eliminar-documento').addEventListener('click', function() {
        const modal = document.getElementById('confirmarEliminarDocumento');
        const documentoId = modal.dataset.documentoId;
        const motivo = document.getElementById('motivo-eliminacion').value.trim();
        
        const submitBtn = this;
        submitBtn.disabled = true;
        const originalHtml = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Eliminando...';
        
        // Llamar al endpoint para eliminar documento
        fetch(`/ejecucion/detalle/documento/${documentoId}/eliminar`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                detalle_flujo_id: detalleFlujoId,
                motivo: motivo
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizar UI del documento
                const documentoItem = document.querySelector(`[data-documento-id="${documentoId}"]`);
                const statusElement = documentoItem.querySelector('.document-status');
                const btnGroup = documentoItem.querySelector('.btn-group-vertical');
                
                // Cambiar estado a pendiente
                statusElement.innerHTML = '<span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>Pendiente</span>';
                
                // Restaurar solo el botón de subir
                btnGroup.innerHTML = `
                    <button type="button" class="btn btn-outline-primary btn-sm subir-documento" 
                            data-documento-id="${documentoId}"
                            title="Subir archivo">
                        <i class="fas fa-upload"></i>
                    </button>
                `;
                
                // Reagregar event listener al botón de subir
                btnGroup.querySelector('.subir-documento').addEventListener('click', function() {
                    // Lógica de subir documento existente
                    mostrarModalSubirDocumento(documentoId);
                });
                
                // Marcar cambios pendientes
                const etapaCard = documentoItem.closest('.etapa-card');
                marcarCambiosPendientes(etapaCard);
                
                // Cerrar modal
                bootstrap.Modal.getInstance(modal).hide();
                
                // Mostrar mensaje de éxito
                mostrarMensajeExito('Documento eliminado correctamente. Recuerda grabar los cambios de la etapa.');
            } else {
                alert('Error al eliminar el documento: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al eliminar el documento');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalHtml;
        });
    });

    // Inicializar listeners de cambios pendientes y eliminación de documentos
    agregarListenersCambiosPendientes();
    agregarListenersEliminarDocumento();

    // Función auxiliar para mostrar modal de subir documento
    function mostrarModalSubirDocumento(documentoId) {
        if (!procesoIniciado) {
            alert('Debes iniciar la ejecución del flujo primero');
            return;
        }

        if (!detalleFlujoId) {
            alert('Error: No se encontró ID de ejecución');
            return;
        }

        const documentoItem = document.querySelector(`[data-documento-id="${documentoId}"]`);
        const documentoNombre = documentoItem.querySelector('h6').textContent;
        
        document.getElementById('documento-nombre').textContent = documentoNombre;
        document.getElementById('uploadModal').dataset.documentoId = documentoId;
        
        const modal = new bootstrap.Modal(document.getElementById('uploadModal'));
        modal.show();
    }
});
</script>
@endpush
