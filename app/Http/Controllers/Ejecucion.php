<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Flujo;
use App\Models\Etapa;
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

        // Query base - solo flujos activos para ejecución
        $query = Flujo::with(['empresa', 'tipo', 'etapas' => function($query) {
                $query->where('estado', 1)->orderBy('nro');
            }])
            ->where('estado', 1) // Solo flujos activos
            ->when(!$isSuper, fn($x) => $x->where('id_emp', $user->id_emp))
            ->when($q !== '', fn($x) => $x->where('nombre', 'like', "%{$q}%"))
            ->when($empresa_id !== '' && $isSuper, fn($x) => $x->where('id_emp', $empresa_id));

        // Aplicar filtros adicionales si es necesario
        $flujos = $query->orderBy('nombre')->paginate(12)->appends($request->query());

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

        // Empresas para filtro (solo para SUPERADMIN)
        $empresas = collect();
        if ($isSuper) {
            $empresas = Empresa::where('estado', 1)->orderBy('nombre')->get(['id', 'nombre']);
        }

        return view('superadmin.ejecucion.index', compact(
            'flujos', 'isSuper', 'estado', 'q', 'empresas', 'empresa_id'
        ));
    }

    /**
     * Show the execution interface for a specific flow.
     */
    public function show(Flujo $flujo)
    {
        $user = Auth::user();
        $isSuper = ($user->rol->nombre === 'SUPERADMIN');

        // Verificar permisos
        if (!$isSuper && $flujo->id_emp != $user->id_emp) {
            abort(403, 'No tienes permisos para ejecutar este flujo.');
        }

        // Verificar que el flujo esté activo
        if (!$flujo->estado) {
            abort(404, 'El flujo no está disponible para ejecución.');
        }

        // Cargar etapas con sus tareas y documentos activos
        $flujo->load([
            'etapas' => function($query) {
                $query->where('estado', 1)->orderBy('nro');
            },
            'etapas.tareas' => function($query) {
                $query->where('estado', 1);
            },
            'etapas.documentos' => function($query) {
                $query->where('estado', 1);
            }
        ]);

        return view('superadmin.ejecucion.show', compact('flujo', 'isSuper'));
    }

    /**
     * Show the execution process interface for a specific flow.
     */
    public function ejecutar(Flujo $flujo)
    {
        $user = Auth::user();
        $isSuper = ($user->rol->nombre === 'SUPERADMIN');

        // Verificar permisos
        if (!$isSuper && $flujo->id_emp != $user->id_emp) {
            abort(403, 'No tienes permisos para ejecutar este flujo.');
        }

        // Verificar que el flujo esté activo o en ejecución
        if (!$flujo->estado && $flujo->estado != 2) {
            abort(404, 'El flujo no está disponible para ejecución.');
        }

        // Cargar etapas con sus tareas y documentos activos o en ejecución
        $estadoACargar = ($flujo->estado == 2) ? [1, 2] : [1]; // Si está en ejecución, cargar estados 1 y 2
        
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

        // Verificar si el proceso ya está iniciado
        $procesoIniciado = ($flujo->estado == 2);
        
        // Si no está iniciado, verificar si hay alguna tarea completada o documento subido como fallback
        if (!$procesoIniciado) {
            foreach ($flujo->etapas as $etapa) {
                if ($etapa->tareas->contains('completada', true) || $etapa->documentos->contains('subido', true)) {
                    $procesoIniciado = true;
                    break;
                }
            }
        }
        
        $flujo->proceso_iniciado = $procesoIniciado;

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

            // Verificar permisos
            if (!$isSuper && $flujo->id_emp != $user->id_emp) {
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
            Log::info('Iniciando actualización de tarea', $request->all());
            
            $request->validate([
                'tarea_id' => 'required|exists:tareas,id',
                'completada' => 'required|boolean'
            ]);

            $user = Auth::user();
            $tareaId = $request->tarea_id;
            $completada = $request->completada;

            Log::info('Datos validados', ['tarea_id' => $tareaId, 'completada' => $completada, 'user_id' => $user->id]);

            // Buscar o crear el detalle de la tarea
            $detalle = DetalleTarea::updateOrCreate(
                ['id_tarea' => $tareaId],
                [
                    'estado' => $completada,
                    'id_user_create' => $user->id
                ]
            );

            Log::info('Detalle guardado', ['detalle_id' => $detalle->id, 'estado' => $detalle->estado]);

            return response()->json([
                'success' => true,
                'message' => $completada ? 'Tarea marcada como completada' : 'Tarea marcada como pendiente',
                'completada' => $detalle->estado,
                'detalle_id' => $detalle->id
            ]);

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
            $request->validate([
                'documento_id' => 'required|exists:documentos,id',
                'archivo' => 'required|file|mimes:pdf|max:10240', // 10MB máximo
                'comentarios' => 'nullable|string|max:500'
            ]);

            $user = Auth::user();
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
                'detalle_id' => $detalle->id
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

        // Verificar permisos
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
}
