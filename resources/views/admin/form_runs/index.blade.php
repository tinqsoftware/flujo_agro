@extends('layouts.dashboard')
@section('title','Ejecuciones')
@section('page-title','Ejecuciones')
@section('content-area')
<div class="d-flex justify-content-between align-items-center">
  <a class="btn btn-primary" href="{{ route('forms.index') }}">Ir a Formularios</a>
</div>
<table class="table table-striped mt-3">
  <tr><th>ID</th><th>Form</th><th>Empresa</th><th>Correlativo</th><th>Estado</th><th></th></tr>
  @foreach($runs as $r)
  <tr>
    <td>{{ $r->id }}</td>
    <td>{{ optional($r->form)->nombre }}</td>
    <td>{{ $r->id_emp }}</td>
    <td>{{ $r->correlativo }}</td>
    <td>{{ $r->estado }}</td>
    <td class="text-end">
      <a class="btn btn-sm btn-secondary" href="{{ route('form-runs.edit',$r) }}">Editar</a>
      <form method="post" action="{{ route('form-runs.destroy',$r) }}" class="d-inline">
        @csrf @method('delete')
        <button class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Eliminar?')">Eliminar</button>
      </form>
    </td>
  </tr>
  @endforeach
</table>
{{ $runs->links() }}
@endsection
