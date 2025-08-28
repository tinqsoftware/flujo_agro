<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Flujo;
use App\Models\Etapa;
use App\Models\Tarea;
use App\Models\Documento;
use App\Models\Empresa;
use App\Models\DetalleTarea;
use App\Models\DetalleDocumento;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class Ejecucion extends Controller
{
    /**
     * Display a listing of available flows for execution.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $isSuper = ($user->rol->nombre === 'SUPERADMIN');

        // Filtros
        $estado = $request->get('estado', 'todos');
        $q = trim((string)$request->get('q', ''));
        $empresa_id = $request->get('empresa_id', '');

        // Query base - solo flujos activos para ejecución (estado = 1)
        $query = Flujo::with(['empresa', 'tipo', 'etapas' => function($query) {
                $query->where('estado', 1)->orderBy('nro');
            }])
            ->where('estado', 1) // Solo flujos con estado 1 (activos/listos para ejecutar)
            ->when(!$isSuper, fn($x) => $x->where('id_emp', $user->id_emp))
            ->when($q !== '', fn($x) => $x->where('nombre', 'like', "%{$q}%"))
            ->when($empresa_id !== '' && $isSuper, fn($x) => $x->where('id_emp', $empresa_id));

        // Aplicar filtros adicionales si es necesario
        $flujos = $query->orderBy('nombre')->paginate(12)->appends($request->query());

        // Debug log para verificar qué flujos se están cargando
        Log::info('Flujos cargados en ejecución:', [
            'total' => $flujos->total(),
            'query_params' => $request->query(),
            'user_empresa' => $isSuper ? 'SUPERADMIN' : $user->id_emp,
            'flujos_encontrados' => $flujos->map(function($flujo) {
                return [
                    'id' => $flujo->id,
                    'nombre' => $flujo->nombre,
                    'estado' => $flujo->estado,
                    'etapas_count' => $flujo->etapas->count(),
                    'total_etapas' => $flujo->total_etapas ?? 0,
                    'tiene_etapas' => $flujo->etapas->count() > 0
                ];
            })->toArray()
        ]);

        // Contar etapas y documentos por flujo
        $flujosConContadores = $flujos->getCollection()->map(function($flujo) {
            $totalEtapas = $flujo->etapas->count();
            $totalDocumentos = $flujo->etapas->sum(function($etapa) {
                return $etapa->documentos()->where('estado', 1)->count();
            });
            
            $flujo->total_etapas = $totalEtapas;
            $flujo->total_documentos = $totalDocumentos;
            
            return $flujo;
        });

        $flujos->setCollection($flujosConContadores);

        // Obtener flujos en ejecución y terminados para mostrar en sección separada
        $flujosEnProceso = Flujo::with(['empresa', 'tipo', 'etapas' => function($query) {
                $query->whereIn('estado', [1, 2])->orderBy('nro');
            }])
            ->whereIn('estado', [2, 3]) // Estado 2 = En ejecución, Estado 3 = Terminado
            ->when(!$isSuper, fn($x) => $x->where('id_emp', $user->id_emp))
            ->orderBy('estado') // Primero los en ejecución (2), luego los terminados (3)
            ->orderBy('updated_at', 'desc')
            ->get();

        // Contar etapas y documentos por flujo en proceso
        $flujosEnProceso = $flujosEnProceso->map(function($flujo) {
            $totalEtapas = $flujo->etapas->count();
            $totalDocumentos = $flujo->etapas->sum(function($etapa) {
                return $etapa->documentos()->whereIn('estado', [1, 2])->count();
            });
            
            $flujo->total_etapas = $totalEtapas;
            $flujo->total_documentos = $totalDocumentos;
            
            return $flujo;
        });

        // Empresas para filtro (solo para SUPERADMIN)
        $empresas = collect();
        if ($isSuper) {
            $empresas = Empresa::where('estado', 1)->orderBy('nombre')->get(['id', 'nombre']);
        }

        return view('superadmin.ejecucion.index', compact(
            'flujos', 'flujosEnProceso', 'isSuper', 'estado', 'q', 'empresas', 'empresa_id'
        ));
    }

    /**
     * Show the execution interface for a specific flow.
     */
    public function show(Flujo $flujo)
    {
        $user = Auth::user();
        $isSuper = ($user->rol->nombre === 'SUPERADMIN');

        // Debug: Verificar que el flujo llegue correctamente
        Log::info('Show Flujo Debug', [
            'flujo_id' => $flujo->id,
            'flujo_nombre' => $flujo->nombre,
            'flujo_estado' => $flujo->estado,
            'user_id' => $user->id,
            'is_super' => $isSuper
        ]);

        // Verificar permisos
        if (!$isSuper && $flujo->id_emp != $user->id_emp) {
            abort(403, 'No tienes permisos para ver este flujo.');
        }

        // Cargar relaciones siempre, sin filtros de estado para debug
        $flujo->load([
            'tipo',
            'empresa', 
            'etapas' => function($query) {
                $query->orderBy('nro');
            },
            'etapas.tareas',
            'etapas.documentos'
        ]);

        Log::info('Flujo Loaded Debug', [
            'etapas_count' => $flujo->etapas->count(),
            'etapas_data' => $flujo->etapas->map(function($etapa) {
                return [
                    'id' => $etapa->id,
                    'nombre' => $etapa->nombre,
                    'nro' => $etapa->nro,
                    'estado' => $etapa->estado,
                    'tareas_count' => $etapa->tareas->count(),
                    'documentos_count' => $etapa->documentos->count()
                ];
            })
        ]);

        // Si el flujo está en ejecución o completado, cargar detalles
        if ($flujo->estado >= 2) {
            foreach ($flujo->etapas as $etapa) {
                foreach ($etapa->tareas as $tarea) {
                    $detalle = DetalleTarea::where('id_tarea', $tarea->id)->first();
                    $tarea->completada = $detalle ? (bool)$detalle->estado : false;
                    $tarea->detalle_id = $detalle ? $detalle->id : null;
                }
                foreach ($etapa->documentos as $documento) {
                    $detalle = DetalleDocumento::where('id_documento', $documento->id)->first();
                    $documento->subido = $detalle ? (bool)$detalle->estado : false;
                    $documento->archivo_url = ($detalle && $detalle->ruta_doc) ? Storage::url($detalle->ruta_doc) : null;
                    $documento->detalle_id = $detalle ? $detalle->id : null;
                }
            }
        }

        return view('superadmin.ejecucion.show', compact('flujo', 'isSuper'));
    }

    /**
     * Show the execution process interface for a specific flow.
     */
    public function ejecutar(Flujo $flujo)
    {
        $user = Auth::user();
        $isSuper = ($user->rol->nombre === 'SUPERADMIN');

        // SUPERADMIN no puede ejecutar flujos, solo visualizarlos
        if ($isSuper) {
            return redirect()->route('ejecucion.show', $flujo)
                ->with('warning', 'Como SUPERADMIN solo puedes visualizar los flujos. La ejecución debe ser realizada por usuarios de la empresa.');
        }

        // Verificar permisos de empresa
        if ($flujo->id_emp != $user->id_emp) {
            abort(403, 'No tienes permisos para ejecutar este flujo.');
        }

        // Verificar que el flujo esté activo (estado 1) o en ejecución (estado 2)
        if ($flujo->estado != 1 && $flujo->estado != 2) {
            abort(404, 'El flujo no está disponible para ejecución.');
        }

        // Si el flujo está en estado 1 (listo), cambiarlo a estado 2 (en ejecución)
        if ($flujo->estado == 1) {
            Log::info('Iniciando proceso de ejecución automáticamente', [
                'flujo_id' => $flujo->id,
                'flujo_nombre' => $flujo->nombre,
                'user_id' => $user->id
            ]);

            // Cambiar estado del flujo a 2 (en ejecución)
            $flujo->update(['estado' => 2]);

            // Cambiar estado de todas las etapas activas a 2
            $flujo->etapas()->where('estado', 1)->update(['estado' => 2]);

            // Cambiar estado de todas las tareas y documentos activos a 2
            foreach ($flujo->etapas as $etapa) {
                $etapa->tareas()->where('estado', 1)->update(['estado' => 2]);
                $etapa->documentos()->where('estado', 1)->update(['estado' => 2]);
            }

            // Recargar el flujo para obtener los datos actualizados
            $flujo->refresh();
        }

        // Cargar etapas con sus tareas y documentos activos o en ejecución
        $estadoACargar = [1, 2]; // Cargar tanto estados activos como en ejecución
        
        $flujo->load([
            'etapas' => function($query) use ($estadoACargar) {
                $query->whereIn('estado', $estadoACargar)->orderBy('nro');
            },
            'etapas.tareas' => function($query) use ($estadoACargar) {
                $query->whereIn('estado', $estadoACargar);
            },
            'etapas.documentos' => function($query) use ($estadoACargar) {
                $query->whereIn('estado', $estadoACargar);
            }
        ]);

        // Cargar detalles de tareas y documentos existentes
        foreach ($flujo->etapas as $etapa) {
            foreach ($etapa->tareas as $tarea) {
                $detalle = DetalleTarea::where('id_tarea', $tarea->id)->first();
                $tarea->completada = $detalle ? (bool)$detalle->estado : false;
                $tarea->detalle_id = $detalle ? $detalle->id : null;
            }
            foreach ($etapa->documentos as $documento) {
                $detalle = DetalleDocumento::where('id_documento', $documento->id)->first();
                $documento->subido = $detalle ? (bool)$detalle->estado : false;
                $documento->archivo_url = ($detalle && $detalle->ruta_doc) ? Storage::url($detalle->ruta_doc) : null;
                $documento->detalle_id = $detalle ? $detalle->id : null;
            }
        }

        // El proceso está iniciado porque acabamos de cambiarlo a estado 2 o ya estaba en estado 2
        $flujo->proceso_iniciado = true;

        return view('superadmin.ejecucion.procesos.ejecutar', compact('flujo', 'isSuper'));
    }

    /**
     * Iniciar el proceso de ejecución de un flujo
     */
    public function iniciarProceso(Request $request, Flujo $flujo)
    {
        try {
            $user = Auth::user();
            $isSuper = ($user->rol->nombre === 'SUPERADMIN');

            // SUPERADMIN no puede ejecutar procesos
            if ($isSuper) {
                return response()->json(['error' => 'Los SUPERADMIN no pueden ejecutar flujos'], 403);
            }

            // Verificar permisos de empresa
            if ($flujo->id_emp != $user->id_emp) {
                return response()->json(['error' => 'No autorizado'], 403);
            }

            // Verificar que el flujo no esté ya en estado de ejecución
            if ($flujo->estado == 2) {
                return response()->json([
                    'success' => true,
                    'message' => 'El proceso ya está en ejecución'
                ]);
            }

            // Cambiar estado del flujo a 2 (en ejecución)
            $flujo->update(['estado' => 2]);

            // Cambiar estado de todas las etapas activas a 2
            $flujo->etapas()->where('estado', 1)->update(['estado' => 2]);

            // Cambiar estado de todas las tareas activas a 2
            foreach ($flujo->etapas as $etapa) {
                $etapa->tareas()->where('estado', 1)->update(['estado' => 2]);
                $etapa->documentos()->where('estado', 1)->update(['estado' => 2]);
            }

            Log::info('Proceso de flujo iniciado', [
                'flujo_id' => $flujo->id,
                'user_id' => $user->id,
                'flujo_nombre' => $flujo->nombre
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Proceso de ejecución iniciado correctamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al iniciar proceso de flujo: ' . $e->getMessage(), [
                'flujo_id' => $flujo->id,
                'user_id' => $user->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al iniciar el proceso: ' . $e->getMessage()
            ], 500);
        }
    }



    

    /**
     * Actualizar el estado de una tarea
     */
    public function actualizarTarea(Request $request)
    {
        try {
            $user = Auth::user();
            $isSuper = ($user->rol->nombre === 'SUPERADMIN');

            // SUPERADMIN no puede actualizar tareas
            if ($isSuper) {
                return response()->json(['error' => 'Los SUPERADMIN no pueden modificar tareas'], 403);
            }

            Log::info('Iniciando actualización de tarea', $request->all());
            
            $request->validate([
                'tarea_id' => 'required|exists:tareas,id',
                'completada' => 'required|boolean'
            ]);
            $tareaId = $request->tarea_id;
            $completada = $request->completada;

            Log::info('Datos validados', ['tarea_id' => $tareaId, 'completada' => $completada, 'user_id' => $user->id]);

            // Verificar si ya existe un detalle para esta tarea
            $detalle = DetalleTarea::where('id_tarea', $tareaId)->first();
            
            if ($detalle) {
                // Actualizar el existente
                $detalle->update([
                    'estado' => $completada,
                    'id_user_create' => $user->id
                ]);
                Log::info('Detalle actualizado', ['detalle_id' => $detalle->id, 'estado' => $detalle->estado]);
            } else {
                // Crear nuevo detalle
                $detalle = DetalleTarea::create([
                    'id_tarea' => $tareaId,
                    'estado' => $completada,
                    'id_user_create' => $user->id
                ]);
                Log::info('Detalle creado', ['detalle_id' => $detalle->id, 'estado' => $detalle->estado]);
            }

            return response()->json([
                'success' => true,
                'message' => $completada ? 'Tarea marcada como completada' : 'Tarea marcada como pendiente',
                'completada' => (bool)$detalle->estado,
                'detalle_id' => $detalle->id,
                'estados' => $this->verificarYActualizarEstados($tareaId)
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Error de validación en actualizar tarea: ' . $e->getMessage(), [
                'request' => $request->all(),
                'errors' => $e->errors()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error de validación: ' . implode(', ', collect($e->errors())->flatten()->toArray())
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error actualizando tarea: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la tarea: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Subir documento
     */
    public function subirDocumento(Request $request)
    {
        try {
            $user = Auth::user();
            $isSuper = ($user->rol->nombre === 'SUPERADMIN');

            // SUPERADMIN no puede subir documentos
            if ($isSuper) {
                return response()->json(['error' => 'Los SUPERADMIN no pueden subir documentos'], 403);
            }

            $request->validate([
                'documento_id' => 'required|exists:documentos,id',
                'archivo' => 'required|file|mimes:pdf|max:10240', // 10MB máximo
                'comentarios' => 'nullable|string|max:500'
            ]);
            $documentoId = $request->documento_id;
            $archivo = $request->file('archivo');

            // Crear directorio si no existe
            $directorio = 'documentos/ejecucion/' . date('Y/m');
            
            // Generar nombre único para el archivo
            $nombreArchivo = time() . '_' . $documentoId . '_' . $archivo->getClientOriginalName();
            
            // Guardar archivo
            $rutaArchivo = $archivo->storeAs($directorio, $nombreArchivo, 'public');

            // Buscar o crear el detalle del documento
            $detalle = DetalleDocumento::updateOrCreate(
                ['id_documento' => $documentoId],
                [
                    'estado' => true,
                    'ruta_doc' => $rutaArchivo,
                    'id_user_create' => $user->id
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Documento subido correctamente',
                'archivo_url' => Storage::url($rutaArchivo),
                'nombre_archivo' => $archivo->getClientOriginalName(),
                'detalle_id' => $detalle->id,
                'estados' => $this->verificarYActualizarEstados($documentoId, 'documento')
            ]);

        } catch (\Exception $e) {
            Log::error('Error subiendo documento: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al subir el documento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener el progreso de un flujo
     */
    public function obtenerProgreso(Flujo $flujo)
    {
        $user = Auth::user();
        $isSuper = ($user->rol->nombre === 'SUPERADMIN');

        // Verificar permisos - tanto SUPERADMIN como usuarios de empresa pueden ver progreso
        if (!$isSuper && $flujo->id_emp != $user->id_emp) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $flujo->load(['etapas.tareas', 'etapas.documentos']);
        
        $etapas = [];
        $totalItems = 0;
        $itemsCompletados = 0;

        foreach ($flujo->etapas as $etapa) {
            $tareasCompletadas = DetalleTarea::where('estado', true)
                ->whereIn('id_tarea', $etapa->tareas->pluck('id'))
                ->count();
            
            $documentosSubidos = DetalleDocumento::where('estado', true)
                ->whereIn('id_documento', $etapa->documentos->pluck('id'))
                ->count();
            
            $totalEtapa = $etapa->tareas->count() + $etapa->documentos->count();
            $completadosEtapa = $tareasCompletadas + $documentosSubidos;
            
            $progresoEtapa = $totalEtapa > 0 ? round(($completadosEtapa / $totalEtapa) * 100) : 0;
            
            $etapas[] = [
                'id' => $etapa->id,
                'progreso' => $progresoEtapa,
                'tareas_completadas' => $tareasCompletadas,
                'documentos_subidos' => $documentosSubidos,
                'total_tareas' => $etapa->tareas->count(),
                'total_documentos' => $etapa->documentos->count()
            ];
            
            $totalItems += $totalEtapa;
            $itemsCompletados += $completadosEtapa;
        }

        $progresoGeneral = $totalItems > 0 ? round(($itemsCompletados / $totalItems) * 100) : 0;

        return response()->json([
            'progreso_general' => $progresoGeneral,
            'etapas' => $etapas,
            'total_items' => $totalItems,
            'items_completados' => $itemsCompletados
        ]);
    }

    /**
     * Verificar y actualizar automáticamente los estados de etapas y flujos
     */
    private function verificarYActualizarEstados($itemId, $tipo = 'tarea')
    {
        try {
            // Obtener la etapa correspondiente según el tipo de ítem
            if ($tipo === 'tarea') {
                $tarea = \App\Models\Tarea::find($itemId);
                if (!$tarea) return false;
                $etapa = $tarea->etapa;
            } else { // documento
                $documento = \App\Models\Documento::find($itemId);
                if (!$documento) return false;
                $etapa = $documento->etapa;
            }

            if (!$etapa) return false;

            // Verificar si todas las tareas de la etapa están completadas
            $totalTareas = $etapa->tareas()->where('estado', '!=', 0)->count();
            $tareasCompletadas = DetalleTarea::where('estado', true)
                ->whereIn('id_tarea', $etapa->tareas()->where('estado', '!=', 0)->pluck('id'))
                ->count();

            // Verificar si todos los documentos de la etapa están subidos
            $totalDocumentos = $etapa->documentos()->where('estado', '!=', 0)->count();
            $documentosSubidos = DetalleDocumento::where('estado', true)
                ->whereIn('id_documento', $etapa->documentos()->where('estado', '!=', 0)->pluck('id'))
                ->count();

            Log::info("Verificando etapa {$etapa->id}", [
                'total_tareas' => $totalTareas,
                'tareas_completadas' => $tareasCompletadas,
                'total_documentos' => $totalDocumentos,
                'documentos_subidos' => $documentosSubidos
            ]);

            // Si todas las tareas y documentos están completados, marcar etapa como completada
            $etapaCompletada = false;
            if ($totalTareas === $tareasCompletadas && $totalDocumentos === $documentosSubidos && ($totalTareas > 0 || $totalDocumentos > 0)) {
                if ($etapa->estado != 3) {
                    $etapa->update(['estado' => 3]);
                    $etapaCompletada = true;
                    Log::info("Etapa {$etapa->id} marcada como completada");

                    // Verificar si todas las etapas del flujo están completadas
                    $flujo = $etapa->flujo;
                    $totalEtapas = $flujo->etapas()->where('estado', '!=', 0)->count();
                    $etapasCompletadas = $flujo->etapas()->where('estado', 3)->count();

                    Log::info("Verificando flujo {$flujo->id}", [
                        'total_etapas' => $totalEtapas,
                        'etapas_completadas' => $etapasCompletadas
                    ]);

                    // Si todas las etapas están completadas, marcar flujo como completado
                    if ($totalEtapas === $etapasCompletadas && $totalEtapas > 0) {
                        $flujo->update(['estado' => 3]);
                        Log::info("Flujo {$flujo->id} marcado como completado");
                        
                        // Retornar información especial para flujo completado
                        return [
                            'etapa_completada' => true,
                            'flujo_completado' => true,
                            'flujo_id' => $flujo->id,
                            'flujo_nombre' => $flujo->nombre
                        ];
                    }
                }
            }

            return $etapaCompletada;

        } catch (\Exception $e) {
            Log::error('Error en verificarYActualizarEstados: ' . $e->getMessage(), [
                'item_id' => $itemId,
                'tipo' => $tipo,
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
}
