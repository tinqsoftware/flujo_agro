@extends('layouts.dashboard')
@section('title','Formularios')
@section('page-title','Formularios')
@section('content-area')
<div class="d-flex justify-content-between align-items-center">
  <a href="{{ route('forms.create') }}" class="btn btn-primary">Nuevo</a>
</div>
<table class="table table-striped mt-3">
  <tr><th>ID</th><th>Empresa</th><th>Nombre</th><th>Tipo</th><th>Correlativo</th><th></th></tr>
  @foreach($forms as $f)
  <tr>
    <td>{{ $f->id }}</td>
    <td>{{ $f->id_emp }}</td>
    <td>{{ $f->nombre }}</td>
    <td>{{ optional($f->type)->nombre }}</td>
    <td>{{ $f->usa_correlativo ? 'Sí' : 'No' }}</td>
    <td class="text-end">
      <a href="{{ route('forms.edit',$f) }}" class="btn btn-sm btn-secondary">Builder</a>
      <form action="{{ route('forms.destroy',$f) }}" method="post" class="d-inline">
        @csrf @method('delete')
        <button class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Eliminar?')">Eliminar</button>
      </form>
    </td>
  </tr>
  @endforeach
</table>
{{ $forms->links() }}
@endsection
