@extends('layouts.dashboard')
@section('title','Detalle: ' . $flujo->nombre)
@section('page-title','Detalle del Flujo')
@section('page-subtitle', $flujo->nombre)

@section('header-actions')
    <a href="{{ route('ejecucion.index') }}" class="btn btn-light">
        <i class="fas fa-arrow-left me-1"></i> Volver a Flujos
    </a>
    @if(!$isSuper && $flujo->estado == 1)
        <button type="button" class="btn btn-success ms-2" onclick="reEjecutarFlujo({{ $flujo->id }})">
            <i class="fas fa-play me-1"></i> Ejecutar Flujo Completo
        </button>
    @elseif(!$isSuper && $flujo->estado == 2)
        <a href="{{ route('ejecucion.ejecutar', $flujo) }}" class="btn btn-warning ms-2">
            <i class="fas fa-play me-1"></i> Continuar Ejecución
        </a>
    @endif
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
    padding: 0.5rem;
    border-radius: 0.25rem;
    border: 1px solid #e9ecef;
    margin-bottom: 0.5rem;
}

.tarea-item.completada {
    background-color: #d4edda;
    border-color: #c3e6cb;
}

.tarea-item.pendiente {
    background-color: #fff3cd;
    border-color: #ffeaa7;
}

.documento-item {
    transition: all 0.2s ease;
    border: 1px solid #e9ecef !important;
}

.documento-item.subido {
    background-color: #d4edda;
    border-color: #c3e6cb !important;
}

.documento-item.pendiente {
    background-color: #fff3cd;
    border-color: #ffeaa7 !important;
}

#pdf-viewer {
    height: calc(100vh - 200px);
    min-height: 500px;
}

.estado-flujo-badge {
    font-size: 0.875rem;
    padding: 0.5rem 1rem;
}

.info-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.readonly-indicator {
    opacity: 0.7;
    pointer-events: none;
}

.progreso-circular {
    width: 60px;
    height: 60px;
}
</style>
@endpush

@section('content-area')

@if($isSuper)
    <!-- Aviso para SUPERADMIN -->
    <div class="alert alert-info border-0 shadow-sm mb-4">
        <div class="d-flex align-items-start">
            <div class="flex-shrink-0">
                <i class="fas fa-info-circle fa-lg mt-1"></i>
            </div>
            <div class="flex-grow-1 ms-3">
                <h6 class="alert-heading mb-2">Modo Solo Visualización - SUPERADMIN</h6>
                <p class="mb-0">Estás viendo los detalles del flujo en modo supervisión. No puedes realizar modificaciones.</p>
            </div>
        </div>
    </div>
@elseif(isset($userRole) && $userRole == 'ADMINISTRADOR')
    <!-- Aviso para ADMINISTRADOR -->
    <div class="alert alert-primary border-0 shadow-sm mb-4">
        <div class="d-flex align-items-start">
            <div class="flex-shrink-0">
                <i class="fas fa-user-shield fa-lg mt-1"></i>
            </div>
            <div class="flex-grow-1 ms-3">
                <h6 class="alert-heading mb-2">Vista de Administrador</h6>
                <p class="mb-0">Puedes visualizar y gestionar los flujos de tu empresa.</p>
            </div>
        </div>
    </div>
@elseif(isset($userRole) && $userRole == 'ADMINISTRATIVO')
    <!-- Aviso para ADMINISTRATIVO -->
    <div class="alert alert-success border-0 shadow-sm mb-4">
        <div class="d-flex align-items-start">
            <div class="flex-shrink-0">
                <i class="fas fa-user-check fa-lg mt-1"></i>
            </div>
            <div class="flex-grow-1 ms-3">
                <h6 class="alert-heading mb-2">Vista Administrativa</h6>
                <p class="mb-0">Puedes visualizar y ejecutar los flujos asignados a tu empresa.</p>
            </div>
        </div>
    </div>
@endif

<!-- Header del flujo con información -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card border-0 info-section">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h4 class="card-title mb-1">{{ $flujo->nombre }}</h4>
                        <div class="d-flex align-items-center mb-2">
                            <span class="me-3">Tipo: <strong>{{ $flujo->tipo->nombre ?? 'Sin tipo' }}</strong></span>
                            @if($isSuper)
                                <span class="me-3">Empresa: <strong>{{ $flujo->empresa->nombre ?? 'Sin empresa' }}</strong></span>
                            @endif
                        </div>
                        @if($flujo->descripcion)
                            <p class="mb-0 opacity-75">{{ $flujo->descripcion }}</p>
                        @endif
                    </div>
                    <div class="text-end">
                        @if($flujo->estado == 1)
                            <span class="badge bg-primary estado-flujo-badge">
                                <i class="fas fa-circle me-1"></i>Listo para Ejecutar
                            </span>
                        @elseif($flujo->estado == 2)
                            <span class="badge bg-warning estado-flujo-badge">
                                <i class="fas fa-play me-1"></i>En Ejecución
                            </span>
                        @elseif($flujo->estado == 3)
                            <span class="badge bg-success estado-flujo-badge">
                                <i class="fas fa-check-circle me-1"></i>Completado
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="row g-3">
            <div class="col-6">
                <div class="card border-0 text-center h-100">
                    <div class="card-body">
                        <h5 class="text-primary mb-1">Progreso</h5>
                        <h2 class="mb-0" id="progreso-general">
                            @if($flujo->estado == 3)
                                100%
                            @elseif($flujo->estado == 2)
                                <span class="text-warning">En curso</span>
                            @else
                                0%
                            @endif
                        </h2>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card border-0 text-center h-100">
                    <div class="card-body">
                        <h5 class="text-info mb-1">Etapas</h5>
                        <h2 class="mb-0">{{ $flujo->etapas->count() }}</h2>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Resumen de estado -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">
                    <i class="fas fa-chart-bar text-primary me-2"></i>
                    Resumen del Flujo
                </h5>
                <div class="row text-center">
                    <div class="col-md-3">
                        <div class="border rounded p-3">
                            <i class="fas fa-list-ol fa-2x text-primary mb-2"></i>
                            <h4 class="mb-1">{{ $flujo->etapas->count() }}</h4>
                            <p class="text-muted mb-0">Etapas Totales</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-3">
                            <i class="fas fa-tasks fa-2x text-warning mb-2"></i>
                            <h4 class="mb-1">{{ $flujo->etapas->sum(function($etapa) { return $etapa->tareas->count(); }) }}</h4>
                            <p class="text-muted mb-0">Tareas Totales</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-3">
                            <i class="fas fa-file-pdf fa-2x text-danger mb-2"></i>
                            <h4 class="mb-1">{{ $flujo->etapas->sum(function($etapa) { return $etapa->documentos->count(); }) }}</h4>
                            <p class="text-muted mb-0">Documentos Totales</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-3">
                            <i class="fas fa-clock fa-2x text-info mb-2"></i>
                            <h4 class="mb-1">
                                @if($flujo->created_at)
                                    {{ $flujo->created_at->diffForHumans() }}
                                @else
                                    Sin fecha
                                @endif
                            </h4>
                            <p class="text-muted mb-0">Creado</p>
                        </div>
                    </div>
                </div>
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
                    @if($flujo->estado == 3)
                        <i class="fas fa-check-circle text-success" id="estado-etapa-{{ $etapa->id }}"></i>
                    @elseif($flujo->estado == 2)
                        <i class="fas fa-play-circle text-warning" id="estado-etapa-{{ $etapa->id }}"></i>
                    @else
                        <i class="fas fa-circle text-secondary" id="estado-etapa-{{ $etapa->id }}"></i>
                    @endif
                </div>
                <div>
                    <h6 class="mb-0">{{ $etapa->nro }}. {{ $etapa->nombre }}</h6>
                    <small class="text-muted">
                        @if($flujo->estado == 3)
                            Completada
                        @elseif($flujo->estado == 2)
                            En ejecución
                        @else
                            Lista para ejecutar
                        @endif
                        @if($etapa->descripcion)
                            • {{ \Illuminate\Support\Str::limit($etapa->descripcion, 50) }}
                        @endif
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
                        <h6 class="mb-0">Tareas ({{ $etapa->tareas->count() }})</h6>
                    </div>
                    
                    <div class="tareas-list">
                        @foreach($etapa->tareas as $tarea)
                        <div class="tarea-item {{ isset($tarea->completada) && $tarea->completada ? 'completada' : 'pendiente' }}" data-tarea-id="{{ $tarea->id }}">
                            <div class="d-flex align-items-start">
                                <div class="me-3">
                                    @if(isset($tarea->completada) && $tarea->completada)
                                        <i class="fas fa-check-circle text-success"></i>
                                    @else
                                        <i class="fas fa-clock text-warning"></i>
                                    @endif
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 {{ isset($tarea->completada) && $tarea->completada ? 'text-decoration-line-through text-muted' : '' }}">
                                        {{ $tarea->nombre }}
                                    </h6>
                                    @if($tarea->descripcion)
                                        <p class="small text-muted mb-0">{{ $tarea->descripcion }}</p>
                                    @endif
                                    <div class="small text-muted mt-1">
                                        Estado: 
                                        @if(isset($tarea->completada) && $tarea->completada)
                                            <span class="text-success fw-bold">Completada</span>
                                        @else
                                            <span class="text-warning fw-bold">Pendiente</span>
                                        @endif
                                    </div>
                                </div>
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
                        <h6 class="mb-0">Documentos ({{ $etapa->documentos->count() }})</h6>
                    </div>
                    
                    <div class="documentos-list">
                        @foreach($etapa->documentos as $documento)
                        <div class="documento-item mb-3 p-3 rounded {{ isset($documento->subido) && $documento->subido ? 'subido' : 'pendiente' }}" data-documento-id="{{ $documento->id }}">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">{{ $documento->nombre }}</h6>
                                    @if($documento->descripcion)
                                        <p class="text-muted small mb-2">{{ $documento->descripcion }}</p>
                                    @endif
                                    
                                    <!-- Estado del documento -->
                                    <div class="document-status mb-2" id="status-{{ $documento->id }}">
                                        @if(isset($documento->subido) && $documento->subido)
                                            <span class="badge bg-success">
                                                <i class="fas fa-check me-1"></i>Documento Subido
                                            </span>
                                        @else
                                            <span class="badge bg-warning text-dark">
                                                <i class="fas fa-clock me-1"></i>Pendiente
                                            </span>
                                        @endif
                                    </div>
                                    
                                    @if(isset($documento->url_archivo) && $documento->url_archivo)
                                        <div class="small text-muted">
                                            <i class="fas fa-paperclip me-1"></i>
                                            Archivo disponible
                                        </div>
                                    @endif
                                </div>
                                
                                @if(isset($documento->url_archivo) && $documento->url_archivo)
                                <div class="flex-shrink-0">
                                    <button type="button" class="btn btn-outline-primary btn-sm ver-pdf" 
                                            data-documento-id="{{ $documento->id }}"
                                            data-url="{{ $documento->url_archivo }}"
                                            data-nombre="{{ $documento->nombre }}">
                                        <i class="fas fa-eye me-1"></i>Ver PDF
                                    </button>
                                </div>
                                @endif
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

@if($flujo->etapas->count() == 0)
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">Sin Etapas Configuradas</h5>
            <p class="text-muted mb-0">Este flujo aún no tiene etapas configuradas.</p>
        </div>
    </div>
@endif

<!-- Modal para visualizar PDF -->
<div class="modal fade" id="pdfModal" tabindex="-1">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-file-pdf text-danger me-2"></i>
                    <span id="pdf-title">Documento PDF</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <iframe id="pdf-viewer" src="" width="100%" frameborder="0"></iframe>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cerrar
                </button>
                <a id="pdf-download" href="" class="btn btn-primary" download>
                    <i class="fas fa-download me-2"></i>Descargar PDF
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Modal para confirmar re-ejecución del flujo -->
<div class="modal fade" id="reEjecutarModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-play-circle me-2"></i>
                    Crear Nueva Ejecución Completa
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info border-0 mb-4">
                    <div class="d-flex align-items-start">
                        <div class="flex-shrink-0">
                            <i class="fas fa-info-circle fa-lg mt-1"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="alert-heading mb-2">¿Qué sucederá?</h6>
                            <ul class="mb-0 ps-3">
                                <li>Se creará una nueva ejecución con <strong>todas las etapas, tareas y documentos</strong> del flujo</li>
                                <li>Todos los elementos empezarán en estado <strong>inicial/pendiente</strong></li>
                                <li>Podrás completar las tareas y subir documentos desde cero</li>
                                <li>Esta ejecución será independiente de cualquier ejecución anterior</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <form id="formReEjecutar">
                    <div class="row">
                        <div class="col-md-12 mb-4">
                            <label for="nombreEjecucion" class="form-label fw-bold">
                                <i class="fas fa-tag text-primary me-1"></i>
                                Nombre para la nueva ejecución
                            </label>
                            <input type="text" 
                                   class="form-control form-control-lg" 
                                   id="nombreEjecucion" 
                                   name="nombre"
                                   placeholder="Ej: Ejecución para cliente ABC - Enero 2025"
                                   maxlength="255"
                                   required>
                            <div class="form-text">
                                <i class="fas fa-lightbulb text-warning me-1"></i>
                                Usa un nombre descriptivo que te ayude a identificar esta ejecución
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card border-primary">
                                <div class="card-body text-center">
                                    <i class="fas fa-list-ol fa-2x text-primary mb-2"></i>
                                    <h5 class="card-title">{{ $flujo->etapas->count() }}</h5>
                                    <p class="card-text text-muted">Etapas</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card border-warning">
                                <div class="card-body text-center">
                                    <i class="fas fa-tasks fa-2x text-warning mb-2"></i>
                                    <h5 class="card-title">{{ $flujo->etapas->sum(function($etapa) { return $etapa->tareas->count(); }) }}</h5>
                                    <p class="card-text text-muted">Tareas Totales</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 mt-3">
                            <div class="card border-danger">
                                <div class="card-body text-center">
                                    <i class="fas fa-file-pdf fa-2x text-danger mb-2"></i>
                                    <h5 class="card-title">{{ $flujo->etapas->sum(function($etapa) { return $etapa->documentos->count(); }) }}</h5>
                                    <p class="card-text text-muted">Documentos Totales</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 mt-3">
                            <div class="card border-success">
                                <div class="card-body text-center">
                                    <i class="fas fa-user fa-2x text-success mb-2"></i>
                                    <h5 class="card-title">{{ Auth::user()->name }}</h5>
                                    <p class="card-text text-muted">Ejecutor</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancelar
                </button>
                <button type="button" class="btn btn-success btn-lg" onclick="confirmarReEjecucion()">
                    <i class="fas fa-rocket me-2"></i>Crear y Ejecutar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de progreso -->
<div class="modal fade" id="progresoModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <div class="mb-3">
                    <div class="spinner-border spinner-border-lg text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                </div>
                <h5 class="mb-2">Creando nueva ejecución...</h5>
                <p class="text-muted mb-0">Por favor espera mientras configuramos todo para ti</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal de éxito -->
<div class="modal fade" id="exitoModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="fas fa-check-circle me-2"></i>
                    ¡Ejecución Creada!
                </h5>
            </div>
            <div class="modal-body text-center p-4">
                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                <h5 class="mb-3">Nueva ejecución creada exitosamente</h5>
                <p class="text-muted mb-4" id="mensajeExito">Tu nueva ejecución está lista para comenzar</p>
                <button type="button" class="btn btn-success btn-lg" id="irAEjecucion">
                    <i class="fas fa-arrow-right me-2"></i>Ir a la Ejecución
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de error -->
<div class="modal fade" id="errorModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Error al Crear Ejecución
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-4">
                <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                <h5 class="mb-3">No se pudo crear la ejecución</h5>
                <p class="text-muted mb-4" id="mensajeError">Ha ocurrido un error inesperado</p>
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('JavaScript cargado correctamente');
    
    // Debug inicial
    console.log('Variables PHP disponibles:', {
        flujo: @json($flujo ? ['id' => $flujo->id, 'nombre' => $flujo->nombre, 'estado' => $flujo->estado] : null),
        isSuper: @json($isSuper ?? false),
        userRole: @json($userRole ?? 'UNKNOWN'),
        etapasCount: @json($flujo ? $flujo->etapas->count() : 0)
    });
    
    // Variables desde PHP
    const flujoId = @json($flujo->id);
    const flujoEstado = @json($flujo->estado);
    const isSuper = @json($isSuper);
    const etapasCount = @json($flujo->etapas->count());
    
    console.log('Flujo ID:', flujoId);
    console.log('Estado del flujo:', flujoEstado);
    console.log('Es SUPERADMIN:', isSuper);
    console.log('Número de etapas:', etapasCount);
    
    // Si el flujo está en ejecución o completado, obtener progreso real
    if (flujoEstado >= 2) {
        actualizarProgreso();
    }

    // Ver PDF
    function verPDF() {
        const documentoId = this.dataset.documentoId;
        const url = this.dataset.url;
        const documentoNombre = this.dataset.nombre || this.closest('.documento-item').querySelector('h6').textContent;
        
        document.getElementById('pdf-title').textContent = documentoNombre;
        document.getElementById('pdf-viewer').src = url;
        document.getElementById('pdf-download').href = url;
        document.getElementById('pdf-download').download = documentoNombre + '.pdf';
        
        const modal = new bootstrap.Modal(document.getElementById('pdfModal'));
        modal.show();
    }

    // Agregar event listeners a botones de ver PDF
    document.querySelectorAll('.ver-pdf').forEach(btn => {
        btn.addEventListener('click', verPDF);
    });

    // Función para actualizar progreso desde el servidor
    function actualizarProgreso() {
        if (flujoEstado < 2) return; // Solo obtener progreso si está en ejecución o completado
        
        const url = '/ejecucion/' + flujoId + '/progreso';
        console.log('URL de progreso:', url);
        
        fetch(url)
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Datos recibidos:', data);
            if (data.progreso_general !== undefined) {
                document.getElementById('progreso-general').textContent = data.progreso_general + '%';
                
                // Actualizar estado visual de cada etapa basado en el progreso real
                data.etapas.forEach(etapaData => {
                    console.log('Procesando etapa:', etapaData);
                    const etapaElement = document.querySelector(`[data-etapa-id="${etapaData.id}"]`);
                    if (etapaElement) {
                        const estadoIcon = etapaElement.querySelector('.estado-etapa i');
                        const statusText = etapaElement.querySelector('.card-header small');
                        
                        // Actualizar ícono y estado según progreso
                        if (etapaData.progreso === 100) {
                            estadoIcon.classList.remove('text-primary', 'text-warning', 'text-secondary');
                            estadoIcon.classList.add('text-success');
                            estadoIcon.classList.remove('fa-circle', 'fa-play-circle');
                            estadoIcon.classList.add('fa-check-circle');
                            statusText.innerHTML = `Completada • Progreso: ${etapaData.progreso}%`;
                            etapaElement.classList.add('completada');
                        } else if (etapaData.progreso > 0) {
                            estadoIcon.classList.remove('text-secondary', 'text-success');
                            estadoIcon.classList.add('text-warning');
                            estadoIcon.classList.remove('fa-circle', 'fa-check-circle');
                            estadoIcon.classList.add('fa-play-circle');
                            statusText.innerHTML = `En progreso • Progreso: ${etapaData.progreso}%`;
                            etapaElement.classList.add('activa');
                        }
                        
                        // Actualizar contador de tareas en el header
                        const tareasHeader = etapaElement.querySelector('.tareas-list')?.parentElement.querySelector('h6');
                        if (tareasHeader) {
                            tareasHeader.textContent = `Tareas (${etapaData.tareas_completadas}/${etapaData.total_tareas})`;
                        }
                        
                        // Actualizar contador de documentos en el header
                        const documentosHeader = etapaElement.querySelector('.documentos-list')?.parentElement.querySelector('h6');
                        if (documentosHeader) {
                            documentosHeader.textContent = `Documentos (${etapaData.documentos_subidos}/${etapaData.total_documentos})`;
                        }
                    }
                });
            }
        })
        .catch(error => {
            console.error('Error al obtener progreso:', error);
        });
    }

    // Auto-expandir primera etapa si hay contenido
    const primeraEtapa = document.querySelector('.etapa-card .collapse');
    if (primeraEtapa) {
        primeraEtapa.classList.add('show');
        const botonToggle = document.querySelector('.etapa-card [data-bs-toggle="collapse"] i');
        if (botonToggle) {
            botonToggle.classList.remove('fa-chevron-down');
            botonToggle.classList.add('fa-chevron-up');
        }
        console.log('Primera etapa expandida');
    } else {
        console.log('No se encontró primera etapa');
    }

    // Logs de verificación del DOM
    const etapas = document.querySelectorAll('.etapa-card');
    console.log('Etapas encontradas en DOM:', etapas.length);

    // Manejar toggle de etapas
    document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(btn => {
        btn.addEventListener('click', function() {
            const icon = this.querySelector('i');
            setTimeout(() => {
                const target = document.querySelector(this.getAttribute('data-bs-target'));
                if (target && target.classList.contains('show')) {
                    icon.classList.remove('fa-chevron-down');
                    icon.classList.add('fa-chevron-up');
                } else {
                    icon.classList.remove('fa-chevron-up');
                    icon.classList.add('fa-chevron-down');
                }
            }, 50);
        });
    });
});

// Función para re-ejecutar flujo completo (crear nueva ejecución)
function reEjecutarFlujo(flujoId) {
    // Generar nombre sugerido
    const fechaActual = new Date().toLocaleDateString('es-ES', {
        day: '2-digit',
        month: '2-digit', 
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
    const nombreSugerido = `Ejecución completa - ${fechaActual}`;
    
    // Llenar el formulario con datos sugeridos
    document.getElementById('nombreEjecucion').value = nombreSugerido;
    
    // Almacenar el flujoId para uso posterior
    window.currentFlujoId = flujoId;
    
    // Mostrar modal de confirmación
    const modal = new bootstrap.Modal(document.getElementById('reEjecutarModal'));
    modal.show();
    
    // Enfocar el campo de nombre después de que se muestre el modal
    document.getElementById('reEjecutarModal').addEventListener('shown.bs.modal', function() {
        document.getElementById('nombreEjecucion').select();
    }, { once: true });
}

// Función para confirmar y procesar la re-ejecución
function confirmarReEjecucion() {
    const nombreEjecucion = document.getElementById('nombreEjecucion').value.trim();
    
    // Validar que el nombre no esté vacío
    if (!nombreEjecucion) {
        // Mostrar error en el campo
        const nombreInput = document.getElementById('nombreEjecucion');
        nombreInput.classList.add('is-invalid');
        
        // Crear o actualizar mensaje de error
        let errorDiv = nombreInput.nextElementSibling;
        if (!errorDiv || !errorDiv.classList.contains('invalid-feedback')) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'invalid-feedback';
            nombreInput.parentNode.insertBefore(errorDiv, nombreInput.nextSibling);
        }
        errorDiv.textContent = 'El nombre de la ejecución es obligatorio';
        
        // Remover error después de escribir
        nombreInput.addEventListener('input', function() {
            this.classList.remove('is-invalid');
            if (errorDiv) errorDiv.remove();
        }, { once: true });
        
        return;
    }
    
    // Cerrar modal de confirmación
    const confirmModal = bootstrap.Modal.getInstance(document.getElementById('reEjecutarModal'));
    confirmModal.hide();
    
    // Mostrar modal de progreso
    const progresoModal = new bootstrap.Modal(document.getElementById('progresoModal'));
    progresoModal.show();
    
    // Realizar petición AJAX
    fetch(`/ejecucion/${window.currentFlujoId}/re-ejecutar`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            nombre: nombreEjecucion
        })
    })
    .then(response => response.json())
    .then(data => {
        // Cerrar modal de progreso
        progresoModal.hide();
        
        if (data.success) {
            // Mostrar modal de éxito
            document.getElementById('mensajeExito').textContent = data.mensaje || 'Nueva ejecución creada exitosamente';
            
            // Configurar botón para ir a la ejecución
            document.getElementById('irAEjecucion').onclick = function() {
                window.location.href = data.redirect_url;
            };
            
            const exitoModal = new bootstrap.Modal(document.getElementById('exitoModal'));
            exitoModal.show();
            
            // Auto-redirigir después de 3 segundos
            setTimeout(() => {
                window.location.href = data.redirect_url;
            }, 3000);
            
        } else {
            // Mostrar modal de error
            document.getElementById('mensajeError').textContent = data.error || 'Ha ocurrido un error inesperado';
            
            const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
            errorModal.show();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        
        // Cerrar modal de progreso
        progresoModal.hide();
        
        // Mostrar modal de error
        document.getElementById('mensajeError').textContent = 'Error de conexión. Verifica tu conexión a internet e intenta nuevamente.';
        
        const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
        errorModal.show();
    });
}
</script>
@endpush
