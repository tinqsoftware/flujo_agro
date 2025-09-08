@extends('layouts.dashboard')

@section('title', 'Gestión de fichas')
@section('page-title', 'Gestión de Fichas')
@section('page-subtitle', 'Administra todas las fichas registradas en el sistema')


@section('header-actions')
    <a href="{{ route('fichas.create') }}" class="btn btn-light">
        <i class="fas fa-plus me-2"></i>Nueva Ficha
    </a>
@endsection

@section('content-area')
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="fas fa-building me-2"></i>
                Lista de Fichas
            </h5>
            <div class="d-flex gap-2">
                <form method="GET" action="{{ route('fichas.index') }}" class="d-flex">
                    <input type="text" name="search" class="form-control form-control-sm me-2" 
                           placeholder="Buscar ficha..." value="{{ request('search') }}">
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
                        @if(Auth::user()->rol->nombre === 'SUPERADMIN' )
                        <th>Empresa</th>
                        @endif
                        <th>Tipo</th>
                        <th>Estado</th>
                        <th>Usuario</th>
                        <th>Fecha Registro</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($fichas as $ficha)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $ficha->nombre }}</div>
                            <small class="text-muted">ID: {{ $ficha->id }}</small>
                        </td>
                        @if(Auth::user()->rol->nombre === 'SUPERADMIN')
                        <td>
                            <div class="d-flex align-items-center">
                                @if($ficha->empresa->ruta_logo)
                                    <img src="{{ Storage::url($ficha->empresa->ruta_logo) }}" 
                                         class="rounded me-2" width="40" height="40" alt="Logo">
                                @else
                                    <div class="bg-primary rounded d-flex align-items-center justify-content-center me-2" 
                                         style="width: 40px; height: 40px;">
                                        <i class="fas fa-building text-white"></i>
                                    </div>
                                @endif
                                <div>
                                    <div class="fw-semibold">{{ $ficha->empresa->nombre }}</div>
                                </div>
                            </div>
                        </td>
                        @endif
                        <td>
                            <span class="badge bg-secondary">{{ $ficha->tipo }}</span>
                        </td>
                         <td>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" 
                                       id="estado{{ $ficha->id }}" 
                                       {{ $ficha->estado ? 'checked' : '' }}
                                       onchange="toggleEstado({{ $ficha->id }}, this.checked)">
                                <label class="form-check-label" for="estado{{ $ficha->id }}">
                                    <span class="badge {{ $ficha->estado ? 'bg-success' : 'bg-danger' }}">
                                        {{ $ficha->estado ? 'Activa' : 'Inactiva' }}
                                    </span>
                                </label>
                            </div>
                        </td>
                        <td>
                            @if($ficha->userCreate)
                                <div>
                                    <div class="fw-semibold">{{ $ficha->userCreate->nombres }} {{ $ficha->userCreate->apellidos }}</div>
                                </div>
                            @endif
                        </td>
                        <td>
                            <div>
                                <div>{{ $ficha->created_at->format('d/m/Y') }}</div>
                                <small class="text-muted">{{ $ficha->created_at->format('H:i') }}</small>
                            </div>
                        </td>
                        <td>
                            <div class="custom-dropdown">
                                <button type="button" class="btn btn-outline-primary btn-sm dropdown-btn" 
                                        onclick="toggleDropdown(this)">
                                    <i class="fas fa-cog"></i>
                                    <i class="fas fa-chevron-down ms-1"></i>
                                </button>
                                <div class="custom-dropdown-menu">
                                    <a class="dropdown-item" href="{{ route('fichas.show', $ficha) }}">
                                        <i class="fas fa-eye me-2"></i>Ver detalles
                                    </a>
                                    <a class="dropdown-item" href="{{ route('fichas.edit', $ficha) }}">
                                        <i class="fas fa-edit me-2"></i>Editar
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <i class="fas fa-building fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No hay fichas registradas</h5>
                            <p class="text-muted">Comienza creando tu primera ficha</p>
                            <a href="{{ route('fichas.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Crear Primera Ficha
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($fichas->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $fichas->links() }}
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
    /* Evitar que se corte */
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

function toggleEstado(fichaId, estado) {
    fetch(`/fichas/${fichaId}/toggle-estado`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ estado: estado })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Actualizar la etiqueta del estado
            const badge = document.querySelector(`#estado${fichaId} + label .badge`);
            if (estado) {
                badge.className = 'badge bg-success';
                badge.textContent = 'Activa';
            } else {
                badge.className = 'badge bg-danger';
                badge.textContent = 'Inactiva';
            }
            
            // Mostrar mensaje de éxito
            showAlert('success', data.message);
        } else {
            // Revertir el switch si hay error
            document.getElementById(`estado${fichaId}`).checked = !estado;
            showAlert('error', data.message || 'Error al cambiar el estado');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // Revertir el switch si hay error
        document.getElementById(`estado${fichaId}`).checked = !estado;
        showAlert('error', 'Error de conexión');
    });
}

function showAlert(type, message) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const icon = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
    
    const alert = document.createElement('div');
    alert.className = `alert ${alertClass} alert-dismissible fade show`;
    alert.innerHTML = `
        <i class="${icon} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Insertar al inicio del content-area
    const contentArea = document.querySelector('.content-area');
    contentArea.insertBefore(alert, contentArea.firstChild);
    
    // Auto-ocultar después de 5 segundos
    setTimeout(() => {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    }, 5000);
}
</script>
@endpush
