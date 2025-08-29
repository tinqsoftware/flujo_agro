@extends('layouts.dashboard')
@section('title','Ejecución de Flujos')
@section('page-title','Ejecución de Flujos')
@section('page-subtitle','Consulta los flujos disponibles y selecciona uno para ejecutar')

@section('content-area')

@if($isSuper)
    <!-- Aviso para SUPERADMIN eliminado -->
@endif


<!-- Sección de Selección de Flujo -->
<div class="selection-section mb-4">
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h5 class="mb-2">
                        <i class="fas fa-{{ $isSuper ? 'eye' : 'play-circle' }} text-{{ $isSuper ? 'info' : 'success' }} me-2"></i>
                        {{ $isSuper ? 'Supervisar Flujos' : 'Ejecutar Flujo' }}
                    </h5>
                    <p class="text-muted mb-0">
                        @if($isSuper)
                            Supervisa los flujos de todas las empresas - Solo visualización
                        @else
                            Utiliza el dropdown para seleccionar un flujo y crear una nueva ejecución. Cada vez que ejecutes se creará una instancia independiente.
                        @endif
                    </p>
                </div>
                <div class="col-md-4">
                    <div class="d-grid">
                        <select id="flujo-selector" class="form-select mb-3">
                            <option value="">Selecciona un flujo...</option>
                            @foreach($flujos as $flujo)
                                @php
                                    $etapasCount = $flujo->etapas->count();
                                    $totalEtapas = $flujo->total_etapas ?? $etapasCount;
                                @endphp
                                @if($etapasCount > 0)
                                    <option value="{{ $flujo->id }}" data-nombre="{{ $flujo->nombre }}" data-etapas="{{ $totalEtapas }}">
                                        {{ $flujo->nombre }} ({{ $totalEtapas }} etapas)
                                    </option>
                                @endif
                            @endforeach
                        </select>
                        <div class="row g-2">
                            <!-- Botón oculto pero funcional para el JavaScript -->
                            <button id="ver-detalle-btn" class="btn btn-outline-info w-100 d-none" disabled>
                                <i class="fas fa-eye me-2"></i>Ver Estado
                            </button>
                            
                            @if(!$isSuper)
                            <div class="col-12">
                                <button id="ejecutar-btn" class="btn btn-success w-100" disabled>
                                    <i class="fas fa-cog me-2"></i>Configurar y Ejecutar
                                </button>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if(!$isSuper)
<!-- Información sobre ejecuciones múltiples -->
<div class="alert alert-success border-0 shadow-sm mb-4">
    <div class="d-flex align-items-start">
        <div class="flex-shrink-0">
            <i class="fas fa-info-circle fa-lg mt-1"></i>
        </div>
        
    </div>
</div>
@endif

<!-- Sección de Ejecuciones Activas -->
@php
    $ejecucionesEnProceso = $ejecucionesActivas->where('estado', 2);
    $ejecucionesPausadas = $ejecucionesActivas->where('estado', 4);
    $ejecucionesCanceladas = $ejecucionesActivas->where('estado', 99);
    $ejecucionesTerminadas = $ejecucionesActivas->where('estado', 3);
@endphp

@if($ejecucionesEnProceso->count() > 0)
    <div class="mt-5">
        <div class="d-flex align-items-center mb-4">
            <h4 class="mb-0">
                <i class="fas fa-play-circle text-warning me-2"></i>
                Ejecuciones en Proceso
            </h4>
            <span class="badge bg-warning ms-2">{{ $ejecucionesEnProceso->count() }}</span>
        </div>

        <div class="row g-4">
            @foreach($ejecucionesEnProceso as $detalleEjecucion)
                @if($detalleEjecucion->flujo)
                <div class="col-12 col-lg-6 col-xl-4">
                    <div class="card h-100 shadow-sm border-warning">
                        <div class="card-body p-4">
                            <!-- Header del flujo -->
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="flex-grow-1">
                                    <h5 class="card-title mb-1 text-primary fw-bold">{{ $detalleEjecucion->nombre ?? $detalleEjecucion->flujo->nombre }}</h5>
                                    <div class="text-muted small">
                                        <span class="badge bg-light text-dark">{{ $detalleEjecucion->flujo->tipo->nombre ?? 'Sin tipo' }}</span>
                                        @if($isSuper)
                                            <span class="badge bg-secondary ms-1">{{ $detalleEjecucion->flujo->empresa->nombre ?? 'Sin empresa' }}</span>
                                        @endif
                                        <span class="badge bg-info ms-1">Ejecución #{{ $detalleEjecucion->id }}</span>
                                    </div>
                                </div>
                                <div class="status-indicator">
                                    <span class="badge bg-warning">
                                        <i class="fas fa-play me-1"></i>En Ejecución
                                    </span>
                                </div>
                            </div>

                            <!-- Descripción del flujo -->
                            @if($detalleEjecucion->flujo->descripcion)
                                <p class="text-muted small mb-3">
                                    {{ \Illuminate\Support\Str::limit($detalleEjecucion->flujo->descripcion, 120) }}
                                </p>
                            @endif

                            <!-- Contadores -->
                            <div class="row text-center mb-3">
                                <div class="col-6">
                                    <div class="p-2 bg-light rounded">
                                        <i class="fas fa-list-ol text-primary d-block mb-1"></i>
                                        <div class="fw-bold">{{ $detalleEjecucion->flujo->total_etapas }}</div>
                                        <small class="text-muted">etapas</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-2 bg-light rounded">
                                        <i class="fas fa-file-alt text-info d-block mb-1"></i>
                                        <div class="fw-bold">{{ $detalleEjecucion->flujo->total_documentos }}</div>
                                        <small class="text-muted">documentos</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Progreso -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="text-muted">Progreso</small>
                                    <small class="text-muted">En desarrollo...</small>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-warning progress-bar-animated" role="progressbar" style="width: 45%"></div>
                                </div>
                            </div>

                            <!-- Información de fecha -->
                            <div class="mb-3">
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>
                                    Iniciado: {{ $detalleEjecucion->created_at->diffForHumans() }}
                                </small>
                                <br>
                                <small class="text-muted">
                                    <i class="fas fa-user me-1"></i>
                                    Por: {{ $detalleEjecucion->userCreate->name ?? 'Usuario desconocido' }}
                                </small>
                            </div>

                            <!-- Botones de acción -->
                            <div class="text-center">
                                @if($isSuper)
                                    <!-- SUPERADMIN solo puede ver -->
                                    <a href="/ejecucion/{{ $detalleEjecucion->flujo->id }}" class="btn btn-outline-info btn-sm w-100 d-none">
                                        <i class="fas fa-eye me-2"></i>Ver Estado de Ejecución
                                    </a>
                                @else
                                    <!-- Usuarios de empresa pueden continuar, pausar y cancelar -->
                                    <div class="d-grid gap-2">
                                        <a href="/ejecucion/detalle/{{ $detalleEjecucion->id }}/ejecutar" class="btn btn-warning btn-sm">
                                            <i class="fas fa-play me-2"></i>Continuar Ejecución
                                        </a>
                                        <div class="row g-1">
                                            <div class="col-6">
                                                <button type="button" class="btn btn-outline-secondary btn-sm w-100 pausar-ejecucion" 
                                                        data-detalle-id="{{ $detalleEjecucion->id }}"
                                                        data-nombre="{{ $detalleEjecucion->nombre ?? $detalleEjecucion->flujo->nombre }}">
                                                    <i class="fas fa-pause me-1"></i>Pausar
                                                </button>
                                            </div>
                                            <div class="col-6">
                                                <button type="button" class="btn btn-outline-danger btn-sm w-100 cancelar-ejecucion" 
                                                        data-detalle-id="{{ $detalleEjecucion->id }}"
                                                        data-nombre="{{ $detalleEjecucion->nombre ?? $detalleEjecucion->flujo->nombre }}">
                                                    <i class="fas fa-times me-1"></i>Cancelar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            @endforeach
        </div>
    </div>
@endif

<!-- Sección de Ejecuciones Pausadas y Canceladas -->
@if($ejecucionesPausadas->count() > 0 || $ejecucionesCanceladas->count() > 0)
    <div class="mt-5">
        <div class="d-flex align-items-center mb-4">
            <h4 class="mb-0">
                <i class="fas fa-pause-circle text-secondary me-2"></i>
                Ejecuciones Pausadas y Canceladas
            </h4>
            <span class="badge bg-secondary ms-2">{{ $ejecucionesPausadas->count() + $ejecucionesCanceladas->count() }}</span>
        </div>

        <div class="row g-4">
            @foreach($ejecucionesPausadas as $detalleEjecucion)
                @if($detalleEjecucion->flujo)
                <div class="col-12 col-lg-6 col-xl-4">
                    <div class="card h-100 shadow-sm border-secondary">
                        <div class="card-body p-4">
                            <!-- Header del flujo -->
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="flex-grow-1">
                                    <h5 class="card-title mb-1 text-primary fw-bold">{{ $detalleEjecucion->nombre ?? $detalleEjecucion->flujo->nombre }}</h5>
                                    <div class="text-muted small">
                                        <span class="badge bg-light text-dark">{{ $detalleEjecucion->flujo->tipo->nombre ?? 'Sin tipo' }}</span>
                                        @if($isSuper)
                                            <span class="badge bg-secondary ms-1">{{ $detalleEjecucion->flujo->empresa->nombre ?? 'Sin empresa' }}</span>
                                        @endif
                                        <span class="badge bg-secondary ms-1">Ejecución #{{ $detalleEjecucion->id }}</span>
                                    </div>
                                </div>
                                <div class="status-indicator">
                                    <span class="badge bg-secondary">
                                        <i class="fas fa-pause me-1"></i>Pausada
                                    </span>
                                </div>
                            </div>

                            <!-- Descripción del flujo -->
                            @if($detalleEjecucion->flujo->descripcion)
                                <p class="text-muted small mb-3">
                                    {{ \Illuminate\Support\Str::limit($detalleEjecucion->flujo->descripcion, 120) }}
                                </p>
                            @endif

                            <!-- Contadores -->
                            <div class="row text-center mb-3">
                                <div class="col-6">
                                    <div class="p-2 bg-light rounded">
                                        <i class="fas fa-list-ol text-primary d-block mb-1"></i>
                                        <div class="fw-bold">{{ $detalleEjecucion->flujo->total_etapas }}</div>
                                        <small class="text-muted">etapas</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-2 bg-light rounded">
                                        <i class="fas fa-file-alt text-info d-block mb-1"></i>
                                        <div class="fw-bold">{{ $detalleEjecucion->flujo->total_documentos }}</div>
                                        <small class="text-muted">documentos</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Información de pausa -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="text-muted">Estado</small>
                                    <small class="text-secondary fw-bold">Pausada</small>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-secondary" role="progressbar" style="width: 50%"></div>
                                </div>
                            </div>

                            <!-- Información de fecha -->
                            <div class="mb-3">
                                <small class="text-muted">
                                    <i class="fas fa-pause me-1"></i>
                                    Pausada: {{ $detalleEjecucion->updated_at->diffForHumans() }}
                                </small>
                                <br>
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>
                                    Iniciada: {{ $detalleEjecucion->created_at->diffForHumans() }}
                                </small>
                                <br>
                                <small class="text-muted">
                                    <i class="fas fa-user me-1"></i>
                                    Por: {{ $detalleEjecucion->userCreate->name ?? 'Usuario desconocido' }}
                                </small>
                            </div>

                            <!-- Botones de acción -->
                            <div class="text-center">
                                @if($isSuper)
                                    <!-- SUPERADMIN solo puede ver -->
                                    <a href="/ejecucion/{{ $detalleEjecucion->flujo->id }}" class="btn btn-outline-info btn-sm w-100">
                                        <i class="fas fa-eye me-2"></i>Ver Estado
                                    </a>
                                @else
                                    <!-- Usuarios de empresa pueden reactivar y cancelar -->
                                    <div class="d-grid gap-2">
                                        <button type="button" class="btn btn-success btn-sm reactivar-ejecucion" 
                                                data-detalle-id="{{ $detalleEjecucion->id }}"
                                                data-nombre="{{ $detalleEjecucion->nombre ?? $detalleEjecucion->flujo->nombre }}">
                                            <i class="fas fa-play me-2"></i>Reactivar Ejecución
                                        </button>
                                        <button type="button" class="btn btn-outline-danger btn-sm cancelar-ejecucion" 
                                                data-detalle-id="{{ $detalleEjecucion->id }}"
                                                data-nombre="{{ $detalleEjecucion->nombre ?? $detalleEjecucion->flujo->nombre }}">
                                            <i class="fas fa-times me-2"></i>Cancelar Ejecución
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            @endforeach
            
            @foreach($ejecucionesCanceladas as $detalleEjecucion)
                @if($detalleEjecucion->flujo)
                <div class="col-12 col-lg-6 col-xl-4">
                    <div class="card h-100 shadow-sm border-danger">
                        <div class="card-body p-4">
                            <!-- Header del flujo -->
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="flex-grow-1">
                                    <h5 class="card-title mb-1 text-primary fw-bold">{{ $detalleEjecucion->nombre ?? $detalleEjecucion->flujo->nombre }}</h5>
                                    <div class="text-muted small">
                                        <span class="badge bg-light text-dark">{{ $detalleEjecucion->flujo->tipo->nombre ?? 'Sin tipo' }}</span>
                                        @if($isSuper)
                                            <span class="badge bg-secondary ms-1">{{ $detalleEjecucion->flujo->empresa->nombre ?? 'Sin empresa' }}</span>
                                        @endif
                                        <span class="badge bg-danger ms-1">Ejecución #{{ $detalleEjecucion->id }}</span>
                                    </div>
                                </div>
                                <div class="status-indicator">
                                    <span class="badge bg-danger">
                                        <i class="fas fa-times me-1"></i>Cancelada
                                    </span>
                                </div>
                            </div>

                            <!-- Descripción del flujo -->
                            @if($detalleEjecucion->flujo->descripcion)
                                <p class="text-muted small mb-3">
                                    {{ \Illuminate\Support\Str::limit($detalleEjecucion->flujo->descripcion, 120) }}
                                </p>
                            @endif

                            <!-- Contadores -->
                            <div class="row text-center mb-3">
                                <div class="col-6">
                                    <div class="p-2 bg-light rounded">
                                        <i class="fas fa-list-ol text-primary d-block mb-1"></i>
                                        <div class="fw-bold">{{ $detalleEjecucion->flujo->total_etapas }}</div>
                                        <small class="text-muted">etapas</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-2 bg-light rounded">
                                        <i class="fas fa-file-alt text-info d-block mb-1"></i>
                                        <div class="fw-bold">{{ $detalleEjecucion->flujo->total_documentos }}</div>
                                        <small class="text-muted">documentos</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Información de cancelación -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="text-muted">Estado</small>
                                    <small class="text-danger fw-bold">Cancelada</small>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-danger" role="progressbar" style="width: 100%"></div>
                                </div>
                            </div>

                            <!-- Motivo de cancelación -->
                            @if($detalleEjecucion->motivo)
                                <div class="mb-3">
                                    <small class="text-muted d-block">Motivo de cancelación:</small>
                                    <small class="text-danger">{{ $detalleEjecucion->motivo }}</small>
                                </div>
                            @endif

                            <!-- Información de fecha -->
                            <div class="mb-3">
                                <small class="text-muted">
                                    <i class="fas fa-times me-1"></i>
                                    Cancelada: {{ $detalleEjecucion->updated_at->diffForHumans() }}
                                </small>
                                <br>
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>
                                    Iniciada: {{ $detalleEjecucion->created_at->diffForHumans() }}
                                </small>
                                <br>
                                <small class="text-muted">
                                    <i class="fas fa-user me-1"></i>
                                    Por: {{ $detalleEjecucion->userCreate->name ?? 'Usuario desconocido' }}
                                </small>
                            </div>

                            <!-- Botones de acción -->
                            <div class="text-center">
                                <!-- Ejecución cancelada - solo se puede ver -->
                                <button class="btn btn-outline-secondary btn-sm w-100" disabled>
                                    <i class="fas fa-ban me-2"></i>Ejecución Cancelada
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            @endforeach
        </div>
    </div>
@endif

<!-- Sección de Ejecuciones Completadas -->
@if($ejecucionesTerminadas->count() > 0)
    <div class="mt-5">
        <div class="d-flex align-items-center mb-4">
            <h4 class="mb-0">
                <i class="fas fa-check-circle text-success me-2"></i>
                Ejecuciones Completadas
            </h4>
            <span class="badge bg-success ms-2">{{ $ejecucionesTerminadas->count() }}</span>
        </div>

        <div class="row g-4">
            @foreach($ejecucionesTerminadas as $detalleEjecucion)
                @if($detalleEjecucion->flujo)
                <div class="col-12 col-lg-6 col-xl-4">
                    <div class="card h-100 shadow-sm border-success">
                        <div class="card-body p-4">
                            <!-- Header del flujo -->
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="flex-grow-1">
                                    <h5 class="card-title mb-1 text-primary fw-bold">{{ $detalleEjecucion->nombre ?? $detalleEjecucion->flujo->nombre }}</h5>
                                    <div class="text-muted small">
                                        <span class="badge bg-light text-dark">{{ $detalleEjecucion->flujo->tipo->nombre ?? 'Sin tipo' }}</span>
                                        @if($isSuper)
                                            <span class="badge bg-secondary ms-1">{{ $detalleEjecucion->flujo->empresa->nombre ?? 'Sin empresa' }}</span>
                                        @endif
                                        <span class="badge bg-success ms-1">Ejecución #{{ $detalleEjecucion->id }}</span>
                                    </div>
                                </div>
                                <div class="status-indicator">
                                    <span class="badge bg-success">
                                        <i class="fas fa-check-circle me-1"></i>Completado
                                    </span>
                                </div>
                            </div>

                            <!-- Descripción del flujo -->
                            @if($detalleEjecucion->flujo->descripcion)
                                <p class="text-muted small mb-3">
                                    {{ \Illuminate\Support\Str::limit($detalleEjecucion->flujo->descripcion, 120) }}
                                </p>
                            @endif

                            <!-- Contadores -->
                            <div class="row text-center mb-3">
                                <div class="col-6">
                                    <div class="p-2 bg-light rounded">
                                        <i class="fas fa-list-ol text-primary d-block mb-1"></i>
                                        <div class="fw-bold">{{ $detalleEjecucion->flujo->total_etapas }}</div>
                                        <small class="text-muted">etapas</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-2 bg-light rounded">
                                        <i class="fas fa-file-alt text-info d-block mb-1"></i>
                                        <div class="fw-bold">{{ $detalleEjecucion->flujo->total_documentos }}</div>
                                        <small class="text-muted">documentos</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Indicador de completado -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="text-muted">Estado</small>
                                    <small class="text-success fw-bold">100% Completado</small>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
                                </div>
                            </div>

                            <!-- Información de fecha -->
                            <div class="mb-3">
                                <small class="text-muted">
                                    <i class="fas fa-check me-1"></i>
                                    Terminado: {{ $detalleEjecucion->updated_at->diffForHumans() }}
                                </small>
                                <br>
                                <small class="text-muted">
                                    <i class="fas fa-user me-1"></i>
                                    Por: {{ $detalleEjecucion->userCreate->name ?? 'Usuario desconocido' }}
                                </small>
                            </div>

                            <!-- Botones de acción -->
                            <div class="text-center">
                                <!-- Ejecución terminada - todos pueden ver detalles -->
                                <a href="/ejecucion/{{ $detalleEjecucion->flujo->id }}" class="btn btn-outline-success btn-sm w-100">
                                    <i class="fas fa-eye me-2"></i>Ver Detalles Completos
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            @endforeach
        </div>
    </div>
@endif

@if(!$isSuper)
<!-- Modal de Configuración de Ejecución -->
<div class="modal fade" id="modalConfiguracion" tabindex="-1" aria-labelledby="modalConfiguracionLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalConfiguracionLabel">
                    <i class="fas fa-cog me-2"></i>Configurar Nueva Ejecución
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formConfiguracion">
                <div class="modal-body">
                    <!-- Nombre de la ejecución -->
                    <div class="mb-4">
                        <label for="nombre-ejecucion" class="form-label fw-bold">
                            <i class="fas fa-tag me-1"></i>Nombre de esta ejecución
                        </label>
                        <input type="text" class="form-control" id="nombre-ejecucion" name="nombre" required 
                               placeholder="Ej: Producción Lote #123, Cliente ABC - Enero 2025">
                        <div class="form-text">Dale un nombre descriptivo para identificar fácilmente esta ejecución</div>
                    </div>

                    <!-- Configuración de tareas y documentos -->
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">
                                <i class="fas fa-tasks me-1"></i>Tareas a incluir
                                <button type="button" class="btn btn-sm btn-outline-success ms-2" id="select-all-tareas">
                                    <i class="fas fa-check-double"></i> Todas
                                </button>
                            </h6>
                            <div id="tareas-container" class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                                <!-- Las tareas se cargarán dinámicamente -->
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">
                                <i class="fas fa-file-alt me-1"></i>Documentos a incluir
                                <button type="button" class="btn btn-sm btn-outline-success ms-2" id="select-all-documentos">
                                    <i class="fas fa-check-double"></i> Todos
                                </button>
                            </h6>
                            <div id="documentos-container" class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                                <!-- Los documentos se cargarán dinámicamente -->
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-play me-1"></i>Crear y Ejecutar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Cancelar Ejecución -->
<div class="modal fade" id="modalCancelarEjecucion" tabindex="-1" aria-labelledby="modalCancelarEjecucionLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="modalCancelarEjecucionLabel">
                    <i class="fas fa-times me-2"></i>Cancelar Ejecución
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formCancelarEjecucion">
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>¿Estás seguro de que quieres cancelar esta ejecución?</strong>
                        <br>Esta acción no se puede deshacer.
                    </div>
                    
                    <div class="mb-3">
                        <p class="mb-2">Ejecución a cancelar:</p>
                        <p class="fw-bold text-primary" id="nombre-ejecucion-cancelar"></p>
                    </div>

                    <div class="mb-3">
                        <label for="motivo-cancelacion" class="form-label fw-bold">
                            <i class="fas fa-comment me-1"></i>Motivo de cancelación *
                        </label>
                        <textarea class="form-control" id="motivo-cancelacion" name="motivo" rows="4" 
                                  placeholder="Describe el motivo por el cual se cancela esta ejecución..." 
                                  required minlength="5" maxlength="500"></textarea>
                        <div class="form-text">Mínimo 5 caracteres, máximo 500. Este motivo quedará registrado en el sistema.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-arrow-left me-1"></i>Volver
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times me-1"></i>Confirmar Cancelación
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@endsection

@push('styles')
<style>
.selection-section {
    position: sticky;
    top: 20px;
    z-index: 100;
}

.card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1) !important;
}

.badge {
    font-size: 0.7rem;
}

#flujo-selector {
    border: 2px solid #e9ecef;
    transition: border-color 0.3s ease;
}

#flujo-selector:focus {
    border-color: #28a745;
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
}

#ejecutar-btn, #ver-detalle-btn {
    transition: all 0.3s ease;
}

#ejecutar-btn:disabled, #ver-detalle-btn:disabled {
    opacity: 0.6;
    transform: scale(0.95);
    cursor: not-allowed;
}

#ejecutar-btn:not(:disabled), #ver-detalle-btn:not(:disabled) {
    opacity: 1;
    transform: scale(1);
    cursor: pointer;
}

#ejecutar-btn:not(:disabled):hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
}

#ver-detalle-btn:not(:disabled):hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(23, 162, 184, 0.3);
}

/* Estilos para ejecuciones en proceso, pausadas y terminadas */
.border-warning {
    border: 2px solid #ffc107 !important;
}

.border-secondary {
    border: 2px solid #6c757d !important;
}

.border-danger {
    border: 2px solid #dc3545 !important;
}

.border-success {
    border: 2px solid #198754 !important;
}

.progress-bar-animated {
    animation: progress-bar-stripes 1s linear infinite;
}

@keyframes progress-bar-stripes {
    0% {
        background-position: 1rem 0;
    }
    100% {
        background-position: 0 0;
    }
}

.card.border-warning:hover {
    box-shadow: 0 8px 25px rgba(255, 193, 7, 0.2) !important;
}

.card.border-secondary:hover {
    box-shadow: 0 8px 25px rgba(108, 117, 125, 0.2) !important;
}

.card.border-danger:hover {
    box-shadow: 0 8px 25px rgba(220, 53, 69, 0.2) !important;
}

.card.border-success:hover {
    box-shadow: 0 8px 25px rgba(25, 135, 84, 0.2) !important;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Obtener elementos
    const flujoSelector = document.getElementById('flujo-selector');
    const ejecutarBtn = document.getElementById('ejecutar-btn');
    const verDetalleBtn = document.getElementById('ver-detalle-btn');
    const isSuper = @json($isSuper);
    
    console.log('DOM loaded. isSuper:', isSuper);
    console.log('flujoSelector existe:', !!flujoSelector);
    console.log('ejecutarBtn existe:', !!ejecutarBtn);
    console.log('verDetalleBtn existe:', !!verDetalleBtn);
    
    // Variables para el modal
    let modalConfiguracion, formConfiguracion;
    let flujoSeleccionado = null;

    // Verificar que los elementos básicos existan
    if (!flujoSelector || !verDetalleBtn) {
        console.error('Elementos básicos no encontrados');
        return;
    }

    // Para usuarios no-super, verificar que existe el botón ejecutar
    if (!isSuper && !ejecutarBtn) {
        console.error('Botón ejecutar no encontrado para usuario no-super');
        return;
    }

    // Inicializar modal si no es SUPERADMIN
    if (!isSuper) {
        const modalElement = document.getElementById('modalConfiguracion');
        if (modalElement) {
            modalConfiguracion = new bootstrap.Modal(modalElement);
            formConfiguracion = document.getElementById('formConfiguracion');
            console.log('Modal inicializado correctamente');
        } else {
            console.error('Modal de configuración no encontrado');
            return;
        }
    }

    // Función para actualizar estado de botones
    function updateButtons() {
        const selectedValue = flujoSelector.value;
        const shouldEnable = selectedValue !== '' && selectedValue !== null && selectedValue !== undefined;
        
        verDetalleBtn.disabled = !shouldEnable;
        
        // Solo actualizar botón ejecutar si no es SUPERADMIN
        if (!isSuper && ejecutarBtn) {
            ejecutarBtn.disabled = !shouldEnable;
        }
    }

    // Manejar cambio en el selector
    flujoSelector.addEventListener('change', function(e) {
        updateButtons();
    });

    // Evento para el botón ver detalles
    verDetalleBtn.addEventListener('click', function(e) {
        e.preventDefault();
        const selectedId = flujoSelector.value;
        
        if (!selectedId) {
            alert('Por favor selecciona un flujo primero');
            return;
        }
        
        const url = `/ejecucion/${selectedId}`;
        window.location.href = url;
    });

    // Evento para el botón ejecutar (solo si no es SUPERADMIN)
    if (!isSuper && ejecutarBtn) {
        ejecutarBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const selectedId = flujoSelector.value;
            
            if (!selectedId) {
                alert('Por favor selecciona un flujo primero');
                return;
            }

            console.log('Iniciando configuración para flujo ID:', selectedId);
            console.log('Modal configuración existe:', !!modalConfiguracion);
            console.log('Form configuración existe:', !!formConfiguracion);
            
            // Cargar configuración del flujo y mostrar modal
            cargarConfiguracionFlujo(selectedId);
        });
    } else if (!isSuper) {
        console.error('Botón ejecutar no encontrado para usuario no-super');
    }

    // Función para cargar la configuración del flujo
    function cargarConfiguracionFlujo(flujoId) {
        console.log('Cargando configuración para flujo:', flujoId);
        
        // Mostrar loading
        ejecutarBtn.disabled = true;
        ejecutarBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Cargando...';

        fetch(`/ejecucion/${flujoId}/configurar`)
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Datos recibidos:', data);
                
                if (!data.flujo) {
                    throw new Error('No se recibieron datos del flujo');
                }
                
                flujoSeleccionado = data.flujo;
                
                // Verificar que el elemento de nombre de ejecución exista
                const nombreEjecucion = document.getElementById('nombre-ejecucion');
                
                console.log('Elementos del modal:');
                console.log('nombreEjecucion existe:', !!nombreEjecucion);
                
                if (!nombreEjecucion) {
                    throw new Error('Campo de nombre de ejecución no encontrado');
                }
                
                // Limpiar el campo de nombre (sin valor por defecto)
                nombreEjecucion.value = '';
                
                // Cargar tareas
                cargarTareas(data.flujo.etapas);
                
                // Cargar documentos
                cargarDocumentos(data.flujo.etapas);
                
                // Mostrar modal
                console.log('Mostrando modal...');
                modalConfiguracion.show();
            })
            .catch(error => {
                console.error('Error completo:', error);
                alert(`Error al cargar la configuración del flujo: ${error.message}`);
            })
            .finally(() => {
                // Restaurar botón
                ejecutarBtn.disabled = false;
                ejecutarBtn.innerHTML = '<i class="fas fa-cog me-2"></i>Configurar y Ejecutar';
            });
    }

    // Función para cargar tareas en el modal
    function cargarTareas(etapas) {
        const container = document.getElementById('tareas-container');
        if (!container) {
            console.error('Container de tareas no encontrado');
            return;
        }
        
        container.innerHTML = '';

        etapas.forEach(etapa => {
            if (etapa.tareas.length > 0) {
                // Título de etapa
                const etapaTitle = document.createElement('div');
                etapaTitle.className = 'mb-2';
                etapaTitle.innerHTML = `<small class="fw-bold text-primary">Etapa ${etapa.nro}: ${etapa.nombre}</small>`;
                container.appendChild(etapaTitle);

                // Tareas de la etapa
                etapa.tareas.forEach(tarea => {
                    const tareaDiv = document.createElement('div');
                    tareaDiv.className = 'form-check mb-2';
                    tareaDiv.innerHTML = `
                        <input class="form-check-input tarea-checkbox" type="checkbox" value="${tarea.id}" 
                               id="tarea-${tarea.id}" checked>
                        <label class="form-check-label" for="tarea-${tarea.id}">
                            <strong>${tarea.nombre}</strong>
                            ${tarea.descripcion ? `<br><small class="text-muted">${tarea.descripcion}</small>` : ''}
                        </label>
                    `;
                    container.appendChild(tareaDiv);
                });
            }
        });
    }

    // Función para cargar documentos en el modal
    function cargarDocumentos(etapas) {
        const container = document.getElementById('documentos-container');
        if (!container) {
            console.error('Container de documentos no encontrado');
            return;
        }
        
        container.innerHTML = '';

        etapas.forEach(etapa => {
            if (etapa.documentos.length > 0) {
                // Título de etapa
                const etapaTitle = document.createElement('div');
                etapaTitle.className = 'mb-2';
                etapaTitle.innerHTML = `<small class="fw-bold text-primary">Etapa ${etapa.nro}: ${etapa.nombre}</small>`;
                container.appendChild(etapaTitle);

                // Documentos de la etapa
                etapa.documentos.forEach(documento => {
                    const docDiv = document.createElement('div');
                    docDiv.className = 'form-check mb-2';
                    docDiv.innerHTML = `
                        <input class="form-check-input documento-checkbox" type="checkbox" value="${documento.id}" 
                               id="documento-${documento.id}" checked>
                        <label class="form-check-label" for="documento-${documento.id}">
                            <strong>${documento.nombre}</strong>
                            ${documento.descripcion ? `<br><small class="text-muted">${documento.descripcion}</small>` : ''}
                        </label>
                    `;
                    container.appendChild(docDiv);
                });
            }
        });
    }

    // Eventos para seleccionar todas las tareas/documentos
    if (!isSuper) {
        const selectAllTareas = document.getElementById('select-all-tareas');
        const selectAllDocumentos = document.getElementById('select-all-documentos');
        
        if (selectAllTareas) {
            selectAllTareas.addEventListener('click', function() {
                const checkboxes = document.querySelectorAll('.tarea-checkbox');
                const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                checkboxes.forEach(cb => cb.checked = !allChecked);
            });
        }

        if (selectAllDocumentos) {
            selectAllDocumentos.addEventListener('click', function() {
                const checkboxes = document.querySelectorAll('.documento-checkbox');
                const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                checkboxes.forEach(cb => cb.checked = !allChecked);
            });
        }

        // Evento para enviar configuración
        if (formConfiguracion) {
            formConfiguracion.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const nombre = document.getElementById('nombre-ejecucion').value.trim();
                if (!nombre) {
                    alert('Por favor ingresa un nombre para la ejecución');
                    return;
                }

                // Recopilar tareas seleccionadas
                const tareasSeleccionadas = Array.from(document.querySelectorAll('.tarea-checkbox:checked'))
                    .map(cb => cb.value);
                
                // Recopilar documentos seleccionados
                const documentosSeleccionados = Array.from(document.querySelectorAll('.documento-checkbox:checked'))
                    .map(cb => cb.value);

                // Enviar configuración
                enviarConfiguracion(flujoSeleccionado.id, {
                    nombre: nombre,
                    tareas_seleccionadas: tareasSeleccionadas,
                    documentos_seleccionados: documentosSeleccionados
                });
            });
        }
    }

    // Función para enviar la configuración
    function enviarConfiguracion(flujoId, configuracion) {
        const submitBtn = formConfiguracion.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Creando...';

        fetch(`/ejecucion/${flujoId}/crear`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(configuracion)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                modalConfiguracion.hide();
                // Redirigir a la ejecución
                window.location.href = data.redirect_url;
            } else {
                alert(data.error || 'Error al crear la ejecución');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al crear la ejecución');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-play me-1"></i>Crear y Ejecutar';
        });
    }
    
    // Manejar botones de pausar ejecución
    document.querySelectorAll('.pausar-ejecucion').forEach(btn => {
        btn.addEventListener('click', function() {
            const detalleId = this.dataset.detalleId;
            const nombre = this.dataset.nombre;
            
            if (confirm(`¿Estás seguro de que quieres pausar la ejecución "${nombre}"?`)) {
                pausarEjecucion(detalleId, this);
            }
        });
    });

    // Manejar botones de reactivar ejecución
    document.querySelectorAll('.reactivar-ejecucion').forEach(btn => {
        btn.addEventListener('click', function() {
            const detalleId = this.dataset.detalleId;
            const nombre = this.dataset.nombre;
            
            if (confirm(`¿Estás seguro de que quieres reactivar la ejecución "${nombre}"?`)) {
                reactivarEjecucion(detalleId, this);
            }
        });
    });

    // Función para pausar ejecución
    function pausarEjecucion(detalleId, btn) {
        // Deshabilitar botón mientras se procesa
        btn.disabled = true;
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Pausando...';

        fetch(`/ejecucion/detalle/${detalleId}/pausar`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Recargar la página para reflejar el cambio
                window.location.reload();
            } else {
                alert(`Error al pausar la ejecución: ${data.error || data.message}`);
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al pausar la ejecución');
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        });
    }

    // Función para reactivar ejecución
    function reactivarEjecucion(detalleId, btn) {
        // Deshabilitar botón mientras se procesa
        btn.disabled = true;
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Reactivando...';

        fetch(`/ejecucion/detalle/${detalleId}/reactivar`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Recargar la página para reflejar el cambio
                window.location.reload();
            } else {
                alert(`Error al reactivar la ejecución: ${data.error || data.message}`);
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al reactivar la ejecución');
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        });
    }
    
    // Manejar botones de cancelar ejecución
    document.querySelectorAll('.cancelar-ejecucion').forEach(btn => {
        btn.addEventListener('click', function() {
            const detalleId = this.dataset.detalleId;
            const nombre = this.dataset.nombre;
            
            // Configurar modal
            document.getElementById('nombre-ejecucion-cancelar').textContent = nombre;
            document.getElementById('motivo-cancelacion').value = '';
            
            // Guardar datos en el modal para su uso posterior
            const modal = document.getElementById('modalCancelarEjecucion');
            modal.dataset.detalleId = detalleId;
            modal.dataset.nombre = nombre;
            
            // Mostrar modal
            const modalCancelar = new bootstrap.Modal(modal);
            modalCancelar.show();
        });
    });

    // Manejar envío del formulario de cancelación
    const formCancelarEjecucion = document.getElementById('formCancelarEjecucion');
    if (formCancelarEjecucion) {
        formCancelarEjecucion.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const modal = document.getElementById('modalCancelarEjecucion');
            const detalleId = modal.dataset.detalleId;
            const motivo = document.getElementById('motivo-cancelacion').value.trim();
            
            if (motivo.length < 5) {
                alert('El motivo debe tener al menos 5 caracteres');
                return;
            }
            
            if (motivo.length > 500) {
                alert('El motivo no puede exceder 500 caracteres');
                return;
            }
            
            cancelarEjecucion(detalleId, motivo);
        });
    }

    // Función para cancelar ejecución
    function cancelarEjecucion(detalleId, motivo) {
        const submitBtn = formCancelarEjecucion.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        const originalHtml = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Cancelando...';

        fetch(`/ejecucion/detalle/${detalleId}/cancelar`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                motivo: motivo
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Cerrar modal
                bootstrap.Modal.getInstance(document.getElementById('modalCancelarEjecucion')).hide();
                // Recargar la página para reflejar el cambio
                window.location.reload();
            } else {
                if (data.errors && data.errors.motivo) {
                    alert(`Error de validación: ${data.errors.motivo[0]}`);
                } else {
                    alert(`Error al cancelar la ejecución: ${data.error || data.message}`);
                }
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalHtml;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al cancelar la ejecución');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalHtml;
        });
    }
    
    // Actualización inicial de botones
    updateButtons();
});
</script>
@endpush
