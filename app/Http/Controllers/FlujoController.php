<?php

namespace App\Http\Controllers;
use DB;
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
                ->withCount(['tareas','documentos'])
                ->whereIn('id_flujo',$ids)->orderBy('nro')->get()
                ->groupBy('id_flujo');
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

        $treeJson = json_encode(['stages'=>[]]); // builder vacío

        return view('superadmin.flujos.create', compact('empresas','tipos','isSuper','treeJson'));
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
                        $ta->estado        = 1;
                        $ta->save();
                    }
                    foreach (($st['documents'] ?? []) as $d) {
                        $doc = new Documento();
                        $doc->id_etapa      = $et->id;
                        $doc->nombre        = $d['name'] ?? 'Documento';
                        $doc->descripcion   = $d['description'] ?? null;
                        $doc->id_user_create= $user->id;
                        $doc->estado        = 1;
                        $doc->save();
                    }
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

        // Tree desde BD
        $stages = Etapa::where('id_flujo',$flujo->id)->orderBy('nro')->get();
        $tree = ['stages'=>[]];
        foreach ($stages as $st) {
            $tree['stages'][] = [
                'id'         => $st->id,
                'name'       => $st->nombre,
                'description'=> $st->descripcion,
                'nro'        => (int)$st->nro,
                'paralelo'   => (int)$st->paralelo,
                'tasks'      => Tarea::where('id_etapa',$st->id)->get(['id','nombre as name','descripcion as description'])->toArray(),
                'documents'  => Documento::where('id_etapa',$st->id)->get(['id','nombre as name','descripcion as description'])->toArray(),
            ];
        }
        $treeJson = json_encode($tree);

        return view('superadmin.flujos.edit', compact('flujo','empresas','tipos','isSuper','treeJson'));
    }

    /** UPDATE: idem store pero limpiando/recreando árbol (simple y seguro) */
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

            // reconstruye árbol
            $builder = json_decode($request->input('builder',''), true);

            // Limpio actual
            $etapas = Etapa::where('id_flujo',$flujo->id)->get();
            foreach ($etapas as $e) {
                Tarea::where('id_etapa',$e->id)->delete();
                Documento::where('id_etapa',$e->id)->delete();
            }
            Etapa::where('id_flujo',$flujo->id)->delete();

            if (is_array($builder) && !empty($builder['stages'])) {
                foreach ($builder['stages'] as $st) {
                    $et = new Etapa();
                    $et->id_flujo  = $flujo->id;
                    $et->nombre    = $st['name'] ?? 'Etapa';
                    $et->descripcion = $st['description'] ?? null;
                    $et->nro       = (int)($st['nro'] ?? 1);
                    $et->paralelo  = !empty($st['paralelo']) ? 1 : 0;
                    $et->id_user_create = $flujo->id_user_create;
                    $et->estado    = 1;
                    $et->save();

                    foreach (($st['tasks'] ?? []) as $t) {
                        $ta = new Tarea();
                        $ta->id_etapa      = $et->id;
                        $ta->nombre        = $t['name'] ?? 'Tarea';
                        $ta->descripcion   = $t['description'] ?? null;
                        $ta->id_user_create= $flujo->id_user_create;
                        $ta->estado        = 1;
                        $ta->save();
                    }
                    foreach (($st['documents'] ?? []) as $d) {
                        $doc = new Documento();
                        $doc->id_etapa      = $et->id;
                        $doc->nombre        = $d['name'] ?? 'Documento';
                        $doc->descripcion   = $d['description'] ?? null;
                        $doc->id_user_create= $flujo->id_user_create;
                        $doc->estado        = 1;
                        $doc->save();
                    }
                }
            }
        });

        return redirect()->route('flujos.index')->with('success','Flujo actualizado correctamente.');
    }
}
