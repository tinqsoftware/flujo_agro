@extends('layouts.dashboard')

@section('title', 'Panel de Administración - AGROEMSE')
@section('page-title', 'Panel de Administración')
@section('page-subtitle', Auth::user()->empresa ? 'Gestión de Flujos de Trabajo - ' . Auth::user()->empresa->nombre : 'Panel de Administración')

@section('sidebar-menu')
    <a href="{{ route('admin.dashboard') }}" class="nav-link active">
        <i class="fas fa-tachometer-alt"></i>
        Dashboard
    </a>
    <a href="#" class="nav-link">
        <i class="fas fa-project-diagram"></i>
        Flujos de Trabajo
        <span class="badge bg-primary ms-auto">{{ $flujosActivos }}</span>
    </a>
    <a href="#" class="nav-link">
        <i class="fas fa-cube"></i>
        Productos
        <span class="badge bg-info ms-auto">{{ $productos }}</span>
    </a>
    <a href="#" class="nav-link">
        <i class="fas fa-users"></i>
        Clientes
        <span class="badge bg-success ms-auto">{{ $clientesActivos }}</span>
    </a>
    <a href="#" class="nav-link">
        <i class="fas fa-truck"></i>
        Proveedores
        <span class="badge bg-warning ms-auto">15</span>
    </a>
    <a href="#" class="nav-link">
        <i class="fas fa-file-alt"></i>
        Documentos
    </a>
    <a href="#" class="nav-link">
        <i class="fas fa-clipboard-list"></i>
        Fichas Técnicas
    </a>
    <a href="{{ route('admin.usuarios') }}" class="nav-link">
        <i class="fas fa-user-friends"></i>
        Usuarios de Empresa
    </a>
    <a href="#" class="nav-link">
        <i class="fas fa-cog"></i>
        Configuración
    </a>
@endsection

@section('header-actions')
    <a href="#" class="btn btn-light me-2">
        <i class="fas fa-download me-2"></i>Reportes
    </a>
    <a href="#" class="btn btn-light">
        <i class="fas fa-plus me-2"></i>Nuevo Flujo
    </a>
@endsection

@section('content-area')
<!-- Estadísticas Principales -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stats-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3>{{ $flujosActivos }}</h3>
                    <p>Flujos Activos</p>
                    <small><i class="fas fa-arrow-up me-1"></i>+3 esta semana</small>
                </div>
                <i class="fas fa-project-diagram fa-2x opacity-75"></i>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stats-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3>{{ $productos }}</h3>
                    <p>Productos</p>
                    <small><i class="fas fa-arrow-up me-1"></i>+2 este mes</small>
                </div>
                <i class="fas fa-cube fa-2x opacity-75"></i>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stats-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3>{{ $clientesActivos }}</h3>
                    <p>Clientes Activos</p>
                    <small><i class="fas fa-arrow-up me-1"></i>+5 nuevos</small>
                </div>
                <i class="fas fa-users fa-2x opacity-75"></i>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stats-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3>{{ $etapasCompletadas }}</h3>
                    <p>Etapas Completadas</p>
                    <small>Este mes</small>
                </div>
                <i class="fas fa-check-circle fa-2x opacity-75"></i>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Flujos de Trabajo en Ejecución -->
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-project-diagram me-2 text-success"></i>
                    Flujos de Trabajo en Ejecución
                </h5>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary active">
                        <i class="fas fa-play me-1"></i>Flujos Activos
                    </button>
                    <button class="btn btn-outline-secondary">
                        <i class="fas fa-wrench me-1"></i>Constructor
                    </button>
                    <button class="btn btn-outline-info">
                        <i class="fas fa-file-alt me-1"></i>Plantillas
                    </button>
                    <button class="btn btn-outline-warning">
                        <i class="fas fa-chart-bar me-1"></i>Análisis
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Flujo</th>
                                <th>Cliente</th>
                                <th>Producto</th>
                                <th>Etapa Actual</th>
                                <th>Progreso</th>
                                <th>Fecha Inicio</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary rounded p-2 me-2">
                                            <i class="fas fa-leaf text-white"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold">Exportación Cítricos Q1</div>
                                            <small class="text-muted">FLJ-2024-001</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <div>Fresh Fruits International</div>
                                        <small class="text-muted">Estados Unidos</small>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-info">Naranjas Valencia</span>
                                </td>
                                <td>
                                    <span class="badge bg-warning">Inspección de Calidad</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="progress me-2" style="width: 100px; height: 8px;">
                                            <div class="progress-bar bg-success" style="width: 75%"></div>
                                        </div>
                                        <small class="text-muted">75%</small>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <div>15/01/2024</div>
                                        <small class="text-muted">hace 3 días</small>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary" title="Ver detalles">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-outline-success" title="Avanzar etapa">
                                            <i class="fas fa-arrow-right"></i>
                                        </button>
                                        <button class="btn btn-outline-info" title="Documentos">
                                            <i class="fas fa-file-alt"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-success rounded p-2 me-2">
                                            <i class="fas fa-apple-alt text-white"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold">Procesamiento Mangos</div>
                                            <small class="text-muted">FLJ-2024-002</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <div>Tropical Export Ltd</div>
                                        <small class="text-muted">Reino Unido</small>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-success">Mango Tommy</span>
                                </td>
                                <td>
                                    <span class="badge bg-info">Empaque y Etiquetado</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="progress me-2" style="width: 100px; height: 8px;">
                                            <div class="progress-bar bg-info" style="width: 60%"></div>
                                        </div>
                                        <small class="text-muted">60%</small>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <div>12/01/2024</div>
                                        <small class="text-muted">hace 6 días</small>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary" title="Ver detalles">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-outline-success" title="Avanzar etapa">
                                            <i class="fas fa-arrow-right"></i>
                                        </button>
                                        <button class="btn btn-outline-info" title="Documentos">
                                            <i class="fas fa-file-alt"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="text-center mt-3">
                    <a href="#" class="btn btn-outline-primary">
                        <i class="fas fa-plus me-2"></i>Ver todos los flujos
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Panel lateral -->
    <div class="col-lg-4">
        <!-- Actividad Reciente -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="fas fa-clock me-2"></i>
                    Actividad Reciente
                </h6>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-marker bg-success"></div>
                        <div class="timeline-content">
                            <h6 class="timeline-title">Flujo completado</h6>
                            <p class="timeline-text">Exportación de aguacates finalizada exitosamente</p>
                            <small class="text-muted">hace 2 horas</small>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-marker bg-warning"></div>
                        <div class="timeline-content">
                            <h6 class="timeline-title">Inspección pendiente</h6>
                            <p class="timeline-text">Lote de naranjas requiere inspección de calidad</p>
                            <small class="text-muted">hace 4 horas</small>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-marker bg-info"></div>
                        <div class="timeline-content">
                            <h6 class="timeline-title">Nuevo cliente</h6>
                            <p class="timeline-text">Fresh Fruits International se registró en el sistema</p>
                            <small class="text-muted">ayer</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Resumen Rápido -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="fas fa-chart-pie me-2"></i>
                    Resumen Rápido
                </h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6 mb-3">
                        <div class="h5 text-primary mb-1">8</div>
                        <small class="text-muted">Flujos en Proceso</small>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="h5 text-success mb-1">12</div>
                        <small class="text-muted">Completados</small>
                    </div>
                    <div class="col-6">
                        <div class="h5 text-warning mb-1">3</div>
                        <small class="text-muted">Pendientes</small>
                    </div>
                    <div class="col-6">
                        <div class="h5 text-danger mb-1">1</div>
                        <small class="text-muted">Con Retrasos</small>
                    </div>
                </div>
                
                <hr>
                
                <div class="d-grid gap-2">
                    <button class="btn btn-primary btn-sm">
                        <i class="fas fa-plus me-2"></i>Crear Nuevo Flujo
                    </button>
                    <button class="btn btn-outline-info btn-sm">
                        <i class="fas fa-chart-bar me-2"></i>Ver Análisis Completo
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 20px;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -25px;
    top: 5px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
}

.timeline-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: -21px;
    top: 15px;
    width: 2px;
    height: calc(100% + 10px);
    background-color: #e5e7eb;
}

.timeline-title {
    font-size: 0.9rem;
    font-weight: 600;
    margin-bottom: 5px;
}

.timeline-text {
    font-size: 0.85rem;
    color: #6b7280;
    margin-bottom: 5px;
}
</style>
@endsection
