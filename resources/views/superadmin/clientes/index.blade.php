@extends('layouts.dashboard')

@section('title','Clientes')
@section('page-title','Clientes')
@section('page-subtitle','Listado general de clientes')

@section('header-actions')
  <a href="{{ route('clientes.create') }}" class="btn btn-light">
    <i class="fas fa-user-friends me-1"></i> Nuevo Cliente
  </a>
@endsection

@section('content-area')

{{-- Toolbar filtros/búsqueda/vista --}}
<form method="GET" action="{{ route('clientes.index') }}" class="card mb-3">
  <div class="card-body d-flex flex-wrap gap-2 align-items-center">

    {{-- Filtro estado --}}
    <div class="btn-group">
      @php
        $qs = request()->query();
        $make = fn($over=[]) => route('clientes.index', array_merge($qs,$over));
        $btn = function($label,$val) use ($estado,$make) {
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
      <input type="hidden" name="vista"  value="{{ $vista }}">
      <input type="hidden" name="sort"   value="{{ $sort }}">
      <input type="hidden" name="dir"    value="{{ $dir }}">

      <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Buscar por nombre...">
      <button class="btn btn-primary ms-2">
        <i class="fas fa-search"></i>
      </button>
    </div>

    {{-- Toggle vista --}}
    <div class="ms-auto btn-group" role="group" aria-label="Vistas">
      <a class="btn btn-outline-secondary {{ $vista==='cards' ? 'active' : '' }}"
         href="{{ $make(['vista'=>'cards','page'=>1]) }}"
         title="Vista tarjetas">
        <i class="fas fa-th-large"></i>
      </a>
      <a class="btn btn-outline-secondary {{ $vista==='tabla' ? 'active' : '' }}"
         href="{{ $make(['vista'=>'tabla','page'=>1]) }}"
         title="Vista tabla">
        <i class="fas fa-table"></i>
      </a>
    </div>
  </div>
</form>

{{-- Contenido --}}
@if($vista === 'tabla')
  {{-- ===== Vista TABLA ===== --}}
    <div class="card">
        <div class="card-body p-0">
            <div class="table-scroll-x">
            @php
                $qs = request()->query();
                $sortLink = function($col) use($qs,$sort,$dir) {
                $next = ($sort===$col && $dir==='asc') ? 'desc' : 'asc';
                $icon = '';
                if ($sort===$col) $icon = $dir==='asc' ? ' <i class="fas fa-sort-up"></i>' : ' <i class="fas fa-sort-down"></i>';
                return '<a href="'.route('clientes.index', array_merge($qs,['sort'=>$col,'dir'=>$next,'page'=>1,'vista'=>'tabla'])).'">'.$col.$icon.'</a>';
                };
                $minWidthPx = 900 + ($atributos->count() * 45);
            @endphp
            <table class="table table-hover align-middle mb-0 wide-table" style="min-width: {{ $minWidthPx }}px;">
                <thead class="table-light">
                <tr>
                    <th>{!! $sortLink('nombre') !!}</th>
                    @if($isSuper)
                    <th>Empresa</th>
                    @endif
                    <th>{!! $sortLink('estado') !!}</th>
                    <th>{!! $sortLink('created_at') !!}</th>
                    {{-- columnas dinámicas --}}
                    @foreach($atributos as $a)
                    <th>{{ $a->titulo }}</th>
                    @endforeach
                    <th style="width:80px;">Acciones</th>
                </tr>
                </thead>
                <tbody>
                @forelse($clientes as $c)
                    <tr>
                    <td class="fw-semibold">
                        {{ $c->nombre }}
                    </td>
                    @if($isSuper)
                        <td>{{ $c->empresa->nombre ?? '—' }}</td>
                    @endif
                    <td>
                        <span class="badge {{ $c->estado ? 'bg-success' : 'bg-secondary' }}">
                        {{ $c->estado ? 'Activo' : 'Inactivo' }}
                        </span>
                    </td>
                    <td>{{ optional($c->created_at)->format('d/m/Y') }}</td>

                    {{-- valores dinámicos --}}
                    @foreach($atributos as $a)
                        @php
                        $v = $valoresByCliente[$c->id][$a->id] ?? null;
                        @endphp
                        <td>
                        @if($a->tipo === 'imagen' && $v)
                            <img src="{{ asset('storage/'.$v) }}" alt="img" style="height:30px;">
                        @else
                            {{ $v ?? '—' }}
                        @endif
                        </td>
                    @endforeach

                    <td>
                        <a href="{{ route('clientes.edit',$c->id) }}" class="btn btn-sm btn-light">
                        <i class="fas fa-edit"></i>
                        </a>
                    </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ ($isSuper?4:3) + $atributos->count() + 1 }}" class="text-center text-muted py-4">No hay clientes.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white">
      {{ $clientes->links() }}
    </div>
  </div>

@else
  {{-- ===== Vista TARJETAS (tu diseño original, con mínimos toques) ===== --}}
  <div class="row g-3">
    @forelse($clientes as $c)
      <div class="col-12 col-md-6 col-xl-4">
        <div class="card h-100 shadow-sm">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <div>
                <h5 class="card-title mb-0">{{ $c->nombre }}</h5>
                @if($isSuper)
                  <div class="text-muted small">{{ $c->empresa->nombre ?? '—' }}</div>
                @endif
              </div>
              <div>
                <span class="badge {{ $c->estado ? 'bg-success' : 'bg-secondary' }}">
                  {{ $c->estado ? 'Activo' : 'Inactivo' }}
                </span>
                <a class="btn btn-sm btn-light ms-2" href="{{ route('clientes.edit',$c->id) }}">
                  <i class="fas fa-edit"></i>
                </a>
              </div>
            </div>

            @if($c->ruta_logo)
              <div class="mb-2">
                <img src="{{ asset('storage/'.$c->ruta_logo) }}" alt="logo"  class="img-fluid rounded" style="max-height:150px;">
              </div>
            @endif

            @if(isset($c->resumenAtributos) && $c->resumenAtributos->count())
              @foreach($c->resumenAtributos as $ra)
                <div class="d-flex justify-content-between">
                  <div class="text-muted">{{ $ra['titulo'] }}:</div>
                  <div class="fw-semibold">{{ $ra['valor'] ?? '—' }}</div>
                </div>
              @endforeach
              @if(($c->otrosAtributosCount ?? 0) > 0)
                <div class="small text-muted mt-2">+{{ $c->otrosAtributosCount }} campos más</div>
              @endif
            @else
              <div class="small text-muted">Sin atributos configurados.</div>
            @endif

            <hr>
            <div class="small text-muted">
              <i class="far fa-calendar-alt me-1"></i>
              Creado por: {{ $c->userCreate->name ?? '—' }}, {{ optional($c->created_at)->format('d/m/Y') }}
            </div>
          </div>
        </div>
      </div>
    @empty
      <div class="col-12">
        <div class="alert alert-info mb-0">No hay clientes registrados.</div>
      </div>
    @endforelse
  </div>

  <div class="mt-3">
    {{ $clientes->links() }}
  </div>
@endif
@endsection

@push('styles')
<style>
/* limitar ancho en vista tarjetas */
.content-area > .row {  margin: 0 auto; }
.card-title { font-weight: 700; }
/* tabla con scroll horizontal ya lo da .table-responsive */

.table-scroll-x {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
  width: 100%;            /* solo ocupará el ancho normal del card */
}

.wide-table {
  border-collapse: separate;
}

.nowrap { white-space: nowrap; }
.truncate {
  max-width: 260px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.table thead th {
  position: sticky;
  top: 0;
  background: #f8f9fa;
  z-index: 2;
}
</style>
@endpush
