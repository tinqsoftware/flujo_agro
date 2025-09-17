<?php
namespace App\Services\Forms;

use App\Models\{FormRun, PdfTemplate};
use Barryvdh\DomPDF\Facade\Pdf; // si usas barryvdh/laravel-dompdf

class PdfRenderService {
    public function render(FormRun $run, PdfTemplate $tpl) {
        // Resuelve bindings aquí y pásalos a una view blade
        $data = app(RunDataAssembler::class)->build($run); // arma hash: campos, grupos, correlativo, etc.
        return Pdf::loadView('pdf.form-run', ['tpl'=>$tpl,'data'=>$data])->setPaper('a4');
    }
    
    /**
     * Renderizar PDF básico sin plantilla específica
     */
    public function renderBasic(FormRun $run) {
        // Ensamblar datos del FormRun
        $data = app(RunDataAssembler::class)->build($run);
        
        // Usar una vista PDF básica sin plantilla específica
        return Pdf::loadView('pdf.form-run-basic', [
            'run' => $run,
            'data' => $data,
            'form' => $run->form
        ])->setPaper('a4');
    }
}
