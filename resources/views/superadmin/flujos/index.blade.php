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
              <div class="d-flex justify-content-between align-items-center mb-1">
                <div class="text-muted">{{ $e->nro }}. {{ $e->nombre }}</div>
                <div class="small d-flex align-items-center gap-2">
                  <span class="badge bg-light text-dark">
                    <i class="fas fa-tasks me-1"></i>{{ $e->tareas_count }} tareas
                  </span>
                  @if($e->documentos_count > 0)
                    <span class="badge bg-info text-white">
                      <i class="fas fa-paperclip me-1"></i>{{ $e->documentos_count }} docs
                    </span>
                  @endif
                </div>
              </div>
            @empty
              <div class="text-muted small">Sin etapas configuradas.</div>
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

@if($flujos->hasPages())
<div class="mt-4">
  <div class="d-flex justify-content-center">
    <nav aria-label="Navegación de páginas">
      <ul class="pagination pagination-sm">
        {{-- Botón Anterior --}}
        @if ($flujos->onFirstPage())
          <li class="page-item disabled">
            <span class="page-link">
              <i class="fas fa-chevron-left"></i>
              <span class="d-none d-sm-inline ms-1">Anterior</span>
            </span>
          </li>
        @else
          <li class="page-item">
            <a class="page-link" href="{{ $flujos->previousPageUrl() }}" rel="prev">
              <i class="fas fa-chevron-left"></i>
              <span class="d-none d-sm-inline ms-1">Anterior</span>
            </a>
          </li>
        @endif

        {{-- Números de página --}}
        @foreach ($flujos->getUrlRange(1, $flujos->lastPage()) as $page => $url)
          @if ($page == $flujos->currentPage())
            <li class="page-item active">
              <span class="page-link">{{ $page }}</span>
            </li>
          @else
            <li class="page-item">
              <a class="page-link" href="{{ $url }}">{{ $page }}</a>
            </li>
          @endif
        @endforeach

        {{-- Botón Siguiente --}}
        @if ($flujos->hasMorePages())
          <li class="page-item">
            <a class="page-link" href="{{ $flujos->nextPageUrl() }}" rel="next">
              <span class="d-none d-sm-inline me-1">Siguiente</span>
              <i class="fas fa-chevron-right"></i>
            </a>
          </li>
        @else
          <li class="page-item disabled">
            <span class="page-link">
              <span class="d-none d-sm-inline me-1">Siguiente</span>
              <i class="fas fa-chevron-right"></i>
            </span>
          </li>
        @endif
      </ul>
    </nav>
  </div>
  
  {{-- Información de resultados --}}
  <div class="text-center mt-2">
    <small class="text-muted">
      Mostrando {{ $flujos->firstItem() }} - {{ $flujos->lastItem() }} de {{ $flujos->total() }} resultados
    </small>
  </div>
</div>
@endif
@endsection
