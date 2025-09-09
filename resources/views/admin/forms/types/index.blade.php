@extends('layouts.dashboard')

@section('title', 'Tipos de formularios')
@section('page-title', 'Tipos de formularios')
@section('page-subtitle', 'Administra todos los tipos de formularios registrados en el sistema')

@section('header-actions')
    <a href="{{ route('form-types.create') }}" class="btn btn-light">
        <i class="fas fa-plus me-2"></i>Nuevo Tipo
    </a>
@endsection

@section('content-area')
{{-- Mensajes de éxito --}}
@if(session('ok'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        {{ session('ok') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="fas fa-list-alt me-2"></i>
                Lista de Tipos de Formularios
            </h5>
            <div class="d-flex gap-2">
                <form method="GET" action="{{ route('form-types.index') }}" class="d-flex">
                    <input type="text" name="search" class="form-control form-control-sm me-2" 
                           placeholder="Buscar tipo..." value="{{ request('search') }}">
                    <button type="submit" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Empresa</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($types as $t)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $t->nombre }}</div>
                            <small class="text-muted">ID: {{ $t->id }}</small>
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $t->empresa->nombre ?? 'Sin empresa' }}</div>
                            <small class="text-muted">ID: {{ $t->id_emp }}</small>
                        </td>
                        <td>
                            <span class="badge bg-{{ $t->estado ? 'success' : 'secondary' }}">
                                {{ $t->estado ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td>
                            <div class="custom-dropdown">
                                <button type="button" class="btn btn-outline-primary btn-sm dropdown-btn" 
                                        onclick="toggleDropdown(this)">
                                    <i class="fas fa-cog"></i>
                                    <i class="fas fa-chevron-down ms-1"></i>
                                </button>
                                <div class="custom-dropdown-menu">
                                    <a class="dropdown-item" href="{{ route('form-types.edit', $t) }}">
                                        <i class="fas fa-edit me-2"></i>Editar
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <form action="{{ route('form-types.destroy', $t) }}" method="post" class="d-inline">
                                        @csrf @method('delete')
                                        <button type="submit" class="dropdown-item text-danger" 
                                                onclick="return confirm('¿Está seguro de que desea eliminar este tipo?')">
                                            <i class="fas fa-trash me-2"></i>Eliminar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center py-5">
                            <i class="fas fa-list-alt fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No hay tipos de formularios registrados</h5>
                            <p class="text-muted">Comienza creando tu primer tipo de formulario</p>
                            <a href="{{ route('form-types.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Crear Primer Tipo
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($types->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $types->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

@push('styles')
<style>
.custom-dropdown {
    position: relative;
    display: inline-block;
}

.custom-dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    z-index: 1050;
    min-width: 160px;
    background-color: #fff;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.25);
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.2s ease;
    margin-top: 2px;
    max-height: 200px;
    overflow-y: auto;
}

.custom-dropdown.show .custom-dropdown-menu {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.dropdown-item {
    display: block;
    width: 100%;
    padding: 0.5rem 1rem;
    clear: both;
    font-weight: 400;
    color: #212529;
    text-decoration: none;
    white-space: nowrap;
    background-color: transparent;
    border: 0;
    transition: background-color 0.15s ease-in-out;
}

.dropdown-item:hover,
.dropdown-item:focus {
    color: #1e2125;
    background-color: #e9ecef;
    text-decoration: none;
}

.dropdown-item.text-danger {
    color: #dc3545;
}

.dropdown-item.text-danger:hover {
    color: #fff;
    background-color: #dc3545;
}

.dropdown-divider {
    height: 0;
    margin: 0.5rem 0;
    overflow: hidden;
    border-top: 1px solid #dee2e6;
}

.dropdown-btn .fa-chevron-down {
    transition: transform 0.2s ease;
}

.custom-dropdown.show .dropdown-btn .fa-chevron-down {
    transform: rotate(180deg);
}
</style>
@endpush

@push('scripts')
<script>
// Manejo del dropdown personalizado
function toggleDropdown(button) {
    const dropdown = button.closest('.custom-dropdown');
    const isOpen = dropdown.classList.contains('show');
    
    // Cerrar todos los dropdowns abiertos
    closeAllDropdowns();
    
    // Si no estaba abierto, abrirlo
    if (!isOpen) {
        dropdown.classList.add('show');
        
        // Ajustar posición si se sale de la pantalla
        const menu = dropdown.querySelector('.custom-dropdown-menu');
        const rect = menu.getBoundingClientRect();
        const windowHeight = window.innerHeight;
        
        // Si el dropdown se sale por abajo, mostrarlo hacia arriba
        if (rect.bottom > windowHeight) {
            menu.style.top = 'auto';
            menu.style.bottom = '100%';
            menu.style.marginTop = '0';
            menu.style.marginBottom = '2px';
        } else {
            menu.style.top = '100%';
            menu.style.bottom = 'auto';
            menu.style.marginTop = '2px';
            menu.style.marginBottom = '0';
        }
    }
}

function closeAllDropdowns() {
    document.querySelectorAll('.custom-dropdown.show').forEach(dropdown => {
        dropdown.classList.remove('show');
    });
}

// Cerrar dropdown al hacer clic fuera
document.addEventListener('click', function(event) {
    if (!event.target.closest('.custom-dropdown')) {
        closeAllDropdowns();
    }
});

// Cerrar dropdown al presionar ESC
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeAllDropdowns();
    }
});
</script>
@endpush
