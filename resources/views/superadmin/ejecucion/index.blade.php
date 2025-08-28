@extends('layouts.dashboard')
@section('title','Ejecución de Flujos')
@section('page-title','Ejecución de Flujos')
@section('page-subtitle','Consulta los flujos disponibles y selecciona uno para ejecutar')

@section('content-area')
<div class="header-section mb-4">
    <div class="row align-items-center">
        <div class="col">
            <h2 class="h4 mb-1 text-white">Flujos Disponibles</h2>
            <p class="text-white-50 mb-0">Revisa los flujos disponibles y selecciona uno para ejecutar</p>
        </div>
    </div>
</div>

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
                                <!-- Debug: Flujo ID: {{ $flujo->id }}, Estado: {{ $flujo->estado }}, Etapas: {{ $flujo->etapas->count() }} -->
                                @if($flujo->etapas->count() > 0)
                                    <option value="{{ $flujo->id }}" data-nombre="{{ $flujo->nombre }}" data-etapas="{{ $flujo->total_etapas }}">
                                        {{ $flujo->nombre }} ({{ $flujo->total_etapas }} etapas)
                                    </option>
                                @endif
                            @endforeach
                        </select>
                        <div class="row g-2">
                            <div class="col-6">
                                <button id="ver-detalle-btn" class="btn btn-outline-info w-100" disabled>
                                    <i class="fas fa-eye me-2"></i>Ver Estado
                                </button>
                            </div>
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
}

#ejecutar-btn:not(:disabled):hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
}

#ver-detalle-btn:not(:disabled):hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(23, 162, 184, 0.3);
}
</style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const flujoSelector = document.getElementById('flujo-selector');
    const ejecutarBtn = document.getElementById('ejecutar-btn');
    const verDetalleBtn = document.getElementById('ver-detalle-btn');
    const flujoCards = document.querySelectorAll('.flujo-card');

    // Verificar que todos los elementos existen
    console.log('Elementos encontrados:', {
        flujoSelector: !!flujoSelector,
        ejecutarBtn: !!ejecutarBtn,
        verDetalleBtn: !!verDetalleBtn,
        flujoCards: flujoCards.length
    });

    // Manejar cambio en el selector
    flujoSelector.addEventListener('change', function() {
        const selectedId = this.value;
        
        console.log('Flujo seleccionado:', selectedId); // Debug
        
        // Habilitar/deshabilitar botones
        ejecutarBtn.disabled = !selectedId;
        verDetalleBtn.disabled = !selectedId;
        
        console.log('Botones habilitados:', !selectedId ? 'No' : 'Sí'); // Debug
        
        // Actualizar clases de las tarjetas
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

    // Manejar clic en las tarjetas
    flujoCards.forEach(card => {
        card.addEventListener('click', function() {
            const flujoId = this.dataset.flujoId;
            const option = flujoSelector.querySelector(`option[value="${flujoId}"]`);
            
            console.log('Tarjeta clickeada:', flujoId, 'Opción encontrada:', !!option); // Debug
            
            if (option) {
                flujoSelector.value = flujoId;
                flujoSelector.dispatchEvent(new Event('change'));
            }
        });
    });

    // Manejar clic en el botón ver detalle
    verDetalleBtn.addEventListener('click', function() {
        const selectedId = flujoSelector.value;
        console.log('Botón Ver Estado clickeado, ID seleccionado:', selectedId); // Debug
        
        if (selectedId) {
            // Agregar animación de carga
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Cargando...';
            this.disabled = true;
            
            const url = "{{ route('ejecucion.index') }}/" + selectedId;
            console.log('Redirigiendo a:', url); // Debug
            
            // Redirigir al detalle del flujo usando la ruta correcta
            window.location.href = url;
        }
    });

    // Manejar clic en el botón ejecutar
    ejecutarBtn.addEventListener('click', function() {
        const selectedId = flujoSelector.value;
        console.log('Botón Ejecutar clickeado, ID seleccionado:', selectedId); // Debug
        
        if (selectedId) {
            // Agregar animación de carga
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Ejecutando...';
            this.disabled = true;
            
            const url = "{{ route('ejecucion.index') }}/" + selectedId + "/ejecutar";
            console.log('Redirigiendo a:', url); // Debug
            
            // Redirigir a la ejecución usando la ruta correcta de Laravel
            window.location.href = url;
        }
    });
});
</script>
@endsection
