<?php

namespace App\Http\Controllers;
use DB;
use App\Models\Empresa;
use App\Models\Ficha;
use App\Models\Flujo;
use App\Models\Etapa;
use App\Models\TipoFlujo;
use App\Models\Proveedor;
use App\Models\User;
use App\Models\Rol;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Cliente;
use App\Models\DatosAtributosFicha; // Alias del modelo de datos_atributos_fichas


class TipoFlujoController extends Controller
{

    /** Listado (solo tabla) con filtros, búsqueda y orden */
    public function index(Request $request)
    {
        $user    = Auth::user();
        $isSuper = ($user->rol->nombre === 'SUPERADMIN');

        // UI state
        $estado = $request->get('estado','todos');        // todos|activos|inactivos
        $q      = trim((string) $request->get('q',''));
        $sort   = $request->get('sort','created_at');     // nombre|created_at|estado|empresa
        $dir    = strtolower($request->get('dir','desc')) === 'asc' ? 'asc' : 'desc';

        $query = TipoFlujo::with(['empresa','userCreate']);

        if (!$isSuper) {
            $query->where('id_emp', $user->id_emp);
        }

        if ($estado === 'activos')   $query->where('estado',1);
        if ($estado === 'inactivos') $query->where('estado',0);

        if ($q !== '') {
            $query->where(function($s) use ($q) {
                $s->where('nombre','like',"%{$q}%")
                  ->orWhere('descripcion','like',"%{$q}%");
            });
        }

        // sort whitelisted
        if (!in_array($sort, ['nombre','created_at','estado','empresa'], true)) {
            $sort = 'created_at';
        }
        if ($sort === 'empresa') {
            $query->join('empresa as e','e.id','=','tipo_flujo.id_emp')
                  ->orderBy('e.nombre',$dir)
                  ->select('tipo_flujo.*');
        } else {
            $query->orderBy($sort,$dir);
        }

        $tipos = $query->paginate(15)->appends($request->query());

        return view('superadmin.tipo_flujo.index', [
            'tipos'  => $tipos,
            'isSuper'=> $isSuper,
            'estado' => $estado,
            'q'      => $q,
            'sort'   => $sort,
            'dir'    => $dir,
        ]);
    }

    /** Crear */
    public function create()
    {
        $user    = Auth::user();
        $isSuper = ($user->rol->nombre === 'SUPERADMIN');

        $empresas = $isSuper
            ? Empresa::where('estado',1)->orderBy('nombre')->get(['id','nombre'])
            : collect(); // no se usa en no-super

        return view('superadmin.tipo_flujo.create', compact('isSuper','empresas'));
    }

    /** Guardar */
    public function store(Request $request)
    {
        $user    = Auth::user();
        $isSuper = ($user->rol->nombre === 'SUPERADMIN');

        $rules = [
            'nombre'      => ['required','string','max:255'],
            'descripcion' => ['nullable','string'],
        ];
        if ($isSuper) {
            $rules['id_emp'] = ['required','exists:empresa,id'];
        }
        $validated = $request->validate($rules);

        $tf = new TipoFlujo();
        $tf->nombre         = $validated['nombre'];
        $tf->descripcion    = $validated['descripcion'] ?? null;
        $tf->id_emp         = $isSuper ? (int)$validated['id_emp'] : $user->id_emp;
        $tf->id_user_create = $user->id;
        $tf->estado         = 1;
        $tf->save();

        return redirect()->route('tipo-flujo.index')->with('success','Tipo de flujo creado.');
    }

    /** Editar */
    public function edit(TipoFlujo $tipo_flujo)
    {
        $user    = Auth::user();
        $isSuper = ($user->rol->nombre === 'SUPERADMIN');

        if (!$isSuper && $tipo_flujo->id_emp != $user->id_emp) {
            abort(403);
        }

        $empresas = $isSuper
            ? Empresa::where('estado',1)->orderBy('nombre')->get(['id','nombre'])
            : collect();

        return view('superadmin.tipo_flujo.edit', [
            'tipo'     => $tipo_flujo,
            'isSuper'  => $isSuper,
            'empresas' => $empresas,
        ]);
    }

    /** Actualizar */
    public function update(Request $request, TipoFlujo $tipo_flujo)
    {
        $user    = Auth::user();
        $isSuper = ($user->rol->nombre === 'SUPERADMIN');

        if (!$isSuper && $tipo_flujo->id_emp != $user->id_emp) {
            abort(403);
        }

        $rules = [
            'nombre'      => ['required','string','max:255'],
            'descripcion' => ['nullable','string'],
            'estado'      => ['nullable','boolean'],
        ];
        if ($isSuper) {
            $rules['id_emp'] = ['required','exists:empresa,id'];
        }
        $validated = $request->validate($rules);

        $tipo_flujo->nombre      = $validated['nombre'];
        $tipo_flujo->descripcion = $validated['descripcion'] ?? null;
        $tipo_flujo->estado      = $request->boolean('estado');
        if ($isSuper) {
            $tipo_flujo->id_emp = (int)$validated['id_emp'];
        }
        $tipo_flujo->save();

        return redirect()->route('tipo-flujo.index')->with('success','Tipo de flujo actualizado.');
    }

    /** Eliminar (opcional) */
    public function destroy(TipoFlujo $tipo_flujo)
    {
        $user    = Auth::user();
        $isSuper = ($user->rol->nombre === 'SUPERADMIN');
        if (!$isSuper && $tipo_flujo->id_emp != $user->id_emp) {
            abort(403);
        }

        $tipo_flujo->delete();
        return back()->with('success','Tipo de flujo eliminado.');
    }
   
}
