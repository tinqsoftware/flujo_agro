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
use App\Models\DetalleFlujo;
use App\Models\DetalleEtapa;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

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

        // Query base - AHORA LISTAMOS TODOS LOS FLUJOS ACTIVOS (no filtramos por estado de ejecución)
        $query = Flujo::with(['empresa', 'tipo', 'etapas' => function($query) {
                $query->where('estado', 1)->orderBy('nro');
            }])
            ->where('estado', 1) // Solo flujos configurados y activos
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

        // Obtener ejecuciones activas usando la tabla detalle_flujo
        $ejecucionesActivas = collect();
        if (!$isSuper) {
            $ejecucionesActivas = DetalleFlujo::with(['flujo.empresa', 'flujo.tipo', 'flujo.etapas'])
                ->where('id_emp', $user->id_emp)
                ->whereIn('estado', [2, 3]) // 2 = En ejecución, 3 = Terminado
                ->orderBy('estado') // Primero en ejecución, luego terminados
                ->orderBy('updated_at', 'desc')
                ->get();
        } else {
            // Para SUPERADMIN, mostrar todas las ejecuciones
            $ejecucionesActivas = DetalleFlujo::with(['flujo.empresa', 'flujo.tipo', 'flujo.etapas'])
                ->whereIn('estado', [2, 3])
                ->orderBy('estado')
                ->orderBy('updated_at', 'desc')
                ->get();
        }

        // Agregar información de progreso a las ejecuciones
        $ejecucionesActivas = $ejecucionesActivas->map(function($detalleFlujo) {
            if ($detalleFlujo->flujo) {
                $flujo = $detalleFlujo->flujo;
                $totalEtapas = $flujo->etapas->count();
                $totalDocumentos = $flujo->etapas->sum(function($etapa) {
                    return $etapa->documentos()->where('estado', 1)->count();
                });
                
                $flujo->total_etapas = $totalEtapas;
                $flujo->total_documentos = $totalDocumentos;
                $flujo->estado_ejecucion = $detalleFlujo->estado;
                $flujo->detalle_flujo_id = $detalleFlujo->id;
                $flujo->fecha_ejecucion = $detalleFlujo->updated_at;
            }
            return $detalleFlujo;
        });

        // Empresas para filtro (solo para SUPERADMIN)
        $empresas = collect();
        if ($isSuper) {
            $empresas = Empresa::where('estado', 1)->orderBy('nombre')->get(['id', 'nombre']);
        }

        return view('superadmin.ejecucion.index', compact(
            'flujos', 'ejecucionesActivas', 'isSuper', 'estado', 'q', 'empresas', 'empresa_id'
        ));
    }

    /**
     * Show the execution interface for a specific flow.
     */
    public function show($id)
    {
        try {
            $user = Auth::user();
            $isSuper = ($user->rol->nombre === 'SUPERADMIN');
            $isAdmin = ($user->rol->nombre === 'ADMINISTRADOR');
            $isAdministrativo = ($user->rol->nombre === 'ADMINISTRATIVO');

            Log::info('Show method called', [
                'id_parameter' => $id,
                'user_id' => $user->id,
                'user_rol' => $user->rol->nombre,
                'is_super' => $isSuper,
                'is_admin' => $isAdmin,
                'is_administrativo' => $isAdministrativo
            ]);

            // Cargar el flujo manualmente con todas sus relaciones
            $flujo = Flujo::with([
                'tipo',
                'empresa', 
                'etapas' => function($query) {
                    $query->orderBy('nro');
                },
                'etapas.tareas',
                'etapas.documentos'
            ])->findOrFail($id);

            // Cargar datos de progreso desde las tablas detalle
            foreach ($flujo->etapas as $etapa) {
                // Cargar tareas completadas
                foreach ($etapa->tareas as $tarea) {
                    $detalleTarea = DetalleTarea::where('id_tarea', $tarea->id)->first();
                    $tarea->completada = $detalleTarea ? $detalleTarea->estado : false;
                    $tarea->detalle = $detalleTarea;
                }
                
                // Cargar documentos subidos
                foreach ($etapa->documentos as $documento) {
                    $detalleDocumento = DetalleDocumento::where('id_documento', $documento->id)->first();
                    $documento->subido = $detalleDocumento ? $detalleDocumento->estado : false;
                    $documento->detalle = $detalleDocumento;
                    // Si hay archivo subido, generar URL
                    if ($detalleDocumento && $detalleDocumento->ruta_doc) {
                        $documento->url_archivo = Storage::url($detalleDocumento->ruta_doc);
                    }
                }
            }

            Log::info('Flujo loaded successfully', [
                'flujo_id' => $flujo->id,
                'flujo_nombre' => $flujo->nombre,
                'flujo_estado' => $flujo->estado,
                'flujo_empresa_id' => $flujo->id_emp,
                'user_empresa_id' => $user->id_emp,
                'etapas_count' => $flujo->etapas->count(),
                'relations_loaded' => $flujo->relationLoaded('etapas')
            ]);

            // Verificar permisos:
            // - SUPERADMIN puede ver todo
            // - ADMINISTRADOR y ADMINISTRATIVO solo de su empresa
            if (!$isSuper && $flujo->id_emp != $user->id_emp) {
                Log::warning('Access denied - different empresa', [
                    'user_id' => $user->id,
                    'user_role' => $user->rol->nombre,
                    'user_empresa' => $user->id_emp,
                    'flujo_empresa' => $flujo->id_emp
                ]);
                abort(403, 'No tienes permisos para ver este flujo.');
            }

            Log::info('Access granted - sending to view', [
                'flujo_id' => $flujo->id,
                'isSuper' => $isSuper,
                'user_role' => $user->rol->nombre,
                'view_data_flujo_id' => $flujo->id
            ]);

            // Pasar información del rol para la vista
            $userRole = $user->rol->nombre;

            return view('superadmin.ejecucion.show', compact('flujo', 'isSuper', 'userRole'));

        } catch (\Exception $e) {
            Log::error('Error in show method', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Show the configuration modal for a specific flow execution.
     */
    public function configurar(Flujo $flujo)
    {
        try {
            $user = Auth::user();
            $isSuper = ($user->rol->nombre === 'SUPERADMIN');

            // SUPERADMIN no puede ejecutar flujos, solo visualizarlos
            if ($isSuper) {
                return response()->json(['error' => 'SUPERADMIN no puede ejecutar flujos'], 403);
            }

            // Verificar permisos de empresa
            if ($flujo->id_emp != $user->id_emp) {
                return response()->json(['error' => 'Sin permisos para este flujo'], 403);
            }

            // Verificar que el flujo esté configurado (estado 1)
            if ($flujo->estado != 1) {
                return response()->json(['error' => 'El flujo no está disponible para ejecución'], 404);
            }

            // Cargar etapas con sus tareas y documentos para mostrar en la configuración
            $flujo->load([
                'etapas' => function($query) {
                    $query->where('estado', 1)->orderBy('nro');
                },
                'etapas.tareas' => function($query) {
                    $query->where('estado', 1)->orderBy('nombre');
                },
                'etapas.documentos' => function($query) {
                    $query->where('estado', 1)->orderBy('nombre');
                }
            ]);

            Log::info('Configuración de flujo cargada', [
                'flujo_id' => $flujo->id,
                'etapas_count' => $flujo->etapas->count(),
                'total_tareas' => $flujo->etapas->sum(function($etapa) { return $etapa->tareas->count(); }),
                'total_documentos' => $flujo->etapas->sum(function($etapa) { return $etapa->documentos->count(); })
            ]);

            // Retornar JSON para el modal
            return response()->json([
                'success' => true,
                'flujo' => [
                    'id' => $flujo->id,
                    'nombre' => $flujo->nombre,
                    'descripcion' => $flujo->descripcion,
                    'etapas' => $flujo->etapas->map(function($etapa) {
                        return [
                            'id' => $etapa->id,
                            'nombre' => $etapa->nombre,
                            'nro' => $etapa->nro,
                            'tareas' => $etapa->tareas->map(function($tarea) {
                                return [
                                    'id' => $tarea->id,
                                    'nombre' => $tarea->nombre,
                                    'descripcion' => $tarea->descripcion
                                ];
                            }),
                            'documentos' => $etapa->documentos->map(function($documento) {
                                return [
                                    'id' => $documento->id,
                                    'nombre' => $documento->nombre,
                                    'descripcion' => $documento->descripcion
                                ];
                            })
                        ];
                    })
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error al cargar configuración de flujo', [
                'flujo_id' => $flujo->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    }

    /**
     * Create a new execution with configuration.
     */
    public function crearEjecucion(Request $request, Flujo $flujo)
    {
        $user = Auth::user();
        $isSuper = ($user->rol->nombre === 'SUPERADMIN');

        // SUPERADMIN no puede ejecutar flujos
        if ($isSuper) {
            return response()->json(['error' => 'SUPERADMIN no puede ejecutar flujos'], 403);
        }

        // Verificar permisos de empresa
        if ($flujo->id_emp != $user->id_emp) {
            return response()->json(['error' => 'Sin permisos para este flujo'], 403);
        }

        // Validar datos
        $request->validate([
            'nombre' => 'required|string|max:255',
            'tareas_seleccionadas' => 'array',
            'documentos_seleccionados' => 'array'
        ]);

        try {
            DB::beginTransaction();

            // Crear nuevo registro de ejecución con nombre personalizado
            $detalleFlujoActivo = DetalleFlujo::create([
                'nombre' => $request->nombre,
                'id_flujo' => $flujo->id,
                'id_emp' => $user->id_emp,
                'id_user_create' => $user->id,
                'estado' => 2 // En ejecución
            ]);

            Log::info('Nueva ejecución creada con configuración personalizada', [
                'detalle_flujo_id' => $detalleFlujoActivo->id,
                'nombre_personalizado' => $request->nombre,
                'flujo_original_id' => $flujo->id,
                'tareas_seleccionadas' => count($request->tareas_seleccionadas ?? []),
                'documentos_seleccionados' => count($request->documentos_seleccionados ?? [])
            ]);

            // Crear registros de detalle_etapa para cada etapa del flujo
            foreach ($flujo->etapas()->where('estado', 1)->get() as $etapa) {
                DetalleEtapa::create([
                    'id_etapa' => $etapa->id,
                    'id_detalle_flujo' => $detalleFlujoActivo->id,
                    'estado' => 2 // En ejecución
                ]);
            }

            // Crear registros de detalle_tarea solo para las tareas seleccionadas
            if (!empty($request->tareas_seleccionadas)) {
                foreach ($request->tareas_seleccionadas as $tareaId) {
                    DetalleTarea::create([
                        'id_tarea' => $tareaId,
                        'id_detalle_flujo' => $detalleFlujoActivo->id,
                        'estado' => 2 // En ejecución, pendiente de completar
                    ]);
                }
            }

            // Crear registros de detalle_documento solo para los documentos seleccionados
            if (!empty($request->documentos_seleccionados)) {
                foreach ($request->documentos_seleccionados as $documentoId) {
                    DetalleDocumento::create([
                        'id_documento' => $documentoId,
                        'id_detalle_flujo' => $detalleFlujoActivo->id,
                        'estado' => 2 // Pendiente de subir
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'redirect_url' => "/ejecucion/{$flujo->id}/ejecutar",
                'detalle_flujo_id' => $detalleFlujoActivo->id,
                'mensaje' => 'Ejecución configurada y creada exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error al crear ejecución configurada', [
                'error' => $e->getMessage(),
                'flujo_id' => $flujo->id,
                'user_id' => $user->id
            ]);

            return response()->json(['error' => 'Error al crear la ejecución'], 500);
        }
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

        // Verificar que el flujo esté configurado (estado 1)
        if ($flujo->estado != 1) {
            abort(404, 'El flujo no está disponible para ejecución.');
        }

        // Buscar ejecución activa más reciente para este usuario/flujo
        $detalleFlujoActivo = DetalleFlujo::where('id_flujo', $flujo->id)
            ->where('id_emp', $user->id_emp)
            ->where('estado', 2) // En ejecución
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$detalleFlujoActivo) {
            // Si no hay ejecución activa, redirigir al index para crear una nueva
            return redirect()->route('ejecucion.index')
                ->with('info', 'No hay ejecución activa de este flujo. Crea una nueva ejecución desde el selector.');
        }

        Log::info('Continuando ejecución existente', [
            'detalle_flujo_id' => $detalleFlujoActivo->id,
            'flujo_id' => $flujo->id,
            'nombre_ejecucion' => $detalleFlujoActivo->nombre
        ]);

        // Crear registros de detalle_etapa para cada etapa del flujo
        foreach ($flujo->etapas()->where('estado', 1)->get() as $etapa) {
            DetalleEtapa::create([
                'id_etapa' => $etapa->id,
                'id_detalle_flujo' => $detalleFlujoActivo->id,
                'estado' => 2 // En ejecución
            ]);
        }

        Log::info('Registros de detalle_etapa creados para nueva ejecución', [
            'detalle_flujo_id' => $detalleFlujoActivo->id,
            'total_etapas_creadas' => $flujo->etapas()->where('estado', 1)->count()
        ]);

        // Cargar etapas con sus tareas y documentos
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

            // Cargar estados de tareas y documentos para esta ejecución específica
            foreach ($flujo->etapas as $etapa) {
                foreach ($etapa->tareas as $tarea) {
                    // Buscar detalle de tarea vinculado a esta ejecución específica
                    $detalle = DetalleTarea::where('id_tarea', $tarea->id)
                        ->whereHas('tarea.etapa.detalleEtapas', function($query) use ($detalleFlujoActivo) {
                            $query->where('id_detalle_flujo', $detalleFlujoActivo->id);
                        })
                        ->first();
                    $tarea->completada = $detalle ? (bool)$detalle->estado : false;
                    $tarea->detalle_id = $detalle ? $detalle->id : null;
                }
                
                foreach ($etapa->documentos as $documento) {
                    // Buscar detalle de documento vinculado a esta ejecución específica
                    $detalle = DetalleDocumento::where('id_documento', $documento->id)
                        ->whereHas('documento.etapa.detalleEtapas', function($query) use ($detalleFlujoActivo) {
                            $query->where('id_detalle_flujo', $detalleFlujoActivo->id);
                        })
                        ->first();
                    $documento->subido = $detalle ? (bool)$detalle->estado : false;
                    $documento->archivo_url = ($detalle && $detalle->ruta_doc) ? Storage::url($detalle->ruta_doc) : null;
                    $documento->detalle_id = $detalle ? $detalle->id : null;
                }
            }        // Agregar información de la ejecución al flujo
        $flujo->proceso_iniciado = true;
        $flujo->detalle_flujo_id = $detalleFlujoActivo->id;
        $flujo->estado_ejecucion = $detalleFlujoActivo->estado;

        return view('superadmin.ejecucion.procesos.ejecutar', compact('flujo', 'isSuper'));
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
                'completada' => 'required|boolean',
                'detalle_flujo_id' => 'required|exists:detalle_flujo,id'
            ]);
            
            $tareaId = $request->tarea_id;
            $completada = $request->completada;
            $detalleFlujoId = $request->detalle_flujo_id;

            Log::info('Datos validados', [
                'tarea_id' => $tareaId, 
                'completada' => $completada, 
                'detalle_flujo_id' => $detalleFlujoId,
                'user_id' => $user->id
            ]);

            // Verificar que el detalle_flujo pertenece a la empresa del usuario
            $detalleFlujo = DetalleFlujo::where('id', $detalleFlujoId)
                ->where('id_emp', $user->id_emp)
                ->firstOrFail();

            // Buscar si ya existe un detalle para esta tarea en esta ejecución específica
            $detalle = DetalleTarea::where('id_tarea', $tareaId)
                ->whereHas('tarea.etapa.detalleEtapas', function($query) use ($detalleFlujoId) {
                    $query->where('id_detalle_flujo', $detalleFlujoId);
                })
                ->first();
            
            if ($detalle) {
                // Actualizar el existente
                $detalle->update([
                    'estado' => $completada,
                    'id_user_create' => $user->id
                ]);
                Log::info('Detalle actualizado', ['detalle_id' => $detalle->id, 'estado' => $detalle->estado]);
            } else {
                // Crear nuevo detalle para esta ejecución específica
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
                'estados' => $this->verificarYActualizarEstados($tareaId, 'tarea', $detalleFlujoId)
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
                'comentarios' => 'nullable|string|max:500',
                'detalle_flujo_id' => 'required|exists:detalle_flujo,id'
            ]);
            
            $documentoId = $request->documento_id;
            $archivo = $request->file('archivo');
            $detalleFlujoId = $request->detalle_flujo_id;

            // Verificar que el detalle_flujo pertenece a la empresa del usuario
            $detalleFlujo = DetalleFlujo::where('id', $detalleFlujoId)
                ->where('id_emp', $user->id_emp)
                ->firstOrFail();

            // Crear directorio si no existe
            $directorio = 'documentos/ejecucion/' . $detalleFlujoId . '/' . date('Y/m');
            
            // Generar nombre único para el archivo
            $nombreArchivo = time() . '_' . $documentoId . '_' . $archivo->getClientOriginalName();
            
            // Guardar archivo
            $rutaArchivo = $archivo->storeAs($directorio, $nombreArchivo, 'public');

            // Buscar si ya existe un detalle para este documento en esta ejecución específica
            $detalle = DetalleDocumento::where('id_documento', $documentoId)
                ->whereHas('documento.etapa.detalleEtapas', function($query) use ($detalleFlujoId) {
                    $query->where('id_detalle_flujo', $detalleFlujoId);
                })
                ->first();

            if ($detalle) {
                // Actualizar el existente
                $detalle->update([
                    'estado' => true,
                    'ruta_doc' => $rutaArchivo,
                    'id_user_create' => $user->id
                ]);
            } else {
                // Crear nuevo detalle para esta ejecución específica
                $detalle = DetalleDocumento::create([
                    'id_documento' => $documentoId,
                    'estado' => true,
                    'ruta_doc' => $rutaArchivo,
                    'id_user_create' => $user->id
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Documento subido correctamente',
                'archivo_url' => Storage::url($rutaArchivo),
                'nombre_archivo' => $archivo->getClientOriginalName(),
                'detalle_id' => $detalle->id,
                'estados' => $this->verificarYActualizarEstados($documentoId, 'documento', $detalleFlujoId)
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
     * Verificar y actualizar automáticamente los estados de etapas y flujos para una ejecución específica
     */
    private function verificarYActualizarEstados($itemId, $tipo = 'tarea', $detalleFlujoId = null)
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

            if (!$etapa || !$detalleFlujoId) return false;

            // Buscar el detalle_etapa para esta ejecución específica
            $detalleEtapa = DetalleEtapa::where('id_etapa', $etapa->id)
                ->where('id_detalle_flujo', $detalleFlujoId)
                ->first();

            if (!$detalleEtapa) return false;

            // Verificar si todas las tareas de la etapa están completadas para esta ejecución
            $totalTareas = $etapa->tareas()->where('estado', 1)->count();
            $tareasCompletadas = DetalleTarea::where('estado', true)
                ->whereIn('id_tarea', $etapa->tareas()->where('estado', 1)->pluck('id'))
                ->whereHas('tarea.etapa.detalleEtapas', function($query) use ($detalleFlujoId) {
                    $query->where('id_detalle_flujo', $detalleFlujoId);
                })
                ->count();

            // Verificar si todos los documentos de la etapa están subidos para esta ejecución
            $totalDocumentos = $etapa->documentos()->where('estado', 1)->count();
            $documentosSubidos = DetalleDocumento::where('estado', true)
                ->whereIn('id_documento', $etapa->documentos()->where('estado', 1)->pluck('id'))
                ->whereHas('documento.etapa.detalleEtapas', function($query) use ($detalleFlujoId) {
                    $query->where('id_detalle_flujo', $detalleFlujoId);
                })
                ->count();

            Log::info("Verificando etapa {$etapa->id} para ejecución {$detalleFlujoId}", [
                'total_tareas' => $totalTareas,
                'tareas_completadas' => $tareasCompletadas,
                'total_documentos' => $totalDocumentos,
                'documentos_subidos' => $documentosSubidos
            ]);

            // Si todas las tareas y documentos están completados, marcar etapa como completada
            $etapaCompletada = false;
            if ($totalTareas === $tareasCompletadas && $totalDocumentos === $documentosSubidos && ($totalTareas > 0 || $totalDocumentos > 0)) {
                if ($detalleEtapa->estado != 3) {
                    $detalleEtapa->update(['estado' => 3]);
                    $etapaCompletada = true;
                    Log::info("DetalleEtapa {$detalleEtapa->id} marcado como completado para ejecución {$detalleFlujoId}");

                    // Verificar si todas las etapas de esta ejecución están completadas
                    $detalleFlujo = DetalleFlujo::find($detalleFlujoId);
                    if ($detalleFlujo) {
                        $totalEtapasEjecucion = DetalleEtapa::where('id_detalle_flujo', $detalleFlujoId)->count();
                        $etapasCompletadasEjecucion = DetalleEtapa::where('id_detalle_flujo', $detalleFlujoId)
                            ->where('estado', 3)->count();

                        Log::info("Verificando ejecución {$detalleFlujoId}", [
                            'total_etapas' => $totalEtapasEjecucion,
                            'etapas_completadas' => $etapasCompletadasEjecucion
                        ]);

                        // Si todas las etapas están completadas, marcar ejecución como completada
                        if ($totalEtapasEjecucion === $etapasCompletadasEjecucion && $totalEtapasEjecucion > 0) {
                            $detalleFlujo->update(['estado' => 3]);
                            Log::info("Ejecución {$detalleFlujoId} marcada como completada");
                            
                            // Retornar información especial para ejecución completada
                            return [
                                'etapa_completada' => true,
                                'ejecucion_completada' => true,
                                'detalle_flujo_id' => $detalleFlujoId,
                                'flujo_id' => $detalleFlujo->id_flujo,
                                'flujo_nombre' => $detalleFlujo->flujo->nombre ?? 'Flujo desconocido'
                            ];
                        }
                    }
                }
            }

            return $etapaCompletada;

        } catch (\Exception $e) {
            Log::error('Error en verificarYActualizarEstados: ' . $e->getMessage(), [
                'item_id' => $itemId,
                'tipo' => $tipo,
                'detalle_flujo_id' => $detalleFlujoId,
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Get progress information for a flow via AJAX
     */
    public function progreso(Flujo $flujo)
    {
        $user = Auth::user();
        
        // Verificar permisos básicos
        if ($user->rol->nombre !== 'SUPERADMIN' && $flujo->id_emp != $user->id_emp) {
            abort(403, 'No tienes permisos para ver este flujo.');
        }

        // Cargar el flujo con todas las relaciones necesarias
        $flujo->load([
            'etapas.tareas', 
            'etapas.documentos'
        ]);

        $progreso_general = 0;
        $total_items = 0;
        $items_completados = 0;
        $etapas_data = [];

        foreach ($flujo->etapas as $etapa) {
            $tareas_completadas = 0;
            $total_tareas = $etapa->tareas->count();
            
            foreach ($etapa->tareas as $tarea) {
                $detalle = DetalleTarea::where('id_tarea', $tarea->id)->first();
                if ($detalle && $detalle->estado) {
                    $tareas_completadas++;
                    $items_completados++;
                }
                $total_items++;
            }

            $documentos_subidos = 0;
            $total_documentos = $etapa->documentos->count();
            
            foreach ($etapa->documentos as $documento) {
                $detalle = DetalleDocumento::where('id_documento', $documento->id)->first();
                if ($detalle && $detalle->estado) {
                    $documentos_subidos++;
                    $items_completados++;
                }
                $total_items++;
            }

            $progreso_etapa = 0;
            if (($total_tareas + $total_documentos) > 0) {
                $progreso_etapa = round((($tareas_completadas + $documentos_subidos) / ($total_tareas + $total_documentos)) * 100);
            }

            $etapas_data[] = [
                'id' => $etapa->id,
                'nombre' => $etapa->nombre,
                'progreso' => $progreso_etapa,
                'tareas_completadas' => $tareas_completadas,
                'total_tareas' => $total_tareas,
                'documentos_subidos' => $documentos_subidos,
                'total_documentos' => $total_documentos,
                'estado' => $etapa->estado
            ];
        }

        if ($total_items > 0) {
            $progreso_general = round(($items_completados / $total_items) * 100);
        }

        return response()->json([
            'progreso_general' => $progreso_general,
            'etapas' => $etapas_data,
            'flujo_estado' => $flujo->estado
        ]);
    }
}
