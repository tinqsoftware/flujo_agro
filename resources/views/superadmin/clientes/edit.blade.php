@extends('layouts.dashboard')

@section('title','Editar Cliente')
@section('page-title','Editar Cliente')

@section('content-area')
@if ($errors->any())
  <div class="alert alert-danger">
    <strong>Revisa el formulario:</strong>
    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
  </div>
@endif

<form action="{{ route('clientes.update',$cliente) }}" method="POST" enctype="multipart/form-data">
  @csrf @method('PUT')
<input type="hidden" name="id_ficha" value="{{ $cliente->id_ficha }}">

  <div class="card mb-4">
    <div class="card-header"><strong>Información básica</strong></div>
    <div class="card-body">
      <div class="row">
        @if($isSuper)
          <div class="col-md-6 mb-3">
            <label class="form-label">Empresa *</label>
            <select name="id_emp" class="form-select" required>
              @foreach($empresas as $e)
                <option value="{{ $e->id }}" {{ $cliente->id_emp==$e->id?'selected':'' }}>{{ $e->nombre }}</option>
              @endforeach
            </select>
          </div>
        @endif

        <div class="col-md-6 mb-3">
          <label class="form-label">Nombre *</label>
          <input type="text" name="nombre" class="form-control" value="{{ old('nombre',$cliente->nombre) }}" required>
        </div>

        <div class="col-md-6 mb-3 d-flex align-items-end">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="estadoCliente"
                    name="estado" value="1" {{ old('estado', $cliente->estado) ? 'checked' : '' }}>
                <label class="form-check-label" for="estadoCliente">
                {{ old('estado', $cliente->estado) ? 'Activo' : 'Inactivo' }}
                </label>
            </div>
        </div>

        <div class="col-md-6 mb-3">
          <label class="form-label">Logo (opcional)</label>
          <input type="file" name="logo" class="form-control">
          @if($cliente->ruta_logo)
            <div class="mt-2">
              <img src="{{ asset('storage/'.$cliente->ruta_logo) }}" style="height:40px">
            </div>
          @endif
        </div>
        
      </div>
      @include('superadmin.clientes.partials.attrs', ['atributos' => $atributos, 'valores' => $valores])
      @include('superadmin.clientes.partials.ficha_groups', ['groupDefs'=>$groupDefs, 'relOptions'=>$relOptions, 'listValues'=>$listValues ?? collect(), 'relValues'=>$relValues ?? collect()  ])

    </div>
  </div>

  <div class="mt-3 d-flex gap-2">
    <a href="{{ route('clientes.index') }}" class="btn btn-secondary">Cancelar</a>
    <button class="btn btn-primary">Guardar</button>
  </div>
</form>
@endsection
