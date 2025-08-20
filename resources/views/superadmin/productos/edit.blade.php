@extends('layouts.dashboard')

@section('title','Editar Producto')
@section('page-title','Editar Producto')

@section('content-area')
@if ($errors->any())
  <div class="alert alert-danger"><strong>Revisa el formulario:</strong>
    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
  </div>
@endif

<form action="{{ route('productos.update',$producto) }}" method="POST" enctype="multipart/form-data">
  @csrf @method('PUT')

  <div class="card mb-4">
    <div class="card-header"><strong>Información básica</strong></div>
    <div class="card-body">
      <div class="row">
        @if($isSuper)
          <div class="col-md-6 mb-3">
            <label class="form-label">Empresa *</label>
            <select name="id_emp" class="form-select" required>
              @foreach($empresas as $e)
                <option value="{{ $e->id }}" {{ $producto->id_emp==$e->id?'selected':'' }}>{{ $e->nombre }}</option>
              @endforeach
            </select>
          </div>
        @endif

        <div class="col-md-6 mb-3">
          <label class="form-label">Nombre *</label>
          <input type="text" name="nombre" class="form-control" value="{{ old('nombre',$producto->nombre) }}" required>
        </div>

        <div class="col-md-6 mb-3 d-flex align-items-end">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="estadoProd" name="estado" value="1" {{ old('estado',$producto->estado) ? 'checked' : '' }}>
            <label class="form-check-label" for="estadoProd">{{ old('estado',$producto->estado) ? 'Activo' : 'Inactivo' }}</label>
          </div>
        </div>

        <div class="col-md-12 mb-3">
          <label class="form-label">Descripción</label>
          <textarea name="descripcion" class="form-control" rows="4">{{ old('descripcion',$producto->descripcion) }}</textarea>
        </div>

        <div class="col-md-6 mb-3">
          <label class="form-label">Fecha de inicio</label>
          <input type="date" name="fecha_inicio" class="form-control" value="{{ old('fecha_inicio', optional($producto->fecha_inicio)->format('Y-m-d')) }}">
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Foto</label>
          <input type="file" name="ruta_foto" class="form-control" accept="image/*">

          @if($producto->ruta_foto)
            <div class="mt-2">
              <p class="mb-1"><strong>Foto actual:</strong></p>
              <img src="{{ asset('storage/'.$producto->ruta_foto) }}" class="img-thumbnail"
                  style="max-width:200px; height:auto;" alt="Foto actual">
              <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" id="eliminar_foto" name="eliminar_foto" value="1">
                <label class="form-check-label" for="eliminar_foto">Eliminar foto actual</label>
              </div>
            </div>
          @endif
        </div>
        
      </div>

      {{-- Atributos dinámicos --}}
      @include('superadmin.clientes.partials.attrs', ['atributos' => $atributos, 'valores' => $valores])
    </div>
  </div>

  <div class="mt-3 d-flex gap-2">
    <a href="{{ route('productos.index') }}" class="btn btn-secondary">Cancelar</a>
    <button class="btn btn-primary">Guardar</button>
  </div>
</form>
@endsection
