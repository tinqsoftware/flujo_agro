<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
    h1,h2,h3 { margin: 0 0 6px; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
    .muted { color: #666; }
  </style>
</head>
<body>
  <h2>{{ $tpl->nombre ?? 'Documento' }}</h2>
  <p class="muted">Ejecución #{{ $data['run']['id'] }} — {{ $data['form']['nombre'] }}</p>
  @if(!empty($data['run']['correlativo']))
    <p><strong>Correlativo:</strong> {{ $data['run']['correlativo'] }}</p>
  @endif

  <h3>Cabecera</h3>
  <table>
    <tbody>
      @foreach($data['header'] as $k => $v)
        <tr>
          <th style="width:30%">{{ $k }}</th>
          <td>{{ is_scalar($v) ? $v : json_encode($v, JSON_UNESCAPED_UNICODE) }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>

  @foreach($data['groups'] as $gname => $rows)
    <h3 style="margin-top:16px">{{ $gname }}</h3>
    @php
      $cols = [];
      foreach ($rows as $row) { $cols = array_unique(array_merge($cols, array_keys($row))); }
    @endphp
    <table>
      <thead>
        <tr>
          @foreach($cols as $c) <th>{{ $c }}</th> @endforeach
        </tr>
      </thead>
      <tbody>
        @foreach($rows as $row)
          <tr>
            @foreach($cols as $c)
              <td>{{ isset($row[$c]) ? (is_scalar($row[$c]) ? $row[$c] : json_encode($row[$c], JSON_UNESCAPED_UNICODE)) : '' }}</td>
            @endforeach
          </tr>
        @endforeach
      </tbody>
    </table>
  @endforeach
</body>
</html>
