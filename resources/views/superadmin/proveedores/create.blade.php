@extends('layouts.dashboard')

@section('title','Nuevo Proveedor')
@section('page-title','Crear Proveedor')

@section('content-area')
@if ($errors->any())
  <div class="alert alert-danger">
    <strong>Revisa el formulario:</strong>
    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
  </div>
@endif

<form action="{{ route('proveedores.store') }}" method="POST" enctype="multipart/form-data" id="provForm">
  @csrf
  <div class="card mb-4">
    <div class="card-header"><strong>Información</strong></div>
    <div class="card-body">
      <div class="row">
        @if($isSuper)
          <div class="col-md-6 mb-3">
            <label class="form-label">Empresa *</label>
            <select name="id_emp" id="id_emp" class="form-select" required>
              <option value="">Seleccionar</option>
              @foreach($empresas as $e)
                <option value="{{ $e->id }}" {{ old('id_emp')==$e->id?'selected':'' }}>{{ $e->nombre }}</option>
              @endforeach
            </select>
          </div>
        @endif

        <div class="col-md-6 mb-3">
          <label class="form-label">Nombre *</label>
          <input type="text" name="nombre" class="form-control" value="{{ old('nombre') }}" required>
        </div>

        <div class="col-md-6 mb-3">
          <label class="form-label">Logo</label>
          <input type="file" name="logo" class="form-control">
          <div class="form-text">Opcional</div>
        </div>
      </div>

      @include('superadmin.proveedores.partials.attrs', ['atributos'=>$atributos, 'valores'=>[]])
    </div>
  </div>

  <div class="mt-3 d-flex gap-2">
    <a href="{{ route('proveedores.index') }}" class="btn btn-secondary">Cancelar</a>
    <button class="btn btn-primary">Crear</button>
  </div>
</form>

@if($isSuper)
  @push('scripts')
  <script>
  document.addEventListener('DOMContentLoaded', function () {
    const emp = document.getElementById('id_emp');
    const cont = document.getElementById('attrsContainer');

    function render(attrs){
      if(!attrs.length){ cont.innerHTML = '<div class="text-muted">.</div>'; return; }
      // (Para carga dinámica podrías generar inputs básicos como hiciste en clientes)
      location.reload(); // atajo simple: recarga para que el server pinte partial correcto al cambiar empresa
    }

    if(emp){
      emp.addEventListener('change', function(){
        fetch(`{{ route('proveedores.atributosByEmpresa') }}?empresa_id=${this.value}`)
          .then(r=>r.json()).then(render).catch(()=>{});
      });
    }
  });
  </script>
  @endpush
@endif
@endsection
