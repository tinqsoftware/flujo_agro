<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\Models\Empresa;
use App\Models\Ficha;
use App\Models\Flujo;
use App\Models\Etapa;
use App\Models\TipoFlujo;
use App\Models\Proveedor;
use App\Models\Tarea;
use App\Models\Documento;
use App\Models\User;
use App\Models\Rol;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Cliente;
use App\Models\DatosAtributosFicha; // Alias del modelo de datos_atributos_fichas


class FlujoController extends Controller
{

    /** INDEX: Cards con etapas y contadores */
    public function index(Request $request)
    {
        $user = Auth::user();
        $isSuper = ($user->rol->nombre === 'SUPERADMIN');

        $estado = $request->get('estado','todos');
        $q      = trim((string)$request->get('q',''));

        $qf = Flujo::with(['empresa','tipo'])
            ->when(!$isSuper, fn($x)=>$x->where('id_emp',$user->id_emp))
            ->when($estado==='activos', fn($x)=>$x->where('estado',1))
            ->when($estado==='inactivos', fn($x)=>$x->where('estado',0))
            ->when($q!=='', fn($x)=>$x->where('nombre','like',"%{$q}%"))
            ->orderByDesc('created_at');

        $flujos = $qf->paginate(12)->appends($request->query());

        // etapas con contadores
        $etapasPorFlujo = [];
        $ids = $flujos->pluck('id');
        if ($ids->count()) {
            $etps = Etapa::select('id','id_flujo','nro','nombre')
                ->withCount(['tareas'])
                ->whereIn('id_flujo',$ids)->orderBy('nro')->get()
                ->groupBy('id_flujo');
            
            // Calcular manualmente el conteo de documentos para compatibilidad
            foreach ($etps->flatten() as $etapa) {
                // Contar documentos nuevos (a través de tareas)
                $documentosNuevos = DB::table('documentos')
                    ->join('tareas', 'documentos.id_tarea', '=', 'tareas.id')
                    ->where('tareas.id_etapa', $etapa->id)
                    ->count();
                
                // Ya no hay documentos antiguos porque la columna id_etapa fue eliminada
                // Solo contamos los documentos que pertenecen a tareas de esta etapa
                $etapa->documentos_count = $documentosNuevos;
            }
            
            foreach ($ids as $fid) $etapasPorFlujo[$fid] = $etps->get($fid) ?? collect();
        }

        return view('superadmin.flujos.index', compact(
            'flujos','etapasPorFlujo','isSuper','estado','q'
        ));
    }

    /** CREATE: pasa empresas, tipos y tree vacío */
    public function create()
    {
        $user = Auth::user();
        $isSuper = ($user->rol->nombre === 'SUPERADMIN');

        $empresas = $isSuper
            ? Empresa::where('estado',1)->orderBy('nombre')->get(['id','nombre'])
            : Empresa::where('id',$user->id_emp)->get(['id','nombre']);

        // Tipos del contexto (si es super, todos; si no, solo su empresa)
        $tipos = $isSuper
            ? TipoFlujo::orderBy('nombre')->get(['id','nombre','id_emp'])
            : TipoFlujo::where('id_emp',$user->id_emp)->orderBy('nombre')->get(['id','nombre','id_emp']);

        // Obtener roles (excluyendo SUPERADMIN)
        $roles = Rol::where('id', '!=', 1)->where('estado', 1)->orderBy('nombre')->get(['id', 'nombre']);

        $treeJson = json_encode(['stages'=>[]]); // builder vacío
        $isEditMode = false;

        return view('superadmin.flujos.create', compact('empresas','tipos','isSuper','treeJson','isEditMode','roles'));
    }

    /** STORE: crea flujo + (opcional) etapas/tareas/docs del builder */
    public function store(Request $request)
    {
        $user = Auth::user();
        $isSuper = ($user->rol->nombre === 'SUPERADMIN');

        $empresaId = $isSuper ? $request->input('id_emp') : $user->id_emp;

        $validated = $request->validate([
            'nombre'         => ['required','string','max:255'],
            'descripcion'    => ['nullable','string'],
            'id_tipo_flujo'  => ['nullable','exists:tipo_flujo,id'],
            'id_emp'         => ['nullable','exists:empresa,id'],
        ]);

        DB::transaction(function() use ($request,$validated,$empresaId,$user) {
            $flujo = new Flujo();
            $flujo->nombre        = $validated['nombre'];
            $flujo->descripcion   = $validated['descripcion'] ?? null;
            $flujo->id_emp        = $empresaId;
            $flujo->id_tipo_flujo = $validated['id_tipo_flujo'] ?? null;
            $flujo->id_user_create= $user->id;
            $flujo->estado        = 1;
            $flujo->save();

            // Builder JSON
            $builder = json_decode($request->input('builder',''), true);
            if (is_array($builder) && !empty($builder['stages'])) {
                foreach ($builder['stages'] as $st) {
                    $et = new Etapa();
                    $et->id_flujo    = $flujo->id;
                    $et->nombre      = $st['name'] ?? 'Etapa';
                    $et->descripcion = $st['description'] ?? null;
                    $et->nro         = (int)($st['nro'] ?? 1);
                    $et->paralelo    = !empty($st['paralelo']) ? 1 : 0;
                    $et->id_user_create = $user->id;
                    $et->estado      = 1;
                    $et->save();

                    foreach (($st['tasks'] ?? []) as $t) {
                        $ta = new Tarea();
                        $ta->id_etapa      = $et->id;
                        $ta->nombre        = $t['name'] ?? 'Tarea';
                        $ta->descripcion   = $t['description'] ?? null;
                        $ta->id_user_create= $user->id;
                        $ta->rol_cambios   = $t['rol_cambios'] ?? null;
                        $ta->estado        = 1;
                        $ta->save();

                        // Crear documentos de esta tarea específica
                        foreach (($t['documents'] ?? []) as $d) {
                            $doc = new Documento();
                            $doc->id_tarea       = $ta->id; // Nueva lógica: documento pertenece a tarea
                            $doc->nombre         = $d['name'] ?? 'Documento';
                            $doc->descripcion    = $d['description'] ?? null;
                            $doc->rol_cambios    = $d['rol_cambios'] ?? null;
                            $doc->id_user_create = $user->id;
                            $doc->estado         = 1;
                            $doc->save();
                        }
                    }
                    
                    // Ya no procesamos documentos a nivel de etapa ($st['documents'])
                    // porque ahora todos los documentos están dentro de las tareas
                }
            }
        });

        return redirect()->route('flujos.index')->with('success','Flujo creado correctamente.');
    }

    /** EDIT: igual a create pero carga árbol existente */
    public function edit(Flujo $flujo)
    {
        $user = Auth::user();
        $isSuper = ($user->rol->nombre === 'SUPERADMIN');
        if (!$isSuper && $flujo->id_emp != $user->id_emp) abort(403);

        $empresas = $isSuper
            ? Empresa::where('estado',1)->orderBy('nombre')->get(['id','nombre'])
            : Empresa::where('id',$user->id_emp)->get(['id','nombre']);

        $tipos = $isSuper
            ? TipoFlujo::orderBy('nombre')->get(['id','nombre','id_emp'])
            : TipoFlujo::where('id_emp',$user->id_emp)->orderBy('nombre')->get(['id','nombre','id_emp']);

        // Tree desde BD - incluir elementos desactivados en edición
        $stages = Etapa::where('id_flujo',$flujo->id)->orderBy('nro')->get();
        $tree = ['stages'=>[]];
        foreach ($stages as $st) {
            // Obtener tareas con información de detalles y sus documentos
            $tareas = Tarea::where('id_etapa',$st->id)->get()->map(function($tarea) {
                // Obtener documentos de esta tarea específica (nueva lógica)
                $documentos = Documento::where('id_tarea', $tarea->id)->get()->map(function($doc) {
                    return [
                        'id' => $doc->id,
                        'name' => $doc->nombre,
                        'description' => $doc->descripcion,
                        'estado' => (int)$doc->estado,
                        'rol_cambios' => $doc->rol_cambios,
                        'has_details' => $doc->detalles()->exists()
                    ];
                })->toArray();

                return [
                    'id' => $tarea->id,
                    'name' => $tarea->nombre,
                    'description' => $tarea->descripcion,
                    'estado' => (int)$tarea->estado,
                    'rol_cambios' => $tarea->rol_cambios,
                    'has_details' => $tarea->detalles()->exists(),
                    'documents' => $documentos // Agregar documentos a la tarea
                ];
            })->toArray();

            // Para compatibilidad: como ya no existe id_etapa, no hay documentos antiguos que cargar
            // Todos los documentos ahora están dentro de las tareas
            $documentosAntiguos = []; // Array vacío ya que no hay documentos antiguos

            $tree['stages'][] = [
                'id'         => $st->id,
                'name'       => $st->nombre,
                'description'=> $st->descripcion,
                'nro'        => (int)$st->nro,
                'paralelo'   => (int)$st->paralelo,
                'estado'     => (int)$st->estado,
                'tasks'      => $tareas,
                'documents'  => $documentosAntiguos, // Siempre vacío ahora
            ];
        }
        $treeJson = json_encode($tree);
        
        // Debug temporal: ver qué datos estamos pasando
        \Illuminate\Support\Facades\Log::info('Tree data for edit:', ['tree' => $tree]);
        
        $isEditMode = true;

        // Obtener roles (excluyendo SUPERADMIN)
        $roles = Rol::where('id', '!=', 1)->where('estado', 1)->orderBy('nombre')->get(['id', 'nombre']);

        return view('superadmin.flujos.edit', compact('flujo','empresas','tipos','isSuper','treeJson','isEditMode','roles'));
    }

    /** UPDATE: actualiza flujo y gestiona árbol manteniendo estados */
    public function update(Request $request, Flujo $flujo)
    {
        $user = Auth::user();
        $isSuper = ($user->rol->nombre === 'SUPERADMIN');
        if (!$isSuper && $flujo->id_emp != $user->id_emp) abort(403);

        $empresaId = $isSuper ? $request->input('id_emp') : $flujo->id_emp;

        $validated = $request->validate([
            'nombre'         => ['required','string','max:255'],
            'descripcion'    => ['nullable','string'],
            'id_tipo_flujo'  => ['nullable','exists:tipo_flujo,id'],
            'id_emp'         => ['nullable','exists:empresa,id'],
            'estado'         => ['nullable','boolean'],
        ]);

        DB::transaction(function() use ($request,$validated,$empresaId,$flujo) {
            $flujo->nombre        = $validated['nombre'];
            $flujo->descripcion   = $validated['descripcion'] ?? null;
            $flujo->id_emp        = $empresaId;
            $flujo->id_tipo_flujo = $validated['id_tipo_flujo'] ?? null;
            $flujo->estado        = $request->boolean('estado');
            $flujo->save();

            // Gestionar árbol de manera inteligente
            $builder = json_decode($request->input('builder',''), true);

            if (is_array($builder) && !empty($builder['stages'])) {
                $existingEtapas = Etapa::where('id_flujo',$flujo->id)->get()->keyBy('id');
                $processedEtapas = [];

                foreach ($builder['stages'] as $stData) {
                    $etapaId = isset($stData['id']) && is_numeric($stData['id']) ? $stData['id'] : null;
                    
                    if ($etapaId && isset($existingEtapas[$etapaId])) {
                        // Actualizar etapa existente
                        $etapa = $existingEtapas[$etapaId];
                        $etapa->nombre       = $stData['name'] ?? 'Etapa';
                        $etapa->descripcion  = $stData['description'] ?? null;
                        $etapa->nro          = (int)($stData['nro'] ?? 1);
                        $etapa->paralelo     = !empty($stData['paralelo']) ? 1 : 0;
                        $etapa->estado       = isset($stData['estado']) ? (int)$stData['estado'] : 1;
                        $etapa->save();
                        $processedEtapas[] = $etapa->id;
                    } else {
                        // Crear nueva etapa
                        $etapa = new Etapa();
                        $etapa->id_flujo      = $flujo->id;
                        $etapa->nombre        = $stData['name'] ?? 'Etapa';
                        $etapa->descripcion   = $stData['description'] ?? null;
                        $etapa->nro           = (int)($stData['nro'] ?? 1);
                        $etapa->paralelo      = !empty($stData['paralelo']) ? 1 : 0;
                        $etapa->estado        = 1;
                        $etapa->id_user_create = $flujo->id_user_create;
                        $etapa->save();
                        $processedEtapas[] = $etapa->id;
                    }

                    // Gestionar tareas
                    if (!empty($stData['tasks'])) {
                        $existingTareas = Tarea::where('id_etapa', $etapa->id)->get()->keyBy('id');
                        $processedTareas = [];

                        foreach ($stData['tasks'] as $tData) {
                            $tareaId = isset($tData['id']) && is_numeric($tData['id']) ? $tData['id'] : null;
                            
                            if ($tareaId && isset($existingTareas[$tareaId])) {
                                // Actualizar tarea existente
                                $tarea = $existingTareas[$tareaId];
                                $tarea->nombre      = $tData['name'] ?? 'Tarea';
                                $tarea->descripcion = $tData['description'] ?? null;
                                $tarea->rol_cambios = $tData['rol_cambios'] ?? null;
                                $tarea->estado      = isset($tData['estado']) ? (int)$tData['estado'] : 1;
                                $tarea->save();
                                $processedTareas[] = $tarea->id;
                            } else {
                                // Crear nueva tarea
                                $tarea = new Tarea();
                                $tarea->id_etapa       = $etapa->id;
                                $tarea->nombre         = $tData['name'] ?? 'Tarea';
                                $tarea->descripcion    = $tData['description'] ?? null;
                                $tarea->rol_cambios    = $tData['rol_cambios'] ?? null;
                                $tarea->estado         = 1;
                                $tarea->id_user_create = $flujo->id_user_create;
                                $tarea->save();
                                $processedTareas[] = $tarea->id;
                            }

                            // Gestionar documentos de esta tarea (nueva lógica)
                            if (!empty($tData['documents'])) {
                                $existingDocsInTask = Documento::where('id_tarea', $tarea->id)->get()->keyBy('id');
                                $processedDocsInTask = [];

                                foreach ($tData['documents'] as $dData) {
                                    $docId = isset($dData['id']) && is_numeric($dData['id']) ? $dData['id'] : null;
                                    
                                    if ($docId && isset($existingDocsInTask[$docId])) {
                                        // Actualizar documento existente
                                        $doc = $existingDocsInTask[$docId];
                                        $doc->nombre      = $dData['name'] ?? 'Documento';
                                        $doc->descripcion = $dData['description'] ?? null;
                                        $doc->rol_cambios = $dData['rol_cambios'] ?? null;
                                        $doc->estado      = isset($dData['estado']) ? (int)$dData['estado'] : 1;
                                        $doc->save();
                                        $processedDocsInTask[] = $doc->id;
                                    } else {
                                        // Crear nuevo documento
                                        $doc = new Documento();
                                        $doc->id_tarea       = $tarea->id; // Nueva lógica: documento pertenece a tarea
                                        $doc->nombre         = $dData['name'] ?? 'Documento';
                                        $doc->descripcion    = $dData['description'] ?? null;
                                        $doc->rol_cambios    = $dData['rol_cambios'] ?? null;
                                        $doc->estado         = 1;
                                        $doc->id_user_create = $flujo->id_user_create;
                                        $doc->save();
                                        $processedDocsInTask[] = $doc->id;
                                    }
                                }

                                // Eliminar documentos de esta tarea no incluidos en el builder
                                Documento::where('id_tarea', $tarea->id)
                                         ->whereNotIn('id', $processedDocsInTask)
                                         ->delete();
                            } else {
                                // Si no hay documentos en el builder para esta tarea, eliminar todos los existentes
                                Documento::where('id_tarea', $tarea->id)->delete();
                            }
                        }

                        // Eliminar tareas no incluidas en el builder
                        Tarea::where('id_etapa', $etapa->id)
                              ->whereNotIn('id', $processedTareas)
                              ->delete();
                    } else {
                        // Si no hay tareas en el builder, eliminar todas las existentes
                        Tarea::where('id_etapa', $etapa->id)->delete();
                    }

                    // Gestionar documentos antiguos - YA NO APLICA
                    // La columna id_etapa fue eliminada, por lo que no hay documentos antiguos que manejar
                    // Todos los documentos ahora se manejan dentro de las tareas
                    // Esta sección se mantiene por compatibilidad pero no hace nada
                    if (!empty($stData['documents'])) {
                        // No hay documentos antiguos que procesar
                        // Los documentos ahora solo existen dentro de las tareas
                    }
                }

                // Eliminar etapas no incluidas en el builder
                $etapasToDelete = Etapa::where('id_flujo', $flujo->id)
                                       ->whereNotIn('id', $processedEtapas)
                                       ->get();
                
                foreach ($etapasToDelete as $etapa) {
                    // Eliminar tareas y todos sus documentos asociados (nueva lógica)
                    $tareasIds = Tarea::where('id_etapa', $etapa->id)->pluck('id');
                    Documento::whereIn('id_tarea', $tareasIds)->delete(); // Documentos nuevos
                    Tarea::where('id_etapa', $etapa->id)->delete();
                    
                    // Ya no eliminamos documentos con id_etapa porque esa columna no existe
                    
                    $etapa->delete();
                }
            } else {
                // Si no hay stages en el builder, eliminar todo
                $etapas = Etapa::where('id_flujo',$flujo->id)->get();
                foreach ($etapas as $e) {
                    // Eliminar documentos nuevos (a través de tareas)
                    $tareasIds = Tarea::where('id_etapa', $e->id)->pluck('id');
                    Documento::whereIn('id_tarea', $tareasIds)->delete();
                    Tarea::where('id_etapa',$e->id)->delete();
                    
                    // Ya no eliminamos documentos con id_etapa porque esa columna no existe
                }
                Etapa::where('id_flujo',$flujo->id)->delete();
            }
        });

        return redirect()->route('flujos.index')->with('success','Flujo actualizado correctamente.');
    }

    /** Toggle estado de etapa */
    public function toggleEtapaEstado(Etapa $etapa)
    {
        $user = Auth::user();
        $isSuper = ($user->rol->nombre === 'SUPERADMIN');
        
        // Verificar permisos
        if (!$isSuper && $etapa->flujo->id_emp != $user->id_emp) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $etapa->estado = !$etapa->estado;
        $etapa->save();

        return response()->json([
            'success' => true,
            'estado' => $etapa->estado,
            'message' => 'Estado de etapa actualizado'
        ]);
    }

    /** Toggle estado de tarea */
    public function toggleTareaEstado(Tarea $tarea)
    {
        $user = Auth::user();
        $isSuper = ($user->rol->nombre === 'SUPERADMIN');
        
        // Verificar permisos
        if (!$isSuper && $tarea->etapa->flujo->id_emp != $user->id_emp) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        // Verificar si tiene detalles - no permitir desactivar si los tiene
        if ($tarea->estado && $tarea->detalles()->exists()) {
            return response()->json([
                'error' => 'No se puede desactivar la tarea porque ya tiene registros asociados'
            ], 422);
        }

        $tarea->estado = !$tarea->estado;
        $tarea->save();

        return response()->json([
            'success' => true,
            'estado' => $tarea->estado,
            'message' => 'Estado de tarea actualizado'
        ]);
    }

    /** Toggle estado de documento */
    public function toggleDocumentoEstado(Documento $documento)
    {
        $user = Auth::user();
        $isSuper = ($user->rol->nombre === 'SUPERADMIN');
        
        // Verificar permisos - ahora el documento pertenece a una tarea
        if (!$isSuper && $documento->tarea->etapa->flujo->id_emp != $user->id_emp) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        // Verificar si tiene detalles - no permitir desactivar si los tiene
        if ($documento->estado && $documento->detalles()->exists()) {
            return response()->json([
                'error' => 'No se puede desactivar el documento porque ya tiene registros asociados'
            ], 422);
        }

        $documento->estado = !$documento->estado;
        $documento->save();

        return response()->json([
            'success' => true,
            'estado' => $documento->estado,
            'message' => 'Estado de documento actualizado'
        ]);
    }
}
