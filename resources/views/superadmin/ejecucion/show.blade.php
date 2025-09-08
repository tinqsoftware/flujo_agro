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
    background-color: #f8f9fa;
    border-color: #28a745;
    border-left: 4px solid #28a745;
}

.tarea-item.pendiente {
    background-color: #f8f9fa;
    border-color: #ffc107;
    border-left: 4px solid #ffc107;
}

.documento-item {
    transition: all 0.2s ease;
    border: 1px solid #e9ecef !important;
    background-color: #ffffff;
}

.documento-item.subido {
    background-color: #f8f9fa;
    border-color: #28a745 !important;
    border-left: 3px solid #28a745 !important;
}

.documento-item.pendiente {
    background-color: #f8f9fa;
    border-color: #6c757d !important;
    border-left: 3px solid #6c757d !important;
}

.completion-info {
    background-color: #e9ecef;
    border-radius: 0.25rem;
    padding: 0.4rem 0.6rem;
    font-size: 0.75rem;
    margin-top: 0.5rem;
}

.completion-info.completed {
    background-color: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}

.user-avatar {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background-color: #007bff;
    color: white;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.6rem;
    font-weight: bold;
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

.task-progress-bar {
    height: 4px;
    background-color: #e9ecef;
    border-radius: 2px;
    overflow: hidden;
}

.task-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #28a745 0%, #20c997 100%);
    transition: width 0.3s ease;
}

.status-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
    margin-right: 0.5rem;
}

.status-indicator.completed {
    background-color: #28a745;
    box-shadow: 0 0 0 2px rgba(40, 167, 69, 0.2);
}

.status-indicator.pending {
    background-color: #6c757d;
    box-shadow: 0 0 0 2px rgba(108, 117, 125, 0.2);
}

/* Accordion personalizado con CSS puro */
.etapa-collapse {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease-out;
}

.etapa-collapse.show {
    max-height: 2000px;
    transition: max-height 0.3s ease-in;
}

.etapa-toggle {
    background: none;
    border: none;
    cursor: pointer;
    outline: none;
    transition: all 0.2s ease;
}

.etapa-toggle:hover {
    background-color: rgba(0, 123, 255, 0.1);
    border-radius: 0.25rem;
}

.etapa-toggle i {
    transition: transform 0.3s ease;
}

.etapa-toggle.expanded i {
    transform: rotate(180deg);
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
                        @if(isset($detalleFlujoActivo))
                            @if($detalleFlujoActivo->estado == 3)
                                <span class="badge bg-success estado-flujo-badge">
                                    <i class="fas fa-check-circle me-1"></i>Ejecución Completada
                                </span>
                            @elseif($detalleFlujoActivo->estado == 2)
                                <span class="badge bg-warning estado-flujo-badge">
                                    <i class="fas fa-play me-1"></i>Ejecución en Curso
                                </span>
                            @elseif($detalleFlujoActivo->estado == 4)
                                <span class="badge bg-secondary estado-flujo-badge">
                                    <i class="fas fa-pause me-1"></i>Ejecución Pausada
                                </span>
                            @endif
                        @else
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
                            @if($detalleFlujoActivo)
                                @if($detalleFlujoActivo->estado == 3)
                                    <span class="text-success">Completado</span>
                                @elseif($detalleFlujoActivo->estado == 2)
                                    <span class="text-warning">Calculando...</span>
                                @elseif($detalleFlujoActivo->estado == 4)
                                    <span class="text-secondary">Pausado</span>
                                @else
                                    <span class="text-muted">0%</span>
                                @endif
                            @else
                                <span class="text-muted">Sin ejecución</span>
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
                            <h4 class="mb-1">{{ $flujo->etapas->sum(function($etapa) { return $etapa->tareas->sum(function($tarea) { return $tarea->documentos->count(); }); }) }}</h4>
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
                    @if(isset($detalleFlujoActivo))
                        <!-- El estado se actualizará via JavaScript -->
                        <i class="fas fa-circle text-secondary" id="estado-etapa-{{ $etapa->id }}"></i>
                    @else
                        <i class="fas fa-circle text-muted" id="estado-etapa-{{ $etapa->id }}"></i>
                    @endif
                </div>
                <div>
                    <h6 class="mb-0">{{ $etapa->nro }}. {{ $etapa->nombre }}</h6>
                    <small class="text-muted">
                        @if(isset($detalleFlujoActivo))
                            <span id="estado-text-{{ $etapa->id }}">100% Completado</span>
                        @else
                            Sin ejecución activa
                        @endif
                        @if($etapa->descripcion)
                            • {{ \Illuminate\Support\Str::limit($etapa->descripcion, 50) }}
                        @endif
                    </small>
                </div>
            </div>
            <div>
                <button class="btn btn-sm btn-outline-primary etapa-toggle" type="button" 
                        onclick="toggleEtapa({{ $etapa->id }})"
                        id="toggle-btn-{{ $etapa->id }}">
                    <i class="fas fa-chevron-down"></i>
                </button>
            </div>
        </div>
    </div>
    
    <div class="etapa-collapse" id="etapa-content-{{ $etapa->id }}">
        <div class="card-body">
            <div class="row">
                <!-- Tareas -->
                @if($etapa->tareas->count() > 0)
                <div class="col-md-12">
                    <div class="d-flex align-items-center mb-3">
                        <i class="fas fa-tasks text-primary me-2"></i>
                        <h6 class="mb-0">Tareas y Documentos ({{ $etapa->tareas->count() }} tareas)</h6>
                    </div>
                    
                    <div class="tareas-list">
                        @foreach($etapa->tareas as $tarea)
                        <div class="tarea-item {{ isset($tarea->completada) && $tarea->completada ? 'completada' : 'pendiente' }}" data-tarea-id="{{ $tarea->id }}">
                            <div class="d-flex align-items-start">
                                <div class="me-3">
                                    <div class="status-indicator {{ isset($tarea->completada) && $tarea->completada ? 'completed' : 'pending' }}"></div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 {{ isset($tarea->completada) && $tarea->completada ? 'text-decoration-line-through text-muted' : '' }}">
                                        {{ $tarea->nombre }}
                                    </h6>
                                    @if($tarea->descripcion)
                                        <p class="small text-muted mb-2">{{ $tarea->descripcion }}</p>
                                    @endif
                                    <div class="small text-muted mb-2">
                                        Estado: 
                                        @if(isset($tarea->completada) && $tarea->completada)
                                            <span class="text-success fw-bold">Completada</span>
                                        @else
                                            <span class="text-secondary fw-bold">Pendiente</span>
                                        @endif
                                    </div>

                                    <!-- Información de finalización -->
                                    @if(isset($tarea->completada) && $tarea->completada)
                                        <div class="completion-info completed">
                                            <div class="d-flex align-items-center">
                                                <div class="user-avatar me-2" title="{{ $tarea->completado_por_nombre ?? 'Usuario' }}">
                                                    {{ strtoupper(substr($tarea->completado_por_nombre ?? 'U', 0, 1)) }}
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="fw-bold">Completada por: {{ $tarea->completado_por_nombre ?? 'Sistema' }}</div>
                                                    <div class="text-muted">
                                                        @if(isset($tarea->fecha_completado))
                                                            {{ \Carbon\Carbon::parse($tarea->fecha_completado)->format('d/m/Y H:i') }}
                                                        @else
                                                            Fecha no disponible
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    <!-- Documentos de esta tarea -->
                                    @if($tarea->documentos->count() > 0)
                                        <div class="ms-3 mt-2">
                                            <small class="text-muted fw-bold">
                                                <i class="fas fa-file-pdf me-1"></i>Documentos de esta tarea ({{ $tarea->documentos->count() }}):
                                            </small>
                                            <div class="row g-2 mt-1">
                                                @foreach($tarea->documentos as $documento)
                                                <div class="col-12">
                                                                <div class="documento-item p-2 rounded {{ isset($documento->subido) && $documento->subido ? 'subido' : 'pendiente' }}" data-documento-id="{{ $documento->id }}">
                                                                    <div class="d-flex justify-content-between align-items-start">
                                                                        <div class="flex-grow-1">
                                                                            <div class="d-flex align-items-center mb-1">
                                                                                <div class="status-indicator {{ isset($documento->subido) && $documento->subido ? 'completed' : 'pending' }} me-2"></div>
                                                                                <h6 class="mb-0 small">{{ $documento->nombre }}</h6>
                                                                            </div>
                                                                            @if($documento->descripcion)
                                                                                <p class="text-muted small mb-1">{{ $documento->descripcion }}</p>
                                                                            @endif                                                                <!-- Estado del documento -->
                                                                <div class="document-status mb-1" id="status-{{ $documento->id }}">
                                                                    @if(isset($documento->subido) && $documento->subido)
                                                                        <span class="badge bg-success" style="font-size: 0.7rem;">
                                                                            <i class="fas fa-check me-1"></i>Documento Subido
                                                                        </span>
                                                                    @else
                                                                        <span class="badge bg-secondary" style="font-size: 0.7rem;">
                                                                            <i class="fas fa-clock me-1"></i>Pendiente
                                                                        </span>
                                                                    @endif
                                                                </div>
                                                                
                                                                <!-- Información de completado del documento -->
                                                                @if(isset($documento->subido) && $documento->subido)
                                                                    <div class="completion-info completed">
                                                                        <div class="d-flex align-items-center">
                                                                            <div class="user-avatar me-2" title="{{ $documento->subido_por_nombre ?? 'Usuario' }}">
                                                                                {{ strtoupper(substr($documento->subido_por_nombre ?? 'U', 0, 1)) }}
                                                                            </div>
                                                                            <div class="flex-grow-1">
                                                                                <div class="fw-bold">Subido por: {{ $documento->subido_por_nombre ?? 'Sistema' }}</div>
                                                                                <div class="text-muted">
                                                                                    @if(isset($documento->fecha_subida))
                                                                                        {{ \Carbon\Carbon::parse($documento->fecha_subida)->format('d/m/Y H:i') }}
                                                                                    @else
                                                                                        Fecha no disponible
                                                                                    @endif
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                @endif
                                                                
                                                                @if(isset($documento->url_archivo) && $documento->url_archivo)
                                                                    <div class="small text-muted">
                                                                        <i class="fas fa-paperclip me-1"></i>
                                                                        Archivo disponible
                                                                    </div>
                                                                @endif
                                                            </div>
                                                            
                                                            @if(isset($documento->url_archivo) && $documento->url_archivo)
                                                            <div class="flex-shrink-0">
                                                                @php
                                                                    $extension = strtolower(pathinfo($documento->url_archivo, PATHINFO_EXTENSION));
                                                                    $esPDF = $extension === 'pdf';
                                                                    $esImagen = in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'bmp', 'webp']);
                                                                    $puedeVisualizar = $esPDF || $esImagen;
                                                                @endphp
                                                                
                                                                @if($puedeVisualizar)
                                                                    <button type="button" class="btn btn-outline-primary btn-sm ver-archivo" 
                                                                            data-documento-id="{{ $documento->id }}"
                                                                            data-url="{{ $documento->url_archivo }}"
                                                                            data-nombre="{{ $documento->nombre }}"
                                                                            data-tipo="{{ $esPDF ? 'pdf' : 'imagen' }}">
                                                                        <i class="fas fa-eye me-1"></i>Ver
                                                                    </button>
                                                                @else
                                                                    <a href="{{ $documento->url_archivo }}" 
                                                                       class="btn btn-outline-success btn-sm"
                                                                       download="{{ $documento->nombre }}.{{ $extension }}"
                                                                       title="Descargar para ver">
                                                                        <i class="fas fa-download me-1"></i>Descargar para Ver
                                                                    </a>
                                                                @endif
                                                            </div>
                                                            @endif
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
                                    <h5 class="card-title">{{ $flujo->etapas->sum(function($etapa) { return $etapa->tareas->sum(function($tarea) { return $tarea->documentos->count(); }); }) }}</h5>
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
    
    // Variables desde PHP
    const flujoId = @json($flujo->id);
    const flujoEstado = @json($flujo->estado);
    const isSuper = @json($isSuper);
    const etapasCount = @json($flujo->etapas->count());
    
    // Variable de ejecución activa (puede ser null si no hay ejecución)
    const detalleFlujoActivo = @json($detalleFlujoActivo ?? null);
    
    // Variables adicionales para manejar la ejecución específica
    const detalleFlujoId = detalleFlujoActivo ? detalleFlujoActivo.id : null;
    
    console.log('Flujo ID:', flujoId);
    console.log('Estado del flujo:', flujoEstado);
    console.log('Es SUPERADMIN:', isSuper);
    console.log('Número de etapas:', etapasCount);
    console.log('DetalleFlujo activo:', detalleFlujoActivo);
    console.log('DetalleFlujo ID:', detalleFlujoId);
    
    // Si hay una ejecución activa, obtener progreso real
    if (detalleFlujoActivo && detalleFlujoId) {
        actualizarProgreso();
    } else {
        console.log('No hay ejecución activa, mostrando estado base del flujo');
    }

    // Auto-expandir primera etapa si hay contenido
    const primeraEtapa = document.querySelector('.etapa-card');
    if (primeraEtapa) {
        const etapaId = primeraEtapa.getAttribute('data-etapa-id');
        if (etapaId) {
            toggleEtapa(etapaId);
            console.log('Primera etapa expandida automáticamente');
        }
    }

    // Logs de verificación del DOM
    const etapas = document.querySelectorAll('.etapa-card');
    console.log('Etapas encontradas en DOM:', etapas.length);

    // Ver PDF/Imagen
    function verArchivo() {
        const documentoId = this.dataset.documentoId;
        const url = this.dataset.url;
        const tipo = this.dataset.tipo;
        const documentoNombre = this.dataset.nombre || this.closest('.documento-item').querySelector('h6').textContent;
        
        if (tipo === 'pdf') {
            // Mostrar PDF en modal
            document.getElementById('pdf-title').textContent = documentoNombre;
            document.getElementById('pdf-viewer').src = url;
            document.getElementById('pdf-download').href = url;
            document.getElementById('pdf-download').download = documentoNombre + '.pdf';
            
            const modal = new bootstrap.Modal(document.getElementById('pdfModal'));
            modal.show();
        } else if (tipo === 'imagen') {
            // Crear y mostrar modal para imagen si no existe
            let imagenModal = document.getElementById('imagenModal');
            if (!imagenModal) {
                const modalHTML = `
                    <div class="modal fade" id="imagenModal" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="imagen-title">Ver Imagen</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body text-center">
                                    <img id="imagen-viewer" src="" alt="Imagen" class="img-fluid" style="max-height: 70vh;">
                                </div>
                                <div class="modal-footer">
                                    <a id="imagen-download" href="" class="btn btn-primary" download>
                                        <i class="fas fa-download me-2"></i>Descargar
                                    </a>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                document.body.insertAdjacentHTML('beforeend', modalHTML);
                imagenModal = document.getElementById('imagenModal');
            }
            
            document.getElementById('imagen-title').textContent = documentoNombre;
            document.getElementById('imagen-viewer').src = url;
            document.getElementById('imagen-download').href = url;
            
            const modal = new bootstrap.Modal(imagenModal);
            modal.show();
        }
    }

    // Agregar event listeners a botones de ver archivo
    document.querySelectorAll('.ver-archivo').forEach(btn => {
        btn.addEventListener('click', verArchivo);
    });

    // Función para compatibilidad con botones PDF existentes (mantener por compatibilidad)
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

    // Agregar event listeners a botones de ver PDF (para compatibilidad)
    document.querySelectorAll('.ver-pdf').forEach(btn => {
        btn.addEventListener('click', verPDF);
    });

    // Función para actualizar progreso desde el servidor
    function actualizarProgreso() {
        if (!detalleFlujoActivo || !detalleFlujoId) {
            console.log('No hay ejecución activa para obtener progreso');
            document.getElementById('progreso-general').innerHTML = '<span class="text-muted">Sin ejecución</span>';
            return;
        }
        
        // Usar la ruta con nombre de Laravel para mayor robustez
        const baseUrl = "{{ route('ejecucion.detalle.progreso', ['detalleFlujo' => ':id']) }}";
        const url = baseUrl.replace(':id', detalleFlujoId);
        console.log('URL de progreso:', url);
        
        fetch(url)
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Datos recibidos:', data);
            if (data.progreso_general !== undefined) {
                // Actualizar progreso general
                const progresoElement = document.getElementById('progreso-general');
                if (data.progreso_general === 100) {
                    progresoElement.innerHTML = '<span class="text-success">100% Completado</span>';
                } else {
                    progresoElement.innerHTML = '<span class="text-warning">' + data.progreso_general + '%</span>';
                }
                
                // Actualizar estado visual de cada etapa basado en el progreso real
                if (data.etapas && Array.isArray(data.etapas)) {
                    data.etapas.forEach(etapaData => {
                        console.log('Procesando etapa:', etapaData);
                        const etapaElement = document.querySelector(`[data-etapa-id="${etapaData.id}"]`);
                        if (etapaElement) {
                            const estadoIcon = etapaElement.querySelector('.estado-etapa i');
                            
                            // Actualizar ícono y estado según progreso
                            const estadoTextElement = etapaElement.querySelector(`#estado-text-${etapaData.id}`);
                            
                            if (etapaData.progreso === 100) {
                                estadoIcon.classList.remove('text-primary', 'text-warning', 'text-secondary', 'text-muted');
                                estadoIcon.classList.add('text-success');
                                estadoIcon.classList.remove('fa-circle', 'fa-play-circle');
                                estadoIcon.classList.add('fa-check-circle');
                                if (estadoTextElement) {
                                    estadoTextElement.textContent = 'Completado al 100%';
                                }
                                etapaElement.classList.add('completada');
                            } else if (etapaData.progreso > 0) {
                                estadoIcon.classList.remove('text-secondary', 'text-success', 'text-muted');
                                estadoIcon.classList.add('text-warning');
                                estadoIcon.classList.remove('fa-circle', 'fa-check-circle');
                                estadoIcon.classList.add('fa-play-circle');
                                if (estadoTextElement) {
                                    estadoTextElement.textContent = `En progreso: ${etapaData.progreso}%`;
                                }
                                etapaElement.classList.add('activa');
                            } else {
                                estadoIcon.classList.remove('text-success', 'text-warning');
                                estadoIcon.classList.add('text-secondary');
                                estadoIcon.classList.remove('fa-check-circle', 'fa-play-circle');
                                estadoIcon.classList.add('fa-circle');
                                if (estadoTextElement) {
                                    estadoTextElement.textContent = `Pendiente: ${etapaData.progreso}%`;
                                }
                            }
                            
                            // Actualizar contador de tareas en el header si existe
                            const tareasHeader = etapaElement.querySelector('.tareas-list')?.parentElement.querySelector('h6');
                            if (tareasHeader && etapaData.tareas_completadas !== undefined && etapaData.total_tareas !== undefined) {
                                tareasHeader.textContent = `Tareas (${etapaData.tareas_completadas}/${etapaData.total_tareas})`;
                            }
                            
                            // Actualizar contador de documentos en el header si existe
                            const documentosHeader = etapaElement.querySelector('.documentos-list')?.parentElement.querySelector('h6');
                            if (documentosHeader && etapaData.documentos_subidos !== undefined && etapaData.total_documentos !== undefined) {
                                documentosHeader.textContent = `Documentos (${etapaData.documentos_subidos}/${etapaData.total_documentos})`;
                            }
                            
                            // Actualizar estado visual de tareas individuales si están disponibles
                            if (etapaData.tareas) {
                                etapaData.tareas.forEach(tareaData => {
                                    const tareaElement = etapaElement.querySelector(`[data-tarea-id="${tareaData.id}"]`);
                                    if (tareaElement) {
                                        const tareaIcon = tareaElement.querySelector('i');
                                        const tareaTitle = tareaElement.querySelector('h6');
                                        const tareaStatus = tareaElement.querySelector('.small.text-muted');
                                        
                                        if (tareaData.completada) {
                                            tareaElement.classList.remove('pendiente');
                                            tareaElement.classList.add('completada');
                                            tareaIcon.classList.remove('fa-clock', 'text-warning');
                                            tareaIcon.classList.add('fa-check-circle', 'text-success');
                                            tareaTitle.classList.add('text-decoration-line-through', 'text-muted');
                                            if (tareaStatus) {
                                                tareaStatus.innerHTML = 'Estado: <span class="text-success fw-bold">Completada</span>';
                                            }
                                        } else {
                                            tareaElement.classList.remove('completada');
                                            tareaElement.classList.add('pendiente');
                                            tareaIcon.classList.remove('fa-check-circle', 'text-success');
                                            tareaIcon.classList.add('fa-clock', 'text-warning');
                                            tareaTitle.classList.remove('text-decoration-line-through', 'text-muted');
                                            if (tareaStatus) {
                                                tareaStatus.innerHTML = 'Estado: <span class="text-warning fw-bold">Pendiente</span>';
                                            }
                                        }
                                    }
                                });
                            }
                            
                            // Actualizar estado visual de documentos individuales si están disponibles
                            if (etapaData.documentos) {
                                etapaData.documentos.forEach(documentoData => {
                                    const documentoElement = etapaElement.querySelector(`[data-documento-id="${documentoData.id}"]`);
                                    if (documentoElement) {
                                        const statusBadge = documentoElement.querySelector('.document-status .badge');
                                        
                                        if (documentoData.subido) {
                                            documentoElement.classList.remove('pendiente');
                                            documentoElement.classList.add('subido');
                                            if (statusBadge) {
                                                statusBadge.className = 'badge bg-success';
                                                statusBadge.innerHTML = '<i class="fas fa-check me-1"></i>Documento Subido';
                                            }
                                        } else {
                                            documentoElement.classList.remove('subido');
                                            documentoElement.classList.add('pendiente');
                                            if (statusBadge) {
                                                statusBadge.className = 'badge bg-warning text-dark';
                                                statusBadge.innerHTML = '<i class="fas fa-clock me-1"></i>Pendiente';
                                            }
                                        }
                                    }
                                });
                            }
                        }
                    });
                }
            }
        })
        .catch(error => {
            console.error('Error al obtener progreso:', error);
            const progresoElement = document.getElementById('progreso-general');
            progresoElement.innerHTML = '<span class="text-danger">Error al cargar</span>';
        });
    }
});

// Función para toggle de etapas con CSS puro
function toggleEtapa(etapaId) {
    const content = document.getElementById('etapa-content-' + etapaId);
    const button = document.getElementById('toggle-btn-' + etapaId);
    const icon = button.querySelector('i');
    
    if (content.classList.contains('show')) {
        // Cerrar
        content.classList.remove('show');
        button.classList.remove('expanded');
        icon.classList.remove('fa-chevron-up');
        icon.classList.add('fa-chevron-down');
    } else {
        // Abrir
        content.classList.add('show');
        button.classList.add('expanded');
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-up');
    }
}

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
    const reEjecutarUrl = "{{ route('ejecucion.re-ejecutar', ['flujo' => ':id']) }}";
    const url = reEjecutarUrl.replace(':id', window.currentFlujoId);
    
    fetch(url, {
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
