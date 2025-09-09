@extends('layouts.dashboard')
@section('title','Editar Tipo #{{ $type->id }}')
@section('page-title','Editar Tipo #{{ $type->id }}')
@section('content-area')
<form method="post" action="{{ route('form-types.update',$type) }}" class="row g-3">
@csrf @method('put')
<div class="col-md-6">
  <label class="form-label">Nombre</label>
  <input name="nombre" class="form-control" value="{{ old('nombre',$type->nombre) }}" required>
</div>
<div class="col-md-12">
  <label class="form-label">Descripción</label>
  <textarea name="descripcion" class="form-control">{{ old('descripcion',$type->descripcion) }}</textarea>
</div>
<div class="col-md-3">
  <label class="form-label">Estado</label>
  <select name="estado" class="form-select">
    <option value="1" @selected($type->estado)>Activo</option>
    <option value="0" @selected(!$type->estado)>Inactivo</option>
  </select>
</div>
<div class="col-12">
  <button class="btn btn-primary">Actualizar</button>
</div>
</form>
@endsection
