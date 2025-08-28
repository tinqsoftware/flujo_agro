@extends('layouts.dashboard')
@section('title','Ejecución de Flujos')
@section('page-title','Ejecución de Flujos')
@section('page-subtitle','Consulta los flujos disponibles y selecciona uno para ejecutar')

@section('content-area')


<!-- Sección de Selección de Flujo -->
<div class="selection-section mb-4">
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h5 class="mb-2">
                        <i class="fas fa-play-circle text-success me-2"></i>
                        Ejecutar Flujo
                    </h5>
                    <p class="text-muted mb-0">Selecciona un flujo para ver su estado actual o ejecutarlo</p>
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
                            
                            <div class="col-6">
                                <button id="ejecutar-btn" class="btn btn-success w-100" disabled>
                                    <i class="fas fa-play me-2"></i>Ejecutar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Lista de Flujos -->
<div class="mb-4">
    <div class="d-flex align-items-center mb-4">
        <h4 class="mb-0">
            <i class="fas fa-rocket text-primary me-2"></i>
            Flujos Listos para Ejecutar
        </h4>
        <span class="badge bg-primary ms-2">{{ $flujos->total() }}</span>
    </div>
</div>

<div class="row g-4">
    @forelse($flujos as $flujo)
        <div class="col-12 col-lg-6 col-xl-4">
            <div class="card h-100 shadow-sm border-0 flujo-card" data-flujo-id="{{ $flujo->id }}">
                <div class="card-body p-4">
                    <!-- Header del flujo -->
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="flex-grow-1">
                            <h5 class="card-title mb-1 text-primary fw-bold">{{ $flujo->nombre }}</h5>
                            <div class="text-muted small">
                                <span class="badge bg-light text-dark">{{ $flujo->tipo->nombre ?? 'Sin tipo' }}</span>
                                @if($isSuper)
                                    <span class="badge bg-secondary ms-1">{{ $flujo->empresa->nombre ?? 'Sin empresa' }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="status-indicator">
                            @if($flujo->etapas->count() > 0)
                                <span class="badge bg-success">
                                    <i class="fas fa-check-circle me-1"></i>Listo
                                </span>
                            @else
                                <span class="badge bg-warning">
                                    <i class="fas fa-exclamation-triangle me-1"></i>Incompleto
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Descripción del flujo -->
                    @if($flujo->descripcion)
                        <p class="text-muted small mb-3">
                            {{ \Illuminate\Support\Str::limit($flujo->descripcion, 120) }}
                        </p>
                    @endif

                    <!-- Contadores -->
                    <div class="row text-center mb-3">
                        <div class="col-6">
                            <div class="p-2 bg-light rounded">
                                <i class="fas fa-list-ol text-primary d-block mb-1"></i>
                                <div class="fw-bold">{{ $flujo->total_etapas }}</div>
                                <small class="text-muted">etapas</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 bg-light rounded">
                                <i class="fas fa-file-alt text-info d-block mb-1"></i>
                                <div class="fw-bold">{{ $flujo->total_documentos }}</div>
                                <small class="text-muted">documentos</small>
                            </div>
                        </div>
                    </div>

                    <!-- Etapas del flujo -->
                    <div class="mb-3">
                        <h6 class="text-muted small mb-2">Etapas del proceso:</h6>
                        <div class="etapas-preview">
                            @forelse($flujo->etapas->take(3) as $etapa)
                                <div class="d-flex justify-content-between align-items-center py-1 border-bottom border-light">
                                    <span class="small">{{ $etapa->nro }}. {{ $etapa->nombre }}</span>
                                    <div class="text-muted small">
                                        <i class="fas fa-tasks me-1"></i>{{ $etapa->tareas->count() }}
                                        <i class="fas fa-file ms-2 me-1"></i>{{ $etapa->documentos->count() }}
                                    </div>
                                </div>
                            @empty
                                <div class="text-muted small text-center py-2">
                                    <i class="fas fa-info-circle me-1"></i>Sin etapas configuradas
                                </div>
                            @endforelse
                            @if($flujo->etapas->count() > 3)
                                <div class="text-center mt-2">
                                    <small class="text-muted">
                                        <i class="fas fa-ellipsis-h me-1"></i>
                                        y {{ $flujo->etapas->count() - 3 }} etapas más
                                    </small>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Indicador de selección -->
                    @if($flujo->etapas->count() > 0)
                        <div class="text-center">
                            <small class="text-muted">
                                <i class="fas fa-mouse-pointer me-1"></i>
                                Selecciona este flujo arriba para ejecutar
                            </small>
                        </div>
                    @else
                        <div class="text-center">
                            <small class="text-warning">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                Este flujo necesita configuración
                            </small>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-project-diagram fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No hay flujos disponibles</h5>
                    <p class="text-muted mb-0">
                        @if($q)
                            No se encontraron flujos que coincidan con tu búsqueda.
                        @else
                            No hay flujos activos disponibles para ejecución.
                        @endif
                    </p>
                    @if($q)
                        <a href="{{ route('ejecucion.index') }}" class="btn btn-outline-primary mt-3">
                            <i class="fas fa-undo me-1"></i>Limpiar búsqueda
                        </a>
                    @endif
                </div>
            </div>
        </div>
    @endforelse
</div>

<!-- Paginación -->
@if($flujos->hasPages())
    <div class="mt-4">
        {{ $flujos->links() }}
    </div>
@endif

<!-- Sección de Flujos en Proceso y Terminados -->
@if($flujosEnProceso->count() > 0)
    <div class="mt-5">
        <div class="d-flex align-items-center mb-4">
            <h4 class="mb-0">
                <i class="fas fa-history text-info me-2"></i>
                Flujos en Proceso y Terminados
            </h4>
            <span class="badge bg-secondary ms-2">{{ $flujosEnProceso->count() }}</span>
        </div>

        <div class="row g-4">
            @foreach($flujosEnProceso as $flujo)
                <div class="col-12 col-lg-6 col-xl-4">
                    <div class="card h-100 shadow-sm border-0 {{ $flujo->estado == 2 ? 'border-warning' : 'border-success' }}">
                        <div class="card-body p-4">
                            <!-- Header del flujo -->
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="flex-grow-1">
                                    <h5 class="card-title mb-1 text-primary fw-bold">{{ $flujo->nombre }}</h5>
                                    <div class="text-muted small">
                                        <span class="badge bg-light text-dark">{{ $flujo->tipo->nombre ?? 'Sin tipo' }}</span>
                                        @if($isSuper)
                                            <span class="badge bg-secondary ms-1">{{ $flujo->empresa->nombre ?? 'Sin empresa' }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="status-indicator">
                                    @if($flujo->estado == 2)
                                        <span class="badge bg-warning">
                                            <i class="fas fa-play me-1"></i>En Ejecución
                                        </span>
                                    @elseif($flujo->estado == 3)
                                        <span class="badge bg-success">
                                            <i class="fas fa-check-circle me-1"></i>Terminado
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Descripción del flujo -->
                            @if($flujo->descripcion)
                                <p class="text-muted small mb-3">
                                    {{ \Illuminate\Support\Str::limit($flujo->descripcion, 120) }}
                                </p>
                            @endif

                            <!-- Contadores -->
                            <div class="row text-center mb-3">
                                <div class="col-6">
                                    <div class="p-2 bg-light rounded">
                                        <i class="fas fa-list-ol text-primary d-block mb-1"></i>
                                        <div class="fw-bold">{{ $flujo->total_etapas }}</div>
                                        <small class="text-muted">etapas</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-2 bg-light rounded">
                                        <i class="fas fa-file-alt text-info d-block mb-1"></i>
                                        <div class="fw-bold">{{ $flujo->total_documentos }}</div>
                                        <small class="text-muted">documentos</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Progreso (solo para flujos en ejecución) -->
                            @if($flujo->estado == 2)
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <small class="text-muted">Progreso</small>
                                        <small class="text-muted">En desarrollo...</small>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-warning progress-bar-animated" role="progressbar" style="width: 45%"></div>
                                    </div>
                                </div>
                            @endif

                            <!-- Información de fecha -->
                            <div class="mb-3">
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>
                                    @if($flujo->estado == 2)
                                        Iniciado: {{ $flujo->updated_at->diffForHumans() }}
                                    @else
                                        Terminado: {{ $flujo->updated_at->diffForHumans() }}
                                    @endif
                                </small>
                            </div>

                            <!-- Botones de acción -->
                            <div class="text-center">
                                @if($flujo->estado == 2)
                                    <!-- Flujo en ejecución - botón para continuar -->
                                    <a href="/ejecucion/{{ $flujo->id }}/ejecutar" class="btn btn-warning btn-sm w-100">
                                        <i class="fas fa-play me-2"></i>Continuar Ejecución
                                    </a>
                                @else
                                    <!-- Flujo terminado - botón para ver detalles -->
                                    <a href="/ejecucion/{{ $flujo->id }}" class="btn btn-outline-success btn-sm w-100">
                                        <i class="fas fa-eye me-2"></i>Ver Detalles
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

@endsection

@push('styles')
<style>
.header-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    margin: -1.5rem -1.5rem 0 -1.5rem;
    padding: 2rem 1.5rem;
    color: white;
    border-radius: 0.5rem 0.5rem 0 0;
}

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

.flujo-card {
    cursor: pointer;
    position: relative;
}

.flujo-card.selected {
    border: 2px solid #28a745 !important;
    box-shadow: 0 4px 20px rgba(40, 167, 69, 0.3) !important;
}

.flujo-card.selected::before {
    content: '✓';
    position: absolute;
    top: 10px;
    right: 10px;
    background: #28a745;
    color: white;
    border-radius: 50%;
    width: 25px;
    height: 25px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 14px;
    z-index: 10;
}

.badge {
    font-size: 0.7rem;
}

.etapas-preview {
    max-height: 120px;
    overflow-y: auto;
}

.status-indicator .badge {
    font-size: 0.65rem;
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

/* Estilos para flujos en proceso y terminados */
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
    const flujoCards = document.querySelectorAll('.flujo-card');

    // Verificar que todos los elementos existan
    if (!flujoSelector || !ejecutarBtn || !verDetalleBtn) {
        return;
    }

    // Función para actualizar estado de botones
    function updateButtons() {
        const selectedValue = flujoSelector.value;
        const shouldEnable = selectedValue !== '' && selectedValue !== null && selectedValue !== undefined;
        
        ejecutarBtn.disabled = !shouldEnable;
        verDetalleBtn.disabled = !shouldEnable;
    }

    // Manejar cambio en el selector
    flujoSelector.addEventListener('change', function(e) {
        updateButtons();
        
        // Actualizar clases de las tarjetas
        const selectedId = e.target.value;
        flujoCards.forEach(card => {
            if (card.dataset.flujoId === selectedId) {
                card.classList.add('selected');
            } else {
                card.classList.remove('selected');
            }
        });
        
        // Scroll hasta la tarjeta seleccionada
        if (selectedId) {
            const selectedCard = document.querySelector(`[data-flujo-id="${selectedId}"]`);
            if (selectedCard) {
                selectedCard.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'center' 
                });
            }
        }
    });

    // Eventos para las tarjetas
    flujoCards.forEach((card) => {
        card.addEventListener('click', function() {
            const flujoId = this.dataset.flujoId;
            const option = flujoSelector.querySelector(`option[value="${flujoId}"]`);
            
            if (option) {
                flujoSelector.value = flujoId;
                const changeEvent = new Event('change', { bubbles: true });
                flujoSelector.dispatchEvent(changeEvent);
            }
        });
    });

    // Eventos para los botones
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

    ejecutarBtn.addEventListener('click', function(e) {
        e.preventDefault();
        const selectedId = flujoSelector.value;
        
        if (!selectedId) {
            alert('Por favor selecciona un flujo primero');
            return;
        }
        
        if (!confirm('¿Estás seguro de que quieres ejecutar este flujo?')) {
            return;
        }
        
        const url = `/ejecucion/${selectedId}/ejecutar`;
        window.location.href = url;
    });
    
    // Actualización inicial de botones
    updateButtons();
});
</script>
@endpush
