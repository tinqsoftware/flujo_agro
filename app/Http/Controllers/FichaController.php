<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Ficha;
use App\Models\Flujo;
use App\Models\Etapa;
use App\Models\AtributoFicha;
use DB;
use App\Models\User;
use App\Models\Rol;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class FichaController extends Controller
{

    //Fichas

    public function index()
    {

        $user = Auth::user();
        if ($user->rol->nombre != 'SUPERADMIN') {
            $fichas = Ficha::with([ 'userCreate'])->where('id_emp',Auth::user()->id_emp)->paginate(10);
        }else{
            $fichas = Ficha::with([ 'userCreate'])->paginate(10);
        }

        return view('superadmin.fichas.index', compact('fichas'));
    }

    public function create()
    {

        if(Auth::user()->rol->nombre == 'SUPERADMIN'){
            $empresas = Empresa::where('estado', true)->orderBy('nombre')->get();
        }
        $empresas = Empresa::orderBy('nombre')->get(['id','nombre']);
        $tipos = ['Producto','Cliente','Proveedor','Flujo','Etapa'];
        $tiposCampo = [
            'texto' => 'Texto',
            'cajatexto' => 'Caja de Texto',
            'decimal' => 'Decimal',
            'entero' => 'Entero',
            'radio' => 'Radio Button',
            'desplegable' => 'Desplegable',
            'checkbox' => 'Checkbox',
            'fecha' => 'Fecha',
            'imagen' => 'Imagen',
        ];

        return view('superadmin.fichas.create', compact('empresas','tipos','tiposCampo'));
        
    }

    public function store(Request $request)
    {
        $empresaId = $request->input('id_emp') ?: Auth::user()->id_emp;
        $tipoFicha = $request->input('tipo');
        $soloUnicos = ['Producto','Cliente','Proveedor'];

        // Reglas base
        $rules = [
            'nombre'            => ['required','string','max:255'],
            'id_emp'            => ['nullable','exists:empresa,id'],
            'tipo'              => ['required', Rule::in(['Producto','Cliente','Proveedor','Flujo','Etapa'])],
            'campos'            => ['required','array','min:1'],
            'campos.*.nombre'   => ['required','string','max:255'],
            'campos.*.tipo'     => ['required', Rule::in([
                'texto','cajatexto','decimal','entero','radio','desplegable','checkbox','fecha','imagen'
            ])],
            'campos.*.nro'      => ['required','integer','min:1'],
            // estos dos serán requeridos solo según el tipo de ficha:
            'id_flujo'          => ['nullable','integer'],
            'id_etapa'          => ['nullable','integer'],
            'campos.*.ancho'       => ['nullable','integer','min:1','max:200'],
            'campos.*.opciones'    => ['nullable','string'],   // JSON en un input hidden
            'campos.*.obligatorio' => ['nullable','in:1'],     // checkbox (si no viene, queda null)
        ];

        // Unicidad por empresa (Producto/Cliente/Proveedor)
        if (in_array($tipoFicha, $soloUnicos, true)) {
            $rules['tipo'][] = Rule::unique('ficha','tipo')
                ->where(fn($q) => $q->where('id_emp', $empresaId));
        }

        // Para ficha tipo Flujo / Etapa, validamos existencia (pero no se guarda en ficha)
        if ($tipoFicha === 'Flujo') {
            $rules['id_flujo'] = ['required','exists:flujos,id'];
        }
        if ($tipoFicha === 'Etapa') {
            $rules['id_flujo'] = ['required','exists:flujos,id'];
            $rules['id_etapa'] = ['required','exists:etapas,id'];
        }

        // Validaciones condicionales por cada campo (ancho/opciones)
        $validated = $request->validate($rules);

        // Validación manual fina por tipo de campo:
        foreach ($validated['campos'] as $idx => $c) {
            $t = $c['tipo'];
            if (in_array($t, ['texto','cajatexto','decimal','entero'], true)) {
                if (!isset($c['ancho']) || $c['ancho'] === '') {
                    return back()->withErrors(["campos.$idx.ancho" => 'El ancho es obligatorio para este tipo de campo.'])
                                ->withInput();
                }
            }
            if (in_array($t, ['radio','desplegable','checkbox'], true)) {
                $ops = $c['opciones'] ?? '[]';
                $arr = is_string($ops) ? json_decode($ops, true) : (array)$ops;
                if (count(array_filter($arr, fn($v)=>trim((string)$v) !== '')) < 2) {
                    return back()->withErrors(["campos.$idx.opciones" => 'Debe registrar al menos 2 opciones.'])
                                ->withInput();
                }
            }
        }

        DB::transaction(function () use ($validated, $empresaId, $tipoFicha) {
            // 1) Crear ficha (sin id_flujo / id_etapa)
            $ficha = new \App\Models\Ficha();
            $ficha->nombre         = $validated['nombre'];
            $ficha->id_emp         = $empresaId;
            $ficha->tipo           = $tipoFicha;
            $ficha->estado         = 1;
            $ficha->id_user_create = Auth::id();
            $ficha->save();

            // 2) Guardar atributos de usuario
            foreach ($validated['campos'] as $c) {
                $attr = new \App\Models\AtributoFicha();
                $attr->id_ficha       = $ficha->id;
                $attr->nro            = (int)$c['nro'];                 // orden
                $attr->titulo         = $c['nombre'];
                $attr->tipo           = $c['tipo'];
                // ancho solo si vino (para tipos que lo usan)
                $attr->ancho          = $c['ancho'] ?? null;
                $attr->obligatorio    = !empty($c['obligatorio']) ? 1 : 0;

                // opciones solo para radio/desplegable/checkbox
                $json = null;
                if (isset($c['opciones']) && $c['opciones'] !== '') {
                    $json = is_string($c['opciones']) ? $c['opciones'] : json_encode($c['opciones']);
                }
                $attr->json           = $json;
                $attr->estado         = 1;
                $attr->id_user_create = Auth::id();
                $attr->save();
            }

            // 3) Guardar “contexto” si la ficha es de Flujo/Etapa (como atributo meta nro=0)
            if (in_array($tipoFicha, ['Flujo','Etapa'], true)) {
                $context = [
                    'id_flujo' => $validated['id_flujo'] ?? null,
                    'id_etapa' => $validated['id_etapa'] ?? null,
                ];
                $meta = new \App\Models\AtributoFicha();
                $meta->id_ficha       = $ficha->id;
                $meta->nro            = 0;                // reservado para meta
                $meta->titulo         = '_contexto';
                $meta->tipo           = 'meta';
                $meta->json           = json_encode($context);
                $meta->estado         = 1;
                $meta->id_user_create = Auth::id();
                $meta->save();
            }
        });

        return redirect()->route('fichas.index')->with('success', 'Ficha creada correctamente.');
    }

    public function editFicha(Ficha $empresa)
    {
        $administradores = User::whereHas('rol', function($query) {
            $query->where('nombre', 'ADMINISTRADOR');
        })->get();
        
        return view('superadmin.empresas.edit', compact('empresa', 'administradores'));
    }

    public function updateFicha(Request $request, Ficha $empresa)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'id_user_admin' => 'required|exists:users,id',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $data = [
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'id_user_admin' => $request->id_user_admin,
            'estado' => $request->has('estado'),
            'editable' => $request->has('editable')
        ];

        if ($request->hasFile('logo')) {
            // Eliminar logo anterior si existe
            if ($empresa->ruta_logo) {
                Storage::disk('public')->delete($empresa->ruta_logo);
            }
            $data['ruta_logo'] = $request->file('logo')->store('logos', 'public');
        }

        $empresa->update($data);

        return redirect()->route('empresas')->with('success', 'Empresa actualizada exitosamente');
    }

    public function destroyFicha(Ficha $empresa)
    {
        if ($empresa->ruta_logo) {
            Storage::disk('public')->delete($empresa->ruta_logo);
        }
        
        $empresa->delete();
        
        return redirect()->route('empresas')->with('success', 'Empresa eliminada exitosamente');
    }

    public function toggleFichaEstado(Ficha $empresa)
    {
        $empresa->update(['estado' => !$empresa->estado]);
        
        $estado = $empresa->estado ? 'activada' : 'desactivada';
        return response()->json([
            'success' => true,
            'message' => "Empresa {$estado} exitosamente"
        ]);
    }



    // === AJAX ===

    public function flujosByEmpresa(Request $request)
    {
        $empresaId = $request->query('empresa_id') ?: Auth::user()->id_emp;
        $flujos = Flujo::where('id_emp', $empresaId)
            ->where('estado',1)
            ->orderBy('nombre')->get(['id','nombre']);
        return response()->json($flujos);
    }

    public function etapasByFlujo(Request $request)
    {
        $flujoId = $request->query('flujo_id');
        $etapas = Etapa::where('id_flujo', $flujoId)
            ->where('estado',1)
            ->orderBy('nombre')->get(['id','nombre','id_flujo']);
        return response()->json($etapas);
    }

    public function checkTipoDisponible(Request $request)
    {
        $empresaId = $request->query('empresa_id') ?: Auth::user()->id_emp;
        $tipo = $request->query('tipo');

        $soloUnicos = ['Producto','Cliente','Proveedor'];
        if (!in_array($tipo, $soloUnicos, true)) {
            return response()->json(['disponible' => true]);
        }

        $existe = Ficha::where('id_emp',$empresaId)->where('tipo',$tipo)->exists();
        return response()->json(['disponible' => !$existe]);
    }





    // CRUD de Roles
    public function usuarios(Request $request)
    {
        $query = User::with(['rol', 'empresa']);
        
        // Filtros
        if ($request->filled('empresa')) {
            $query->where('id_emp', $request->empresa);
        }
        
        if ($request->filled('rol')) {
            $query->where('id_rol', $request->rol);
        }
        
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nombres', 'LIKE', "%{$search}%")
                  ->orWhere('apellidos', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }
        $user = Auth::user();
        if ($user->rol->nombre != 'SUPERADMIN') {
            $usuarios = $query->where('id_emp', $user->id_emp)->orderBy('created_at', 'desc')->paginate(15)->appends($request->query());
            
            // Para los filtros
            $empresas = Empresa::where('id',$user->id_emp)->where('estado', true)->orderBy('nombre')->get();
            $roles = Rol::where('id', '!=', 1)->where('estado', true)->orderBy('nombre')->get();

        }else{
            $usuarios = $query->orderBy('created_at', 'desc')->paginate(15)->appends($request->query());
            
            // Para los filtros
            $empresas = Empresa::where('estado', true)->orderBy('nombre')->get();
            $roles = Rol::where('estado', true)->orderBy('nombre')->get();
        }
        

        
        
        return view('superadmin.usuarios.index', compact('usuarios', 'empresas', 'roles'));
    }

    public function roles()
    {
        $roles = Rol::orderBy('id', 'asc')->paginate(10);
        if(Auth::user()->rol->nombre != 'SUPERADMIN'){
            $roles = Rol::where('id', '!=', 1)->where('estado', true)->paginate(10);  // ocultar rol id 1
        }
        
        return view('superadmin.roles.index', compact('roles'));
    }

    public function createRol()
    {
        return view('superadmin.roles.create');
    }

    public function storeRol(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:rol,nombre',
            'descripcion' => 'required|string'
        ]);

        Rol::create([
            'nombre' => strtoupper($request->nombre),
            'descripcion' => $request->descripcion,
            'estado' => true
        ]);

        return redirect()->route('roles')->with('success', 'Rol creado exitosamente');
    }

    public function editRol(Rol $rol)
    {
        return view('superadmin.roles.edit', compact('rol'));
    }

    public function updateRol(Request $request, Rol $rol)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:rol,nombre,'.$rol->id,
            'descripcion' => 'required|string'
        ]);

        $rol->update([
            'nombre' => strtoupper($request->nombre),
            'descripcion' => $request->descripcion,
            'estado' => $request->has('estado')
        ]);

        return redirect()->route('roles')->with('success', 'Rol actualizado exitosamente');
    }

    public function destroyRol(Rol $rol)
    {
        // Verificar que no hay usuarios con este rol
        if ($rol->users()->count() > 0) {
            return redirect()->route('roles')->with('error', 'No se puede eliminar el rol porque tiene usuarios asignados');
        }
        
        $rol->delete();
        
        return redirect()->route('roles')->with('success', 'Rol eliminado exitosamente');
    }

    public function toggleRolEstado(Rol $rol)
    {
        // No permitir cambiar estado de roles del sistema
        if (in_array($rol->nombre, ['SUPERADMIN', 'ADMINISTRADOR', 'ADMINISTRATIVO'])) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede cambiar el estado de los roles del sistema'
            ]);
        }

        $rol->update(['estado' => !$rol->estado]);
        
        $estado = $rol->estado ? 'activado' : 'desactivado';
        return response()->json([
            'success' => true,
            'message' => "Rol {$estado} exitosamente"
        ]);
    }
}
