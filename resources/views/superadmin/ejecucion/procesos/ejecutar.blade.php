@extends('layouts.dashboard')
@section('title', $detalleFlujo->nombre ?? $flujo->nombre)
@section('page-title',$detalleFlujo->nombre ?? $flujo->nombre)
@section('page-subtitle', '(nombre de ejecución)')

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

.etapa-card.bloqueada {
    border-color: #6c757d;
    background-color: #f8f9fa;
    opacity: 0.6;
}

.etapa-card.bloqueada .card-header {
    background-color: #e9ecef !important;
    cursor: not-allowed;
}

.etapa-card.bloqueada .collapse-toggle {
    pointer-events: none;
    opacity: 0.5;
}

.etapa-card.bloqueada .tarea-checkbox,
.etapa-card.bloqueada .documento-checkbox,
.etapa-card.bloqueada .btn {
    pointer-events: none;
    opacity: 0.5;
    cursor: not-allowed;
}

.etapa-card.bloqueada .grabar-cambios-etapa {
    display: none !important;
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

.tarea-bloqueada {
    opacity: 0.7;
    background-color: #fff3cd;
    border-left: 4px solid #ffc107;
    padding-left: 8px;
    border-radius: 4px;
}

.documento-bloqueado {
    opacity: 0.7;
    background-color: #fff3cd !important;
    border-left: 4px solid #ffc107 !important;
}

.tarea-bloqueada input[disabled] {
    cursor: not-allowed;
}

.documento-bloqueado .btn.disabled {
    cursor: not-allowed;
    opacity: 0.5;
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

/* Estilos para cambios pendientes */
.cambio-pendiente {
    background-color: #fff3cd !important;
    border-left: 4px solid #ffc107 !important;
    transition: all 0.3s ease;
}

.cambio-pendiente-tarea {
    background-color: #fff3cd !important;
    border: 1px solid #ffc107 !important;
    border-radius: 0.25rem;
    padding: 0.25rem !important;
}

.cambio-pendiente-documento {
    background-color: #fff3cd !important;
    border: 1px solid #ffc107 !important;
}

/* Estilos para notificaciones toast personalizadas */
.notification-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 10000;
    max-width: 400px;
}

.notification {
    margin-bottom: 10px;
    padding: 16px 20px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    display: flex;
    align-items: center;
    font-size: 14px;
    font-weight: 500;
    opacity: 0;
    transform: translateX(100%);
    transition: all 0.3s ease;
    border-left: 4px solid;
    backdrop-filter: blur(10px);
}

.notification.show {
    opacity: 1;
    transform: translateX(0);
}

.notification.hide {
    opacity: 0;
    transform: translateX(100%);
}

.notification.success {
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    color: #155724;
    border-left-color: #28a745;
}

.notification.error {
    background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
    color: #721c24;
    border-left-color: #dc3545;
}

.notification.warning {
    background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
    color: #856404;
    border-left-color: #ffc107;
}

.notification.info {
    background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
    color: #0c5460;
    border-left-color: #17a2b8;
}

.notification i {
    margin-right: 10px;
    font-size: 16px;
}

.notification .close-btn {
    margin-left: auto;
    background: none;
    border: none;
    font-size: 18px;
    cursor: pointer;
    opacity: 0.7;
    padding: 0;
    margin-left: 15px;
}

.notification .close-btn:hover {
    opacity: 1;
}

/* Nuevos estilos para la estructura de tareas con documentos agrupados */
.tarea-container {
    border-left: 4px solid #007bff !important;
    transition: all 0.3s ease;
    margin-bottom: 1rem;
}

.tarea-container:hover {
    box-shadow: 0 2px 8px rgba(0,123,255,0.15);
    transform: translateY(-1px);
}

.tarea-container .tarea-item {
    background-color: white;
    margin-bottom: 0;
}

.documentos-tarea {
    border-left: 2px solid #e9ecef;
    padding-left: 15px;
    margin-left: 20px;
    margin-top: 10px;
}

.documentos-tarea .documento-item {
    border-left: 4px solid #dc3545 !important;
    transition: all 0.2s ease;
    margin-bottom: 0.75rem;
}

.documentos-tarea .documento-item:hover {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transform: translateY(-1px);
}

/* Estilo para tareas completadas automáticamente */
.tarea-completada {
    background-color: #d1edff !important;
    border: 2px solid #28a745 !important;
    border-radius: 8px;
}

.tarea-completada .tarea-checkbox {
    accent-color: #28a745;
}

.tarea-completada .tarea-container {
    background-color: #d1edff !important;
}

.documentos-sin-tarea {
    border-top: 2px solid #e9ecef;
    padding-top: 20px;
    margin-top: 20px;
}

.tareas-con-documentos h6 {
    color: #495057;
    font-weight: 600;
}

.tarea-container .form-check-label {
    font-weight: 600;
    font-size: 0.95rem;
}

.documentos-tarea .documento-item h6 {
    color: #dc3545;
    font-weight: 600;
    font-size: 0.9rem;
}

/* Mejoras visuales para los badges */
.tarea-container .badge {
    font-size: 0.75rem;
}

.documentos-tarea .badge {
    font-size: 0.7rem;
}

/* Espaciado mejorado */
.tarea-container .d-flex.align-items-start {
    gap: 0.5rem;
}

.documentos-tarea .small {
    font-size: 0.8rem;
}

/* Debugging para botones deshabilitados */
.btn.disabled,
.btn[disabled],
button[disabled] {
    background-color: #6c757d !important;
    border-color: #6c757d !important;
    opacity: 0.65 !important;
    pointer-events: none !important;
}

.etapa-card.bloqueada .btn {
    background-color: #6c757d !important;
    border-color: #6c757d !important;
    opacity: 0.5 !important;
    pointer-events: none !important;
}

/* Asegurar que botones habilitados se vean correctamente */
.btn.grabar-cambios-etapa:not([disabled]):not(.disabled) {
    background-color: #007bff !important;
    border-color: #007bff !important;
    color: white !important;
    opacity: 1 !important;
    pointer-events: auto !important;
}
</style>
@endpush

@section('content-area')
<!-- Header del flujo con progreso -->
<div class="row mb-4">
    <div class="col-md-8 d-flex">
        <div class="card border-0 bg-primary text-white h-100 w-100">
            <div class="card-body d-flex justify-content-center align-items-center">
                <div class="d-flex flex-column flex-md-row align-items-center justify-content-center text-center gap-3 m-0">
                    <span>Flujo: <strong>{{ $flujo->nombre }}</strong></span>
                    <span>Tipo: <strong>{{ $flujo->tipo->nombre ?? 'Sin tipo' }}</strong></span>
                    @if(!$flujo->proceso_iniciado)
                        <span class="badge bg-warning text-dark">Sin Iniciar</span>
                    @else
                        <span class="badge bg-success">En Progreso</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 d-flex">
        <div class="card border-0 text-center h-100 w-100">
            <div class="card-body d-flex flex-column justify-content-center">
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
                <h5 class="mb-1">Flujo: {{ $flujo->etapas->count() }} etapas</h5>
               
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
@php
    // Lógica para determinar si la etapa está disponible basada en BD
    $etapaDisponible = true;
    $estadoEtapaActual = null;
    
    // Si el proceso está iniciado y hay un detalle_flujo_id, consultar estado real
    if ($flujo->proceso_iniciado && $flujo->detalle_flujo_id) {
        // Obtener el estado actual de esta etapa
        $detalleEtapaActual = App\Models\DetalleEtapa::where('id_detalle_flujo', $flujo->detalle_flujo_id)
            ->where('id_etapa', $etapa->id)
            ->first();
        
        $estadoEtapaActual = $detalleEtapaActual ? $detalleEtapaActual->estado : 1; // 1 = pendiente por defecto
        
        // Solo verificar bloqueos para etapas secuenciales
        if (!$etapa->paralelo) {
            // Si es la primera etapa secuencial, siempre está disponible
            if ($index == 0) {
                $etapaDisponible = true;
            } else {
                // Verificar que todas las etapas secuenciales anteriores estén completadas (estado = 3)
                $etapasAnteriores = $flujo->etapas->slice(0, $index);
                foreach ($etapasAnteriores as $etapaAnterior) {
                    // Solo verificar etapas secuenciales anteriores
                    if (!$etapaAnterior->paralelo) {
                        $detalleEtapaAnterior = App\Models\DetalleEtapa::where('id_detalle_flujo', $flujo->detalle_flujo_id)
                            ->where('id_etapa', $etapaAnterior->id)
                            ->first();
                        
                        $estadoAnterior = $detalleEtapaAnterior ? $detalleEtapaAnterior->estado : 1;
                        
                        // Si la etapa anterior no está completada (estado != 3), bloquear esta etapa
                        if ($estadoAnterior != 3) {
                            $etapaDisponible = false;
                            break;
                        }
                    }
                }
            }
        }
        // Las etapas paralelas siempre están disponibles cuando el proceso está iniciado
    } else {
        // Si el proceso no está iniciado, solo la primera etapa está disponible
        if (!$etapa->paralelo && $index > 0) {
            // Verificar etapas anteriores sin estado de BD
            $etapasAnteriores = $flujo->etapas->slice(0, $index);
            foreach ($etapasAnteriores as $etapaAnterior) {
                if (!$etapaAnterior->paralelo) {
                    $etapaDisponible = false;
                    break;
                }
            }
        }
    }
    
    // Determinar clases CSS basadas en estado real de BD
    $clasesEtapa = 'card mb-3 etapa-card';
    if ($estadoEtapaActual == 3) {
        $clasesEtapa .= ' completada';
    } elseif (!$etapaDisponible) {
        $clasesEtapa .= ' bloqueada';
    }
@endphp
<div class="{{ $clasesEtapa }}" data-etapa-id="{{ $etapa->id }}" data-paralelo="{{ $etapa->paralelo ? '1' : '0' }}">
    <div class="card-header bg-light">
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <div class="estado-etapa me-3">
                    @if($estadoEtapaActual == 3)
                        <i class="fas fa-check-circle text-success" id="estado-etapa-{{ $etapa->id }}"></i>
                    @elseif($estadoEtapaActual == 2)
                        <i class="fas fa-play-circle text-primary" id="estado-etapa-{{ $etapa->id }}"></i>
                    @elseif($etapaDisponible)
                        <i class="fas fa-circle text-warning" id="estado-etapa-{{ $etapa->id }}"></i>
                    @else
                        <i class="fas fa-lock text-secondary" id="estado-etapa-{{ $etapa->id }}"></i>
                    @endif
                </div>
                <div>
                    <h6 class="mb-0">
                        {{ $etapa->nro }}. {{ $etapa->nombre }}
                        @if($etapa->paralelo)
                            <span class="badge bg-info ms-2" title="Etapa paralela - Puede ejecutarse simultáneamente">
                                <i class="fas fa-layer-group"></i> Paralela
                            </span>
                        @else
                            <span class="badge bg-warning ms-2" title="Etapa secuencial - Debe completarse en orden">
                                <i class="fas fa-arrow-right"></i> Secuencial
                            </span>
                        @endif
                    </h6>
                    <small class="text-muted">
                        @if($estadoEtapaActual == 3)
                            <i class="fas fa-check-circle text-success me-1"></i>Completada • Progreso: <span class="progreso-etapa" data-etapa="{{ $etapa->id }}">100%</span>
                        @elseif($etapaDisponible)
                            @if($estadoEtapaActual == 2)
                                <i class="fas fa-play-circle text-primary me-1"></i>En Progreso • Progreso: <span class="progreso-etapa" data-etapa="{{ $etapa->id }}">{{ $etapa->progreso_porcentaje ?? 0 }}%</span>
                            @else
                                Pendiente • Progreso: <span class="progreso-etapa" data-etapa="{{ $etapa->id }}">{{ $etapa->progreso_porcentaje ?? 0 }}%</span>
                            @endif
                        @else
                            <i class="fas fa-lock me-1"></i>Bloqueada - Completa las etapas secuenciales anteriores
                        @endif
                    </small>
                </div>
            </div>
            <div>
                <button class="btn btn-sm btn-outline-primary collapse-toggle collapsed" type="button" 
                        data-target="etapa-content-{{ $etapa->id }}"
                        @if(!$etapaDisponible) disabled title="Etapa bloqueada" @endif>>
                    <i class="fas fa-chevron-down"></i>
                </button>
            </div>
        </div>
    </div>
    
    <div class="etapa-content" id="etapa-content-{{ $etapa->id }}">
        <div class="card-body">
            <form class="etapa-form" data-etapa-id="{{ $etapa->id }}">
                <!-- Tareas con sus documentos agrupados -->
                @if($etapa->tareas->count() > 0)
                <div class="tareas-con-documentos">
                    <div class="d-flex align-items-center mb-4">
                        <i class="fas fa-tasks text-primary me-2"></i>
                        <h6 class="mb-0">Tareas y Documentos ({{ $etapa->tareas->where('completada', true)->count() }}/{{ $etapa->tareas->count() }} tareas completadas)</h6>
                    </div>
                    
                    @foreach($etapa->tareas as $tarea)
                    @php
                        $puedeModificar = !$tarea->rol_cambios || $tarea->rol_cambios == Auth::user()->id_rol;
                        $rolAsignado = $tarea->rol_cambios ? App\Models\Rol::find($tarea->rol_cambios) : null;
                        // Obtener documentos relacionados a esta tarea
                        $documentosTarea = $etapa->documentos->where('id_tarea', $tarea->id);
                    @endphp
                    
                    <!-- Contenedor de Tarea -->
                    <div class="tarea-container mb-4 p-3 border rounded {{ !$puedeModificar ? 'tarea-bloqueada' : '' }}" style="background-color: #f8f9fa;">
                        <!-- Información de la Tarea -->
                        <div class="d-flex align-items-start mb-3 tarea-item" data-tarea-id="{{ $tarea->id }}">
                            <div class="form-check me-3 mt-1">
                                <input class="form-check-input tarea-checkbox" 
                                       type="checkbox" 
                                       id="tarea-{{ $tarea->id }}" 
                                       data-tarea-id="{{ $tarea->id }}"
                                       {{ $tarea->completada ? 'checked' : '' }}
                                       @if($documentosTarea->count() > 0)
                                           disabled
                                           title="Los checkboxes son solo visuales - El estado se controla automáticamente por la subida de documentos"
                                       @else
                                           title="Marcar como completada"
                                       @endif>
                            </div>
                            <div class="flex-grow-1">
                                <label class="form-check-label fw-bold {{ $tarea->completada ? 'text-decoration-line-through text-muted' : '' }}" 
                                       for="tarea-{{ $tarea->id }}">
                                    <i class="fas fa-check-circle me-2"></i>{{ $tarea->nombre }}
                                    @if(!$puedeModificar)
                                        <span class="badge bg-warning ms-2" title="Requiere rol: {{ $rolAsignado ? $rolAsignado->nombre : 'Rol específico' }}">
                                            <i class="fas fa-lock"></i> {{ $rolAsignado ? $rolAsignado->nombre : 'Rol específico' }}
                                        </span>
                                    @endif
                                </label>
                                @if($tarea->descripcion)
                                    <div class="small text-muted mt-1">{{ $tarea->descripcion }}</div>
                                @endif
                                @if($tarea->completada && $tarea->detalle && $tarea->detalle->userCreate)
                                    <div class="small text-success mt-2">
                                        <i class="fas fa-user me-1"></i>
                                        Completada por: <strong>{{ $tarea->detalle->userCreate->name }}</strong>
                                        <span class="text-muted ms-2">
                                            <i class="fas fa-clock me-1"></i>
                                            {{ $tarea->detalle->updated_at->format('d/m/Y H:i') }}
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>
                        
                        <!-- Documentos de esta Tarea -->
                        @if($documentosTarea->count() > 0)
                        <div class="documentos-tarea ms-4">
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-file-pdf text-danger me-2"></i>
                                <small class="text-muted fw-bold">
                                    Documentos de esta tarea ({{ $documentosTarea->where('subido', true)->count() }}/{{ $documentosTarea->count() }})
                                </small>
                            </div>
                            
                            <div class="row">
                                @foreach($documentosTarea as $documento)
                                @php
                                    $puedeSubir = !$documento->rol_cambios || $documento->rol_cambios == Auth::user()->id_rol;
                                    $rolAsignado = $documento->rol_cambios ? App\Models\Rol::find($documento->rol_cambios) : null;
                                @endphp
                                <div class="col-md-6 mb-3">
                                    <div class="documento-item p-3 border rounded h-100 {{ !$puedeSubir ? 'documento-bloqueado' : '' }}" 
                                         data-documento-id="{{ $documento->id }}" 
                                         style="background-color: white; border-left: 4px solid #dc3545 !important;">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1 text-danger">
                                                    <i class="fas fa-file-pdf me-1"></i>{{ $documento->nombre }}
                                                    @if(!$puedeSubir)
                                                        <span class="badge bg-warning ms-2" title="Requiere rol: {{ $rolAsignado ? $rolAsignado->nombre : 'Rol específico' }}">
                                                            <i class="fas fa-lock"></i> {{ $rolAsignado ? $rolAsignado->nombre : 'Rol específico' }}
                                                        </span>
                                                    @endif
                                                </h6>
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
                                                                       {{ $documento->subido ? 'checked' : '' }}
                                                                       disabled
                                                                       title="Los checkboxes son solo visuales - El estado se controla por la subida de archivos">
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
                                                                    {{ $documento->detalle->updated_at->format('d/m/Y H:i') }}
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
                                                    @php
                                                        $extension = strtolower(pathinfo($documento->archivo_url, PATHINFO_EXTENSION));
                                                        $esPDF = $extension === 'pdf';
                                                        $esImagen = in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'bmp', 'webp']);
                                                        $puedePrevisualizar = $esPDF || $esImagen;
                                                    @endphp
                                                    
                                                    @if($puedePrevisualizar)
                                                        <!-- Botón para ver archivo -->
                                                        <button type="button" class="btn btn-outline-primary btn-sm ver-archivo" 
                                                                data-documento-id="{{ $documento->id }}"
                                                                data-url="{{ $documento->archivo_url }}"
                                                                data-tipo="{{ $esPDF ? 'pdf' : 'imagen' }}"
                                                                title="{{ $esPDF ? 'Ver PDF' : 'Ver Imagen' }}">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    @endif
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
                                                <button type="button" class="btn btn-outline-primary btn-sm subir-documento {{ !$puedeSubir ? 'disabled' : '' }}" 
                                                        data-documento-id="{{ $documento->id }}"
                                                        title="{{ $documento->archivo_url ? 'Cambiar archivo' : 'Subir archivo' }}"
                                                        {{ !$puedeSubir ? 'disabled' : '' }}>
                                                    <i class="fas fa-upload"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
                @endif
                
                <!-- Documentos sin tarea asignada (si los hay) -->
                @php
                    $documentosSinTarea = $etapa->documentos->whereNull('id_tarea');
                @endphp
                @if($documentosSinTarea->count() > 0)
                <div class="documentos-sin-tarea mt-4">
                    <div class="d-flex align-items-center mb-3">
                        <i class="fas fa-file-pdf text-warning me-2"></i>
                        <h6 class="mb-0">Documentos Generales de la Etapa ({{ $documentosSinTarea->where('subido', true)->count() }}/{{ $documentosSinTarea->count() }})</h6>
                    </div>
                    
                    <div class="row">
                        @foreach($documentosSinTarea as $documento)
                        @php
                            $puedeSubir = !$documento->rol_cambios || $documento->rol_cambios == Auth::user()->id_rol;
                            $rolAsignado = $documento->rol_cambios ? App\Models\Rol::find($documento->rol_cambios) : null;
                        @endphp
                        <div class="col-md-6 mb-3">
                            <div class="documento-item p-3 border rounded {{ !$puedeSubir ? 'documento-bloqueado' : '' }}" data-documento-id="{{ $documento->id }}">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">
                                            {{ $documento->nombre }}
                                            @if(!$puedeSubir)
                                                <span class="badge bg-warning ms-2" title="Requiere rol: {{ $rolAsignado ? $rolAsignado->nombre : 'Rol específico' }}">
                                                    <i class="fas fa-lock"></i> {{ $rolAsignado ? $rolAsignado->nombre : 'Rol específico' }}
                                                </span>
                                            @endif
                                        </h6>
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
                                                               {{ $documento->subido ? 'checked' : '' }}
                                                               disabled
                                                               title="Los checkboxes son solo visuales - El estado se controla por la subida de archivos">
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
                                                            {{ $documento->detalle->updated_at->format('d/m/Y H:i') }}
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
                                            @php
                                                $extension = strtolower(pathinfo($documento->archivo_url, PATHINFO_EXTENSION));
                                                $esPDF = $extension === 'pdf';
                                                $esImagen = in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'bmp', 'webp']);
                                                $puedePrevisualizar = $esPDF || $esImagen;
                                            @endphp
                                            
                                            @if($puedePrevisualizar)
                                                <!-- Botón para ver archivo -->
                                                <button type="button" class="btn btn-outline-primary btn-sm ver-archivo" 
                                                        data-documento-id="{{ $documento->id }}"
                                                        data-url="{{ $documento->archivo_url }}"
                                                        data-tipo="{{ $esPDF ? 'pdf' : 'imagen' }}"
                                                        title="{{ $esPDF ? 'Ver PDF' : 'Ver Imagen' }}">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            @endif
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
                                        <button type="button" class="btn btn-outline-primary btn-sm subir-documento {{ !$puedeSubir ? 'disabled' : '' }}" 
                                                data-documento-id="{{ $documento->id }}"
                                                title="{{ $documento->archivo_url ? 'Cambiar archivo' : 'Subir archivo' }}"
                                                {{ !$puedeSubir ? 'disabled' : '' }}>
                                            <i class="fas fa-upload"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
                
                <!-- Formularios de la etapa -->
                @if($etapa->etapaForms->count() > 0)
                <div class="formularios-etapa mt-4">
                    <div class="d-flex align-items-center mb-3">
                        <i class="fas fa-clipboard-list text-success me-2"></i>
                        <h6 class="mb-0">Formularios (<span class="formularios-completados">0</span>/<span class="total-formularios">{{ $etapa->etapaForms->count() }}</span> completados)</h6>
                    </div>
                    
                    @foreach($etapa->etapaForms as $etapaForm)
                    @php
                        // Usar los datos procesados en el controlador para esta ejecución específica
                        $formRun = $etapaForm->formRun ?? null;
                        $formularioCompletado = $etapaForm->formularioCompletado ?? false;
                        $formularioIniciado = $formRun && in_array($formRun->estado, ['draft', 'submitted', 'approved']);
                    @endphp
                    
                    <div class="formulario-container mb-3 p-3 border rounded" style="background-color: #f0f8f0; border-left: 4px solid #28a745 !important;">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center mb-1">
                                    <i class="fas fa-file-alt me-2 text-success"></i>
                                    <strong>{{ $etapaForm->form->nombre }}</strong>
                                    @if($formularioCompletado)
                                        <span class="badge bg-success ms-2">
                                            <i class="fas fa-check-circle"></i> Completado
                                        </span>
                                    @elseif($formularioIniciado)
                                        <span class="badge bg-warning ms-2">
                                            <i class="fas fa-clock"></i> En Progreso
                                        </span>
                                    @else
                                        <span class="badge bg-secondary ms-2">
                                            <i class="fas fa-minus-circle"></i> Pendiente
                                        </span>
                                    @endif
                                </div>
                                @if($etapaForm->form->descripcion)
                                    <div class="small text-muted">{{ $etapaForm->form->descripcion }}</div>
                                @endif
                                @if($formRun && $formRun->correlativo)
                                    <div class="small text-info mt-1">
                                        <i class="fas fa-hashtag me-1"></i>Correlativo: <strong>{{ $formRun->correlativo }}</strong>
                                    </div>
                                @endif
                            </div>
                            <div class="d-flex gap-2">
                                @if($flujo->proceso_iniciado)
                                    @if($formularioCompletado)
                                        <!-- Botones para formulario completado -->
                                        <div class="btn-group" role="group">
                                            <!-- Botón para ver formulario completado -->
                                            <button type="button" class="btn btn-outline-success btn-sm ver-formulario-completado" 
                                                    data-form-run-id="{{ $formRun->id }}"
                                                    data-form-nombre="{{ $etapaForm->form->nombre }}"
                                                    title="Ver formulario completado">
                                                <i class="fas fa-eye"></i> Ver
                                            </button>
                                            
                                            @php
                                                $pdfTemplate = $etapaForm->form->pdfTemplates->first();
                                            @endphp
                                            @if($pdfTemplate)
                                                <!-- Botón para generar PDF -->
                                                <button type="button" class="btn btn-outline-danger btn-sm ver-formulario-pdf"
                                                        data-form-run-id="{{ $formRun->id }}"
                                                        data-template-id="{{ $pdfTemplate->id }}"
                                                        data-form-nombre="{{ $etapaForm->form->nombre }}"
                                                        title="Descargar formulario como PDF">
                                                    <i class="fas fa-file-pdf"></i> PDF
                                                </button>
                                            @endif
                                        </div>
                                    @else
                                        <!-- Botón para rellenar formulario -->
                                        <button type="button" class="btn btn-success btn-sm rellenar-formulario" 
                                                data-etapa-form-id="{{ $etapaForm->id }}"
                                                data-form-id="{{ $etapaForm->form->id }}"
                                                data-form-nombre="{{ $etapaForm->form->nombre }}"
                                                data-form-run-id="{{ $formRun->id ?? '' }}"
                                                title="{{ $formularioIniciado ? 'Continuar llenado' : 'Rellenar formulario' }}">
                                            <i class="fas fa-edit"></i> {{ $formularioIniciado ? 'Continuar' : 'Rellenar' }}
                                        </button>
                                    @endif
                                @else
                                    <!-- Proceso no iniciado -->
                                    <button type="button" class="btn btn-secondary btn-sm" disabled title="Inicie el proceso para poder rellenar formularios">
                                        <i class="fas fa-lock"></i> Bloqueado
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
                
                <!-- Botón para grabar cambios de esta etapa -->
                @if($flujo->proceso_iniciado)
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="d-flex justify-content-center">
                            <button class="btn btn-primary grabar-cambios-etapa" 
                                    data-etapa-id="{{ $etapa->id }}" 
                                    style="display: none;">
                                <i class="fas fa-save me-2"></i>Grabar Cambios de esta Etapa
                                <span class="badge bg-light text-primary ms-1 contador-cambios-etapa">0</span>
                            </button>
                        </div>
                    </div>
                </div>
                @endif
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
                        <label class="form-label">Seleccionar archivo</label>
               <input type="file" class="form-control" id="documentFile" 
                   accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.ppt,.pptx,.png,.jpg,.jpeg,.gif,.bmp,.webp" 
                   required>
               <div class="form-text">Se permiten archivos PDF, Word (.doc/.docx), Excel (.xls/.xlsx), PowerPoint (.ppt/.pptx), CSV, e imágenes (.png/.jpg/.jpeg/.gif/.bmp/.webp) - máximo 10MB</div>
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

<!-- Modal para rellenar formulario -->
<div class="modal fade" id="rellenarFormularioModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="fas fa-clipboard-list me-2"></i>Rellenar Formulario: <span id="modal-form-nombre"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                <div id="formulario-contenido">
                    <!-- El contenido del formulario se cargará aquí dinámicamente -->
                    <div class="text-center py-4">
                        <div class="spinner-border text-success" role="status">
                            <span class="visually-hidden">Cargando formulario...</span>
                        </div>
                        <p class="mt-2 text-muted">Cargando campos del formulario...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancelar
                </button>
                <button type="button" class="btn btn-warning" id="guardar-borrador-formulario" style="display: none;">
                    <i class="fas fa-save me-2"></i>Guardar Borrador
                </button>
                <button type="button" class="btn btn-success" id="completar-formulario" style="display: none;">
                    <i class="fas fa-check-circle me-2"></i>Completar Formulario
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para ver formulario completado -->
<div class="modal fade" id="verFormularioCompletadoModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">
                    <i class="fas fa-eye me-2"></i>Formulario Completado: <span id="modal-form-completado-nombre"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                <div id="formulario-completado-contenido">
                    <!-- El contenido del formulario completado se cargará aquí -->
                    <div class="text-center py-4">
                        <div class="spinner-border text-info" role="status">
                            <span class="visually-hidden">Cargando formulario...</span>
                        </div>
                        <p class="mt-2 text-muted">Cargando formulario completado...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cerrar
                </button>
                <button type="button" class="btn btn-outline-primary" id="imprimir-formulario" style="display: none;">
                    <i class="fas fa-print me-2"></i>Imprimir
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

<!-- Contenedor para notificaciones -->
<div id="notification-container" class="notification-container"></div>

@push('scripts')
<script>
// Variables globales desde PHP
const flujoId = {{ $flujo->id }};
let detalleFlujoId = {{ $flujo->detalle_flujo_id ?? 'null' }};
let procesoIniciado = {{ $flujo->proceso_iniciado ? 'true' : 'false' }};

// Variables para el manejo de cambios pendientes por etapa
let cambiosPendientesPorEtapa = {};

// Función para mostrar notificaciones CSS elegantes
function mostrarNotificacion(mensaje, tipo = 'info', duracion = 5000) {
    const container = document.getElementById('notification-container');
    if (!container) {
        console.warn('Contenedor de notificaciones no encontrado');
        return;
    }
    
    // Crear el elemento de notificación
    const notification = document.createElement('div');
    notification.className = `notification ${tipo}`;
    
    // Definir iconos según el tipo
    const iconos = {
        success: 'fas fa-check-circle',
        error: 'fas fa-exclamation-circle',
        warning: 'fas fa-exclamation-triangle',
        info: 'fas fa-info-circle'
    };
    
    notification.innerHTML = `
        <i class="${iconos[tipo] || iconos.info}"></i>
        <span>${mensaje}</span>
        <button class="close-btn" onclick="cerrarNotificacion(this.parentElement)">&times;</button>
    `;
    
    // Agregar al contenedor
    container.appendChild(notification);
    
    // Mostrar con animación
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);
    
    // Auto-remover después de la duración especificada
    if (duracion > 0) {
        setTimeout(() => {
            cerrarNotificacion(notification);
        }, duracion);
    }
    
    return notification;
}

// Función para cerrar notificación
function cerrarNotificacion(notification) {
    notification.classList.remove('show');
    notification.classList.add('hide');
    
    setTimeout(() => {
        if (notification.parentElement) {
            notification.parentElement.removeChild(notification);
        }
    }, 300);
}

document.addEventListener('DOMContentLoaded', function() {
    console.log('Flujo ID:', flujoId);
    console.log('Proceso iniciado:', procesoIniciado);
    
    // Verificar si la URL tiene parámetros de query innecesarios y limpiarla
    if (window.location.search && window.location.search === '?') {
        const cleanUrl = window.location.protocol + '//' + window.location.host + window.location.pathname;
        window.history.replaceState({}, document.title, cleanUrl);
        console.log('URL limpiada de parámetros vacíos');
    }
    
    // Inicializar control de etapas bloqueadas
    inicializarControlEtapas();
    
    // Inicializar listeners de cambios visuales TEMPRANO
    agregarListenersCambiosVisuales();
    agregarListenersEliminarDocumento();
    agregarListenersFormularios();
    
    // Inicializar event listeners de botones "Grabar Cambios" por etapa
    document.querySelectorAll('.grabar-cambios-etapa').forEach(btn => {
        btn.addEventListener('click', function() {
            const etapaId = this.dataset.etapaId;
            grabarCambiosDeEtapa(etapaId);
        });
    });
    
    // Inicializar estado del flujo
    if (procesoIniciado) {
        actualizarProgreso();
        
        // Verificar estados de etapas desde BD al cargar la página
        if (detalleFlujoId) {
            // Llamada inmediata para estados ya disponibles
            verificarYActualizarEstadoEtapas();
            
            // Llamada con delay para asegurar que todo esté cargado
            setTimeout(() => {
                verificarYActualizarEstadoEtapas();
                verificarEstadoCompletado();
            }, 1000);
        }
    }

    // Inicializar progreso de las etapas al cargar la página
    actualizarProgreso();

    // Verificar estado de todas las tareas al cargar la página
    function verificarTodasLasTareasAlCargar() {
        console.log('Verificando estado de todas las tareas al cargar...');
        const todasLasTareas = document.querySelectorAll('[data-tarea-id]');
        
        todasLasTareas.forEach(tareaElement => {
            setTimeout(() => verificarYActualizarEstadoTarea(tareaElement), 200);
        });
    }

    // Ejecutar verificación de tareas después de que todo esté cargado
    if (procesoIniciado && detalleFlujoId) {
        // Verificación inmediata para debugging
        setTimeout(() => {
            console.log('🔍 Ejecutando verificación inmediata de tareas...');
            verificarTodasLasTareasAlCargar();
        }, 500);
        
        // Verificación principal
        setTimeout(verificarTodasLasTareasAlCargar, 1500);
    }

    // Función para inicializar el control de etapas bloqueadas
    function inicializarControlEtapas() {
        // Verificar el estado inicial de las etapas
        const etapas = document.querySelectorAll('.etapa-card');
        
        etapas.forEach((etapa, index) => {
            const esParalela = etapa.dataset.paralelo === '1';
            const estaBloqueada = etapa.classList.contains('bloqueada');
            
            console.log(`Etapa ${index + 1}: Paralela=${esParalela}, Bloqueada=${estaBloqueada}`);
            
            // Si la etapa está bloqueada, deshabilitarla completamente
            if (estaBloqueada) {
                deshabilitarEtapa(etapa);
            }
        });
    }
    
    // Función para deshabilitar una etapa
    function deshabilitarEtapa(etapaElement) {
        console.log('Deshabilitando etapa:', etapaElement.dataset.etapaId);
        
        // Deshabilitar botón de colapso
        const collapseBtn = etapaElement.querySelector('.collapse-toggle');
        if (collapseBtn) {
            collapseBtn.disabled = true;
            collapseBtn.title = 'Etapa bloqueada - Completa las etapas secuenciales anteriores';
        }
        
        // Deshabilitar todos los controles dentro de la etapa
        const controles = etapaElement.querySelectorAll('input, button, select, textarea');
        controles.forEach(control => {
            control.disabled = true;
            control.style.cursor = 'not-allowed';
            control.style.pointerEvents = 'none';
        });
        
        // Agregar mensaje de información
        const cardHeader = etapaElement.querySelector('.card-header');
        if (cardHeader && !cardHeader.querySelector('.etapa-bloqueada-info')) {
            const infoDiv = document.createElement('div');
            infoDiv.className = 'etapa-bloqueada-info mt-2';
            infoDiv.innerHTML = `
                <small class="text-warning">
                    <i class="fas fa-info-circle me-1"></i>
                    Esta etapa secuencial está bloqueada. Completa las etapas anteriores para poder acceder.
                </small>
            `;
            cardHeader.appendChild(infoDiv);
        }
    }
    
    // Función para habilitar una etapa
    function habilitarEtapa(etapaElement) {
        console.log('Habilitando etapa:', etapaElement.dataset.etapaId);
        
        // Quitar clase de bloqueado
        etapaElement.classList.remove('bloqueada');
        
        // Habilitar botón de colapso
        const collapseBtn = etapaElement.querySelector('.collapse-toggle');
        if (collapseBtn) {
            collapseBtn.disabled = false;
            collapseBtn.title = '';
        }
        
        // Habilitar controles específicos (no todos, solo los que deben estar habilitados)
        const checksboxes = etapaElement.querySelectorAll('.tarea-checkbox, .documento-checkbox');
        checksboxes.forEach(checkbox => {
            if (!checkbox.closest('.tarea-bloqueada, .documento-bloqueado')) {
                checkbox.disabled = false;
                checkbox.style.cursor = '';
            }
        });
        
        // Habilitar TODOS los botones cuando el proceso está iniciado, excepto los específicamente bloqueados
        if (procesoIniciado) {
            const botones = etapaElement.querySelectorAll('.btn:not(.collapse-toggle)');
            botones.forEach(boton => {
                // Solo habilitar si no está en una tarea/documento bloqueado por roles
                if (!boton.closest('.tarea-bloqueada, .documento-bloqueado')) {
                    boton.disabled = false;
                    boton.style.cursor = '';
                    boton.style.pointerEvents = '';
                    
                    // Específicamente para el botón "Grabar Cambios"
                    if (boton.classList.contains('grabar-cambios-etapa')) {
                        console.log('Habilitando botón grabar cambios para etapa:', etapaElement.dataset.etapaId);
                    }
                }
            });
        }
        
        // Quitar mensaje de información
        const infoDiv = etapaElement.querySelector('.etapa-bloqueada-info');
        if (infoDiv) {
            infoDiv.remove();
        }
        
        // Actualizar el texto del estado
        const statusText = etapaElement.querySelector('.card-header small');
        if (statusText && statusText.textContent.includes('Bloqueada')) {
            statusText.innerHTML = 'Pendiente • Progreso: <span class="progreso-etapa">0%</span>';
        }
    }
    
    // Función para verificar y actualizar el estado de las etapas basado en el estado real de BD
    function verificarYActualizarEstadoEtapas() {
        if (!detalleFlujoId) {
            console.log('No hay detalleFlujoId, no se puede verificar estados de etapas');
            return;
        }
        
        console.log('Verificando estados de etapas desde BD...');
        
        // Consultar estado real de las etapas desde el servidor
        fetch(`{{ route('ejecucion.detalle.progreso', ':detalleFlujoId') }}`.replace(':detalleFlujoId', detalleFlujoId))
        .then(response => response.json())
        .then(data => {
            if (data && data.etapas) {
                console.log('Estados de etapas desde BD:', data.etapas);
                
                const etapas = document.querySelectorAll('.etapa-card');
                
                etapas.forEach((etapa, index) => {
                    const esParalela = etapa.dataset.paralelo === '1';
                    const etapaId = parseInt(etapa.dataset.etapaId);
                    const estadoEtapa = data.etapas[etapaId] || { estado: 1 };
                    
                    console.log(`Etapa ${index + 1} (ID: ${etapaId}): Paralela=${esParalela}, Estado BD=${estadoEtapa.estado}`);
                    
                    if (!esParalela) {
                        // Para etapas secuenciales, verificar que las anteriores estén completadas
                        let puedeHabilitarse = true;
                        
                        // Revisar todas las etapas anteriores
                        for (let i = 0; i < index; i++) {
                            const etapaAnterior = etapas[i];
                            const esParalelaAnterior = etapaAnterior.dataset.paralelo === '1';
                            const etapaAnteriorId = parseInt(etapaAnterior.dataset.etapaId);
                            const estadoAnterior = data.etapas[etapaAnteriorId] || { estado: 1 };
                            
                            // Solo verificar etapas secuenciales anteriores
                            if (!esParalelaAnterior && estadoAnterior.estado !== 3) {
                                puedeHabilitarse = false;
                                console.log(`Etapa ${index + 1} bloqueada por etapa anterior ${i + 1} (estado: ${estadoAnterior.estado})`);
                                break;
                            }
                        }
                        
                        // Aplicar el estado correspondiente
                        if (puedeHabilitarse && etapa.classList.contains('bloqueada')) {
                            habilitarEtapa(etapa);
                            console.log(`Etapa ${index + 1} habilitada por completar etapas anteriores`);
                        } else if (!puedeHabilitarse && !etapa.classList.contains('bloqueada')) {
                            etapa.classList.add('bloqueada');
                            deshabilitarEtapa(etapa);
                            console.log(`Etapa ${index + 1} bloqueada por etapas anteriores incompletas`);
                        }
                    }
                    
                    // Actualizar visual según estado real de BD
                    actualizarVisualEtapaSegunEstado(etapa, estadoEtapa.estado);
                });
            }
        })
        .catch(error => {
            console.error('Error al verificar estados de etapas:', error);
        });
    }
    
    // Función para actualizar visual de etapa según estado de BD
    function actualizarVisualEtapaSegunEstado(etapaElement, estado) {
        const etapaId = etapaElement.dataset.etapaId;
        const estadoIcon = etapaElement.querySelector('.estado-etapa i');
        const statusText = etapaElement.querySelector('.card-header small');
        const progresoElement = etapaElement.querySelector('.progreso-etapa');
        
        // Obtener el progreso actual del elemento (puede venir del servidor)
        let progresoActual = '0';
        if (progresoElement) {
            progresoActual = progresoElement.textContent.replace('%', '') || '0';
        }
        
        // Remover clases anteriores
        etapaElement.classList.remove('completada', 'activa');
        estadoIcon.classList.remove('text-success', 'text-primary', 'text-warning', 'text-secondary');
        
        // Aplicar visual según estado
        switch(estado) {
            case 3: // Completada
                etapaElement.classList.add('completada');
                estadoIcon.classList.add('text-success');
                if (statusText) {
                    statusText.innerHTML = '<i class="fas fa-check-circle text-success me-1"></i>Completada • Progreso: <span class="progreso-etapa" data-etapa="' + etapaId + '">100%</span>';
                }
                break;
            case 2: // En progreso
                etapaElement.classList.add('activa');
                estadoIcon.classList.add('text-primary');
                if (statusText) {
                    statusText.innerHTML = '<i class="fas fa-play-circle text-primary me-1"></i>En Progreso • Progreso: <span class="progreso-etapa" data-etapa="' + etapaId + '">' + progresoActual + '%</span>';
                }
                break;
            case 1: // Pendiente
            default:
                if (!etapaElement.classList.contains('bloqueada')) {
                    estadoIcon.classList.add('text-warning');
                    if (statusText) {
                        statusText.innerHTML = 'Pendiente • Progreso: <span class="progreso-etapa" data-etapa="' + etapaId + '">' + progresoActual + '%</span>';
                    }
                }
                break;
        }
    }

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
                    mostrarNotificacion('Error al iniciar el proceso: ' + data.message, 'error');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-play me-2"></i>Iniciar Ejecución';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarNotificacion('Error al iniciar el proceso', 'error');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-play me-2"></i>Iniciar Ejecución';
            });
        }
    });

    // Event listeners para los botones "Grabar Cambios" de cada etapa
    document.querySelectorAll('.grabar-cambios-etapa').forEach(btn => {
        btn.addEventListener('click', function() {
            const etapaId = this.dataset.etapaId;
            const cambiosEtapa = cambiosPendientesPorEtapa[etapaId] || [];
            
            if (cambiosEtapa.length === 0) {
                mostrarNotificacion('No hay cambios pendientes para grabar en esta etapa', 'warning');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Grabando...';
            
            grabarCambiosDeEtapa(etapaId)
                .then(() => {
                    // Limpiar cambios pendientes de esta etapa
                    delete cambiosPendientesPorEtapa[etapaId];
                    actualizarContadorCambiosEtapa(etapaId);
                    
                    // Limpiar estilos visuales de cambios pendientes en esta etapa
                    limpiarEstilosVisualesEtapa(etapaId);
                    
                    mostrarMensajeExito('Todos los cambios de esta etapa han sido grabados correctamente');
                    
                    // Recargar la página para mostrar los últimos cambios
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500); // Delay de 1.5 segundos para que el usuario vea el mensaje de éxito
                })
                .catch(error => {
                    console.error('Error al grabar cambios de etapa:', error);
                    mostrarNotificacion('Error al grabar algunos cambios. Revisa la consola para más detalles.', 'error');
                    
                    // Restaurar botón
                    btn.disabled = false;
                    const cambiosCount = cambiosPendientesPorEtapa[etapaId]?.length || 0;
                    btn.innerHTML = `<i class="fas fa-save me-2"></i>Grabar Cambios de esta Etapa <span class="badge bg-light text-primary ms-1 contador-cambios-etapa">${cambiosCount}</span>`;
                });
        });
    });

    // Función para grabar cambios de una etapa específica
    async function grabarCambiosDeEtapa(etapaId) {
        const cambiosEtapa = cambiosPendientesPorEtapa[etapaId] || {};
        const promesas = [];
        
        console.log(`Grabando cambios para etapa ${etapaId}:`, cambiosEtapa);
        
        // Procesar tareas pendientes
        if (cambiosEtapa.tareas) {
            for (const [tareaId, cambio] of Object.entries(cambiosEtapa.tareas)) {
                console.log(`Procesando tarea ${tareaId}:`, cambio);
                promesas.push(actualizarTareaIndividual(tareaId, cambio.completada));
            }
        }
        
        // Procesar documentos pendientes
        if (cambiosEtapa.documentos) {
            for (const [documentoId, cambio] of Object.entries(cambiosEtapa.documentos)) {
                console.log(`Procesando documento ${documentoId}:`, cambio);
                promesas.push(actualizarDocumentoIndividual(documentoId, cambio.validado));
            }
        }
        
        // Procesar formularios pendientes
        if (cambiosEtapa.formularios) {
            for (const [etapaFormId, cambio] of Object.entries(cambiosEtapa.formularios)) {
                console.log(`Procesando formulario ${etapaFormId}:`, cambio);
                // Los formularios ya están guardados en la BD, solo necesitamos actualizar la vista
                promesas.push(Promise.resolve({
                    success: true,
                    tipo: 'formulario',
                    etapaFormId: etapaFormId,
                    formRunId: cambio.formRunId
                }));
            }
        }
        
        try {
            const resultados = await Promise.all(promesas);
            console.log('Todos los cambios procesados exitosamente:', resultados);
            
            // Actualizar vistas de formularios que estaban pendientes
            if (cambiosEtapa.formularios) {
                for (const [etapaFormId, cambio] of Object.entries(cambiosEtapa.formularios)) {
                    console.log(`Actualizando vista de formulario completado: ${etapaFormId}`);
                    actualizarVistaFormularioCompletado(etapaFormId, cambio.formRunId, false);
                }
            }
            
            // Actualizar contadores y progreso solo después de que todos los cambios se hayan guardado
            actualizarContadoresYProgreso();
            
            // Verificar y actualizar estado de etapas
            verificarYActualizarEstadoEtapas();
            
            // Verificar si se completó etapa o flujo
            const algunResultadoConEstados = resultados.find(r => r && r.estados);
            if (algunResultadoConEstados?.estados) {
                console.log('Estados recibidos en batch:', algunResultadoConEstados.estados);
                if (typeof algunResultadoConEstados.estados === 'object' && algunResultadoConEstados.estados.flujo_completado) {
                    console.log('Flujo completado detectado en batch, mostrando animación');
                    mostrarAnimacionComplecion(algunResultadoConEstados.estados.flujo_nombre);
                } else if (algunResultadoConEstados.estados === true) {
                    console.log('Etapa completada detectada en batch');
                    const etapaCard = document.querySelector(`[data-etapa-id="${etapaId}"]`);
                    marcarEtapaComoCompletada(etapaCard);
                }
            }
            
            return resultados;
        } catch (error) {
            console.error('Error en alguno de los cambios:', error);
            
            // Mostrar cuáles cambios fallaron específicamente
            let mensajeError = 'Error al grabar algunos cambios: ';
            if (error.message.includes('Invalid JSON')) {
                mensajeError += 'El servidor devolvió una respuesta inválida';
            } else if (error.message.includes('HTTP error')) {
                mensajeError += 'Error de conexión con el servidor';
            } else {
                mensajeError += error.message;
            }
            
            mostrarNotificacion(mensajeError + '. Revisa la consola para más detalles.', 'error');
            throw error;
        }
    }

    // Función para actualizar el contador de cambios pendientes de una etapa
    function actualizarContadorCambiosEtapa(etapaId) {
        const cambiosEtapa = cambiosPendientesPorEtapa[etapaId] || {};
        const etapaCard = document.querySelector(`[data-etapa-id="${etapaId}"]`);
        
        // Contar todos los cambios pendientes
        const totalCambios = (
            Object.keys(cambiosEtapa.tareas || {}).length +
            Object.keys(cambiosEtapa.documentos || {}).length +
            Object.keys(cambiosEtapa.formularios || {}).length
        );
        
        if (etapaCard) {
            const contador = etapaCard.querySelector('.contador-cambios-etapa');
            const boton = etapaCard.querySelector('.grabar-cambios-etapa');
            
            console.log(`Actualizando contador para etapa ${etapaId}: ${totalCambios} cambios pendientes`, {
                tareas: Object.keys(cambiosEtapa.tareas || {}).length,
                documentos: Object.keys(cambiosEtapa.documentos || {}).length,
                formularios: Object.keys(cambiosEtapa.formularios || {}).length
            });
            
            if (contador) {
                contador.textContent = totalCambios;
            }
            
            if (boton) {
                console.log(`Botón grabar cambios encontrado para etapa ${etapaId}, disabled: ${boton.disabled}`);
                
                if (totalCambios > 0) {
                    boton.style.display = 'inline-block';
                    // Asegurar que el botón esté habilitado si la etapa no está bloqueada
                    if (!etapaCard.classList.contains('bloqueada')) {
                        boton.disabled = false;
                        boton.style.pointerEvents = '';
                        console.log(`Habilitando botón grabar cambios para etapa ${etapaId}`);
                    }
                } else {
                    boton.style.display = 'none';
                }
            } else {
                console.warn(`No se encontró botón grabar-cambios-etapa para etapa ${etapaId}`);
            }
        }
    }

    // Función para agregar un cambio pendiente a una etapa específica
    function agregarCambioPendienteEtapa(etapaId, tipo, id, estado, datos = {}) {
        // Inicializar estructura para la etapa si no existe
        if (!cambiosPendientesPorEtapa[etapaId]) {
            cambiosPendientesPorEtapa[etapaId] = {
                tareas: {},
                documentos: {},
                formularios: {}
            };
        }
        
        const cambiosEtapa = cambiosPendientesPorEtapa[etapaId];
        
        // Agregar cambio según el tipo
        if (tipo === 'tarea') {
            cambiosEtapa.tareas[id] = { completada: estado };
        } else if (tipo === 'documento') {
            cambiosEtapa.documentos[id] = { validado: estado };
        } else if (tipo === 'formulario') {
            cambiosEtapa.formularios[id] = { 
                completado: estado,
                formRunId: datos.formRunId,
                nombre: datos.nombre 
            };
        }
        
        console.log(` Cambio agregado para etapa ${etapaId}:`, { tipo, id, estado, datos });
        console.log('📊 Estado actual de cambios pendientes:', cambiosPendientesPorEtapa[etapaId]);
        
        // Actualizar contador de cambios para esta etapa
        actualizarContadorCambiosEtapa(etapaId);
    }

    // Función para limpiar estilos visuales de cambios pendientes de una etapa
    function limpiarEstilosVisualesEtapa(etapaId) {
        const etapaCard = document.querySelector(`[data-etapa-id="${etapaId}"]`);
        
        if (etapaCard) {
            // Limpiar estilos de tareas y actualizar estado interno
            etapaCard.querySelectorAll('.cambio-pendiente-tarea').forEach(elemento => {
                elemento.classList.remove('cambio-pendiente-tarea');
                elemento.style.backgroundColor = '';
                elemento.style.border = '';
                
                // Actualizar estado interno del checkbox de la tarea
                const checkbox = elemento.querySelector('.tarea-checkbox');
                if (checkbox) {
                    checkbox.dataset.previouslyChecked = checkbox.checked.toString();
                }
            });
            
            // Limpiar estilos de documentos y actualizar estado interno
            etapaCard.querySelectorAll('.cambio-pendiente-documento').forEach(elemento => {
                elemento.classList.remove('cambio-pendiente-documento');
                elemento.style.backgroundColor = '';
                elemento.style.border = '';
                
                // Actualizar estado interno del checkbox del documento
                const checkbox = elemento.querySelector('.documento-checkbox');
                if (checkbox) {
                    checkbox.dataset.previouslyChecked = checkbox.checked.toString();
                }
            });
        }
    }

    // Función para expandir una etapa específica
    function expandirEtapa(etapaId) {
        const etapaCard = document.querySelector(`[data-etapa-id="${etapaId}"]`);
        if (!etapaCard) return;
        
        const targetId = `etapa-content-${etapaId}`;
        const targetElement = document.getElementById(targetId);
        const collapseButton = etapaCard.querySelector('.collapse-toggle');
        
        console.log('Expandiendo etapa:', targetId);
        
        if (targetElement && collapseButton) {
            // Asegurar que la etapa esté expandida
            targetElement.classList.add('show');
            collapseButton.classList.remove('collapsed');
            collapseButton.classList.add('expanded');
        }
    }

    // Función para limpiar estilos visuales de cambios pendientes
    function limpiarEstilosVisuales() {
        // Limpiar estilos de tareas
        document.querySelectorAll('.cambio-pendiente-tarea').forEach(elemento => {
            elemento.classList.remove('cambio-pendiente-tarea');
            elemento.style.backgroundColor = '';
            elemento.style.border = '';
        });
        
        // Limpiar estilos de documentos
        document.querySelectorAll('.cambio-pendiente-documento').forEach(elemento => {
            elemento.classList.remove('cambio-pendiente-documento');
            elemento.style.backgroundColor = '';
            elemento.style.border = '';
        });
    }

    // Función para actualizar visual de la tarea sin enviar al servidor
    function actualizarVisualTarea(checkbox, completada) {
        // Buscar el label
        let label = null;
        
        // Opción 1: label como hermano siguiente
        label = checkbox.parentElement.nextElementSibling?.querySelector('label');
        
        // Opción 2: label dentro del mismo contenedor padre
        if (!label) {
            const tareaContainer = checkbox.closest('.tarea-item, .form-check, .task-item');
            if (tareaContainer) {
                label = tareaContainer.querySelector('label');
            }
        }
        
        // Opción 3: label asociado por ID (for attribute)
        if (!label && checkbox.id) {
            label = document.querySelector(`label[for="${checkbox.id}"]`);
        }
        
        if (label) {
            // Actualizar visual del label
            if (completada) {
                label.classList.add('text-decoration-line-through', 'text-muted');
            } else {
                label.classList.remove('text-decoration-line-through', 'text-muted');
            }
        }
        
        // Actualizar visual del contenedor
        const tareaItem = checkbox.closest('.tarea-item');
        if (tareaItem) {
            // Remover clases anteriores
            tareaItem.classList.remove('cambio-pendiente-tarea');
            tareaItem.style.backgroundColor = '';
            tareaItem.style.border = '';
            
            // Agregar clase de cambio pendiente
            tareaItem.classList.add('cambio-pendiente-tarea');
        }
    }

    // Función para actualizar visual del documento sin enviar al servidor
    function actualizarVisualDocumento(checkbox, validado) {
        const documentoItem = checkbox.closest('.documento-item');
        if (documentoItem) {
            // Remover clases anteriores
            documentoItem.classList.remove('cambio-pendiente-documento');
            documentoItem.style.backgroundColor = '';
            documentoItem.style.border = '';
            
            // Agregar clase de cambio pendiente
            documentoItem.classList.add('cambio-pendiente-documento');
        }
    }

    // Función para actualizar una tarea individual
    function actualizarTareaIndividual(tareaId, completada) {
        if (!procesoIniciado) {
            mostrarNotificacion('Debes iniciar la ejecución del flujo primero', 'warning');
            return Promise.reject(new Error('Proceso no iniciado'));
        }

        if (!detalleFlujoId) {
            mostrarNotificacion('Error: No se encontró ID de ejecución', 'error');
            return Promise.reject(new Error('Sin detalle_flujo_id'));
        }

        // Mostrar indicador de carga en la tarea
        const tareaItem = document.querySelector(`[data-tarea-id="${tareaId}"]`).closest('.tarea-item');
        const originalClass = tareaItem.className;
        tareaItem.classList.add('border', 'border-info');
        
        // Enviar al servidor
        return fetch('{{ route('ejecucion.detalle.tarea.actualizar') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                tarea_id: tareaId,
                completada: completada,
                detalle_flujo_id: detalleFlujoId
            })
        })
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return response.text().then(text => {
                console.log('Raw response text:', text);
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('JSON parse error:', e);
                    console.error('Response text that failed to parse:', text);
                    throw new Error('Invalid JSON response from server');
                }
            });
        })
        .then(data => {
            console.log('Parsed response data:', data);
            
            if (data.success) {
                // Actualizar UI de la tarea
                const checkbox = document.querySelector(`[data-tarea-id="${tareaId}"]`);
                if (!checkbox) {
                    console.error('No se encontró checkbox con tarea-id:', tareaId);
                    return data;
                }
                
                // Actualizar checkbox con el estado real del servidor
                checkbox.checked = data.completada; // Usar el estado real del servidor
                
                // Buscar el label de manera más flexible
                let label = null;
                
                // Opción 1: label como hermano siguiente
                label = checkbox.parentElement.nextElementSibling?.querySelector('label');
                
                // Opción 2: label dentro del mismo contenedor padre
                if (!label) {
                    const tareaContainer = checkbox.closest('.tarea-item, .form-check, .task-item');
                    if (tareaContainer) {
                        label = tareaContainer.querySelector('label');
                    }
                }
                
                // Opción 3: label asociado por ID (for attribute)
                if (!label && checkbox.id) {
                    label = document.querySelector(`label[for="${checkbox.id}"]`);
                }
                
                // Opción 4: buscar cualquier label en el contenedor padre más amplio
                if (!label) {
                    const parentContainer = checkbox.closest('.tarea-item, .task-container, .form-group, .mb-2, .mb-3');
                    if (parentContainer) {
                        label = parentContainer.querySelector('label');
                    }
                }
                
                if (!label) {
                    console.warn('No se encontró label para la tarea:', tareaId, '. Estructura DOM:', checkbox.parentElement.parentElement);
                    // Continuar sin actualizar el label visual, pero la funcionalidad principal sigue funcionando
                } else {
                    // Actualizar visual del label según el estado real del servidor
                    if (data.completada) {
                        label.classList.add('text-decoration-line-through', 'text-muted');
                    } else {
                        label.classList.remove('text-decoration-line-through', 'text-muted');
                    }
                }
                
                // Actualizar visual del contenedor de la tarea
                if (data.completada) {
                    tareaItem.classList.add('border-success');
                    tareaItem.classList.remove('border-info');
                } else {
                    tareaItem.classList.remove('border-success', 'border-info');
                }
                
                // Actualizar estado interno
                checkbox.dataset.previouslyChecked = data.completada;
                checkbox.dataset.cambioLocal = 'false';
                
                console.log('Tarea actualizada:', {
                    tareaId: tareaId,
                    estadoSolicitado: completada,
                    estadoReal: data.completada,
                    checkboxChecked: checkbox.checked
                });
                
                // Limpiar estilos de cambio pendiente
                tareaItem.classList.remove('cambio-pendiente-tarea');
                
                return data;
            } else {
                // Revertir checkbox si hubo error del servidor
                const checkbox = document.querySelector(`[data-tarea-id="${tareaId}"]`);
                if (checkbox) {
                    checkbox.checked = !completada;
                }
                console.error('Server error:', data.message);
                throw new Error('Error del servidor: ' + (data.message || 'Error desconocido'));
            }
        })
        .catch(error => {
            console.error('Error completo:', error);
            console.error('Error stack:', error.stack);
            
            // Revertir checkbox si hubo error
            const checkbox = document.querySelector(`[data-tarea-id="${tareaId}"]`);
            if (checkbox) {
                checkbox.checked = !completada;
            }
            
            // Lanzar error para que Promise.all lo capture
            throw error;
        })
        .finally(() => {
            // Restaurar estado visual original
            tareaItem.className = originalClass;
        });
    }

    // Función para actualizar validación de documento individual
    function actualizarDocumentoIndividual(documentoId, validado) {
        if (!procesoIniciado) {
            mostrarNotificacion('Debes iniciar la ejecución del flujo primero', 'warning');
            return Promise.reject(new Error('Proceso no iniciado'));
        }

        if (!detalleFlujoId) {
            mostrarNotificacion('Error: No se encontró ID de ejecución', 'error');
            return Promise.reject(new Error('Sin detalle_flujo_id'));
        }

        // Mostrar indicador de carga en el documento
        const documentoItem = document.querySelector(`[data-documento-id="${documentoId}"]`).closest('.documento-item');
        const originalClass = documentoItem.className;
        documentoItem.classList.add('border', 'border-info');
        
        // Enviar al servidor
        return fetch('{{ route('ejecucion.detalle.documento.validar') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                documento_id: documentoId,
                validado: validado,
                detalle_flujo_id: detalleFlujoId
            })
        })
        .then(response => {
            console.log('Documento response status:', response.status);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return response.text().then(text => {
                console.log('Documento raw response:', text);
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Documento JSON parse error:', e);
                    console.error('Documento response text that failed to parse:', text);
                    throw new Error('Invalid JSON response from server');
                }
            });
        })
        .then(data => {
            console.log('Documento parsed response:', data);
            
            if (data.success) {
                // Actualizar checkbox con el estado real del servidor
                const checkbox = document.querySelector(`[data-documento-id="${documentoId}"]`);
                if (checkbox) {
                    checkbox.checked = data.validado; // Usar el estado real del servidor
                    checkbox.dataset.cambioLocal = 'false';
                }
                
                // Actualizar visual del documento según el estado real del servidor
                if (data.validado) {
                    documentoItem.classList.add('border-success');
                    documentoItem.classList.remove('border-warning', 'border-info');
                } else {
                    documentoItem.classList.remove('border-success', 'border-info');
                    documentoItem.classList.add('border-warning');
                }
                
                // Limpiar estilos de cambio pendiente
                documentoItem.classList.remove('cambio-pendiente-documento');
                
                console.log('Documento actualizado:', {
                    documentoId: documentoId,
                    estadoSolicitado: validado,
                    estadoReal: data.validado,
                    checkboxChecked: checkbox ? checkbox.checked : 'not found'
                });
                
                return data;
            } else {
                // Revertir checkbox si hubo error del servidor
                const checkbox = document.querySelector(`[data-documento-id="${documentoId}"]`);
                if (checkbox) {
                    checkbox.checked = !validado;
                }
                console.error('Documento server error:', data.message);
                throw new Error('Error del servidor: ' + (data.message || 'Error desconocido'));
            }
        })
        .catch(error => {
            console.error('Documento error completo:', error);
            console.error('Documento error stack:', error.stack);
            
            // Revertir checkbox si hubo error
            const checkbox = document.querySelector(`[data-documento-id="${documentoId}"]`);
            if (checkbox) {
                checkbox.checked = !validado;
            }
            
            // Lanzar error para que Promise.all lo capture
            throw error;
        })
        .finally(() => {
            // Restaurar estado visual original
            documentoItem.className = originalClass;
        });
    }

    // Manejar subida de documentos
    document.querySelectorAll('.subir-documento').forEach(btn => {
        btn.addEventListener('click', function() {
            if (!procesoIniciado) {
                mostrarNotificacion('Debes iniciar la ejecución del flujo primero', 'warning');
                return;
            }

            if (!detalleFlujoId) {
                mostrarNotificacion('Error: No se encontró ID de ejecución', 'error');
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
            mostrarNotificacion('Por favor selecciona un archivo', 'warning');
            return;
        }

        // Validar tipos de archivo permitidos
        const tiposPermitidos = [
            'application/pdf',                                          // PDF
            'application/msword',                                       // DOC
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // DOCX
            'application/vnd.ms-excel',                                // XLS
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // XLSX
            'application/vnd.ms-powerpoint',                           // PPT
            'application/vnd.openxmlformats-officedocument.presentationml.presentation', // PPTX
            'text/csv',                                                // CSV
            'image/png',                                               // PNG
            'image/jpeg',                                              // JPG/JPEG
            'image/gif',                                               // GIF
            'image/bmp',                                               // BMP
            'image/webp'                                               // WEBP
        ];

        if (!tiposPermitidos.includes(file.type)) {
            mostrarNotificacion('Tipo de archivo no permitido. Se aceptan: PDF, Word, Excel, PowerPoint, CSV e imágenes', 'warning');
            return;
        }

        if (file.size > 10 * 1024 * 1024) { // 10MB
            mostrarNotificacion('El archivo es demasiado grande. Máximo 10MB', 'warning');
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
                                   data-documento-id="${documentoId}"
                                   checked
                                   disabled
                                   title="Los checkboxes son solo visuales - El estado se controla automáticamente">
                        </div>
                        <span class="badge bg-success">
                            <i class="fas fa-check me-1"></i>Subido - Listo para Validar
                        </span>
                    </div>
                `;
                
                // La información de "Subido por" se mostrará automáticamente
                // cuando se recargue la página desde el servidor
                
                // Agregar botones de ver y descargar
                const archivoUrl = data.archivo_url;
                const extension = archivoUrl.split('.').pop().toLowerCase();
                const esPDF = extension === 'pdf';
                const esImagen = ['png', 'jpg', 'jpeg', 'gif', 'bmp', 'webp'].includes(extension);
                const puedePrevisualizar = esPDF || esImagen;
                
                let botonesHTML = '';
                
                if (puedePrevisualizar) {
                    botonesHTML += `
                        <button type="button" class="btn btn-outline-primary btn-sm ver-archivo" 
                                data-documento-id="${documentoId}"
                                data-url="${archivoUrl}"
                                data-tipo="${esPDF ? 'pdf' : 'imagen'}"
                                title="${esPDF ? 'Ver PDF' : 'Ver Imagen'}">
                            <i class="fas fa-eye"></i>
                        </button>`;
                }
                
                botonesHTML += `
                    <a href="${archivoUrl}" 
                       class="btn btn-outline-secondary btn-sm" 
                       download="${data.nombre_archivo || 'documento'}"
                       title="Descargar">
                        <i class="fas fa-download"></i>
                    </a>
                    <button type="button" class="btn btn-outline-danger btn-sm eliminar-documento" 
                            data-documento-id="${documentoId}"
                            data-documento-nombre="${document.getElementById('documento-nombre').textContent}"
                            data-url="${archivoUrl}"
                            title="Eliminar documento">
                        <i class="fas fa-trash"></i>
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-sm subir-documento" 
                            data-documento-id="${documentoId}"
                            title="Cambiar archivo">
                        <i class="fas fa-upload"></i>
                    </button>
                `;
                
                btnGroup.innerHTML = botonesHTML;
                
                // Reagregar event listeners
                const btnVerArchivo = btnGroup.querySelector('.ver-archivo');
                if (btnVerArchivo) {
                    btnVerArchivo.addEventListener('click', verArchivo);
                }
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
                        mostrarNotificacion('Debes iniciar la ejecución del flujo primero', 'warning');
                        return;
                    }

                    if (!detalleFlujoId) {
                        mostrarNotificacion('Error: No se encontró ID de ejecución', 'error');
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
                
                // Agregar event listener al nuevo checkbox
                const newCheckbox = statusElement.querySelector('.documento-checkbox');
                if (newCheckbox) {
                    newCheckbox.addEventListener('change', function() {
                        const documentoId = this.dataset.documentoId;
                        const validado = this.checked;
                        
                        // Encontrar la etapa padre
                        const etapaCard = this.closest('.card');
                        const etapaId = etapaCard ? etapaCard.querySelector('.grabar-cambios-etapa').dataset.etapaId : null;
                        
                        // Solo cambio visual
                        actualizarVisualDocumento(this, validado);
                        
                        // Agregar a cambios pendientes por etapa
                        if (etapaId) {
                            agregarCambioPendienteEtapa(etapaId, 'documento', documentoId, validado);
                        }
                    });
                    
                    // Como el checkbox está marcado automáticamente, agregar el cambio pendiente
                    const etapaId = documentoItem.closest('.card').querySelector('.grabar-cambios-etapa')?.dataset.etapaId;
                    if (etapaId) {
                        agregarCambioPendienteEtapa(etapaId, 'documento', documentoId, true);
                        // Actualizar visual del documento como validado
                        actualizarVisualDocumento(newCheckbox, true);
                    }
                }
                
                // Verificar si la tarea debe completarse automáticamente
                console.log('🔍 Buscando tareaElement para verificación automática...');
                
                // Primero buscar directamente
                let tareaElement = documentoItem.closest('[data-tarea-id]');
                
                // Si no lo encuentra, buscar dentro del tarea-container
                if (!tareaElement) {
                    const tareaContainer = documentoItem.closest('.tarea-container');
                    if (tareaContainer) {
                        tareaElement = tareaContainer.querySelector('[data-tarea-id]');
                        console.log('🎯 Buscando dentro de tarea-container:', tareaContainer);
                        console.log('🎯 TareaElement encontrado en container:', tareaElement);
                    }
                }
                
                console.log(' TareaElement encontrado:', tareaElement);
                
                if (tareaElement) {
                    console.log(' Ejecutando verificación automática de tarea...');
                    setTimeout(() => {
                        console.log('⏰ Timeout ejecutado, llamando verificarYActualizarEstadoTarea');
                        verificarYActualizarEstadoTarea(tareaElement);
                    }, 100);
                } else {
                    console.log(' No se encontró tareaElement para verificación automática');
                    console.log('📄 DocumentoItem:', documentoItem);
                    console.log('🔍 Buscando [data-tarea-id] en ancestors...');
                    let ancestor = documentoItem.parentElement;
                    let level = 1;
                    while (ancestor && level <= 10) {
                        console.log(`  Nivel ${level}:`, ancestor, ancestor.getAttribute('data-tarea-id'));
                        if (ancestor.getAttribute('data-tarea-id')) {
                            console.log(`   Encontrado en nivel ${level}!`);
                            break;
                        }
                        ancestor = ancestor.parentElement;
                        level++;
                    }
                }
                
                // Cerrar modal
                bootstrap.Modal.getInstance(document.getElementById('uploadModal')).hide();
                document.getElementById('uploadForm').reset();
                
                // Mostrar mensaje de éxito
                mostrarMensajeExito('Documento subido y marcado para validación. Presiona "Grabar Cambios" para confirmar.');
                
                console.log('Documento subido:', data);
            } else {
                mostrarNotificacion('Error al subir el documento: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarNotificacion('Error al subir el documento', 'error');
        })
        .finally(() => {
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-upload me-2"></i>Subir Documento';
        });
    });

    // Ver PDF
    function verArchivo() {
        const documentoId = this.dataset.documentoId;
        const url = this.dataset.url;
        const tipo = this.dataset.tipo;
        const documentoNombre = this.closest('.documento-item').querySelector('h6').textContent;
        
        if (tipo === 'pdf') {
            // Mostrar PDF en modal existente
            document.getElementById('pdf-title').textContent = documentoNombre;
            document.getElementById('pdf-viewer').src = url;
            document.getElementById('pdf-download').href = url;
            
            const modal = new bootstrap.Modal(document.getElementById('pdfModal'));
            modal.show();
        } else if (tipo === 'imagen') {
            // Crear y mostrar modal para imagen
            let imagenModal = document.getElementById('imagenModal');
            if (!imagenModal) {
                // Crear modal de imagen si no existe
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

    // Event listeners para ver archivos (PDFs e imágenes)
    document.querySelectorAll('.ver-archivo').forEach(btn => {
        btn.addEventListener('click', verArchivo);
    });

    // Mantener compatibilidad con botones ver-pdf existentes
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
                        const estadoIcon = etapaElement.querySelector('.estado-etapa i');
                        const statusText = etapaElement.querySelector('.card-header small');
                        
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
                                statusText.innerHTML = `<i class="fas fa-check-circle text-success me-1"></i>Completada • Progreso: <span class="progreso-etapa" data-etapa="${etapaData.id}">${etapaData.progreso}%</span>`;
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
                                estadoIcon.classList.add('text-primary');
                            }
                            if (statusText) {
                                statusText.innerHTML = `<i class="fas fa-play-circle text-primary me-1"></i>En Progreso • Progreso: <span class="progreso-etapa" data-etapa="${etapaData.id}">${etapaData.progreso}%</span>`;
                            }
                        } else {
                            // Etapa pendiente (progreso = 0)
                            if (statusText) {
                                statusText.innerHTML = `Pendiente • Progreso: <span class="progreso-etapa" data-etapa="${etapaData.id}">${etapaData.progreso}%</span>`;
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

    // Nueva función que solo actualiza contadores sin afectar checkboxes
    function actualizarContadoresYProgreso() {
        if (!detalleFlujoId) {
            console.log('No hay detalleFlujoId, omitiendo actualización de progreso');
            return;
        }
        
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
                    // Actualizar solo contadores y progreso, NO los checkboxes
                    data.etapas.forEach(etapaData => {
                        const etapaElement = document.querySelector(`[data-etapa-id="${etapaData.id}"]`);
                        if (etapaElement) {
                            const progresoElement = etapaElement.querySelector('.progreso-etapa');
                            const estadoIcon = etapaElement.querySelector('.estado-etapa i');
                            const statusText = etapaElement.querySelector('.card-header small');
                            
                            // Actualizar progreso
                            if (progresoElement) {
                                progresoElement.textContent = etapaData.progreso + '%';
                            }
                            
                            // Sincronizar estado de tareas con servidor (sin forzar cambios)
                            if (etapaData.tareas && Array.isArray(etapaData.tareas)) {
                                etapaData.tareas.forEach(tareaServer => {
                                    const checkbox = etapaElement.querySelector(`[data-tarea-id="${tareaServer.id}"]`);
                                    if (checkbox) {
                                        // Solo actualizar si hay diferencia y no hay cambios pendientes locales
                                        const estadoLocalPendiente = checkbox.dataset.cambioLocal === 'true';
                                        if (!estadoLocalPendiente && checkbox.checked !== tareaServer.completada) {
                                            checkbox.checked = tareaServer.completada;
                                            checkbox.dataset.previouslyChecked = tareaServer.completada;
                                            
                                            // Actualizar visual del label
                                            let label = null;
                                            
                                            // Buscar el label de manera flexible (mismo código que arriba)
                                            label = checkbox.parentElement.nextElementSibling?.querySelector('label');
                                            
                                            if (!label) {
                                                const tareaContainer = checkbox.closest('.tarea-item, .form-check, .task-item');
                                                if (tareaContainer) {
                                                    label = tareaContainer.querySelector('label');
                                                }
                                            }
                                            
                                            if (!label && checkbox.id) {
                                                label = document.querySelector(`label[for="${checkbox.id}"]`);
                                            }
                                            
                                            if (!label) {
                                                const parentContainer = checkbox.closest('.tarea-item, .task-container, .form-group, .mb-2, .mb-3');
                                                if (parentContainer) {
                                                    label = parentContainer.querySelector('label');
                                                }
                                            }
                                            
                                            if (label) {
                                                if (tareaServer.completada) {
                                                    label.classList.add('text-decoration-line-through', 'text-muted');
                                                } else {
                                                    label.classList.remove('text-decoration-line-through', 'text-muted');
                                                }
                                            }
                                        }
                                        
                                        // La información de completado se gestiona desde el servidor
                                        // y se muestra correctamente en el HTML inicial
                                    }
                                });
                            }
                            
                            // Sincronizar estado de documentos con servidor
                            if (etapaData.documentos && Array.isArray(etapaData.documentos)) {
                                etapaData.documentos.forEach(documentoServer => {
                                    const checkbox = etapaElement.querySelector(`[data-documento-id="${documentoServer.id}"]`);
                                    if (checkbox) {
                                        // Solo actualizar si hay diferencia y no hay cambios pendientes locales
                                        const estadoLocalPendiente = checkbox.dataset.cambioLocal === 'true';
                                        if (!estadoLocalPendiente && checkbox.checked !== documentoServer.validado) {
                                            checkbox.checked = documentoServer.validado;
                                        }
                                        
                                        // La información de validación se gestiona desde el servidor
                                        // y se muestra correctamente en el HTML inicial
                                    }
                                });
                            }
                            
                            // Actualizar contadores de tareas y documentos
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
                            
                            // Actualizar contadores de formularios
                            const formulariosCompletadosElement = etapaElement.querySelector('.formularios-completados');
                            const totalFormulariosElement = etapaElement.querySelector('.total-formularios');
                            
                            if (formulariosCompletadosElement && etapaData.formularios_completados !== undefined) {
                                formulariosCompletadosElement.textContent = etapaData.formularios_completados;
                            }
                            
                            if (totalFormulariosElement && etapaData.total_formularios !== undefined) {
                                totalFormulariosElement.textContent = etapaData.total_formularios;
                            }                            // Actualizar estado visual de la etapa
                            if (etapaData.progreso === 100) {
                                etapaElement.classList.remove('activa');
                                etapaElement.classList.add('completada');
                                if (estadoIcon) {
                                    estadoIcon.classList.remove('text-primary', 'text-warning', 'text-secondary');
                                    estadoIcon.classList.add('text-success');
                                }
                                if (statusText) {
                                    statusText.innerHTML = `Completada • Progreso: <span class="progreso-etapa">${etapaData.progreso}%</span>`;
                                }
                                
                                // Activar siguiente etapa si existe
                                const siguienteEtapa = etapaElement.nextElementSibling;
                                if (siguienteEtapa && siguienteEtapa.classList.contains('etapa-card') && 
                                    !siguienteEtapa.classList.contains('activa') && 
                                    !siguienteEtapa.classList.contains('completada')) {
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
                            } else {
                                if (statusText) {
                                    statusText.innerHTML = `Pendiente • Progreso: <span class="progreso-etapa">${etapaData.progreso}%</span>`;
                                }
                            }
                        }
                    });
                }
            }
        })
        .catch(error => {
            console.error('Error al obtener progreso:', error);
        });
    }

    // Función para verificar si el flujo está completado al cargar la página
    function verificarEstadoCompletado() {
        if (!detalleFlujoId) {
            return;
        }
        
        console.log('Verificando estado completado del flujo...');
        
        fetch(`{{ route('ejecucion.detalle.progreso', ':detalleFlujoId') }}`.replace(':detalleFlujoId', detalleFlujoId))
        .then(response => response.json())
        .then(data => {
            console.log('Datos de progreso obtenidos:', data);
            
            if (data && data.progreso_general === 100) {
                console.log('Flujo al 100% detectado');
                // Si el flujo está al 100%, debería activarse la animación automáticamente
                // Simular que se está completando ahora mismo
                setTimeout(() => {
                    const flujoNombre = '{{ $flujo->nombre ?? "Flujo" }}';
                    console.log('Activando animación para flujo completado:', flujoNombre);
                    mostrarAnimacionComplecion(flujoNombre);
                }, 500);
            }
        })
        .catch(error => {
            console.error('Error al verificar progreso inicial:', error);
        });
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
        
        // Verificar y actualizar estado de etapas bloqueadas
        verificarYActualizarEstadoEtapas();
        
        // Activar siguiente etapa disponible
        const todasLasEtapas = document.querySelectorAll('.etapa-card');
        const indiceActual = Array.from(todasLasEtapas).indexOf(etapaCard);
        
        // Buscar la siguiente etapa que pueda ser activada
        for (let i = indiceActual + 1; i < todasLasEtapas.length; i++) {
            const siguienteEtapa = todasLasEtapas[i];
            
            if (!siguienteEtapa.classList.contains('activa') && 
                !siguienteEtapa.classList.contains('completada') &&
                !siguienteEtapa.classList.contains('bloqueada')) {
                
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
                break; // Solo activar la primera etapa disponible
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
                // Asegurar redirección limpia sin parámetros de query
                window.location.href = '/ejecucion';
            }
        }, 1000);
        
        // Botón para redirigir inmediatamente
        overlay.querySelector('#redirect-now').addEventListener('click', () => {
            clearInterval(countdownInterval);
            // Asegurar redirección limpia sin parámetros de query
            window.location.href = '/ejecucion';
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

    // Función para agregar event listeners a los checkboxes para cambios visuales
    function agregarListenersCambiosVisuales() {
        // Event listeners para checkboxes de tareas
        document.querySelectorAll('.tarea-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function(e) {
                // Si el checkbox está deshabilitado, no hacer nada (tareas con documentos)
                if (this.disabled) {
                    console.log('Checkbox de tarea deshabilitado - controlado automáticamente por documentos');
                    return;
                }
                
                // Verificar si la etapa está bloqueada
                const etapaCard = this.closest('.etapa-card');
                if (etapaCard && etapaCard.classList.contains('bloqueada')) {
                    // Revertir el cambio
                    e.preventDefault();
                    this.checked = !this.checked;
                    mostrarNotificacion('Esta etapa está bloqueada. Completa las etapas secuenciales anteriores primero.', 'warning');
                    return;
                }
                
                const wasChecked = e.target.checked;
                const previouslyChecked = this.dataset.previouslyChecked === 'true';
                const tareaId = this.dataset.tareaId;
                
                console.log(`📝 Tarea manual ${tareaId}: marcada=${wasChecked}, anteriormente=${previouslyChecked}`);
                
                // Encontrar la etapa padre
                const etapaId = etapaCard ? etapaCard.querySelector('.grabar-cambios-etapa').dataset.etapaId : null;
                
                // Si se está desmarcando una tarea que estaba marcada originalmente, mostrar modal de confirmación
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
                    document.getElementById('confirmar-desmarcar-tarea').dataset.tareaId = tareaId;
                    document.getElementById('confirmar-desmarcar-tarea').dataset.etapaId = etapaId;
                    document.getElementById('confirmar-desmarcar-tarea').dataset.tipo = 'tarea';
                    
                    modal.show();
                } else {
                    // Solo cambio visual
                    actualizarVisualTarea(this, wasChecked);
                    
                    // Agregar a cambios pendientes por etapa
                    if (etapaId) {
                        agregarCambioPendienteEtapa(etapaId, 'tarea', tareaId, wasChecked);
                    }
                }
            });
            
            // Establecer estado inicial
            checkbox.dataset.previouslyChecked = checkbox.checked;
        });
        
        // Event listeners para checkboxes de documentos
        document.querySelectorAll('.documento-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                // Verificar si la etapa está bloqueada
                const etapaCard = this.closest('.etapa-card');
                if (etapaCard && etapaCard.classList.contains('bloqueada')) {
                    // Revertir el cambio
                    this.checked = !this.checked;
                    mostrarNotificacion('Esta etapa está bloqueada. Completa las etapas secuenciales anteriores primero.', 'warning');
                    return;
                }
                
                const documentoId = this.dataset.documentoId;
                const validado = this.checked;
                const previouslyChecked = this.dataset.previouslyChecked === 'true';
                
                // Encontrar la etapa padre
                const etapaId = etapaCard ? etapaCard.querySelector('.grabar-cambios-etapa').dataset.etapaId : null;
                
                // Si se está desmarcando un documento que estaba validado originalmente, mostrar modal de confirmación
                if (!validado && previouslyChecked) {
                    this.checked = true; // Revertir temporalmente
                    
                    const documentoItem = this.closest('.documento-item');
                    const documentoLabel = documentoItem.querySelector('h6');
                    const documentoNombre = documentoLabel.textContent.trim();
                    
                    // Configurar modal (reutilizamos el mismo modal)
                    document.getElementById('nombre-tarea-desmarcar').textContent = documentoNombre;
                    
                    // Cambiar textos del modal para documentos
                    const modalTitle = document.querySelector('#confirmarDesmarcarTarea .modal-title');
                    const modalQuestion = document.querySelector('#confirmarDesmarcarTarea h6');
                    const modalDescription = document.querySelector('#confirmarDesmarcarTarea p');
                    
                    modalTitle.textContent = 'Confirmar Acción';
                    modalQuestion.textContent = '¿Desmarcar documento validado?';
                    modalDescription.innerHTML = `
                        Estás a punto de cambiar el estado del documento "<strong>${documentoNombre}</strong>" 
                        de <span class="badge bg-success">Validado</span> a <span class="badge bg-secondary">Pendiente</span>.
                    `;
                    
                    const modal = new bootstrap.Modal(document.getElementById('confirmarDesmarcarTarea'));
                    
                    // Guardar referencia para la confirmación
                    document.getElementById('confirmar-desmarcar-tarea').dataset.documentoCheckbox = this.id;
                    document.getElementById('confirmar-desmarcar-tarea').dataset.documentoId = documentoId;
                    document.getElementById('confirmar-desmarcar-tarea').dataset.etapaId = etapaId;
                    document.getElementById('confirmar-desmarcar-tarea').dataset.tipo = 'documento';
                    
                    modal.show();
                } else {
                    // Solo cambio visual
                    actualizarVisualDocumento(this, validado);
                    
                    // Agregar a cambios pendientes por etapa
                    if (etapaId) {
                        agregarCambioPendienteEtapa(etapaId, 'documento', documentoId, validado);
                    }
                }
            });
            
            // Establecer estado inicial
            checkbox.dataset.previouslyChecked = checkbox.checked;
        });
    }

    // Event listener para confirmar desmarcar tarea o documento
    document.getElementById('confirmar-desmarcar-tarea').addEventListener('click', function() {
        const tipo = this.dataset.tipo || 'tarea';
        const etapaId = this.dataset.etapaId;
        
        console.log('Confirmando desmarcar:', { tipo, etapaId });
        
        if (tipo === 'tarea') {
            // Manejar desmarcar tarea
            const checkboxId = this.dataset.tareaCheckbox;
            const tareaId = this.dataset.tareaId;
            const checkbox = document.getElementById(checkboxId);
            
            console.log('Desmarcando tarea:', { checkboxId, tareaId, checkbox: !!checkbox });
            
            if (checkbox && tareaId) {
                // Cerrar modal primero
                bootstrap.Modal.getInstance(document.getElementById('confirmarDesmarcarTarea')).hide();
                
                // Actualizar al servidor y luego recargar página
                fetch('{{ route('ejecucion.detalle.tarea.actualizar') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        tarea_id: tareaId,
                        completada: false,
                        detalle_flujo_id: detalleFlujoId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        mostrarNotificacion('Tarea regresada a estado inicial', 'success');
                        // Recargar página después de un breve delay
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        mostrarNotificacion('Error: ' + (data.message || 'Error desconocido'), 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    mostrarNotificacion('Error al actualizar la tarea', 'error');
                });
            }
        } else if (tipo === 'documento') {
            // Manejar desmarcar documento
            const checkboxId = this.dataset.documentoCheckbox;
            const documentoId = this.dataset.documentoId;
            const checkbox = document.getElementById(checkboxId);
            
            console.log('Desmarcando documento:', { checkboxId, documentoId, checkbox: !!checkbox });
            
            if (checkbox && documentoId) {
                // Cerrar modal primero
                bootstrap.Modal.getInstance(document.getElementById('confirmarDesmarcarTarea')).hide();
                
                // Actualizar al servidor y luego recargar página
                fetch('{{ route('ejecucion.detalle.documento.validar') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        documento_id: documentoId,
                        validado: false,
                        detalle_flujo_id: detalleFlujoId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        mostrarNotificacion('Documento regresado a estado inicial', 'success');
                        // Recargar página después de un breve delay
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        mostrarNotificacion('Error: ' + (data.message || 'Error desconocido'), 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    mostrarNotificacion('Error al actualizar el documento', 'error');
                });
            }
        }
        
        // Limpiar datasets al final
        delete this.dataset.tipo;
        delete this.dataset.tareaCheckbox;
        delete this.dataset.tareaId;
        delete this.dataset.documentoCheckbox;
        delete this.dataset.documentoId;
        delete this.dataset.etapaId;
    });

    // Event listeners para eliminar documentos
    function agregarListenersEliminarDocumento() {
        document.querySelectorAll('.eliminar-documento').forEach(btn => {
            btn.addEventListener('click', function() {
                if (!procesoIniciado) {
                    mostrarNotificacion('Debes iniciar la ejecución del flujo primero', 'warning');
                    return;
                }

                if (!detalleFlujoId) {
                    mostrarNotificacion('Error: No se encontró ID de ejecución', 'error');
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
                
                // Verificar si la tarea debe desmarcarse automáticamente
                let tareaElement = documentoItem.closest('[data-tarea-id]');
                
                // Si no lo encuentra, buscar dentro del tarea-container
                if (!tareaElement) {
                    const tareaContainer = documentoItem.closest('.tarea-container');
                    if (tareaContainer) {
                        tareaElement = tareaContainer.querySelector('[data-tarea-id]');
                    }
                }
                
                if (tareaElement) {
                    setTimeout(() => verificarYActualizarEstadoTarea(tareaElement), 100);
                }
                
                // Cerrar modal
                bootstrap.Modal.getInstance(modal).hide();
                
                // Mostrar mensaje de éxito y recargar página
                mostrarMensajeExito('Documento eliminado correctamente. Recargando página...');
                
                // Recargar la página después de un breve delay
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                mostrarNotificacion('Error al eliminar el documento: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarNotificacion('Error al eliminar el documento', 'error');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalHtml;
        });
    });

    // Función auxiliar para mostrar modal de subir documento
    function mostrarModalSubirDocumento(documentoId) {
        if (!procesoIniciado) {
            mostrarNotificacion('Debes iniciar la ejecución del flujo primero', 'warning');
            return;
        }

        if (!detalleFlujoId) {
            mostrarNotificacion('Error: No se encontró ID de ejecución', 'error');
            return;
        }

        const documentoItem = document.querySelector(`[data-documento-id="${documentoId}"]`);
        const documentoNombre = documentoItem.querySelector('h6').textContent;
        
        document.getElementById('documento-nombre').textContent = documentoNombre;
        document.getElementById('uploadModal').dataset.documentoId = documentoId;
        
        const modal = new bootstrap.Modal(document.getElementById('uploadModal'));
        modal.show();
    }

    // Función para verificar automáticamente si una tarea debe marcarse/desmarcarse basándose en sus documentos
    function verificarYActualizarEstadoTarea(tareaElement) {
        console.log(' === INICIANDO verificarYActualizarEstadoTarea ===');
        console.log(' TareaElement recibido:', tareaElement);
        
        if (!tareaElement) {
            console.log(' TareaElement es null o undefined');
            return;
        }

        const tareaId = tareaElement.dataset.tareaId;
        console.log('🆔 TareaId extraído:', tareaId);
        
        if (!tareaId) {
            console.log(' No se pudo extraer tareaId del elemento');
            return;
        }

        // Buscar todos los documentos asociados a esta tarea específicamente dentro del contenedor de la tarea
        const tareaContainer = tareaElement.closest('.tarea-container');
        const documentoElements = tareaContainer ? tareaContainer.querySelectorAll('[data-documento-id]') : [];
        
        // Obtener IDs únicos de documentos (evitar duplicados de botones/checkboxes del mismo documento)
        const documentosUnicos = new Set();
        documentoElements.forEach(element => {
            const docId = element.dataset.documentoId;
            if (docId) {
                documentosUnicos.add(docId);
            }
        });
        
        const documentosTarea = Array.from(documentosUnicos);
        console.log(`Verificando tarea ${tareaId}, encontrados ${documentosTarea.length} documentos únicos: [${documentosTarea.join(', ')}]`);
        
        if (documentosTarea.length === 0) {
            // Si no hay documentos, esta tarea se maneja manualmente - no hacer nada automático
            console.log(`Tarea ${tareaId} no tiene documentos asociados - se maneja manualmente`);
            return;
        }

        // Verificar si todos los documentos están subidos
        let todosDocumentosSubidos = true;
        let documentosSubidos = 0;
        
        documentosTarea.forEach((documentoId, index) => {
            // Buscar el div principal del documento (que contiene el estado)
            const documentoDiv = tareaContainer.querySelector(`.documento-item[data-documento-id="${documentoId}"]`);
            console.log(`  Documento ${index + 1} (ID: ${documentoId}):`, documentoDiv);
            
            if (documentoDiv) {
                const statusElement = documentoDiv.querySelector('.document-status');
                if (statusElement) {
                    // Buscar cualquier badge que indique estado completado
                    const badges = statusElement.querySelectorAll('.badge');
                    let documentoCompletado = false;
                    
                    badges.forEach(badge => {
                        const badgeText = badge.textContent.toLowerCase();
                        console.log(`    Badge encontrado: "${badgeText}", clases: ${badge.className}`);
                        
                        // Considerar completado si es validado, subido, o tiene bg-success/bg-info
                        if (badge.classList.contains('bg-success') || 
                            badge.classList.contains('bg-info') ||
                            badgeText.includes('validado') ||
                            badgeText.includes('subido')) {
                            documentoCompletado = true;
                        }
                    });
                    
                    if (documentoCompletado) {
                        documentosSubidos++;
                        console.log(`    ✓ Documento ${index + 1} (ID: ${documentoId}) está completado`);
                    } else {
                        todosDocumentosSubidos = false;
                        console.log(`    ✗ Documento ${index + 1} (ID: ${documentoId}) NO está completado`);
                    }
                } else {
                    todosDocumentosSubidos = false;
                    console.log(`    ✗ Documento ${index + 1} (ID: ${documentoId}) no tiene elemento de estado`);
                }
            } else {
                todosDocumentosSubidos = false;
                console.log(`    ✗ Documento ${index + 1} (ID: ${documentoId}) no se encontró el div principal`);
            }
        });
        
        console.log(`Tarea ${tareaId}: ${documentosSubidos}/${documentosTarea.length} documentos subidos. Todos completos: ${todosDocumentosSubidos}`);

        // Buscar el checkbox de la tarea en el contenedor de la tarea
        const tareaCheckbox = tareaContainer ? tareaContainer.querySelector('.tarea-checkbox') : null;
        if (!tareaCheckbox) {
            console.log(`No se encontró checkbox para tarea ${tareaId}`);
            return;
        }

        const tareaActualmenteCompletada = tareaCheckbox.checked;
        console.log(`Tarea ${tareaId} actualmente completada: ${tareaActualmenteCompletada}`);

        // Si todos los documentos están subidos y la tarea no está marcada, marcarla
        if (todosDocumentosSubidos && !tareaActualmenteCompletada) {
            console.log(`Marcando tarea ${tareaId} como completada automáticamente`);
            
            // Marcar el checkbox físicamente
            tareaCheckbox.checked = true;
            
            // Forzar el disparo del evento de cambio para actualizar visual
            const changeEvent = new Event('change', { bubbles: true });
            tareaCheckbox.dispatchEvent(changeEvent);
            
            // También actualizar visual directamente
            actualizarVisualTarea(tareaCheckbox, true);
            
            // Agregar clase visual de completado
            const tareaContainer = tareaCheckbox.closest('.tarea-container');
            if (tareaContainer) {
                tareaContainer.classList.add('tarea-completada');
            }
            
            // Actualizar en el servidor
            actualizarTareaIndividual(tareaId, true)
                .then(() => {
                    console.log(` Tarea ${tareaId} marcada exitosamente en BD`);
                    mostrarMensajeExito('Tarea completada automáticamente al subir todos los documentos.');
                })
                .catch(error => {
                    console.error(' Error al actualizar tarea:', error);
                    // Revertir cambio visual si falla
                    tareaCheckbox.checked = false;
                    actualizarVisualTarea(tareaCheckbox, false);
                    if (tareaContainer) {
                        tareaContainer.classList.remove('tarea-completada');
                    }
                });
        }
        // Si faltan documentos y la tarea está marcada, desmarcarla
        else if (!todosDocumentosSubidos && tareaActualmenteCompletada) {
            console.log(`Desmarcando tarea ${tareaId} porque faltan documentos`);
            
            // Desmarcar el checkbox físicamente
            tareaCheckbox.checked = false;
            
            // Forzar el disparo del evento de cambio para actualizar visual
            const changeEvent = new Event('change', { bubbles: true });
            tareaCheckbox.dispatchEvent(changeEvent);
            
            // También actualizar visual directamente
            actualizarVisualTarea(tareaCheckbox, false);
            
            // Remover clase visual de completado
            const tareaContainer = tareaCheckbox.closest('.tarea-container');
            if (tareaContainer) {
                tareaContainer.classList.remove('tarea-completada');
            }
            
            // Actualizar en el servidor
            actualizarTareaIndividual(tareaId, false)
                .then(() => {
                    console.log(` Tarea ${tareaId} desmarcada exitosamente en BD`);
                    mostrarMensajeExito('Tarea desmarcada automáticamente porque faltan documentos.');
                })
                .catch(error => {
                    console.error(' Error al actualizar tarea:', error);
                    // Revertir cambio visual si falla
                    tareaCheckbox.checked = true;
                    actualizarVisualTarea(tareaCheckbox, true);
                    if (tareaContainer) {
                        tareaContainer.classList.add('tarea-completada');
                    }
                });
        }
    }

    // Event listeners para los formularios
    function agregarListenersFormularios() {
        // Event listener para rellenar formulario
        document.querySelectorAll('.rellenar-formulario').forEach(btn => {
            btn.addEventListener('click', function() {
                const etapaFormId = this.dataset.etapaFormId;
                const formId = this.dataset.formId;
                const formNombre = this.dataset.formNombre;
                const formRunId = this.dataset.formRunId;
                
                console.log('Abriendo formulario:', {etapaFormId, formId, formNombre, formRunId});
                
                abrirModalFormulario(etapaFormId, formId, formNombre, formRunId);
            });
        });

        // Event listener para ver formulario completado
        document.querySelectorAll('.ver-formulario-completado').forEach(btn => {
            btn.addEventListener('click', function() {
                const formRunId = this.dataset.formRunId;
                const formNombre = this.dataset.formNombre;
                
                console.log('Viendo formulario completado:', {formRunId, formNombre});
                
                abrirModalFormularioCompletado(formRunId, formNombre);
            });
        });

        // Event listener para ver formulario como PDF
        document.querySelectorAll('.ver-formulario-pdf').forEach(btn => {
            btn.addEventListener('click', function() {
                const formRunId = this.dataset.formRunId;
                const templateId = this.dataset.templateId;
                const formNombre = this.dataset.formNombre;
                
                console.log('Generando PDF de formulario:', {formRunId, templateId, formNombre});
                
                // Abrir PDF en nueva pestaña - construir URL directamente
                const pdfUrl = `/form-runs/${formRunId}/pdf/${templateId}`;
                window.open(pdfUrl, '_blank');
            });
        });

        // Event listener para guardar borrador
        document.getElementById('guardar-borrador-formulario')?.addEventListener('click', function() {
            guardarFormulario('borrador');
        });

        // Event listener para completar formulario
        document.getElementById('completar-formulario')?.addEventListener('click', function() {
            console.log(' Botón Completar Formulario presionado');
            
            // Verificar que existe el formulario
            const form = document.getElementById('dynamic-form');
            if (!form) {
                console.error(' No se encontró el formulario dynamic-form');
                mostrarNotificacion('Error: No se encontró el formulario', 'error');
                return;
            }
            
            console.log(' Formulario encontrado, procediendo con validación');
            
            if (validarFormulario()) {
                console.log(' Validación exitosa, guardando formulario como completado');
                guardarFormulario('completado');
            } else {
                console.log(' Validación falló');
            }
        });
    }

    // Función para abrir modal de formulario
    function abrirModalFormulario(etapaFormId, formId, formNombre, formRunId = null) {
        const modal = new bootstrap.Modal(document.getElementById('rellenarFormularioModal'));
        
        // Actualizar título
        document.getElementById('modal-form-nombre').textContent = formNombre;
        
        // Mostrar modal
        modal.show();
        
        // Cargar contenido del formulario
        cargarContenidoFormulario(etapaFormId, formId, formRunId);
    }

    // Función para cargar el contenido del formulario dinámico
    function cargarContenidoFormulario(etapaFormId, formId, formRunId = null) {
        const contenedor = document.getElementById('formulario-contenido');
        
        // Mostrar loading
        contenedor.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-success" role="status">
                    <span class="visually-hidden">Cargando formulario...</span>
                </div>
                <p class="mt-2 text-muted">Cargando campos del formulario...</p>
            </div>
        `;

        // Hacer petición al servidor para obtener la estructura del formulario
        let url;
        if (formRunId) {
            url = `{{ route('ejecucion.formulario.editar', ':formRunId') }}`.replace(':formRunId', formRunId);
        } else {
            url = `{{ route('ejecucion.formulario.nuevo', ':etapaFormId') }}`.replace(':etapaFormId', etapaFormId);
            // Agregar detalleFlujoId como parámetro para asociar al FormRun correcto
            if (detalleFlujoId) {
                url += `?detalle_flujo_id=${detalleFlujoId}`;
            }
        }

        fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderizarFormulario(data.formulario, data.respuestas || {});
                
                // Mostrar botones
                document.getElementById('guardar-borrador-formulario').style.display = 'inline-block';
                document.getElementById('completar-formulario').style.display = 'inline-block';
                
                // Guardar datos del formulario actual
                window.formularioActual = {
                    etapaFormId: etapaFormId,
                    formId: formId,
                    formRunId: formRunId || data.formRunId
                };
                
                // Guardar estructura del formulario para funciones auxiliares
                window.currentFormulario = data.formulario;
            } else {
                contenedor.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Error al cargar el formulario: ${data.message || 'Error desconocido'}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error al cargar formulario:', error);
            contenedor.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Error al cargar el formulario. Por favor, intente nuevamente.
                </div>
            `;
        });
    }

    // Función para renderizar el formulario dinámico
    function renderizarFormulario(formulario, respuestas = {}) {
        console.log(' Renderizando formulario con respuestas:', respuestas);
        
        const contenedor = document.getElementById('formulario-contenido');
        let html = '<form id="dynamic-form">';

        // Agrupar campos por grupos si existen
        const grupos = formulario.groups || [];
        const camposSinGrupo = formulario.fields.filter(field => !field.id_group);

        console.log('Campos sin grupo encontrados:', camposSinGrupo.length);
        console.log(' Grupos encontrados:', grupos.length);

        // Renderizar grupos
        grupos.forEach(grupo => {
            const camposGrupo = formulario.fields.filter(field => field.id_group === grupo.id);
            
            if (grupo.repetible) {
                // Grupo repetible
                html += `
                    <div class="card mb-3">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">${grupo.nombre}</h6>
                                ${grupo.descripcion ? `<small class="text-muted">${grupo.descripcion}</small>` : ''}
                            </div>
                            <button type="button" class="btn btn-sm btn-primary" onclick="addRow('${grupo.codigo}')">
                                <i class="fas fa-plus"></i> Agregar fila
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="grupo-${grupo.codigo}">
                                    <thead>
                                        <tr>
                `;

                // Cabeceras de la tabla
                camposGrupo.forEach(field => {
                    html += `<th>${field.etiqueta}</th>`;
                });
                html += `<th width="50">Acción</th></tr></thead><tbody id="tbody-${grupo.codigo}">`;

                // Obtener respuestas del grupo
                const respuestasGrupo = respuestas.groups && respuestas.groups[grupo.codigo] 
                    ? respuestas.groups[grupo.codigo] : [{}];

                // Renderizar filas existentes
                respuestasGrupo.forEach((fila, index) => {
                    html += `<tr data-row="${index}">`;
                    camposGrupo.forEach(field => {
                        const valor = fila[field.codigo] || '';
                        html += `<td>${renderizarCampo(field, valor, true, grupo.codigo, index)}</td>`;
                    });
                    html += `
                        <td>
                            <button type="button" class="btn btn-sm btn-danger" onclick="removeRow(this)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>`;
                });

                html += `</tbody></table></div></div></div>`;

            } else {
                // Grupo normal (no repetible)
                html += `
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">${grupo.nombre}</h6>
                            ${grupo.descripcion ? `<small class="text-muted">${grupo.descripcion}</small>` : ''}
                        </div>
                        <div class="card-body">
                            <div class="row">
                `;

                camposGrupo.forEach(field => {
                    // Usar field.codigo para buscar la respuesta (que viene del controlador como {field_codigo: valor})
                    const valor = respuestas[field.codigo] || '';
                    html += renderizarCampo(field, valor);
                });

                html += '</div></div></div>';
            }
        });

        // Renderizar campos sin grupo
        if (camposSinGrupo.length > 0) {
            html += '<div class="card mb-3"><div class="card-body"><div class="row">';
            camposSinGrupo.forEach(field => {
                // Usar field.codigo para buscar la respuesta (que viene del controlador como {field_codigo: valor})
                const valor = respuestas[field.codigo] || '';
                console.log(` Campo ${field.codigo}: valor="${valor}" (etiqueta: ${field.etiqueta})`);
                html += renderizarCampo(field, valor);
            });
            html += '</div></div></div>';
        }

        html += '</form>';
        contenedor.innerHTML = html;

        // Inicializar funcionalidades
        wireRealtimeCalc();
        wireRowButtons();
        wireOnSelectAutofill();
    }

    // Función para renderizar un campo individual
    function renderizarCampo(field, valor, isInGroup = false, groupName = '', rowIndex = 0) {
        const required = field.requerido ? 'required' : '';
        const readonly = field.kind === 'output' ? 'readonly' : '';
        const disabled = field.kind === 'output' ? 'disabled' : '';
        const colClass = field.ancho ? `col-md-${field.ancho}` : 'col-md-3';
        
        // Construir el name del campo - usar field.codigo como espera el controlador
        let fieldName;
        if (isInGroup) {
            fieldName = `groups[${groupName}][${rowIndex}][${field.codigo}]`;
        } else {
            fieldName = `fields[${field.codigo}]`;
        }
        
        // Atributos data para fórmulas
        let dataAttrs = `data-field-code="${field.codigo}"`;
        if (field.kind === 'output' && field.formula && field.formula.expression) {
            dataAttrs += ` data-expression="${field.formula.expression}" data-output-type="${field.formula.output_type || 'decimal'}"`;
        }
        
        let html = `<div class="${colClass} mb-3">`;
        html += `<label class="form-label">${field.etiqueta}`;
        if (field.requerido) html += ' <span class="text-danger">*</span>';
        if (field.kind === 'output' && field.formula) {
            html += ' <span class="badge bg-info ms-1">calc</span>';
        }
        html += '</label>';

        if (field.descripcion) {
            html += `<div class="text-muted small mb-2">${field.descripcion}</div>`;
        }

        // Verificar si es un campo de output (calculado)
        if (field.kind === 'output') {
            // Campo de output se renderiza como un div no editable
            html += `<div class="form-control bg-light" style="min-height: 38px; padding: 0.375rem 0.75rem; border: 1px solid #ced4da;" 
                     ${dataAttrs} 
                     id="output-${field.codigo}">
                        <span class="text-muted">${valor || '(Se calculará automáticamente)'}</span>
                     </div>`;
            // Agregar input hidden para enviar el valor
            html += `<input type="hidden" name="${fieldName}" value="${valor || ''}" ${dataAttrs}>`;
        } else {
            // Campo normal de input
            switch (field.datatype) {
            case 'textarea':
                html += `<textarea class="form-control" name="${fieldName}" ${dataAttrs} ${readonly} ${required}>${valor || ''}</textarea>`;
                break;
                
            case 'int':
                html += `<input type="number" step="1" class="form-control" name="${fieldName}" value="${valor || ''}" ${dataAttrs} ${readonly} ${required}>`;
                break;
                
            case 'decimal':
                html += `<input type="number" step="0.000001" class="form-control" name="${fieldName}" value="${valor || ''}" ${dataAttrs} ${readonly} ${required}>`;
                break;
                
            case 'date':
                html += `<input type="date" class="form-control" name="${fieldName}" value="${valor || ''}" ${dataAttrs} ${readonly} ${required}>`;
                break;
                
            case 'datetime':
                html += `<input type="datetime-local" class="form-control" name="${fieldName}" value="${valor || ''}" ${dataAttrs} ${readonly} ${required}>`;
                break;
                
            case 'boolean':
                const selectedNo = (!valor || valor === '0') ? 'selected' : '';
                const selectedYes = (valor === '1' || valor === true) ? 'selected' : '';
                html += `<select class="form-select" name="${fieldName}" ${dataAttrs} ${disabled}>
                          <option value="0" ${selectedNo}>No</option>
                          <option value="1" ${selectedYes}>Sí</option>
                        </select>`;
                break;
                
            case 'select':
            case 'multiselect':
            case 'fk':
                const isMulti = field.datatype === 'multiselect';
                const multiple = isMulti ? 'multiple' : '';
                const bracketName = isMulti ? `${fieldName}[]` : fieldName;
                
                // Configuración para autollenado
                const config = field.config_json || {};
                const onSelect = config.on_select || null;
                const onSelectAttr = onSelect ? `data-on-select='${JSON.stringify(onSelect)}'` : '';
                
                html += `<select class="form-select" name="${bracketName}" ${dataAttrs} ${onSelectAttr} ${multiple} ${disabled}>`;
                
                if (!isMulti) {
                    html += '<option value="">Seleccione una opción</option>';
                }
                
                if (field.opciones && Array.isArray(field.opciones)) {
                    field.opciones.forEach(opcion => {
                        const selected = (valor === opcion.valor || (Array.isArray(valor) && valor.includes(opcion.valor))) ? 'selected' : '';
                        const metaAttr = opcion.meta ? `data-meta='${JSON.stringify(opcion.meta)}'` : '';
                        html += `<option value="${opcion.valor}" ${selected} ${metaAttr}>${opcion.etiqueta}</option>`;
                    });
                }
                html += '</select>';
                break;
                
            case 'file':
                html += `<input type="file" class="form-control" name="${fieldName}" ${dataAttrs} ${disabled}>`;
                if (valor) {
                    html += `<div class="mt-2"><small class="text-muted">Archivo actual: ${valor}</small></div>`;
                }
                break;
                
            // Mantener compatibilidad con tipos antiguos
            case 'texto':
            case 'email':
            case 'telefono':
                html += `<input type="${field.datatype === 'email' ? 'email' : 'text'}" 
                         class="form-control" 
                         name="${fieldName}" 
                         value="${valor || ''}" 
                         ${dataAttrs}
                         ${readonly}
                         ${required}
                         placeholder="${field.placeholder || ''}">`;
                break;
            
            case 'numero':
                html += `<input type="number" 
                         class="form-control" 
                         name="${fieldName}" 
                         value="${valor || ''}" 
                         ${dataAttrs}
                         ${readonly}
                         ${required}
                         placeholder="${field.placeholder || ''}">`;
                break;
            
            case 'fecha':
                html += `<input type="date" 
                         class="form-control" 
                         name="${fieldName}" 
                         value="${valor || ''}" 
                         ${dataAttrs}
                         ${readonly}
                         ${required}>`;
                break;
            
            case 'checkbox':
                const checked = valor === '1' || valor === true ? 'checked' : '';
                html += `<div class="form-check">
                          <input class="form-check-input" 
                                 type="checkbox" 
                                 name="${fieldName}" 
                                 value="1" 
                                 ${checked} 
                                 ${disabled}
                                 ${dataAttrs}
                                 ${required}>
                          <label class="form-check-label">${field.placeholder || 'Sí'}</label>
                        </div>`;
                break;
                
            default: // text
                html += `<input type="text" 
                         class="form-control" 
                         name="${fieldName}" 
                         value="${valor || ''}" 
                         ${dataAttrs}
                         ${readonly}
                         ${required}>`;
        }
        } // Cierre del bloque else para campos no-output

        html += '</div>';
        return html;
    }

    // Función para validar el formulario
    function validarFormulario() {
        console.log(' Iniciando validación del formulario');
        
        const form = document.getElementById('dynamic-form');
        if (!form) {
            console.error(' No se encontró el formulario para validar');
            return false;
        }

        const elementos = form.querySelectorAll('[required]');
        console.log(` Elementos requeridos encontrados: ${elementos.length}`);
        
        let valido = true;

        elementos.forEach((elemento, index) => {
            const valor = elemento.value?.trim() || '';
            console.log(`Campo ${index + 1}: ${elemento.name || elemento.id} = "${valor}" (requerido: ${elemento.required})`);
            
            if (!valor) {
                elemento.classList.add('is-invalid');
                valido = false;
                console.log(` Campo ${elemento.name || elemento.id} está vacío`);
            } else {
                elemento.classList.remove('is-invalid');
                console.log(` Campo ${elemento.name || elemento.id} validado`);
            }
        });

        if (!valido) {
            console.log(' Validación falló - campos requeridos vacíos');
            mostrarNotificacion('Por favor, complete todos los campos requeridos', 'warning');
        } else {
            console.log(' Validación exitosa - todos los campos completos');
        }

        return valido;
    }

    // Función para guardar el formulario
    function guardarFormulario(estado) {
        console.log(` Iniciando guardado del formulario con estado: ${estado}`);
        
        const form = document.getElementById('dynamic-form');
        if (!form) {
            console.error(' No se encontró el formulario para guardar');
            mostrarNotificacion('Error: No se encontró el formulario', 'error');
            return;
        }

        // Verificar que window.formularioActual existe
        if (!window.formularioActual) {
            console.error(' No se encontró window.formularioActual');
            mostrarNotificacion('Error: Datos del formulario no encontrados', 'error');
            return;
        }

        console.log(' Datos del formulario actual:', window.formularioActual);

        const formData = new FormData(form);
        const data = {
            estado: estado,
            etapa_form_id: window.formularioActual.etapaFormId,
            form_run_id: window.formularioActual.formRunId,
            detalle_flujo_id: detalleFlujoId, // Agregar contexto de ejecución de flujo
            respuestas: {}
        };

        console.log(' Recopilando respuestas del formulario...');

        // Recopilar respuestas
        for (let [key, value] of formData.entries()) {
            if (key.startsWith('field_')) {
                const fieldId = key.replace('field_', '');
                data.respuestas[fieldId] = value;
                console.log(`Campo ${fieldId}: ${value}`);
            }
        }

        // También incluir checkboxes no marcados
        form.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
            if (!checkbox.checked && checkbox.name.startsWith('field_')) {
                const fieldId = checkbox.name.replace('field_', '');
                data.respuestas[fieldId] = '0';
                console.log(`Checkbox no marcado ${fieldId}: 0`);
            }
        });

        console.log(' Datos a enviar:', data);

        // Enviar al servidor
        fetch(`{{ route('ejecucion.formulario.guardar') }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(data)
        })
        .then(response => {
            console.log('📡 Respuesta del servidor:', response.status, response.statusText);
            return response.json();
        })
        .then(data => {
            console.log('📥 Datos de respuesta:', data);
            if (data.success) {
                mostrarNotificacion(
                    estado === 'completado' ? 
                    'Formulario completado exitosamente' : 
                    'Borrador guardado exitosamente', 
                    'success'
                );
                
                // Cerrar modal
                bootstrap.Modal.getInstance(document.getElementById('rellenarFormularioModal')).hide();
                
                // Si el formulario se completó, actualizar la vista dinámicamente E INTEGRAR CON CAMBIOS PENDIENTES
                if (estado === 'completado' && data.formRunId) {
                    console.log(' Actualizando vista del formulario completado');
                    console.log('🔍 Datos recibidos del servidor:', data);
                    
                    // Usar etapaFormId del servidor si está disponible, sino usar el de window.formularioActual
                    const etapaFormId = data.etapaFormId || window.formularioActual.etapaFormId;
                    console.log('🎯 Usando etapaFormId:', etapaFormId);
                    
                    // NUEVA LÓGICA: Agregar al sistema de cambios pendientes
                    const etapaId = obtenerEtapaIdDesdeEtapaFormId(etapaFormId);
                    if (etapaId) {
                        console.log('🔄 Agregando formulario a cambios pendientes de etapa:', etapaId);
                        agregarCambioPendienteEtapa(etapaId, 'formulario', data.formRunId, 'completado');
                        
                        // Actualizar vista para mostrar cambio pendiente
                        actualizarVistaFormularioCompletado(etapaFormId, data.formRunId, true); // true = cambio pendiente
                        
                        // Expandir la etapa para que el usuario vea los cambios
                        expandirEtapa(etapaId);
                        
                        mostrarNotificacion('Formulario completado. Presiona "Grabar Cambios de esta Etapa" para confirmar.', 'warning');
                    } else {
                        console.warn('No se pudo determinar el etapaId para integrar con cambios pendientes');
                        // Fallback al comportamiento anterior
                        actualizarVistaFormularioCompletado(etapaFormId, data.formRunId);
                    }
                } else {
                    // Solo recargar para borradores
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                }
                
            } else {
                console.error(' Error en respuesta del servidor:', data);
                mostrarNotificacion(`Error al guardar: ${data.message || 'Error desconocido'}`, 'error');
            }
        })
        .catch(error => {
            console.error(' Error al guardar formulario:', error);
            mostrarNotificacion('Error al guardar el formulario', 'error');
        });
    }

    // Función para abrir modal de formulario completado
    function abrirModalFormularioCompletado(formRunId, formNombre) {
        const modal = new bootstrap.Modal(document.getElementById('verFormularioCompletadoModal'));
        
        // Actualizar título
        document.getElementById('modal-form-completado-nombre').textContent = formNombre;
        
        // Mostrar modal
        modal.show();
        
        // Cargar contenido del formulario completado
        cargarFormularioCompletado(formRunId);
    }

    // Función para cargar formulario completado
    function cargarFormularioCompletado(formRunId) {
        const contenedor = document.getElementById('formulario-completado-contenido');
        
        // Mostrar loading
        contenedor.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-info" role="status">
                    <span class="visually-hidden">Cargando formulario...</span>
                </div>
                <p class="mt-2 text-muted">Cargando formulario completado...</p>
            </div>
        `;

        fetch(`{{ route('ejecucion.formulario.ver', ':formRunId') }}`.replace(':formRunId', formRunId))
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderizarFormularioCompletado(data.formulario, data.respuestas);
                
                // Mostrar botón de imprimir si es necesario
                document.getElementById('imprimir-formulario').style.display = 'inline-block';
            } else {
                contenedor.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Error al cargar el formulario: ${data.message || 'Error desconocido'}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error al cargar formulario completado:', error);
            contenedor.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Error al cargar el formulario. Por favor, intente nuevamente.
                </div>
            `;
        });
    }

    // Función para renderizar formulario completado (solo lectura)
    function renderizarFormularioCompletado(formulario, respuestas) {
        console.log('📖 Renderizando formulario completado:', formulario);
        console.log('📝 Respuestas recibidas:', respuestas);
        
        const contenedor = document.getElementById('formulario-completado-contenido');
        let html = '<div class="form-readonly">';

        // Información del formulario
        html += `
            <div class="alert alert-success mb-4">
                <div class="d-flex align-items-center">
                    <i class="fas fa-check-circle fa-2x me-3"></i>
                    <div>
                        <h6 class="mb-1">Formulario Completado</h6>
                        <small>Este formulario ha sido completado exitosamente y sus respuestas están guardadas.</small>
                    </div>
                </div>
            </div>
        `;

        // Renderizar grupos y campos (solo lectura)
        const grupos = formulario.groups || [];
        const camposSinGrupo = formulario.fields.filter(field => !field.id_group);

        grupos.forEach(grupo => {
            html += `
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">${grupo.nombre}</h6>
                        ${grupo.descripcion ? `<small class="text-muted">${grupo.descripcion}</small>` : ''}
                    </div>
                    <div class="card-body">
            `;

            const camposGrupo = formulario.fields.filter(field => field.id_group === grupo.id);
            camposGrupo.forEach(field => {
                // Usar field.codigo para buscar la respuesta (consistente con el controlador)
                const valor = respuestas[field.codigo] || '';
                console.log(`📖 Campo completado ${field.codigo}: valor="${valor}" (etiqueta: ${field.etiqueta})`);
                html += renderizarCampoSoloLectura(field, valor);
            });

            html += '</div></div>';
        });

        if (camposSinGrupo.length > 0) {
            html += '<div class="row">';
            camposSinGrupo.forEach(field => {
                // Usar field.codigo para buscar la respuesta (consistente con el controlador)
                const valor = respuestas[field.codigo] || '';
                console.log(`📖 Campo sin grupo completado ${field.codigo}: valor="${valor}" (etiqueta: ${field.etiqueta})`);
                html += renderizarCampoSoloLectura(field, valor);
            });
            html += '</div>';
        }

        html += '</div>';
        contenedor.innerHTML = html;
    }

    // Función para renderizar campo en modo solo lectura
    function renderizarCampoSoloLectura(field, valor) {
        const colClass = field.ancho ? `col-md-${field.ancho}` : 'col-md-6';
        
        let html = `<div class="${colClass} mb-3">`;
        html += `<label class="form-label fw-bold">${field.etiqueta}</label>`;
        
        let valorMostrar = valor || '<em class="text-muted">Sin respuesta</em>';
        
        if (field.datatype === 'checkbox') {
            valorMostrar = (valor === '1' || valor === true) ? 
                '<span class="badge bg-success">Sí</span>' : 
                '<span class="badge bg-secondary">No</span>';
        } else if (field.datatype === 'select' && field.opciones) {
            const opcionSeleccionada = field.opciones.find(opt => opt.valor === valor);
            valorMostrar = opcionSeleccionada ? opcionSeleccionada.etiqueta : valorMostrar;
        }
        
        html += `<div class="form-control-plaintext border rounded p-2 bg-light">${valorMostrar}</div>`;
        
        html += '</div>';
        return html;
    }

    // ============================================
    // FUNCIONES PARA GRUPOS REPETIBLES
    // ============================================

    // Función para agregar una fila a un grupo repetible
    function addRow(groupCode) {
        const tbody = document.getElementById(`tbody-${groupCode}`);
        const rows = tbody.querySelectorAll('tr');
        const newIndex = rows.length;
        
        // Obtener campos del grupo
        const formulario = window.currentFormulario;
        const grupo = formulario.groups.find(g => g.codigo === groupCode);
        const camposGrupo = formulario.fields.filter(field => field.id_group === grupo.id);
        
        let html = `<tr data-row="${newIndex}">`;
        camposGrupo.forEach(field => {
            html += `<td>${renderizarCampo(field, '', true, groupCode, newIndex)}</td>`;
        });
        html += `
            <td>
                <button type="button" class="btn btn-sm btn-danger" onclick="removeRow(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>`;
        
        tbody.insertAdjacentHTML('beforeend', html);
        renumberRows(groupCode);
        
        // Re-inicializar funcionalidades para la nueva fila
        wireRealtimeCalc();
        wireOnSelectAutofill();
    }

    // Función para eliminar una fila
    function removeRow(button) {
        const tr = button.closest('tr');
        const tbody = tr.closest('tbody');
        const groupCode = tbody.id.replace('tbody-', '');
        
        tr.remove();
        renumberRows(groupCode);
    }

    // Función para renumerar las filas después de agregar/eliminar
    function renumberRows(groupCode) {
        const tbody = document.getElementById(`tbody-${groupCode}`);
        const rows = tbody.querySelectorAll('tr');
        
        rows.forEach((row, index) => {
            row.setAttribute('data-row', index);
            
            // Actualizar nombres de inputs en la fila
            const inputs = row.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                const name = input.getAttribute('name');
                if (name && name.includes('groups[')) {
                    const newName = name.replace(/\[(\d+)\]/, `[${index}]`);
                    input.setAttribute('name', newName);
                }
            });
        });
    }

    // ============================================
    // FUNCIONES PARA CÁLCULOS EN TIEMPO REAL
    // ============================================

    // Función para inicializar cálculos en tiempo real
    function wireRealtimeCalc() {
        console.log('🔢 Inicializando cálculos en tiempo real...');
        
        // Buscar todos los campos con fórmulas (tanto DIVs como inputs)
        const formulaFields = document.querySelectorAll('[data-expression]');
        console.log(`🎯 Encontrados ${formulaFields.length} campos con fórmulas`);
        
        formulaFields.forEach(field => {
            const expression = field.getAttribute('data-expression');
            const outputType = field.getAttribute('data-output-type') || 'decimal';
            const fieldCode = field.getAttribute('data-field-code');
            
            console.log(`📊 Configurando campo output: ${fieldCode}, expresión: ${expression}`);
            
            // Encontrar campos relacionados en la expresión
            const relatedFields = findRelatedFields(expression);
            console.log(`🔗 Campos relacionados para ${fieldCode}:`, relatedFields);
            
            // Agregar listeners a campos relacionados
            relatedFields.forEach(relatedFieldCode => {
                // Buscar inputs que contengan el código del campo en su name
                const possibleSelectors = [
                    `[name="fields[${relatedFieldCode}]"]`,
                    `[name*="[${relatedFieldCode}]"]`,
                    `[data-field-code="${relatedFieldCode}"]`
                ];
                
                let foundInputs = [];
                possibleSelectors.forEach(selector => {
                    const inputs = document.querySelectorAll(selector);
                    inputs.forEach(input => {
                        if (!foundInputs.includes(input)) {
                            foundInputs.push(input);
                        }
                    });
                });
                
                console.log(`🎯 Para campo ${relatedFieldCode} encontrados ${foundInputs.length} inputs`);
                
                foundInputs.forEach(input => {
                    console.log(`⚡ Agregando listeners a input:`, input.name || input.getAttribute('data-field-code'));
                    
                    // Remover listeners existentes para evitar duplicados
                    input.removeEventListener('input', input._formulaHandler);
                    input.removeEventListener('change', input._formulaHandler);
                    
                    // Crear handler específico para este input
                    input._formulaHandler = () => {
                        console.log(`🔄 Input ${relatedFieldCode} cambió, recalculando ${fieldCode}`);
                        calculateFormula(field, expression, outputType);
                    };
                    
                    input.addEventListener('input', input._formulaHandler);
                    input.addEventListener('change', input._formulaHandler);
                });
            });
            
            // Calcular valor inicial
            console.log(` Calculando valor inicial para ${fieldCode}`);
            calculateFormula(field, expression, outputType);
        });
        
        console.log(' Cálculos en tiempo real configurados');
    }

    // Función para encontrar campos relacionados en una expresión
    function findRelatedFields(expression) {
        // Verificar que la expresión no sea undefined, null o vacía
        if (!expression || expression === 'undefined' || expression === 'null') {
            console.log('⚠️ Expresión inválida, retornando array vacío');
            return [];
        }
        
        const normalizedExpression = expression.replace(/\{\{([^}]+)\}\}/g, '[$1]');
        
        const fieldPattern = /\[([^\]]+)\]/g;
        const fields = [];
        let match;
        
        while ((match = fieldPattern.exec(normalizedExpression)) !== null) {
            fields.push(match[1]);
        }
        
        return [...new Set(fields)]; // Eliminar duplicados
    }

    // Función para calcular una fórmula
    function calculateFormula(outputField, expression, outputType) {
        console.log(`🧮 === CALCULANDO FÓRMULA ===`);
        console.log(`📝 Expresión original: ${expression}`);
        console.log(`🎯 Campo output:`, outputField);
        console.log(`📊 Tipo output: ${outputType}`);
        
        // Verificar que la expresión sea válida
        if (!expression || expression === 'undefined' || expression === 'null') {
            console.log('⚠️ Expresión inválida, estableciendo valor 0');
            if (outputField.tagName === 'DIV') {
                outputField.innerHTML = '<span class="text-muted">0</span>';
                const fieldCode = outputField.getAttribute('data-field-code');
                const hiddenInput = outputField.parentNode.querySelector(`input[type="hidden"][data-field-code="${fieldCode}"]`);
                if (hiddenInput) {
                    hiddenInput.value = '0';
                }
            } else if (outputField.type === 'hidden') {
                // Es un input hidden de output, actualizar el DIV visual correspondiente
                const fieldCode = outputField.getAttribute('data-field-code');
                const visualDiv = document.getElementById(`output-${fieldCode}`);
                
                if (visualDiv) {
                    visualDiv.innerHTML = '<span class="text-muted">0</span>';
                }
                
                // Actualizar el input hidden también
                outputField.value = '0';
            } else {
                outputField.value = '0';
            }
            return;
        }
        
        try {
            
            let normalizedExpression = expression.replace(/\{\{([^}]+)\}\}/g, '[$1]');
            console.log(`🔄 Expresión normalizada: ${normalizedExpression}`);
            
            // Reemplazar códigos de campo con valores
            let processedExpression = normalizedExpression;
            
            console.log(`🔍 Buscando campos en la expresión...`);
            
            // Usar replace con función callback en lugar de exec para evitar problemas de bucle infinito
            processedExpression = processedExpression.replace(/\[([^\]]+)\]/g, (fullMatch, fieldCode) => {
                console.log(`🎯 Procesando campo: ${fieldCode}`);
                
                // Buscar el valor del campo
                let value = getFieldValue(fieldCode, outputField);
                
                // Convertir a número si es necesario
                if (value === '' || value === null || value === undefined) {
                    value = 0;
                } else {
                    value = parseFloat(value) || 0;
                }
                
                console.log(`🔢 Reemplazando ${fullMatch} con ${value}`);
                return value;
            });
            
            console.log(`⚙️ Expresión procesada: ${processedExpression}`);
            
            // Evaluar la expresión
            const result = evalExpression(processedExpression);
            console.log(`🎯 Resultado crudo: ${result}`);
            
            // Formatear el resultado según el tipo
            let formattedResult;
            switch (outputType) {
                case 'int':
                    formattedResult = Math.round(result);
                    break;
                case 'decimal':
                    formattedResult = parseFloat(result.toFixed(2));
                    break;
                default:
                    formattedResult = result;
            }
            
            console.log(`✨ Resultado formateado: ${formattedResult}`);
            
            // Establecer el valor en el campo de salida
            if (outputField.tagName === 'DIV') {
                // Es un div de output, actualizar el contenido visual y el input hidden
                console.log(`📺 Actualizando DIV con resultado: ${formattedResult}`);
                outputField.innerHTML = `<span class="fw-bold text-success">${formattedResult}</span>`;
                
                // Buscar el input hidden asociado
                const fieldCode = outputField.getAttribute('data-field-code');
                const hiddenInput = outputField.parentNode.querySelector(`input[type="hidden"][data-field-code="${fieldCode}"]`);
                if (hiddenInput) {
                    hiddenInput.value = formattedResult;
                    console.log(`🔒 Input hidden actualizado con: ${formattedResult}`);
                } else {
                    console.log(` No se encontró input hidden para ${fieldCode}`);
                }
            } else if (outputField.type === 'hidden') {
                // Es un input hidden de output, buscar el DIV visual correspondiente
                console.log(`🔒 Es input hidden, buscando DIV visual...`);
                const fieldCode = outputField.getAttribute('data-field-code');
                const visualDiv = document.getElementById(`output-${fieldCode}`);
                
                if (visualDiv) {
                    console.log(`📺 Actualizando DIV visual con resultado: ${formattedResult}`);
                    visualDiv.innerHTML = `<span class="fw-bold text-success">${formattedResult}</span>`;
                } else {
                    console.log(` No se encontró DIV visual para ${fieldCode}`);
                }
                
                // Actualizar el input hidden también
                console.log(`📝 Actualizando INPUT hidden con resultado: ${formattedResult}`);
                outputField.value = formattedResult;
            } else {
                // Es un input normal
                console.log(`📝 Actualizando INPUT con resultado: ${formattedResult}`);
                outputField.value = formattedResult;
            }
            
            console.log(` Cálculo completado exitosamente`);
            
        } catch (error) {
            console.error(' Error calculando fórmula:', error);
            if (outputField.tagName === 'DIV') {
                outputField.innerHTML = '<span class="text-danger">Error en cálculo</span>';
                
                // Limpiar input hidden asociado
                const fieldCode = outputField.getAttribute('data-field-code');
                const hiddenInput = outputField.parentNode.querySelector(`input[type="hidden"][data-field-code="${fieldCode}"]`);
                if (hiddenInput) {
                    hiddenInput.value = '';
                }
            } else if (outputField.type === 'hidden') {
                // Es un input hidden de output, limpiar el DIV visual correspondiente
                const fieldCode = outputField.getAttribute('data-field-code');
                const visualDiv = document.getElementById(`output-${fieldCode}`);
                
                if (visualDiv) {
                    visualDiv.innerHTML = '<span class="text-danger">Error en cálculo</span>';
                }
                
                // Limpiar el input hidden también
                outputField.value = '';
            } else {
                outputField.value = '';
            }
        }
    }

    // Función para obtener el valor de un campo
    function getFieldValue(fieldCode, contextField) {
        console.log(`🔍 Buscando valor para campo: ${fieldCode}`);
        
        // Determinar el contexto (grupo y fila si aplica)
        const fieldName = contextField.getAttribute('name');
        
        if (fieldName && fieldName.includes('groups[')) {
            // Estamos en un grupo, buscar en la misma fila
            const groupMatch = fieldName.match(/groups\[([^\]]+)\]\[(\d+)\]/);
            if (groupMatch) {
                const groupName = groupMatch[1];
                const rowIndex = groupMatch[2];
                
                const selector = `[name="groups[${groupName}][${rowIndex}][${fieldCode}]"]`;
                console.log(`🎯 Buscando en grupo con selector: ${selector}`);
                
                const input = document.querySelector(selector);
                const value = input ? input.value : 0;
                console.log(`📊 Valor encontrado para ${fieldCode} en grupo: ${value}`);
                return value;
            }
        } else {
            // Campo normal - probar múltiples selectores
            const possibleSelectors = [
                `[name="fields[${fieldCode}]"]`,
                `[data-field-code="${fieldCode}"]`,
                `input[name*="${fieldCode}"]`,
                `select[name*="${fieldCode}"]`,
                `textarea[name*="${fieldCode}"]`
            ];
            
            for (let selector of possibleSelectors) {
                console.log(`🔎 Probando selector: ${selector}`);
                const input = document.querySelector(selector);
                
                if (input) {
                    let value = input.value;
                    
                    // Manejar checkboxes
                    if (input.type === 'checkbox') {
                        value = input.checked ? 1 : 0;
                    }
                    
                    console.log(` Valor encontrado para ${fieldCode}: ${value} (usando ${selector})`);
                    return parseFloat(value) || 0;
                }
            }
        }
        
        console.log(` No se encontró valor para campo: ${fieldCode}, retornando 0`);
        return 0;
    }

    // Función segura para evaluar expresiones matemáticas
    function evalExpression(expression) {
        console.log(`🧮 Evaluando expresión: "${expression}"`);
        
        // Verificar si quedan campos sin reemplazar
        if (expression.includes('[') && expression.includes(']')) {
            throw new Error(`Expresión contiene campos sin reemplazar: ${expression}`);
        }
        
        // Lista de operadores y funciones permitidas
        const allowedChars = /^[0-9+\-*/.() ]+$/;
        
        if (!allowedChars.test(expression)) {
            console.log(` Caracteres no permitidos en: "${expression}"`);
            throw new Error(`Expresión contiene caracteres no permitidos: ${expression}`);
        }
        
        try {
            const result = Function(`"use strict"; return (${expression})`)();
            console.log(` Resultado de evaluación: ${result}`);
            return result;
        } catch (error) {
            console.log(` Error en evaluación:`, error);
            throw new Error('Error evaluando expresión: ' + error.message);
        }
    }

    // ============================================
    // FUNCIONES PARA AUTOFILL
    // ============================================

    // Función para inicializar autofill en selects
    function wireOnSelectAutofill() {
        const autofillSelects = document.querySelectorAll('[data-on-select]');
        
        autofillSelects.forEach(select => {
            select.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                if (!selectedOption || !selectedOption.value) return;
                
                // Obtener metadatos del option seleccionado
                const metaData = selectedOption.getAttribute('data-meta');
                if (!metaData) return;
                
                try {
                    const meta = JSON.parse(metaData);
                    
                    // Aplicar valores a campos relacionados
                    Object.keys(meta).forEach(fieldCode => {
                        const value = meta[fieldCode];
                        
                        // Determinar el contexto del campo actual
                        const currentName = this.getAttribute('name');
                        let targetInput;
                        
                        if (currentName && currentName.includes('groups[')) {
                            // Estamos en un grupo, buscar en la misma fila
                            const groupMatch = currentName.match(/groups\[([^\]]+)\]\[(\d+)\]/);
                            if (groupMatch) {
                                const groupName = groupMatch[1];
                                const rowIndex = groupMatch[2];
                                targetInput = document.querySelector(`[name="groups[${groupName}][${rowIndex}][${fieldCode}]"]`);
                            }
                        } else {
                            // Campo normal
                            targetInput = document.querySelector(`[name="fields[${fieldCode}]"]`);
                        }
                        
                        if (targetInput) {
                            targetInput.value = value;
                            // Disparar evento para recalcular fórmulas
                            targetInput.dispatchEvent(new Event('input'));
                        }
                    });
                    
                } catch (error) {
                    console.error('Error procesando autofill:', error);
                }
            });
        });
    }

    // Función para inicializar manejo de botones de fila
    function wireRowButtons() {
        // Esta función se llama automáticamente cuando se renderizan nuevas filas
        // Los botones se manejan directamente con onclick en el HTML
    }

    // ============================================
    // FUNCIONES PARA GUARDAR FORMULARIO
    // ============================================

    // Función para validar el formulario antes de completar
    function validarFormulario() {
        const form = document.getElementById('dynamic-form');
        if (!form) return false;
        
        let isValid = true;
        const errors = [];
        
        // Validar campos requeridos
        const requiredFields = form.querySelectorAll('[required]');
        
        requiredFields.forEach(field => {
            // Limpiar errores previos
            field.classList.remove('is-invalid');
            const errorDiv = field.parentNode.querySelector('.invalid-feedback');
            if (errorDiv) errorDiv.remove();
            
            if (!field.value.trim()) {
                isValid = false;
                field.classList.add('is-invalid');
                
                // Agregar mensaje de error
                const errorMsg = document.createElement('div');
                errorMsg.className = 'invalid-feedback';
                errorMsg.textContent = 'Este campo es requerido';
                field.parentNode.appendChild(errorMsg);
                
                // Obtener etiqueta del campo para el resumen
                const label = field.parentNode.querySelector('label');
                const fieldName = label ? label.textContent : field.name;
                errors.push(fieldName);
            }
        });
        
        // Mostrar resumen de errores si existen
        if (!isValid) {
            const errorList = errors.join(', ');
            mostrarMensaje(`Por favor complete los siguientes campos requeridos: ${errorList}`, 'warning');
            
            // Scroll al primer campo con error
            const firstErrorField = form.querySelector('.is-invalid');
            if (firstErrorField) {
                firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstErrorField.focus();
            }
        }
        
        return isValid;
    }

    // Función para guardar el formulario
    function guardarFormulario(estado) {
        console.log(`💾 Iniciando guardado del formulario con estado: ${estado}`);
        
        // Validar formulario solo si se está completando
        if (estado === 'completado' && !validarFormulario()) {
            console.log(' Validación falló, no se puede completar');
            return;
        }
        
        const form = document.getElementById('dynamic-form');
        if (!form) {
            console.error(' No se encontró el formulario para guardar');
            mostrarNotificacion('Error: No se encontró el formulario', 'error');
            return;
        }

        if (!window.formularioActual) {
            console.error(' No se encontró window.formularioActual');
            mostrarNotificacion('Error: Datos del formulario no encontrados', 'error');
            return;
        }

        console.log('📊 Datos del formulario actual:', window.formularioActual);

        const formData = new FormData(form);
        const data = {
            estado: estado,
            etapa_form_id: window.formularioActual.etapaFormId,
            form_run_id: window.formularioActual.formRunId || null,
            detalle_flujo_id: detalleFlujoId, // Agregar contexto de ejecución de flujo
            respuestas: {}
        };

        console.log('📝 Recopilando respuestas del formulario...');

        // Recopilar respuestas de campos normales
        for (let [key, value] of formData.entries()) {
            if (key.startsWith('fields[')) {
                const match = key.match(/fields\[(\d+)\]/);
                if (match) {
                    const fieldId = match[1];
                    data.respuestas[fieldId] = value;
                    console.log(`Campo normal ${fieldId}: ${value}`);
                }
            } else if (key.startsWith('groups[')) {
                const match = key.match(/groups\[([^\]]+)\]\[(\d+)\]\[(\d+)\]/);
                if (match) {
                    const grupoId = match[1];
                    const filaIndex = match[2];
                    const fieldId = match[3];
                    
                    if (!data.grupos) {
                        data.grupos = {};
                    }
                    if (!data.grupos[grupoId]) {
                        data.grupos[grupoId] = [];
                    }
                    if (!data.grupos[grupoId][filaIndex]) {
                        data.grupos[grupoId][filaIndex] = {};
                    }
                    
                    data.grupos[grupoId][filaIndex][fieldId] = value;
                    console.log(`Grupo ${grupoId}, fila ${filaIndex}, campo ${fieldId}: ${value}`);
                }
            }
        }

        // También incluir checkboxes no marcados
        form.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
            if (!checkbox.checked) {
                const name = checkbox.name;
                if (name.startsWith('fields[')) {
                    const match = name.match(/fields\[(\d+)\]/);
                    if (match) {
                        const fieldId = match[1];
                        if (!data.respuestas.hasOwnProperty(fieldId)) {
                            data.respuestas[fieldId] = '0';
                            console.log(`Checkbox no marcado ${fieldId}: 0`);
                        }
                    }
                } else if (name.startsWith('groups[')) {
                    const match = name.match(/groups\[([^\]]+)\]\[(\d+)\]\[(\d+)\]/);
                    if (match) {
                        const grupoId = match[1];
                        const filaIndex = match[2];
                        const fieldId = match[3];
                        
                        if (!data.grupos) data.grupos = {};
                        if (!data.grupos[grupoId]) data.grupos[grupoId] = [];
                        if (!data.grupos[grupoId][filaIndex]) data.grupos[grupoId][filaIndex] = {};
                        
                        if (!data.grupos[grupoId][filaIndex].hasOwnProperty(fieldId)) {
                            data.grupos[grupoId][filaIndex][fieldId] = '0';
                            console.log(`Checkbox grupo no marcado ${grupoId}[${filaIndex}][${fieldId}]: 0`);
                        }
                    }
                }
            }
        });

        console.log('📦 Datos a enviar:', data);

        // Deshabilitar botones durante el guardado
        const btnGuardar = document.getElementById('guardar-borrador-formulario');
        const btnCompletar = document.getElementById('completar-formulario');
        
        if (estado === 'borrador' && btnGuardar) {
            btnGuardar.disabled = true;
            btnGuardar.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Guardando...';
        } else if (estado === 'completado' && btnCompletar) {
            btnCompletar.disabled = true;
            btnCompletar.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Completando...';
        }

        fetch(`{{ route('ejecucion.formulario.guardar') }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            console.log(' Respuesta del servidor:', data);
            
            if (data.success) {
                // Actualizar form_run_id si es nuevo
                if (data.formRunId) {
                    window.formularioActual.formRunId = data.formRunId;
                    console.log(`📄 FormRun ID actualizado: ${data.formRunId}`);
                }
                
                if (estado === 'completado') {
                    mostrarNotificacion('Formulario completado exitosamente', 'success');
                    console.log('🎉 Formulario completado');
                    
                    // Cerrar modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('rellenarFormularioModal'));
                    if (modal) {
                        modal.hide();
                    }
                    
                    // Actualizar la vista del formulario dinámicamente
                    actualizarVistaFormularioCompletado(window.formularioActual.etapaFormId, data.formRunId);
                } else {
                    mostrarNotificacion('Borrador guardado exitosamente', 'success');
                    console.log('💾 Borrador guardado');
                }
            } else {
                console.error(' Error del servidor:', data.message);
                mostrarNotificacion(data.message || 'Error al guardar el formulario', 'error');
            }
        })
        .catch(error => {
            console.error(' Error de conexión:', error);
            mostrarNotificacion('Error de conexión al guardar el formulario', 'error');
        })
        .finally(() => {
            // Restaurar botones
            if (estado === 'borrador' && btnGuardar) {
                btnGuardar.disabled = false;
                btnGuardar.innerHTML = '<i class="fas fa-save me-2"></i>Guardar como Borrador';
            } else if (estado === 'completado' && btnCompletar) {
                btnCompletar.disabled = false;
                btnCompletar.innerHTML = '<i class="fas fa-check me-2"></i>Completar Formulario';
            }
            console.log('🔄 Botones restaurados');
        });
    }

    // Función auxiliar para mostrar mensajes
    function mostrarMensaje(mensaje, tipo) {
        // Crear y mostrar toast o alert
        const alertClass = tipo === 'error' ? 'alert-danger' : 
                          tipo === 'warning' ? 'alert-warning' : 
                          'alert-success';
        
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert ${alertClass} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${mensaje}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        // Insertar al principio del contenido del modal
        const modalBody = document.querySelector('#formulario-modal .modal-body');
        if (modalBody) {
            modalBody.insertBefore(alertDiv, modalBody.firstChild);
            
            // Auto-remove después de 5 segundos
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        }
    }

    // ============================================
    // FUNCIONES PARA GESTIÓN DE FORMULARIOS COMPLETADOS
    // ============================================

    // Función para actualizar la vista cuando un formulario se completa
    function actualizarVistaFormularioCompletado(etapaFormId, formRunId, esCambioPendiente = false) {
        console.log('🔄 Actualizando vista de formulario completado:', { etapaFormId, formRunId, esCambioPendiente });
        
        // Buscar el botón específico con data-etapa-form-id y obtener su contenedor
        const botonRellenar = document.querySelector(`button[data-etapa-form-id="${etapaFormId}"]`);
        
        if (!botonRellenar) {
            console.error(' No se encontró el botón del formulario con etapaFormId:', etapaFormId);
            // Como fallback, buscar todos los botones y ver si alguno coincide
            const todosLosBotones = document.querySelectorAll('[data-etapa-form-id]');
            console.log('🔍 Botones disponibles:', Array.from(todosLosBotones).map(b => ({
                etapaFormId: b.dataset.etapaFormId,
                formNombre: b.dataset.formNombre
            })));
            return;
        }

        const formularioContainer = botonRellenar.closest('.formulario-container');
        
        if (!formularioContainer) {
            console.error(' No se encontró el contenedor del formulario');
            return;
        }

        console.log(' Contenedor encontrado, actualizando elementos...');

        // Buscar elementos a actualizar
        const badge = formularioContainer.querySelector('.badge');
        const botones = formularioContainer.querySelector('.d-flex.gap-2');
        const nombreFormulario = formularioContainer.querySelector('strong').textContent;

        console.log('📝 Datos del formulario:', { nombreFormulario, badge: !!badge, botones: !!botones, esCambioPendiente });

        // Si es un cambio pendiente, aplicar estilos visuales especiales
        if (esCambioPendiente) {
            formularioContainer.classList.add('cambio-pendiente-formulario');
            formularioContainer.style.backgroundColor = '#fff3cd';
            formularioContainer.style.border = '2px solid #ffc107';
            formularioContainer.style.borderRadius = '0.5rem';
            
            // Actualizar badge a "Pendiente de Confirmar"
            if (badge) {
                badge.className = 'badge bg-warning text-dark ms-2';
                badge.innerHTML = '<i class="fas fa-clock"></i> Pendiente de Confirmar';
                console.log('⏳ Badge actualizado a pendiente de confirmar');
            }
            
            // Agregar mensaje informativo
            const infoDiv = document.createElement('div');
            infoDiv.className = 'alert alert-warning mt-2 mb-0 cambio-pendiente-info';
            infoDiv.innerHTML = `
                <small>
                    <i class="fas fa-info-circle me-1"></i>
                    Formulario completado. <strong>Presiona "Grabar Cambios de esta Etapa"</strong> para confirmar.
                </small>
            `;
            formularioContainer.appendChild(infoDiv);
            
        } else {
            // Comportamiento normal - formulario definitivamente completado
            formularioContainer.classList.remove('cambio-pendiente-formulario');
            formularioContainer.style.backgroundColor = '';
            formularioContainer.style.border = '';
            
            // Actualizar badge a completado
            if (badge) {
                badge.className = 'badge bg-success ms-2';
                badge.innerHTML = '<i class="fas fa-check-circle"></i> Completado';
                console.log(' Badge actualizado a completado');
            }
        }

        // Reemplazar botones según el estado
        if (botones) {
            if (esCambioPendiente) {
                // Para cambios pendientes, mantener el botón de rellenar pero deshabilitado temporalmente
                botones.innerHTML = `
                    <button type="button" class="btn btn-outline-warning btn-sm" disabled
                            title="Formulario completado, pendiente de confirmación">
                        <i class="fas fa-clock"></i> Pendiente
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm cancelar-formulario-pendiente" 
                            data-etapa-form-id="${etapaFormId}"
                            data-form-nombre="${nombreFormulario}"
                            title="Cancelar y volver a estado anterior">
                        <i class="fas fa-undo"></i> Cancelar
                    </button>
                `;
                
                // Agregar event listener al botón cancelar
                const cancelarBtn = botones.querySelector('.cancelar-formulario-pendiente');
                if (cancelarBtn) {
                    cancelarBtn.addEventListener('click', function() {
                        console.log(' Cancelando formulario pendiente:', etapaFormId);
                        cancelarFormularioPendiente(etapaFormId);
                    });
                }
                
            } else {
                // Para formularios completados definitivamente
                botones.innerHTML = `
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-success btn-sm ver-formulario-completado" 
                                data-form-run-id="${formRunId}"
                                data-form-nombre="${nombreFormulario}"
                                title="Ver formulario completado">
                            <i class="fas fa-eye"></i> Ver
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm ver-formulario-pdf-dinamico" 
                                data-form-run-id="${formRunId}"
                                data-form-nombre="${nombreFormulario}"
                                title="Descargar formulario como PDF"
                                style="display: none;">
                            <i class="fas fa-file-pdf"></i> PDF
                        </button>
                    </div>
                    <button type="button" class="btn btn-outline-danger btn-sm borrar-formulario ms-2" 
                            data-form-run-id="${formRunId}"
                            data-etapa-form-id="${etapaFormId}"
                            data-form-nombre="${nombreFormulario}"
                            title="Borrar formulario y volver al estado anterior">
                        <i class="fas fa-trash"></i> Borrar
                    </button>
                `;
                
                // Agregar event listeners a los nuevos botones
                const verBtn = botones.querySelector('.ver-formulario-completado');
                const pdfBtn = botones.querySelector('.ver-formulario-pdf-dinamico');
                const borrarBtn = botones.querySelector('.borrar-formulario');
                
                // Verificar si existe plantilla PDF para este formulario
                verificarPlantillaPDF(formRunId, pdfBtn);
                
                if (verBtn) {
                    verBtn.addEventListener('click', function() {
                        console.log('👁️ Abriendo formulario completado:', formRunId);
                        abrirFormularioCompletado(formRunId, nombreFormulario);
                    });
                    console.log(' Event listener agregado al botón Ver');
                }
                
                if (borrarBtn) {
                    borrarBtn.addEventListener('click', function() {
                        console.log('🗑️ Confirmando borrado de formulario:', formRunId);
                        confirmarBorrarFormulario(formRunId, etapaFormId, nombreFormulario);
                    });
                    console.log(' Event listener agregado al botón Borrar');
                }
                
                if (pdfBtn) {
                    pdfBtn.addEventListener('click', function() {
                        const templateId = this.dataset.templateId;
                        console.log('📄 Generando PDF de formulario:', {formRunId, templateId, nombreFormulario});
                        
                        // Abrir PDF en nueva pestaña - construir URL directamente
                        const pdfUrl = `/form-runs/${formRunId}/pdf/${templateId}`;
                        window.open(pdfUrl, '_blank');
                    });
                    console.log(' Event listener agregado al botón PDF');
                }
            }
        }

        // Actualizar contador de formularios completados
        actualizarContadorFormulariosCompletados();
        
        console.log('🎉 Vista de formulario actualizada exitosamente');
    }

    // Función auxiliar para obtener etapaId desde etapaFormId
    function obtenerEtapaIdDesdeEtapaFormId(etapaFormId) {
        // Buscar en el DOM el contenedor que tenga el etapaFormId
        const botonFormulario = document.querySelector(`button[data-etapa-form-id="${etapaFormId}"]`);
        if (!botonFormulario) {
            console.error(' No se encontró botón con etapaFormId:', etapaFormId);
            return null;
        }

        // Buscar el contenedor de la etapa que contiene este formulario
        const etapaContainer = botonFormulario.closest('[data-etapa-id]');
        if (!etapaContainer) {
            console.error(' No se encontró contenedor de etapa para etapaFormId:', etapaFormId);
            return null;
        }

        const etapaId = etapaContainer.dataset.etapaId;
        console.log(' EtapaId encontrado:', { etapaFormId, etapaId });
        return etapaId;
    }

    // Función para cancelar un formulario pendiente
    function cancelarFormularioPendiente(etapaFormId) {
        console.log(' Cancelando formulario pendiente:', etapaFormId);
        
        const etapaId = obtenerEtapaIdDesdeEtapaFormId(etapaFormId);
        if (!etapaId) {
            console.error(' No se pudo obtener etapaId para cancelar formulario');
            return;
        }

        // Remover del objeto de cambios pendientes
        if (cambiosPendientesPorEtapa[etapaId] && cambiosPendientesPorEtapa[etapaId].formularios) {
            delete cambiosPendientesPorEtapa[etapaId].formularios[etapaFormId];
            
            // Si no quedan más cambios pendientes en esta etapa, limpiar el objeto
            const tieneCambios = (
                Object.keys(cambiosPendientesPorEtapa[etapaId].formularios || {}).length > 0 ||
                Object.keys(cambiosPendientesPorEtapa[etapaId].tareas || {}).length > 0 ||
                Object.keys(cambiosPendientesPorEtapa[etapaId].documentos || {}).length > 0
            );
            
            if (!tieneCambios) {
                delete cambiosPendientesPorEtapa[etapaId];
            }
        }

        // Restaurar el formulario a su estado original
        const botonFormulario = document.querySelector(`button[data-etapa-form-id="${etapaFormId}"]`);
        if (botonFormulario) {
            const formularioContainer = botonFormulario.closest('.formulario-container');
            if (formularioContainer) {
                // Remover estilos de cambio pendiente
                formularioContainer.classList.remove('cambio-pendiente-formulario');
                formularioContainer.style.backgroundColor = '';
                formularioContainer.style.border = '';
                
                // Remover mensaje informativo
                const infoDiv = formularioContainer.querySelector('.cambio-pendiente-info');
                if (infoDiv) {
                    infoDiv.remove();
                }
                
                // Restaurar badge a "Pendiente"
                const badge = formularioContainer.querySelector('.badge');
                if (badge) {
                    badge.className = 'badge bg-secondary ms-2';
                    badge.innerHTML = '<i class="fas fa-clock"></i> Pendiente';
                }
                
                // Restaurar botón original
                const botones = formularioContainer.querySelector('.d-flex.gap-2');
                const nombreFormulario = formularioContainer.querySelector('strong').textContent;
                if (botones) {
                    botones.innerHTML = `
                        <button type="button" class="btn btn-primary btn-sm rellenar-formulario" 
                                data-etapa-form-id="${etapaFormId}"
                                data-form-id="${botonFormulario.dataset.formId}"
                                data-form-nombre="${nombreFormulario}"
                                title="Rellenar formulario">
                            <i class="fas fa-edit"></i> Rellenar
                        </button>
                    `;
                    
                    // Re-agregar event listener
                    const nuevoBoton = botones.querySelector('.rellenar-formulario');
                    if (nuevoBoton) {
                        nuevoBoton.addEventListener('click', function() {
                            const formId = this.dataset.formId;
                            const etapaFormId = this.dataset.etapaFormId;
                            const nombreForm = this.dataset.formNombre;
                            console.log('📝 Abriendo formulario:', { formId, etapaFormId, nombreForm });
                            abrirFormulario(formId, etapaFormId, nombreForm);
                        });
                    }
                }
            }
        }

        // Actualizar contadores
        actualizarContadorFormulariosCompletados();
        
        // Actualizar botón de grabar cambios
        actualizarBotonGrabarCambios();
        
        mostrarNotificacion('Formulario cancelado', 'info', 2000);
        console.log(' Formulario pendiente cancelado exitosamente');
    }

    // Función para abrir formulario completado en modo lectura
    function abrirFormularioCompletado(formRunId, nombreFormulario) {
        console.log('Abriendo formulario completado:', { formRunId, nombreFormulario });
        
        // Mostrar indicador de carga
        mostrarNotificacion('Cargando formulario...', 'info', 2000);
        
        fetch(`{{ route('ejecucion.formulario.ver', ':formRunId') }}`.replace(':formRunId', formRunId))
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Abrir modal con formulario en modo lectura
                    abrirModalFormularioCompletado(formRunId, nombreFormulario);
                } else {
                    mostrarNotificacion(data.message || 'Error al cargar el formulario', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarNotificacion('Error de conexión al cargar el formulario', 'error');
            });
    }

    // Función para confirmar borrado de formulario
    function confirmarBorrarFormulario(formRunId, etapaFormId, nombreFormulario) {
        if (confirm(`¿Estás seguro de que deseas borrar el formulario "${nombreFormulario}"?\n\nEsta acción no se puede deshacer y el formulario volverá al estado pendiente.`)) {
            borrarFormulario(formRunId, etapaFormId, nombreFormulario);
        }
    }

    // Función para borrar formulario
    function borrarFormulario(formRunId, etapaFormId, nombreFormulario) {
        console.log('Borrando formulario:', { formRunId, etapaFormId, nombreFormulario });
        
        // Mostrar indicador de carga
        mostrarNotificacion('Borrando formulario...', 'warning', 0);
        
        fetch(`{{ route('ejecucion.formulario.borrar', ':formRunId') }}`.replace(':formRunId', formRunId), {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarNotificacion('Formulario borrado exitosamente', 'success');
                
                // Actualizar la vista para mostrar el formulario como pendiente
                actualizarVistaFormularioPendiente(etapaFormId, nombreFormulario);
            } else {
                mostrarNotificacion(data.message || 'Error al borrar el formulario', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarNotificacion('Error de conexión al borrar el formulario', 'error');
        });
    }

    // Función para actualizar vista a formulario pendiente
    function actualizarVistaFormularioPendiente(etapaFormId, nombreFormulario) {
        console.log('Actualizando vista a formulario pendiente:', { etapaFormId, nombreFormulario });
        
        // Buscar el contenedor del formulario en la vista por el botón de borrar
        const borrarBtn = document.querySelector(`[data-etapa-form-id="${etapaFormId}"]`);
        const formularioContainer = borrarBtn ? borrarBtn.closest('.formulario-container') : null;
        
        if (!formularioContainer) {
            console.error('No se encontró el contenedor del formulario');
            return;
        }

        // Buscar elementos a actualizar
        const badge = formularioContainer.querySelector('.badge');
        const botones = formularioContainer.querySelector('.d-flex.gap-2');

        // Actualizar badge a pendiente
        if (badge) {
            badge.className = 'badge bg-secondary ms-2';
            badge.innerHTML = '<i class="fas fa-minus-circle"></i> Pendiente';
        }

        // Reemplazar botones con el botón de rellenar
        if (botones) {
            botones.innerHTML = `
                <button type="button" class="btn btn-success btn-sm rellenar-formulario" 
                        data-etapa-form-id="${etapaFormId}"
                        data-form-nombre="${nombreFormulario}"
                        data-form-run-id=""
                        title="Rellenar formulario">
                    <i class="fas fa-edit"></i> Rellenar
                </button>
            `;
            
            // Agregar event listener al nuevo botón
            const rellenarBtn = botones.querySelector('.rellenar-formulario');
            if (rellenarBtn) {
                rellenarBtn.addEventListener('click', function() {
                    const etapaFormId = this.getAttribute('data-etapa-form-id');
                    const formNombre = this.getAttribute('data-form-nombre');
                    abrirModalFormulario(etapaFormId, null, formNombre);
                });
            }
        }

        // Actualizar contador de formularios completados
        actualizarContadorFormulariosCompletados();
        
        console.log('Vista actualizada a formulario pendiente');
    }

    // Función para actualizar contador de formularios completados
    function actualizarContadorFormulariosCompletados() {
        // Contar formularios completados en todas las etapas
        const formulariosCompletados = document.querySelectorAll('.badge.bg-success').length;
        const totalFormularios = document.querySelectorAll('.formulario-container').length;
        
        // Actualizar contadores
        document.querySelectorAll('.formularios-completados').forEach(span => {
            span.textContent = formulariosCompletados;
        });
        
        document.querySelectorAll('.total-formularios').forEach(span => {
            span.textContent = totalFormularios;
        });
    }

    // Función para verificar si existe plantilla PDF para un formulario
    async function verificarPlantillaPDF(formRunId, pdfBtn) {
        try {
            const response = await fetch(`/ejecucion/formulario/verificar-plantilla-pdf/${formRunId}`, {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();
            
            if (data.success && data.template_id) {
                // Mostrar el botón PDF
                pdfBtn.style.display = 'block';
                pdfBtn.dataset.templateId = data.template_id;
                console.log(' Plantilla PDF encontrada para FormRun:', formRunId, 'Template:', data.template_id);
            } else {
                // Ocultar el botón PDF
                pdfBtn.style.display = 'none';
                console.log('ℹ️ No hay plantilla PDF para FormRun:', formRunId);
            }
        } catch (error) {
            console.error(' Error verificando plantilla PDF:', error);
            pdfBtn.style.display = 'none';
        }
    }
});
</script>
@endpush
