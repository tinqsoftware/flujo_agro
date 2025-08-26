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

        // Cargar detalles de tareas y documentos
        $user = Auth::user();
        foreach ($flujo->etapas as $etapa) {
            foreach ($etapa->tareas as $tarea) {
                $detalle = DetalleTarea::where('id_tarea', $tarea->id)->first();
                $tarea->completada = $detalle ? $detalle->estado : false;
            }
            foreach ($etapa->documentos as $documento) {
                $detalle = DetalleDocumento::where('id_documento', $documento->id)->first();
                $documento->subido = $detalle ? $detalle->estado : false;
                $documento->archivo_url = $detalle && $detalle->ruta_doc ? Storage::url($detalle->ruta_doc) : null;
            }
        }

        // Agregar campos virtuales para el seguimiento del proceso
        $flujo->proceso_iniciado = $flujo->etapas->flatMap->tareas->contains('completada', true) || 
                                  $flujo->etapas->flatMap->documentos->contains('subido', true);

        return view('superadmin.ejecucion.procesos.ejecutar', compact('flujo', 'isSuper'));
    }

    /**
     * Iniciar el proceso de ejecución de un flujo
     */
    public function iniciarProceso(Request $request, Flujo $flujo)
    {
        $user = Auth::user();
        $isSuper = ($user->rol->nombre === 'SUPERADMIN');

        // Verificar permisos
        if (!$isSuper && $flujo->id_emp != $user->id_emp) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        // Aquí podrías agregar lógica adicional para marcar el flujo como iniciado en la BD
        
        return response()->json([
            'success' => true,
            'message' => 'Proceso de ejecución iniciado correctamente'
        ]);
    }

    /**
     * Actualizar el estado de una tarea
     */
    public function actualizarTarea(Request $request)
    {
        $request->validate([
            'tarea_id' => 'required|exists:tarea,id',
            'completada' => 'required|boolean'
        ]);

        $user = Auth::user();
        $tareaId = $request->tarea_id;
        $completada = $request->completada;

        // Buscar o crear el detalle de la tarea
        $detalle = DetalleTarea::updateOrCreate(
            ['id_tarea' => $tareaId],
            [
                'estado' => $completada,
                'id_user_create' => $user->id
            ]
        );

        return response()->json([
            'success' => true,
            'message' => $completada ? 'Tarea marcada como completada' : 'Tarea marcada como pendiente',
            'completada' => $detalle->estado
        ]);
    }

    /**
     * Subir documento
     */
    public function subirDocumento(Request $request)
    {
        $request->validate([
            'documento_id' => 'required|exists:documento,id',
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
            'nombre_archivo' => $archivo->getClientOriginalName()
        ]);
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
