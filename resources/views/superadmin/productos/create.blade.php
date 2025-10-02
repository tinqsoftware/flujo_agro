@extends('layouts.dashboard')

@section('title','Nuevo Producto')
@section('page-title','Crear Producto')

@section('content-area')
@if ($errors->any())
  <div class="alert alert-danger"><strong>Revisa el formulario:</strong>
    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
  </div>
@endif

<form action="{{ route('productos.store') }}" method="POST" enctype="multipart/form-data" id="productoForm">
  @csrf

  <div class="card mb-4">
    <div class="card-header"><strong>Información</strong></div>
    <div class="card-body">
      <div class="row">
        @if($isSuper)
          <div class="col-md-6 mb-3">
            <label class="form-label">Empresa *</label>
            <select name="id_emp" id="id_emp" class="form-select" required>
              <option value="">Seleccionar</option>
              @foreach($empresas as $e)
                <option value="{{ $e->id }}" {{ old('id_emp')==$e->id?'selected':'' }}>{{ $e->nombre }}</option>
              @endforeach
            </select>
          </div>
        @endif

        <div class="col-md-6 mb-3">
          <label class="form-label">Nombre *</label>
          <input type="text" name="nombre" class="form-control" value="{{ old('nombre') }}" required>
        </div>

        <div class="col-md-12 mb-3">
          <label class="form-label">Descripción</label>
          <textarea name="descripcion" class="form-control" rows="4">{{ old('descripcion') }}</textarea>
        </div>

        <div class="col-md-6 mb-3">
          <label class="form-label">Fecha de inicio</label>
          <input type="date" name="fecha_inicio" class="form-control" value="{{ old('fecha_inicio') }}">
        </div>

        <div class="col-md-6 mb-3">
          <label class="form-label">Foto</label>
          <input type="file" name="ruta_foto" class="form-control" accept="image/*">
          <div class="form-text">Opcional</div>
        </div>
      </div>

      {{-- Campos dinámicos de la ficha Producto --}}
      @include('superadmin.productos.partials.attrs', ['atributos' => $atributos, 'valores' => []])
      @include('superadmin.productos.partials.ficha_groups', ['groupDefs'=>$groupDefs, 'relOptions'=>$relOptions])
    </div>
  </div>

  <div class="mt-3 d-flex gap-2">
    <a href="{{ route('productos.index') }}" class="btn btn-secondary">Cancelar</a>
    <button class="btn btn-primary">Crear</button>
  </div>
</form>
@endsection

@push('scripts')
{{-- Si el usuario es superadmin, recargar atributos al cambiar empresa (mismo patrón que clientes) --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
  const isSuper = {{ $isSuper ? 'true' : 'false' }};
  if (isSuper) {
    const emp = document.getElementById('id_emp');
    const cont = document.getElementById('attrsContainer');

    function renderAttrs(attrs) {
      let html = '';
      if (!attrs.length) {
        html = '<div class="text-muted">.</div>';
      } else {
        html = `{!! str_replace("\n","", addslashes(view('superadmin.clientes.partials.attrs', ['atributos' => collect(), 'valores'=>[]])->render())) !!}`;
        // Eso imprime un contenedor vacío; lo sustituimos dinámicamente:
        html = '<div class="row g-3">' +
          attrs.map(a => {
            const req = a.obligatorio ? 'required' : '';
            const col = 'col-12'; // puedes usar a.ancho si quieres grid 12
            if (['texto','cajatexto','decimal','entero','fecha','imagen'].includes(a.tipo)) {
              const type = (a.tipo==='fecha') ? 'date' : 'text';
              return `<div class="${col}">
                <label class="form-label">${a.titulo}${a.obligatorio?' *':''}</label>
                <input ${req} type="${type}" name="atributos[${a.id}]" class="form-control">
              </div>`;
            }
            if (['desplegable','radio','checkbox'].includes(a.tipo)) {
              const opts = (a.opciones||[]).map(o => o).filter(Boolean);
              if (a.tipo==='desplegable') {
                return `<div class="${col}">
                  <label class="form-label">${a.titulo}${a.obligatorio?' *':''}</label>
                  <select ${req} name="atributos[${a.id}]" class="form-select">
                    <option value="">Seleccionar</option>
                    ${opts.map(o=>`<option value="${o}">${o}</option>`).join('')}
                  </select>
                </div>`;
              }
              if (a.tipo==='radio') {
                return `<div class="${col}">
                  <label class="form-label d-block">${a.titulo}${a.obligatorio?' *':''}</label>
                  ${opts.map(o=>`
                    <div class="form-check form-check-inline">
                      <input ${req} class="form-check-input" type="radio" name="atributos[${a.id}]" value="${o}">
                      <label class="form-check-label">${o}</label>
                    </div>`).join('')}
                </div>`;
              }
              if (a.tipo==='checkbox') {
                return `<div class="${col}">
                  <label class="form-label d-block">${a.titulo}${a.obligatorio?' *':''}</label>
                  ${opts.map(o=>`
                    <div class="form-check form-check-inline">
                      <input class="form-check-input" type="checkbox" name="atributos[${a.id}][]" value="${o}">
                      <label class="form-check-label">${o}</label>
                    </div>`).join('')}
                </div>`;
              }
            }
            return '';
          }).join('') + '</div>';
      }
      cont.innerHTML = html;
    }

    emp.addEventListener('change', function(){
      const id = this.value;
      cont.innerHTML = '<div class="text-muted">Cargando campos…</div>';
      if (!id) { renderAttrs([]); return; }
      fetch(`{{ route('clientes.atributosByEmpresa') }}?empresa_id=${id}`)
        .then(r=>r.json())
        .then(renderAttrs)
        .catch(()=> cont.innerHTML = '<div class="text-danger">Error al cargar atributos.</div>');
    });

    if (emp.value) {
      emp.dispatchEvent(new Event('change'));
    }
  }
});
</script>
@endpush
