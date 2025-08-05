@extends('layouts.dashboard')

@section('title', 'Gestión de Usuarios - AGROEMSE')
@section('page-title', 'Usuarios del Sistema')
@section('page-subtitle', 'Administra los usuarios de la empresa')

@section('sidebar-menu')
    <a href="{{ route('admin.dashboard') }}" class="nav-link">
        <i class="fas fa-tachometer-alt"></i>
        Dashboard
    </a>
    <a href="#" class="nav-link">
        <i class="fas fa-project-diagram"></i>
        Flujos de Trabajo
    </a>
    <a href="#" class="nav-link">
        <i class="fas fa-cube"></i>
        Productos
    </a>
    <a href="#" class="nav-link">
        <i class="fas fa-users"></i>
        Clientes
    </a>
    <a href="#" class="nav-link">
        <i class="fas fa-truck"></i>
        Proveedores
    </a>
    <a href="#" class="nav-link">
        <i class="fas fa-file-alt"></i>
        Documentos
    </a>
    <a href="#" class="nav-link">
        <i class="fas fa-clipboard-list"></i>
        Fichas Técnicas
    </a>
    <a href="{{ route('admin.usuarios') }}" class="nav-link active">
        <i class="fas fa-user-friends"></i>
        Usuarios de Empresa
    </a>
    <a href="#" class="nav-link">
        <i class="fas fa-cog"></i>
        Configuración
    </a>
@endsection

@section('header-actions')
    <a href="{{ route('admin.usuarios.create') }}" class="btn btn-light">
        <i class="fas fa-user-plus me-2"></i>Nuevo Usuario
    </a>
@endsection

@section('content-area')
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="fas fa-user-friends me-2"></i>
                Lista de Usuarios
            </h5>
            <div class="d-flex gap-2">
                <form method="GET" action="{{ route('admin.usuarios') }}" class="d-flex">
                    <select name="estado" class="form-select form-select-sm me-2" onchange="this.form.submit()">
                        <option value="">Todos los estados</option>
                        <option value="1" {{ request('estado') === '1' ? 'selected' : '' }}>Activos</option>
                        <option value="0" {{ request('estado') === '0' ? 'selected' : '' }}>Inactivos</option>
                    </select>
                    <input type="text" name="search" class="form-control form-control-sm me-2" 
                           placeholder="Buscar usuario..." value="{{ request('search') }}">
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
                        <th>Usuario</th>
                        <th>Rol</th>
                        <th>Empresa</th>
                        <th>Contacto</th>
                        <th>Estado</th>
                        <th>Último Acceso</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($usuarios as $usuario)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-2" 
                                     style="width: 40px; height: 40px;">
                                    <span class="text-white fw-semibold">
                                        {{ strtoupper(substr($usuario->nombres, 0, 1) . substr($usuario->apellidos, 0, 1)) }}
                                    </span>
                                </div>
                                <div>
                                    <div class="fw-semibold">{{ $usuario->nombres }} {{ $usuario->apellidos }}</div>
                                    <small class="text-muted">{{ $usuario->email }}</small>
                                    <br>
                                    <small class="text-muted">ID: {{ $usuario->id }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($usuario->rol)
                                @php
                                    $badgeClass = '';
                                    switch($usuario->rol->nombre) {
                                        case 'SUPERADMIN':
                                            $badgeClass = 'bg-warning';
                                            break;
                                        case 'ADMINISTRADOR':
                                            $badgeClass = 'bg-primary';
                                            break;
                                        case 'ADMINISTRATIVO':
                                            $badgeClass = 'bg-info';
                                            break;
                                        default:
                                            $badgeClass = 'bg-secondary';
                                    }
                                @endphp
                                <span class="badge {{ $badgeClass }}">{{ $usuario->rol->nombre }}</span>
                                <br>
                                <small class="text-muted">{{ $usuario->rol->descripcion }}</small>
                            @else
                                <span class="text-warning">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    Sin rol asignado
                                </span>
                            @endif
                        </td>
                        <td>
                            @if($usuario->empresa)
                                <div>
                                    <div class="fw-semibold">{{ $usuario->empresa->nombre }}</div>
                                    <small class="text-muted">{{ Str::limit($usuario->empresa->descripcion, 30) }}</small>
                                </div>
                            @else
                                <span class="text-warning">
                                    <i class="fas fa-building me-1"></i>
                                    Sin empresa
                                </span>
                            @endif
                        </td>
                        <td>
                            <div>
                                <div>
                                    <i class="fas fa-envelope me-1 text-muted"></i>
                                    {{ $usuario->email }}
                                </div>
                                @if($usuario->celular)
                                    <div>
                                        <i class="fas fa-phone me-1 text-muted"></i>
                                        {{ $usuario->celular }}
                                    </div>
                                @endif
                                <small class="text-muted">
                                    <i class="fas fa-venus-mars me-1"></i>
                                    {{ $usuario->sexo === 'M' ? 'Masculino' : 'Femenino' }}
                                </small>
                            </div>
                        </td>
                        <td>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" 
                                       id="estado{{ $usuario->id }}" 
                                       {{ $usuario->estado ? 'checked' : '' }}
                                       onchange="toggleEstado({{ $usuario->id }}, this.checked)">
                                <label class="form-check-label" for="estado{{ $usuario->id }}">
                                    <span class="badge {{ $usuario->estado ? 'bg-success' : 'bg-danger' }}">
                                        {{ $usuario->estado ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </label>
                            </div>
                            @if($usuario->id === Auth::id())
                                <small class="text-info d-block">Tu usuario</small>
                            @endif
                        </td>
                        <td>
                            <div>
                                <div>--/--/----</div>
                                <small class="text-muted">Nunca</small>
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
                                            <i class="fas fa-eye me-2"></i>Ver perfil
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('admin.usuarios.edit', $usuario) }}">
                                            <i class="fas fa-edit me-2"></i>Editar
                                        </a>
                                    </li>
                                    @if($usuario->id !== Auth::id())
                                        <li>
                                            @if($usuario->estado)
                                                <a class="dropdown-item toggle-estado" href="#" 
                                                   data-usuario-id="{{ $usuario->id }}" 
                                                   data-nuevo-estado="false">
                                                    <i class="fas fa-user-slash me-2 text-warning"></i>Desactivar
                                                </a>
                                            @else
                                                <a class="dropdown-item toggle-estado" href="#" 
                                                   data-usuario-id="{{ $usuario->id }}" 
                                                   data-nuevo-estado="true">
                                                    <i class="fas fa-user-check me-2 text-success"></i>Activar
                                                </a>
                                            @endif
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item text-danger eliminar-usuario" href="#" 
                                               data-usuario-id="{{ $usuario->id }}" 
                                               data-usuario-nombre="{{ $usuario->nombres }} {{ $usuario->apellidos }}">
                                                <i class="fas fa-trash me-2"></i>Eliminar
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <i class="fas fa-user-friends fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No hay usuarios registrados</h5>
                            <p class="text-muted">Comienza agregando el primer usuario</p>
                            <a href="{{ route('admin.usuarios.create') }}" class="btn btn-primary">
                                <i class="fas fa-user-plus me-2"></i>Crear Primer Usuario
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($usuarios->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $usuarios->links() }}
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
                <p>¿Estás seguro de que deseas eliminar al usuario <strong id="nombreUsuario"></strong>?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Esta acción eliminará:
                    <ul class="mb-0 mt-2">
                        <li>El acceso del usuario al sistema</li>
                        <li>Todos los datos asociados al usuario</li>
                        <li>El historial de actividades</li>
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
                        <i class="fas fa-trash me-2"></i>Eliminar Usuario
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Delegación de eventos para toggle estado
document.addEventListener('click', function(e) {
    if (e.target.closest('.toggle-estado')) {
        e.preventDefault();
        const link = e.target.closest('.toggle-estado');
        const usuarioId = link.getAttribute('data-usuario-id');
        const nuevoEstado = link.getAttribute('data-nuevo-estado') === 'true';
        toggleEstado(usuarioId, nuevoEstado);
    }
    
    if (e.target.closest('.eliminar-usuario')) {
        e.preventDefault();
        const link = e.target.closest('.eliminar-usuario');
        const usuarioId = link.getAttribute('data-usuario-id');
        const nombreUsuario = link.getAttribute('data-usuario-nombre');
        confirmarEliminacion(usuarioId, nombreUsuario);
    }
});

function confirmarEliminacion(usuarioId, nombreUsuario) {
    document.getElementById('nombreUsuario').textContent = nombreUsuario;
    document.getElementById('formEliminar').action = `/admin/usuarios/${usuarioId}`;
    
    const modal = new bootstrap.Modal(document.getElementById('confirmarEliminacionModal'));
    modal.show();
}

function toggleEstado(usuarioId, estado) {
    fetch(`/admin/usuarios/${usuarioId}/toggle-estado`, {
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
            const badge = document.querySelector(`#estado${usuarioId} + label .badge`);
            if (estado) {
                badge.className = 'badge bg-success';
                badge.textContent = 'Activo';
            } else {
                badge.className = 'badge bg-danger';
                badge.textContent = 'Inactivo';
            }
            
            // Actualizar el switch
            document.getElementById(`estado${usuarioId}`).checked = estado;
            
            showAlert('success', data.message);
            
            // Recargar la página para actualizar los dropdowns
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            showAlert('error', data.message || 'Error al cambiar el estado');
        }
    })
    .catch(error => {
        console.error('Error:', error);
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
