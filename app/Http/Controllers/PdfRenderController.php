<?php

namespace App\Http\Controllers;

use App\Models\{FormRun, PdfTemplate};
use App\Services\Forms\PdfRenderService;

class PdfRenderController extends Controller
{
    public function show(FormRun $run, PdfTemplate $template, PdfRenderService $service) {
        $pdf = $service->render($run, $template);
        return $pdf->stream("form-{$run->id}.pdf");
    }
    
    /**
     * Generar PDF sin plantilla específica (usar plantilla por defecto o genérica)
     */
    public function showWithoutTemplate(FormRun $run, PdfRenderService $service) {
        // Buscar plantilla del formulario o crear una básica
        $template = PdfTemplate::where('id_form', $run->id_form)->first();
        
        if ($template) {
            // Si existe plantilla, usarla
            $pdf = $service->render($run, $template);
        } else {
            // Si no existe plantilla, generar PDF básico
            $pdf = $service->renderBasic($run);
        }
        
        return $pdf->stream("form-{$run->id}.pdf");
    }
}
