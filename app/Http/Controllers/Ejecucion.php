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
                ->whereIn('estado', [2, 3, 4, 99]) // 2 = En ejecución, 3 = Terminado, 4 = Pausado, 99 = Cancelado
                ->orderBy('estado') // Primero en ejecución, luego pausados, luego terminados, luego cancelados
                ->orderBy('updated_at', 'desc')
                ->get();
        } else {
            // Para SUPERADMIN, mostrar todas las ejecuciones
            $ejecucionesActivas = DetalleFlujo::with(['flujo.empresa', 'flujo.tipo', 'flujo.etapas'])
                ->whereIn('estado', [2, 3, 4, 99])
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
                // Cargar tareas completadas con información del usuario
                foreach ($etapa->tareas as $tarea) {
                    $detalleTarea = DetalleTarea::with('userCreate')->where('id_tarea', $tarea->id)->first();
                    $tarea->completada = $detalleTarea ? $detalleTarea->estado : false;
                    $tarea->detalle = $detalleTarea;
                }
                
                // Cargar documentos subidos con información del usuario
                foreach ($etapa->documentos as $documento) {
                    $detalleDocumento = DetalleDocumento::with('userCreate')->where('id_documento', $documento->id)->first();
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
     * Get flow preview information.
     */
    public function previsualizar(Flujo $flujo)
    {
        try {
            $user = Auth::user();
            $isSuper = ($user->rol->nombre === 'SUPERADMIN');

            // Verificar permisos de empresa (SUPERADMIN puede ver todos)
            if (!$isSuper && $flujo->id_emp != $user->id_emp) {
                return response()->json(['error' => 'Sin permisos para ver este flujo'], 403);
            }

            // Cargar el flujo con todas sus relaciones
            $flujo->load([
                'tipo',
                'empresa',
                'etapas' => function($query) {
                    $query->where('estado', 1)->orderBy('nro');
                },
                'etapas.tareas' => function($query) {
                    $query->where('estado', 1)->orderBy('id');
                },
                'etapas.documentos' => function($query) {
                    $query->where('estado', 1)->orderBy('id');
                }
            ]);

            return response()->json([
                'success' => true,
                'flujo' => [
                    'id' => $flujo->id,
                    'nombre' => $flujo->nombre,
                    'descripcion' => $flujo->descripcion,
                    'tipo' => $flujo->tipo ? [
                        'id' => $flujo->tipo->id,
                        'nombre' => $flujo->tipo->nombre
                    ] : null,
                    'empresa' => $flujo->empresa ? [
                        'id' => $flujo->empresa->id,
                        'nombre' => $flujo->empresa->nombre
                    ] : null,
                    'etapas' => $flujo->etapas->map(function($etapa) {
                        return [
                            'id' => $etapa->id,
                            'nombre' => $etapa->nombre,
                            'nro' => $etapa->nro,
                            'descripcion' => $etapa->descripcion,
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
            Log::error('Error al cargar previsualización de flujo', [
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
                    // Buscar la tarea para obtener su etapa
                    $tarea = \App\Models\Tarea::find($tareaId);
                    if ($tarea && $tarea->etapa) {
                        // Buscar el detalle_etapa correspondiente
                        $detalleEtapa = DetalleEtapa::where('id_etapa', $tarea->etapa->id)
                            ->where('id_detalle_flujo', $detalleFlujoActivo->id)
                            ->first();
                        
                        if ($detalleEtapa) {
                            DetalleTarea::create([
                                'id_tarea' => $tareaId,
                                'id_detalle_etapa' => $detalleEtapa->id,
                                'estado' => 0, // Inicial/inactivo
                                'id_user_create' => $user->id
                            ]);
                        }
                    }
                }
            }

            // Crear registros de detalle_documento solo para los documentos seleccionados
            if (!empty($request->documentos_seleccionados)) {
                foreach ($request->documentos_seleccionados as $documentoId) {
                    // Buscar el documento para obtener su etapa
                    $documento = \App\Models\Documento::find($documentoId);
                    if ($documento && $documento->etapa) {
                        // Buscar el detalle_etapa correspondiente
                        $detalleEtapa = DetalleEtapa::where('id_etapa', $documento->etapa->id)
                            ->where('id_detalle_flujo', $detalleFlujoActivo->id)
                            ->first();
                        
                        if ($detalleEtapa) {
                            DetalleDocumento::create([
                                'id_documento' => $documentoId,
                                'id_detalle_etapa' => $detalleEtapa->id,
                                'estado' => 0, // Inicial/inactivo
                                'id_user_create' => $user->id
                            ]);
                        }
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'redirect_url' => "/ejecucion/detalle/{$detalleFlujoActivo->id}/ejecutar",
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

        // Redirigir al método ejecutarDetalle para mantener consistencia
        return redirect()->route('ejecucion.detalle.ejecutar', $detalleFlujoActivo);
    }

    /**
     * Show the execution process interface for a specific DetalleFlujo (new method).
     */
    public function ejecutarDetalle(DetalleFlujo $detalleFlujo)
    {
        $user = Auth::user();
        $isSuper = ($user->rol->nombre === 'SUPERADMIN');

        // SUPERADMIN no puede ejecutar flujos, solo visualizarlos
        if ($isSuper) {
            return redirect()->route('ejecucion.show', $detalleFlujo->flujo)
                ->with('warning', 'Como SUPERADMIN solo puedes visualizar los flujos. La ejecución debe ser realizada por usuarios de la empresa.');
        }

        // Verificar permisos de empresa
        if ($detalleFlujo->id_emp != $user->id_emp) {
            abort(403, 'No tienes permisos para ejecutar esta ejecución.');
        }

        // Verificar que la ejecución esté activa (estado 2) o permita visualización
        if ($detalleFlujo->estado != 2) {
            // Para ejecuciones canceladas, mostrar vista de solo lectura
            if ($detalleFlujo->estado == 99) {
                return redirect()->route('ejecucion.index')
                    ->with('warning', 'Esta ejecución ha sido cancelada. Motivo: ' . 
                        ($detalleFlujo->motivo ?? 'No especificado'));
            }
            
            return redirect()->route('ejecucion.index')
                ->with('warning', 'Esta ejecución no está activa. Estado actual: ' . 
                    ($detalleFlujo->estado == 1 ? 'Creada' : ($detalleFlujo->estado == 3 ? 'Completada' : ($detalleFlujo->estado == 4 ? 'Pausada' : 'Desconocido'))));
        }

        Log::info('Accediendo a ejecución específica', [
            'detalle_flujo_id' => $detalleFlujo->id,
            'nombre_ejecucion' => $detalleFlujo->nombre,
            'flujo_id' => $detalleFlujo->id_flujo,
            'estado' => $detalleFlujo->estado
        ]);

        // Cargar el flujo con todas sus relaciones
        $flujo = $detalleFlujo->flujo()->with([
            'tipo',
            'empresa', 
            'etapas' => function($query) {
                $query->where('estado', 1)->orderBy('nro');
            },
            'etapas.tareas' => function($query) {
                $query->where('estado', 1);
            },
            'etapas.documentos' => function($query) {
                $query->where('estado', 1);
            }
        ])->first();

        if (!$flujo) {
            abort(404, 'Flujo no encontrado.');
        }

        // Cargar estados específicos de esta ejecución (DetalleFlujo)
        foreach ($flujo->etapas as $etapa) {
            // Buscar el DetalleEtapa correspondiente
            $detalleEtapa = DetalleEtapa::where('id_etapa', $etapa->id)
                ->where('id_detalle_flujo', $detalleFlujo->id)
                ->first();
            
            $etapa->detalle_etapa = $detalleEtapa;
            $etapa->estado_ejecucion = $detalleEtapa ? $detalleEtapa->estado : 1;

            foreach ($etapa->tareas as $tarea) {
                // Buscar DetalleTarea vinculado a esta ejecución específica a través del detalle_etapa
                $detalleTarea = null;
                if ($detalleEtapa) {
                    $detalleTarea = DetalleTarea::with('userCreate')->where('id_tarea', $tarea->id)
                        ->where('id_detalle_etapa', $detalleEtapa->id)
                        ->whereNotIn('estado', [99]) // Excluir tareas canceladas
                        ->first();
                }
                
                // Solo estado 3 significa completado (no estado 2 que es "en ejecución")
                $tarea->completada = $detalleTarea ? ($detalleTarea->estado == 3) : false;
                $tarea->detalle_id = $detalleTarea ? $detalleTarea->id : null;
                $tarea->detalle_flujo_id = $detalleFlujo->id;
                $tarea->detalle = $detalleTarea; // Agregar referencia al detalle completo
            }
            
            foreach ($etapa->documentos as $documento) {
                // Buscar DetalleDocumento vinculado a esta ejecución específica a través del detalle_etapa
                $detalleDocumento = null;
                if ($detalleEtapa) {
                    $detalleDocumento = DetalleDocumento::with('userCreate')->where('id_documento', $documento->id)
                        ->where('id_detalle_etapa', $detalleEtapa->id)
                        ->whereNotIn('estado', [99]) // Excluir documentos cancelados
                        ->first();
                }
                
                // Solo estado 3 significa subido/completado (no estado 2 que es "pendiente")
                $documento->subido = $detalleDocumento ? ($detalleDocumento->estado == 3) : false;
                $documento->archivo_url = ($detalleDocumento && $detalleDocumento->ruta_doc) ? 
                    Storage::url($detalleDocumento->ruta_doc) : null;
                $documento->detalle_id = $detalleDocumento ? $detalleDocumento->id : null;
                $documento->detalle_flujo_id = $detalleFlujo->id;
                $documento->detalle = $detalleDocumento; // Agregar referencia al detalle completo
            }
        }

        // Agregar información de la ejecución al flujo
        $flujo->proceso_iniciado = true;
        $flujo->detalle_flujo_id = $detalleFlujo->id;
        $flujo->detalle_flujo = $detalleFlujo;
        $flujo->nombre_ejecucion = $detalleFlujo->nombre;
        $flujo->estado_ejecucion = $detalleFlujo->estado;

        Log::info('Datos cargados para vista de ejecución', [
            'detalle_flujo_id' => $detalleFlujo->id,
            'etapas_count' => $flujo->etapas->count(),
            'total_tareas_activas' => $flujo->etapas->sum(function($etapa) { 
                return $etapa->tareas->count(); 
            }),
            'total_documentos_activos' => $flujo->etapas->sum(function($etapa) { 
                return $etapa->documentos->count(); 
            })
        ]);

        return view('superadmin.ejecucion.procesos.ejecutar', compact('flujo', 'isSuper', 'detalleFlujo'));
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

            // Verificar que la ejecución no esté cancelada
            if ($detalleFlujo->estado == 99) {
                return response()->json(['error' => 'No se pueden modificar tareas de una ejecución cancelada'], 400);
            }

            // Buscar la tarea para obtener su etapa
            $tarea = \App\Models\Tarea::findOrFail($tareaId);
            
            // Buscar el detalle_etapa correspondiente
            $detalleEtapa = DetalleEtapa::where('id_etapa', $tarea->id_etapa)
                ->where('id_detalle_flujo', $detalleFlujoId)
                ->firstOrFail();

            // Buscar si ya existe un detalle para esta tarea en esta ejecución específica
            $detalle = DetalleTarea::where('id_tarea', $tareaId)
                ->where('id_detalle_etapa', $detalleEtapa->id)
                ->first();
            
            if ($detalle) {
                // Actualizar el existente
                $detalle->update([
                    'estado' => $completada ? 3 : 2, // 3 = completado, 2 = en ejecución
                    'id_user_create' => $user->id
                ]);
                Log::info('Detalle actualizado', ['detalle_id' => $detalle->id, 'estado' => $detalle->estado]);
            } else {
                // Crear nuevo detalle para esta ejecución específica
                $detalle = DetalleTarea::create([
                    'id_tarea' => $tareaId,
                    'id_detalle_etapa' => $detalleEtapa->id,
                    'estado' => $completada ? 3 : 2, // 3 = completado, 2 = en ejecución (activado desde estado 0)
                    'id_user_create' => $user->id
                ]);
                Log::info('Detalle creado', ['detalle_id' => $detalle->id, 'estado' => $detalle->estado]);
            }

            return response()->json([
                'success' => true,
                'message' => $completada ? 'Tarea marcada como completada' : 'Tarea marcada como pendiente',
                'completada' => ($detalle->estado == 3), // Verificar que sea exactamente 3
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
     * Grabar cambios de una etapa completa (tareas en lote)
     */
    public function grabarEtapa(Request $request)
    {
        try {
            $user = Auth::user();
            $isSuper = ($user->rol->nombre === 'SUPERADMIN');

            // SUPERADMIN no puede actualizar tareas
            if ($isSuper) {
                return response()->json(['error' => 'Los SUPERADMIN no pueden modificar tareas'], 403);
            }

            Log::info('Iniciando grabado de etapa', $request->all());
            
            $request->validate([
                'etapa_id' => 'required|exists:etapas,id',
                'detalle_flujo_id' => 'required|exists:detalle_flujo,id',
                'tareas' => 'nullable|array',
                'tareas.*.tarea_id' => 'required|exists:tareas,id',
                'tareas.*.completada' => 'required|boolean'
            ]);
            
            $etapaId = $request->etapa_id;
            $detalleFlujoId = $request->detalle_flujo_id;
            $tareas = $request->tareas;

            Log::info('Datos validados para grabar etapa', [
                'etapa_id' => $etapaId,
                'detalle_flujo_id' => $detalleFlujoId,
                'tareas_count' => count($tareas),
                'user_id' => $user->id
            ]);

            // Verificar que el detalle_flujo pertenece a la empresa del usuario
            $detalleFlujo = DetalleFlujo::where('id', $detalleFlujoId)
                ->where('id_emp', $user->id_emp)
                ->firstOrFail();

            // Verificar que la ejecución no esté cancelada
            if ($detalleFlujo->estado == 99) {
                return response()->json(['error' => 'No se pueden modificar etapas de una ejecución cancelada'], 400);
            }

            // Verificar que la etapa pertenece al flujo
            $etapa = \App\Models\Etapa::where('id', $etapaId)
                ->where('id_flujo', $detalleFlujo->id_flujo)
                ->firstOrFail();

            // Buscar el detalle_etapa correspondiente
            $detalleEtapa = DetalleEtapa::where('id_etapa', $etapaId)
                ->where('id_detalle_flujo', $detalleFlujoId)
                ->firstOrFail();

            DB::beginTransaction();

            $tareasActualizadas = 0;
            $etapaCompletada = false;
            $flujoCompletado = false;

            // Procesar cada tarea (si existen)
            if (!empty($tareas)) {
                foreach ($tareas as $tareaData) {
                    $tareaId = $tareaData['tarea_id'];
                    $completada = $tareaData['completada'];

                    // Verificar que la tarea pertenece a la etapa
                    $tarea = \App\Models\Tarea::where('id', $tareaId)
                        ->where('id_etapa', $etapaId)
                        ->first();
                    
                    if (!$tarea) {
                        Log::warning('Tarea no pertenece a la etapa', ['tarea_id' => $tareaId, 'etapa_id' => $etapaId]);
                        continue;
                    }

                    // Buscar si ya existe un detalle para esta tarea
                    $detalle = DetalleTarea::where('id_tarea', $tareaId)
                        ->where('id_detalle_etapa', $detalleEtapa->id)
                        ->first();
                    
                    if ($detalle) {
                        // Actualizar el existente
                        $detalle->update([
                            'estado' => $completada ? 3 : 2, // 3 = completado, 2 = en ejecución
                            'id_user_create' => $user->id
                        ]);
                    } else {
                        // Crear nuevo detalle
                    $detalle = DetalleTarea::create([
                        'id_tarea' => $tareaId,
                        'id_detalle_etapa' => $detalleEtapa->id,
                        'estado' => $completada ? 3 : 2,
                        'id_user_create' => $user->id
                    ]);
                }

                    $tareasActualizadas++;
                    Log::info('Tarea procesada', ['tarea_id' => $tareaId, 'completada' => $completada, 'detalle_id' => $detalle->id]);
                }
            }

            // Verificar estados después de procesar todas las tareas
            if ($tareasActualizadas > 0) {
                // Usar la primera tarea para verificar estados (todas pertenecen a la misma etapa)
                $primeraTaskaId = $tareas[0]['tarea_id'];
                $estadosResult = $this->verificarYActualizarEstados($primeraTaskaId, 'tarea', $detalleFlujoId);
                
                if (is_array($estadosResult) && isset($estadosResult['flujo_completado'])) {
                    $flujoCompletado = $estadosResult;
                } elseif ($estadosResult === true) {
                    $etapaCompletada = true;
                }
            } else {
                // Si no hay tareas, verificar si solo hay documentos y están completos
                $totalDocumentos = $etapa->documentos()->where('estado', 1)->count();
                $documentosCompletos = DetalleDocumento::whereHas('documento', function($q) use ($etapaId) {
                        $q->where('id_etapa', $etapaId)->where('estado', 1);
                    })
                    ->where('id_detalle_etapa', $detalleEtapa->id)
                    ->whereNotNull('archivo_url')
                    ->count();
                
                if ($totalDocumentos > 0 && $documentosCompletos == $totalDocumentos) {
                    // Verificar estados usando cualquier documento de la etapa
                    $primerDocumento = $etapa->documentos()->where('estado', 1)->first();
                    if ($primerDocumento) {
                        $estadosResult = $this->verificarYActualizarEstados($primerDocumento->id, 'documento', $detalleFlujoId);
                        
                        if (is_array($estadosResult) && isset($estadosResult['flujo_completado'])) {
                            $flujoCompletado = $estadosResult;
                        } elseif ($estadosResult === true) {
                            $etapaCompletada = true;
                        }
                    }
                }
            }

            DB::commit();

            Log::info('Etapa grabada exitosamente', [
                'etapa_id' => $etapaId,
                'tareas_actualizadas' => $tareasActualizadas,
                'etapa_completada' => $etapaCompletada,
                'flujo_completado' => $flujoCompletado
            ]);

            return response()->json([
                'success' => true,
                'message' => $tareasActualizadas > 0 
                    ? "Etapa grabada correctamente. {$tareasActualizadas} tareas actualizadas."
                    : "Etapa grabada correctamente.",
                'tareas_actualizadas' => $tareasActualizadas,
                'estados' => $flujoCompletado ?: $etapaCompletada
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            Log::error('Error de validación en grabar etapa: ' . $e->getMessage(), [
                'request' => $request->all(),
                'errors' => $e->errors()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error de validación: ' . implode(', ', collect($e->errors())->flatten()->toArray())
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error grabando etapa: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al grabar la etapa: ' . $e->getMessage()
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

            // Verificar que la ejecución no esté cancelada
            if ($detalleFlujo->estado == 99) {
                return response()->json(['error' => 'No se pueden subir documentos a una ejecución cancelada'], 400);
            }

            // Crear directorio si no existe
            $directorio = 'documentos/ejecucion/' . $detalleFlujoId . '/' . date('Y/m');
            
            // Generar nombre único para el archivo
            $nombreArchivo = time() . '_' . $documentoId . '_' . $archivo->getClientOriginalName();
            
            // Guardar archivo
            $rutaArchivo = $archivo->storeAs($directorio, $nombreArchivo, 'public');

            // Buscar si ya existe un detalle para este documento en esta ejecución específica
            $documento = \App\Models\Documento::find($documentoId);
            if (!$documento) {
                throw new \Exception('Documento no encontrado');
            }

            // Buscar la etapa del documento
            $etapa = $documento->etapa;
            if (!$etapa) {
                throw new \Exception('Etapa del documento no encontrada');
            }

            // Buscar el detalle_etapa correspondiente a esta ejecución
            $detalleEtapa = DetalleEtapa::where('id_etapa', $etapa->id)
                ->where('id_detalle_flujo', $detalleFlujoId)
                ->first();

            if (!$detalleEtapa) {
                throw new \Exception('Detalle de etapa no encontrado para esta ejecución');
            }

            $detalle = DetalleDocumento::where('id_documento', $documentoId)
                ->where('id_detalle_etapa', $detalleEtapa->id)
                ->first();

            if ($detalle) {
                // Actualizar el existente
                $detalle->update([
                    'estado' => 3, // 3 = subido/completado
                    'ruta_doc' => $rutaArchivo,
                    'id_user_create' => $user->id
                ]);
            } else {
                // Crear nuevo detalle para esta ejecución específica
                $detalle = DetalleDocumento::create([
                    'id_documento' => $documentoId,
                    'id_detalle_etapa' => $detalleEtapa->id,
                    'estado' => 3, // 3 = subido/completado
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
                'usuario' => [
                    'name' => $user->name,
                    'id' => $user->id
                ],
                'fecha_subida' => $detalle->updated_at->format('d/m/Y H:i'),
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
            $tareasCompletadas = DetalleTarea::where('estado', 3) // Solo estado 3 es completado
                ->whereIn('id_tarea', $etapa->tareas->pluck('id'))
                ->whereNotIn('estado', [99]) // Excluir tareas canceladas
                ->count();
            
            $documentosSubidos = DetalleDocumento::where('estado', 3) // Solo estado 3 es subido
                ->whereIn('id_documento', $etapa->documentos->pluck('id'))
                ->whereNotIn('estado', [99]) // Excluir documentos cancelados
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

            // Verificar si todas las tareas de la etapa están completadas para esta ejecución (excluyendo canceladas)
            $totalTareas = $etapa->tareas()->where('estado', 1)->count();
            $tareasCompletadas = DetalleTarea::where('estado', 3) // Solo estado 3 es completado
                ->where('id_detalle_etapa', $detalleEtapa->id)
                ->whereIn('id_tarea', $etapa->tareas()->where('estado', 1)->pluck('id'))
                ->whereNotIn('estado', [99]) // Excluir tareas canceladas
                ->count();

            // Verificar si todos los documentos de la etapa están subidos para esta ejecución (excluyendo cancelados)
            $totalDocumentos = $etapa->documentos()->where('estado', 1)->count();
            $documentosSubidos = DetalleDocumento::where('estado', 3) // Solo estado 3 es subido
                ->whereHas('detalleEtapa', function($query) use ($detalleFlujoId) {
                    $query->where('id_detalle_flujo', $detalleFlujoId);
                })
                ->whereIn('id_documento', $etapa->documentos()->where('estado', 1)->pluck('id'))
                ->whereNotIn('estado', [99]) // Excluir documentos cancelados
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
                        $totalEtapasEjecucion = DetalleEtapa::where('id_detalle_flujo', $detalleFlujoId)
                            ->whereNotIn('estado', [99]) // Excluir etapas canceladas
                            ->count();
                        $etapasCompletadasEjecucion = DetalleEtapa::where('id_detalle_flujo', $detalleFlujoId)
                            ->where('estado', 3)
                            ->whereNotIn('estado', [99]) // Excluir etapas canceladas
                            ->count();

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
                                'flujo_completado' => true,
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
     * Eliminar documento subido
     */
    public function eliminarDocumento(Request $request, $documentoId)
    {
        try {
            $user = Auth::user();
            $isSuper = ($user->rol->nombre === 'SUPERADMIN');

            // SUPERADMIN no puede eliminar documentos
            if ($isSuper) {
                return response()->json(['error' => 'Los SUPERADMIN no pueden eliminar documentos'], 403);
            }

            Log::info('Iniciando eliminación de documento', [
                'documento_id' => $documentoId,
                'user_id' => $user->id,
                'request' => $request->all()
            ]);

            $request->validate([
                'detalle_flujo_id' => 'required|exists:detalle_flujo,id',
                'motivo' => 'nullable|string|max:500'
            ]);

            $detalleFlujoId = $request->detalle_flujo_id;
            $motivo = $request->motivo;

            // Verificar que el detalle_flujo pertenece a la empresa del usuario
            $detalleFlujo = DetalleFlujo::where('id', $detalleFlujoId)
                ->where('id_emp', $user->id_emp)
                ->firstOrFail();

            // Verificar que la ejecución no esté cancelada
            if ($detalleFlujo->estado == 99) {
                return response()->json(['error' => 'No se pueden eliminar documentos de una ejecución cancelada'], 400);
            }

            // Buscar el documento
            $documento = \App\Models\Documento::findOrFail($documentoId);
            
            // Buscar el detalle_etapa correspondiente
            $detalleEtapa = DetalleEtapa::where('id_etapa', $documento->id_etapa)
                ->where('id_detalle_flujo', $detalleFlujoId)
                ->firstOrFail();

            // Buscar el detalle_documento
            $detalleDocumento = DetalleDocumento::where('id_documento', $documentoId)
                ->where('id_detalle_etapa', $detalleEtapa->id)
                ->first();

            if (!$detalleDocumento) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró el documento en esta ejecución'
                ], 404);
            }

            if (!$detalleDocumento->archivo_url) {
                return response()->json([
                    'success' => false,
                    'message' => 'El documento no tiene archivo para eliminar'
                ], 400);
            }

            DB::beginTransaction();

            // Eliminar archivo físico del storage
            $archivoPath = str_replace('/storage/', '', $detalleDocumento->archivo_url);
            if (Storage::disk('public')->exists($archivoPath)) {
                Storage::disk('public')->delete($archivoPath);
                Log::info('Archivo físico eliminado', ['path' => $archivoPath]);
            }

            // Actualizar el detalle_documento - resetear a estado inicial
            $detalleDocumento->update([
                'archivo_url' => null,
                'nombre_archivo' => null,
                'comentarios' => $motivo ? "Eliminado: " . $motivo : "Documento eliminado",
                'estado' => 0, // Volver a estado inicial/pendiente
                'id_user_create' => $user->id
            ]);

            DB::commit();

            Log::info('Documento eliminado exitosamente', [
                'documento_id' => $documentoId,
                'detalle_documento_id' => $detalleDocumento->id,
                'motivo' => $motivo,
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Documento eliminado correctamente',
                'documento_id' => $documentoId,
                'detalle_id' => $detalleDocumento->id
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            Log::error('Error de validación en eliminar documento: ' . $e->getMessage(), [
                'request' => $request->all(),
                'errors' => $e->errors()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error de validación: ' . implode(', ', collect($e->errors())->flatten()->toArray())
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error eliminando documento: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el documento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get progress information for a detalle_flujo via AJAX
     */
    public function progreso(DetalleFlujo $detalleFlujo)
    {
        $user = Auth::user();
        
        // Verificar permisos básicos
        if ($user->rol->nombre !== 'SUPERADMIN' && $detalleFlujo->id_emp != $user->id_emp) {
            abort(403, 'No tienes permisos para ver esta ejecución.');
        }

        // Cargar el flujo con todas las relaciones necesarias
        $flujo = $detalleFlujo->flujo()->with([
            'etapas.tareas', 
            'etapas.documentos'
        ])->first();

        if (!$flujo) {
            return response()->json(['error' => 'Flujo no encontrado'], 404);
        }

        $progreso_general = 0;
        $total_items = 0;
        $items_completados = 0;
        $etapas_data = [];

        foreach ($flujo->etapas as $etapa) {
            $tareas_completadas = 0;
            $total_tareas = $etapa->tareas->count();
            
            // Buscar detalle_etapa para esta ejecución
            $detalleEtapa = DetalleEtapa::where('id_etapa', $etapa->id)
                ->where('id_detalle_flujo', $detalleFlujo->id)
                ->first();
            
            foreach ($etapa->tareas as $tarea) {
                if ($detalleEtapa) {
                    $detalle = DetalleTarea::where('id_tarea', $tarea->id)
                        ->where('id_detalle_etapa', $detalleEtapa->id)
                        ->whereNotIn('estado', [99]) // Excluir tareas canceladas
                        ->first();
                    if ($detalle && $detalle->estado == 3) { // Solo estado 3 es completado
                        $tareas_completadas++;
                        $items_completados++;
                    }
                }
                $total_items++;
            }

            $documentos_subidos = 0;
            $total_documentos = $etapa->documentos->count();
            
            if ($detalleEtapa) {
                foreach ($etapa->documentos as $documento) {
                    $detalle = DetalleDocumento::where('id_documento', $documento->id)
                        ->where('id_detalle_etapa', $detalleEtapa->id)
                        ->whereNotIn('estado', [99]) // Excluir documentos cancelados
                        ->first();
                    if ($detalle && $detalle->estado == 3) { // Solo estado 3 es subido
                        $documentos_subidos++;
                        $items_completados++;
                    }
                    $total_items++;
                }
            } else {
                // Si no hay detalle_etapa, contar documentos como pendientes
                $total_items += $total_documentos;
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
            'detalle_flujo_estado' => $detalleFlujo->estado,
            'nombre_ejecucion' => $detalleFlujo->nombre
        ]);
    }

    /**
     * Pausar una ejecución activa
     */
    public function pausarEjecucion(DetalleFlujo $detalleFlujo)
    {
        try {
            $user = Auth::user();
            $isSuper = ($user->rol->nombre === 'SUPERADMIN');

            // SUPERADMIN no puede pausar ejecuciones
            if ($isSuper) {
                return response()->json(['error' => 'SUPERADMIN no puede pausar ejecuciones'], 403);
            }

            // Verificar permisos de empresa
            if ($detalleFlujo->id_emp != $user->id_emp) {
                return response()->json(['error' => 'Sin permisos para esta ejecución'], 403);
            }

            // Verificar que la ejecución esté activa (estado 2)
            if ($detalleFlujo->estado != 2) {
                return response()->json(['error' => 'Solo se pueden pausar ejecuciones activas'], 400);
            }

            // Pausar la ejecución (estado 4 = pausado)
            $detalleFlujo->update(['estado' => 4]);

            Log::info('Ejecución pausada', [
                'detalle_flujo_id' => $detalleFlujo->id,
                'usuario_id' => $user->id,
                'fecha_pausa' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Ejecución pausada correctamente',
                'nuevo_estado' => $detalleFlujo->estado,
                'fecha_pausa' => $detalleFlujo->updated_at->format('d/m/Y H:i:s')
            ]);

        } catch (\Exception $e) {
            Log::error('Error al pausar ejecución', [
                'detalle_flujo_id' => $detalleFlujo->id ?? null,
                'error' => $e->getMessage(),
                'user_id' => $user->id ?? null
            ]);

            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    }

    /**
     * Reactivar una ejecución pausada
     */
    public function reactivarEjecucion(DetalleFlujo $detalleFlujo)
    {
        try {
            $user = Auth::user();
            $isSuper = ($user->rol->nombre === 'SUPERADMIN');

            // SUPERADMIN no puede reactivar ejecuciones
            if ($isSuper) {
                return response()->json(['error' => 'SUPERADMIN no puede reactivar ejecuciones'], 403);
            }

            // Verificar permisos de empresa
            if ($detalleFlujo->id_emp != $user->id_emp) {
                return response()->json(['error' => 'Sin permisos para esta ejecución'], 403);
            }

            // Verificar que la ejecución esté pausada (estado 4)
            if ($detalleFlujo->estado != 4) {
                return response()->json(['error' => 'Solo se pueden reactivar ejecuciones pausadas'], 400);
            }

            // Reactivar la ejecución (estado 2 = en ejecución)
            $detalleFlujo->update(['estado' => 2]);

            Log::info('Ejecución reactivada', [
                'detalle_flujo_id' => $detalleFlujo->id,
                'usuario_id' => $user->id,
                'fecha_reactivacion' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Ejecución reactivada correctamente',
                'nuevo_estado' => $detalleFlujo->estado,
                'fecha_reactivacion' => $detalleFlujo->updated_at->format('d/m/Y H:i:s')
            ]);

        } catch (\Exception $e) {
            Log::error('Error al reactivar ejecución', [
                'detalle_flujo_id' => $detalleFlujo->id ?? null,
                'error' => $e->getMessage(),
                'user_id' => $user->id ?? null
            ]);

            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    }

    /**
     * Cancelar una ejecución activa o pausada
     */
    public function cancelarEjecucion(Request $request, DetalleFlujo $detalleFlujo)
    {
        try {
            $user = Auth::user();
            $isSuper = ($user->rol->nombre === 'SUPERADMIN');

            // SUPERADMIN no puede cancelar ejecuciones
            if ($isSuper) {
                return response()->json(['error' => 'SUPERADMIN no puede cancelar ejecuciones'], 403);
            }

            // Verificar permisos de empresa
            if ($detalleFlujo->id_emp != $user->id_emp) {
                return response()->json(['error' => 'Sin permisos para esta ejecución'], 403);
            }

            // Verificar que la ejecución esté activa (estado 2) o pausada (estado 4)
            if (!in_array($detalleFlujo->estado, [2, 4])) {
                return response()->json(['error' => 'Solo se pueden cancelar ejecuciones activas o pausadas'], 400);
            }

            // Validar que se proporcione un motivo
            $request->validate([
                'motivo' => 'required|string|min:5|max:500'
            ], [
                'motivo.required' => 'El motivo de cancelación es obligatorio',
                'motivo.min' => 'El motivo debe tener al menos 5 caracteres',
                'motivo.max' => 'El motivo no puede exceder 500 caracteres'
            ]);

            // Cancelar la ejecución (estado 99 = cancelado)
            DB::beginTransaction();

            // Actualizar el DetalleFlujo principal
            $detalleFlujo->update([
                'estado' => 99,
                'motivo' => $request->motivo
            ]);

            // Actualizar todos los DetalleEtapa relacionados a estado 99
            DetalleEtapa::where('id_detalle_flujo', $detalleFlujo->id)
                ->update(['estado' => 99]);

            // Obtener todos los IDs de DetalleEtapa para actualizar sus detalles relacionados
            $detalleEtapaIds = DetalleEtapa::where('id_detalle_flujo', $detalleFlujo->id)
                ->pluck('id')
                ->toArray();

            // Actualizar todas las DetalleTarea relacionadas a estado 99
            if (!empty($detalleEtapaIds)) {
                DetalleTarea::whereIn('id_detalle_etapa', $detalleEtapaIds)
                    ->update(['estado' => 99]);

                // Actualizar todos los DetalleDocumento relacionados a estado 99
                DetalleDocumento::whereIn('id_detalle_etapa', $detalleEtapaIds)
                    ->update(['estado' => 99]);
            }

            DB::commit();

            Log::info('Ejecución cancelada con actualización en cascada', [
                'detalle_flujo_id' => $detalleFlujo->id,
                'usuario_id' => $user->id,
                'motivo' => $request->motivo,
                'fecha_cancelacion' => now(),
                'detalle_etapas_actualizadas' => count($detalleEtapaIds),
                'detalle_tareas_actualizadas' => !empty($detalleEtapaIds) ? DetalleTarea::whereIn('id_detalle_etapa', $detalleEtapaIds)->count() : 0,
                'detalle_documentos_actualizados' => !empty($detalleEtapaIds) ? DetalleDocumento::whereIn('id_detalle_etapa', $detalleEtapaIds)->count() : 0
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Ejecución cancelada correctamente',
                'nuevo_estado' => $detalleFlujo->estado,
                'motivo' => $detalleFlujo->motivo,
                'fecha_cancelacion' => $detalleFlujo->updated_at->format('d/m/Y H:i:s')
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollback();
            return response()->json([
                'error' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error al cancelar ejecución', [
                'detalle_flujo_id' => $detalleFlujo->id ?? null,
                'error' => $e->getMessage(),
                'user_id' => $user->id ?? null
            ]);

            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    }
}
