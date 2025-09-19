<?php

namespace App\Http\Controllers;

use App\Models\{FormRun, PdfTemplate};
use App\Services\Forms\PdfRenderService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Response;

class PdfRenderController extends Controller
{
    public function show(FormRun $run, PdfTemplate $template, PdfRenderService $service) {
        // Buscar directamente en storage por archivos generados para este FormRun
        // Primero buscar en disco público (storage/app/public/form_runs/{id})
        $publicFiles = Storage::disk('public')->files("form_runs/{$run->id}");
        $foundFile = null;
        if (!empty($publicFiles)) {
            usort($publicFiles, function($a, $b) {
                return filemtime(storage_path('app/public/' . $b)) <=> filemtime(storage_path('app/public/' . $a));
            });
            $foundFile = ['disk' => 'public', 'path' => $publicFiles[0]];
        } else {
            // Si no hay en público, buscar en storage/app/form_runs y storage/app/private/form_runs
            $candidates = ["form_runs/{$run->id}", "private/form_runs/{$run->id}"];
            foreach ($candidates as $dir) {
                $files = Storage::files($dir);
                if (!empty($files)) {
                    usort($files, function($a, $b) {
                        return filemtime(storage_path('app/' . $b)) <=> filemtime(storage_path('app/' . $a));
                    });
                    $foundFile = ['disk' => 'local', 'path' => $files[0]];
                    break;
                }
            }
        }

        if ($foundFile) {
            if ($foundFile['disk'] === 'public') {
                $fullPath = storage_path('app/public/' . $foundFile['path']);
            } else {
                $fullPath = storage_path('app/' . $foundFile['path']);
            }
            Log::info('PdfRenderController: found file to serve', ['foundFile' => $foundFile, 'fullPath' => $fullPath]);
            if (is_file($fullPath)) {
                return response()->file($fullPath, ['Content-Type' => 'application/pdf']);
            }
            Log::warning('PdfRenderController: foundFile path is not a file', ['fullPath' => $fullPath]);
        }

    // Generar PDF dinámicamente
        $pdf = $service->render($run, $template);

        // Guardar PDF en storage (público bajo storage/app/form_runs)
        $output = $pdf->output();
        $filename = "form-{$run->id}-" . time() . ".pdf";
        // Guardar en disco público (storage/app/public/form_runs/{id})
        $path = "form_runs/{$run->id}/{$filename}";
        Storage::disk('public')->put($path, $output);
        Log::info('PdfRenderController: saved generated PDF to public disk', ['diskPath' => $path]);
    Log::info('PdfRenderController: saved generated PDF', ['path' => $path]);

        // Nota: no persistimos en Documento/DetalleDocumento aquí; el PDF queda en storage bajo private/form_runs/{id}

        return response()->stream(function() use ($output) {
            echo $output;
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"form-{$run->id}.pdf\""
        ]);
    }
    
    /**
     * Generar PDF sin plantilla específica (usar plantilla por defecto o genérica)
     */
    public function showWithoutTemplate(FormRun $run, PdfRenderService $service) {
        // Buscar plantilla del formulario o crear una básica
        $template = PdfTemplate::where('id_form', $run->id_form)->first();
        
        if ($template) {
            // Si existe plantilla, usarla
            // Reutilizar lógica de show para manejo de caché/guardado
            return $this->show($run, $template, $service);
        } else {
            // Si no existe plantilla, generar PDF básico
            // Buscar archivo ya generado en storage (sin tocar Documento/DetalleDocumento)
                        $dirs = [
                            "private/form_runs/{$run->id}",
                            "form_runs/{$run->id}"
                        ];
                        $foundFile = null;
                        // Primero revisar disco público
                        $publicFiles = Storage::disk('public')->files("form_runs/{$run->id}");
                        $foundFile = null;
                        if (!empty($publicFiles)) {
                            usort($publicFiles, function($a, $b) {
                                return filemtime(storage_path('app/public/' . $b)) <=> filemtime(storage_path('app/public/' . $a));
                            });
                            $foundFile = ['disk' => 'public', 'path' => $publicFiles[0]];
                        } else {
                            $candidates = ["form_runs/{$run->id}", "private/form_runs/{$run->id}"];
                            foreach ($candidates as $dir) {
                                $files = Storage::files($dir);
                                if (!empty($files)) {
                                    usort($files, function($a, $b) {
                                        return filemtime(storage_path('app/' . $b)) <=> filemtime(storage_path('app/' . $a));
                                    });
                                    $foundFile = ['disk' => 'local', 'path' => $files[0]];
                                    break;
                                }
                            }
                        }

                        if ($foundFile) {
                            if ($foundFile['disk'] === 'public') {
                                $fullPath = storage_path('app/public/' . $foundFile['path']);
                            } else {
                                $fullPath = storage_path('app/' . $foundFile['path']);
                            }
                            Log::info('PdfRenderController (basic): found file to serve', ['foundFile' => $foundFile, 'fullPath' => $fullPath]);
                            if (is_file($fullPath)) {
                                return response()->file($fullPath, ['Content-Type' => 'application/pdf']);
                            }
                            Log::warning('PdfRenderController (basic): foundFile path is not a file', ['fullPath' => $fullPath]);
                        }

            $pdf = $service->renderBasic($run);

            // Guardar PDF en storage (público bajo storage/app/form_runs)
            $output = $pdf->output();
            $filename = "form-{$run->id}-" . time() . ".pdf";
            // Guardar en disco público
            $path = "form_runs/{$run->id}/{$filename}";
            Storage::disk('public')->put($path, $output);
            Log::info('PdfRenderController: saved generated PDF (basic) to public disk', ['diskPath' => $path]);

            // No registrar en Documento/DetalleDocumento por diseño: los PDFs generados por FormRun
            // se guardan en storage bajo private/form_runs/{id} y se sirven desde ahí.

            return response()->stream(function() use ($output) {
                echo $output;
            }, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "inline; filename=\"form-{$run->id}.pdf\""
            ]);
        }
    }
}
