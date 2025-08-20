@extends('layouts.dashboard')

@section('title','Tipos de Flujo')
@section('page-title','Tipos de Flujo')
@section('page-subtitle','Listado')

@section('header-actions')
  <a href="{{ route('tipo-flujo.create') }}" class="btn btn-light">
    <i class="fas fa-plus me-1"></i> Nuevo Tipo de Flujo
  </a>
@endsection

@section('content-area')

{{-- Toolbar filtros/busqueda/orden (mantiene ancho normal) --}}
<form method="GET" action="{{ route('tipo-flujo.index') }}" class="card mb-3">
  <div class="card-body d-flex flex-wrap gap-2 align-items-center">
    {{-- Filtro estado --}}
    <div class="btn-group">
      @php
        $qs   = request()->query();
        $make = fn($over=[]) => route('tipo-flujo.index', array_merge($qs,$over));
        $btn  = function($label,$val) use ($estado,$make) {
          $active = ($estado===$val || ($val==='todos' && $estado==='todos')) ? 'active' : '';
          return '<a class="btn btn-outline-secondary '.$active.'" href="'.$make(['estado'=>$val,'page'=>1]).'">'.$label.'</a>';
        };
      @endphp
      {!! $btn('Todos','todos') !!}
      {!! $btn('Activos','activos') !!}
      {!! $btn('Inactivos','inactivos') !!}
    </div>

    {{-- Buscador --}}
    <div class="ms-auto d-flex" style="min-width: 320px;">
      <input type="hidden" name="estado" value="{{ $estado }}">
      <input type="hidden" name="sort"   value="{{ $sort }}">
      <input type="hidden" name="dir"    value="{{ $dir }}">
      <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Buscar por nombre o descripción...">
      <button class="btn btn-primary ms-2"><i class="fas fa-search"></i></button>
    </div>
  </div>
</form>

{{-- Tabla con scroll horizontal --}}
<div class="card">
  <div class="table-scroll-x">
    @php
      $qs = request()->query();
      $sortLink = function($col,$label=null) use($qs,$sort,$dir) {
        $next = ($sort===$col && $dir==='asc') ? 'desc' : 'asc';
        $icon = '';
        if ($sort===$col) $icon = $dir==='asc' ? ' <i class="fas fa-sort-up"></i>' : ' <i class="fas fa-sort-down"></i>';
        return '<a href="'.route('tipo-flujo.index', array_merge($qs,['sort'=>$col,'dir'=>$next,'page'=>1])).'">'.($label ?? $col).$icon.'</a>';
      };
      $minWidthPx = 900; // ancho mínimo
    @endphp

    <table class="table table-hover align-middle mb-0 wide-table" style="min-width: {{ $minWidthPx }}px;">
      <thead class="table-light">
        <tr>
          <th>{!! $sortLink('nombre','Nombre') !!}</th>
          <th>Descripción</th>
          @if($isSuper)
            <th>{!! $sortLink('empresa','Empresa') !!}</th>
          @endif
          <th>{!! $sortLink('estado','Estado') !!}</th>
          <th>{!! $sortLink('created_at','Creado') !!}</th>
          <th style="width:90px;">Acciones</th>
        </tr>
      </thead>
      <tbody>
        @forelse($tipos as $t)
          <tr>
            <td class="fw-semibold">{{ $t->nombre }}</td>
            <td class="truncate">{{ $t->descripcion ?? '—' }}</td>
            @if($isSuper)
              <td>{{ $t->empresa->nombre ?? '—' }}</td>
            @endif
            <td>
              <span class="badge {{ $t->estado ? 'bg-success' : 'bg-secondary' }}">
                {{ $t->estado ? 'Activo' : 'Inactivo' }}
              </span>
            </td>
            <td>{{ optional($t->created_at)->format('d/m/Y') }}</td>
            <td>
              <a href="{{ route('tipo-flujo.edit',$t->id) }}" class="btn btn-sm btn-light">
                <i class="fas fa-edit"></i>
              </a>
              {{-- eliminar opcional --}}
              {{-- <form method="POST" action="{{ route('tipo-flujo.destroy',$t->id) }}" class="d-inline" onsubmit="return confirm('¿Eliminar?')">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
              </form> --}}
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="{{ $isSuper ? 6 : 5 }}" class="text-center text-muted py-4">No hay registros.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div class="card-footer bg-white">
    {{ $tipos->links() }}
  </div>
</div>
@endsection

@push('styles')
<style>
.table-scroll-x{ overflow-x:auto; -webkit-overflow-scrolling:touch; }
.truncate{ max-width:460px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.table thead th{ position:sticky; top:0; background:#f8f9fa; z-index:2; }
</style>
@endpush
