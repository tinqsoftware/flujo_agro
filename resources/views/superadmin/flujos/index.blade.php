@extends('layouts.dashboard')
@section('title','Flujos')
@section('page-title','Flujos')
@section('page-subtitle','Listado general de flujos')

@section('header-actions')
  <a href="{{ route('flujos.create') }}" class="btn btn-light">
    <i class="fas fa-project-diagram me-1"></i> Nuevo Flujo
  </a>
@endsection

@section('content-area')
<form method="GET" action="{{ route('flujos.index') }}" class="card mb-3">
  <div class="card-body d-flex flex-wrap gap-2 align-items-center">
    <div class="btn-group">
      @php
        $qs = request()->query();
        $make = fn($o=[]) => route('flujos.index', array_merge($qs,$o));
        $estado = $estado ?? 'todos';
        $btn = function($label,$val) use ($estado,$make) {
          $active = $estado===$val ? 'active' : '';
          return '<a class="btn btn-outline-secondary '.$active.'" href="'.$make(['estado'=>$val,'page'=>1]).'">'.$label.'</a>';
        };
      @endphp
      {!! $btn('Todos','todos') !!}
      {!! $btn('Activos','activos') !!}
      {!! $btn('Inactivos','inactivos') !!}
    </div>

    <div class="ms-auto d-flex" style="min-width:320px;">
      <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Buscar por nombre...">
      <button class="btn btn-primary ms-2"><i class="fas fa-search"></i></button>
    </div>
  </div>
</form>

<div class="row g-3">
  @forelse($flujos as $f)
    <div class="col-12 col-md-6 col-xl-4">
      <div class="card h-100 shadow-sm">
        <div class="card-body">
          <div class="d-flex justify-content-between mb-1">
            <div>
              <h5 class="card-title mb-0">{{ $f->nombre }}</h5>
              <div class="text-muted small">
                {{ $f->tipo->nombre ?? '—' }}
                @if($isSuper) · {{ $f->empresa->nombre ?? '—' }} @endif
              </div>
            </div>
            <div>
              <span class="badge {{ $f->estado?'bg-success':'bg-secondary' }}">{{ $f->estado?'Activo':'Inactivo' }}</span>
              <a class="btn btn-sm btn-light ms-2" href="{{ route('flujos.edit',$f->id) }}"><i class="fas fa-edit"></i></a>
            </div>
          </div>

          <div class="mt-2">
            @php $etps = $etapasPorFlujo[$f->id] ?? collect(); @endphp
            @forelse($etps as $e)
              <div class="d-flex justify-content-between">
                <div class="text-muted">{{ $e->nro }}. {{ $e->nombre }}</div>
                <div class="small">
                  <i class="far fa-check-square me-1"></i>{{ $e->tareas_count }}
                  <i class="far fa-file-alt ms-3 me-1"></i>{{ $e->documentos_count }}
                </div>
              </div>
            @empty
              <div class="text-muted small">Sin etapas.</div>
            @endforelse
          </div>

          @if($f->descripcion)
            <hr><div class="small text-muted">{{ \Illuminate\Support\Str::limit($f->descripcion,140) }}</div>
          @endif
        </div>
      </div>
    </div>
  @empty
    <div class="col-12"><div class="alert alert-info mb-0">No hay flujos registrados.</div></div>
  @endforelse
</div>

<div class="mt-3">{{ $flujos->links() }}</div>
@endsection
