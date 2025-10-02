@extends('layouts.dashboard')

@section('title', 'Nueva Ficha')
@section('page-title', 'Crear Nueva Ficha')
@section('page-subtitle', 'Registra una nueva ficha en el sistema')

@section('header-actions')
    <a href="{{ route('fichas.index') }}" class="btn btn-light">
        <i class="fas fa-arrow-left me-2"></i>Volver
    </a>
@endsection

@section('content-area')

@if ($errors->any())
  <div class="alert alert-danger">
    <strong>Revisa el formulario:</strong>
    <ul class="mb-0">
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

<div class="row">
    <div class="col-lg-8">
        <form method="POST" action="{{ route('fichas.store') }}" id="fichaForm">
            @csrf
            
            <!-- Información Básica -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Información Básica
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nombre" class="form-label">
                                <i class="fas fa-tag me-1"></i>
                                Nombre de la Ficha <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control @error('nombre') is-invalid @enderror" 
                                   id="nombre" name="nombre" value="{{ old('nombre') }}" 
                                   placeholder="Ej: Ficha de Productos Cítricos" required>
                            @error('nombre')
                                <div class="invalid-feedback">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        
                        @if(auth()->user()->rol->nombre === 'SUPERADMIN')
                        <div class="col-md-6 mb-3">
                            <label for="id_emp" class="form-label">
                                <i class="fas fa-building me-1"></i>
                                Empresa <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('id_emp') is-invalid @enderror" 
                                    id="id_emp" name="id_emp" required>
                                <option value="">Seleccionar empresa</option>
                                @foreach($empresas as $empresa)
                                    <option value="{{ $empresa->id }}" {{ old('id_emp') == $empresa->id ? 'selected' : '' }}>
                                        {{ $empresa->nombre }}
                                    </option>
                                @endforeach
                            </select>
                            @error('id_emp')
                                <div class="invalid-feedback">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        @else
                        <input type="hidden" name="id_emp" value="{{ auth()->user()->id_emp }}">
                        @endif
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="tipo" class="form-label">
                                <i class="fas fa-list me-1"></i>
                                Tipo de Ficha <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('tipo') is-invalid @enderror" 
                                    id="tipo" name="tipo" required>
                                <option value="">Seleccionar tipo</option>
                                @foreach($tipos as $tipo)
                                    <option value="{{ $tipo }}" {{ old('tipo') == $tipo ? 'selected' : '' }}>
                                        {{ $tipo }}
                                    </option>
                                @endforeach
                            </select>
                            @error('tipo')
                                <div class="invalid-feedback">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    {{ $message }}
                                </div>
                            @enderror
                            <div id="tipoWarning" class="form-text text-warning" style="display: none;">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                Ya existe una ficha de este tipo para la empresa seleccionada.
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3" id="flujoContainer" style="display: none;">
                            <label for="id_flujo" class="form-label">
                                <i class="fas fa-project-diagram me-1"></i>
                                Flujo <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('id_flujo') is-invalid @enderror" 
                                    id="id_flujo" name="id_flujo">
                                <option value="">Seleccionar flujo</option>
                            </select>
                            @error('id_flujo')
                                <div class="invalid-feedback">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="row" id="etapaContainer" style="display: none;">
                        <div class="col-md-6 mb-3">
                            <label for="id_etapa" class="form-label">
                                <i class="fas fa-step-forward me-1"></i>
                                Etapa <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('id_etapa') is-invalid @enderror" 
                                    id="id_etapa" name="id_etapa">
                                <option value="">Seleccionar etapa</option>
                            </select>
                            @error('id_etapa')
                                <div class="invalid-feedback">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Constructor de Campos -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-tools me-2"></i>
                        Constructor de Campos
                    </h5>
                    <button type="button" class="btn btn-primary btn-sm" id="addCampo">
                        <i class="fas fa-plus me-1"></i>Agregar Campo
                    </button>
                </div>
                <div class="card-body">
                    <div id="camposContainer">
                        <!-- Los campos se agregarán dinámicamente aquí -->
                    </div>
                    
                    <div id="noCamposMessage" class="text-center py-4">
                        <i class="fas fa-plus-circle fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">No hay campos agregados</h6>
                        <p class="text-muted mb-0">Haz clic en "Agregar Campo" para comenzar</p>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">Relaciones e Items</strong></div>
                <div class="card-body">

                    <div id="groups-container" class="d-flex flex-column gap-3">
                    {{-- Aquí se agregan las tarjetitas de grupos --}}
                    </div>

                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="addGroupCard()">+ Añadir grupo</button>
                </div>
            </div>

            @push('scripts')
            <script>
            (function () {
            const container = document.getElementById('groups-container');

            // Crea una tarjetita de grupo NUEVO (solo en CREAR)
            window.addGroupCard = function(initial = {}) {
                const idx = container.querySelectorAll('.group-card').length;
                const card = document.createElement('div');
                card.className = 'group-card border rounded p-3';

                const _code    = initial.code  || '';
                const _label   = initial.label || '';
                const _type    = initial.group_type || 'list';
                const _related = initial.related_entity_type || 'cliente';
                const _allow   = (typeof initial.allow_multiple !== 'undefined') ? !!initial.allow_multiple : true;

                card.innerHTML = `
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                    <label class="form-label">Código</label>
                    <input class="form-control" name="group_defs[new][${idx}][code]" value="${_code}" placeholder="p.ej. precios" required>
                    </div>
                    <div class="col-md-4">
                    <label class="form-label">Etiqueta</label>
                    <input class="form-control" name="group_defs[new][${idx}][label]" value="${_label}" placeholder="p.ej. Ingrese precios" required>
                    </div>
                    <div class="col-md-3">
                    <label class="form-label">Tipo</label>
                    <select class="form-select group-type" name="group_defs[new][${idx}][group_type]">
                        <option value="list" ${_type==='list'?'selected':''}>Lista (ítems)</option>
                        <option value="relation" ${_type==='relation'?'selected':''}>Relación</option>
                    </select>
                    </div>
                    <div class="col-md-2 text-end">
                    <button type="button" class="btn btn-outline-danger" onclick="this.closest('.group-card').remove()">Quitar</button>
                    </div>
                </div>

                <!-- BLOQUE RELACIÓN -->
                <div class="row g-3 mt-2 relation-row">
                    <div class="col-md-4">
                    <label class="form-label">Relacionado con</label>
                    <select class="form-select" name="group_defs[new][${idx}][related_entity_type]">
                        <option value="cliente"  ${_related==='cliente'?'selected':''}>Cliente</option>
                        <option value="proveedor"${_related==='proveedor'?'selected':''}>Proveedor</option>
                        <option value="producto" ${_related==='producto'?'selected':''}>Producto</option>
                    </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-center">
                    <div class="form-check form-switch mt-4">
                        <!-- hidden para enviar 0 cuando está desmarcado -->
                        <input type="hidden" name="group_defs[new][${idx}][allow_multiple]" value="0">
                        <input class="form-check-input" type="checkbox"
                            id="allow_${idx}"
                            name="group_defs[new][${idx}][allow_multiple]"
                            value="1" ${_allow?'checked':''}>
                        <label for="allow_${idx}" class="form-check-label">Permitir múltiples</label>
                    </div>
                    </div>
                </div>

                <!-- BLOQUE LISTA -->
                <div class="mt-3 list-row">
                    <div class="row g-2">
                    <div class="col-12">
                        <div class="small text-muted">Campos del ítem (LISTA): code / label / type</div>
                    </div>
                    <div class="col-12">
                        <div class="vf-rows" data-next-index="0"></div>
                        <button type="button" class="btn btn-xs btn-outline-primary mt-2" onclick="addItemFieldRow(this, ${idx})">+ Campo</button>
                    </div>
                    </div>
                </div>
                `;

                container.appendChild(card);

                // Semillas útiles para LISTA
                const rowsWrap = card.querySelector('.vf-rows');
                rowsWrap.setAttribute('data-next-index', '0');
                appendFieldRowIndexed(rowsWrap, idx, 0, 'tipo',   'Tipo',   'text');
                rowsWrap.setAttribute('data-next-index', '1');
                appendFieldRowIndexed(rowsWrap, idx, 1, 'precio', 'Precio', 'decimal');
                rowsWrap.setAttribute('data-next-index', '2');

                // Toggle inicial y onChange
                const sel = card.querySelector('.group-type');
                toggleRowsForType(sel);
                sel.addEventListener('change', function(){ toggleRowsForType(sel); });
            };

            // Añade una fila indexada a los campos de ítem (LISTA)
            window.addItemFieldRow = function(btn, groupIdx){
                const wrap = btn.closest('.list-row').querySelector('.vf-rows');
                const next = parseInt(wrap.getAttribute('data-next-index') || '0', 10);
                appendFieldRowIndexed(wrap, groupIdx, next, '', '', 'text');
                wrap.setAttribute('data-next-index', String(next + 1));
            };

            // Crea la fila (con índice correcto para que el JSON quede bien)
            function appendFieldRowIndexed(wrap, groupIdx, rowIndex, code, label, type){
                const row = document.createElement('div');
                row.className = 'd-flex gap-2 mb-2 flex-wrap';
                row.setAttribute('data-row-index', rowIndex);
                row.innerHTML = `
                <input class="form-control" style="max-width:180px" placeholder="code"
                        name="group_defs[new][${groupIdx}][item_fields][${rowIndex}][code]"  value="${code||''}">
                <input class="form-control" style="max-width:220px" placeholder="label"
                        name="group_defs[new][${groupIdx}][item_fields][${rowIndex}][label]" value="${label||''}">
                <select class="form-select" style="max-width:160px"
                        name="group_defs[new][${groupIdx}][item_fields][${rowIndex}][type]">
                    <option value="text" ${(!type||type==='text')?'selected':''}>text</option>
                    <option value="decimal" ${(type==='decimal')?'selected':''}>decimal</option>
                    <option value="int" ${(type==='int')?'selected':''}>int</option>
                </select>
                <button type="button" class="btn btn-outline-danger" onclick="this.parentElement.remove()">x</button>
                `;
                wrap.appendChild(row);
            }

            // Muestra/oculta bloques según tipo
            function toggleRowsForType(selectEl){
                const card = selectEl.closest('.group-card');
                const isRelation = (selectEl.value === 'relation');
                card.querySelector('.relation-row').style.display = isRelation ? '' : 'none';
                card.querySelector('.list-row').style.display     = isRelation ? 'none' : '';
            }

            // Inicia con una card lista para llenar
            addGroupCard();
            })();
            </script>
            @endpush


            
            <div class="d-flex gap-2 justify-content-end mt-4">
                <a href="{{ route('fichas.index') }}" class="btn btn-secondary">
                    <i class="fas fa-times me-2"></i>Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Crear Ficha
                </button>
            </div>
        </form>
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
                        Tipos de Ficha
                    </h6>
                    <ul class="mb-0">
                        <li><strong>Producto/Cliente/Proveedor:</strong> Solo una por empresa</li>
                        <li><strong>Flujo:</strong> Requiere seleccionar un flujo existente</li>
                        <li><strong>Etapa:</strong> Requiere flujo y etapa específica</li>
                    </ul>
                </div>
                
                <div class="alert alert-warning">
                    <h6 class="alert-heading">
                        <i class="fas fa-tools me-2"></i>
                        Tipos de Campo
                    </h6>
                    <ul class="mb-0">
                        <li><strong>Texto/Decimal/Entero:</strong> Requieren ancho</li>
                        <li><strong>Radio/Desplegable/Checkbox:</strong> Requieren opciones</li>
                        <li><strong>Fecha/Imagen:</strong> Sin configuración adicional</li>
                    </ul>
                </div>
                
                <div class="alert alert-success">
                    <h6 class="alert-heading">
                        <i class="fas fa-arrows-alt me-2"></i>
                        Reordenar Campos
                    </h6>
                    <p class="mb-0">
                        Puedes arrastrar y soltar los campos para cambiar su orden.
                        El orden determina cómo aparecerán en los formularios.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Template para Campo -->
<template id="campoTemplate">
    <div class="campo-item border rounded p-3 mb-3" data-campo-index="">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="d-flex align-items-center">
                <i class="fas fa-grip-vertical text-muted me-2" style="cursor: move;"></i>
                <h6 class="mb-0">Campo <span class="campo-number"></span></h6>
            </div>
            <button type="button" class="btn btn-outline-danger btn-sm remove-campo">
                <i class="fas fa-trash"></i>
            </button>
        </div>
        
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">
                    <i class="fas fa-tag me-1"></i>
                    Nombre del Campo <span class="text-danger">*</span>
                </label>
                <input type="text" class="form-control campo-nombre" name="campos[][nombre]" 
                       placeholder="Ej: Peso del producto" required>
            </div>
            
            <div class="col-md-6 mb-3">
                <label class="form-label">
                    <i class="fas fa-cog me-1"></i>
                    Tipo de Campo <span class="text-danger">*</span>
                </label>
                <select class="form-select campo-tipo" name="campos[][tipo]" required>
                    <option value="">Seleccionar tipo</option>
                    @foreach($tiposCampo as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        
        <!-- Configuración de Ancho -->
        <div class="ancho-config" style="display: none;">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        <i class="fas fa-arrows-alt-h me-1"></i>
                        Ancho (1-200)
                    </label>
                    <input type="number" class="form-control campo-ancho" name="campos[][ancho]" min="1" max="200" value="5">
                </div>
            </div>
        </div>
        
        <!-- Configuración de Opciones -->
        <div class="opciones-config" style="display: none;">
            <label class="form-label">
                <i class="fas fa-list me-1"></i>
                Opciones
            </label>
            <div class="opciones-container">
                <div class="input-group mb-2">
                    <input type="text" class="form-control opcion-input" placeholder="Opción 1">
                    <button type="button" class="btn btn-outline-danger remove-opcion">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="input-group mb-2">
                    <input type="text" class="form-control opcion-input" placeholder="Opción 2">
                    <button type="button" class="btn btn-outline-danger remove-opcion">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <button type="button" class="btn btn-outline-primary btn-sm add-opcion">
                <i class="fas fa-plus me-1"></i>Agregar Opción
            </button>
            <input type="hidden" class="opciones-hidden" name="campos[][opciones]">
        </div>
        
        <!-- Campo Obligatorio -->
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="campos[][obligatorio]" value="1">
            <label class="form-check-label">
                <i class="fas fa-asterisk me-1"></i>
                Campo obligatorio
            </label>
        </div>
        <input type="hidden" class="campo-nro" name="campos[][nro]" value="">
    </div>
</template>
@endsection

@push('styles')
<style>
.campo-item {
    background-color: #f8f9fa;
    transition: all 0.3s ease;
}

.campo-item:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.campo-item.sortable-ghost {
    opacity: 0.5;
}

.campo-item.sortable-chosen {
    transform: scale(1.02);
}

.grip-handle {
    cursor: move;
}

.opciones-container .input-group {
    position: relative;
}

.remove-opcion {
    border-left: none;
}

#noCamposMessage {
    border: 2px dashed #dee2e6;
    border-radius: 0.375rem;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Variables globales
    let campoCounter = 0;
    const camposContainer = document.getElementById('camposContainer');
    const noCamposMessage = document.getElementById('noCamposMessage');
    const campoTemplate = document.getElementById('campoTemplate');
    
    // Inicializar SortableJS para reordenar campos
    const sortable = Sortable.create(camposContainer, {
        handle: '.fas.fa-grip-vertical',
        animation: 150,
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        onEnd: function() {
            updateCampoNumbers();
        }
    });

    // Event Listeners principales
    setupEventListeners();
    
    function setupEventListeners() {
        // Cambio de empresa (solo para SuperAdmin)
        const empresaSelect = document.getElementById('id_emp');
        if (empresaSelect) {
            empresaSelect.addEventListener('change', handleEmpresaChange);
        }
        
        // Cambio de tipo
        document.getElementById('tipo').addEventListener('change', handleTipoChange);
        
        // Cambio de flujo
        document.getElementById('id_flujo').addEventListener('change', handleFlujoChange);
        
        // Agregar campo
        document.getElementById('addCampo').addEventListener('click', addCampo);
        
        // Validación del formulario
        document.getElementById('fichaForm').addEventListener('submit', validateForm);
    }
    
    function handleEmpresaChange() {
        const empresaId = this.value;
        const tipoSelect = document.getElementById('tipo');
        
        // Limpiar selecciones dependientes
        clearDependentSelects();
        
        if (empresaId) {
            // Cargar flujos de la empresa
            loadFlujosByEmpresa(empresaId);
            
            // Verificar tipo disponible si ya hay tipo seleccionado
            if (tipoSelect.value) {
                checkTipoDisponible(empresaId, tipoSelect.value);
            }
        }
    }
    
    function handleTipoChange() {
        const tipo = this.value;
        const empresaId = document.getElementById('id_emp')?.value || '{{ auth()->user()->id_emp }}';
        const flujoContainer = document.getElementById('flujoContainer');
        const etapaContainer = document.getElementById('etapaContainer');
        
        // Ocultar contenedores por defecto
        flujoContainer.style.display = 'none';
        etapaContainer.style.display = 'none';
        
        // Limpiar selecciones
        document.getElementById('id_flujo').value = '';
        document.getElementById('id_etapa').value = '';
        
        if (tipo === 'Flujo' || tipo === 'Etapa') {
            flujoContainer.style.display = 'block';
            if (!document.getElementById('id_flujo').options.length > 1) {
                loadFlujosByEmpresa(empresaId);
            }
        }
        
        if (tipo === 'Etapa') {
            etapaContainer.style.display = 'block';
        }
        
        // Verificar disponibilidad del tipo
        if (tipo && empresaId) {
            checkTipoDisponible(empresaId, tipo);
        }
    }
    
    function handleFlujoChange() {
        const flujoId = this.value;
        const etapaSelect = document.getElementById('id_etapa');
        
        // Limpiar etapas
        etapaSelect.innerHTML = '<option value="">Seleccionar etapa</option>';
        
        if (flujoId) {
            loadEtapasByFlujo(flujoId);
        }
    }
    
    function loadFlujosByEmpresa(empresaId) {
        fetch(`/fichas/flujos-by-empresa?empresa_id=${empresaId}`)
            .then(response => response.json())
            .then(data => {
                const flujoSelect = document.getElementById('id_flujo');
                flujoSelect.innerHTML = '<option value="">Seleccionar flujo</option>';
                
                data.forEach(flujo => {
                    const option = document.createElement('option');
                    option.value = flujo.id;
                    option.textContent = flujo.nombre;
                    flujoSelect.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Error loading flujos:', error);
                showAlert('Error al cargar los flujos', 'danger');
            });
    }
    
    function loadEtapasByFlujo(flujoId) {
        fetch(`/fichas/etapas-by-flujo?flujo_id=${flujoId}`)
            .then(response => response.json())
            .then(data => {
                const etapaSelect = document.getElementById('id_etapa');
                etapaSelect.innerHTML = '<option value="">Seleccionar etapa</option>';
                
                data.forEach(etapa => {
                    const option = document.createElement('option');
                    option.value = etapa.id;
                    option.textContent = etapa.nombre;
                    etapaSelect.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Error loading etapas:', error);
                showAlert('Error al cargar las etapas', 'danger');
            });
    }
    
    function checkTipoDisponible(empresaId, tipo) {
        const tipoWarning = document.getElementById('tipoWarning');
        
        fetch(`/fichas/check-tipo-disponible?empresa_id=${empresaId}&tipo=${tipo}`)
            .then(response => response.json())
            .then(data => {
                if (data.disponible) {
                    tipoWarning.style.display = 'none';
                } else {
                    tipoWarning.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error checking tipo disponible:', error);
            });
    }
    
    function addCampo() {
        campoCounter++;
        
        // Clonar template
        const template = campoTemplate.content.cloneNode(true);
        const campoItem = template.querySelector('.campo-item');
        
        // Configurar índice y número
        campoItem.setAttribute('data-campo-index', campoCounter);
        campoItem.querySelector('.campo-number').textContent = campoCounter;
        
        // Actualizar nombres de inputs
        updateInputNames(campoItem, campoCounter - 1);
        campoItem.querySelector('.campo-nro').value = campoCounter;
        
        // Agregar event listeners
        setupCampoEventListeners(campoItem);
        
        // Agregar al contenedor
        camposContainer.appendChild(campoItem);
        
        // Ocultar mensaje de no campos
        noCamposMessage.style.display = 'none';
        
        // Actualizar números
        updateCampoNumbers();
    }
    
    function setupCampoEventListeners(campoItem) {
        // Remover campo
        campoItem.querySelector('.remove-campo').addEventListener('click', function() {
            removeCampo(campoItem);
        });
        
        // Cambio de tipo de campo
        campoItem.querySelector('.campo-tipo').addEventListener('change', function() {
            handleCampoTipoChange(campoItem, this.value);
        });
        
        // Agregar opción
        campoItem.querySelector('.add-opcion').addEventListener('click', function() {
            addOpcion(campoItem);
        });
        
        // Event delegation para remover opciones
        campoItem.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-opcion') || 
                e.target.parentElement.classList.contains('remove-opcion')) {
                const button = e.target.classList.contains('remove-opcion') ? e.target : e.target.parentElement;
                removeOpcion(button);
            }
        });
        
        // Actualizar opciones cuando cambie el input
        campoItem.addEventListener('input', function(e) {
            if (e.target.classList.contains('opcion-input')) {
                updateOpcionesHidden(campoItem);
            }
        });
    }
    
    function handleCampoTipoChange(campoItem, tipo) {
        const anchoConfig = campoItem.querySelector('.ancho-config');
        const opcionesConfig = campoItem.querySelector('.opciones-config');
        const anchoEl = campoItem.querySelector('.campo-ancho');
         const hiddenInput   = campoItem.querySelector('.opciones-hidden');
        
        // Ocultar todas las configuraciones
        anchoConfig.style.display = 'none';
        opcionesConfig.style.display = 'none';
        if (anchoEl) { anchoEl.disabled = true; }
        
        // Mostrar configuración según tipo
        if (['texto', 'cajatexto', 'decimal', 'entero'].includes(tipo)) {
            anchoConfig.style.display = 'block';
            if (anchoEl) { anchoEl.disabled = false; }
        } else if (['radio', 'desplegable', 'checkbox'].includes(tipo)) {
            opcionesConfig.style.display = 'block';
            updateOpcionesHidden(campoItem);
            hiddenInput.value = JSON.stringify(
            Array.from(campoItem.querySelectorAll('.opcion-input'))
                .map(i => i.value.trim())
                .filter(v => v !== '')
            );
        }
    }
    
    function addOpcion(campoItem) {
        const opcionesContainer = campoItem.querySelector('.opciones-container');
        const opcionCount = opcionesContainer.children.length + 1;
        
        const opcionDiv = document.createElement('div');
        opcionDiv.className = 'input-group mb-2';
        opcionDiv.innerHTML = `
            <input type="text" class="form-control opcion-input" placeholder="Opción ${opcionCount}">
            <button type="button" class="btn btn-outline-danger remove-opcion">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        opcionesContainer.appendChild(opcionDiv);
        updateOpcionesHidden(campoItem);
    }
    
    function removeOpcion(button) {
        const opcionDiv = button.closest('.input-group');
        const campoItem = button.closest('.campo-item');
        
        // No permitir eliminar si solo quedan 2 opciones
        const opcionesContainer = campoItem.querySelector('.opciones-container');
        if (opcionesContainer.children.length <= 2) {
            showAlert('Debe haber al menos 2 opciones', 'warning');
            return;
        }
        
        opcionDiv.remove();
        updateOpcionesHidden(campoItem);
    }
    
    function updateOpcionesHidden(campoItem) {
        const opcionInputs = campoItem.querySelectorAll('.opcion-input');
        const hiddenInput = campoItem.querySelector('.opciones-hidden');
        
        const opciones = Array.from(opcionInputs)
            .map(input => input.value.trim())
            .filter(value => value !== '');
        
        hiddenInput.value = JSON.stringify(opciones);
    }
    
    function removeCampo(campoItem) {
        campoItem.remove();
        updateCampoNumbers();
        
        // Mostrar mensaje si no hay campos
        if (camposContainer.children.length === 0) {
            noCamposMessage.style.display = 'block';
        }
    }
    
    function updateCampoNumbers() {
        const campos = camposContainer.querySelectorAll('.campo-item');
        campos.forEach((campo, index) => {
            const number = index + 1;
            campo.querySelector('.campo-number').textContent = number;
            campo.querySelector('.campo-nro').value = number; // <-- orden real
            updateInputNames(campo, index);
        });
    }
    
    function updateInputNames(campoItem, index) {
        const inputs = campoItem.querySelectorAll('input, select,textarea');
        inputs.forEach(input => {
            if (input.name && input.name.includes('campos[')) {
                const match = input.name.match(/\[([^\]]+)\]$/);
                if (!match) return;
                const fieldName = match[1];
                input.name = `campos[${index}][${fieldName}]`;
            }
        });
    }
    
    function validateForm(e) {
        const campos = camposContainer.querySelectorAll('.campo-item');
        
        if (campos.length === 0) {
            e.preventDefault();
            showAlert('Debes agregar al menos un campo', 'danger');
            return false;
        }
        
        // Validar cada campo
        let isValid = true;
        campos.forEach(campo => {
            const nombre = campo.querySelector('.campo-nombre').value.trim();
            const tipo = campo.querySelector('.campo-tipo').value;
            
            
            if (!nombre || !tipo) {
                isValid = false;
            }
            
            // Validar opciones para campos que las requieren
            if (['radio', 'desplegable', 'checkbox'].includes(tipo)) {
                const opciones = JSON.parse(campo.querySelector('.opciones-hidden').value || '[]');
                if (opciones.length < 2) {
                    isValid = false;
                }
            }else if(['texto', 'cajatexto', 'decimal', 'entero'].includes(tipo)) {
                const anchoEl = campo.querySelector('.campo-ancho');
                const ancho = anchoEl ? String(anchoEl.value).trim() : '';
                if (!ancho) isValid = false;
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            showAlert('Por favor completa todos los campos requeridos', 'danger');
            return false;
        }
        
        return true;
    }
    
    function clearDependentSelects() {
        document.getElementById('id_flujo').innerHTML = '<option value="">Seleccionar flujo</option>';
        document.getElementById('id_etapa').innerHTML = '<option value="">Seleccionar etapa</option>';
        document.getElementById('tipoWarning').style.display = 'none';
    }
    
    function showAlert(message, type = 'info') {
        // Crear alert temporal
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        // Insertar al inicio del contenido
        const contentArea = document.querySelector('.content-area') || document.body;
        contentArea.insertBefore(alertDiv, contentArea.firstChild);
        
        // Auto-dismiss después de 5 segundos
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }
    
    // Inicializar con un campo por defecto
    addCampo();
});
</script>
@endpush
