@extends('layouts.modern')

@section('content')
<div class="d-flex">
    <!-- Sidebar -->
    <div class="sidebar d-flex flex-column p-3" style="width: 250px;">
        <div class="text-center mb-4">
            <h4 class="mb-0">
                <div class="logo">
                    <img src="/access/logo.jpg"  style="width:80%" alt="Logo AGROEMSE">
                </div>
            </h4>
            <small class="opacity-75"><b>{{ Auth::user()->name }}</b></small><br/>
            <small class="opacity-75">{{ Auth::user()->rol->nombre }}</small>
        </div>
        
        <nav class="nav nav-pills flex-column">
           <!-- Navegación común para todos -->
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('superadmin.dashboard') ? 'active' : '' }}">
                <i class="fas fa-tachometer-alt"></i>
                Panel
            </a>
            
            @if(Auth::user()->rol->nombre === 'SUPERADMIN')
                <!-- Solo SuperAdmin ve gestión de empresas -->
                <a href="{{ route('empresas') }}" class="nav-link {{ request()->routeIs('superadmin.empresas*') ? 'active' : '' }}">
                    <i class="fas fa-building"></i>
                    Empresas
                </a>
            @endif

            @if(Auth::user()->rol->nombre === 'SUPERADMIN')
                <!-- Solo SuperAdmin ve gestión de empresas -->
                <a href="{{ route('fichas.index') }}" class="nav-link {{ request()->routeIs('superadmin.fichas*') ? 'active' : '' }}">
                    <i class="fas fa-building"></i>
                    Fichas
                </a>

                <a href="{{ route('form-types.index')}}" class="nav-link ">
                    <i class="fas fa-building"></i>
                    Tipos
                </a>

                <a href="{{ route('forms.index') }}" class="nav-link " >
                    <i class="fas fa-building"></i>
                    Formularios
                </a>

                <a href="{{ route('form-runs.index') }}" class="nav-link ">
                    <i class="fas fa-building"></i>
                    Ejecuciones
                </a>

            @endif
            
            @if(in_array(Auth::user()->rol->nombre, ['SUPERADMIN', 'ADMINISTRADOR']))
                <!-- Solo SuperAdmin ve gestión de roles -->
                <a href="{{ route('roles') }}" class="nav-link {{ request()->routeIs('superadmin.roles*') ? 'active' : '' }}">
                    <i class="fas fa-users-cog"></i>
                    Roles
                </a>
                <!-- SuperAdmin y Administrador ven gestión de usuarios -->
                <a href="{{ route('usuarios') }}" class="nav-link {{ request()->routeIs('superadmin.usuarios*') ? 'active' : '' }}">
                    <i class="fas fa-users"></i>
                    Usuarios
                </a>
                <a href="{{ route('tipo-flujo.index') }}" class="nav-link {{ request()->routeIs('tipo-flujo.*') ? 'active' : '' }}">
                    <i class="fas fa-diagram-project"></i>
                    Tipos de flujo
                </a>
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('flujos*') ? 'active' : '' }}" href="{{ route('flujos.index') }}">
                        <i class="fas fa-project-diagram me-1"></i> Flujos
                    </a>
                </li>
                @if((Auth::user()->empresa && Auth::user()->empresa->editable == '1') )
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('ejecucion*') ? 'active' : '' }}" href="{{ route('ejecucion.index') }}">
                            <i class="fas fa-project-diagram me-1"></i> Ejecución
                        </a>
                    </li>
                @endif
            @endif


            <!-- (todos los usuarios) -->
            <a href="{{ route('clientes.index') }}" class="nav-link {{ request()->routeIs('clientes*') ? 'active' : '' }}">
                <i class="fas fa-user-friends"></i>
                Clientes
            </a>
            <a href="{{ route('productos.index') }}" class="nav-link {{ request()->routeIs('productos*') ? 'active' : '' }}">
                <i class="fas fa-box"></i>
                Productos
            </a>
            <a href="{{ route('proveedores.index') }}" class="nav-link {{ request()->routeIs('proveedores*') ? 'active' : '' }}">
                <i class="fas fa-truck"></i>
                Proveedores
            </a>

            
            
            <!-- Perfil para todos -->
            <a href="{{ route('perfil') }}" class="nav-link {{ request()->routeIs('superadmin.perfil*') ? 'active' : '' }}">
                <i class="fas fa-user-circle"></i>
                Mi Perfil
            </a>
        </nav>
        
        <div class="mt-auto">
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" 
                   data-bs-toggle="dropdown">
                    <div class="bg-white bg-opacity-25 rounded-circle p-2 me-2">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <div class="fw-semibold">{{ Auth::user()->nombres }}</div>
                        <small class="opacity-75">{{ Auth::user()->email }}</small>
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-dark">
                    @if(Auth::user()->rol->nombre === 'ADMINISTRATIVO' || Auth::user()->rol->nombre === 'ADMINISTRADOR' || Auth::user()->rol->nombre === 'SUPERADMIN')
                        <li>
                            <a class="dropdown-item" href="{{ route('perfil') }}">
                                <i class="fas fa-user-edit me-2"></i>Mi Perfil
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                    @endif
                    <li>
                        <a class="dropdown-item" href="{{ route('logout') }}"
                           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
                        </a>
                    </li>
                </ul>
            </div>
            
            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                @csrf
            </form>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="flex-grow-1">
        <!-- Mobile Menu Button -->
        <div class="d-lg-none">
            <button class="btn btn-primary m-3" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        
        <div class="main-content">
            <div class="header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h3 mb-1">@yield('page-title')</h1>
                        <p class="mb-0 opacity-75">@yield('page-subtitle')</p>
                    </div>
                    <div>
                        @yield('header-actions')
                    </div>
                </div>
            </div>
            
            <div class="content-area">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if (session('warning'))
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        {{ session('warning') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                
                @yield('content-area')
            </div>
        </div>
    </div>
</div>
@endsection
