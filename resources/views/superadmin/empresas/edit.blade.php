@extends('layouts.dashboard')

@section('title', 'Editar Empresa - AGROEMSE')
@section('page-title', 'Editar Empresa')
@section('page-subtitle', 'Modifica la información de ' . $empresa->nombre)

@section('sidebar-menu')
    <a href="{{ route('superadmin.dashboard') }}" class="nav-link">
        <i class="fas fa-tachometer-alt"></i>
        Dashboard Global
    </a>
    <a href="{{ route('superadmin.empresas') }}" class="nav-link active">
        <i class="fas fa-building"></i>
        Gestión de Empresas
    </a>
    <a href="{{ route('superadmin.roles') }}" class="nav-link">
        <i class="fas fa-users-cog"></i>
        Configuración Global
    </a>
    <a href="{{ route('superadmin.usuarios') }}" class="nav-link">
        <i class="fas fa-users"></i>
        Usuarios del Sistema
    </a>
@endsection

@section('header-actions')
    <div class="d-flex gap-2">
        <a href="{{ route('superadmin.empresas') }}" class="btn btn-light">
            <i class="fas fa-arrow-left me-2"></i>Volver
        </a>
        <button type="button" class="btn btn-outline-danger" onclick="confirmarEliminacion()">
            <i class="fas fa-trash me-2"></i>Eliminar
        </button>
    </div>
@endsection

@section('content-area')
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-building me-2"></i>
                    Información de la Empresa
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('superadmin.empresas.update', $empresa) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nombre" class="form-label">
                                <i class="fas fa-building me-1"></i>
                                Nombre de la Empresa <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control @error('nombre') is-invalid @enderror" 
                                   id="nombre" name="nombre" value="{{ old('nombre', $empresa->nombre) }}" 
                                   placeholder="Ej: CitrusExport Colombia" required>
                            @error('nombre')
                                <div class="invalid-feedback">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="id_user_admin" class="form-label">
                                <i class="fas fa-user-tie me-1"></i>
                                Administrador <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('id_user_admin') is-invalid @enderror" 
                                    id="id_user_admin" name="id_user_admin" required>
                                <option value="">Seleccionar administrador</option>
                                @foreach($administradores as $admin)
                                    <option value="{{ $admin->id }}" 
                                            {{ old('id_user_admin', $empresa->id_user_admin) == $admin->id ? 'selected' : '' }}>
                                        {{ $admin->nombres }} {{ $admin->apellidos }} ({{ $admin->email }})
                                    </option>
                                @endforeach
                            </select>
                            @error('id_user_admin')
                                <div class="invalid-feedback">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descripcion" class="form-label">
                            <i class="fas fa-align-left me-1"></i>
                            Descripción <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control @error('descripcion') is-invalid @enderror" 
                                  id="descripcion" name="descripcion" rows="3" 
                                  placeholder="Describe la actividad principal de la empresa" required>{{ old('descripcion', $empresa->descripcion) }}</textarea>
                        @error('descripcion')
                            <div class="invalid-feedback">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="logo" class="form-label">
                                <i class="fas fa-image me-1"></i>
                                Logo de la Empresa
                            </label>
                            <input type="file" class="form-control @error('logo') is-invalid @enderror" 
                                   id="logo" name="logo" accept="image/*" onchange="previewLogo(this)">
                            @error('logo')
                                <div class="invalid-feedback">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    {{ $message }}
                                </div>
                            @enderror
                            <div class="form-text">
                                Formatos permitidos: JPG, PNG, GIF. Máximo 2MB.
                                <br>Dejar vacío para mantener el logo actual.
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Logo actual / Vista previa</label>
                            <div id="logoPreview" class="border rounded p-3 text-center" style="min-height: 120px; display: flex; align-items: center; justify-content: center;">
                                @if($empresa->ruta_logo)
                                    <img src="{{ Storage::url($empresa->ruta_logo) }}" alt="Logo actual" 
                                         style="max-width: 100%; max-height: 100px; object-fit: contain;">
                                @else
                                    <span class="text-muted">
                                        <i class="fas fa-image fa-2x mb-2"></i><br>
                                        Sin logo
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" 
                                       id="estado" name="estado" {{ old('estado', $empresa->estado) ? 'checked' : '' }}>
                                <label class="form-check-label" for="estado">
                                    <i class="fas fa-toggle-on me-1"></i>
                                    Empresa activa
                                </label>
                            </div>
                            <div class="form-text">
                                Las empresas activas pueden operar normalmente
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" 
                                       id="editable" name="editable" {{ old('editable', $empresa->editable) ? 'checked' : '' }}>
                                <label class="form-check-label" for="editable">
                                    <i class="fas fa-edit me-1"></i>
                                    Permitir edición
                                </label>
                            </div>
                            <div class="form-text">
                                Si está deshabilitado, solo el SuperAdmin puede editar
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="{{ route('superadmin.empresas') }}" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    Información de la Empresa
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6">
                        <small class="text-muted">ID de Empresa</small>
                        <div class="fw-semibold">{{ $empresa->id }}</div>
                    </div>
                    <div class="col-6">
                        <small class="text-muted">Fecha de Registro</small>
                        <div class="fw-semibold">{{ $empresa->created_at->format('d/m/Y') }}</div>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-6">
                        <small class="text-muted">Creado por</small>
                        <div class="fw-semibold">
                            {{ $empresa->userCreate->nombres ?? 'Sistema' }}
                        </div>
                    </div>
                    <div class="col-6">
                        <small class="text-muted">Última modificación</small>
                        <div class="fw-semibold">{{ $empresa->updated_at->format('d/m/Y') }}</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="fas fa-chart-bar me-2"></i>
                    Estadísticas
                </h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6 mb-3">
                        <div class="h4 text-primary mb-1">45</div>
                        <small class="text-muted">Empleados</small>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="h4 text-success mb-1">12</div>
                        <small class="text-muted">Flujos Activos</small>
                    </div>
                    <div class="col-6">
                        <div class="h4 text-info mb-1">28</div>
                        <small class="text-muted">Clientes</small>
                    </div>
                    <div class="col-6">
                        <div class="h4 text-warning mb-1">156</div>
                        <small class="text-muted">Productos</small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Zona de Peligro
                </h6>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">
                    La eliminación de la empresa es permanente y no se puede deshacer.
                </p>
                <button type="button" class="btn btn-outline-danger w-100" onclick="confirmarEliminacion()">
                    <i class="fas fa-trash me-2"></i>
                    Eliminar Empresa
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
                    <i class="fas fa-exclamation-triangle text-danger me-2"></i>
                    Confirmar Eliminación
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que deseas eliminar la empresa <strong>{{ $empresa->nombre }}</strong>?</p>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Esta acción eliminará:
                    <ul class="mb-0 mt-2">
                        <li>Todos los usuarios de la empresa</li>
                        <li>Todos los flujos y procesos</li>
                        <li>Todos los documentos asociados</li>
                        <li>Toda la información relacionada</li>
                    </ul>
                </div>
                <p class="text-danger"><strong>Esta acción no se puede deshacer.</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancelar
                </button>
                <form method="POST" action="{{ route('superadmin.empresas.destroy', $empresa) }}" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>Eliminar Definitivamente
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function previewLogo(input) {
    const preview = document.getElementById('logoPreview');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.innerHTML = `
                <img src="${e.target.result}" alt="Preview" 
                     style="max-width: 100%; max-height: 100px; object-fit: contain;">
            `;
        };
        
        reader.readAsDataURL(input.files[0]);
    }
}

function confirmarEliminacion() {
    const modal = new bootstrap.Modal(document.getElementById('confirmarEliminacionModal'));
    modal.show();
}

// Validación de archivo
document.getElementById('logo').addEventListener('change', function() {
    const file = this.files[0];
    if (file) {
        // Validar tamaño (2MB máximo)
        if (file.size > 2 * 1024 * 1024) {
            alert('El archivo es demasiado grande. Máximo 2MB permitido.');
            this.value = '';
            return;
        }
        
        // Validar tipo de archivo
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!allowedTypes.includes(file.type)) {
            alert('Tipo de archivo no permitido. Use JPG, PNG o GIF.');
            this.value = '';
            return;
        }
    }
});
</script>
@endpush
