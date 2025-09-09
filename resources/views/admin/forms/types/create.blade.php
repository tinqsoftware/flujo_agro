@extends('layouts.dashboard')
@section('title','Nuevo Tipo de Formulario')
@section('page-title','Nuevo Tipo de Formulario')
@section('content-area')

<form method="post" action="{{ route('form-types.store') }}" class="row g-3">
@csrf
<div class="col-md-3">
  <label class="form-label">Empresa</label>
  <select name="id_emp" class="form-select" required>
    <option value="">Seleccione una empresa</option>
    @foreach($empresas as $empresa)
      <option value="{{ $empresa->id }}">{{ $empresa->nombre }}</option>
    @endforeach
  </select>
</div>
<div class="col-md-6">
  <label class="form-label">Nombre</label>
  <input name="nombre" class="form-control" required>
</div>
<div class="col-md-12">
  <label class="form-label">Descripción</label>
  <textarea name="descripcion" class="form-control"></textarea>
</div>
<div class="col-md-3">
  <label class="form-label">Estado</label>
  <select name="estado" class="form-select">
    <option value="1">Activo</option>
    <option value="0">Inactivo</option>
  </select>
</div>
<div class="col-12">
  <button class="btn btn-primary">Guardar</button>
</div>
</form>
@endsection
