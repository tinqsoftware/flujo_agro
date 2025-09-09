@if ($errors->any())
  <div class="alert alert-danger">
    <strong>Errores:</strong>
    <ul class="mb-0">
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

@if (session('ok'))
  <div class="alert alert-success">{{ session('ok') }}</div>
@endif
