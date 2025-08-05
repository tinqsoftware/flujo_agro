@extends('layouts.dashboard')

@section('title', 'Gestión Global de Usuarios - AGROEMSE')
@section('page-title', 'Usuarios del Sistema')
@section('page-subtitle', 'Gestión completa de usuarios de todas las empresas')

@section('sidebar-menu')
    <a href="{{ route('superadmin.dashboard') }}" class="nav-link">
        <i class="fas fa-tachometer-alt"></i>
        Dashboard Global
    </a>
    <a href="{{ route('superadmin.empresas') }}" class="nav-link">
        <i class="fas fa-building"></i>
        Gestión de Empresas
    </a>
    <a href="{{ route('superadmin.roles') }}" class="nav-link">
        <i class="fas fa-users-cog"></i>
        Configuración Global
    </a>
    <a href="{{ route('superadmin.usuarios') }}" class="nav-link active">
        <i class="fas fa-users"></i>
        Usuarios del Sistema
    </a>
@endsection

@section('header-actions')
    <a href="{{ route('superadmin.usuarios.create') }}" class="btn btn-light">
        <i class="fas fa-user-plus me-2"></i>Nuevo Usuario
    </a>
@endsection

@section('content-area')
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="fas fa-users me-2"></i>
                Todos los Usuarios del Sistema
            </h5>
            <div class="d-flex gap-2">
                <form method="GET" action="{{ route('superadmin.usuarios') }}" class="d-flex">
                    <select name="empresa" class="form-select form-select-sm me-2" onchange="this.form.submit()">
                        <option value="">Todas las empresas</option>
                        @foreach($empresas as $empresa)
                            <option value="{{ $empresa->id }}" {{ request('empresa') == $empresa->id ? 'selected' : '' }}>
                                {{ $empresa->nombre }}
                            </option>
                        @endforeach
                    </select>
                    <select name="rol" class="form-select form-select-sm me-2" onchange="this.form.submit()">
                        <option value="">Todos los roles</option>
                        @foreach($roles as $rol)
                            <option value="{{ $rol->id }}" {{ request('rol') == $rol->id ? 'selected' : '' }}>
                                {{ $rol->nombre }}
                            </option>
                        @endforeach
                    </select>
                    <select name="estado" class="form-select form-select-sm me-2" onchange="this.form.submit()">
                        <option value="">Todos los estados</option>
                        <option value="1" {{ request('estado') === '1' ? 'selected' : '' }}>Activos</option>
                        <option value="0" {{ request('estado') === '0' ? 'selected' : '' }}>Inactivos</option>
                    </select>
                    <input type="text" name="search" class="form-control form-control-sm me-2" 
                           placeholder="Buscar..." value="{{ request('search') }}">
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
                                     style="width: 32px; height: 32px;">
                                    <span class="text-white fw-semibold" style="font-size: 12px;">
                                        {{ strtoupper(substr($usuario->nombres, 0, 1) . substr($usuario->apellidos, 0, 1)) }}
                                    </span>
                                </div>
                                <div>
                                    <div class="fw-semibold">{{ $usuario->nombres }} {{ $usuario->apellidos }}</div>
                                    <small class="text-muted">{{ $usuario->email }}</small>
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
                            @else
                                <span class="text-warning">Sin rol</span>
                            @endif
                        </td>
                        <td>
                            @if($usuario->empresa)
                                <div>
                                    <div class="fw-semibold">{{ $usuario->empresa->nombre }}</div>
                                    <small class="text-muted">{{ Str::limit($usuario->empresa->descripcion, 25) }}</small>
                                </div>
                            @else
                                <span class="text-warning">Sin empresa</span>
                            @endif
                        </td>
                        <td>
                            <div>
                                <div class="small">{{ $usuario->email }}</div>
                                @if($usuario->celular)
                                    <div class="small text-muted">{{ $usuario->celular }}</div>
                                @endif
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
                        </td>
                        <td>
                            <div class="small text-muted">--/--/----</div>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('superadmin.usuarios.edit', $usuario) }}" 
                                   class="btn btn-outline-primary" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @if($usuario->id !== Auth::id())
                                    <button type="button" class="btn btn-outline-danger eliminar-usuario" 
                                            data-usuario-id="{{ $usuario->id }}" 
                                            data-usuario-nombre="{{ $usuario->nombres }} {{ $usuario->apellidos }}"
                                            title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No hay usuarios encontrados</h5>
                            <p class="text-muted">Ajusta los filtros o crea un nuevo usuario</p>
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
                    Esta acción eliminará permanentemente:
                    <ul class="mb-0 mt-2">
                        <li>El acceso del usuario al sistema</li>
                        <li>Todos los datos asociados</li>
                        <li>El historial de actividades</li>
                    </ul>
                </div>
                <p class="text-danger"><strong>Esta acción no se puede deshacer.</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Cancelar
                </button>
                <form id="formEliminar" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        Eliminar Usuario
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Delegación de eventos para eliminación
document.addEventListener('click', function(e) {
    if (e.target.closest('.eliminar-usuario')) {
        e.preventDefault();
        const button = e.target.closest('.eliminar-usuario');
        const usuarioId = button.getAttribute('data-usuario-id');
        const nombreUsuario = button.getAttribute('data-usuario-nombre');
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
            const badge = document.querySelector(`#estado${usuarioId} + label .badge`);
            if (estado) {
                badge.className = 'badge bg-success';
                badge.textContent = 'Activo';
            } else {
                badge.className = 'badge bg-danger';
                badge.textContent = 'Inactivo';
            }
            
            showAlert('success', data.message);
        } else {
            document.getElementById(`estado${usuarioId}`).checked = !estado;
            showAlert('error', data.message || 'Error al cambiar el estado');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById(`estado${usuarioId}`).checked = !estado;
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
