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
}
