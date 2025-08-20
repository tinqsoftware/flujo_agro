<div class="row">
  @if($isSuper)
    <div class="col-md-6 mb-3">
      <label class="form-label">Empresa *</label>
      <select name="id_emp" class="form-select" required>
        <option value="">Seleccionar</option>
        @foreach($empresas as $e)
          <option value="{{ $e->id }}" {{ old('id_emp', $tipo->id_emp ?? '')==$e->id ? 'selected':'' }}>
            {{ $e->nombre }}
          </option>
        @endforeach
      </select>
    </div>
  @else
    {{-- oculto para no-super --}}
    <input type="hidden" name="id_emp" value="{{ auth()->user()->id_emp }}">
  @endif

  <div class="col-md-6 mb-3">
    <label class="form-label">Nombre *</label>
    <input type="text" name="nombre" class="form-control"
           value="{{ old('nombre', $tipo->nombre ?? '') }}" required>
  </div>

  <div class="col-12 mb-3">
    <label class="form-label">Descripción</label>
    <textarea name="descripcion" class="form-control" rows="3"
      maxlength="2000">{{ old('descripcion', $tipo->descripcion ?? '') }}</textarea>
  </div>

  @isset($tipo) {{-- solo en editar --}}
    <div class="col-md-6 mb-3 d-flex align-items-end">
      <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" id="estadoTipo"
               name="estado" value="1" {{ old('estado', $tipo->estado ?? true) ? 'checked' : '' }}>
        <label for="estadoTipo" class="form-check-label">
          {{ old('estado', $tipo->estado ?? true) ? 'Activo' : 'Inactivo' }}
        </label>
      </div>
    </div>
  @endisset
</div>
