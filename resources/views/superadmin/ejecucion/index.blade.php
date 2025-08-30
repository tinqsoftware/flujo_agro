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

<!-- Sección de Ejecuciones en Layout de 3 Columnas -->
@php
    $ejecucionesEnProceso = $ejecucionesActivas->where('estado', 2);
    $ejecucionesPausadas = $ejecucionesActivas->where('estado', 4);
    $ejecucionesCanceladas = $ejecucionesActivas->where('estado', 99);
    $ejecucionesTerminadas = $ejecucionesActivas->where('estado', 3);
    $ejecucionesPausadasYCanceladas = $ejecucionesPausadas->merge($ejecucionesCanceladas);
@endphp

@if($ejecucionesEnProceso->count() > 0 || $ejecucionesTerminadas->count() > 0 || $ejecucionesPausadasYCanceladas->count() > 0)
    <div class="mt-5">
        <div class="d-flex align-items-center mb-4">
            <h4 class="mb-0">
                <i class="fas fa-tasks text-primary me-2"></i>
                Estado de Ejecuciones
            </h4>
        </div>

        <div class="row g-4">
            <!-- Columna 1: Ejecuciones en Proceso -->
            <div class="col-12 col-lg-4">
                <div class="card h-100 border-warning">
                    <div class="card-header bg-warning text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-play-circle me-2"></i>
                            En Proceso
                            <span class="badge bg-light text-warning ms-2">{{ $ejecucionesEnProceso->count() }}</span>
                        </h5>
                    </div>
                    <div class="card-body p-2" style="max-height: 600px; overflow-y: auto;">
                        @if($ejecucionesEnProceso->count() > 0)
                            @foreach($ejecucionesEnProceso as $detalleEjecucion)
                                @if($detalleEjecucion->flujo)
                                <div class="card shadow-sm border-warning mb-3 mx-2">
                                    <div class="card-body p-3">
                                        <!-- Header del flujo -->
                                        <div class="mb-3">
                                            <h6 class="fw-bold text-primary mb-1">{{ $detalleEjecucion->nombre ?? $detalleEjecucion->flujo->nombre }}</h6>
                                        <div class="text-muted small">
                                            <span class="badge bg-light text-dark">{{ $detalleEjecucion->flujo->tipo->nombre ?? 'Sin tipo' }}</span>
                                            @if($isSuper)
                                                <span class="badge bg-secondary ms-1">{{ $detalleEjecucion->flujo->empresa->nombre ?? 'Sin empresa' }}</span>
                                            @endif
                                            <span class="badge bg-warning ms-1">#{{ $detalleEjecucion->id }}</span>
                                        </div>
                                    </div>

                                    <!-- Información básica -->
                                    <div class="row text-center mb-3">
                                        <div class="col-6">
                                            <div class="p-2 bg-light rounded">
                                                <small class="text-muted d-block">Etapas</small>
                                                <div class="fw-bold text-primary">{{ $detalleEjecucion->flujo->total_etapas }}</div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="p-2 bg-light rounded">
                                                <small class="text-muted d-block">Docs</small>
                                                <div class="fw-bold text-info">{{ $detalleEjecucion->flujo->total_documentos }}</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Progreso -->
                                    <div class="mb-3">
                                        <small class="text-muted d-block">Progreso</small>
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar bg-warning progress-bar-animated" role="progressbar" style="width: 45%"></div>
                                        </div>
                                    </div>

                                    <!-- Fechas -->
                                    <div class="mb-3">
                                        <small class="text-muted d-block">
                                            <i class="fas fa-clock me-1"></i>{{ $detalleEjecucion->created_at->diffForHumans() }}
                                        </small>
                                        <small class="text-muted">
                                            <i class="fas fa-user me-1"></i>{{ $detalleEjecucion->userCreate->name ?? 'Usuario desconocido' }}
                                        </small>
                                    </div>

                                    <!-- Botones de acción -->
                                    @if($isSuper)
                                        <div class="d-grid">
                                            <a href="/ejecucion/{{ $detalleEjecucion->flujo->id }}" class="btn btn-outline-info btn-sm">
                                                <i class="fas fa-eye me-1"></i>Ver Estado
                                            </a>
                                        </div>
                                    @else
                                        <div class="d-grid gap-2">
                                            <a href="/ejecucion/detalle/{{ $detalleEjecucion->id }}/ejecutar" class="btn btn-warning btn-sm">
                                                <i class="fas fa-play me-1"></i>Continuar
                                            </a>
                                            <div class="row g-1">
                                                <div class="col-6">
                                                    <button type="button" class="btn btn-outline-secondary btn-sm w-100 pausar-ejecucion" 
                                                            data-detalle-id="{{ $detalleEjecucion->id }}"
                                                            data-nombre="{{ $detalleEjecucion->nombre ?? $detalleEjecucion->flujo->nombre }}">
                                                        <i class="fas fa-pause"></i>
                                                    </button>
                                                </div>
                                                <div class="col-6">
                                                    <button type="button" class="btn btn-outline-danger btn-sm w-100 cancelar-ejecucion" 
                                                            data-detalle-id="{{ $detalleEjecucion->id }}"
                                                            data-nombre="{{ $detalleEjecucion->nombre ?? $detalleEjecucion->flujo->nombre }}">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                                @endif
                            @endforeach
                        @else
                            <div class="p-4 text-center text-muted">
                                <i class="fas fa-inbox fa-2x mb-2"></i>
                                <p class="mb-0">No hay ejecuciones en proceso</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Columna 2: Ejecuciones Completadas -->
            <div class="col-12 col-lg-4">
                <div class="card h-100 border-success">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-check-circle me-2"></i>
                            Completadas
                            <span class="badge bg-light text-success ms-2">{{ $ejecucionesTerminadas->count() }}</span>
                        </h5>
                    </div>
                    <div class="card-body p-2" style="max-height: 600px; overflow-y: auto;">
                        @if($ejecucionesTerminadas->count() > 0)
                            @foreach($ejecucionesTerminadas as $detalleEjecucion)
                                @if($detalleEjecucion->flujo)
                                <div class="card shadow-sm border-success mb-3 mx-2">
                                    <div class="card-body p-3">
                                        <!-- Header del flujo -->
                                        <div class="mb-3">
                                            <h6 class="fw-bold text-primary mb-1">{{ $detalleEjecucion->nombre ?? $detalleEjecucion->flujo->nombre }}</h6>
                                            <div class="text-muted small">
                                                <span class="badge bg-light text-dark">{{ $detalleEjecucion->flujo->tipo->nombre ?? 'Sin tipo' }}</span>
                                                @if($isSuper)
                                                    <span class="badge bg-secondary ms-1">{{ $detalleEjecucion->flujo->empresa->nombre ?? 'Sin empresa' }}</span>
                                                @endif
                                                <span class="badge bg-success ms-1">#{{ $detalleEjecucion->id }}</span>
                                            </div>
                                        </div>

                                        <!-- Información básica -->
                                        <div class="row text-center mb-3">
                                            <div class="col-6">
                                                <div class="p-2 bg-light rounded">
                                                    <small class="text-muted d-block">Etapas</small>
                                                    <div class="fw-bold text-primary">{{ $detalleEjecucion->flujo->total_etapas }}</div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="p-2 bg-light rounded">
                                                    <small class="text-muted d-block">Docs</small>
                                                    <div class="fw-bold text-info">{{ $detalleEjecucion->flujo->total_documentos }}</div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Estado completado -->
                                        <div class="mb-3">
                                            <small class="text-success fw-bold d-block">100% Completado</small>
                                            <div class="progress" style="height: 6px;">
                                                <div class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
                                            </div>
                                        </div>

                                        <!-- Fechas -->
                                        <div class="mb-3">
                                            <small class="text-muted d-block">
                                                <i class="fas fa-check me-1"></i>{{ $detalleEjecucion->updated_at->diffForHumans() }}
                                            </small>
                                            <small class="text-muted">
                                                <i class="fas fa-user me-1"></i>{{ $detalleEjecucion->userCreate->name ?? 'Usuario desconocido' }}
                                            </small>
                                        </div>

                                        <!-- Botones de acción -->
                                        <div class="d-grid gap-2">
                                            <a href="/ejecucion/{{ $detalleEjecucion->flujo->id }}" class="btn btn-outline-success btn-sm">
                                                <i class="fas fa-eye me-1"></i>Ver Detalles
                                            </a>
                                            <button type="button" class="btn btn-outline-primary btn-sm previsualizar-flujo" 
                                                    data-flujo-id="{{ $detalleEjecucion->flujo->id }}"
                                                    data-flujo-nombre="{{ $detalleEjecucion->nombre ?? $detalleEjecucion->flujo->nombre }}">
                                                <i class="fas fa-search me-1"></i>Previsualizar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            @endforeach
                        @else
                            <div class="p-4 text-center text-muted">
                                <i class="fas fa-check-circle fa-2x mb-2"></i>
                                <p class="mb-0">No hay ejecuciones completadas</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Columna 3: Ejecuciones Pausadas y Canceladas -->
            <div class="col-12 col-lg-4">
                <div class="card h-100 border-secondary">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-pause-circle me-2"></i>
                            Pausadas y Canceladas
                            <span class="badge bg-light text-secondary ms-2">{{ $ejecucionesPausadasYCanceladas->count() }}</span>
                        </h5>
                    </div>
                    <div class="card-body p-2" style="max-height: 600px; overflow-y: auto;">
                        @if($ejecucionesPausadasYCanceladas->count() > 0)
                            @foreach($ejecucionesPausadasYCanceladas as $detalleEjecucion)
                                @if($detalleEjecucion->flujo)
                                <div class="card shadow-sm mb-3 mx-2 {{ $detalleEjecucion->estado == 4 ? 'border-secondary' : 'border-danger' }}">
                                    <div class="card-body p-3">
                                        <!-- Header del flujo -->
                                        <div class="mb-3">
                                            <h6 class="fw-bold text-primary mb-1">{{ $detalleEjecucion->nombre ?? $detalleEjecucion->flujo->nombre }}</h6>
                                            <div class="text-muted small">
                                                <span class="badge bg-light text-dark">{{ $detalleEjecucion->flujo->tipo->nombre ?? 'Sin tipo' }}</span>
                                                @if($isSuper)
                                                    <span class="badge bg-secondary ms-1">{{ $detalleEjecucion->flujo->empresa->nombre ?? 'Sin empresa' }}</span>
                                                @endif
                                                @if($detalleEjecucion->estado == 4)
                                                    <span class="badge bg-secondary ms-1">#{{ $detalleEjecucion->id }}</span>
                                                @else
                                                    <span class="badge bg-danger ms-1">#{{ $detalleEjecucion->id }}</span>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Estado visual -->
                                        <div class="mb-3">
                                            @if($detalleEjecucion->estado == 4)
                                                <span class="badge bg-secondary mb-2">
                                                    <i class="fas fa-pause me-1"></i>Pausada
                                                </span>
                                            @else
                                                <span class="badge bg-danger mb-2">
                                                    <i class="fas fa-times me-1"></i>Cancelada
                                                </span>
                                            @endif
                                        </div>

                                        <!-- Información básica -->
                                        <div class="row text-center mb-3">
                                            <div class="col-6">
                                                <div class="p-2 bg-light rounded">
                                                    <small class="text-muted d-block">Etapas</small>
                                                    <div class="fw-bold text-primary">{{ $detalleEjecucion->flujo->total_etapas }}</div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="p-2 bg-light rounded">
                                                    <small class="text-muted d-block">Docs</small>
                                                    <div class="fw-bold text-info">{{ $detalleEjecucion->flujo->total_documentos }}</div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Progreso -->
                                        <div class="mb-3">
                                            <div class="progress" style="height: 6px;">
                                                @if($detalleEjecucion->estado == 4)
                                                    <div class="progress-bar bg-secondary" role="progressbar" style="width: 50%"></div>
                                                @else
                                                    <div class="progress-bar bg-danger" role="progressbar" style="width: 100%"></div>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Motivo de cancelación si aplica -->
                                        @if($detalleEjecucion->estado == 99 && $detalleEjecucion->motivo)
                                            <div class="mb-3">
                                                <small class="text-muted d-block">Motivo:</small>
                                                <small class="text-danger">{{ \Illuminate\Support\Str::limit($detalleEjecucion->motivo, 80) }}</small>
                                            </div>
                                        @endif

                                        <!-- Fechas -->
                                        <div class="mb-3">
                                            <small class="text-muted d-block">
                                                @if($detalleEjecucion->estado == 4)
                                                    <i class="fas fa-pause me-1"></i>{{ $detalleEjecucion->updated_at->diffForHumans() }}
                                                @else
                                                    <i class="fas fa-times me-1"></i>{{ $detalleEjecucion->updated_at->diffForHumans() }}
                                                @endif
                                            </small>
                                            <small class="text-muted">
                                                <i class="fas fa-user me-1"></i>{{ $detalleEjecucion->userCreate->name ?? 'Usuario desconocido' }}
                                            </small>
                                        </div>

                                        <!-- Botones de acción -->
                                        @if($detalleEjecucion->estado == 4)
                                            <!-- Ejecución pausada -->
                                            @if($isSuper)
                                                <div class="d-grid gap-2">
                                                    <a href="/ejecucion/{{ $detalleEjecucion->flujo->id }}" class="btn btn-outline-info btn-sm">
                                                        <i class="fas fa-eye me-1"></i>Ver Estado
                                                    </a>
                                                    <button type="button" class="btn btn-outline-primary btn-sm previsualizar-flujo" 
                                                            data-flujo-id="{{ $detalleEjecucion->flujo->id }}"
                                                            data-flujo-nombre="{{ $detalleEjecucion->nombre ?? $detalleEjecucion->flujo->nombre }}">
                                                        <i class="fas fa-search me-1"></i>Ver
                                                    </button>
                                                </div>
                                            @else
                                                <div class="d-grid gap-2">
                                                    <button type="button" class="btn btn-success btn-sm reactivar-ejecucion" 
                                                            data-detalle-id="{{ $detalleEjecucion->id }}"
                                                            data-nombre="{{ $detalleEjecucion->nombre ?? $detalleEjecucion->flujo->nombre }}">
                                                        <i class="fas fa-play me-1"></i>Reactivar
                                                    </button>
                                                    <div class="row g-1">
                                                        <div class="col-6">
                                                            <button type="button" class="btn btn-outline-danger btn-sm cancelar-ejecucion" 
                                                                    data-detalle-id="{{ $detalleEjecucion->id }}"
                                                                    data-nombre="{{ $detalleEjecucion->nombre ?? $detalleEjecucion->flujo->nombre }}">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </div>
                                                        <div class="col-6">
                                                            <button type="button" class="btn btn-outline-primary btn-sm previsualizar-flujo" 
                                                                    data-flujo-id="{{ $detalleEjecucion->flujo->id }}"
                                                                    data-flujo-nombre="{{ $detalleEjecucion->nombre ?? $detalleEjecucion->flujo->nombre }}">
                                                                <i class="fas fa-search"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        @else
                                            <!-- Ejecución cancelada -->
                                            <div class="d-grid gap-2">
                                                <button class="btn btn-outline-secondary btn-sm" disabled>
                                                    <i class="fas fa-ban me-1"></i>Cancelada
                                                </button>
                                                <button type="button" class="btn btn-outline-primary btn-sm previsualizar-flujo" 
                                                        data-flujo-id="{{ $detalleEjecucion->flujo->id }}"
                                                        data-flujo-nombre="{{ $detalleEjecucion->nombre ?? $detalleEjecucion->flujo->nombre }}">
                                                    <i class="fas fa-search me-1"></i>Previsualizar
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                @endif
                            @endforeach
                        @else
                            <div class="p-4 text-center text-muted">
                                <i class="fas fa-pause-circle fa-2x mb-2"></i>
                                <p class="mb-0">No hay ejecuciones pausadas o canceladas</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
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

<!-- Modal para Previsualizar Flujo -->
<div class="modal fade" id="modalPrevisualizarFlujo" tabindex="-1" aria-labelledby="modalPrevisualizarFlujoLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalPrevisualizarFlujoLabel">
                    <i class="fas fa-search me-2"></i>Previsualización del Flujo
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                <div id="contenido-previsualizacion">
                    <div class="text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                        <p class="mt-2">Cargando información del flujo...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

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

/* Estilos mejorados para las columnas de ejecuciones */
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

/* Estilos específicos para las tarjetas individuales */
.card.shadow-sm {
    border-radius: 0.5rem;
    background: #fff;
    transition: all 0.3s ease;
}

.card.shadow-sm:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.15) !important;
}

.card.border-warning:hover {
    box-shadow: 0 10px 30px rgba(255, 193, 7, 0.3) !important;
}

.card.border-secondary:hover {
    box-shadow: 0 10px 30px rgba(108, 117, 125, 0.3) !important;
}

.card.border-danger:hover {
    box-shadow: 0 10px 30px rgba(220, 53, 69, 0.3) !important;
}

.card.border-success:hover {
    box-shadow: 0 10px 30px rgba(25, 135, 84, 0.3) !important;
}

/* Mejoras para el contenedor de scroll */
.card-body[style*="overflow-y: auto"] {
    padding-right: 0.25rem;
    scrollbar-width: none; /* Firefox */
    -ms-overflow-style: none; /* Internet Explorer 10+ */
}

/* Ocultar scrollbar en Webkit browsers (Chrome, Safari, Edge) */
.card-body[style*="overflow-y: auto"]::-webkit-scrollbar {
    display: none;
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

/* Mejoras para badges y elementos pequeños */
.badge {
    font-weight: 500;
    letter-spacing: 0.025em;
}

.btn-sm {
    font-size: 0.8rem;
    padding: 0.375rem 0.75rem;
}

/* Espaciado mejorado para información básica */
.bg-light.rounded {
    border: 1px solid rgba(0,0,0,0.05);
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
    
    // Manejar botones de previsualizar flujo
    document.querySelectorAll('.previsualizar-flujo').forEach(btn => {
        btn.addEventListener('click', function() {
            const flujoId = this.dataset.flujoId;
            const flujoNombre = this.dataset.flujoNombre;
            
            // Actualizar título del modal
            document.getElementById('modalPrevisualizarFlujoLabel').innerHTML = 
                `<i class="fas fa-search me-2"></i>Previsualización: ${flujoNombre}`;
            
            // Mostrar modal
            const modal = new bootstrap.Modal(document.getElementById('modalPrevisualizarFlujo'));
            modal.show();
            
            // Cargar contenido del flujo
            cargarPrevisualizacionFlujo(flujoId);
        });
    });

    // Función para cargar la previsualización del flujo
    function cargarPrevisualizacionFlujo(flujoId) {
        const contenido = document.getElementById('contenido-previsualizacion');
        
        // Mostrar loading
        contenido.innerHTML = `
            <div class="text-center py-4">
                <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                <p class="mt-2">Cargando información del flujo...</p>
            </div>
        `;

        fetch(`/ejecucion/${flujoId}/previsualizar`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (!data.flujo) {
                    throw new Error('No se recibieron datos del flujo');
                }
                
                mostrarPrevisualizacionFlujo(data.flujo);
            })
            .catch(error => {
                console.error('Error:', error);
                contenido.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Error al cargar la información del flujo: ${error.message}
                    </div>
                `;
            });
    }

    // Función para mostrar la previsualización del flujo
    function mostrarPrevisualizacionFlujo(flujo) {
        const contenido = document.getElementById('contenido-previsualizacion');
        
        let html = `
            <!-- Información del flujo -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0">
                        <i class="fas fa-info-circle text-primary me-2"></i>
                        Información General
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Nombre:</strong> ${flujo.nombre}</p>
                            <p><strong>Tipo:</strong> ${flujo.tipo ? flujo.tipo.nombre : 'Sin tipo'}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Estado:</strong> <span class="badge bg-success">Activo</span></p>
                            <p><strong>Total Etapas:</strong> ${flujo.etapas.length}</p>
                        </div>
                    </div>
                    ${flujo.descripcion ? `<p><strong>Descripción:</strong> ${flujo.descripcion}</p>` : ''}
                </div>
            </div>

            <!-- Etapas del flujo -->
            <div class="mb-4">
                <h6 class="mb-3">
                    <i class="fas fa-list-ol text-primary me-2"></i>
                    Etapas del Flujo (${flujo.etapas.length})
                </h6>
        `;

        flujo.etapas.forEach((etapa, index) => {
            const totalTareas = etapa.tareas ? etapa.tareas.length : 0;
            const totalDocumentos = etapa.documentos ? etapa.documentos.length : 0;
            
            html += `
                <div class="card mb-3">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">
                                <span class="badge bg-primary me-2">${etapa.nro}</span>
                                ${etapa.nombre}
                            </h6>
                            <div>
                                <span class="badge bg-info me-1">${totalTareas} tareas</span>
                                <span class="badge bg-warning">${totalDocumentos} documentos</span>
                            </div>
                        </div>
                        ${etapa.descripcion ? `<small class="text-muted">${etapa.descripcion}</small>` : ''}
                    </div>
                    <div class="card-body">
                        <div class="row">
            `;

            // Mostrar tareas si existen
            if (totalTareas > 0) {
                html += `
                    <div class="col-md-6">
                        <h6 class="text-primary">
                            <i class="fas fa-tasks me-1"></i>Tareas (${totalTareas})
                        </h6>
                        <div class="list-group list-group-flush">
                `;
                
                etapa.tareas.forEach(tarea => {
                    html += `
                        <div class="list-group-item border-0 px-0">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-circle text-secondary me-2 mt-1" style="font-size: 0.5rem;"></i>
                                <div class="flex-grow-1">
                                    <strong>${tarea.nombre}</strong>
                                    ${tarea.descripcion ? `<br><small class="text-muted">${tarea.descripcion}</small>` : ''}
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                html += `
                        </div>
                    </div>
                `;
            }

            // Mostrar documentos si existen
            if (totalDocumentos > 0) {
                html += `
                    <div class="col-md-6">
                        <h6 class="text-primary">
                            <i class="fas fa-file-pdf me-1"></i>Documentos (${totalDocumentos})
                        </h6>
                        <div class="list-group list-group-flush">
                `;
                
                etapa.documentos.forEach(documento => {
                    html += `
                        <div class="list-group-item border-0 px-0">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-file-pdf text-danger me-2 mt-1"></i>
                                <div class="flex-grow-1">
                                    <strong>${documento.nombre}</strong>
                                    ${documento.descripcion ? `<br><small class="text-muted">${documento.descripcion}</small>` : ''}
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                html += `
                        </div>
                    </div>
                `;
            }

            // Si no hay tareas ni documentos
            if (totalTareas === 0 && totalDocumentos === 0) {
                html += `
                    <div class="col-12">
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-info-circle me-1"></i>
                            Esta etapa no tiene tareas ni documentos configurados
                        </div>
                    </div>
                `;
            }

            html += `
                        </div>
                    </div>
                </div>
            `;
        });

        html += `
            </div>

            <!-- Resumen -->
            <div class="card bg-light">
                <div class="card-body">
                    <h6 class="text-primary mb-3">
                        <i class="fas fa-chart-bar me-1"></i>Resumen del Flujo
                    </h6>
                    <div class="row text-center">
                        <div class="col-md-3">
                            <div class="p-3">
                                <i class="fas fa-list-ol fa-2x text-primary mb-2"></i>
                                <h5>${flujo.etapas.length}</h5>
                                <small class="text-muted">Etapas</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3">
                                <i class="fas fa-tasks fa-2x text-info mb-2"></i>
                                <h5>${flujo.etapas.reduce((total, etapa) => total + (etapa.tareas ? etapa.tareas.length : 0), 0)}</h5>
                                <small class="text-muted">Tareas Totales</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3">
                                <i class="fas fa-file-pdf fa-2x text-warning mb-2"></i>
                                <h5>${flujo.etapas.reduce((total, etapa) => total + (etapa.documentos ? etapa.documentos.length : 0), 0)}</h5>
                                <small class="text-muted">Documentos Totales</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3">
                                <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                                <h5>100%</h5>
                                <small class="text-muted">Configurado</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        contenido.innerHTML = html;
    }
    
    // Actualización inicial de botones
    updateButtons();
});
</script>
@endpush
