<div class="col-md-3 col-lg-2 p-0">
    <div class="sidebar">
        <!-- Brand Section -->
        <div class="brand-section">
            <div class="brand-icon">
                <i class="fas fa-cube"></i>
            </div>
            <h5 class="mb-0">AGROEMSE</h5>
            <small class="text-muted">Agro Empaques y Servicios</small>
        </div>

        <!-- Navigation -->
        <nav class="nav flex-column pt-3">
            <!-- Gestión Principal -->
            <div class="section-title">Gestión Principal</div>
            
            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                <i class="fas fa-home me-2"></i>
                Dashboard
            </a>
            
            <a class="nav-link {{ request()->routeIs('empresa.*') ? 'active' : '' }}" href="{{ route('empresa.index') }}">
                <i class="fas fa-building me-2"></i>
                Información de Empresa
            </a>
            
            <a class="nav-link {{ request()->routeIs('productos.*') ? 'active' : '' }}" href="{{ route('productos.index') }}">
                <i class="fas fa-box me-2"></i>
                Productos
                <span class="badge-count ms-auto">12</span>
            </a>
            
            <a class="nav-link {{ request()->routeIs('flujos.*') ? 'active' : '' }}" href="{{ route('flujos.index') }}">
                <i class="fas fa-project-diagram me-2"></i>
                Flujos de Trabajo
                <i class="fas fa-chevron-right ms-auto"></i>
            </a>
            
            <a class="nav-link {{ request()->routeIs('documentos.*') ? 'active' : '' }}" href="{{ route('documentos.index') }}">
                <i class="fas fa-file-alt me-2"></i>
                Documentos
                <i class="fas fa-chevron-right ms-auto"></i>
            </a>
            
            <a class="nav-link {{ request()->routeIs('clientes.*') ? 'active' : '' }}" href="{{ route('clientes.index') }}">
                <i class="fas fa-users me-2"></i>
                Clientes
                <span class="badge-count ms-auto">8</span>
            </a>
            
            <a class="nav-link {{ request()->routeIs('proveedores.*') ? 'active' : '' }}" href="{{ route('proveedores.index') }}">
                <i class="fas fa-truck me-2"></i>
                Proveedores
            </a>
            
            <a class="nav-link {{ request()->routeIs('fichas.*') ? 'active' : '' }}" href="{{ route('fichas.index') }}">
                <i class="fas fa-clipboard-list me-2"></i>
                Fichas Técnicas
                <i class="fas fa-chevron-right ms-auto"></i>
            </a>
            
            <a class="nav-link {{ request()->routeIs('reportes.*') ? 'active' : '' }}" href="{{ route('reportes.index') }}">
                <i class="fas fa-chart-bar me-2"></i>
                Reportes
            </a>

            <!-- Administración -->
            <div class="section-title mt-4">Administración</div>
            
            <a class="nav-link {{ request()->routeIs('usuarios.*') ? 'active' : '' }}" href="{{ route('usuarios.index') }}">
                <i class="fas fa-user-cog me-2"></i>
                Usuarios y Roles
            </a>
            
            <a class="nav-link {{ request()->routeIs('configuracion.*') ? 'active' : '' }}" href="{{ route('configuracion.index') }}">
                <i class="fas fa-cog me-2"></i>
                Configuración
            </a>
        </nav>

        <!-- User Section -->
        <div class="user-section">
            <div class="d-flex align-items-center">
                <div class="user-avatar">
                    JD
                </div>
                <div class="ms-2">
                    <div class="fw-bold">Juan Pérez</div>
                    <small class="text-muted">Administrador</small>
                </div>
            </div>
        </div>
    </div>
</div>
