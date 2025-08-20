@if(empty($atributos) || !count($atributos))
  <div id="attrsContainer" class="text-muted"></div>
@else
  <div id="attrsContainer" class="row g-3">
    @foreach($atributos as $a)
      @php
        $value  = $valores[$a->id] ?? null;
        $opts   = (in_array($a->tipo,['radio','desplegable','checkbox']) && $a->json) ? json_decode($a->json,true) : null;
        if (is_string($value) && $a->tipo==='checkbox') { $dv=json_decode($value,true); if (json_last_error()===JSON_ERROR_NONE) $value=$dv; }
        $chars  = $a->ancho ? (int)$a->ancho : null;
        $req    = $a->obligatorio ? 'required' : '';
        $scalar = is_array($value) ? '' : ($value ?? '');
      @endphp

      <div class="col-md-6 mb-3">
        <label class="form-label">
          {{ $a->titulo }} @if($a->obligatorio)<span class="text-danger">*</span>@endif
        </label>

        @if(in_array($a->tipo,['texto','cajatexto','decimal','entero','fecha','imagen']))
          @switch($a->tipo)
            @case('texto')
              <input type="text" name="atributos[{{ $a->id }}]" value="{{ old("atributos.$a->id",$scalar) }}" class="form-control" {{ $req }} @if($chars) maxlength="{{ $chars }}" size="{{ $chars }}" style="max-width:100%;" @endif>
              @break
            @case('cajatexto')
              <textarea name="atributos[{{ $a->id }}]" class="form-control" rows="3" {{ $req }} @if($chars) maxlength="{{ $chars }}" cols="{{ $chars }}" style="max-width:100%;" @endif>{{ old("atributos.$a->id",$scalar) }}</textarea>
              @break
            @case('entero')
              <input type="number" name="atributos[{{ $a->id }}]" value="{{ old("atributos.$a->id",$scalar) }}" class="form-control" step="1" inputmode="numeric" pattern="\d*" {{ $req }}>
              @break
            @case('decimal')
              <input type="number" name="atributos[{{ $a->id }}]" value="{{ old("atributos.$a->id",$scalar) }}" class="form-control" step="0.01" inputmode="decimal" {{ $req }}>
              @break
            @case('fecha')
              <input type="date" name="atributos[{{ $a->id }}]" value="{{ old("atributos.$a->id",$scalar) }}" class="form-control" {{ $req }}>
              @break
            @case('imagen')
              <input type="file" name="atributos_archivo[{{ $a->id }}]" class="form-control" accept="image/*">
              @if(!empty($scalar))
                <input type="hidden" name="atributos[{{ $a->id }}]" value="{{ $scalar }}">
                <div class="mt-2">
                  <p class="mb-1"><strong>Imagen actual:</strong></p>
                  <img src="{{ asset('storage/'.$scalar) }}" class="img-thumbnail" style="max-width:200px;height:auto;">
                  <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="atributos_eliminar[{{ $a->id }}]" value="1" id="del{{ $a->id }}">
                    <label for="del{{ $a->id }}" class="form-check-label">Eliminar imagen actual</label>
                  </div>
                </div>
              @endif
              @break
          @endswitch

        @elseif($a->tipo==='desplegable')
          <select name="atributos[{{ $a->id }}]" class="form-select" {{ $req }}>
            <option value="">Seleccionar</option>
            @foreach((array)$opts as $o)
              <option value="{{ $o }}" {{ old("atributos.$a->id",$scalar)==$o?'selected':'' }}>{{ $o }}</option>
            @endforeach
          </select>

        @elseif($a->tipo==='radio')
          <div>
            @foreach((array)$opts as $o)
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="atributos[{{ $a->id }}]" value="{{ $o }}" {{ old("atributos.$a->id",$scalar)==$o?'checked':'' }} {{ $req }}>
              <label class="form-check-label">{{ $o }}</label>
            </div>
            @endforeach
          </div>

        @elseif($a->tipo==='checkbox')
          @php $vals = old("atributos.$a->id", is_array($value)?$value:(array)$value); @endphp
          <div>
            @foreach((array)$opts as $o)
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="checkbox" name="atributos[{{ $a->id }}][]" value="{{ $o }}" {{ in_array($o, $vals ?? []) ? 'checked' : '' }}>
              <label class="form-check-label">{{ $o }}</label>
            </div>
            @endforeach
          </div>
        @endif
      </div>
    @endforeach
  </div>
@endif
