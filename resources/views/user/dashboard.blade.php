@extends('layouts.dashboard')

@section('title', 'Panel Usuario - AGROEMSE')
@section('page-title', 'Panel de Usuario')
@section('page-subtitle', Auth::user()->empresa ? 'Gestión de Tareas - ' . Auth::user()->empresa->nombre : 'Panel de Usuario')

@section('sidebar-menu')
    <a href="{{ route('user.dashboard') }}" class="nav-link active">
        <i class="fas fa-tachometer-alt"></i>
        Mi Dashboard
    </a>
    <a href="#" class="nav-link">
        <i class="fas fa-tasks"></i>
        Mis Tareas
        <span class="badge bg-warning ms-auto">{{ $tareasAsignadas }}</span>
    </a>
    <a href="#" class="nav-link">
        <i class="fas fa-file-alt"></i>
        Documentos
        <span class="badge bg-info ms-auto">{{ $documentosPendientes }}</span>
    </a>
    <a href="#" class="nav-link">
        <i class="fas fa-project-diagram"></i>
        Flujos en Proceso
        <span class="badge bg-primary ms-auto">{{ $flujosEnProceso }}</span>
    </a>
    <a href="#" class="nav-link">
        <i class="fas fa-clipboard-list"></i>
        Fichas Técnicas
    </a>
    <a href="#" class="nav-link">
        <i class="fas fa-calendar-alt"></i>
        Calendario
    </a>
    <a href="#" class="nav-link">
        <i class="fas fa-bell"></i>
        Notificaciones
        @if($notificaciones > 0)
            <span class="badge bg-danger ms-auto">{{ $notificaciones }}</span>
        @endif
    </a>
    <a href="{{ route('user.perfil') }}" class="nav-link">
        <i class="fas fa-user-edit"></i>
        Mi Perfil
    </a>
@endsection

@section('header-actions')
    <div class="dropdown">
        <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
            <i class="fas fa-plus me-2"></i>Acciones Rápidas
        </button>
        <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="#"><i class="fas fa-file-plus me-2"></i>Nuevo Documento</a></li>
            <li><a class="dropdown-item" href="#"><i class="fas fa-tasks me-2"></i>Reportar Progreso</a></li>
            <li><a class="dropdown-item" href="#"><i class="fas fa-camera me-2"></i>Subir Evidencia</a></li>
        </ul>
    </div>
@endsection

@section('content-area')
<!-- Resumen Personal -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stats-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3>{{ $tareasAsignadas }}</h3>
                    <p>Tareas Asignadas</p>
                    <small><i class="fas fa-clock me-1"></i>5 pendientes</small>
                </div>
                <i class="fas fa-tasks fa-2x opacity-75"></i>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stats-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3>{{ $documentosPendientes }}</h3>
                    <p>Documentos Pendientes</p>
                    <small><i class="fas fa-exclamation-circle me-1"></i>Revisar</small>
                </div>
                <i class="fas fa-file-alt fa-2x opacity-75"></i>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stats-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3>{{ $flujosEnProceso }}</h3>
                    <p>Flujos en Proceso</p>
                    <small><i class="fas fa-arrow-right me-1"></i>Participando</small>
                </div>
                <i class="fas fa-project-diagram fa-2x opacity-75"></i>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="stats-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3>{{ $notificaciones }}</h3>
                    <p>Notificaciones</p>
                    <small><i class="fas fa-bell me-1"></i>Sin leer</small>
                </div>
                <i class="fas fa-bell fa-2x opacity-75"></i>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Tareas Pendientes -->
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-tasks me-2 text-warning"></i>
                    Mis Tareas Pendientes
                </h5>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-warning active">
                        <i class="fas fa-clock me-1"></i>Pendientes
                    </button>
                    <button class="btn btn-outline-success">
                        <i class="fas fa-check me-1"></i>Completadas
                    </button>
                    <button class="btn btn-outline-info">
                        <i class="fas fa-calendar me-1"></i>Programadas
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <div class="list-group-item border-0 px-0">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="d-flex align-items-start">
                                <div class="form-check me-3 mt-1">
                                    <input class="form-check-input" type="checkbox" id="tarea1">
                                </div>
                                <div>
                                    <h6 class="mb-1">Inspección de calidad - Lote #2024-001</h6>
                                    <p class="mb-1 text-muted">Revisar y documentar la calidad de las naranjas del lote para exportación.</p>
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="badge bg-warning">Alta Prioridad</span>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>Vence en 2 horas
                                        </small>
                                        <small class="text-muted">
                                            <i class="fas fa-user me-1"></i>Asignado por María González
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" title="Ver detalles">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-outline-success" title="Marcar como completada">
                                    <i class="fas fa-check"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="list-group-item border-0 px-0">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="d-flex align-items-start">
                                <div class="form-check me-3 mt-1">
                                    <input class="form-check-input" type="checkbox" id="tarea2">
                                </div>
                                <div>
                                    <h6 class="mb-1">Actualizar documentación de proceso</h6>
                                    <p class="mb-1 text-muted">Subir fotos y completar formulario de seguimiento del flujo de mangos.</p>
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="badge bg-info">Media Prioridad</span>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>Vence hoy
                                        </small>
                                        <small class="text-muted">
                                            <i class="fas fa-user me-1"></i>Asignado por Carlos Rodríguez
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" title="Ver detalles">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-outline-success" title="Marcar como completada">
                                    <i class="fas fa-check"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="list-group-item border-0 px-0">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="d-flex align-items-start">
                                <div class="form-check me-3 mt-1">
                                    <input class="form-check-input" type="checkbox" id="tarea3">
                                </div>
                                <div>
                                    <h6 class="mb-1">Preparar reporte semanal</h6>
                                    <p class="mb-1 text-muted">Compilar datos de productividad y eficiencia de la semana.</p>
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="badge bg-secondary">Baja Prioridad</span>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>Vence el viernes
                                        </small>
                                        <small class="text-muted">
                                            <i class="fas fa-user me-1"></i>Asignado por Sistema
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" title="Ver detalles">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-outline-success" title="Marcar como completada">
                                    <i class="fas fa-check"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-3">
                    <a href="#" class="btn btn-outline-primary">
                        <i class="fas fa-tasks me-2"></i>Ver todas mis tareas
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Panel lateral -->
    <div class="col-lg-4">
        <!-- Calendario y Próximas Fechas -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="fas fa-calendar-alt me-2"></i>
                    Próximas Fechas Importantes
                </h6>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-marker bg-warning"></div>
                        <div class="timeline-content">
                            <h6 class="timeline-title">Inspección de Calidad</h6>
                            <p class="timeline-text">Lote #2024-001 - Naranjas Valencia</p>
                            <small class="text-muted">Hoy, 14:00</small>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-marker bg-info"></div>
                        <div class="timeline-content">
                            <h6 class="timeline-title">Reunión de seguimiento</h6>
                            <p class="timeline-text">Revisión de flujos de trabajo del mes</p>
                            <small class="text-muted">Mañana, 09:00</small>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-marker bg-success"></div>
                        <div class="timeline-content">
                            <h6 class="timeline-title">Entrega de documentos</h6>
                            <p class="timeline-text">Reportes mensuales de productividad</p>
                            <small class="text-muted">Viernes, 17:00</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Notificaciones Recientes -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="fas fa-bell me-2"></i>
                    Notificaciones Recientes
                </h6>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <div class="list-group-item border-0 px-0 py-2">
                        <div class="d-flex align-items-start">
                            <div class="bg-primary rounded-circle p-2 me-2">
                                <i class="fas fa-tasks text-white fa-sm"></i>
                            </div>
                            <div class="flex-grow-1">
                                <p class="mb-1 small">Nueva tarea asignada: "Inspección de calidad"</p>
                                <small class="text-muted">hace 30 minutos</small>
                            </div>
                        </div>
                    </div>
                    <div class="list-group-item border-0 px-0 py-2">
                        <div class="d-flex align-items-start">
                            <div class="bg-success rounded-circle p-2 me-2">
                                <i class="fas fa-check text-white fa-sm"></i>
                            </div>
                            <div class="flex-grow-1">
                                <p class="mb-1 small">Flujo "Exportación Aguacates" completado</p>
                                <small class="text-muted">hace 2 horas</small>
                            </div>
                        </div>
                    </div>
                    <div class="list-group-item border-0 px-0 py-2">
                        <div class="d-flex align-items-start">
                            <div class="bg-info rounded-circle p-2 me-2">
                                <i class="fas fa-file-alt text-white fa-sm"></i>
                            </div>
                            <div class="flex-grow-1">
                                <p class="mb-1 small">Documento actualizado en el sistema</p>
                                <small class="text-muted">ayer</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-3">
                    <a href="#" class="btn btn-outline-info btn-sm">
                        <i class="fas fa-bell me-1"></i>Ver todas
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Acceso Rápido -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="fas fa-bolt me-2"></i>
                    Acceso Rápido
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-camera me-2"></i>Subir Evidencia Fotográfica
                    </button>
                    <button class="btn btn-outline-success btn-sm">
                        <i class="fas fa-check-circle me-2"></i>Reportar Progreso
                    </button>
                    <button class="btn btn-outline-info btn-sm">
                        <i class="fas fa-file-download me-2"></i>Descargar Formatos
                    </button>
                    <button class="btn btn-outline-warning btn-sm">
                        <i class="fas fa-question-circle me-2"></i>Solicitar Ayuda
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
