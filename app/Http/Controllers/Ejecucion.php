<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Flujo;
use App\Models\Etapa;
use App\Models\Tarea;
use App\Models\Documento;
use App\Models\Empresa;
use App\Models\Rol;
use App\Models\DetalleTarea;
use App\Models\DetalleDocumento;
use App\Models\DetalleFlujo;
use App\Models\DetalleEtapa;
use App\Models\FormRun;
use App\Models\FormAnswer;
use App\Models\FormField;
use App\Models\FormGroup;
use App\Models\EtapaForm;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
                return $etapa->documentos()->where('documentos.estado', 1)->count();
            });
            
            $flujo->total_etapas = $totalEtapas;
            $flujo->total_documentos = $totalDocumentos;
            
            return $flujo;
        });

        $flujos->setCollection($flujosConContadores);

        // Obtener ejecuciones activas usando la tabla detalle_flujo
        $ejecucionesActivas = collect();
        if (!$isSuper) {
            $ejecucionesActivas = DetalleFlujo::with([
                'flujo.empresa', 
                'flujo.tipo', 
                'flujo.etapas.tareas.documentos'
            ])
                ->where('id_emp', $user->id_emp)
                ->whereIn('estado', [2, 3, 4, 99]) // 2 = En ejecución, 3 = Terminado, 4 = Pausado, 99 = Cancelado
                ->orderBy('estado') // Primero en ejecución, luego pausados, luego terminados, luego cancelados
                ->orderBy('updated_at', 'desc')
                ->get();
        } else {
            // Para SUPERADMIN, mostrar todas las ejecuciones
            $ejecucionesActivas = DetalleFlujo::with([
                'flujo.empresa', 
                'flujo.tipo', 
                'flujo.etapas.tareas.documentos'
            ])
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
                    return $etapa->documentos()->where('documentos.estado', 1)->count();
                });
                
                // Calcular progreso usando exactamente la misma lógica que ejecutar.blade.php
                // Llamar al método progreso() que es el mismo que usa el JavaScript
                $progresoResponse = $this->progreso($detalleFlujo);
                $progresoData = json_decode($progresoResponse->getContent(), true);
                $progresoPorcentaje = $progresoData['progreso_general'] ?? 0;
                
                $flujo->total_etapas = $totalEtapas;
                $flujo->total_documentos = $totalDocumentos;
                $flujo->estado_ejecucion = $detalleFlujo->estado;
                $flujo->detalle_flujo_id = $detalleFlujo->id;
                $flujo->fecha_ejecucion = $detalleFlujo->updated_at;
                $flujo->progreso_porcentaje = $progresoPorcentaje;
                
                // TAMBIÉN asignar el progreso al DetalleFlujo para asegurar que esté disponible
                $detalleFlujo->progreso_porcentaje = $progresoPorcentaje;
            }
            return $detalleFlujo;
        });

        // Empresas para filtro (solo para SUPERADMIN)
        $empresas = collect();
        if ($isSuper) {
            $empresas = Empresa::where('estado', 1)->orderBy('nombre')->get(['id', 'nombre']);
        }

        // Separar ejecuciones por estado para la vista
        $ejecucionesEnProceso = $ejecucionesActivas->where('estado', 2);
        $ejecucionesTerminadas = $ejecucionesActivas->where('estado', 3);
        $ejecucionesPausadasYCanceladas = $ejecucionesActivas->whereIn('estado', [4, 99]);

        return view('superadmin.ejecucion.index', compact(
            'flujos', 'ejecucionesActivas', 'ejecucionesEnProceso', 'ejecucionesTerminadas', 'ejecucionesPausadasYCanceladas', 'isSuper', 'estado', 'q', 'empresas', 'empresa_id'
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

            // Buscar ejecución activa para mostrar progreso real
            $detalleFlujoActivo = DetalleFlujo::where('id_flujo', $flujo->id)
                ->when(!$isSuper, fn($q) => $q->where('id_emp', $user->id_emp))
                ->whereIn('estado', [2, 3, 4]) // En proceso, completado, pausado
                ->orderBy('updated_at', 'desc')
                ->first();

            // Si hay ejecución activa, cargar datos específicos de esa ejecución
            if ($detalleFlujoActivo) {
                // Cargar estados específicos de esta ejecución (DetalleFlujo)
                foreach ($flujo->etapas as $etapa) {
                    // Buscar el DetalleEtapa correspondiente
                    $detalleEtapa = DetalleEtapa::where('id_etapa', $etapa->id)
                        ->where('id_detalle_flujo', $detalleFlujoActivo->id)
                        ->first();
                    
                    $etapa->detalle_etapa = $detalleEtapa;
                    $etapa->estado_ejecucion = $detalleEtapa ? $detalleEtapa->estado : 1;

                    foreach ($etapa->tareas as $tarea) {
                        // Buscar DetalleTarea vinculado a esta ejecución específica a través del detalle_etapa
                        $detalleTarea = null;
                        if ($detalleEtapa) {
                            $detalleTarea = DetalleTarea::with('userCreate')->where('id_tarea', $tarea->id)
                                ->where('id_detalle_etapa', $detalleEtapa->id)
                                ->whereNotIn('estado', [66, 99]) // Excluir tareas que no influyen en flujo (66) y canceladas (99)
                                ->first();
                        }
                        
                        if ($detalleTarea) {
                            // Solo estado 3 significa completado
                            $tarea->completada = ($detalleTarea->estado == 3);
                            $tarea->detalle_id = $detalleTarea->id;
                            $tarea->detalle_flujo_id = $detalleFlujoActivo->id;
                            $tarea->detalle = $detalleTarea;
                            
                            // Agregar información de completado
                            if ($detalleTarea->estado == 3) {
                                $tarea->completado_por_nombre = $detalleTarea->userCreate ? $detalleTarea->userCreate->name : 'Sistema';
                                $tarea->fecha_completado = $detalleTarea->updated_at;
                            }
                        } else {
                            // Si no tiene detalle o tiene estado 66, marcar como no incluida
                            $tarea->completada = false;
                            $tarea->detalle = null;
                        }
                    }
                    
                    foreach ($etapa->documentos as $documento) {
                        // Buscar DetalleDocumento vinculado a esta ejecución específica a través del detalle_tarea
                        $detalleDocumento = null;
                        if ($detalleEtapa) {
                            $detalleDocumento = DetalleDocumento::with('userCreate')->where('id_documento', $documento->id)
                                ->whereHas('detalleTarea', function($query) use ($detalleEtapa) {
                                    $query->where('id_detalle_etapa', $detalleEtapa->id);
                                })
                                ->whereNotIn('estado', [66, 99]) // Excluir documentos que no influyen en flujo (66) y cancelados (99)
                                ->first();
                        }
                        
                        if ($detalleDocumento) {
                            // Solo estado 3 significa subido/completado
                            $documento->subido = ($detalleDocumento->estado == 3);
                            $documento->url_archivo = ($detalleDocumento && $detalleDocumento->ruta_doc) ? 
                                Storage::url($detalleDocumento->ruta_doc) : null;
                            $documento->detalle_id = $detalleDocumento->id;
                            $documento->detalle_flujo_id = $detalleFlujoActivo->id;
                            $documento->detalle = $detalleDocumento;
                            
                            // Agregar información de subida
                            if ($detalleDocumento->estado == 3) {
                                $documento->subido_por_nombre = $detalleDocumento->userCreate ? $detalleDocumento->userCreate->name : 'Sistema';
                                $documento->fecha_subida = $detalleDocumento->updated_at;
                            }
                        } else {
                            // Si no tiene detalle o tiene estado 66, marcar como no incluido
                            $documento->subido = false;
                            $documento->url_archivo = null;
                            $documento->detalle = null;
                        }
                    }
                }
            } else {
                // Si no hay ejecución activa, marcar todo como no completado
                foreach ($flujo->etapas as $etapa) {
                    foreach ($etapa->tareas as $tarea) {
                        $tarea->completada = false;
                        $tarea->detalle = null;
                    }
                    
                    foreach ($etapa->documentos as $documento) {
                        $documento->subido = false;
                        $documento->url_archivo = null;
                        $documento->detalle = null;
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
                'view_data_flujo_id' => $flujo->id,
                'detalle_flujo_activo' => $detalleFlujoActivo ? $detalleFlujoActivo->id : null
            ]);

            // Pasar información del rol para la vista
            $userRole = $user->rol->nombre;

            return view('superadmin.ejecucion.show', compact('flujo', 'isSuper', 'userRole', 'detalleFlujoActivo'));

        } catch (\Exception $e) {
            Log::error('Error in show method', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Show a flujo (redirects to active detalle_flujo if exists)
     */
    public function showFlujo(Flujo $flujo)
    {
        $user = Auth::user();
        $isSuper = ($user->rol->nombre === 'SUPERADMIN');

        // Verificar permisos
        if (!$isSuper && $flujo->id_emp != $user->id_emp) {
            abort(403, 'No tienes permisos para ver este flujo.');
        }

        // Buscar ejecución activa
        $detalleFlujoActivo = DetalleFlujo::where('id_flujo', $flujo->id)
            ->when(!$isSuper, fn($q) => $q->where('id_emp', $user->id_emp))
            ->whereIn('estado', [2, 3, 4]) // En proceso, completado, pausado
            ->orderBy('updated_at', 'desc')
            ->first();

        if ($detalleFlujoActivo) {
            // Redirigir a la vista específica del detalle_flujo
            return redirect()->route('ejecucion.detalle.show', $detalleFlujoActivo);
        } else {
            // Mostrar vista del flujo sin ejecución activa
            return $this->showFlujoSinEjecucion($flujo);
        }
    }

    /**
     * Show a specific detalle_flujo execution
     */
    public function showDetalle(DetalleFlujo $detalleFlujo)
    {
        try {
            $user = Auth::user();
            $isSuper = ($user->rol->nombre === 'SUPERADMIN');

            // Cargar el flujo con todas sus relaciones
            $flujo = $detalleFlujo->flujo()->with([
                'tipo',
                'empresa', 
                'etapas' => function($query) {
                    $query->orderBy('nro');
                },
                'etapas.tareas.documentos' // Cargar documentos de las tareas
            ])->first();

            if (!$flujo) {
                abort(404, 'Flujo no encontrado');
            }

            // Verificar permisos
            if (!$isSuper && $detalleFlujo->id_emp != $user->id_emp) {
                abort(403, 'No tienes permisos para ver esta ejecución.');
            }

            // Cargar estados específicos de esta ejecución
            foreach ($flujo->etapas as $etapa) {
                // Buscar el DetalleEtapa correspondiente
                $detalleEtapa = DetalleEtapa::where('id_etapa', $etapa->id)
                    ->where('id_detalle_flujo', $detalleFlujo->id)
                    ->first();
                
                $etapa->detalle_etapa = $detalleEtapa;
                $etapa->estado_ejecucion = $detalleEtapa ? $detalleEtapa->estado : 1;

                foreach ($etapa->tareas as $tarea) {
                    // Buscar DetalleTarea vinculado a esta ejecución específica
                    $detalleTarea = null;
                    if ($detalleEtapa) {
                        $detalleTarea = DetalleTarea::with('userCreate')->where('id_tarea', $tarea->id)
                            ->where('id_detalle_etapa', $detalleEtapa->id)
                            ->whereNotIn('estado', [66, 99]) // Excluir tareas que no influyen en flujo
                            ->first();
                    }
                    
                    if ($detalleTarea) {
                        $tarea->completada = ($detalleTarea->estado == 3);
                        $tarea->detalle_id = $detalleTarea->id;
                        $tarea->detalle_flujo_id = $detalleFlujo->id;
                        $tarea->detalle = $detalleTarea;
                        
                        // Agregar información de completado
                        if ($detalleTarea->estado == 3) {
                            $tarea->completado_por_nombre = $detalleTarea->userCreate ? $detalleTarea->userCreate->name : 'Sistema';
                            $tarea->fecha_completado = $detalleTarea->updated_at;
                        }
                    } else {
                        $tarea->completada = false;
                        $tarea->detalle = null;
                    }

                    // Cargar documentos de esta tarea con sus estados específicos
                    foreach ($tarea->documentos as $documento) {
                        // Buscar DetalleDocumento vinculado a esta tarea específica
                        $detalleDocumento = null;
                        if ($detalleTarea) {
                            $detalleDocumento = DetalleDocumento::with('userCreate')->where('id_documento', $documento->id)
                                ->where('id_detalle_tarea', $detalleTarea->id)
                                ->whereNotIn('estado', [66, 99]) // Excluir documentos que no influyen en flujo
                                ->first();
                        }
                        
                        if ($detalleDocumento) {
                            $documento->subido = ($detalleDocumento->estado == 3);
                            $documento->url_archivo = ($detalleDocumento && $detalleDocumento->ruta_doc) ? 
                                Storage::url($detalleDocumento->ruta_doc) : null;
                            $documento->detalle_id = $detalleDocumento->id;
                            $documento->detalle_flujo_id = $detalleFlujo->id;
                            $documento->detalle = $detalleDocumento;
                            
                            // Agregar información de subida
                            if ($detalleDocumento->estado == 3) {
                                $documento->subido_por_nombre = $detalleDocumento->userCreate ? $detalleDocumento->userCreate->name : 'Sistema';
                                $documento->fecha_subida = $detalleDocumento->updated_at;
                            }
                        } else {
                            $documento->subido = false;
                            $documento->url_archivo = null;
                            $documento->detalle = null;
                        }
                    }
                }
            }

            // Pasar información del rol para la vista
            $userRole = $user->rol->nombre;

            // Usar detalleFlujoActivo para consistencia con el blade
            $detalleFlujoActivo = $detalleFlujo;

            return view('superadmin.ejecucion.show', compact('flujo', 'isSuper', 'userRole', 'detalleFlujoActivo'));

        } catch (\Exception $e) {
            Log::error('Error in showDetalle method', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Show flujo without active execution
     */
    private function showFlujoSinEjecucion(Flujo $flujo)
    {
        $user = Auth::user();
        $isSuper = ($user->rol->nombre === 'SUPERADMIN');

        // Cargar el flujo con todas sus relaciones
        $flujo = $flujo->load([
            'tipo',
            'empresa', 
            'etapas' => function($query) {
                $query->orderBy('nro');
            },
            'etapas.tareas',
            'etapas.documentos'
        ]);

        // Marcar todo como no completado
        foreach ($flujo->etapas as $etapa) {
            foreach ($etapa->tareas as $tarea) {
                $tarea->completada = false;
                $tarea->detalle = null;
            }
            
            foreach ($etapa->documentos as $documento) {
                $documento->subido = false;
                $documento->url_archivo = null;
                $documento->detalle = null;
            }
        }

        $userRole = $user->rol->nombre;
        $detalleFlujoActivo = null; // No hay ejecución activa

        return view('superadmin.ejecucion.show', compact('flujo', 'isSuper', 'userRole', 'detalleFlujoActivo'));
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

            // Cargar etapas con sus tareas, documentos y formularios para mostrar en la configuración
            $flujo->load([
                'etapas' => function($query) {
                    $query->where('etapas.estado', 1)->orderBy('nro');
                },
                'etapas.tareas' => function($query) {
                    $query->where('tareas.estado', 1)->orderBy('nombre');
                },
                'etapas.documentos' => function($query) {
                    $query->where('documentos.estado', 1)->orderBy('nombre');
                },
                'etapas.etapaForms.form' => function($query) {
                    $query->where('forms.estado', 1)->orderBy('forms.nombre');
                }
            ]);

            Log::info('Configuración de flujo cargada', [
                'flujo_id' => $flujo->id,
                'etapas_count' => $flujo->etapas->count(),
                'total_tareas' => $flujo->etapas->sum(function($etapa) { return $etapa->tareas->count(); }),
                'total_documentos' => $flujo->etapas->sum(function($etapa) { return $etapa->documentos->count(); }),
                'total_formularios' => $flujo->etapas->sum(function($etapa) { return $etapa->etapaForms->count(); })
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
                                    'descripcion' => $tarea->descripcion,
                                    'documentos' => $tarea->documentos()->where('documentos.estado', 1)->get()->map(function($documento) {
                                        return [
                                            'id' => $documento->id,
                                            'nombre' => $documento->nombre,
                                            'descripcion' => $documento->descripcion
                                        ];
                                    })
                                ];
                            }),
                            // Para compatibilidad, mantener documentos a nivel de etapa (todos los documentos de sus tareas)
                            'documentos' => $etapa->documentos()->where('documentos.estado', 1)->get()->map(function($documento) {
                                return [
                                    'id' => $documento->id,
                                    'nombre' => $documento->nombre,
                                    'descripcion' => $documento->descripcion
                                ];
                            }),
                            // Formularios asociados a esta etapa
                            'formularios' => $etapa->etapaForms->map(function($etapaForm) {
                                return [
                                    'id' => $etapaForm->form->id,
                                    'nombre' => $etapaForm->form->nombre,
                                    'descripcion' => $etapaForm->form->descripcion,
                                    'etapa_form_id' => $etapaForm->id,
                                    'requerido' => $etapaForm->requerido ?? true
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
                    $query->where('etapas.estado', 1)->orderBy('nro');
                },
                'etapas.tareas' => function($query) {
                    $query->where('tareas.estado', 1)->orderBy('id');
                },
                'etapas.documentos' => function($query) {
                    $query->where('documentos.estado', 1)->orderBy('id');
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
                                    'descripcion' => $tarea->descripcion,
                                    'documentos' => $tarea->documentos()->where('documentos.estado', 1)->get()->map(function($documento) {
                                        return [
                                            'id' => $documento->id,
                                            'nombre' => $documento->nombre,
                                            'descripcion' => $documento->descripcion
                                        ];
                                    })
                                ];
                            }),
                            // Para compatibilidad, mantener documentos a nivel de etapa
                            'documentos' => $etapa->documentos()->where('documentos.estado', 1)->get()->map(function($documento) {
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
     * Get specific execution preview information with real status.
     */
    public function previsualizarDetalle(DetalleFlujo $detalleFlujo)
    {
        try {
            $user = Auth::user();
            $isSuper = ($user->rol->nombre === 'SUPERADMIN');

            // Verificar permisos de empresa (SUPERADMIN puede ver todos)
            if (!$isSuper && $detalleFlujo->id_emp != $user->id_emp) {
                return response()->json(['error' => 'Sin permisos para ver esta ejecución'], 403);
            }

            // Cargar el flujo con todas sus relaciones y los detalles específicos de esta ejecución
            $flujo = $detalleFlujo->flujo;
            $flujo->load([
                'tipo',
                'empresa',
                'etapas' => function($query) {
                    $query->where('etapas.estado', 1)->orderBy('nro');
                },
                'etapas.tareas' => function($query) {
                    $query->where('tareas.estado', 1)->orderBy('id');
                },
                'etapas.documentos' => function($query) {
                    $query->where('documentos.estado', 1)->orderBy('id');
                }
            ]);

            // Cargar los detalles de ejecución específicos
            $detalleFlujo->load([
                'detalleEtapas.detalleTareas.tarea',
                'detalleEtapas.detalleTareas.detalleDocumentos.documento',
                'detalleEtapas.etapa'
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
                    'etapas' => $flujo->etapas->map(function($etapa) use ($detalleFlujo) {
                        // Buscar el detalle de etapa correspondiente
                        $detalleEtapa = $detalleFlujo->detalleEtapas->where('id_etapa', $etapa->id)->first();
                        
                        return [
                            'id' => $etapa->id,
                            'nombre' => $etapa->nombre,
                            'nro' => $etapa->nro,
                            'descripcion' => $etapa->descripcion,
                            'tareas' => $etapa->tareas->map(function($tarea) use ($detalleEtapa) {
                                // Buscar el detalle de tarea correspondiente
                                $detalleTarea = $detalleEtapa ? 
                                    $detalleEtapa->detalleTareas->where('id_tarea', $tarea->id)->first() : null;
                                
                                return [
                                    'id' => $tarea->id,
                                    'nombre' => $tarea->nombre,
                                    'descripcion' => $tarea->descripcion,
                                    'detalle' => $detalleTarea ? [
                                        'estado' => $detalleTarea->estado ? 3 : 1  // boolean to integer conversion
                                    ] : null,
                                    'documentos' => $tarea->documentos()->where('documentos.estado', 1)->get()->map(function($documento) use ($detalleTarea) {
                                        // Buscar el detalle de documento correspondiente dentro de esta tarea específica
                                        $detalleDocumento = $detalleTarea ? 
                                            $detalleTarea->detalleDocumentos->where('id_documento', $documento->id)->first() : null;
                                        
                                        return [
                                            'id' => $documento->id,
                                            'nombre' => $documento->nombre,
                                            'descripcion' => $documento->descripcion,
                                            'detalle' => $detalleDocumento ? [
                                                'estado' => $detalleDocumento->estado ? 3 : 1  // boolean to integer conversion
                                            ] : null
                                        ];
                                    })
                                ];
                            }),
                            // Para compatibilidad, mantener documentos a nivel de etapa (aunque normalmente estarán vacíos)
                            'documentos' => []  // Los documentos están ahora en las tareas
                        ];
                    })
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error al cargar previsualización de detalle de flujo', [
                'detalle_flujo_id' => $detalleFlujo->id ?? null,
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
            'documentos_seleccionados' => 'array',
            'formularios_seleccionados' => 'array'
        ]);

        // Validar que al menos una tarea, documento o formulario esté seleccionado
        $tareasSeleccionadas = $request->tareas_seleccionadas ?? [];
        $documentosSeleccionados = $request->documentos_seleccionados ?? [];
        $formulariosSeleccionados = $request->formularios_seleccionados ?? [];
        
        if (empty($tareasSeleccionadas) && empty($documentosSeleccionados) && empty($formulariosSeleccionados)) {
            return response()->json([
                'error' => 'Debes seleccionar al menos una tarea, un documento o un formulario para crear la ejecución'
            ], 422);
        }

        Log::info('Validación de selección completada', [
            'tareas_seleccionadas_count' => count($tareasSeleccionadas),
            'documentos_seleccionados_count' => count($documentosSeleccionados),
            'formularios_seleccionados_count' => count($formulariosSeleccionados),
            'total_elementos_seleccionados' => count($tareasSeleccionadas) + count($documentosSeleccionados) + count($formulariosSeleccionados)
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

            // Crear registros de detalle_tarea para TODAS las tareas del flujo
            foreach ($flujo->etapas()->where('estado', 1)->get() as $etapa) {
                // Buscar el detalle_etapa correspondiente
                $detalleEtapa = DetalleEtapa::where('id_etapa', $etapa->id)
                    ->where('id_detalle_flujo', $detalleFlujoActivo->id)
                    ->first();
                
                if ($detalleEtapa) {
                    // Procesar todas las tareas de la etapa
                    foreach ($etapa->tareas()->where('estado', 1)->get() as $tarea) {
                        $estadoTarea = in_array($tarea->id, $request->tareas_seleccionadas ?? []) ? 0 : 66;
                        
                        DetalleTarea::create([
                            'id_tarea' => $tarea->id,
                            'id_detalle_etapa' => $detalleEtapa->id,
                            'estado' => $estadoTarea, // 0 = activa en flujo, 66 = no influye en flujo
                            'id_user_create' => $user->id
                        ]);
                        
                        Log::info('Tarea creada en ejecución', [
                            'tarea_id' => $tarea->id,
                            'estado' => $estadoTarea,
                            'incluida_en_flujo' => $estadoTarea === 0
                        ]);
                    }
                }
            }

            // Crear registros de detalle_documento para TODOS los documentos del flujo
            foreach ($flujo->etapas()->where('estado', 1)->get() as $etapa) {
                // Buscar el detalle_etapa correspondiente
                $detalleEtapa = DetalleEtapa::where('id_etapa', $etapa->id)
                    ->where('id_detalle_flujo', $detalleFlujoActivo->id)
                    ->first();
                
                if ($detalleEtapa) {
                    // Procesar todos los documentos de la etapa (ahora a través de las tareas)
                    foreach ($etapa->tareas()->where('estado', 1)->get() as $tarea) {
                        // Obtener el DetalleTarea correspondiente para poder relacionar los documentos
                        $detalleTarea = DetalleTarea::where('id_tarea', $tarea->id)
                            ->where('id_detalle_etapa', $detalleEtapa->id)
                            ->first();
                        
                        if ($detalleTarea) {
                            $documentosTarea = $tarea->documentos()->where('documentos.estado', 1)->get();
                            
                            foreach ($documentosTarea as $documento) {
                                $estadoDocumento = in_array($documento->id, $request->documentos_seleccionados ?? []) ? 0 : 66;
                                
                                DetalleDocumento::create([
                                    'id_documento' => $documento->id,
                                    'id_detalle_tarea' => $detalleTarea->id, // Nueva relación a través de DetalleTarea
                                    'estado' => $estadoDocumento, // 0 = activo en flujo, 66 = no influye en flujo
                                    'id_user_create' => $user->id
                                ]);
                                
                                Log::info('Documento creado en ejecución', [
                                    'documento_id' => $documento->id,
                                    'detalle_tarea_id' => $detalleTarea->id,
                                    'estado' => $estadoDocumento,
                                    'incluido_en_flujo' => $estadoDocumento === 0
                                ]);
                            }
                        }
                    }
                }
            }

            // Crear registros de form_runs para formularios seleccionados
            if (!empty($formulariosSeleccionados)) {
                foreach ($formulariosSeleccionados as $etapaFormId) {
                    try {
                        // Buscar la relación EtapaForm
                        $etapaForm = \App\Models\EtapaForm::with('form')->find($etapaFormId);
                        
                        if ($etapaForm && $etapaForm->form) {
                            // Generar correlativo si el formulario lo requiere
                            $correlativo = null;
                            if ($etapaForm->form->usa_correlativo) {
                                $correlativo = $this->generarCorrelativo($etapaForm->form, $user->id_emp);
                            }
                            
                            // Crear FormRun
                            $formRun = \App\Models\FormRun::create([
                                'id_form' => $etapaForm->form->id,
                                'id_emp' => $user->id_emp,
                                'id_etapas_forms' => $etapaFormId,
                                'correlativo' => $correlativo,
                                'estado' => 'draft',
                                'created_by' => $user->id,
                                'updated_by' => $user->id
                            ]);
                            
                            Log::info('FormRun creado en ejecución', [
                                'form_run_id' => $formRun->id,
                                'form_id' => $etapaForm->form->id,
                                'etapa_form_id' => $etapaFormId,
                                'correlativo' => $correlativo,
                                'detalle_flujo_id' => $detalleFlujoActivo->id
                            ]);
                        }
                    } catch (\Exception $e) {
                        Log::error('Error al crear FormRun', [
                            'etapa_form_id' => $etapaFormId,
                            'error' => $e->getMessage()
                        ]);
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
                $query->where('etapas.estado', 1)->orderBy('nro');
            },
            'etapas.tareas' => function($query) {
                $query->where('tareas.estado', 1);
            },
            'etapas.documentos' => function($query) {
                $query->where('documentos.estado', 1);
            },
            'etapas.etapaForms.form' => function($query) {
                $query->where('forms.estado', 1);
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

            $tareasCompletadas = 0;
            $totalTareas = 0;
            $documentosCompletados = 0;
            $totalDocumentos = 0;

            foreach ($etapa->tareas as $tarea) {
                // Buscar DetalleTarea vinculado a esta ejecución específica a través del detalle_etapa
                $detalleTarea = null;
                if ($detalleEtapa) {
                    $detalleTarea = DetalleTarea::with('userCreate')->where('id_tarea', $tarea->id)
                        ->where('id_detalle_etapa', $detalleEtapa->id)
                        ->whereNotIn('estado', [66, 99]) // Excluir tareas que no influyen en flujo (66) y canceladas (99)
                        ->first();
                }
                
                // Configurar estado de la tarea
                if ($detalleTarea) {
                    // Si existe detalle, usar su estado (solo estado 3 significa completado)
                    $tarea->completada = ($detalleTarea->estado == 3);
                    $tarea->detalle_id = $detalleTarea->id;
                    $tarea->detalle_flujo_id = $detalleFlujo->id;
                    $tarea->detalle = $detalleTarea; // Agregar referencia al detalle completo
                    
                    // Contar para progreso
                    $totalTareas++;
                    if ($detalleTarea->estado == 3) {
                        $tareasCompletadas++;
                    }
                } else {
                    // Si no existe detalle, mostrar como no completada (estado inicial)
                    $tarea->completada = false;
                    $tarea->detalle_id = null;
                    $tarea->detalle_flujo_id = $detalleFlujo->id;
                    $tarea->detalle = null;
                    
                    // Contar para progreso (tarea existe pero no completada)
                    $totalTareas++;
                }
            }
            
            foreach ($etapa->documentos as $documento) {
                // Buscar DetalleDocumento vinculado a esta ejecución específica a través del detalle_tarea
                $detalleDocumento = null;
                if ($detalleEtapa) {
                    $detalleDocumento = DetalleDocumento::with('userCreate')->where('id_documento', $documento->id)
                        ->whereHas('detalleTarea', function($query) use ($detalleEtapa) {
                            $query->where('id_detalle_etapa', $detalleEtapa->id);
                        })
                        ->whereNotIn('estado', [66, 99]) // Excluir documentos que no influyen en flujo (66) y cancelados (99)
                        ->first();
                }
                
                // Configurar estado del documento
                if ($detalleDocumento) {
                    // Si existe detalle, usar su estado (solo estado 3 significa subido/completado)
                    $documento->subido = ($detalleDocumento->estado == 3);
                    $documento->archivo_url = ($detalleDocumento && $detalleDocumento->ruta_doc) ? 
                        Storage::url($detalleDocumento->ruta_doc) : null;
                    $documento->detalle_id = $detalleDocumento->id;
                    $documento->detalle_flujo_id = $detalleFlujo->id;
                    $documento->detalle = $detalleDocumento; // Agregar referencia al detalle completo
                    
                    // Contar para progreso
                    $totalDocumentos++;
                    if ($detalleDocumento->estado == 3) {
                        $documentosCompletados++;
                    }
                } else {
                    // Si no existe detalle, mostrar como no subido (estado inicial)
                    $documento->subido = false;
                    $documento->archivo_url = null;
                    $documento->detalle_id = null;
                    $documento->detalle_flujo_id = $detalleFlujo->id;
                    $documento->detalle = null;
                    
                    // Contar para progreso (documento existe pero no completado)
                    $totalDocumentos++;
                }
            }
            
            // Procesar formularios (etapaForms) para esta ejecución específica
            $formulariosCompletados = 0;
            $totalFormularios = 0;
            
            foreach ($etapa->etapaForms as $etapaForm) {
                // Buscar FormRun específico para esta ejecución de flujo
                $formRun = null;
                
                // Aplicar misma lógica que nuevoFormulario para buscar FormRun específico
                if ($detalleFlujo && $detalleFlujo->id) {
                    $formRun = \App\Models\FormRun::where('id_etapas_forms', $etapaForm->id)
                        ->where('id_emp', $detalleFlujo->id_emp)
                        ->where('id_form', $etapaForm->form->id)
                        ->where(function($query) use ($detalleFlujo) {
                            $query->where('correlativo', 'LIKE', "DF{$detalleFlujo->id}-%")
                                  ->orWhere('created_by', $detalleFlujo->id);
                        })
                        ->first();
                        
                    Log::info('Procesando formulario en vista ejecutarDetalle:', [
                        'detalle_flujo_id' => $detalleFlujo->id,
                        'etapa_form_id' => $etapaForm->id,
                        'form_id' => $etapaForm->form->id,
                        'encontrado' => $formRun ? $formRun->id : 'NO'
                    ]);
                }
                
                // Configurar propiedades del etapaForm para la vista
                if ($formRun && $formRun->estado === 'completado') {
                    $etapaForm->formularioCompletado = true;
                    $etapaForm->formRun = $formRun;
                    $formulariosCompletados++;
                } else {
                    $etapaForm->formularioCompletado = false;
                    $etapaForm->formRun = $formRun; // Puede ser null
                }
                
                $totalFormularios++;
            }

            // Calcular progreso de la etapa incluyendo formularios
            $totalItems = $totalTareas + $totalDocumentos + $totalFormularios;
            $itemsCompletados = $tareasCompletadas + $documentosCompletados + $formulariosCompletados;
            
            if ($totalItems > 0) {
                $etapa->progreso_porcentaje = round(($itemsCompletados / $totalItems) * 100);
            } else {
                $etapa->progreso_porcentaje = 0;
            }
            
            // Log para debug
            Log::debug("Progreso etapa {$etapa->id}", [
                'tareas_completadas' => $tareasCompletadas,
                'total_tareas' => $totalTareas,
                'documentos_completados' => $documentosCompletados,
                'total_documentos' => $totalDocumentos,
                'formularios_completados' => $formulariosCompletados,
                'total_formularios' => $totalFormularios,
                'progreso_porcentaje' => $etapa->progreso_porcentaje
            ]);
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

            // Buscar la tarea para obtener su etapa y validar el rol
            $tarea = \App\Models\Tarea::findOrFail($tareaId);
            
            // Validar permisos por rol
            if ($tarea->rol_cambios && $tarea->rol_cambios != $user->id_rol) {
                return response()->json([
                    'error' => 'No tienes permisos para modificar esta tarea. Se requiere el rol: ' . 
                              ($tarea->rol ? $tarea->rol->nombre : 'Rol específico')
                ], 403);
            }
            
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
                if ($completada) {
                    // Marcar como completado
                    $detalle->update([
                        'estado' => 3, // 3 = completado
                        'id_user_create' => $user->id,
                        'updated_at' => now()
                    ]);
                    Log::info('Tarea marcada como completada', [
                        'detalle_id' => $detalle->id,
                        'estado_anterior' => $detalle->getOriginal('estado'),
                        'estado_nuevo' => 3,
                        'usuario' => $user->id
                    ]);
                } else {
                    // Desmarcar: cambiar a estado 0 (inicial) y limpiar usuario
                    $detalle->update([
                        'estado' => 0, // 0 = inicial/sin completar
                        'id_user_create' => null, // Limpiar usuario que completó
                        'updated_at' => now()
                    ]);
                    Log::info('Tarea desmarcada a estado inicial', [
                        'detalle_id' => $detalle->id,
                        'estado_anterior' => $detalle->getOriginal('estado'),
                        'estado_nuevo' => 0,
                        'usuario_anterior' => $detalle->getOriginal('id_user_create'),
                        'usuario_nuevo' => null
                    ]);
                }
                Log::info('Detalle actualizado', ['detalle_id' => $detalle->id, 'estado' => $detalle->estado, 'updated_by' => $user->id]);
            } else {
                // Crear nuevo detalle para esta ejecución específica
                $detalle = DetalleTarea::create([
                    'id_tarea' => $tareaId,
                    'id_detalle_etapa' => $detalleEtapa->id,
                    'estado' => $completada ? 3 : 0, // 3 = completado, 0 = inicial
                    'id_user_create' => $completada ? $user->id : null, // Solo asignar usuario si se completa
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                Log::info('Detalle creado', ['detalle_id' => $detalle->id, 'estado' => $detalle->estado, 'created_by' => $user->id]);
            }

            return response()->json([
                'success' => true,
                'message' => $completada ? 'Tarea marcada como completada' : 'Tarea regresada a estado inicial',
                'completada' => ($detalle->estado == 3), // Verificar que sea exactamente 3
                'detalle_id' => $detalle->id,
                'usuario' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email
                ],
                'fecha_completada' => $detalle->updated_at->format('d/m/Y H:i:s'),
                'fecha_completada_legible' => $detalle->updated_at->format('d/m/Y'),
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
                'tareas.*.completada' => 'required|boolean',
                'documentos' => 'nullable|array',
                'documentos.*.documento_id' => 'required|exists:documentos,id',
                'documentos.*.validado' => 'required|boolean'
            ]);
            
            $etapaId = $request->etapa_id;
            $detalleFlujoId = $request->detalle_flujo_id;
            $tareas = $request->tareas ?? [];
            $documentos = $request->documentos ?? [];

            Log::info('Datos validados para grabar etapa', [
                'etapa_id' => $etapaId,
                'detalle_flujo_id' => $detalleFlujoId,
                'tareas_count' => count($tareas),
                'documentos_count' => count($documentos),
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
            $documentosActualizados = 0;
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
                            'id_user_create' => $user->id,
                            'updated_at' => now()
                        ]);
                    } else {
                        // Crear nuevo detalle
                    $detalle = DetalleTarea::create([
                        'id_tarea' => $tareaId,
                        'id_detalle_etapa' => $detalleEtapa->id,
                        'estado' => $completada ? 3 : 2,
                        'id_user_create' => $user->id,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }

                    $tareasActualizadas++;
                    Log::info('Tarea procesada', ['tarea_id' => $tareaId, 'completada' => $completada, 'detalle_id' => $detalle->id]);
                }
            }

            // Procesar cada documento (si existen)
            if (!empty($documentos)) {
                foreach ($documentos as $documentoData) {
                    $documentoId = $documentoData['documento_id'];
                    $validado = $documentoData['validado'];

                    // Verificar que el documento pertenece a la etapa
                    $documento = \App\Models\Documento::where('id', $documentoId)
                        ->where('id_etapa', $etapaId)
                        ->first();
                    
                    if (!$documento) {
                        Log::warning('Documento no pertenece a la etapa', ['documento_id' => $documentoId, 'etapa_id' => $etapaId]);
                        continue;
                    }

                    // Buscar si ya existe un detalle para este documento
                    $detalle = DetalleDocumento::where('id_documento', $documentoId)
                        ->whereHas('detalleTarea', function($query) use ($detalleEtapa) {
                            $query->where('id_detalle_etapa', $detalleEtapa->id);
                        })
                        ->first();
                    
                    if ($detalle) {
                        // Solo actualizar si tiene archivo y se está validando
                        if ($detalle->ruta_doc && $validado) {
                            $detalle->update([
                                'estado' => 3, // 3 = validado/completado
                                'id_user_create' => $user->id,
                                'updated_at' => now()
                            ]);
                            $documentosActualizados++;
                            Log::info('Documento validado', ['documento_id' => $documentoId, 'detalle_id' => $detalle->id]);
                        } elseif (!$validado && $detalle->estado == 3) {
                            // Si se desmarca un documento previamente validado
                            $detalle->update([
                                'estado' => 2, // 2 = archivo subido pero no validado
                                'id_user_create' => $user->id,
                                'updated_at' => now()
                            ]);
                            $documentosActualizados++;
                            Log::info('Documento desvalidado', ['documento_id' => $documentoId, 'detalle_id' => $detalle->id]);
                        }
                    }
                }
            }

            // Verificar estados después de procesar tareas y documentos
            if ($tareasActualizadas > 0 || $documentosActualizados > 0) {
                // Usar la primera tarea o documento para verificar estados
                if ($tareasActualizadas > 0) {
                    $primeraTaskaId = $tareas[0]['tarea_id'];
                    $estadosResult = $this->verificarYActualizarEstados($primeraTaskaId, 'tarea', $detalleFlujoId);
                } elseif ($documentosActualizados > 0) {
                    $primerDocumentoId = $documentos[0]['documento_id'];
                    $estadosResult = $this->verificarYActualizarEstados($primerDocumentoId, 'documento', $detalleFlujoId);
                }
                
                if (isset($estadosResult) && is_array($estadosResult) && isset($estadosResult['flujo_completado'])) {
                    $flujoCompletado = $estadosResult;
                } elseif (isset($estadosResult) && $estadosResult === true) {
                    $etapaCompletada = true;
                }
            } else {
                // Si no hay tareas ni documentos, verificar si solo hay documentos y están completos
                $totalDocumentos = $etapa->documentos()->where('documentos.estado', 1)->count();
                $documentosCompletos = DetalleDocumento::whereHas('documento', function($q) use ($etapaId) {
                        // Nueva lógica: los documentos pertenecen a tareas, no directamente a etapas
                        $q->whereHas('tarea', function($tq) use ($etapaId) {
                            $tq->where('tareas.id_etapa', $etapaId);
                        })->where('documentos.estado', 1);
                    })
                    ->whereHas('detalleTarea', function($query) use ($detalleEtapa) {
                        $query->where('id_detalle_etapa', $detalleEtapa->id);
                    })
                    ->where('estado', 3) // Solo documentos validados
                    ->count();
                
                if ($totalDocumentos > 0 && $documentosCompletos == $totalDocumentos) {
                    // Verificar estados usando cualquier documento de la etapa
                    $primerDocumento = $etapa->documentos()->where('documentos.estado', 1)->first();
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
                'documentos_actualizados' => $documentosActualizados,
                'etapa_completada' => $etapaCompletada,
                'flujo_completado' => $flujoCompletado
            ]);

            return response()->json([
                'success' => true,
                'message' => ($tareasActualizadas > 0 || $documentosActualizados > 0) 
                    ? "Etapa grabada correctamente. {$tareasActualizadas} tareas y {$documentosActualizados} documentos actualizados."
                    : "Etapa grabada correctamente.",
                'tareas_actualizadas' => $tareasActualizadas,
                'documentos_actualizados' => $documentosActualizados,
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
                'archivo' => 'required|file|mimes:pdf,doc,docx,xls,xlsx,csv,ppt,pptx,png,jpg,jpeg,gif,bmp,webp|max:10240', // 10MB máximo
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

            // Buscar el documento para validar permisos, cargando las relaciones necesarias
            $documento = \App\Models\Documento::with(['tarea', 'tarea.etapa'])->find($documentoId);
            if (!$documento) {
                return response()->json(['error' => 'Documento no encontrado'], 404);
            }

            // Validar permisos por rol
            if ($documento->rol_cambios && $documento->rol_cambios != $user->id_rol) {
                return response()->json([
                    'error' => 'No tienes permisos para subir este documento. Se requiere el rol: ' . 
                              ($documento->rol ? $documento->rol->nombre : 'Rol específico')
                ], 403);
            }

            // Crear directorio si no existe
            $directorio = 'documentos/ejecucion/' . $detalleFlujoId . '/' . date('Y/m');
            
            // Generar nombre único para el archivo
            $nombreArchivo = time() . '_' . $documentoId . '_' . $archivo->getClientOriginalName();
            
            // Guardar archivo
            $rutaArchivo = $archivo->storeAs($directorio, $nombreArchivo, 'public');

            // En el nuevo enfoque, necesitamos encontrar el DetalleTarea directamente
            // basándonos en la tarea del documento y la ejecución actual
            $tarea = $documento->tarea;
            if (!$tarea) {
                throw new \Exception('Tarea del documento no encontrada');
            }

            // Buscar el detalle_etapa correspondiente a esta ejecución a través de la tarea
            $detalleEtapa = DetalleEtapa::where('id_etapa', $tarea->id_etapa)
                ->where('id_detalle_flujo', $detalleFlujoId)
                ->first();

            if (!$detalleEtapa) {
                throw new \Exception('Detalle de etapa no encontrado para esta ejecución');
            }

            // Buscar o crear el DetalleTarea correspondiente
            $detalleTarea = DetalleTarea::where('id_tarea', $tarea->id)
                ->where('id_detalle_etapa', $detalleEtapa->id)
                ->first();

            if (!$detalleTarea) {
                throw new \Exception('DetalleTarea no encontrado para esta ejecución');
            }

            // Verificar si ya existe un detalle para este documento en esta ejecución específica
            $detalle = DetalleDocumento::where('id_documento', $documentoId)
                ->where('id_detalle_tarea', $detalleTarea->id)
                ->first();

            if ($detalle) {
                // Actualizar el existente - solo guardar el archivo, no cambiar estado hasta que se grabe la etapa
                $detalle->update([
                    'ruta_doc' => $rutaArchivo,
                    'archivo_url' => Storage::url($rutaArchivo),
                    'nombre_archivo' => $archivo->getClientOriginalName(),
                    'comentarios' => $request->comentarios,
                    'id_user_create' => $user->id,
                    'updated_at' => now()
                ]);
            } else {
                // Crear nuevo detalle para esta ejecución específica - estado 2 (pendiente de validación)
                $detalle = DetalleDocumento::create([
                    'id_documento' => $documentoId,
                    'id_detalle_tarea' => $detalleTarea->id, // Nueva relación a través de DetalleTarea
                    'estado' => 2, // 2 = archivo subido, pendiente de validación con "Grabar"
                    'ruta_doc' => $rutaArchivo,
                    'archivo_url' => Storage::url($rutaArchivo),
                    'nombre_archivo' => $archivo->getClientOriginalName(),
                    'comentarios' => $request->comentarios,
                    'id_user_create' => $user->id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Documento subido correctamente. Presiona "Grabar Cambios" para validar.',
                'archivo_url' => Storage::url($rutaArchivo),
                'nombre_archivo' => $archivo->getClientOriginalName(),
                'detalle_id' => $detalle->id,
                'usuario' => [
                    'name' => $user->name,
                    'id' => $user->id
                ],
                'fecha_subida' => $detalle->updated_at->format('d/m/Y H:i'),
                'estado_temporal' => true // Indica que está en estado temporal, necesita validación
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

        // Buscar ejecución activa de este flujo
        $detalleFlujoActivo = DetalleFlujo::where('id_flujo', $flujo->id)
            ->when(!$isSuper, fn($q) => $q->where('id_emp', $user->id_emp))
            ->whereIn('estado', [2, 3, 4]) // En proceso, completado, pausado
            ->orderBy('updated_at', 'desc')
            ->first();

        if (!$detalleFlujoActivo) {
            return response()->json([
                'progreso_general' => 0,
                'etapas' => [],
                'mensaje' => 'No hay ejecuciones activas para este flujo'
            ]);
        }

        // Usar el método progreso existente que maneja DetalleFlujo
        $progresoResponse = $this->progreso($detalleFlujoActivo);
        
        // Como progreso() retorna un JsonResponse, necesitamos obtener los datos
        $responseData = json_decode($progresoResponse->getContent(), true);
        
        return response()->json($responseData);
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
                
                // Obtener etapa a través de la tarea
                $tarea = $documento->tarea;
                if (!$tarea) return false;
                $etapa = $tarea->etapa;
            }

            if (!$etapa || !$detalleFlujoId) return false;

            // Buscar el detalle_etapa para esta ejecución específica
            $detalleEtapa = DetalleEtapa::where('id_etapa', $etapa->id)
                ->where('id_detalle_flujo', $detalleFlujoId)
                ->first();

            if (!$detalleEtapa) return false;

            // Verificar si todas las tareas de la etapa están completadas para esta ejecución (excluyendo las que no influyen en flujo y canceladas)
            $totalTareas = DetalleTarea::where('id_detalle_etapa', $detalleEtapa->id)
                ->whereNotIn('estado', [66, 99]) // Excluir tareas que no influyen en flujo (66) y canceladas (99)
                ->count();
            $tareasCompletadas = DetalleTarea::where('estado', 3) // Solo estado 3 es completado
                ->where('id_detalle_etapa', $detalleEtapa->id)
                ->whereNotIn('estado', [66, 99]) // Excluir tareas que no influyen en flujo (66) y canceladas (99)
                ->count();

            // Verificar si todos los documentos de la etapa están subidos para esta ejecución (excluyendo los que no influyen en flujo y cancelados)
            // Nueva lógica: documentos están relacionados a través de DetalleTarea
            $totalDocumentos = DetalleDocumento::whereHas('detalleTarea', function($query) use ($detalleEtapa) {
                    $query->where('id_detalle_etapa', $detalleEtapa->id);
                })
                ->whereNotIn('estado', [66, 99]) // Excluir documentos que no influyen en flujo (66) y cancelados (99)  
                ->count();
            $documentosSubidos = DetalleDocumento::where('estado', 3) // Solo estado 3 es subido
                ->whereHas('detalleTarea', function($query) use ($detalleEtapa) {
                    $query->where('id_detalle_etapa', $detalleEtapa->id);
                })
                ->whereNotIn('estado', [66, 99]) // Excluir documentos que no influyen en flujo (66) y cancelados (99)
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
                            ->where('estado', 3) // Solo etapas completadas (estado 3 no puede ser 99)
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
            
            // Buscar el detalle_etapa correspondiente a través de la tarea
            $tarea = $documento->tarea;
            if (!$tarea) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tarea del documento no encontrada'
                ], 404);
            }
            
            $detalleEtapa = DetalleEtapa::where('id_etapa', $tarea->id_etapa)
                ->where('id_detalle_flujo', $detalleFlujoId)
                ->firstOrFail();

            // Buscar el detalle_documento
            $detalleDocumento = DetalleDocumento::where('id_documento', $documentoId)
                ->whereHas('detalleTarea', function($query) use ($detalleEtapa) {
                    $query->where('id_detalle_etapa', $detalleEtapa->id);
                })
                ->first();

            if (!$detalleDocumento) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró el documento en esta ejecución'
                ], 404);
            }

            if (!$detalleDocumento->ruta_doc) {
                return response()->json([
                    'success' => false,
                    'message' => 'El documento no tiene archivo para eliminar'
                ], 400);
            }

            DB::beginTransaction();

            // Eliminar archivo físico del storage
            if ($detalleDocumento->ruta_doc && Storage::disk('public')->exists($detalleDocumento->ruta_doc)) {
                Storage::disk('public')->delete($detalleDocumento->ruta_doc);
                Log::info('Archivo físico eliminado', ['path' => $detalleDocumento->ruta_doc]);
            }

            // Actualizar el detalle_documento - resetear a estado inicial
            $detalleDocumento->update([
                'archivo_url' => null,
                'nombre_archivo' => null,
                'ruta_doc' => null,
                'comentarios' => $motivo ? "Eliminado: " . $motivo : "Documento eliminado",
                'estado' => 0, // Volver a estado inicial/pendiente
                'id_user_create' => $user->id,
                'updated_at' => now()
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
            'etapas.documentos',
            'etapas.etapaForms.form'
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
            $tareas_data = [];
            
            // Buscar detalle_etapa para esta ejecución
            $detalleEtapa = DetalleEtapa::where('id_etapa', $etapa->id)
                ->where('id_detalle_flujo', $detalleFlujo->id)
                ->first();
            
            // Obtener solo las tareas que influyen en el flujo (excluir estado 66)
            $tareasActivasIds = [];
            if ($detalleEtapa) {
                $tareasActivasIds = DetalleTarea::where('id_detalle_etapa', $detalleEtapa->id)
                    ->whereNotIn('estado', [66, 99]) // Excluir tareas que no influyen en flujo (66) y canceladas (99)
                    ->pluck('id_tarea')
                    ->toArray();
            }
            $total_tareas = count($tareasActivasIds);
            
            foreach ($etapa->tareas as $tarea) {
                // Solo procesar tareas que están activas en el flujo
                if (!in_array($tarea->id, $tareasActivasIds)) {
                    continue;
                }
                
                $tarea_completada = false;
                $usuario_completo = null;
                $fecha_completada = null;
                
                if ($detalleEtapa) {
                    $detalle = DetalleTarea::with('userCreate')->where('id_tarea', $tarea->id)
                        ->where('id_detalle_etapa', $detalleEtapa->id)
                        ->whereNotIn('estado', [66, 99]) // Excluir tareas que no influyen en flujo (66) y canceladas (99)
                        ->first();
                    if ($detalle && $detalle->estado == 3) { // Solo estado 3 es completado
                        $tareas_completadas++;
                        $items_completados++;
                        $tarea_completada = true;
                        
                        if ($detalle->userCreate) {
                            $usuario_completo = [
                                'id' => $detalle->userCreate->id,
                                'name' => $detalle->userCreate->name,
                                'email' => $detalle->userCreate->email
                            ];
                        }
                        $fecha_completada = $detalle->updated_at ? $detalle->updated_at->format('d/m/Y') : null;
                    }
                }
                $total_items++;
                
                $tareas_data[] = [
                    'id' => $tarea->id,
                    'completada' => $tarea_completada,
                    'usuario_completo' => $usuario_completo,
                    'fecha_completada' => $fecha_completada
                ];
            }

            $documentos_subidos = 0;
            $documentos_data = [];
            
            // Obtener solo los documentos que influyen en el flujo (excluir estado 66)
            $documentosActivosIds = [];
            if ($detalleEtapa) {
                $documentosActivosIds = DetalleDocumento::whereHas('detalleTarea', function($query) use ($detalleEtapa) {
                        $query->where('id_detalle_etapa', $detalleEtapa->id);
                    })
                    ->whereNotIn('estado', [66, 99]) // Excluir documentos que no influyen en flujo (66) y cancelados (99)
                    ->pluck('id_documento')
                    ->toArray();
            }
            $total_documentos = count($documentosActivosIds);
            
            if ($detalleEtapa) {
                foreach ($etapa->documentos as $documento) {
                    // Solo procesar documentos que están activos en el flujo
                    if (!in_array($documento->id, $documentosActivosIds)) {
                        continue;
                    }
                    
                    $documento_subido = false;
                    $usuario_validado = null;
                    $fecha_validada = null;
                    
                    $detalle = DetalleDocumento::with('userCreate')->where('id_documento', $documento->id)
                        ->whereHas('detalleTarea', function($query) use ($detalleEtapa) {
                            $query->where('id_detalle_etapa', $detalleEtapa->id);
                        })
                        ->whereNotIn('estado', [66, 99]) // Excluir documentos que no influyen en flujo (66) y cancelados (99)
                        ->first();
                    if ($detalle && $detalle->estado == 3) { // Solo estado 3 es subido
                        $documentos_subidos++;
                        $items_completados++;
                        $documento_subido = true;
                        
                        if ($detalle->userCreate) {
                            $usuario_validado = [
                                'id' => $detalle->userCreate->id,
                                'name' => $detalle->userCreate->name,
                                'email' => $detalle->userCreate->email
                            ];
                        }
                        $fecha_validada = $detalle->updated_at ? $detalle->updated_at->format('d/m/Y') : null;
                    }
                    $total_items++;
                    
                    $documentos_data[] = [
                        'id' => $documento->id,
                        'subido' => $documento_subido,
                        'validado' => $documento_subido, // Para este caso, subido = validado
                        'usuario_validado' => $usuario_validado,
                        'fecha_validada' => $fecha_validada
                    ];
                }
            } else {
                // Si no hay detalle_etapa, no hay documentos activos
                // No agregar documentos al total porque no están incluidos en el flujo
            }

            // Obtener información de formularios
            $formularios_completados = 0;
            $formularios_data = [];
            $total_formularios = $etapa->etapaForms()->count();
            
            if ($total_formularios > 0) {
                foreach ($etapa->etapaForms as $etapaForm) {
                    // Buscar FormRun específico para esta ejecución de flujo
                    $formRun = null;
                    
                    // Aplicar misma lógica que nuevoFormulario para buscar FormRun específico
                    if ($detalleFlujo && $detalleFlujo->id) {
                        $formRun = \App\Models\FormRun::where('id_etapas_forms', $etapaForm->id)
                            ->where('id_emp', $detalleFlujo->id_emp)
                            ->where('id_form', $etapaForm->form->id)
                            ->where('correlativo', 'LIKE', "DF{$detalleFlujo->id}-%")
                            ->first();
                            
                        Log::info('Buscando FormRun para vista - Ejecución específica:', [
                            'detalle_flujo_id' => $detalleFlujo->id,
                            'etapa_form_id' => $etapaForm->id,
                            'encontrado' => $formRun ? $formRun->id : 'NO'
                        ]);
                    }
                    
                    $formulario_completado = false;
                    $correlativo = null;
                    $fecha_completada = null;
                    
                    if ($formRun && $formRun->estado === 'completado') {
                        $formularios_completados++;
                        $items_completados++;
                        $formulario_completado = true;
                        $correlativo = $formRun->correlativo;
                        $fecha_completada = $formRun->updated_at ? $formRun->updated_at->format('d/m/Y') : null;
                    }
                    
                    $total_items++;
                    
                    $formularios_data[] = [
                        'id' => $etapaForm->id,
                        'form_id' => $etapaForm->form->id,
                        'nombre' => $etapaForm->form->nombre,
                        'completado' => $formulario_completado,
                        'estado' => $formRun ? $formRun->estado : 'pendiente',
                        'correlativo' => $correlativo,
                        'fecha_completada' => $fecha_completada
                    ];
                }
            }

            $progreso_etapa = 0;
            if (($total_tareas + $total_documentos + $total_formularios) > 0) {
                $progreso_etapa = round((($tareas_completadas + $documentos_subidos + $formularios_completados) / ($total_tareas + $total_documentos + $total_formularios)) * 100);
            }

            $etapas_data[] = [
                'id' => $etapa->id,
                'nombre' => $etapa->nombre,
                'progreso' => $progreso_etapa,
                'tareas_completadas' => $tareas_completadas,
                'total_tareas' => $total_tareas,
                'documentos_subidos' => $documentos_subidos,
                'total_documentos' => $total_documentos,
                'formularios_completados' => $formularios_completados,
                'total_formularios' => $total_formularios,
                'estado' => $detalleEtapa ? $detalleEtapa->estado : 1, // Estado de BD: 1=Pendiente, 2=En progreso, 3=Completada
                'tareas' => $tareas_data,
                'documentos' => $documentos_data,
                'formularios' => $formularios_data
            ];
        }

        if ($total_items > 0) {
            $progreso_general = round(($items_completados / $total_items) * 100);
        }

        // Crear un array de estados indexado por ID de etapa para facilitar acceso desde JavaScript
        $estados_por_etapa = [];
        foreach ($etapas_data as $etapa_data) {
            $estados_por_etapa[$etapa_data['id']] = [
                'estado' => $etapa_data['estado'],
                'progreso' => $etapa_data['progreso']
            ];
        }

        return response()->json([
            'progreso_general' => $progreso_general,
            'etapas' => $estados_por_etapa, // Estados indexados por ID para JavaScript
            'etapas_detalle' => $etapas_data, // Información completa de etapas
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
                // Nueva lógica: usar la relación a través de DetalleTarea
                $detalleTareaIds = DetalleTarea::whereIn('id_detalle_etapa', $detalleEtapaIds)
                    ->pluck('id')
                    ->toArray();
                
                if (!empty($detalleTareaIds)) {
                    DetalleDocumento::whereIn('id_detalle_tarea', $detalleTareaIds)
                        ->update(['estado' => 99]);
                }
            }

            DB::commit();

            Log::info('Ejecución cancelada con actualización en cascada', [
                'detalle_flujo_id' => $detalleFlujo->id,
                'usuario_id' => $user->id,
                'motivo' => $request->motivo,
                'fecha_cancelacion' => now(),
                'detalle_etapas_actualizadas' => count($detalleEtapaIds),
                'detalle_tareas_actualizadas' => !empty($detalleEtapaIds) ? DetalleTarea::whereIn('id_detalle_etapa', $detalleEtapaIds)->count() : 0,
                'detalle_documentos_actualizados' => !empty($detalleTareaIds) ? DetalleDocumento::whereIn('id_detalle_tarea', $detalleTareaIds)->count() : 0
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

    /**
     * Validar o invalidar un documento individual
     */
    public function validarDocumento(Request $request)
    {
        try {
            $user = Auth::user();
            $isSuper = ($user->rol->nombre === 'SUPERADMIN');

            // SUPERADMIN no puede actualizar documentos
            if ($isSuper) {
                return response()->json(['error' => 'Los SUPERADMIN no pueden modificar documentos'], 403);
            }

            Log::info('Iniciando validación de documento', $request->all());
            
            $request->validate([
                'documento_id' => 'required|exists:documentos,id',
                'validado' => 'required|boolean',
                'detalle_flujo_id' => 'required|exists:detalle_flujo,id'
            ]);
            
            $documentoId = $request->documento_id;
            $validado = $request->validado;
            $detalleFlujoId = $request->detalle_flujo_id;

            Log::info('Datos validados', [
                'documento_id' => $documentoId, 
                'validado' => $validado, 
                'detalle_flujo_id' => $detalleFlujoId,
                'user_id' => $user->id
            ]);

            // Verificar que el detalle_flujo pertenece a la empresa del usuario
            $detalleFlujo = DetalleFlujo::where('id', $detalleFlujoId)
                ->where('id_emp', $user->id_emp)
                ->firstOrFail();

            // Verificar que la ejecución no esté cancelada
            if ($detalleFlujo->estado == 99) {
                return response()->json(['error' => 'No se pueden modificar documentos de una ejecución cancelada'], 400);
            }

            // Buscar el documento para obtener su etapa y validar el rol
            $documento = \App\Models\Documento::findOrFail($documentoId);
            
            // Validar permisos por rol
            if ($documento->rol_validacion && $documento->rol_validacion != $user->id_rol) {
                return response()->json([
                    'error' => 'No tienes permisos para validar este documento. Se requiere el rol: ' . 
                              ($documento->rol ? $documento->rol->nombre : 'Rol específico')
                ], 403);
            }
            
            // Buscar el detalle_etapa correspondiente a través de la tarea del documento
            $detalleEtapa = DetalleEtapa::where('id_etapa', $documento->tarea->id_etapa)
                ->where('id_detalle_flujo', $detalleFlujoId)
                ->firstOrFail();

            // Buscar si ya existe un detalle para este documento en esta ejecución específica
            $detalle = DetalleDocumento::where('id_documento', $documentoId)
                ->whereHas('detalleTarea', function($query) use ($detalleEtapa) {
                    $query->where('id_detalle_etapa', $detalleEtapa->id);
                })
                ->first();
            
            if ($detalle) {
                // Actualizar el existente
                if ($validado) {
                    // Marcar como validado
                    $detalle->update([
                        'estado' => 3, // 3 = validado
                        'id_user_create' => $user->id,
                        'updated_at' => now()
                    ]);
                    Log::info('Documento marcado como validado', [
                        'detalle_id' => $detalle->id,
                        'estado_anterior' => $detalle->getOriginal('estado'),
                        'estado_nuevo' => 3,
                        'usuario' => $user->id
                    ]);
                } else {
                    // Desmarcar: cambiar a estado 0, limpiar usuario y eliminar archivo
                    $rutaAnterior = $detalle->ruta_doc;
                    
                    // Eliminar archivo físico si existe
                    if ($rutaAnterior && Storage::exists($rutaAnterior)) {
                        Storage::delete($rutaAnterior);
                        Log::info('Archivo de documento eliminado', [
                            'ruta_eliminada' => $rutaAnterior,
                            'detalle_id' => $detalle->id
                        ]);
                    }
                    
                    $detalle->update([
                        'estado' => 0, // 0 = inicial/sin validar
                        'id_user_create' => null, // Limpiar usuario que validó
                        'ruta_doc' => null, // Limpiar ruta del documento
                        'updated_at' => now()
                    ]);
                    
                    Log::info('Documento desmarcado a estado inicial', [
                        'detalle_id' => $detalle->id,
                        'estado_anterior' => $detalle->getOriginal('estado'),
                        'estado_nuevo' => 0,
                        'usuario_anterior' => $detalle->getOriginal('id_user_create'),
                        'usuario_nuevo' => null,
                        'ruta_anterior' => $rutaAnterior,
                        'ruta_nueva' => null
                    ]);
                }
                Log::info('Detalle de documento actualizado', ['detalle_id' => $detalle->id, 'estado' => $detalle->estado, 'updated_by' => $user->id]);
            } else {
                // Crear nuevo detalle para esta ejecución específica
                $detalle = DetalleDocumento::create([
                    'id_documento' => $documentoId,
                    'id_detalle_etapa' => $detalleEtapa->id,
                    'estado' => $validado ? 3 : 0, // 3 = validado, 0 = inicial
                    'id_user_create' => $validado ? $user->id : null, // Solo asignar usuario si se valida
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                Log::info('Detalle de documento creado', ['detalle_id' => $detalle->id, 'estado' => $detalle->estado, 'created_by' => $user->id]);
            }

            return response()->json([
                'success' => true,
                'message' => $validado ? 'Documento marcado como validado' : 'Documento regresado a estado inicial',
                'validado' => ($detalle->estado == 3), // Verificar que sea exactamente 3
                'detalle_id' => $detalle->id,
                'usuario' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email
                ],
                'fecha_validada' => $detalle->updated_at->format('d/m/Y H:i:s'),
                'fecha_validada_legible' => $detalle->updated_at->format('d/m/Y'),
                'estados' => $this->verificarYActualizarEstados($documentoId, 'documento', $detalleFlujoId)
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Error de validación en validar documento: ' . $e->getMessage(), [
                'request' => $request->all(),
                'errors' => $e->errors()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error de validación: ' . implode(', ', collect($e->errors())->flatten()->toArray())
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error validando documento: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al validar el documento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Re-ejecutar un flujo completo creando una nueva ejecución
     */
    public function reEjecutarFlujo(Request $request, Flujo $flujo)
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

        // Verificar que el flujo esté configurado (estado 1)
        if ($flujo->estado != 1) {
            return response()->json(['error' => 'El flujo no está disponible para ejecución'], 400);
        }

        // Validar datos del formulario
        $request->validate([
            'nombre' => 'required|string|max:255'
        ]);

        try {
            DB::beginTransaction();

            // Usar el nombre proporcionado por el usuario
            $nombreEjecucion = trim($request->nombre);

            // Crear nuevo registro de ejecución completa
            $nuevaEjecucion = DetalleFlujo::create([
                'nombre' => $nombreEjecucion,
                'id_flujo' => $flujo->id,
                'id_emp' => $user->id_emp,
                'id_user_create' => $user->id,
                'estado' => 2 // En ejecución
            ]);

            Log::info('Nueva ejecución completa creada', [
                'detalle_flujo_id' => $nuevaEjecucion->id,
                'nombre_ejecucion' => $nombreEjecucion,
                'flujo_original_id' => $flujo->id,
                'user_id' => $user->id
            ]);

            // Crear registros de detalle_etapa para cada etapa activa del flujo
            foreach ($flujo->etapas()->where('estado', 1)->orderBy('nro')->get() as $etapa) {
                $detalleEtapa = DetalleEtapa::create([
                    'id_etapa' => $etapa->id,
                    'id_detalle_flujo' => $nuevaEjecucion->id,
                    'estado' => 2 // En ejecución
                ]);

                Log::info('Etapa agregada a nueva ejecución', [
                    'etapa_id' => $etapa->id,
                    'detalle_etapa_id' => $detalleEtapa->id,
                    'etapa_numero' => $etapa->nro
                ]);

                // Crear registros de detalle_tarea para TODAS las tareas activas de la etapa (estado = 0, incluidas en flujo)
                foreach ($etapa->tareas()->where('estado', 1)->get() as $tarea) {
                    DetalleTarea::create([
                        'id_tarea' => $tarea->id,
                        'id_detalle_etapa' => $detalleEtapa->id,
                        'estado' => 0, // 0 = activa en flujo, pendiente de completar
                        'id_user_create' => $user->id
                    ]);
                    
                    Log::info('Tarea agregada a nueva ejecución', [
                        'tarea_id' => $tarea->id,
                        'tarea_nombre' => $tarea->nombre,
                        'estado' => 0
                    ]);
                }

                // Crear registros de detalle_documento para TODOS los documentos activos de la etapa (estado = 0, incluidos en flujo)
                foreach ($etapa->documentos()->where('documentos.estado', 1)->get() as $documento) {
                    DetalleDocumento::create([
                        'id_documento' => $documento->id,
                        'id_detalle_etapa' => $detalleEtapa->id,
                        'estado' => 0, // 0 = activo en flujo, pendiente de subir
                        'id_user_create' => $user->id
                    ]);
                    
                    Log::info('Documento agregado a nueva ejecución', [
                        'documento_id' => $documento->id,
                        'documento_nombre' => $documento->nombre,
                        'estado' => 0
                    ]);
                }
            }

            DB::commit();

            Log::info('Re-ejecución de flujo completada exitosamente', [
                'nueva_ejecucion_id' => $nuevaEjecucion->id,
                'flujo_id' => $flujo->id,
                'nombre_personalizado' => $nombreEjecucion,
                'total_etapas' => $flujo->etapas()->where('estado', 1)->count(),
                'total_tareas' => $flujo->etapas()->where('estado', 1)->get()->sum(function($etapa) {
                    return $etapa->tareas()->where('estado', 1)->count();
                }),
                'total_documentos' => $flujo->etapas()->where('estado', 1)->get()->sum(function($etapa) {
                    return $etapa->documentos()->where('documentos.estado', 1)->count();
                })
            ]);

            return response()->json([
                'success' => true,
                'redirect_url' => route('ejecucion.detalle.ejecutar', $nuevaEjecucion->id),
                'detalle_flujo_id' => $nuevaEjecucion->id,
                'mensaje' => "Ejecución '{$nombreEjecucion}' creada exitosamente"
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollback();
            return response()->json([
                'error' => 'Datos inválidos: ' . implode(', ', collect($e->errors())->flatten()->toArray())
            ], 422);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error al re-ejecutar flujo completo', [
                'error' => $e->getMessage(),
                'flujo_id' => $flujo->id,
                'user_id' => $user->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['error' => 'Error al crear la nueva ejecución'], 500);
        }
    }

    /**
     * Calcula el progreso simplificado para el índice
     * Basado en la lógica del método progreso() pero sin retornar JSON
     */
    private function calcularProgresoSimple(DetalleFlujo $detalleFlujo)
    {
        $flujo = $detalleFlujo->flujo;
        if (!$flujo) {
            return 0;
        }

        $items_completados = 0;
        $total_items = 0;

        foreach ($flujo->etapas as $etapa) {
            // Buscar detalle_etapa para esta ejecución
            $detalleEtapa = DetalleEtapa::where('id_etapa', $etapa->id)
                ->where('id_detalle_flujo', $detalleFlujo->id)
                ->first();
            
            if (!$detalleEtapa) {
                continue;
            }

            // Obtener solo las tareas que influyen en el flujo (excluir estado 66)
            $tareasActivasIds = DetalleTarea::where('id_detalle_etapa', $detalleEtapa->id)
                ->whereNotIn('estado', [66, 99]) // Excluir tareas que no influyen en flujo (66) y canceladas (99)
                ->pluck('id_tarea')
                ->toArray();

            $total_items += count($tareasActivasIds);

            // Contar tareas completadas
            $tareasCompletadas = DetalleTarea::where('id_detalle_etapa', $detalleEtapa->id)
                ->whereIn('id_tarea', $tareasActivasIds)
                ->where('estado', 3)
                ->count();

            $items_completados += $tareasCompletadas;

            // Contar documentos validados
            foreach ($etapa->tareas as $tarea) {
                if (!in_array($tarea->id, $tareasActivasIds)) {
                    continue;
                }

                $detalleTarea = DetalleTarea::where('id_tarea', $tarea->id)
                    ->where('id_detalle_etapa', $detalleEtapa->id)
                    ->whereNotIn('estado', [66, 99])
                    ->first();

                if ($detalleTarea) {
                    $documentosEtapa = $tarea->documentos()->where('documentos.estado', 1)->get();
                    
                    foreach ($documentosEtapa as $documento) {
                        $total_items++;
                        
                        $documentoSubido = DetalleDocumento::where('id_documento', $documento->id)
                            ->where('id_detalle_tarea', $detalleTarea->id)
                            ->where('estado', 3) // Solo documentos validados
                            ->first();

                        if ($documentoSubido) {
                            $items_completados++;
                        }
                    }
                }
            }
        }

        if ($total_items > 0) {
            return round(($items_completados / $total_items) * 100);
        }

        return 0;
    }

    /**
     * Generar correlativo para un formulario
     */
    private function generarCorrelativo($form, $empresaId)
    {
        if (!$form->usa_correlativo) {
            return null;
        }
        
        // Buscar o crear la secuencia para este formulario y empresa
        $sequence = \App\Models\FormSequence::firstOrCreate(
            ['id_form' => $form->id, 'id_emp' => $empresaId],
            ['last_number' => 0]
        );
        
        // Incrementar el último número
        $sequence->increment('last_number');
        
        // Generar el correlativo según el formato del formulario
        return ($form->prefijo ?? '') . 
               str_pad($sequence->last_number, $form->padding ?? 6, '0', STR_PAD_LEFT) . 
               ($form->sufijo ?? '');
    }

    /**
     * Cargar formulario nuevo para rellenar
     */
    public function nuevoFormulario(Request $request, $etapaFormId)
    {
        try {
            $detalleFlujoId = $request->input('detalle_flujo_id');
            Log::info("=== NUEVO FORMULARIO ===", [
                'etapa_form_id' => $etapaFormId,
                'detalle_flujo_id' => $detalleFlujoId,
                'user_id' => Auth::id()
            ]);
            
            $etapaForm = \App\Models\EtapaForm::with(['form.groups', 'form.fields.source', 'form.fields.formula', 'etapa'])
                ->findOrFail($etapaFormId);
            
            // Verificar que el usuario tenga acceso
            if (!$etapaForm || $etapaForm->etapa->flujo->id_emp != Auth::user()->id_emp) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes acceso a este formulario'
                ], 403);
            }

            // Crear o buscar FormRun existente específico para esta ejecución de flujo
            $searchCriteria = [
                'id_etapas_forms' => $etapaFormId,
                'id_emp' => Auth::user()->id_emp,
                'id_form' => $etapaForm->form->id
            ];
            
            $formRun = null;
            
            // ESTRATEGIA CORREGIDA: Siempre usar correlativo con prefijo DF{detalle_flujo_id}
            // para identificar formularios específicos de cada ejecución
            if ($detalleFlujoId) {
                // Buscar FormRuns específicos de esta ejecución usando correlativo
                $formRun = \App\Models\FormRun::where($searchCriteria)
                    ->where('correlativo', 'LIKE', "DF{$detalleFlujoId}-%")
                    ->first();
                    
                Log::info('Buscando FormRun para ejecución específica:', [
                    'detalle_flujo_id' => $detalleFlujoId,
                    'busqueda_correlativo' => "DF{$detalleFlujoId}-%",
                    'encontrado' => $formRun ? $formRun->id : 'NO'
                ]);
            }
            
            // Si no se encontró con detalle_flujo_id, NO buscar FormRuns genéricos
            // Cada ejecución de flujo debe tener sus propios FormRuns únicos
            if (!$formRun) {
                Log::info('No se encontró FormRun específico para esta ejecución - se creará uno nuevo:', [
                    'detalle_flujo_id' => $detalleFlujoId,
                    'etapa_form_id' => $etapaFormId
                ]);
            }
            
            // Si no existe, crear uno nuevo
            if (!$formRun) {
                $correlativoBase = $this->generarCorrelativo($etapaForm->form, Auth::user()->id_emp);
                $correlativoFinal = null;
                
                // SIEMPRE agregar prefijo DF{detalleFlujoId} si tenemos detalle_flujo_id
                // para garantizar que cada ejecución tenga FormRuns únicos
                if ($detalleFlujoId) {
                    if ($correlativoBase) {
                        $correlativoFinal = "DF{$detalleFlujoId}-{$correlativoBase}";
                    } else {
                        // Si el formulario no usa correlativo, generar uno para esta ejecución
                        $correlativoFinal = "DF{$detalleFlujoId}-1";
                    }
                } else {
                    $correlativoFinal = $correlativoBase;
                }
                
                $formRun = \App\Models\FormRun::create([
                    'id_form' => $etapaForm->form->id,
                    'id_etapas_forms' => $etapaFormId,
                    'id_emp' => Auth::user()->id_emp,
                    'correlativo' => $correlativoFinal,
                    'estado' => 'draft',
                    'created_by' => Auth::id(), // SIEMPRE usuario actual
                    'updated_by' => Auth::id()
                ]);
                
                Log::info('Nuevo FormRun creado:', [
                    'id' => $formRun->id,
                    'correlativo' => $correlativoFinal,
                    'created_by' => Auth::id(),
                    'detalle_flujo_id' => $detalleFlujoId,
                    'usa_correlativo' => $etapaForm->form->usa_correlativo ? 'SI' : 'NO'
                ]);
            }

            // Preparar campos con opciones y fórmulas
            $fieldsWithOptions = $etapaForm->form->fields->map(function($field) {
                $fieldData = $field->toArray();
                
                // Agregar opciones si el campo tiene source
                if ($field->source && in_array($field->datatype, ['select', 'multiselect'])) {
                    $fieldData['opciones'] = $this->resolverOpcionesField($field);
                }
                
                // Agregar fórmula si el campo es de tipo output
                if ($field->kind === 'output' && $field->formula) {
                    $fieldData['formula'] = [
                        'expression' => $field->formula->expression,
                        'output_type' => $field->formula->output_type ?? 'decimal'
                    ];
                }
                
                return $fieldData;
            });

            return response()->json([
                'success' => true,
                'formulario' => [
                    'id' => $etapaForm->form->id,
                    'nombre' => $etapaForm->form->nombre,
                    'descripcion' => $etapaForm->form->descripcion,
                    'groups' => $etapaForm->form->groups,
                    'fields' => $fieldsWithOptions
                ],
                'formRunId' => $formRun->id,
                'respuestas' => $this->obtenerRespuestasFormRun($formRun->id)
            ]);

        } catch (\Exception $e) {
            Log::error('Error al cargar formulario nuevo: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar el formulario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cargar formulario existente para editar
     */
    public function editarFormulario($formRunId)
    {
        try {
            $formRun = \App\Models\FormRun::with(['form.groups', 'form.fields.source', 'form.fields.formula', 'etapaForm.etapa'])
                ->findOrFail($formRunId);
            
            // Verificar que el usuario tenga acceso
            if (!$formRun || $formRun->id_emp != Auth::user()->id_emp) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes acceso a este formulario'
                ], 403);
            }

            // Si está completado, no permitir edición - usar estados correctos de la BD
            if (in_array($formRun->estado, ['submitted', 'approved'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este formulario ya está completado y no se puede editar'
                ], 400);
            }

            // Preparar campos con opciones y fórmulas
            $fieldsWithOptions = $formRun->form->fields->map(function($field) {
                $fieldData = $field->toArray();
                
                // Agregar opciones si el campo tiene source
                if ($field->source && in_array($field->datatype, ['select', 'multiselect'])) {
                    $fieldData['opciones'] = $this->resolverOpcionesField($field);
                }
                
                // Agregar fórmula si el campo es de tipo output
                if ($field->kind === 'output' && $field->formula) {
                    $fieldData['formula'] = [
                        'expression' => $field->formula->expression,
                        'output_type' => $field->formula->output_type ?? 'decimal'
                    ];
                }
                
                return $fieldData;
            });

            return response()->json([
                'success' => true,
                'formulario' => [
                    'id' => $formRun->form->id,
                    'nombre' => $formRun->form->nombre,
                    'descripcion' => $formRun->form->descripcion,
                    'groups' => $formRun->form->groups,
                    'fields' => $fieldsWithOptions
                ],
                'formRunId' => $formRun->id,
                'respuestas' => $this->obtenerRespuestasFormRun($formRun->id)
            ]);

        } catch (\Exception $e) {
            Log::error('Error al cargar formulario para editar: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar el formulario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ver formulario completado
     */
    public function verFormulario($formRunId)
    {
        try {
            Log::info("=== VER FORMULARIO ===", ['formRunId' => $formRunId]);
            
            $formRun = \App\Models\FormRun::with(['form.groups', 'form.fields.source', 'form.fields.formula', 'etapaForm.etapa'])
                ->findOrFail($formRunId);
            
            Log::info("FormRun encontrado", [
                'id' => $formRun->id,
                'correlativo' => $formRun->correlativo,
                'estado' => $formRun->estado,
                'form_id' => $formRun->form->id,
                'form_nombre' => $formRun->form->nombre
            ]);
            
            // Verificar que el usuario tenga acceso
            if (!$formRun || $formRun->id_emp != Auth::user()->id_emp) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes acceso a este formulario'
                ], 403);
            }

            // Preparar campos con opciones y fórmulas
            $fieldsWithOptions = $formRun->form->fields->map(function($field) {
                $fieldData = $field->toArray();
                
                // Agregar opciones si el campo tiene source
                if ($field->source && in_array($field->datatype, ['select', 'multiselect'])) {
                    $fieldData['opciones'] = $this->resolverOpcionesField($field);
                }
                
                // Agregar fórmula si el campo es de tipo output
                if ($field->kind === 'output' && $field->formula) {
                    $fieldData['formula'] = [
                        'expression' => $field->formula->expression,
                        'output_type' => $field->formula->output_type ?? 'decimal'
                    ];
                }
                
                return $fieldData;
            });

            $respuestas = $this->obtenerRespuestasFormRun($formRun->id);
            Log::info("Respuestas obtenidas del FormRun", ['respuestas' => $respuestas]);

            return response()->json([
                'success' => true,
                'formulario' => [
                    'id' => $formRun->form->id,
                    'nombre' => $formRun->form->nombre,
                    'descripcion' => $formRun->form->descripcion,
                    'groups' => $formRun->form->groups,
                    'fields' => $fieldsWithOptions
                ],
                'formRunId' => $formRun->id,
                'estado' => $formRun->estado,
                'correlativo' => $formRun->correlativo,
                'respuestas' => $respuestas
            ]);

        } catch (\Exception $e) {
            Log::error('Error al ver formulario: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar el formulario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Guardar respuestas del formulario
     */
    public function guardarFormulario(Request $request)
    {
        try {
            Log::info('=== INICIO GUARDAR FORMULARIO ===');
            Log::info('Request Content:', $request->all());
            
            // Validación básica de datos - aceptar los estados que vienen del frontend
            $request->validate([
                'estado' => 'required|in:draft,submitted,approved,void,borrador,completado,en_progreso,pendiente',
                'etapa_form_id' => 'required|integer|exists:etapas_forms,id'
            ]);

            $etapaFormId = $request->input('etapa_form_id');
            $estado = $request->input('estado');
            $formRunId = $request->input('form_run_id');
            
            // Normalizar estado para asegurar compatibilidad
            $estadoNormalizado = $this->normalizarEstadoFormulario($estado);
            
            Log::info('Datos básicos:', [
                'etapa_form_id' => $etapaFormId,
                'estado_original' => $estado,
                'estado_normalizado' => $estadoNormalizado,
                'form_run_id' => $formRunId
            ]);

            // Buscar o crear el FormRun
            if ($formRunId) {
                $formRun = FormRun::find($formRunId);
                if (!$formRun) {
                    return response()->json([
                        'success' => false,
                        'message' => 'FormRun no encontrado'
                    ], 404);
                }
                Log::info('FormRun existente encontrado:', ['id' => $formRun->id]);
            } else {
                // Crear nuevo FormRun
                $etapaForm = EtapaForm::find($etapaFormId);
                if (!$etapaForm) {
                    return response()->json([
                        'success' => false,
                        'message' => 'EtapaForm no encontrado'
                    ], 404);
                }
                
                $detalleFlujoId = $request->input('detalle_flujo_id');
                
                // Buscar FormRun existente usando la misma lógica que nuevoFormulario
                $searchCriteria = [
                    'id_etapas_forms' => $etapaFormId,
                    'id_emp' => Auth::user()->id_emp,
                    'id_form' => $etapaForm->id_forms
                ];
                
                $formRun = null;
                
                // Usar misma estrategia: buscar sólo por correlativo específico de ejecución
                if ($detalleFlujoId) {
                    $formRun = FormRun::where($searchCriteria)
                        ->where('correlativo', 'LIKE', "DF{$detalleFlujoId}-%")
                        ->first();
                        
                    Log::info('Guardando - Buscando FormRun para ejecución específica:', [
                        'detalle_flujo_id' => $detalleFlujoId,
                        'busqueda_correlativo' => "DF{$detalleFlujoId}-%",
                        'encontrado' => $formRun ? $formRun->id : 'NO'
                    ]);
                }
                
                // Si no se encontró con detalle_flujo_id, NO buscar FormRuns genéricos
                // Cada ejecución de flujo debe tener sus propios FormRuns únicos
                if (!$formRun) {
                    Log::info('Guardando - No se encontró FormRun específico para esta ejecución - se creará uno nuevo:', [
                        'detalle_flujo_id' => $detalleFlujoId,
                        'etapa_form_id' => $etapaFormId
                    ]);
                    
                    // Crear nuevo FormRun con la misma estrategia que nuevoFormulario
                    $correlativoBase = $this->generarCorrelativo($etapaForm->form, Auth::user()->id_emp);
                    $correlativoFinal = null;
                    
                    // SIEMPRE agregar prefijo DF{detalleFlujoId} si tenemos detalle_flujo_id
                    // para garantizar que cada ejecución tenga FormRuns únicos
                    if ($detalleFlujoId) {
                        if ($correlativoBase) {
                            $correlativoFinal = "DF{$detalleFlujoId}-{$correlativoBase}";
                        } else {
                            // Si el formulario no usa correlativo, generar uno para esta ejecución
                            $correlativoFinal = "DF{$detalleFlujoId}-1";
                        }
                    } else {
                        $correlativoFinal = $correlativoBase;
                    }
                    
                    $formRun = FormRun::create([
                        'id_form' => $etapaForm->id_forms,
                        'id_etapas_forms' => $etapaFormId,
                        'id_emp' => Auth::user()->id_emp,
                        'estado' => $estadoNormalizado,
                        'correlativo' => $correlativoFinal,
                        'created_by' => Auth::id(), // SIEMPRE usuario actual
                        'updated_by' => Auth::id()
                    ]);
                    Log::info('Guardando - Nuevo FormRun creado:', [
                        'id' => $formRun->id,
                        'correlativo' => $correlativoFinal,
                        'created_by' => Auth::id(),
                        'detalle_flujo_id' => $detalleFlujoId,
                        'usa_correlativo' => $etapaForm->form->usa_correlativo ? 'SI' : 'NO'
                    ]);
                } else {
                    Log::info('Guardando - FormRun existente encontrado:', [
                        'id' => $formRun->id,
                        'correlativo' => $formRun->correlativo,
                        'detalle_flujo_id' => $detalleFlujoId
                    ]);
                }
            }
            
            // Verificar que el usuario tenga acceso
            if ($formRun->id_emp != Auth::user()->id_emp) {
                Log::warning('Usuario sin acceso al formulario');
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes acceso a este formulario'
                ], 403);
            }

            // Actualizar estado del FormRun
            $formRun->update([
                'estado' => $estadoNormalizado,
                'updated_by' => Auth::id()
            ]);
            Log::info('FormRun actualizado exitosamente');

            // Recopilar campos del formulario del request
            $camposFormulario = [];
            foreach ($request->all() as $key => $value) {
                if (str_starts_with($key, 'respuestas[')) {
                    // Extraer el ID del campo
                    preg_match('/respuestas\[(.+)\]/', $key, $matches);
                    if (isset($matches[1])) {
                        $camposFormulario[$matches[1]] = $value;
                    }
                }
            }
            
            Log::info('Campos del formulario encontrados:', [
                'total' => count($camposFormulario),
                'campos' => array_keys($camposFormulario)
            ]);
            
            $respuestasGuardadas = 0;
            foreach ($camposFormulario as $fieldId => $valor) {
                Log::info("Procesando campo {$fieldId} con valor:", [
                    'valor' => $valor, 
                    'tipo' => gettype($valor),
                    'fieldId' => $fieldId
                ]);
                
                // Buscar el campo por ID (el JavaScript envía field IDs)
                $field = FormField::find($fieldId);
                if (!$field) {
                    Log::warning("Campo no encontrado con ID: {$fieldId}");
                    continue;
                }

                Log::info("Campo encontrado:", [
                    'field_id' => $field->id,
                    'field_codigo' => $field->codigo,
                    'field_datatype' => $field->datatype
                ]);

                // Preparar datos para FormAnswer
                $answerData = [
                    'id_run' => $formRun->id,
                    'id_field' => $field->id,
                    'value_text' => null,
                    'value_number' => null,
                    'value_int' => null,
                    'value_date' => null,
                    'value_datetime' => null,
                    'value_bool' => null,
                    'value_json' => null
                ];

                // Asignar el valor al campo correcto según el tipo
                switch ($field->datatype) {
                    case 'text':
                    case 'textarea':
                    case 'select':
                    case 'radio':
                        $answerData['value_text'] = (string) $valor;
                        break;
                    case 'number':
                    case 'decimal':
                        $answerData['value_number'] = is_numeric($valor) ? (float) $valor : null;
                        break;
                    case 'integer':
                        $answerData['value_int'] = is_numeric($valor) ? (int) $valor : null;
                        break;
                    case 'date':
                        $answerData['value_date'] = $valor ? date('Y-m-d', strtotime($valor)) : null;
                        break;
                    case 'datetime':
                        $answerData['value_datetime'] = $valor ? date('Y-m-d H:i:s', strtotime($valor)) : null;
                        break;
                    case 'checkbox':
                        $answerData['value_bool'] = (bool) $valor;
                        break;
                    case 'multiselect':
                    case 'json':
                        $answerData['value_json'] = is_array($valor) ? $valor : [$valor];
                        break;
                    default:
                        $answerData['value_text'] = (string) $valor;
                        break;
                }

                // Crear o actualizar la respuesta
                $formAnswer = FormAnswer::updateOrCreate([
                    'id_run' => $formRun->id,
                    'id_field' => $field->id
                ], $answerData);

                $respuestasGuardadas++;
                Log::info("Respuesta guardada para campo {$fieldId}", [
                    'field_codigo' => $field->codigo,
                    'field_type' => $field->datatype,
                    'valor_original' => $valor,
                    'form_answer_id' => $formAnswer->id,
                    'answer_data_saved' => $answerData
                ]);
            }

            Log::info("Total de respuestas procesadas: {$respuestasGuardadas}");

            // Si se completó el formulario, actualizar el progreso de la etapa
            if ($estado === 'completado') {
                Log::info('Formulario completado, actualizando progreso de etapa');
                $this->actualizarProgresoEtapaPorFormulario($formRun);
            }

            Log::info('=== FORMULARIO GUARDADO EXITOSAMENTE ===');

            return response()->json([
                'success' => true,
                'message' => $estado === 'completado' ? 'Formulario completado exitosamente' : 'Borrador guardado exitosamente',
                'formRunId' => $formRun->id,
                'form_run_id' => $formRun->id, // Mantener por compatibilidad
                'etapaFormId' => $formRun->id_etapas_forms, // Agregar para el frontend
                'estado' => $formRun->estado,
                'debug' => [
                    'form_run_id' => $formRun->id,
                    'etapa_form_id' => $formRun->id_etapas_forms,
                    'form_id' => $formRun->id_form,
                    'respuestas_guardadas' => $respuestasGuardadas,
                    'respuestas_recibidas' => count($camposFormulario)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error al guardar formulario: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar el formulario: ' . $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ], 500);
        }
    }

    /**
     * Obtener respuestas de un FormRun
     */
    private function obtenerRespuestasFormRun($formRunId)
    {
        Log::info("=== OBTENER RESPUESTAS FormRun {$formRunId} ===");
        
        // Cargar el FormRun con todas sus respuestas (igual que en FormRunController)
        $formRun = FormRun::with(['answers.field', 'rows.values.field'])->find($formRunId);
        
        if (!$formRun) {
            Log::warning("FormRun {$formRunId} no encontrado");
            return [];
        }
        
        Log::info("FormRun encontrado", [
            'id' => $formRun->id,
            'correlativo' => $formRun->correlativo,
            'estado' => $formRun->estado,
            'answers_count' => $formRun->answers->count(),
            'rows_count' => $formRun->rows->count()
        ]);
        
        Log::info("DEBUG: Answers crudos", [
            'answers' => $formRun->answers->map(function($answer) {
                return [
                    'id' => $answer->id,
                    'field_id' => $answer->id_field,
                    'field_codigo' => $answer->field ? $answer->field->codigo : 'SIN_FIELD',
                    'value_text' => $answer->value_text,
                    'value_number' => $answer->value_number,
                    'value_date' => $answer->value_date,
                    'value_bool' => $answer->value_bool
                ];
            })->toArray()
        ]);
        
        $resultado = [];
        
        // 1. Procesar respuestas simples (form_answers)
        foreach ($formRun->answers as $answer) {
            if (!$answer->field) {
                Log::warning("Answer sin field asociado: " . $answer->id);
                continue;
            }
            
            $field = $answer->field;
            
            // Obtener el valor según el tipo de campo
            $valor = $this->extraerValorDeAnswer($answer, $field);
            
            // IMPORTANTE: El frontend espera las respuestas indexadas por field_id, no por codigo
            $resultado[$field->id] = $valor;
            Log::info("Campo simple: {$field->codigo} (ID: {$field->id}) = {$valor} (tipo: {$field->datatype})");
        }
        
        // 2. Procesar respuestas de grupos (form_answer_rows)
        if ($formRun->rows->count() > 0) {
            Log::info("Procesando " . $formRun->rows->count() . " filas de grupos");
            
            // Agrupar por grupo
            $gruposPorId = [];
            foreach ($formRun->rows as $row) {
                $gruposPorId[$row->id_group] = $gruposPorId[$row->id_group] ?? [];
                $gruposPorId[$row->id_group][] = $row;
            }
            
            foreach ($gruposPorId as $groupId => $rows) {
                // Obtener el código del grupo
                $grupo = \App\Models\FormGroup::find($groupId);
                if (!$grupo) continue;
                
                $resultado['groups'] = $resultado['groups'] ?? [];
                $resultado['groups'][$grupo->codigo] = [];
                
                foreach ($rows as $row) {
                    $filaData = [];
                    
                    foreach ($row->values as $value) {
                        if ($value->field) {
                            $valorCampo = $this->extraerValorDeAnswer($value, $value->field);
                            $filaData[$value->field->codigo] = $valorCampo;
                        }
                    }
                    
                    $resultado['groups'][$grupo->codigo][$row->row_index] = $filaData;
                    Log::info("Grupo {$grupo->codigo}, fila {$row->row_index}: " . json_encode($filaData));
                }
            }
        }
        
        Log::info("Resultado final:", [
            'campos_simples' => count($resultado) - (isset($resultado['groups']) ? 1 : 0),
            'grupos' => isset($resultado['groups']) ? count($resultado['groups']) : 0,
            'estructura' => $resultado
        ]);
        
        return $resultado;
    }
    
    /**
     * Extrae el valor de un FormAnswer o FormAnswerRowValue según el tipo de campo
     */
    private function extraerValorDeAnswer($answer, $field)
    {
        switch ($field->datatype) {
            case 'text':
            case 'textarea':
            case 'select':
            case 'radio':
                return $answer->value_text;
            case 'number':
            case 'decimal':
                return $answer->value_number;
            case 'integer':
            case 'int':
                return $answer->value_int;
            case 'date':
                return $answer->value_date;
            case 'datetime':
                return $answer->value_datetime;
            case 'checkbox':
            case 'boolean':
                return $answer->value_bool ? '1' : '0';
            case 'multiselect':
            case 'json':
                return $answer->value_json;
            default:
                return $answer->value_text;
        }
    }

    /**
     * Actualizar progreso de etapa cuando se completa un formulario
     */
    private function actualizarProgresoEtapaPorFormulario($formRun)
    {
        try {
            $etapaForm = $formRun->etapaForm;
            if (!$etapaForm) {
                Log::warning('EtapaForm no encontrado para FormRun', ['form_run_id' => $formRun->id]);
                return;
            }

            $etapa = $etapaForm->etapa;
            if (!$etapa) {
                Log::warning('Etapa no encontrada para EtapaForm', ['etapa_form_id' => $etapaForm->id]);
                return;
            }

            $flujo = $etapa->flujo;
            if (!$flujo) {
                Log::warning('Flujo no encontrado para Etapa', ['etapa_id' => $etapa->id]);
                return;
            }

            // Buscar el detalle_flujo activo para esta empresa y flujo
            $detalleFlujo = \App\Models\DetalleFlujo::where('id_flujo', $flujo->id)
                ->where('id_emp', $formRun->id_emp)
                ->where('estado', 2) // Solo ejecuciones activas
                ->orderBy('updated_at', 'desc')
                ->first();

            if (!$detalleFlujo) {
                Log::warning('DetalleFlujo activo no encontrado', [
                    'flujo_id' => $flujo->id,
                    'empresa_id' => $formRun->id_emp
                ]);
                return;
            }

            // Buscar el detalle de la etapa correspondiente
            $detalleEtapa = \App\Models\DetalleEtapa::where('id_etapa', $etapa->id)
                ->where('id_detalle_flujo', $detalleFlujo->id)
                ->first();

            if ($detalleEtapa) {
                Log::info('Verificando completitud de etapa por formulario completado', [
                    'form_run_id' => $formRun->id,
                    'etapa_id' => $etapa->id,
                    'detalle_etapa_id' => $detalleEtapa->id,
                    'detalle_flujo_id' => $detalleFlujo->id
                ]);
                
                // Verificar si todos los elementos de la etapa están completados
                $this->verificarYActualizarCompletitudEtapa($detalleEtapa);
            } else {
                Log::warning('DetalleEtapa no encontrado', [
                    'etapa_id' => $etapa->id,
                    'detalle_flujo_id' => $detalleFlujo->id
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error al actualizar progreso de etapa por formulario: ' . $e->getMessage(), [
                'form_run_id' => $formRun->id,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Verificar si una etapa está completada incluyendo formularios
     */
    private function verificarYActualizarCompletitudEtapa($detalleEtapa)
    {
        try {
            $etapa = $detalleEtapa->etapa;
            $detalleFlujo = $detalleEtapa->detalleFlujo;
            
            Log::info('Verificando completitud de etapa', [
                'etapa_id' => $etapa->id,
                'detalle_etapa_id' => $detalleEtapa->id,
                'detalle_flujo_id' => $detalleFlujo->id
            ]);

            // Verificar tareas completadas (usando la nueva lógica de DetalleTarea)
            $totalTareas = \App\Models\DetalleTarea::where('id_detalle_etapa', $detalleEtapa->id)
                ->whereNotIn('estado', [66, 99]) // Excluir no influyentes y canceladas
                ->count();
            $tareasCompletadas = \App\Models\DetalleTarea::where('id_detalle_etapa', $detalleEtapa->id)
                ->where('estado', 3) // Solo completadas
                ->whereNotIn('estado', [66, 99])
                ->count();

            // Verificar documentos validados (usando la nueva lógica de DetalleDocumento)
            $totalDocumentos = \App\Models\DetalleDocumento::whereHas('detalleTarea', function($query) use ($detalleEtapa) {
                    $query->where('id_detalle_etapa', $detalleEtapa->id);
                })
                ->whereNotIn('estado', [66, 99]) // Excluir no influyentes y cancelados
                ->count();
            $documentosValidados = \App\Models\DetalleDocumento::whereHas('detalleTarea', function($query) use ($detalleEtapa) {
                    $query->where('id_detalle_etapa', $detalleEtapa->id);
                })
                ->where('estado', 3) // Solo validados
                ->whereNotIn('estado', [66, 99])
                ->count();

            // Verificar formularios completados
            $etapaFormsIds = $etapa->etapaForms()->pluck('id')->toArray();
            $totalFormularios = count($etapaFormsIds);
            $formulariosCompletados = 0;
            
            if ($totalFormularios > 0) {
                // Contar solo FormRuns específicos para esta ejecución de flujo
                $formulariosCompletados = \App\Models\FormRun::whereIn('id_etapas_forms', $etapaFormsIds)
                    ->where('id_emp', $detalleFlujo->id_emp)
                    ->where('estado', 'completado')
                    ->where(function($query) use ($detalleFlujo) {
                        $query->where('correlativo', 'LIKE', "DF{$detalleFlujo->id}-%")
                              ->orWhere('created_by', $detalleFlujo->id);
                    })
                    ->count();
                    
                Log::info('Conteo de formularios específicos para ejecución:', [
                    'detalle_flujo_id' => $detalleFlujo->id,
                    'total_formularios' => $totalFormularios,
                    'formularios_completados_especificos' => $formulariosCompletados
                ]);
            }

            Log::info('Conteo de elementos de etapa', [
                'etapa_id' => $etapa->id,
                'total_tareas' => $totalTareas,
                'tareas_completadas' => $tareasCompletadas,
                'total_documentos' => $totalDocumentos,
                'documentos_validados' => $documentosValidados,
                'total_formularios' => $totalFormularios,
                'formularios_completados' => $formulariosCompletados
            ]);

            // Una etapa está completada si TODOS sus elementos están completados
            $etapaCompleta = ($totalTareas == $tareasCompletadas) && 
                            ($totalDocumentos == $documentosValidados) &&
                            ($totalFormularios == $formulariosCompletados);

            if ($etapaCompleta && $detalleEtapa->estado != 3) {
                $detalleEtapa->update([
                    'estado' => 3, // completada
                    'updated_at' => now()
                ]);

                Log::info("Etapa {$etapa->nombre} (ID: {$etapa->id}) completada automáticamente", [
                    'detalle_etapa_id' => $detalleEtapa->id,
                    'detalle_flujo_id' => $detalleFlujo->id
                ]);

                // Verificar si todo el flujo está completado
                $this->verificarYActualizarEstados(null, 'etapa', $detalleFlujo->id);
            } else {
                Log::info("Etapa no completada aún", [
                    'etapa_id' => $etapa->id,
                    'etapa_completa' => $etapaCompleta,
                    'estado_actual' => $detalleEtapa->estado
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error verificando completitud de etapa: ' . $e->getMessage(), [
                'detalle_etapa_id' => $detalleEtapa->id ?? null,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Resolver opciones para un campo según su fuente
     */
    private function resolverOpcionesField($field)
    {
        if (!$field->source) {
            return [];
        }

        $source = $field->source;
        $opciones = [];

        switch ($source->source_kind) {
            case 'static_options':
                if ($source->options_json && is_array($source->options_json)) {
                    $opciones = collect($source->options_json)->map(function($opcion) {
                        return [
                            'valor' => $opcion['value'] ?? $opcion['valor'] ?? '',
                            'etiqueta' => $opcion['label'] ?? $opcion['etiqueta'] ?? $opcion['value'] ?? $opcion['valor'] ?? ''
                        ];
                    })->toArray();
                }
                break;

            case 'query':
                if ($source->query_sql) {
                    try {
                        $results = DB::select($source->query_sql);
                        $opciones = collect($results)->map(function($row) {
                            $row = (array) $row;
                            return [
                                'valor' => $row['value'] ?? $row['id'] ?? array_values($row)[0] ?? '',
                                'etiqueta' => $row['label'] ?? $row['nombre'] ?? $row['text'] ?? array_values($row)[1] ?? array_values($row)[0] ?? ''
                            ];
                        })->toArray();
                    } catch (\Exception $e) {
                        Log::error("Error ejecutando query para campo {$field->id}: " . $e->getMessage());
                    }
                }
                break;

            case 'table_column':
                if ($source->table_name && $source->column_name) {
                    try {
                        $results = DB::table($source->table_name)
                            ->select($source->column_name . ' as value')
                            ->distinct()
                            ->whereNotNull($source->column_name)
                            ->orderBy($source->column_name)
                            ->get();
                        
                        $opciones = $results->map(function($row) {
                            return [
                                'valor' => $row->value,
                                'etiqueta' => $row->value
                            ];
                        })->toArray();
                    } catch (\Exception $e) {
                        Log::error("Error obteniendo opciones de tabla para campo {$field->id}: " . $e->getMessage());
                    }
                }
                break;

            case 'ficha_attr':
                // Para atributos de fichas, implementar según sea necesario
                break;
        }

        return $opciones;
    }

    /**
     * Borrar un formulario completado
     */
    public function borrarFormulario($formRunId)
    {
        try {
            $formRun = \App\Models\FormRun::with(['etapasForm', 'answers.rows.values'])
                ->findOrFail($formRunId);

            // Verificar que el usuario tenga acceso
            if ($formRun->id_emp != Auth::user()->id_emp) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes acceso a este formulario'
                ], 403);
            }

            // Verificar que el formulario esté completado
            if ($formRun->estado !== 'completado') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden borrar formularios completados'
                ], 400);
            }

            DB::beginTransaction();

            try {
                // Borrar respuestas relacionadas
                foreach ($formRun->answers as $answer) {
                    // Borrar valores de filas
                    foreach ($answer->rows as $row) {
                        $row->values()->delete();
                    }
                    // Borrar filas
                    $answer->rows()->delete();
                }
                // Borrar respuestas
                $formRun->answers()->delete();

                // Borrar el FormRun
                $formRun->delete();

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Formulario borrado exitosamente'
                ]);

            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Error borrando formulario: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al borrar el formulario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Normalizar el estado del formulario a valores válidos
     */
    private function normalizarEstadoFormulario($estado)
    {
        // Normalizar el estado a minúsculas y eliminar espacios
        $estado = strtolower(trim($estado));
        
        // Mapear estados válidos a los estados de la base de datos: draft, submitted, approved, void
        switch ($estado) {
            case 'completado':
            case 'completo':
            case 'finished':
            case 'complete':
            case 'submitted':
                return 'submitted';  // Estado para formularios completados y enviados
                
            case 'borrador':
            case 'draft':
            case 'pendiente':
            case 'pending':
            case 'en_progreso':
            case 'en progreso':
            case 'in_progress':
                return 'draft';  // Estado para borradores
                
            case 'approved':
            case 'aprobado':
                return 'approved';  // Estado para formularios aprobados
                
            case 'void':
            case 'anulado':
            case 'cancelado':
            case 'cancelled':
            case 'canceled':
                return 'void';  // Estado para formularios anulados
                
            default:
                // Si no coincide con ningún estado conocido, defaultear a 'draft'
                Log::warning("Estado desconocido recibido: {$estado}, normalizando a 'draft'");
                return 'draft';
        }
    }

    /**
     * Verificar si existe plantilla PDF para un FormRun
     */
    public function verificarPlantillaPdf($formRunId)
    {
        try {
            $formRun = FormRun::find($formRunId);
            
            if (!$formRun) {
                return response()->json([
                    'success' => false,
                    'message' => 'FormRun no encontrado'
                ], 404);
            }

            // Verificar que el usuario tenga acceso
            if ($formRun->id_emp != Auth::user()->id_emp) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes acceso a este formulario'
                ], 403);
            }

            // Buscar plantilla PDF para este formulario
            $pdfTemplate = \App\Models\PdfTemplate::where('id_form', $formRun->id_form)->first();
            
            if ($pdfTemplate) {
                return response()->json([
                    'success' => true,
                    'template_id' => $pdfTemplate->id,
                    'template_name' => $pdfTemplate->nombre
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay plantilla PDF disponible para este formulario'
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error verificando plantilla PDF: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error verificando plantilla PDF'
            ], 500);
        }
    }

}