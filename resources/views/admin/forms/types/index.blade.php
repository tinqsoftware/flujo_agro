@extends('layouts.dashboard')
@section('title','Tipos de formularios')
@section('page-title','Tipos de formularios')
@section('content-area')
<div class="d-flex justify-content-between align-items-center">
  <a class="btn btn-primary" href="{{ route('form-types.create') }}">Nuevo Tipo</a>
</div>
<table class="table table-striped mt-3">
  <thead><tr><th>ID</th><th>Empresa</th><th>Nombre</th><th>Estado</th><th></th></tr></thead>
  <tbody>
  @foreach($types as $t)
    <tr>
      <td>{{ $t->id }}</td>
      <td>{{ $t->id_emp }}</td>
      <td>{{ $t->nombre }}</td>
      <td><span class="badge bg-{{ $t->estado?'success':'secondary' }}">{{ $t->estado?'Activo':'Inactivo' }}</span></td>
      <td class="text-end">
        <a class="btn btn-sm btn-secondary" href="{{ route('form-types.edit',$t) }}">Editar</a>
        <form action="{{ route('form-types.destroy',$t) }}" method="post" class="d-inline">
          @csrf @method('delete')
          <button class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Eliminar?')">Eliminar</button>
        </form>
      </td>
    </tr>
  @endforeach
  </tbody>
</table>
{{ $types->links() }}
@endsection
