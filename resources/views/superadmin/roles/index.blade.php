@extends('layouts.dashboard')

@section('title', 'Gestión de Roles - AGROEMSE')
@section('page-title', 'Configuración Global')
@section('page-subtitle', 'Administra los roles del sistema')

@section('header-actions')
    @if(Auth::user()->rol->nombre === 'SUPERADMIN')
        <a href="{{ route('roles.create') }}" class="btn btn-light">
            <i class="fas fa-plus me-2"></i>Nuevo Rol
        </a>
    @endif
@endsection

@section('content-area')
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-users-cog me-2"></i>
                    Roles del Sistema
                </h5>
                <small class="text-muted">
                    Los roles definen los permisos de acceso al sistema
                </small>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Rol</th>
                            <th>Descripción</th>
                            <th>Estado</th>
                            <th>Usuarios Asignados</th>
                            <th>Fecha Creación</th>
                            @if(Auth::user()->rol->nombre === 'SUPERADMIN')
                            <th>Acciones</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($roles as $rol)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    @php
                                        $iconClass = '';
                                        $badgeClass = '';
                                        switch($rol->nombre) {
                                            case 'SUPERADMIN':
                                                $iconClass = 'fas fa-crown text-warning';
                                                $badgeClass = 'bg-warning';
                                                break;
                                            case 'ADMINISTRADOR':
                                                $iconClass = 'fas fa-user-tie text-primary';
                                                $badgeClass = 'bg-primary';
                                                break;
                                            case 'ADMINISTRATIVO':
                                                $iconClass = 'fas fa-user text-info';
                                                $badgeClass = 'bg-info';
                                                break;
                                            default:
                                                $iconClass = 'fas fa-user-tag text-secondary';
                                                $badgeClass = 'bg-secondary';
                                        }
                                    @endphp
                                    <i class="{{ $iconClass }} me-2"></i>
                                    <div>
                                        <div class="fw-semibold">{{ $rol->nombre }}</div>
                                        @if(Auth::user()->rol->nombre === 'SUPERADMIN')
                                        <span class="badge {{ $badgeClass }} badge-sm">ID: {{ $rol->id }}</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="text-wrap" style="max-width: 300px;">
                                    {{ $rol->descripcion }}
                                </div>
                            </td>
                            <td>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" 
                                        id="estado{{ $rol->id }}" 
                                        {{ $rol->estado ? 'checked' : '' }}
                                        onchange="toggleEstado({{ $rol->id }}, this.checked)"
                                        {{ in_array($rol->nombre, ['SUPERADMIN', 'ADMINISTRADOR', 'ADMINISTRATIVO']) ? 'disabled' : '' }}>
                                    <label class="form-check-label" for="estado{{ $rol->id }}">
                                        <span class="badge {{ $rol->estado ? 'bg-success' : 'bg-danger' }}">
                                            {{ $rol->estado ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </label>
                                </div>
                                @if(in_array($rol->nombre, ['SUPERADMIN', 'ADMINISTRADOR', 'ADMINISTRATIVO']))
                                    <small class="text-muted d-block">Rol del sistema</small>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="fw-semibold me-2">{{ $rol->users->count() }}</span>
                                    @if(Auth::user()->rol->nombre === 'SUPERADMIN')
                                        @if($rol->users->count() > 0)
                                            <button type="button" class="btn btn-outline-info btn-sm" 
                                                    data-bs-toggle="modal" data-bs-target="#usuariosModal{{ $rol->id }}">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        @endif
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div>
                                    <div>{{ $rol->created_at->format('d/m/Y') }}</div>
                                    <small class="text-muted">{{ $rol->created_at->format('H:i') }}</small>
                                </div>
                            </td>
                            @if(Auth::user()->rol->nombre === 'SUPERADMIN')
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('roles.edit', $rol) }}" 
                                    class="btn btn-outline-warning" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @if(!in_array($rol->nombre, ['SUPERADMIN', 'ADMINISTRADOR', 'ADMINISTRATIVO']))
                                        <button type="button" class="btn btn-outline-danger" 
                                                onclick="confirmarEliminacion({{ $rol->id }}, '{{ $rol->nombre }}')" 
                                                title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    @else
                                        <button type="button" class="btn btn-outline-secondary" disabled title="Rol del sistema">
                                            <i class="fas fa-lock"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                            @endif
                        </tr>

                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <i class="fas fa-users-cog fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No hay roles configurados</h5>
                                <p class="text-muted">Comienza creando el primer rol personalizado</p>
                                <a href="{{ route('roles.create') }}" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Crear Primer Rol
                                </a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($roles->hasPages())
                <div class="d-flex justify-content-center mt-4">
                    {{ $roles->links() }}
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
                    <p>¿Estás seguro de que deseas eliminar el rol <strong id="nombreRol"></strong>?</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Importante:</strong> Solo se pueden eliminar roles que no tengan usuarios asignados.
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
                            <i class="fas fa-trash me-2"></i>Eliminar Rol
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>



    <!-- Modal para mostrar usuarios -->
    @if($rol->users->count() > 0)
    <div class="modal fade" id="usuariosModal{{ $rol->id }}" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="{{ $iconClass }} me-2"></i>
                        Usuarios con rol {{ $rol->nombre }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Usuario</th>
                                    <th>Email</th>
                                    <th>Empresa</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rol->users as $usuario)
                                <tr>
                                    <td>{{ $usuario->nombres }} {{ $usuario->apellidos }}</td>
                                    <td>{{ $usuario->email }}</td>
                                    <td>{{ $usuario->empresa->nombre ?? 'Sin empresa' }}</td>
                                    <td>
                                        <span class="badge {{ $usuario->estado ? 'bg-success' : 'bg-danger' }}">
                                            {{ $usuario->estado ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

@endsection

@push('scripts')
<script>
    function confirmarEliminacion(rolId, nombreRol) {
        document.getElementById('nombreRol').textContent = nombreRol;
        document.getElementById('formEliminar').action = `/roles/${rolId}`;
        
        const modal = new bootstrap.Modal(document.getElementById('confirmarEliminacionModal'));
        modal.show();
    }

    function toggleEstado(rolId, estado) {
        fetch(`/roles/${rolId}/toggle-estado`, {
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
                const badge = document.querySelector(`#estado${rolId} + label .badge`);
                if (estado) {
                    badge.className = 'badge bg-success';
                    badge.textContent = 'Activo';
                } else {
                    badge.className = 'badge bg-danger';
                    badge.textContent = 'Inactivo';
                }
                
                showAlert('success', data.message);
            } else {
                // Revertir el switch si hay error
                document.getElementById(`estado${rolId}`).checked = !estado;
                showAlert('error', data.message || 'Error al cambiar el estado');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Revertir el switch si hay error
            document.getElementById(`estado${rolId}`).checked = !estado;
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
        
        const contentArea = document.querySelector('.content-area');
        contentArea.insertBefore(alert, contentArea.firstChild);
        
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    }
</script>
@endpush
