@extends('layouts.dashboard')

@section('title', 'Gestión de Empresas - AGROEMSE')
@section('page-title', 'Gestión de Empresas')
@section('page-subtitle', 'Administra todas las empresas registradas en el sistema')


@section('header-actions')
    <a href="{{ route('empresas.create') }}" class="btn btn-light">
        <i class="fas fa-plus me-2"></i>Nueva Empresa
    </a>
@endsection

@section('content-area')
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="fas fa-building me-2"></i>
                Lista de Empresas
            </h5>
            <div class="d-flex gap-2">
                <form method="GET" action="{{ route('empresas') }}" class="d-flex">
                    <input type="text" name="search" class="form-control form-control-sm me-2" 
                           placeholder="Buscar empresa..." value="{{ request('search') }}">
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
                        <th>Empresa</th>
                        <th>Administrador</th>
                        <th>Sector</th>
                        <th>Estado</th>
                        <th>Empleados</th>
                        <th>Flujos Activos</th>
                        <th>Fecha Registro</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($empresas as $empresa)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                @if($empresa->ruta_logo)
                                    <img src="{{ Storage::url($empresa->ruta_logo) }}" 
                                         class="rounded me-2" width="40" height="40" alt="Logo">
                                @else
                                    <div class="bg-primary rounded d-flex align-items-center justify-content-center me-2" 
                                         style="width: 40px; height: 40px;">
                                        <i class="fas fa-building text-white"></i>
                                    </div>
                                @endif
                                <div>
                                    <div class="fw-semibold">{{ $empresa->nombre }}</div>
                                    <small class="text-muted">ID: {{ $empresa->id }}</small>
                                    <br>
                                    <small class="text-muted">{{ Str::limit($empresa->descripcion, 50) }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($empresa->userAdmin)
                                <div>
                                    <div class="fw-semibold">{{ $empresa->userAdmin->nombres }} {{ $empresa->userAdmin->apellidos }}</div>
                                    <small class="text-muted">{{ $empresa->userAdmin->email }}</small>
                                </div>
                            @else
                                <span class="text-warning">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    Sin asignar
                                </span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-secondary">Por definir</span>
                        </td>
                        <td>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" 
                                       id="estado{{ $empresa->id }}" 
                                       {{ $empresa->estado ? 'checked' : '' }}
                                       onchange="toggleEstado({{ $empresa->id }}, this.checked)">
                                <label class="form-check-label" for="estado{{ $empresa->id }}">
                                    <span class="badge {{ $empresa->estado ? 'bg-success' : 'bg-danger' }}">
                                        {{ $empresa->estado ? 'Activa' : 'Inactiva' }}
                                    </span>
                                </label>
                            </div>
                        </td>
                        <td>
                            <span class="fw-semibold">--</span>
                        </td>
                        <td>
                            <span class="fw-semibold text-primary">{{ $empresa->flujos_count }}</span>
                            @if($empresa->flujos_count > 0)
                                <small class="text-muted d-block">flujos activos</small>
                            @endif
                        </td>
                        <td>
                            <div>
                                <div>{{ $empresa->created_at->format('d/m/Y') }}</div>
                                <small class="text-muted">{{ $empresa->created_at->format('H:i') }}</small>
                            </div>
                        </td>
                        <td>
                            <div class="dropdown-custom">
                                <button type="button" class="btn btn-outline-primary btn-sm dropdown-btn">
                                    <i class="fas fa-cog"></i>
                                    <i class="fas fa-chevron-down ms-1"></i>
                                </button>
                                <div class="dropdown-menu-custom">
                                    <a href="#" onclick="verDetallesEmpresa({{ $empresa->id }}); return false;" class="dropdown-item-custom">
                                        <i class="fas fa-eye me-2"></i>Ver detalles
                                    </a>
                                    <a href="{{ route('empresas.edit', $empresa) }}" class="dropdown-item-custom">
                                        <i class="fas fa-edit me-2"></i>Editar
                                    </a>
                                    <div class="dropdown-divider-custom"></div>
                                    <a href="#" onclick="confirmarEliminacion({{ $empresa->id }}, '{{ addslashes($empresa->nombre) }}'); return false;" class="dropdown-item-custom text-danger">
                                        <i class="fas fa-trash me-2"></i>Eliminar
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <i class="fas fa-building fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No hay empresas registradas</h5>
                            <p class="text-muted">Comienza creando tu primera empresa</p>
                            <a href="{{ route('empresas.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Crear Primera Empresa
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($empresas->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $empresas->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal de detalles de empresa -->
<div class="modal fade" id="detallesEmpresaModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-building text-primary me-2"></i>
                    Detalles de la Empresa
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="contenidoDetallesEmpresa">
                <!-- El contenido se cargará dinámicamente -->
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-2 text-muted">Cargando detalles...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cerrar
                </button>
            </div>
        </div>
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
                <p>¿Estás seguro de que deseas eliminar la empresa <strong id="nombreEmpresa"></strong>?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Esta acción eliminará:
                    <ul class="mb-0 mt-2">
                        <li>Todos los usuarios de la empresa</li>
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
                        <i class="fas fa-trash me-2"></i>Eliminar Empresa
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* Dropdown CSS puro */
.dropdown-custom {
    position: relative;
    display: inline-block;
}

.dropdown-btn {
    background: #fff;
    border: 1px solid #0d6efd;
    color: #0d6efd;
    padding: 0.375rem 0.75rem;
    border-radius: 0.25rem;
    cursor: pointer;
    transition: all 0.15s ease-in-out;
    font-size: 0.875rem;
}

.dropdown-btn:hover {
    background: #0d6efd;
    color: #fff;
    border-color: #0d6efd;
}

.dropdown-btn:focus {
    outline: none;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

.dropdown-menu-custom {
    position: absolute;
    top: 100%;
    right: 0;
    z-index: 9999;
    min-width: 160px;
    padding: 0.5rem 0;
    margin: 0.125rem 0 0;
    background-color: #fff;
    border: 1px solid rgba(0, 0, 0, 0.15);
    border-radius: 0.375rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.175);
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.2s ease-in-out;
}

.dropdown-custom:hover .dropdown-menu-custom {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.dropdown-item-custom {
    display: block;
    width: 100%;
    padding: 0.375rem 1rem;
    clear: both;
    font-weight: 400;
    color: #212529;
    text-decoration: none;
    white-space: nowrap;
    background-color: transparent;
    border: 0;
    transition: background-color 0.15s ease-in-out;
}

.dropdown-item-custom:hover {
    background-color: #f8f9fa;
    color: #1e2125;
    text-decoration: none;
}

.dropdown-item-custom.text-danger {
    color: #dc3545 !important;
}

.dropdown-item-custom.text-danger:hover {
    background-color: #dc3545;
    color: #fff !important;
}

.dropdown-divider-custom {
    height: 0;
    margin: 0.5rem 0;
    overflow: hidden;
    border-top: 1px solid rgba(0, 0, 0, 0.15);
}

/* Animación del ícono chevron */
.dropdown-custom:hover .dropdown-btn .fa-chevron-down {
    transform: rotate(180deg);
}

.dropdown-btn .fa-chevron-down {
    transition: transform 0.2s ease-in-out;
    font-size: 0.75rem;
}

/* Responsivo */
@media (max-width: 768px) {
    .dropdown-menu-custom {
        left: 0;
        right: auto;
        min-width: 140px;
    }
}

/* Tabla sin scroll horizontal */
.table-responsive {
    overflow-x: visible !important;
}

/* Asegurar que el dropdown esté por encima de otros elementos */
.dropdown-custom {
    position: relative;
    z-index: 1;
}

.dropdown-custom:hover {
    z-index: 9999;
}
</style>
@endpush

@push('scripts')
<script>
function verDetallesEmpresa(empresaId) {
    const modal = new bootstrap.Modal(document.getElementById('detallesEmpresaModal'));
    modal.show();
    
    // Limpiar contenido anterior
    document.getElementById('contenidoDetallesEmpresa').innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-2 text-muted">Cargando detalles...</p>
        </div>
    `;
    
    // Cargar detalles de la empresa
    fetch(`/empresas/${empresaId}/show`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.empresa) {
            mostrarDetallesEmpresa(data.empresa, data.estadisticas);
        } else {
            throw new Error('No se recibieron datos de la empresa');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('contenidoDetallesEmpresa').innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i>
                Error al cargar los detalles de la empresa: ${error.message}
            </div>
        `;
    });
}

function mostrarDetallesEmpresa(empresa, estadisticas) {
    const fechaRegistro = new Date(empresa.created_at).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
    
    const fechaInicio = empresa.fecha_inicio ? new Date(empresa.fecha_inicio).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    }) : 'No especificada';
    
    let logoHtml = '';
    if (empresa.ruta_logo) {
        logoHtml = `<img src="/storage/${empresa.ruta_logo}" class="img-thumbnail" style="max-width: 150px; max-height: 150px;" alt="Logo de ${empresa.nombre}">`;
    } else {
        logoHtml = `
            <div class="bg-primary rounded d-flex align-items-center justify-content-center" style="width: 150px; height: 150px;">
                <i class="fas fa-building text-white fa-3x"></i>
            </div>
        `;
    }
    
    const contenido = `
        <div class="row">
            <!-- Información básica -->
            <div class="col-md-8">
                <div class="card h-100">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>Información General
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-sm-3"><strong>ID:</strong></div>
                            <div class="col-sm-9">${empresa.id}</div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-3"><strong>Nombre:</strong></div>
                            <div class="col-sm-9">${empresa.nombre}</div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-3"><strong>Descripción:</strong></div>
                            <div class="col-sm-9">${empresa.descripcion || 'Sin descripción'}</div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-3"><strong>Estado:</strong></div>
                            <div class="col-sm-9">
                                <span class="badge ${empresa.estado ? 'bg-success' : 'bg-danger'}">
                                    ${empresa.estado ? 'Activa' : 'Inactiva'}
                                </span>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-3"><strong>Fecha Inicio:</strong></div>
                            <div class="col-sm-9">${fechaInicio}</div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-3"><strong>Fecha Registro:</strong></div>
                            <div class="col-sm-9">${fechaRegistro}</div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-3"><strong>Editable:</strong></div>
                            <div class="col-sm-9">
                                <span class="badge ${empresa.editable ? 'bg-success' : 'bg-secondary'}">
                                    ${empresa.editable ? 'Sí' : 'No'}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Logo y administrador -->
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-image me-2"></i>Logo y Administrador
                        </h6>
                    </div>
                    <div class="card-body text-center">
                        <div class="mb-4">
                            ${logoHtml}
                        </div>
                        <div class="mt-3">
                            <h6 class="mb-2">Administrador</h6>
                            ${(empresa.user_admin || empresa.userAdmin) ? `
                                <div class="text-muted">
                                    <strong>${(empresa.user_admin || empresa.userAdmin).nombres} ${(empresa.user_admin || empresa.userAdmin).apellidos}</strong><br>
                                    <small>${(empresa.user_admin || empresa.userAdmin).email}</small>
                                </div>
                            ` : '<span class="text-warning">Sin asignar</span>'}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Estadísticas -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-chart-bar me-2"></i>Estadísticas
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3 mb-3">
                                <div class="p-3 bg-primary bg-opacity-10 rounded">
                                    <i class="fas fa-project-diagram fa-2x text-primary mb-2"></i>
                                    <h4 class="mb-1">${estadisticas.total_flujos}</h4>
                                    <small class="text-muted">Total Flujos</small>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="p-3 bg-success bg-opacity-10 rounded">
                                    <i class="fas fa-play-circle fa-2x text-success mb-2"></i>
                                    <h4 class="mb-1">${estadisticas.flujos_activos}</h4>
                                    <small class="text-muted">Flujos Activos</small>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="p-3 bg-warning bg-opacity-10 rounded">
                                    <i class="fas fa-users fa-2x text-warning mb-2"></i>
                                    <h4 class="mb-1">${estadisticas.usuarios_count}</h4>
                                    <small class="text-muted">Usuarios</small>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="p-3 bg-info bg-opacity-10 rounded">
                                    <i class="fas fa-file-alt fa-2x text-info mb-2"></i>
                                    <h4 class="mb-1">${estadisticas.total_fichas}</h4>
                                    <small class="text-muted">Fichas</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row text-center mt-3">
                            <div class="col-md-4 mb-3">
                                <div class="p-3 bg-secondary bg-opacity-10 rounded">
                                    <i class="fas fa-handshake fa-2x text-secondary mb-2"></i>
                                    <h4 class="mb-1">${estadisticas.clientes_count}</h4>
                                    <small class="text-muted">Clientes</small>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="p-3 bg-dark bg-opacity-10 rounded">
                                    <i class="fas fa-box fa-2x text-dark mb-2"></i>
                                    <h4 class="mb-1">${estadisticas.productos_count}</h4>
                                    <small class="text-muted">Productos</small>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="p-3 bg-danger bg-opacity-10 rounded">
                                    <i class="fas fa-truck fa-2x text-danger mb-2"></i>
                                    <h4 class="mb-1">${estadisticas.proveedores_count}</h4>
                                    <small class="text-muted">Proveedores</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Lista de flujos -->
        ${empresa.flujos && empresa.flujos.length > 0 ? `
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-list me-2"></i>Flujos Registrados
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Tipo</th>
                                        <th>Estado</th>
                                        <th>Fecha Creación</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${empresa.flujos.map(flujo => `
                                        <tr>
                                            <td>${flujo.nombre}</td>
                                            <td>${flujo.tipo ? flujo.tipo.nombre : 'Sin tipo'}</td>
                                            <td>
                                                <span class="badge ${flujo.estado === 1 ? 'bg-success' : 'bg-danger'}">
                                                    ${flujo.estado === 1 ? 'Activo' : 'Inactivo'}
                                                </span>
                                            </td>
                                            <td>${new Date(flujo.created_at).toLocaleDateString('es-ES')}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        ` : ''}
    `;
    
    document.getElementById('contenidoDetallesEmpresa').innerHTML = contenido;
}

function confirmarEliminacion(empresaId, nombreEmpresa) {
    document.getElementById('nombreEmpresa').textContent = nombreEmpresa;
    document.getElementById('formEliminar').action = `/empresas/${empresaId}`;
    
    const modal = new bootstrap.Modal(document.getElementById('confirmarEliminacionModal'));
    modal.show();
}

function toggleEstado(empresaId, estado) {
    fetch(`/empresas/${empresaId}/toggle-estado`, {
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
            const badge = document.querySelector(`#estado${empresaId} + label .badge`);
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
            document.getElementById(`estado${empresaId}`).checked = !estado;
            showAlert('error', data.message || 'Error al cambiar el estado');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // Revertir el switch si hay error
        document.getElementById(`estado${empresaId}`).checked = !estado;
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
