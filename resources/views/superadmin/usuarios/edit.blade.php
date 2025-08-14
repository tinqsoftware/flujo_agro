@extends('layouts.dashboard')

@section('title', 'Editar Usuario - AGROEMSE')
@section('page-title', 'Editar Usuario')
@section('page-subtitle', 'Modificar información del usuario')


@section('header-actions')
    <a href="{{ route('usuarios') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i>Volver a Usuarios
    </a>
@endsection

@section('content-area')
<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-user-edit me-2"></i>
                    Editar Usuario: {{ $usuario->nombres }} {{ $usuario->apellidos }}
                </h5>
                <span class="badge {{ $usuario->estado ? 'bg-success' : 'bg-danger' }}">
                    {{ $usuario->estado ? 'Activo' : 'Inactivo' }}
                </span>
            </div>
            <div class="card-body">
                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Por favor, corrige los siguientes errores:</strong>
                        <ul class="mb-0 mt-2">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <form action="{{ route('usuarios.update', $usuario) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nombres" class="form-label">Nombres <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('nombres') is-invalid @enderror" 
                                   id="nombres" 
                                   name="nombres" 
                                   value="{{ old('nombres', $usuario->nombres) }}" 
                                   required>
                            @error('nombres')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="apellidos" class="form-label">Apellidos <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('apellidos') is-invalid @enderror" 
                                   id="apellidos" 
                                   name="apellidos" 
                                   value="{{ old('apellidos', $usuario->apellidos) }}" 
                                   required>
                            @error('apellidos')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" 
                                   class="form-control @error('email') is-invalid @enderror" 
                                   id="email" 
                                   name="email" 
                                   value="{{ old('email', $usuario->email) }}" 
                                   required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="celular" class="form-label">Celular</label>
                            <input type="tel" 
                                   class="form-control @error('celular') is-invalid @enderror" 
                                   id="celular" 
                                   name="celular" 
                                   value="{{ old('celular', $usuario->celular) }}">
                            @error('celular')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="sexo" class="form-label">Sexo <span class="text-danger">*</span></label>
                            <select class="form-select @error('sexo') is-invalid @enderror" 
                                    id="sexo" 
                                    name="sexo" 
                                    required>
                                <option value="">Seleccionar...</option>
                                <option value="M" {{ old('sexo', $usuario->sexo) == 'M' ? 'selected' : '' }}>Masculino</option>
                                <option value="F" {{ old('sexo', $usuario->sexo) == 'F' ? 'selected' : '' }}>Femenino</option>
                            </select>
                            @error('sexo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="id_rol" class="form-label">Rol <span class="text-danger">*</span></label>
                            <select class="form-select @error('id_rol') is-invalid @enderror" 
                                    id="id_rol" 
                                    name="id_rol" 
                                    required>
                                <option value="">Seleccionar rol...</option>
                                @foreach($roles as $rol)
                                    <option value="{{ $rol->id }}" {{ old('id_rol', $usuario->id_rol) == $rol->id ? 'selected' : '' }}>
                                        {{ $rol->nombre }}
                                    </option>
                                @endforeach
                            </select>
                            @error('id_rol')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    @if(Auth::user()->rol->nombre === 'SUPERADMIN')
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="id_emp" class="form-label">Empresa <span class="text-danger">*</span></label>
                            <select class="form-select @error('id_emp') is-invalid @enderror" 
                                    id="id_emp" 
                                    name="id_emp" 
                                    required>
                                <option value="">Seleccionar empresa...</option>
                                @foreach($empresas as $empresa)
                                    <option value="{{ $empresa->id }}" {{ old('id_emp', $usuario->id_emp) == $empresa->id ? 'selected' : '' }}>
                                        {{ $empresa->nombre }}
                                    </option>
                                @endforeach
                            </select>
                            @error('id_emp')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="estado" class="form-label">Estado</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="estado" 
                                       name="estado" 
                                       {{ old('estado', $usuario->estado) ? 'checked' : '' }}>
                                <label class="form-check-label" for="estado">
                                    Usuario Activo
                                </label>
                            </div>
                        </div>
                    </div>
                    @endif
                    
                    <hr class="my-4">
                    
                    <h6 class="mb-3">
                        <i class="fas fa-lock me-2"></i>
                        Cambiar Contraseña (Opcional)
                    </h6>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Nueva Contraseña</label>
                            <input type="password" 
                                   class="form-control @error('password') is-invalid @enderror" 
                                   id="password" 
                                   name="password">
                            <small class="form-text text-muted">Dejar en blanco para mantener la contraseña actual</small>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="password_confirmation" class="form-label">Confirmar Nueva Contraseña</label>
                            <input type="password" 
                                   class="form-control" 
                                   id="password_confirmation" 
                                   name="password_confirmation">
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="mb-3">Información Adicional</h6>
                            <p><strong>Fecha de Registro:</strong> {{ $usuario->created_at->format('d/m/Y H:i') }}</p>
                            <p><strong>Última Actualización:</strong> {{ $usuario->updated_at->format('d/m/Y H:i') }}</p>
                            <p><strong>ID:</strong> #{{ $usuario->id }}</p>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('usuarios') }}" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </a>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Actualizar Usuario
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
