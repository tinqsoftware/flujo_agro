<?php

namespace App\Http\Controllers;
use DB;
use App\Models\Empresa;
use App\Models\Ficha;
use App\Models\Flujo;
use App\Models\Etapa;
use App\Models\AtributoFicha;
use App\Models\Proveedor;
use App\Models\User;
use App\Models\Rol;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Cliente;
use App\Models\FichaListItem;
use App\Models\FichaRelationLink;

use App\Models\DatosAtributosFicha; // Alias del modelo de datos_atributos_fichas


class ProveedorController extends Controller
{
     /** Listado con filtros, buscador, toggle de vista y sort */
    public function index(Request $request)
    {
        $user    = Auth::user();
        $isSuper = ($user->rol->nombre === 'SUPERADMIN');

        // UI state
        $estado = $request->get('estado', 'todos');      // todos | activos | inactivos
        $q      = trim((string) $request->get('q', ''));
        $vista  = $request->get('vista', 'cards');       // cards | tabla
        $sort   = $request->get('sort', 'created_at');   // nombre | created_at | estado
        $dir    = strtolower($request->get('dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $query = Proveedor::with(['empresa','userCreate']);

        // ámbito empresa
        if (!$isSuper) {
            $query->where('id_emp', $user->id_emp);
        }

        // estado
        if ($estado === 'activos')   $query->where('estado', 1);
        if ($estado === 'inactivos') $query->where('estado', 0);

        // búsqueda
        if ($q !== '') {
            $query->where('nombre', 'like', "%{$q}%");
        }

        // sort
        if (!in_array($sort, ['nombre','created_at','estado'], true)) {
            $sort = 'created_at';
        }
        $query->orderBy($sort, $dir);

        $proveedores = $query->paginate(12)->appends($request->query());

        // Ficha y atributos (tipo Proveedor)
        $empresaId = $isSuper ? null : $user->id_emp;
        $ficha = Ficha::when($empresaId, fn($q) => $q->where('id_emp',$empresaId))
            ->where('tipo','Proveedor')->first();

        $atributos = $ficha
            ? AtributoFicha::where('id_ficha',$ficha->id)->where('nro','>',0)->orderBy('nro')->get()
            : collect();

        // ===== Datos para TARJETAS (primeros 5) =====
        if ($atributos->isNotEmpty()) {
            $primeros = $atributos->take(5)->pluck('id')->toArray();
            foreach ($proveedores as $p) {
                $vals = DatosAtributosFicha::where('id_relacion',$p->id)
                    ->whereIn('id_atributo',$primeros)
                    ->get()->keyBy('id_atributo');

                $p->resumenAtributos = $atributos->take(5)->map(function($a) use ($vals) {
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
            foreach ($proveedores as $p) {
                $p->resumenAtributos = collect();
                $p->otrosAtributosCount = 0;
            }
        }

        // ===== Datos para TABLA (todos los atributos de la página) =====
        $valoresByProveedor = [];
        if ($vista === 'tabla' && $atributos->isNotEmpty() && $proveedores->count()) {
            $attrIds = $atributos->pluck('id');
            $provIds = $proveedores->pluck('id');

            $rows = DatosAtributosFicha::whereIn('id_atributo',$attrIds)
                ->whereIn('id_relacion',$provIds)->get();

            foreach ($rows as $r) {
                $valor = $r->json ?? $r->dato;
                if (is_string($valor) && $this->looksLikeJsonArray($valor)) {
                    $arr = json_decode($valor,true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($arr)) {
                        $valor = implode(', ', $arr);
                    }
                }
                $valoresByProveedor[$r->id_relacion][$r->id_atributo] = $valor;
            }
        }

        return view('superadmin.proveedores.index', [
            'proveedores'       => $proveedores,
            'atributos'         => $atributos,
            'ficha'             => $ficha,
            'isSuper'           => $isSuper,
            'vista'             => $vista,
            'estado'            => $estado,
            'q'                 => $q,
            'sort'              => $sort,
            'dir'               => $dir,
            'valoresByProveedor'=> $valoresByProveedor,
        ]);
    }

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
            ->where('tipo','Proveedor')->first();

        $atributos = $ficha
            ? AtributoFicha::where('id_ficha',$ficha->id)->where('nro','>',0)->orderBy('nro')->get()
            : collect();

        $groupDefs  = $this->loadGroupDefsForProv($ficha?->id);
        $relOptions = $this->loadRelationOptions($ficha?->id_emp);

        return view('superadmin.proveedores.create', compact('groupDefs','relOptions','empresas','atributos','ficha','isSuper'));
    }

    /** Guardar */
    public function store(Request $request)
    {
        $user      = Auth::user();
        $empresaId = $request->input('id_emp') ?: $user->id_emp;
        $isSuper   = ($user->rol->nombre === 'SUPERADMIN');

        $ficha = Ficha::where('id_emp',$empresaId)->where('tipo','Proveedor')->first();
        $atributos = $ficha
            ? AtributoFicha::where('id_ficha',$ficha->id)->where('nro','>',0)->orderBy('nro')->get()
            : collect();

        $rules = [
            'nombre' => ['required','string','max:255'],
            'logo'   => ['nullable','image','mimes:jpeg,png,jpg,gif','max:2048'],
        ];
        if ($isSuper) $rules['id_emp'] = ['required','exists:empresa,id'];

        // validación dinámica
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

        $prov = new Proveedor();
        $prov->nombre         = $validated['nombre'];
        $prov->id_emp         = $empresaId;
        $prov->id_user_create = $user->id;
        $prov->estado         = 1;

        if ($request->hasFile('logo')) {
            $prov->ruta_logo = $request->file('logo')->store('proveedores','public');
        }
        if ($ficha) $prov->id_ficha = $ficha->id;
        $prov->save();
        $this->saveFichaValuesForProveedor($prov->id, $request);
        // guardar atributos
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
                    $arr = (array) $request->input("atributos.{$a->id}", []);
                    $json = json_encode(array_values($arr));
                    break;
                case 'imagen':
                    if ($request->hasFile("atributos_archivo.{$a->id}")) {
                        $dato = $request->file("atributos_archivo.{$a->id}")
                                ->store('proveedor_attrs','public');
                    } else {
                        $dato = $request->input("atributos.{$a->id}");
                    }
                    break;
            }

            DatosAtributosFicha::create([
                'id_atributo'   => $a->id,
                'id_relacion'   => $prov->id,
                'dato'          => $dato,
                'json'          => $json,
                'id_user_create'=> $user->id,
                'isSuper'     => $isSuper,
            ]);
        }

        return redirect()->route('proveedores.index')->with('success','Proveedor creado correctamente.');
    }

    /** Form editar */
    public function edit(Proveedor $proveedor)
    {
        $user = Auth::user();
        $isSuper = ($user->rol->nombre === 'SUPERADMIN');
        $empresas = $isSuper
            ? Empresa::where('estado',1)->orderBy('nombre')->get(['id','nombre'])
            : Empresa::where('id',$user->id_emp)->get(['id','nombre']);

        $ficha = Ficha::where('id_emp', $user->rol->nombre === 'SUPERADMIN' ? ($empresas->first()->id ?? null) : $user->id_emp)
            ->where('tipo','Proveedor')->first();
        $groupDefs  = $this->loadGroupDefsForProv($ficha?->id);
        $relOptions = $this->loadRelationOptions($ficha?->id_emp);

        if (!$isSuper && $proveedor->id_emp != $user->id_emp) {
            abort(403);
        }

        $atributos = collect(); 
        $valores = [];
        if ($proveedor->id_ficha) {
            $atributos = AtributoFicha::where('id_ficha',$proveedor->id_ficha)
                ->where('nro','>',0)->orderBy('nro')->get();

            $rows = DatosAtributosFicha::where('id_relacion',$proveedor->id)
                ->whereIn('id_atributo',$atributos->pluck('id'))
                ->get();

            foreach ($rows as $r) {
                $valores[$r->id_atributo] = $r->json ?? $r->dato;
            }
        }

        // Valores actuales (para pre-llenar el form)
        $listValues = FichaListItem::where('entity_type','proveedor')
            ->where('entity_id',$proveedor->id)->get()->groupBy('group_code');

        $relValues  = FichaRelationLink::where('entity_type','proveedor')
            ->where('entity_id',$proveedor->id)->get()->groupBy('group_code');


        return view('superadmin.proveedores.edit', compact('proveedor','groupDefs','relOptions','listValues','relValues','empresas','atributos','valores','isSuper'));
    }

    /** Actualizar */
    public function update(Request $request, Proveedor $proveedor)
    {
        $user = Auth::user();
        $isSuper = ($user->rol->nombre === 'SUPERADMIN');
        if (!$isSuper && $proveedor->id_emp != $user->id_emp) abort(403);

        $empresaId = $request->input('id_emp') ?: $proveedor->id_emp;

        $rules = [
            'nombre' => ['required','string','max:255'],
            'logo'   => ['nullable','image','mimes:jpeg,png,jpg,gif','max:2048'],
            'id_emp' => ['nullable','exists:empresa,id'],
            'estado' => ['nullable','boolean'],
        ];
        $validated = $request->validate($rules);

        DB::transaction(function () use ($request,$validated,$proveedor,$empresaId,$user) {
            $proveedor->nombre = $validated['nombre'];
            $proveedor->id_emp = $empresaId;
            $proveedor->estado = $request->boolean('estado');

            if ($request->hasFile('logo')) {
                if ($proveedor->ruta_logo) {
                    \Storage::disk('public')->delete($proveedor->ruta_logo);
                }
                $proveedor->ruta_logo = $request->file('logo')->store('logos/proveedores','public');
            }

            $proveedor->save();

            if ($proveedor->id_ficha) {
                $atributos = AtributoFicha::where('id_ficha',$proveedor->id_ficha)
                ->where('nro','>',0)->get();
                $valsReq = (array) $request->input('atributos', []);

                foreach ($atributos as $attr) {
                    $valorReq = $valsReq[$attr->id] ?? null;

                    $dato = DatosAtributosFicha::where('id_atributo',$attr->id)
                        ->where('id_relacion',$proveedor->id)
                        ->first();

                    if (!$dato) {
                        $dato = new DatosAtributosFicha();
                        $dato->id_atributo    = $attr->id;
                        $dato->id_relacion    = $proveedor->id;
                        $dato->id_user_create = $user->id;
                    }

                    $dato->dato = null; 
                    $dato->json = null;

                    switch ($attr->tipo) {
                        case 'checkbox':
                            $dato->json = json_encode(is_array($valorReq) ? array_values($valorReq) : []);
                            break;

                        case 'imagen':
                            $del = $request->boolean("atributos_eliminar.$attr->id");
                            if ($del) {
                                if (!empty($dato->dato)) Storage::disk('public')->delete($dato->dato);
                                $dato->dato = null; break;
                            }

                            if ($request->hasFile("atributos_archivo.$attr->id")) {
                                if (!empty($dato->dato)) Storage::disk('public')->delete($dato->dato);
                                $dato->dato = $request->file("atributos_archivo.$attr->id")
                                    ->store('proveedor_attrs','public');
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
         $this->saveFichaValuesForProveedor($proveedor->id, $request);

        return redirect()->route('proveedores.index')->with('success','Proveedor actualizado correctamente.');
    }

    /** (Opcional) eliminar registro completo */
    public function destroy(Proveedor $proveedor)
    {
        $user = Auth::user();
        $isSuper = ($user->rol->nombre === 'SUPERADMIN');
        if (!$isSuper && $proveedor->id_emp != $user->id_emp) abort(403);

        if ($proveedor->ruta_logo) Storage::disk('public')->delete($proveedor->ruta_logo);
        // si deseas: borrar attrs asociados
        // DatosAtributosFicha::where('id_relacion',$proveedor->id)->delete();
        // borrar valores de atributos asociados
        DatosAtributosFicha::where('ref_tipo','proveedor')->where('ref_id',$proveedor->id)->delete();

        $proveedor->delete();
        return back()->with('success','Proveedor eliminado correctamente.');
    }

    /** AJAX: trae atributos de la ficha tipo Proveedor para una empresa */
    public function atributosByEmpresa(Request $request)
    {
        $empresaId = $request->query('empresa_id') ?: Auth::user()->id_emp;

        $ficha = Ficha::where('id_emp',$empresaId)->where('tipo','Proveedor')->first();
        if (!$ficha) return response()->json([]);

        $attrs = AtributoFicha::where('id_ficha',$ficha->id)
            ->where('nro','>',0)
            ->orderBy('nro')
            ->get(['id','titulo','tipo','ancho','json','obligatorio','nro']);

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

    private function fichaEntity(): string { return 'proveedor'; }

    
    private function loadGroupDefsForProv(?int $idFicha)
    {
        if (!$idFicha) return collect(); // si no hay ficha, no hay defs
        return \App\Models\FichaGroupDef::where('entity_type','proveedor')
            ->where('id_ficha', $idFicha)
            ->where('is_active', 1)
            ->orderBy('id')
            ->get();
    }

    private function loadRelationOptions(?int $idEmp): array
    {
        return [
            'cliente'   => \App\Models\Cliente::orderBy('nombre')->where('id_emp',$idEmp)->where('estado','1')->get(['id','nombre']),
            'proveedor' => \App\Models\Proveedor::orderBy('nombre')->where('id_emp',$idEmp)->where('estado','1')->get(['id','nombre']),
            'producto'  => \App\Models\Producto::orderBy('nombre')->where('id_emp',$idEmp)->where('estado','1')->get(['id','nombre']),
        ];
    }


    /** Devuelve la ficha de tipo 'Cliente' que aplica al usuario actual (ADMIN) o la que venga por request (SUPERADMIN). */
    private function resolveProveedorFichaId(?int $requestedFichaId = null): ?int
    {
        $user = \Auth::user();

        // SUPERADMIN puede elegir explícitamente una ficha (por request o por el <select>)
        if ($user->rol->nombre === 'SUPERADMIN') {
            if ($requestedFichaId) {
                return \App\Models\Ficha::where('id', $requestedFichaId)
                    ->whereIn('tipo', ['Proveedor','proveedor'])
                    ->value('id');
            }
            // Si no manda, no forzamos nada (podrás poner un <select> de fichas en el create de SUPERADMIN)
            return null;
        }

        // ADMIN: la ficha debe ser de su empresa y tipo Cliente
        return \App\Models\Ficha::where('id_emp', $user->id_emp)
            ->whereIn('tipo', ['Proveedor','proveedor'])
            ->where('estado', 1)
            ->orderByDesc('id')
            ->value('id'); // la más reciente
    }


    /** Carga definiciones de grupos solo de ESA ficha. */
    private function loadProveedorGroupDefsForFicha(?int $idFicha): \Illuminate\Support\Collection
    {
        if (!$idFicha) return collect();
        return \App\Models\FichaGroupDef::where('entity_type','proveedor')
            ->where('id_ficha', $idFicha)
            ->where('is_active', 1)
            ->orderBy('id')
            ->get();
    }

   
    /** Carga valores existentes (listas y relaciones) del cliente, scoping por id_ficha. */
    private function loadProveedorFichaValues(int $proveedorId, ?int $idFicha): array
    {
        if (!$idFicha) return ['lists'=>collect(), 'rels'=>collect()];

        $lists = \App\Models\FichaListItem::where('entity_type','proveedor')
            ->where('entity_id', $proveedorId)
            ->where('id_ficha', $idFicha)
            ->orderBy('sort_order')->get()
            ->groupBy('group_code');

        $rels  = \App\Models\FichaRelationLink::where('entity_type','proveedor')
            ->where('entity_id', $proveedorId)
            ->where('id_ficha', $idFicha)
            ->get()->groupBy('group_code');

        return ['lists'=>$lists, 'rels'=>$rels];
    }

    /** Guarda valores LIST/REL del cliente, scoping por id_ficha (versión robusta). */
    private function saveFichaValuesForProveedor(int $proveedorId, \Illuminate\Http\Request $r): void
    {
        $entityType = 'proveedor';
        $idFicha = \App\Models\Proveedor::where('id',$proveedorId)->value('id_ficha');

        // Borrar SOLO dentro de esta ficha
        \App\Models\FichaListItem::where('entity_type',$entityType)->where('entity_id',$proveedorId)
            ->when($idFicha, fn($q)=>$q->where('id_ficha',$idFicha))->delete();
        \App\Models\FichaRelationLink::where('entity_type',$entityType)->where('entity_id',$proveedorId)
            ->when($idFicha, fn($q)=>$q->where('id_ficha',$idFicha))->delete();

        // Defs de esa ficha
        $defs    = $this->loadProveedorGroupDefsForFicha($idFicha);
        $payload = (array) $r->input('groups', []);

        foreach ($defs as $def) {
            $code = $def->code;

            if ($def->group_type === 'list') {
                $rows = array_values((array) data_get($payload, "$code.items", []));
                $fields = is_array($def->item_fields_json)
                    ? $def->item_fields_json
                    : (json_decode($def->item_fields_json, true) ?: []);
                $sort = 0;

                foreach ($rows as $row) {
                    if (!is_array($row)) continue;
                    $has = false;
                    foreach ($fields as $f) {
                        $k = $f['code'] ?? null; if(!$k)continue;
                        if (trim((string)($row[$k] ?? '')) !== '') { $has = true; break; }
                    }
                    if (!$has) continue;

                    \App\Models\FichaListItem::create([
                        'entity_type' => $entityType,
                        'entity_id'   => $proveedorId,
                        'id_ficha'    => $idFicha,
                        'group_code'  => $code,
                        'value_json'  => $row,
                        'sort_order'  => $sort++,
                    ]);
                }

            } else { // relation
                $ids = [];
                if ((int)$def->allow_multiple === 1) {
                    $ids = array_values(array_map('intval', (array) data_get($payload, "$code.related_ids", [])));
                } else {
                    $rid  = (int) data_get($payload, "$code.related_id", 0);
                    if ($rid > 0) $ids[] = $rid;
                }
                $ids = array_values(array_unique(array_filter($ids, fn($v)=>(int)$v>0)));

                foreach ($ids as $rid) {
                    \App\Models\FichaRelationLink::create([
                        'entity_type'         => $entityType,
                        'entity_id'           => $proveedorId,
                        'id_ficha'            => $idFicha,
                        'group_code'          => $code,
                        'related_entity_type' => $def->related_entity_type,
                        'related_entity_id'   => $rid,
                    ]);
                }
            }
        }
    }
}
