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
    {{-- Si tienes plantillas por form, pon aquí el id de la que quieras --}}
    @php
      $templateId = \App\Models\PdfTemplate::where('id_form', $run->id_form)->value('id');
    @endphp
    @if($templateId)
      <a class="btn btn-outline-secondary"
         href="{{ route('form-runs.pdf', [$run->id, $templateId]) }}" target="_blank">Generar PDF</a>
    @else
      <a class="btn btn-outline-secondary disabled" href="#">PDF (sin plantilla)</a>
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
