@extends('layouts.dashboard')

@section('title','Nuevo Tipo de Flujo')
@section('page-title','Nuevo Tipo de Flujo')

@section('content-area')
@if ($errors->any())
  <div class="alert alert-danger">
    <strong>Revisa el formulario</strong>
    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
  </div>
@endif

<form action="{{ route('tipo-flujo.store') }}" method="POST">
  @csrf
  <div class="card">
    <div class="card-header"><strong>Información</strong></div>
    <div class="card-body">
      @include('superadmin.tipo_flujo._form', ['tipo' => null])
    </div>
  </div>

  <div class="mt-3 d-flex gap-2">
    <a href="{{ route('tipo-flujo.index') }}" class="btn btn-secondary">Cancelar</a>
    <button class="btn btn-primary">Crear</button>
  </div>
</form>
@endsection
