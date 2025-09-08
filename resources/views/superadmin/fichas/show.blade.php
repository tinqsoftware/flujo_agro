@extends('layouts.dashboard')

@section('title', 'Vista Previa de Ficha')
@section('page-title', 'Vista Previa de Ficha')
@section('page-subtitle', 'Visualiza cómo se verá esta ficha para los usuarios finales')

@section('header-actions')
    <div class="d-flex gap-2">
        <a href="{{ route('fichas.edit', $ficha) }}" class="btn btn-outline-primary">
            <i class="fas fa-edit me-2"></i>Editar Ficha
        </a>
        <a href="{{ route('fichas.index') }}" class="btn btn-light">
            <i class="fas fa-arrow-left me-2"></i>Volver
        </a>
    </div>
@endsection

@section('content-area')

<div class="row">
    <div class="col-lg-8">
        <!-- Información de la Ficha -->
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Información de la Ficha
                    </h5>
                    <span class="badge bg-{{ $ficha->estado ? 'success' : 'danger' }}">
                        {{ $ficha->estado ? 'Activa' : 'Inactiva' }}
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">
                            <i class="fas fa-tag me-1"></i>Nombre de la Ficha
                        </label>
                        <p class="form-control-plaintext">{{ $ficha->nombre }}</p>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">
                            <i class="fas fa-building me-1"></i>Empresa
                        </label>
                        <div class="d-flex align-items-center">
                            @if($ficha->empresa->ruta_logo)
                                <img src="{{ Storage::url($ficha->empresa->ruta_logo) }}" 
                                     class="rounded me-2" width="30" height="30" alt="Logo">
                            @else
                                <div class="bg-primary rounded d-flex align-items-center justify-content-center me-2" 
                                     style="width: 30px; height: 30px;">
                                    <i class="fas fa-building text-white"></i>
                                </div>
                            @endif
                            <span>{{ $ficha->empresa->nombre }}</span>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">
                            <i class="fas fa-list me-1"></i>Tipo de Ficha
                        </label>
                        <p class="form-control-plaintext">
                            <span class="badge bg-secondary">{{ $ficha->tipo }}</span>
                        </p>
                    </div>
                    
                    @if($contexto)
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">
                            <i class="fas fa-project-diagram me-1"></i>Contexto
                        </label>
                        <div>
                            @if($flujo)
                                <p class="mb-1"><strong>Flujo:</strong> {{ $flujo->nombre }}</p>
                            @endif
                            @if($etapa)
                                <p class="mb-1"><strong>Etapa:</strong> {{ $etapa->nombre }}</p>
                            @endif
                        </div>
                    </div>
                    @endif
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">
                            <i class="fas fa-user me-1"></i>Creado por
                        </label>
                        <p class="form-control-plaintext">{{ $ficha->userCreate->nombres }} {{ $ficha->userCreate->apellidos }}</p>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">
                            <i class="fas fa-calendar me-1"></i>Fecha de Creación
                        </label>
                        <p class="form-control-plaintext">{{ $ficha->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Vista Previa del Formulario -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-eye me-2"></i>
                    Vista Previa del Formulario
                </h5>
                <small class="text-muted">Así es como se verá este formulario para los usuarios finales</small>
            </div>
            <div class="card-body">
                @if($atributos->count() > 0)
                    <form class="preview-form">
                        <div class="row">
                            @foreach($atributos as $atributo)
                                <div class="col-md-{{ $atributo->ancho ?: 6 }} mb-3">
                                    <label class="form-label">
                                        @switch($atributo->tipo)
                                            @case('texto')
                                                <i class="fas fa-font me-1"></i>
                                                @break
                                            @case('cajatexto')
                                                <i class="fas fa-align-left me-1"></i>
                                                @break
                                            @case('decimal')
                                            @case('entero')
                                                <i class="fas fa-calculator me-1"></i>
                                                @break
                                            @case('radio')
                                                <i class="fas fa-dot-circle me-1"></i>
                                                @break
                                            @case('desplegable')
                                                <i class="fas fa-list me-1"></i>
                                                @break
                                            @case('checkbox')
                                                <i class="fas fa-check-square me-1"></i>
                                                @break
                                            @case('fecha')
                                                <i class="fas fa-calendar me-1"></i>
                                                @break
                                            @case('imagen')
                                                <i class="fas fa-image me-1"></i>
                                                @break
                                            @default
                                                <i class="fas fa-question me-1"></i>
                                        @endswitch
                                        {{ $atributo->titulo }}
                                        @if($atributo->obligatorio)
                                            <span class="text-danger">*</span>
                                        @endif
                                    </label>
                                    
                                    @switch($atributo->tipo)
                                        @case('texto')
                                            <input type="text" class="form-control" placeholder="Ingrese {{ strtolower($atributo->titulo) }}" disabled>
                                            @break
                                            
                                        @case('cajatexto')
                                            <textarea class="form-control" rows="3" placeholder="Ingrese {{ strtolower($atributo->titulo) }}" disabled></textarea>
                                            @break
                                            
                                        @case('decimal')
                                            <input type="number" step="0.01" class="form-control" placeholder="0.00" disabled>
                                            @break
                                            
                                        @case('entero')
                                            <input type="number" class="form-control" placeholder="0" disabled>
                                            @break
                                            
                                        @case('radio')
                                            @if($atributo->json && is_array($atributo->json))
                                                @foreach($atributo->json as $index => $opcion)
                                                    <div class="form-check">
                                                        <input class="form-check-input preview-radio" type="radio" 
                                                               name="radio_{{ $atributo->id }}" 
                                                               id="radio_{{ $atributo->id }}_{{ $index }}"
                                                               value="{{ $opcion }}">
                                                        <label class="form-check-label" for="radio_{{ $atributo->id }}_{{ $index }}">
                                                            {{ $opcion }}
                                                        </label>
                                                    </div>
                                                @endforeach
                                            @endif
                                            @break
                                            
                                        @case('desplegable')
                                            <select class="form-select preview-select">
                                                <option value="">Seleccionar opción</option>
                                                @if($atributo->json && is_array($atributo->json))
                                                    @foreach($atributo->json as $opcion)
                                                        <option value="{{ $opcion }}">{{ $opcion }}</option>
                                                    @endforeach
                                                @endif
                                            </select>
                                            @break
                                            
                                        @case('checkbox')
                                            @if($atributo->json && is_array($atributo->json))
                                                @foreach($atributo->json as $index => $opcion)
                                                    <div class="form-check">
                                                        <input class="form-check-input preview-checkbox" type="checkbox" 
                                                               id="checkbox_{{ $atributo->id }}_{{ $index }}"
                                                               value="{{ $opcion }}">
                                                        <label class="form-check-label" for="checkbox_{{ $atributo->id }}_{{ $index }}">
                                                            {{ $opcion }}
                                                        </label>
                                                    </div>
                                                @endforeach
                                            @endif
                                            @break
                                            
                                        @case('fecha')
                                            <input type="date" class="form-control" disabled>
                                            @break
                                            
                                        @case('imagen')
                                            <input type="file" class="form-control" accept="image/*" disabled>
                                            <small class="form-text text-muted">Formatos permitidos: JPG, PNG, GIF (máx. 2MB)</small>
                                            @break
                                            
                                        @default
                                            <input type="text" class="form-control" placeholder="Campo no definido" disabled>
                                    @endswitch
                                    
                                    @if($atributo->obligatorio)
                                        <small class="text-muted">
                                            <i class="fas fa-asterisk text-danger me-1" style="font-size: 0.7em;"></i>
                                            Campo obligatorio
                                        </small>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        
                        <div class="d-flex gap-2 justify-content-end mt-4">
                            <button type="button" class="btn btn-secondary" disabled>
                                <i class="fas fa-times me-2"></i>Cancelar
                            </button>
                            <button type="button" class="btn btn-primary" disabled>
                                <i class="fas fa-save me-2"></i>Guardar {{ $ficha->tipo }}
                            </button>
                        </div>
                    </form>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-exclamation-circle fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Sin campos configurados</h5>
                        <p class="text-muted">Esta ficha no tiene campos configurados aún.</p>
                        <a href="{{ route('fichas.edit', $ficha) }}" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Agregar Campos
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Estadísticas de la Ficha -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="fas fa-chart-bar me-2"></i>
                    Estadísticas de la Ficha
                </h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6">
                        <div class="border-end">
                            <h4 class="text-primary mb-1">{{ $atributos->count() }}</h4>
                            <small class="text-muted">Campos</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <h4 class="text-success mb-1">{{ $atributos->where('obligatorio', true)->count() }}</h4>
                        <small class="text-muted">Obligatorios</small>
                    </div>
                </div>
                
                @if($atributos->count() > 0)
                <hr>
                <h6 class="mb-2">Tipos de Campo:</h6>
                @php
                    $tiposCampo = $atributos->groupBy('tipo')->map->count();
                @endphp
                @foreach($tiposCampo as $tipo => $cantidad)
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-capitalize">{{ $tipo }}</span>
                        <span class="badge bg-light text-dark">{{ $cantidad }}</span>
                    </div>
                @endforeach
                @endif
            </div>
        </div>
        
        <!-- Información Técnica -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="fas fa-cog me-2"></i>
                    Información Técnica
                </h6>
            </div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr>
                        <td><strong>ID:</strong></td>
                        <td>{{ $ficha->id }}</td>
                    </tr>
                    <tr>
                        <td><strong>Estado:</strong></td>
                        <td>
                            <span class="badge bg-{{ $ficha->estado ? 'success' : 'danger' }}">
                                {{ $ficha->estado ? 'Activa' : 'Inactiva' }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Última actualización:</strong></td>
                        <td>{{ $ficha->updated_at->format('d/m/Y H:i') }}</td>
                    </tr>
                    @if($contexto)
                    <tr>
                        <td><strong>Contexto:</strong></td>
                        <td>
                            @if(isset($contexto['id_flujo']))
                                Flujo ID: {{ $contexto['id_flujo'] }}<br>
                            @endif
                            @if(isset($contexto['id_etapa']))
                                Etapa ID: {{ $contexto['id_etapa'] }}
                            @endif
                        </td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>
        
        <!-- Acciones Adicionales -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="fas fa-tools me-2"></i>
                    Acciones
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('fichas.edit', $ficha) }}" class="btn btn-outline-primary">
                        <i class="fas fa-edit me-2"></i>Editar Ficha
                    </a>
                    
                    @if($ficha->tipo === 'Cliente')
                        <a href="{{ route('clientes.index') }}" class="btn btn-outline-info">
                            <i class="fas fa-users me-2"></i>Ver Clientes
                        </a>
                    @elseif($ficha->tipo === 'Producto')
                        <a href="{{ route('productos.index') }}" class="btn btn-outline-info">
                            <i class="fas fa-box me-2"></i>Ver Productos
                        </a>
                    @elseif($ficha->tipo === 'Proveedor')
                        <a href="{{ route('proveedores.index') }}" class="btn btn-outline-info">
                            <i class="fas fa-truck me-2"></i>Ver Proveedores
                        </a>
                    @endif
                    
                    <button type="button" class="btn btn-outline-warning" onclick="toggleEstado({{ $ficha->id }}, {{ $ficha->estado ? 'false' : 'true' }})">
                        <i class="fas fa-{{ $ficha->estado ? 'eye-slash' : 'eye' }} me-2"></i>
                        {{ $ficha->estado ? 'Desactivar' : 'Activar' }} Ficha
                    </button>
                    
                    <button type="button" class="btn btn-outline-danger" onclick="confirmarEliminacion({{ $ficha->id }}, '{{ $ficha->nombre }}')">
                        <i class="fas fa-trash me-2"></i>Eliminar Ficha
                    </button>
                </div>
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
                <p>¿Estás seguro de que deseas eliminar la ficha <strong id="nombreFicha"></strong>?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Esta acción eliminará:
                    <ul class="mb-0 mt-2">
                        <li>Todos los campos de la ficha</li>
                        <li>Todos los datos asociados a esta ficha</li>
                        <li>Referencias en {{ strtolower($ficha->tipo) }}s relacionados</li>
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
                        <i class="fas fa-trash me-2"></i>Eliminar Ficha
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.preview-form {
    background-color: #f8f9fa;
    border: 2px dashed #dee2e6;
    border-radius: 0.375rem;
    padding: 1.5rem;
    position: relative;
}

.preview-form::before {
    content: "VISTA PREVIA";
    position: absolute;
    top: -12px;
    left: 20px;
    background: #fff;
    color: #6c757d;
    font-size: 0.75rem;
    font-weight: bold;
    padding: 0 8px;
    letter-spacing: 1px;
}

.preview-form .form-control[disabled],
.preview-form .form-control[readonly] {
    opacity: 0.7;
    cursor: not-allowed;
}

/* Estilos para elementos interactivos en vista previa */
.preview-form .preview-select,
.preview-form .preview-radio,
.preview-form .preview-checkbox {
    opacity: 1;
    cursor: pointer;
    transition: all 0.2s ease;
}

.preview-form .preview-select {
    border-color: #28a745;
    background-color: #f8fff9;
}

.preview-form .preview-select:focus {
    border-color: #28a745;
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
}

.preview-form .preview-radio:checked,
.preview-form .preview-checkbox:checked {
    background-color: #28a745;
    border-color: #28a745;
}

.preview-form .form-check-label {
    cursor: pointer;
    transition: color 0.2s ease;
}

.preview-form .form-check-input:checked + .form-check-label {
    color: #28a745;
    font-weight: 500;
}

.preview-form .btn {
    opacity: 0.6;
    cursor: not-allowed;
}

.form-control-plaintext {
    padding-left: 0;
    margin-bottom: 0;
}

/* Efecto hover para elementos interactivos */
.preview-form .preview-select:hover {
    background-color: #e8f5e8;
}

.preview-form .form-check:hover .form-check-label {
    color: #28a745;
}

/* Indicador visual para campos funcionales */
.preview-form .preview-select,
.preview-form .form-check {
    position: relative;
}

.preview-form .preview-select::after {
    content: "🔍";
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none;
    font-size: 0.8rem;
}

.preview-form .form-check::before {
    content: "👆";
    position: absolute;
    right: -25px;
    top: 0;
    font-size: 0.7rem;
    opacity: 0;
    transition: opacity 0.2s ease;
}

.preview-form .form-check:hover::before {
    opacity: 1;
}
</style>
@endpush

@push('scripts')
<script>
// Prevenir envío del formulario de vista previa
document.addEventListener('DOMContentLoaded', function() {
    const previewForm = document.querySelector('.preview-form');
    if (previewForm) {
        previewForm.addEventListener('submit', function(e) {
            e.preventDefault();
            showPreviewAlert();
        });
        
        // Agregar funcionalidad interactiva a los elementos de vista previa
        setupPreviewInteractivity();
    }
});

function setupPreviewInteractivity() {
    // Efecto visual para selects
    const previewSelects = document.querySelectorAll('.preview-select');
    previewSelects.forEach(select => {
        select.addEventListener('change', function() {
            if (this.value) {
                this.style.backgroundColor = '#d4edda';
                this.style.borderColor = '#28a745';
                
                // Mostrar tooltip temporal
                showTooltip(this, 'Opción seleccionada: ' + this.value);
            } else {
                this.style.backgroundColor = '#f8fff9';
                this.style.borderColor = '#28a745';
            }
        });
    });
    
    // Efecto visual para radios
    const previewRadios = document.querySelectorAll('.preview-radio');
    previewRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.checked) {
                showTooltip(this, 'Seleccionado: ' + this.value);
                
                // Animar el label
                const label = this.closest('.form-check').querySelector('.form-check-label');
                label.style.transform = 'scale(1.05)';
                setTimeout(() => {
                    label.style.transform = 'scale(1)';
                }, 200);
            }
        });
    });
    
    // Efecto visual para checkboxes
    const previewCheckboxes = document.querySelectorAll('.preview-checkbox');
    previewCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const label = this.closest('.form-check').querySelector('.form-check-label');
            if (this.checked) {
                showTooltip(this, 'Marcado: ' + this.value);
                label.style.transform = 'scale(1.05)';
                setTimeout(() => {
                    label.style.transform = 'scale(1)';
                }, 200);
            } else {
                label.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    label.style.transform = 'scale(1)';
                }, 200);
            }
        });
    });
}

function showTooltip(element, message) {
    // Crear tooltip temporal
    const tooltip = document.createElement('div');
    tooltip.className = 'preview-tooltip';
    tooltip.innerHTML = `
        <div class="bg-success text-white rounded px-2 py-1 small">
            <i class="fas fa-eye me-1"></i>
            ${message}
        </div>
    `;
    tooltip.style.cssText = `
        position: absolute;
        z-index: 1060;
        pointer-events: none;
        opacity: 0;
        transition: opacity 0.3s ease;
    `;
    
    document.body.appendChild(tooltip);
    
    // Posicionar cerca del elemento
    const rect = element.getBoundingClientRect();
    tooltip.style.top = (rect.top - 40) + 'px';
    tooltip.style.left = rect.left + 'px';
    
    // Mostrar con animación
    setTimeout(() => {
        tooltip.style.opacity = '1';
    }, 10);
    
    // Ocultar después de 2 segundos
    setTimeout(() => {
        tooltip.style.opacity = '0';
        setTimeout(() => {
            if (tooltip.parentNode) {
                tooltip.parentNode.removeChild(tooltip);
            }
        }, 300);
    }, 2000);
}

function showPreviewAlert() {
    const alert = document.createElement('div');
    alert.className = 'alert alert-info alert-dismissible fade show';
    alert.innerHTML = `
        <i class="fas fa-info-circle me-2"></i>
        Esta es una vista previa del formulario. Para realizar cambios, usa el botón "Editar Ficha".
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const contentArea = document.querySelector('.content-area');
    contentArea.insertBefore(alert, contentArea.firstChild);
    
    setTimeout(() => {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    }, 5000);
}

function confirmarEliminacion(fichaId, nombreFicha) {
    document.getElementById('nombreFicha').textContent = nombreFicha;
    document.getElementById('formEliminar').action = `/fichas/${fichaId}`;
    
    const modal = new bootstrap.Modal(document.getElementById('confirmarEliminacionModal'));
    modal.show();
}

function toggleEstado(fichaId, estado) {
    fetch(`/fichas/${fichaId}/toggle-estado`, {
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
            showAlert('success', data.message);
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showAlert('error', data.message || 'Error al cambiar el estado');
        }
    })
    .catch(error => {
        console.error('Error:', error);
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
