@extends('layouts.dashboard')
@section('title','Nuevo formulario')
@section('page-title','Nuevo formulario')
@section('content-area')
<form method="post" action="{{ route('forms.store') }}" class="row g-3">
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
<div class="col-md-4">
  <label class="form-label">Tipo</label>
  <select name="id_type" class="form-select" required>
    @foreach($types as $t)
      <option value="{{ $t->id }}">{{ $t->nombre }} (emp {{ $t->id_emp }})</option>
    @endforeach
  </select>
</div>
<div class="col-md-5">
  <label class="form-label">Nombre</label>
  <input name="nombre" class="form-control" required>
</div>
<div class="col-12">
  <label class="form-label">Descripción</label>
  <textarea name="descripcion" class="form-control"></textarea>
</div>

<div class="col-md-2">
  <label class="form-label">Usa correlativo</label>
  <select name="usa_correlativo" class="form-select">
    <option value="0">No</option>
    <option value="1">Sí</option>
  </select>
</div>
<div class="col-md-2">
  <label class="form-label">Prefijo</label>
  <input name="correlativo_prefijo" class="form-control">
</div>
<div class="col-md-2">
  <label class="form-label">Sufijo</label>
  <input name="correlativo_sufijo" class="form-control">
</div>
<div class="col-md-2">
  <label class="form-label">Padding</label>
  <input name="correlativo_padding" type="number" class="form-control" value="6">
</div>
<div class="col-md-2">
  <label class="form-label">Estado</label>
  <select name="estado" class="form-select">
    <option value="1">Activo</option>
    <option value="0">Inactivo</option>
  </select>
</div>

<div class="col-12">
  <button class="btn btn-primary">Crear</button>
</div>
</form>
@endsection
