@extends('layouts.dashboard')

@section('title') Ejecución #{{ $run->id }} — {{ $run->form->nombre }} @endsection
@section('page-title') Ejecución #{{ $run->id }} — {{ $run->form->nombre }} @endsection

@section('content-area')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <div><strong>Correlativo:</strong> {{ $run->correlativo ?? '—' }}</div>
    <div><strong>Estado:</strong> {{ $run->estado }}</div>
  </div>
  <div>
    @php
      // Plantilla PDF asociada al formulario, si existe
      $templateId = \App\Models\PdfTemplate::where('id_form', $run->id_form)->value('id');
  // Buscar PDF previamente generado y guardado para este FormRun (incluye private/)
  $detallePdf = \App\Models\DetalleDocumento::where(function($q) use ($run) {
          $q->where('ruta_doc', 'like', "form_runs/{$run->id}/%")
        ->orWhere('ruta_doc', 'like', "private/form_runs/{$run->id}/%");
      })
      ->orderBy('created_at', 'desc')
      ->first();
    @endphp

    @if($detallePdf)
      @php
        $candidates = [$detallePdf->ruta_doc, 'private/' . ltrim($detallePdf->ruta_doc, '/'), preg_replace('/^private\//', '', $detallePdf->ruta_doc)];
        $found = null;
        foreach ($candidates as $cand) {
          if (\Illuminate\Support\Facades\Storage::exists($cand)) { $found = $cand; break; }
        }
      @endphp
      @if($found)
      {{-- Mostrar enlace al PDF guardado --}}
      @php
        $isPrivate = str_starts_with($detallePdf->ruta_doc, 'private/');
      @endphp
      @php $isPrivate = str_starts_with($found, 'private/'); @endphp
      @if($isPrivate)
        <a class="btn btn-outline-primary" href="{{ route('form-runs.pdf.basic', $run->id) }}" target="_blank">
          <i class="fas fa-file-pdf"></i> Ver PDF guardado
        </a>
      @else
        <a class="btn btn-outline-primary" href="{{ \Illuminate\Support\Facades\Storage::url($found) }}" target="_blank">
          <i class="fas fa-file-pdf"></i> Ver PDF guardado
        </a>
      @endif
      <small class="text-muted ms-2">Generado: {{ optional($detallePdf->updated_at)->format('d/m/Y H:i') }}</small>
      {{-- Botón para regenerar (usar plantilla si existe, si no usar generación básica) --}}
      @if($templateId)
        <a class="btn btn-outline-secondary ms-2" href="{{ route('form-runs.pdf', [$run->id, $templateId]) }}" target="_blank">Regenerar PDF (plantilla)</a>
      @else
        <a class="btn btn-outline-secondary ms-2" href="{{ route('form-runs.pdf.basic', $run->id) }}" target="_blank">Regenerar PDF</a>
      @endif
    @else
      {{-- No hay PDF guardado: mostrar botón para generar usando plantilla o básico --}}
      @if($templateId)
        <a class="btn btn-outline-secondary" href="{{ route('form-runs.pdf', [$run->id, $templateId]) }}" target="_blank">Generar PDF</a>
      @else
        <a class="btn btn-outline-secondary" href="{{ route('form-runs.pdf.basic', $run->id) }}" target="_blank">Generar PDF</a>
      @endif
    @endif
    @endif
  </div>
</div>

{{-- aquí puedes mostrar un resumen de valores guardados si quieres --}}
<div class="card">
  <div class="card-header">Datos guardados</div>
  <div class="card-body">
    <p>La ejecución se guardó correctamente. Puedes generar el PDF desde el botón.</p>
  </div>
</div>
@endsection
