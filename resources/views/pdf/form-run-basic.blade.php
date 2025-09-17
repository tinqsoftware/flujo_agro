<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $form->nombre }} - Formulario #{{ $run->id }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #007bff;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .header h1 {
            margin: 0;
            color: #007bff;
            font-size: 24px;
        }
        .header .subtitle {
            margin: 5px 0 0 0;
            color: #666;
            font-size: 14px;
        }
        .info-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .info-row {
            margin-bottom: 8px;
        }
        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 120px;
        }
        .form-section {
            margin-bottom: 25px;
        }
        .form-title {
            background-color: #007bff;
            color: white;
            padding: 10px;
            margin: 0 0 15px 0;
            font-size: 16px;
            font-weight: bold;
        }
        .field-group {
            margin-bottom: 15px;
        }
        .field-label {
            font-weight: bold;
            color: #495057;
            margin-bottom: 5px;
        }
        .field-value {
            padding: 8px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 3px;
            min-height: 20px;
        }
        .no-data {
            color: #6c757d;
            font-style: italic;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #6c757d;
            border-top: 1px solid #dee2e6;
            padding-top: 10px;
        }
        .group-section {
            border: 1px solid #dee2e6;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .group-header {
            background-color: #e9ecef;
            padding: 10px;
            font-weight: bold;
            border-bottom: 1px solid #dee2e6;
        }
        .group-content {
            padding: 15px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $form->nombre }}</h1>
        <div class="subtitle">Formulario Completado</div>
    </div>

    <div class="info-section">
        <div class="info-row">
            <span class="info-label">ID Ejecución:</span>
            <span>#{{ $run->id }}</span>
        </div>
        @if($run->correlativo)
        <div class="info-row">
            <span class="info-label">Correlativo:</span>
            <span>{{ $run->correlativo }}</span>
        </div>
        @endif
        <div class="info-row">
            <span class="info-label">Estado:</span>
            <span>{{ $run->estado }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Fecha Creación:</span>
            <span>{{ $run->created_at->format('d/m/Y H:i:s') }}</span>
        </div>
        @if($run->updated_at != $run->created_at)
        <div class="info-row">
            <span class="info-label">Última Actualización:</span>
            <span>{{ $run->updated_at->format('d/m/Y H:i:s') }}</span>
        </div>
        @endif
    </div>

    <div class="form-section">
        <h2 class="form-title">Datos del Formulario</h2>
        
        @if($form->descripcion)
        <div class="field-group">
            <div class="field-label">Descripción:</div>
            <div class="field-value">{{ $form->descripcion }}</div>
        </div>
        @endif

        @if(isset($data['fields']) && count($data['fields']) > 0)
            @foreach($data['fields'] as $field)
                <div class="field-group">
                    <div class="field-label">{{ $field['etiqueta'] ?? $field['codigo'] }}</div>
                    <div class="field-value">
                        @if(!empty($field['valor']))
                            {{ $field['valor'] }}
                        @else
                            <span class="no-data">Sin datos</span>
                        @endif
                    </div>
                </div>
            @endforeach
        @else
            <div class="no-data">No se encontraron campos de datos.</div>
        @endif
        
        @if(isset($data['groups']) && count($data['groups']) > 0)
            @foreach($data['groups'] as $group)
                <div class="group-section">
                    <div class="group-header">{{ $group['nombre'] ?? 'Grupo' }}</div>
                    <div class="group-content">
                        @if(isset($group['fields']) && count($group['fields']) > 0)
                            @foreach($group['fields'] as $field)
                                <div class="field-group">
                                    <div class="field-label">{{ $field['etiqueta'] ?? $field['codigo'] }}</div>
                                    <div class="field-value">
                                        @if(!empty($field['valor']))
                                            {{ $field['valor'] }}
                                        @else
                                            <span class="no-data">Sin datos</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="no-data">No se encontraron campos en este grupo.</div>
                        @endif
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    <div class="footer">
        Generado el {{ now()->format('d/m/Y H:i:s') }} | Sistema de Gestión de Flujos
    </div>
</body>
</html>