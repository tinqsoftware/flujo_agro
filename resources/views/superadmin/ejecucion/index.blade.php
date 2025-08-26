@extends('layouts.dashboard')
@section('title','Ejecución de Flujos')
@section('page-title','Ejecución de Flujos')
@section('page-subtitle','Selecciona un flujo para comenzar la ejecución')

@section('content-area')
<div class="header-section mb-4">
    <div class="row align-items-center">
        <div class="col">
            <h2 class="h4 mb-1 text-primary">Flujos Disponibles</h2>
            <p class="text-muted mb-0">Selecciona un flujo para comenzar su ejecución</p>
        </div>
    </div>
</div>


<!-- Lista de Flujos -->
<div class="row g-4">
    @forelse($flujos as $flujo)
        <div class="col-12 col-lg-6 col-xl-4">
            <div class="card h-100 shadow-sm border-0">
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
                        <h6 class="text-muted small mb-2">Etapas:</h6>
                        @forelse($flujo->etapas as $etapa)
                            <div class="d-flex justify-content-between align-items-center py-1">
                                <span class="small">{{ $etapa->nro }}. {{ $etapa->nombre }}</span>
                                <div class="text-muted small">
                                    <i class="fas fa-tasks me-1"></i>{{ $etapa->tareas->count() }}
                                    <i class="fas fa-file ms-2 me-1"></i>{{ $etapa->documentos->count() }}
                                </div>
                            </div>
                        @empty
                            <div class="text-muted small">Sin etapas configuradas</div>
                        @endforelse
                    </div>

                    <!-- Botón de ejecución -->
                    <div class="d-grid">
                        @if($flujo->etapas->count() > 0)
                            <a href="{{ route('ejecucion.ejecutar', $flujo->id) }}" class="btn btn-primary">
                                <i class="fas fa-play me-2"></i>Ejecutar Flujo
                            </a>
                        @else
                            <button class="btn btn-secondary" disabled>
                                <i class="fas fa-exclamation-triangle me-2"></i>Sin etapas
                            </button>
                        @endif
                    </div>
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
</style>
@endsection
