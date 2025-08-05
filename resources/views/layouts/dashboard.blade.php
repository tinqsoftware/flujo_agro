@extends('layouts.modern')

@section('content')
<div class="d-flex">
    <!-- Sidebar -->
    <div class="sidebar d-flex flex-column p-3" style="width: 250px;">
        <div class="text-center mb-4">
            <h4 class="mb-0">
                <i class="fas fa-leaf me-2"></i>
                AGROEMSE
            </h4>
            <small class="opacity-75">{{ Auth::user()->rol->nombre }}</small>
        </div>
        
        <nav class="nav nav-pills flex-column">
            @yield('sidebar-menu')
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
                            <a class="dropdown-item" href="{{ route('superadmin.perfil') }}">
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
