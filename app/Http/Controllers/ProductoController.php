<?php

namespace App\Http\Controllers;
use DB;
use App\Models\Empresa;
use App\Models\Ficha;
use App\Models\Flujo;
use App\Models\Etapa;
use App\Models\AtributoFicha;
use App\Models\Producto;
use App\Models\User;
use App\Models\Rol;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Cliente;
use App\Models\DatosAtributosFicha; // Alias del modelo de datos_atributos_fichas


class ProductoController extends Controller
{
    /** Listado con filtros, buscador, toggle de vista y sort */
    public function index(Request $request)
    {
        $user    = Auth::user();
        $isSuper = ($user->rol->nombre === 'SUPERADMIN');

        // UI state
        $estado = $request->get('estado', 'todos');            // todos | activos | inactivos
        $q      = trim((string) $request->get('q', ''));
        $vista  = $request->get('vista', 'cards');             // cards | tabla
        $sort   = $request->get('sort', 'created_at');         // nombre | created_at | estado | fecha_inicio
        $dir    = strtolower($request->get('dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $query = Producto::with(['empresa','userCreate']);

        // ámbito empresa
        if (!$isSuper) {
            $query->where('id_emp', $user->id_emp);
        }

        // estado
        if ($estado === 'activos')   $query->where('estado', 1);
        if ($estado === 'inactivos') $query->where('estado', 0);

        // búsqueda por nombre y descripción
        if ($q !== '') {
            $query->where(function($s) use ($q){
                $s->where('nombre','like',"%{$q}%")
                  ->orWhere('descripcion','like',"%{$q}%");
            });
        }

        // sort
        if (!in_array($sort, ['nombre','created_at','estado','fecha_inicio'], true)) {
            $sort = 'created_at';
        }
        $query->orderBy($sort, $dir);

        $productos = $query->paginate(12)->appends($request->query());

        // ===== Ficha & atributos tipo Producto =====
        $empresaId = $isSuper ? null : $user->id_emp;

        $ficha = Ficha::when($empresaId, fn($q) => $q->where('id_emp',$empresaId))
            ->where('tipo','Producto')->first();

        $atributos = $ficha
            ? AtributoFicha::where('id_ficha',$ficha->id)->where('nro','>',0)->orderBy('nro')->get()
            : collect();

        // ===== Datos para TARJETAS (primeros 5) =====
        if ($atributos->isNotEmpty()) {
            $primeros = $atributos->take(5)->pluck('id')->toArray();
            foreach ($productos as $p) {
                $vals = DatosAtributosFicha::where('id_relacion',$p->id)
                    ->whereIn('id_atributo',$primeros)
                    ->get()->keyBy('id_atributo');

                $p->resumenAtributos = $atributos->take(5)->map(function($a) use ($vals){
                    $v = $vals->get($a->id);
                    $valor = $v ? ($v->json ?? $v->dato) : null;
                    if ($a->tipo === 'checkbox' && $valor) {
                        $arr = is_string($valor) ? json_decode($valor,true) : (array)$valor;
                        $valor = implode(', ', $arr);
                    }
                    return ['titulo'=>$a->titulo,'valor'=>$valor];
                });
                $p->otrosAtributosCount = max(0, $atributos->count() - 5);
            }
        } else {
            foreach ($productos as $p) {
                $p->resumenAtributos = collect();
                $p->otrosAtributosCount = 0;
            }
        }

        // ===== Datos para TABLA (todos los atributos visibles en la página) =====
        $valoresByProducto = [];
        if ($vista === 'tabla' && $atributos->isNotEmpty() && $productos->count()) {
            $attrIds = $atributos->pluck('id');
            $prodIds = $productos->pluck('id');

            $rows = DatosAtributosFicha::whereIn('id_atributo',$attrIds)
                ->whereIn('id_relacion',$prodIds)
                ->get();

            foreach ($rows as $r) {
                $valor = $r->json ?? $r->dato;
                if (is_string($valor) && $this->looksLikeJsonArray($valor)) {
                    $arr = json_decode($valor,true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($arr)) {
                        $valor = implode(', ', $arr);
                    }
                }
                $valoresByProducto[$r->id_relacion][$r->id_atributo] = $valor;
            }
        }

        return view('superadmin.productos.index', [
            'productos'        => $productos,
            'atributos'        => $atributos,
            'ficha'            => $ficha,
            'isSuper'          => $isSuper,
            'vista'            => $vista,
            'estado'           => $estado,
            'q'                => $q,
            'sort'             => $sort,
            'dir'              => $dir,
            'valoresByProducto'=> $valoresByProducto,
        ]);
    }

    /** Helper */
    private function looksLikeJsonArray(?string $s): bool
    {
        if ($s === null) return false;
        $s = trim($s);
        return strlen($s) >= 2 && $s[0] === '[' && substr($s,-1) === ']';
    }

    /** Form crear */
    public function create()
    {
        $user    = Auth::user();
        $isSuper = ($user->rol->nombre === 'SUPERADMIN');

        $empresas = $isSuper
            ? Empresa::where('estado',1)->orderBy('nombre')->get(['id','nombre'])
            : Empresa::where('id',$user->id_emp)->get(['id','nombre']);

        $ficha = Ficha::where('id_emp', $isSuper ? ($empresas->first()->id ?? null) : $user->id_emp)
            ->where('tipo','Producto')->first();

        $atributos = $ficha
            ? AtributoFicha::where('id_ficha',$ficha->id)->where('nro','>',0)->orderBy('nro')->get()
            : collect();

        return view('superadmin.productos.create', compact('empresas','atributos','ficha','isSuper'));
    }

    /** Guardar */
    public function store(Request $request)
    {
        $user      = Auth::user();
        $isSuper   = ($user->rol->nombre === 'SUPERADMIN');
        $empresaId = $request->input('id_emp') ?: $user->id_emp;

        $ficha = Ficha::where('id_emp',$empresaId)->where('tipo','Producto')->first();
        $atributos = $ficha
            ? AtributoFicha::where('id_ficha',$ficha->id)->where('nro','>',0)->orderBy('nro')->get()
            : collect();

        // reglas base (incluye descripcion y fecha_inicio opcionales)
        $rules = [
            'nombre'       => ['required','string','max:255'],
            'descripcion'  => ['nullable','string'],
            'fecha_inicio' => ['nullable','date'],
            'ruta_foto'    => ['nullable','image','mimes:jpeg,png,jpg,gif,webp','max:4096'],
        ];
        if ($isSuper) $rules['id_emp'] = ['required','exists:empresa,id'];

        // reglas dinámicas por atributo
        foreach ($atributos as $a) {
            $key = "atributos.{$a->id}";
            $r = [];
            if ($a->obligatorio) $r[] = 'required';

            switch ($a->tipo) {
                case 'texto':
                case 'cajatexto': $a->ancho && $r[] = "max:{$a->ancho}"; $r[]='string'; break;
                case 'entero':    $r[]='integer';  break;
                case 'decimal':   $r[]='numeric';  break;
                case 'fecha':     $r[]='date';     break;
                case 'desplegable':
                case 'radio':
                    $ops = $a->json ? json_decode($a->json,true) : [];
                    if (is_array($ops) && count($ops)) $r[] = Rule::in($ops);
                    break;
                case 'checkbox':
                    $key = "atributos.{$a->id}"; $r[]='array'; if ($a->obligatorio) $r[]='min:1'; break;
                case 'imagen':
                    $rules["atributos_archivo.{$a->id}"] = ['nullable','image','mimes:jpeg,png,jpg,gif','max:4096'];
                    break;
            }
            if ($a->tipo !== 'imagen') $rules[$key] = $r;
        }

        $validated = $request->validate($rules);

        $prod = new Producto();
        $prod->nombre         = $validated['nombre'];
        $prod->descripcion    = $validated['descripcion'] ?? null;
        $prod->fecha_inicio   = $validated['fecha_inicio'] ?? null;
        $prod->id_emp         = $empresaId;
        $prod->id_user_create = $user->id;
        $prod->estado         = 1;
        if ($ficha) $prod->id_ficha = $ficha->id;
        // subir imagen si viene
        if ($request->hasFile('ruta_foto')) {
            $prod->ruta_foto = $request->file('ruta_foto')->store('productos','public'); // <---
        }
        $prod->save();

        // atributos
        foreach ($atributos as $a) {
            $dato = null; $json = null;
            switch ($a->tipo) {
                case 'texto':
                case 'cajatexto':
                case 'entero':
                case 'decimal':
                case 'fecha':
                case 'desplegable':
                case 'radio':
                    $dato = $request->input("atributos.{$a->id}");
                    break;
                case 'checkbox':
                    $arr  = (array) $request->input("atributos.{$a->id}", []);
                    $json = json_encode(array_values($arr));
                    break;
                case 'imagen':
                    if ($request->hasFile("atributos_archivo.{$a->id}")) {
                        $dato = $request->file("atributos_archivo.{$a->id}")
                                ->store('producto_attrs','public');
                    } else {
                        $dato = $request->input("atributos.{$a->id}");
                    }
                    break;
            }

            DatosAtributosFicha::create([
                'id_atributo'   => $a->id,
                'id_relacion'   => $prod->id,
                'dato'          => $dato,
                'json'          => $json,
                'id_user_create'=> $user->id,
            ]);
        }

        return redirect()->route('productos.index')->with('success','Producto creado correctamente.');
    }

    /** Form editar */
    public function edit(Producto $producto)
    {
        $user = Auth::user();
        $isSuper = ($user->rol->nombre === 'SUPERADMIN');
        if (!$isSuper && $producto->id_emp != $user->id_emp) abort(403);

        $empresas = $isSuper
            ? Empresa::where('estado',1)->orderBy('nombre')->get(['id','nombre'])
            : Empresa::where('id',$user->id_emp)->get(['id','nombre']);

        $atributos = collect(); $valores = [];
        if ($producto->id_ficha) {
            $atributos = AtributoFicha::where('id_ficha',$producto->id_ficha)
                ->where('nro','>',0)->orderBy('nro')->get();

            $rows = DatosAtributosFicha::where('id_relacion',$producto->id)
                ->whereIn('id_atributo',$atributos->pluck('id'))->get();

            foreach ($rows as $r) {
                $valores[$r->id_atributo] = $r->json ?? $r->dato;
            }
        }

        return view('superadmin.productos.edit', compact('producto','empresas','atributos','valores','isSuper'));
    }

    /** Actualizar */
    public function update(Request $request, Producto $producto)
    {
        $user = Auth::user();
        $isSuper = ($user->rol->nombre === 'SUPERADMIN');
        if (!$isSuper && $producto->id_emp != $user->id_emp) abort(403);

        $empresaId = $request->input('id_emp') ?: $producto->id_emp;

        $rules = [
            'nombre'       => ['required','string','max:255'],
            'descripcion'  => ['nullable','string'],
            'fecha_inicio' => ['nullable','date'],
            'id_emp'       => ['nullable','exists:empresa,id'],
            'estado'       => ['nullable','boolean'],
            'ruta_foto'    => ['nullable','image','mimes:jpeg,png,jpg,gif,webp','max:4096'], // <--- NUEVO
        ];
        $validated = $request->validate($rules);

        DB::transaction(function () use ($request,$validated,$producto,$empresaId,$user) {
            $producto->nombre       = $validated['nombre'];
            $producto->descripcion  = $validated['descripcion'] ?? null;
            $producto->fecha_inicio = $validated['fecha_inicio'] ?? null;
            $producto->id_emp       = $empresaId;
            $producto->estado       = $request->boolean('estado');
            
            // ¿eliminar foto actual?
            if ($request->boolean('eliminar_foto') && $producto->ruta_foto) {
                \Storage::disk('public')->delete($producto->ruta_foto);
                $producto->ruta_foto = null;
            }

            // ¿subió nueva foto?
            if ($request->hasFile('ruta_foto')) {
                if ($producto->ruta_foto) {
                    \Storage::disk('public')->delete($producto->ruta_foto);
                }
                $producto->ruta_foto = $request->file('ruta_foto')->store('productos','public');
            }

            $producto->save();

            if ($producto->id_ficha) {
                $atributos = AtributoFicha::where('id_ficha',$producto->id_ficha)->where('nro','>',0)->get();
                $valsReq   = (array) $request->input('atributos', []);

                foreach ($atributos as $attr) {
                    $valorReq = $valsReq[$attr->id] ?? null;

                    $dato = DatosAtributosFicha::where('id_atributo',$attr->id)
                        ->where('id_relacion',$producto->id)
                        ->first();

                    if (!$dato) {
                        $dato = new DatosAtributosFicha();
                        $dato->id_atributo    = $attr->id;
                        $dato->id_relacion    = $producto->id;
                        $dato->id_user_create = $user->id;
                    }

                    $dato->dato = null; $dato->json = null;

                    switch ($attr->tipo) {
                        case 'checkbox':
                            $dato->json = json_encode(is_array($valorReq) ? array_values($valorReq) : []);
                            break;

                        case 'imagen':
                            $del = $request->boolean("atributos_eliminar.$attr->id");
                            if ($del) {
                                if (!empty($dato->dato)) \Storage::disk('public')->delete($dato->dato);
                                $dato->dato = null; break;
                            }
                            if ($request->hasFile("atributos_archivo.$attr->id")) {
                                if (!empty($dato->dato)) \Storage::disk('public')->delete($dato->dato);
                                $dato->dato = $request->file("atributos_archivo.$attr->id")
                                    ->store('producto_attrs','public');
                            } else {
                                if ($valorReq !== null) $dato->dato = (string) $valorReq; // conserva hidden
                            }
                            break;

                        default:
                            $dato->dato = is_array($valorReq) ? json_encode($valorReq) : ($valorReq !== null ? (string)$valorReq : null);
                            break;
                    }

                    $dato->save();
                }
            }
        });

        return redirect()->route('productos.index')->with('success','Producto actualizado correctamente.');
    }

    /** AJAX: atributos de ficha Producto por empresa */
    public function atributosByEmpresa(Request $request)
    {
        $empresaId = $request->query('empresa_id') ?: Auth::user()->id_emp;

        $ficha = Ficha::where('id_emp',$empresaId)->where('tipo','Producto')->first();
        if (!$ficha) return response()->json([]);

        $attrs = AtributoFicha::where('id_ficha',$ficha->id)
            ->where('nro','>',0)->orderBy('nro')->get(['id','titulo','tipo','ancho','json','obligatorio','nro']);

        return response()->json(
            $attrs->map(function($a){
                return [
                    'id'          => $a->id,
                    'titulo'      => $a->titulo,
                    'tipo'        => $a->tipo,
                    'ancho'       => $a->ancho,
                    'opciones'    => (in_array($a->tipo,['radio','desplegable','checkbox']) && $a->json) ? json_decode($a->json,true) : null,
                    'obligatorio' => (bool)$a->obligatorio,
                    'nro'         => $a->nro,
                ];
            })
        );
    }

    /** (Opcional) eliminar registro */
    public function destroy(Producto $producto)
    {
        $user = Auth::user();
        $isSuper = ($user->rol->nombre === 'SUPERADMIN');
        if (!$isSuper && $producto->id_emp != $user->id_emp) abort(403);

        // Si quieres borrar también archivos de atributos imagen:
        // foreach (DatosAtributosFicha::where('id_relacion',$producto->id)->get() as $r) {
        //     if ($r->dato && \Storage::disk('public')->exists($r->dato)) \Storage::disk('public')->delete($r->dato);
        //     $r->delete();
        // }

        $producto->delete();
        return back()->with('success','Producto eliminado correctamente.');
    }
   
}
