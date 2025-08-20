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
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-primary dropdown-toggle" 
                                        data-bs-toggle="dropdown">
                                    <i class="fas fa-cog"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="#">
                                            <i class="fas fa-eye me-2"></i>Ver detalles
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('fichas.edit', $ficha) }}">
                                            <i class="fas fa-edit me-2"></i>Editar
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item text-danger" href="#" 
                                           onclick="confirmarEliminacion({{ $ficha->id }}, '{{ $ficha->nombre }}')">
                                            <i class="fas fa-trash me-2"></i>Eliminar
                                        </a>
                                    </li>
                                </ul>
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

<!-- Modal de confirmación de eliminación -->
<div class="modal fade" id="confirmarEliminacionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                    Confirmar Eliminación
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que deseas eliminar la ficha <strong id="nombreFicha"></strong>?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Esta acción eliminará:
                    <ul class="mb-0 mt-2">
                        <li>Todos los usuarios de la ficha</li>
                        <li>Todos los flujos y procesos</li>
                        <li>Todos los documentos asociados</li>
                    </ul>
                </div>
                <p class="text-danger"><strong>Esta acción no se puede deshacer.</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancelar
                </button>
                <form id="formEliminar" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>Eliminar Ficha
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function confirmarEliminacion(ficha, nombreFicha) {
    document.getElementById('nombreFicha').textContent = nombreFicha;
    document.getElementById('formEliminar').action = `/fichas/${fichaId}`;
    
    const modal = new bootstrap.Modal(document.getElementById('confirmarEliminacionModal'));
    modal.show();
}

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
