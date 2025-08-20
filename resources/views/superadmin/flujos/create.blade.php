@extends('layouts.dashboard')
@section('title','Nuevo Flujo')
@section('page-title','Crear Flujo')

@section('content-area')
@if ($errors->any())
  <div class="alert alert-danger"><strong>Revisa el formulario:</strong>
    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
  </div>
@endif

<form action="{{ route('flujos.store') }}" method="POST" id="flujoForm">
@csrf
<div class="row">
  <div class="col-lg-4">
    <div class="card mb-3">
      <div class="card-header"><strong>Datos del Flujo</strong></div>
      <div class="card-body">
        @if($isSuper)
          <div class="mb-3">
            <label class="form-label">Empresa *</label>
            <select name="id_emp" id="empresaSelect" class="form-select" required>
              <option value="">Seleccionar</option>
              @foreach($empresas as $e)
                <option value="{{ $e->id }}">{{ $e->nombre }}</option>
              @endforeach
            </select>
          </div>
        @endif

        <div class="mb-3">
          <label class="form-label">Nombre *</label>
          <input name="nombre" class="form-control" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Tipo de flujo</label>
          <select name="id_tipo_flujo" id="tipoSelect" class="form-select">
            <option value="">—</option>
            @foreach($tipos as $t)
              <option value="{{ $t->id }}" data-emp="{{ $t->id_emp }}">{{ $t->nombre }}</option>
            @endforeach
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label">Descripción</label>
          <textarea name="descripcion" rows="3" class="form-control"></textarea>
        </div>

        <input type="hidden" name="builder" id="builderInput" value='@json($treeJson)'>

        <div class="d-flex gap-2">
          <a href="{{ route('flujos.index') }}" class="btn btn-secondary">Cancelar</a>
          <button class="btn btn-primary">Crear</button>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-8">
    @include('superadmin.flujos.partials.builder', ['treeJson' => $treeJson])
  </div>
</div>
</form>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>

{{-- Filtro de tipos por empresa (si es super) --}}
<script>
  (function(){
    const empSel = document.getElementById('empresaSelect');
    const tipoSel = document.getElementById('tipoSelect');
    if (empSel && tipoSel) {
      function filterTipos(){
        const emp = empSel.value;
        [...tipoSel.options].forEach(opt=>{
          if (!opt.value) return opt.hidden = false;
          const e = opt.getAttribute('data-emp');
          opt.hidden = (emp && e !== emp);
        });
        const cur = tipoSel.selectedOptions[0];
        if (cur && cur.hidden) tipoSel.value='';
      }
      empSel.addEventListener('change', filterTipos);
      filterTipos();
    }
  })();
</script>

{{-- Builder (mismo script que en EDIT) --}}
@include('superadmin.flujos.partials.builder-script')
@endpush
