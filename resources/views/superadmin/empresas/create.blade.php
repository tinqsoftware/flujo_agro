@extends('layouts.dashboard')

@section('title', 'Nueva Empresa - AGROEMSE')
@section('page-title', 'Crear Nueva Empresa')
@section('page-subtitle', 'Registra una nueva empresa en el sistema')


@section('header-actions')
    <a href="{{ route('empresas') }}" class="btn btn-light">
        <i class="fas fa-arrow-left me-2"></i>Volver
    </a>
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
                <form method="POST" action="{{ route('empresas.store') }}" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nombre" class="form-label">
                                <i class="fas fa-building me-1"></i>
                                Nombre de la Empresa <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control @error('nombre') is-invalid @enderror" 
                                   id="nombre" name="nombre" value="{{ old('nombre') }}" 
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
                                    <option value="{{ $admin->id }}" {{ old('id_user_admin') == $admin->id ? 'selected' : '' }}>
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
                            <div class="form-text">
                                El usuario debe tener rol de "ADMINISTRADOR"
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descripcion" class="form-label">
                            <i class="fas fa-align-left me-1"></i>
                            Descripción <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control @error('descripcion') is-invalid @enderror" 
                                  id="descripcion" name="descripcion" rows="3" 
                                  placeholder="Describe la actividad principal de la empresa" required>{{ old('descripcion') }}</textarea>
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
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Vista previa</label>
                            <div id="logoPreview" class="border rounded p-3 text-center" style="min-height: 120px; display: flex; align-items: center; justify-content: center;">
                                <span class="text-muted">
                                    <i class="fas fa-image fa-2x mb-2"></i><br>
                                    Sin logo seleccionado
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" 
                                       id="estado" name="estado" checked>
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
                                       id="editable" name="editable">
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
                        <a href="{{ route('empresas') }}" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Crear Empresa
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
                    Información Importante
                </h6>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <h6 class="alert-heading">
                        <i class="fas fa-lightbulb me-2"></i>
                        Consejos
                    </h6>
                    <ul class="mb-0">
                        <li>El nombre debe ser único en el sistema</li>
                        <li>El administrador será el encargado de gestionar la empresa</li>
                        <li>Una descripción clara ayuda a identificar la empresa</li>
                        <li>El logo aparecerá en reportes y documentos</li>
                    </ul>
                </div>
                
                <div class="alert alert-warning">
                    <h6 class="alert-heading">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Importante
                    </h6>
                    <p class="mb-0">
                        Una vez creada la empresa, el administrador asignado podrá:
                    </p>
                    <ul class="mb-0 mt-2">
                        <li>Crear y gestionar usuarios</li>
                        <li>Configurar flujos de trabajo</li>
                        <li>Administrar productos y clientes</li>
                        <li>Generar reportes</li>
                    </ul>
                </div>
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
    } else {
        preview.innerHTML = `
            <span class="text-muted">
                <i class="fas fa-image fa-2x mb-2"></i><br>
                Sin logo seleccionado
            </span>
        `;
    }
}

// Validación de archivo
document.getElementById('logo').addEventListener('change', function() {
    const file = this.files[0];
    if (file) {
        // Validar tamaño (2MB máximo)
        if (file.size > 2 * 1024 * 1024) {
            alert('El archivo es demasiado grande. Máximo 2MB permitido.');
            this.value = '';
            document.getElementById('logoPreview').innerHTML = `
                <span class="text-muted">
                    <i class="fas fa-image fa-2x mb-2"></i><br>
                    Sin logo seleccionado
                </span>
            `;
            return;
        }
        
        // Validar tipo de archivo
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!allowedTypes.includes(file.type)) {
            alert('Tipo de archivo no permitido. Use JPG, PNG o GIF.');
            this.value = '';
            document.getElementById('logoPreview').innerHTML = `
                <span class="text-muted">
                    <i class="fas fa-image fa-2x mb-2"></i><br>
                    Sin logo seleccionado
                </span>
            `;
            return;
        }
    }
});
</script>
@endpush
