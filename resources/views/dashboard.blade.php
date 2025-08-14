@extends('layouts.dashboard')

@section('title', 'Dashboard - AGROEMSE')
@section('page-title', 'Panel de Control')
@section('page-subtitle', 'Sistema de Gestión AGROEMSE')

@section('header-actions')
    @if(Auth::user()->rol->nombre === 'SUPERADMIN')
        <a href="{{ route('empresas.create') }}" class="btn btn-light me-2">
            <i class="fas fa-plus me-2"></i>Nueva Empresa
        </a>
    @endif
    
    @if(in_array(Auth::user()->rol->nombre, ['SUPERADMIN', 'ADMINISTRADOR']))
        <a href="{{ route('usuarios.create') }}" class="btn btn-light">
            <i class="fas fa-user-plus me-2"></i>Nuevo Usuario
        </a>
    @endif
@endsection

@section('content-area')
<!-- Estadísticas principales -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stats-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3>{{ $empresasRegistradas }}</h3>
                    <p>Empresas</p>
                    <small class="text-muted">Total registradas</small>
                </div>
                <i class="fas fa-building text-primary"></i>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stats-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3>{{ $usuariosActivos }}</h3>
                    <p>Usuarios Activos</p>
                    <small class="text-muted">De {{ $usuariosTotal }} total</small>
                </div>
                <i class="fas fa-users text-success"></i>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stats-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3>{{ $rolesActivos }}</h3>
                    <p>Roles Activos</p>
                    <small class="text-muted">En el sistema</small>
                </div>
                <i class="fas fa-users-cog text-info"></i>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stats-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3>{{ Auth::user()->rol->nombre }}</h3>
                    <p>Tu Rol</p>
                    <small class="text-muted">Nivel de acceso</small>
                </div>
                <i class="fas fa-id-badge text-warning"></i>
            </div>
        </div>
    </div>
</div>

<div class="row">
    @if($rol === 'SUPERADMIN' && $empresas->count() > 0)
    <!-- Lista de empresas para SuperAdmin -->
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-building me-2"></i>
                    Empresas Recientes
                </h5>
                <a href="{{ route('empresas') }}" class="btn btn-outline-primary btn-sm">
                    Ver todas
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Empresa</th>
                                <th>Administrador</th>
                                <th>Estado</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($empresas as $empresa)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        @if($empresa->ruta_logo)
                                            <img src="{{ Storage::url($empresa->ruta_logo) }}" 
                                                 class="rounded me-2" width="24" height="24" alt="Logo">
                                        @else
                                            <div class="bg-primary rounded d-flex align-items-center justify-content-center me-2" 
                                                 style="width: 24px; height: 24px;">
                                                <i class="fas fa-building text-white" style="font-size: 10px;"></i>
                                            </div>
                                        @endif
                                        <small class="fw-semibold">{{ $empresa->nombre }}</small>
                                    </div>
                                </td>
                                <td>
                                    <small>{{ $empresa->userAdmin->nombres ?? 'Sin asignar' }}</small>
                                </td>
                                <td>
                                    <span class="badge {{ $empresa->estado ? 'bg-success' : 'bg-danger' }} badge-sm">
                                        {{ $empresa->estado ? 'Activa' : 'Inactiva' }}
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted">{{ $empresa->created_at->format('d/m/Y') }}</small>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-3">
                                    <small class="text-muted">No hay empresas registradas</small>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif
    
    @if(in_array($rol, ['SUPERADMIN', 'ADMINISTRADOR']) && $usuariosRecientes->count() > 0)
    <!-- Lista de usuarios recientes -->
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-users me-2"></i>
                    Usuarios Recientes
                </h5>
                <a href="{{ route('usuarios') }}" class="btn btn-outline-primary btn-sm">
                    Ver todos
                </a>
            </div>
            <div class="card-body">
                @forelse($usuariosRecientes as $usuario)
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center me-2" 
                         style="width: 32px; height: 32px;">
                        <span class="text-white fw-semibold" style="font-size: 12px;">
                            {{ strtoupper(substr($usuario->nombres, 0, 1) . substr($usuario->apellidos, 0, 1)) }}
                        </span>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-semibold" style="font-size: 14px;">{{ $usuario->nombres }} {{ $usuario->apellidos }}</div>
                        <small class="text-muted">{{ $usuario->rol->nombre ?? 'Sin rol' }}</small>
                    </div>
                    <span class="badge {{ $usuario->estado ? 'bg-success' : 'bg-danger' }} badge-sm">
                        {{ $usuario->estado ? 'Activo' : 'Inactivo' }}
                    </span>
                </div>
                @empty
                <div class="text-center py-3">
                    <small class="text-muted">No hay usuarios registrados</small>
                </div>
                @endforelse
            </div>
        </div>
    </div>
    @endif
    
    @if($rol === 'ADMINISTRATIVO' || ($rol === 'ADMINISTRADOR' && Auth::user()->empresa))
    <!-- Información de la empresa para Administrador y Administrativo -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-building me-2"></i>
                    Mis datos
                </h5>
            </div>
            <div class="card-body">
                @if(Auth::user()->empresa)
                    <div class="d-flex align-items-center mb-3">
                        @if(Auth::user()->empresa->ruta_logo)
                            <img src="{{ Storage::url(Auth::user()->empresa->ruta_logo) }}" 
                                 class="rounded me-3" width="48" height="48" alt="Logo">
                        @else
                            <div class="bg-primary rounded d-flex align-items-center justify-content-center me-3" 
                                 style="width: 48px; height: 48px;">
                                <i class="fas fa-building text-white"></i>
                            </div>
                        @endif
                        <div>
                            <h6 class="mb-1">{{ Auth::user()->empresa->nombre }}</h6>
                            <small class="text-muted">{{ Auth::user()->empresa->descripcion }}</small>
                        </div>
                    </div>
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="fw-semibold">{{ Auth::user()->empresa->users->where('estado', true)->count() }}</div>
                            <small class="text-muted">Usuarios Activos</small>
                        </div>
                        <div class="col-6">
                            <div class="fw-semibold">{{ Auth::user()->empresa->created_at->format('Y') }}</div>
                            <small class="text-muted">Año de Registro</small>
                        </div>
                    </div>
                @else
                    <div class="text-center py-3">
                        <i class="fas fa-building fa-2x text-muted mb-2"></i>
                        <p class="text-muted">No tienes empresa asignada</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
    @endif
    
    <!-- Información del usuario actual -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-user me-2"></i>
                    Mi Información
                </h5>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3" 
                         style="width: 48px; height: 48px;">
                        <span class="text-white fw-semibold">
                            {{ strtoupper(substr(Auth::user()->nombres, 0, 1) . substr(Auth::user()->apellidos, 0, 1)) }}
                        </span>
                    </div>
                    <div>
                        <h6 class="mb-1">{{ Auth::user()->nombres }} {{ Auth::user()->apellidos }}</h6>
                        <small class="text-muted">{{ Auth::user()->email }}</small>
                    </div>
                </div>
                <div class="row text-center">
                    <div class="col-6">
                        <span class="badge bg-primary mb-1">{{ Auth::user()->rol->nombre }}</span>
                        <div><small class="text-muted">Rol Asignado</small></div>
                    </div>
                    <div class="col-6">
                        <span class="badge {{ Auth::user()->estado ? 'bg-success' : 'bg-danger' }} mb-1">
                            {{ Auth::user()->estado ? 'Activo' : 'Inactivo' }}
                        </span>
                        <div><small class="text-muted">Estado</small></div>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="{{ route('perfil') }}" class="btn btn-outline-primary btn-sm w-100">
                        <i class="fas fa-edit me-2"></i>Editar Perfil
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
