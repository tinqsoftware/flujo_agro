@extends('layouts.dashboard')
@section('title','Ejecución de Flujos')
@section('page-title','Ejecución de Flujos')
@section('page-subtitle','Consulta los flujos disponibles y selecciona uno para ejecutar')

@section('content-area')

@if($isSuper)
    <!-- Aviso para SUPERADMIN -->
    <div class="alert alert-info border-0 shadow-sm mb-4">
        <div class="d-flex align-items-start">
            <div class="flex-shrink-0">
                <i class="fas fa-info-circle fa-lg mt-1"></i>
            </div>
            <div class="flex-grow-1 ms-3">
                <h6 class="alert-heading mb-2">Modo Supervisión - SUPERADMIN</h6>
                <p class="mb-1">Como SUPERADMIN, solo puedes <strong>visualizar</strong> los flujos de todas las empresas para supervisión.</p>
                <p class="mb-0"><small class="text-muted">La ejecución de flujos debe ser realizada por usuarios de la empresa correspondiente</small></p>
            </div>
        </div>
    </div>
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
                            <button id="ver-detalle-btn" class="btn btn-outline-info w-100" disabled>
                                <i class="fas fa-eye me-2"></i>Ver Estado
                            </button>
                            
                            @if(!$isSuper)
                            <div class="col-12">
                                <button id="ejecutar-btn" class="btn btn-success w-100" disabled>
                                    <i class="fas fa-play me-2"></i>Nueva Ejecución
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
                                    <h5 class="card-title mb-1 text-primary fw-bold">{{ $detalleEjecucion->flujo->nombre }}</h5>
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
                                    <a href="/ejecucion/{{ $detalleEjecucion->flujo->id }}" class="btn btn-outline-info btn-sm w-100">
                                        <i class="fas fa-eye me-2"></i>Ver Estado de Ejecución
                                    </a>
                                @else
                                    <!-- Usuarios de empresa pueden continuar -->
                                    <a href="/ejecucion/{{ $detalleEjecucion->flujo->id }}/ejecutar" class="btn btn-warning btn-sm w-100">
                                        <i class="fas fa-play me-2"></i>Continuar Ejecución
                                    </a>
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
                                    <h5 class="card-title mb-1 text-primary fw-bold">{{ $detalleEjecucion->flujo->nombre }}</h5>
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

/* Estilos para ejecuciones en proceso y terminadas */
.border-warning {
    border: 2px solid #ffc107 !important;
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

    // Verificar que los elementos básicos existan
    if (!flujoSelector || !verDetalleBtn) {
        return;
    }

    // Para usuarios no-super, verificar que existe el botón ejecutar
    if (!isSuper && !ejecutarBtn) {
        return;
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
            
            if (!confirm('¿Estás seguro de que quieres iniciar una nueva ejecución de este flujo?\n\nSe creará una instancia independiente que podrás ejecutar por separado.')) {
                return;
            }
            
            const url = `/ejecucion/${selectedId}/ejecutar`;
            window.location.href = url;
        });
    }
    
    // Actualización inicial de botones
    updateButtons();
});
</script>
@endpush
