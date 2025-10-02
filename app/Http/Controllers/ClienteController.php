<?php

namespace App\Http\Controllers;
use DB;
use App\Models\Empresa;
use App\Models\Ficha;
use App\Models\Flujo;
use App\Models\Etapa;
use App\Models\AtributoFicha;

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


class ClienteController extends Controller
{
    //Cliente
    public function index(Request $request)
    {
        $user = Auth::user();
        $isSuper = ($user->rol->nombre === 'SUPERADMIN');

        // UI state
        $estado = $request->get('estado', 'todos');   // todos | activos | inactivos
        $q      = trim((string) $request->get('q', ''));
        $vista  = $request->get('vista', 'cards');    // cards | tabla
        $sort   = $request->get('sort', 'created_at');// nombre | created_at | estado
        $dir    = strtolower($request->get('dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $query = Cliente::with(['empresa','userCreate'])
            ->orderBy('created_at','desc');

        if ($user->rol->nombre !== 'SUPERADMIN') {
            $query->where('id_emp', $user->id_emp);
        }

        // Filtro estado
        if ($estado === 'activos')   $query->where('estado', 1);
        if ($estado === 'inactivos') $query->where('estado', 0);

            // Búsqueda (por nombre; si quieres también por atributos, ver comentario más abajo)
        if ($q !== '') {
            $query->where('nombre', 'like', "%{$q}%");
            // Para buscar también en atributos:
            // $query->orWhereExists(function($sub) use ($q) {
            //     $sub->from('datos_atributos_ficha as daf')
            //         ->whereColumn('daf.id_relacion','clientes.id')
            //         ->where(function($s) use ($q){
            //             $s->where('daf.dato', 'like', "%{$q}%")
            //               ->orWhere('daf.json', 'like', "%{$q}%");
            //         });
            // });
        }

        // Orden
        if (!in_array($sort, ['nombre','created_at','estado'], true)) {
            $sort = 'created_at';
        }
        $query->orderBy($sort, $dir);

        $clientes = $query->paginate(12)->appends($request->query());

        // Ficha de Cliente y atributos (para armar columnas/resumen)
        $empresaId = $user->rol->nombre === 'SUPERADMIN'
            ? null
            : $user->id_emp;

        // si es superadmin y quieres filtrar por empresa desde un select, ajusta empresaId
        $fichaCliente = Ficha::when($empresaId, fn($q) => $q->where('id_emp', $empresaId))
            ->where('tipo','Cliente')->first();

        $atributos = $fichaCliente
            ? AtributoFicha::where('id_ficha',$fichaCliente->id)
                ->where('nro','>',0)      // ignorar meta nro=0
                ->orderBy('nro')
                ->get()
            : collect();

        // Adjuntar “resumen de atributos” a cada card (máx 2)
        // ===== Datos para TARJETAS (igual que antes, primeros 5) =====
        if ($atributos->isNotEmpty()) {
            $primeros = $atributos->take(5)->pluck('id')->toArray();

            foreach ($clientes as $c) {
                $valores = DatosAtributosFicha::where('id_relacion',$c->id)
                    ->whereIn('id_atributo',$primeros)
                    ->get()->keyBy('id_atributo');

                $c->resumenAtributos = $atributos->take(5)->map(function($a) use ($valores) {
                    $v = $valores->get($a->id);
                    $valor = $v ? ($v->json ?? $v->dato) : null;
                    if ($a->tipo === 'checkbox' && $valor) {
                        $arr = is_string($valor) ? json_decode($valor,true) : (array)$valor;
                        $valor = implode(', ', $arr);
                    }
                    return [
                        'titulo' => $a->titulo,
                        'valor'  => $valor,
                    ];
                });
                $c->otrosAtributosCount = max(0, $atributos->count() - 5);
            }
        } else {
            foreach ($clientes as $c) {
                $c->resumenAtributos = collect();
                $c->otrosAtributosCount = 0;
            }
        }

        // ===== Datos para TABLA (todos los atributos de la página) =====
        $valoresByCliente = [];
        if ($vista === 'tabla' && $atributos->isNotEmpty() && $clientes->count()) {
            $attrIds = $atributos->pluck('id');
            $cliIds  = $clientes->pluck('id');

            $rows = DatosAtributosFicha::whereIn('id_atributo', $attrIds)
                ->whereIn('id_relacion', $cliIds)
                ->get();

            foreach ($rows as $r) {
                $valor = $r->json ?? $r->dato;
                // formatea checkbox
                if (is_string($valor) && $this->looksLikeJsonArray($valor)) {
                    $arr = json_decode($valor, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($arr)) {
                        $valor = implode(', ', $arr);
                    }
                }
                $valoresByCliente[$r->id_relacion][$r->id_atributo] = $valor;
            }
        }

        $groupTitles = \App\Models\FichaGroupDef::where('entity_type','cliente')
        ->where('is_active',1)->pluck('label','code');

        return view('superadmin.clientes.index', [
            'clientes'         => $clientes,
            'atributos'        => $atributos,
            'ficha'            => $fichaCliente,
            'isSuper'          => $isSuper,
            'vista'            => $vista,
            'estado'           => $estado,
            'q'                => $q,
            'sort'             => $sort,
            'dir'              => $dir,
            'valoresByCliente' => $valoresByCliente,
            'groupTitles'      => $groupTitles,
        ]);
    }


    /** Helper para detectar json de array simple */
    private function looksLikeJsonArray(?string $s): bool
    {
        if ($s === null) return false;
        $s = trim($s);
        return strlen($s) >= 2 && $s[0] === '[' && substr($s, -1) === ']';
    }

    public function create()
    {
        $user = Auth::user();
        $isSuper = ($user->rol->nombre === 'SUPERADMIN');

        $empresas = ($user->rol->nombre === 'SUPERADMIN')
            ? Empresa::where('estado',1)->orderBy('nombre')->get(['id','nombre'])
            : Empresa::where('id',$user->id_emp)->get(['id','nombre']);

        $ficha = Ficha::where('id_emp', $user->rol->nombre === 'SUPERADMIN' ? ($empresas->first()->id ?? null) : $user->id_emp)
            ->where('tipo','Cliente')->first();

        $atributos = $ficha
            ? AtributoFicha::where('id_ficha',$ficha->id)->where('nro','>',0)->orderBy('nro')->get()
            : collect();
        
        $groupDefs  = $this->loadGroupDefsForClientes($ficha?->id);
        $relOptions = $this->loadRelationOptions($ficha?->id_emp);

        return view('superadmin.clientes.create', compact('groupDefs','relOptions','empresas','atributos','ficha','isSuper'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $empresaId = $request->input('id_emp') ?: $user->id_emp;
        $isSuper = ($user->rol->nombre === 'SUPERADMIN');

        // Ficha & atributos de tipo Cliente para esta empresa
        $ficha = Ficha::where('id_emp',$empresaId)->where('tipo','Cliente')->first();
        $atributos = $ficha
            ? AtributoFicha::where('id_ficha',$ficha->id)->where('nro','>',0)->orderBy('nro')->get()
            : collect();

        // Validación base del cliente
        $rules = [
            'nombre' => ['required','string','max:255'],
            'logo'   => ['nullable','image','mimes:jpeg,png,jpg,gif','max:2048'],
        ];
        if ($user->rol->nombre === 'SUPERADMIN') {
            $rules['id_emp'] = ['required','exists:empresa,id'];
        }

        // Validación dinámica por atributo
        foreach ($atributos as $a) {
            $key = "atributos.{$a->id}";
            $r = [];

            if ($a->obligatorio) $r[] = 'required';

            switch ($a->tipo) {
                case 'texto':
                case 'cajatexto':
                    if ($a->ancho) $r[] = "max:{$a->ancho}";
                    $r[] = 'string';
                    break;
                case 'entero':
                    $r[] = 'integer';
                    break;
                case 'decimal':
                    $r[] = 'numeric';
                    break;
                case 'fecha':
                    $r[] = 'date';
                    break;
                case 'desplegable':
                case 'radio':
                    // Opciones válidas
                    $ops = $a->json ? json_decode($a->json,true) : [];
                    if (is_array($ops) && count($ops)) {
                        $r[] = Rule::in($ops);
                    }
                    break;
                case 'checkbox':
                    // múltiple
                    $key = "atributos.{$a->id}";
                    $r[] = 'array';
                    if ($a->obligatorio) $r[] = 'min:1';
                    break;
                case 'imagen':
                    // si subes archivo desde atributos:
                    // usa name="atributos_archivo[ID]"
                    $rules["atributos_archivo.{$a->id}"] = ['nullable','image','mimes:jpeg,png,jpg,gif','max:4096'];
                    break;
            }

            if ($a->tipo !== 'imagen') {
                $rules[$key] = $r;
            }
        }

        $validated = $request->validate($rules);

        // Crear cliente
        $cliente = new Cliente();
        $cliente->nombre         = $validated['nombre'];
        $cliente->id_emp         = $empresaId;
        $cliente->id_user_create = $user->id;
        $cliente->estado         = 1;

        // logo (opcional)
        if ($request->hasFile('logo')) {
            $cliente->ruta_logo = $request->file('logo')->store('clientes', 'public');
        }

        // asociar la ficha (si quieres relación directa)
        if ($ficha) $cliente->id_ficha = $ficha->id;

        $cliente->save();
        $this->saveFichaValuesForCliente($cliente->id, $request);

        // Guardar valores de atributos
        foreach ($atributos as $a) {
            $dato = null;
            $json = null;

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
                        $path = $request->file("atributos_archivo.{$a->id}")->store('cliente_attrs', 'public');
                        $dato = $path; // guardamos ruta
                    } else {
                        $dato = $request->input("atributos.{$a->id}"); // por si guardas texto/ruta
                    }
                    break;
            }

            DatosAtributosFicha::create([
                'id_atributo'   => $a->id,
                'id_relacion'   => $cliente->id,  // <-- requiere esta columna
                'dato'          => $dato,
                'json'          => $json,
                'id_user_create'=> $user->id,
                'isSuper'     => $isSuper,
            ]);
        }

        return redirect()->route('clientes.index')->with('success','Cliente creado correctamente.');
    }

    public function edit(Cliente $cliente)
    {
        $user = Auth::user();
        $isSuper = ($user->rol->nombre === 'SUPERADMIN');
        $empresas = $isSuper
            ? Empresa::where('estado',1)->orderBy('nombre')->get(['id','nombre'])
            : Empresa::where('id',$user->id_emp)->get(['id','nombre']);

        $ficha = Ficha::where('id_emp', $user->rol->nombre === 'SUPERADMIN' ? ($empresas->first()->id ?? null) : $user->id_emp)
            ->where('tipo','Cliente')->first();
        $groupDefs  = $this->loadGroupDefsForClientes($ficha?->id);
        $relOptions = $this->loadRelationOptions($ficha?->id_emp);

        // Seguridad: si no es superadmin, solo su empresa
        if (!$isSuper && $cliente->id_emp != $user->id_emp) {
            abort(403);
        }

        // Atributos de la ficha del cliente (si tiene)
        $atributos = collect();
        $valores   = [];
        if ($cliente->id_ficha) {
            $atributos = AtributoFicha::where('id_ficha',$cliente->id_ficha)
                ->where('nro','>',0)->orderBy('nro')->get();

             // Trae dato y json en 1 sola consulta y arma el map por id_atributo
            $rows = DatosAtributosFicha::where('id_relacion', $cliente->id)
                ->whereIn('id_atributo', $atributos->pluck('id'))
                ->get();

            foreach ($rows as $row) {
                // preferimos json para checkbox; para los demás, dato
                $valores[$row->id_atributo] = $row->json ?? $row->dato;
            }
        }

        // Valores actuales (para pre-llenar el form)
        $listValues = FichaListItem::where('entity_type','cliente')
            ->where('entity_id',$cliente->id)->get()->groupBy('group_code');

        $relValues  = FichaRelationLink::where('entity_type','cliente')
            ->where('entity_id',$cliente->id)->get()->groupBy('group_code');

        return view('superadmin.clientes.edit', compact('cliente','groupDefs','relOptions','listValues','relValues','empresas','atributos','valores','isSuper'));
    }

    public function update(Request $request, Cliente $cliente)
    {
        $user = Auth::user();
        $isSuper = ($user->rol->nombre === 'SUPERADMIN');

        if (!$isSuper && $cliente->id_emp != $user->id_emp) {
            abort(403);
        }

        $empresaId = $request->input('id_emp') ?: $cliente->id_emp;

        $rules = [
            'nombre' => ['required','string','max:255'],
            'logo'   => ['nullable','image','mimes:jpeg,png,jpg,gif','max:2048'],
            'id_emp' => ['nullable','exists:empresa,id'],
            'estado' => ['nullable','boolean'],
        ];
        $validated = $request->validate($rules);

        DB::transaction(function () use ($request, $validated, $cliente, $empresaId, $user) {
            $cliente->nombre = $validated['nombre'];
            // cambiar empresa solo superadmin (si quieres lo limitas en la vista)
            $cliente->id_emp = $empresaId;
            $cliente->estado = $request->boolean('estado');

            if ($request->hasFile('logo')) {
                if ($cliente->ruta_logo) {
                    \Storage::disk('public')->delete($cliente->ruta_logo);
                }
                $cliente->ruta_logo = $request->file('logo')->store('logos/clientes','public');
            }

            $cliente->save();

            // actualizar valores de atributos si tiene ficha
            if ($cliente->id_ficha) {
                $atributos = AtributoFicha::where('id_ficha',$cliente->id_ficha)
                    ->where('nro','>',0)->get();

                // valores que vienen del form
                $valoresReq = (array) $request->input('atributos', []);

                foreach ($atributos as $attr) {
                    $valorReq = $valoresReq[$attr->id] ?? null;

                    $dato = DatosAtributosFicha::where('id_atributo', $attr->id)
                        ->where('id_relacion', $cliente->id)
                        ->first();

                    if (!$dato) {
                        $dato = new DatosAtributosFicha();
                        $dato->id_atributo    = $attr->id;
                        $dato->id_relacion    = $cliente->id;
                        $dato->id_user_create = $user->id;
                    }

                    // Limpia
                    $dato->dato = null;
                    $dato->json = null;

                    switch ($attr->tipo) {

                        case 'checkbox':
                            $arr = is_array($valorReq) ? array_values($valorReq) : [];
                            $dato->json = json_encode($arr);
                            break;

                        case 'imagen':
                            // ¿pidió eliminar?
                            $del = $request->boolean("atributos_eliminar.$attr->id");
                            if ($del) {
                                if (!empty($dato->dato)) {
                                    \Storage::disk('public')->delete($dato->dato); // borra archivo físico
                                }
                                $dato->dato = null;
                                break; // listo
                            }

                            // ¿subió una nueva?
                            if ($request->hasFile("atributos_archivo.$attr->id")) {
                                // opcional: borra la anterior
                                if (!empty($dato->dato)) {
                                    \Storage::disk('public')->delete($dato->dato);
                                }
                                $path = $request->file("atributos_archivo.$attr->id")
                                    ->store('cliente_attrs', 'public');
                                $dato->dato = $path;
                            } else {
                                // conservar la ruta que vino en el hidden (si existe)
                                // $valorReq = hidden atributos[ID] que pusiste en la vista
                                if ($valorReq !== null) {
                                    $dato->dato = (string) $valorReq;
                                }
                                // si ni hidden ni file → se mantiene lo que ya tenía en BD ($dato->dato)
                            }
                            break;

                        default:
                            // radio / desplegable / fecha / texto / cajatexto / entero / decimal
                            if (is_array($valorReq)) {
                                $dato->dato = json_encode($valorReq);
                            } else {
                                $dato->dato = $valorReq !== null ? (string) $valorReq : null;
                            }
                            break;
                    }

                    $dato->save();
                }
            }
        });
        $this->saveFichaValuesForCliente($cliente->id, $request);

        return redirect()->route('clientes.index')->with('success','Cliente actualizado correctamente.');
    }

    public function destroy(Cliente $cliente)
    {
        $user = Auth::user();
        $isSuper = ($user->rol->nombre === 'SUPERADMIN');

        if (!$isSuper && $cliente->id_emp != $user->id_emp) {
            abort(403);
        }

        if ($cliente->ruta_logo) {
            \Storage::disk('public')->delete($cliente->ruta_logo);
        }

        // borrar valores de atributos asociados
        DatosAtributosFicha::where('ref_tipo','cliente')->where('ref_id',$cliente->id)->delete();

        $cliente->delete();

        return back()->with('success','Cliente eliminado correctamente.');
    }

    // ===== AJAX =====

    // Devuelve los atributos de la ficha tipo Cliente para una empresa dada
    public function atributosByEmpresa(Request $request)
    {
        $empresaId = $request->query('empresa_id') ?: Auth::user()->id_emp;

        $ficha = Ficha::where('id_emp', $empresaId)->where('tipo','Cliente')->first();
        if (!$ficha) return response()->json([]);

        $attrs = AtributoFicha::where('id_ficha',$ficha->id)
            ->where('nro','>',0)
            ->orderBy('nro')
            ->get(['id','titulo','tipo','ancho','json','obligatorio','nro']);

        // parse options para radio/select/checkbox
        $out = $attrs->map(function($a) {
            $opts = null;
            if (in_array($a->tipo, ['radio','desplegable','checkbox'], true) && $a->json) {
                $opts = json_decode($a->json, true);
            }
            return [
                'id'          => $a->id,
                'titulo'      => $a->titulo,
                'tipo'        => $a->tipo,
                'ancho'       => $a->ancho,
                'opciones'    => $opts,
                'obligatorio' => (bool)$a->obligatorio,
                'nro'         => $a->nro,
            ];
        });

        return response()->json($out);
    }

    private function fichaEntity(): string { return 'cliente'; }

    private function loadGroupDefsForClientes(?int $idFicha)
    {
        if (!$idFicha) return collect(); // si no hay ficha, no hay defs
        return \App\Models\FichaGroupDef::where('entity_type','cliente')
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
    private function resolveClienteFichaId(?int $requestedFichaId = null): ?int
    {
        $user = \Auth::user();

        // SUPERADMIN puede elegir explícitamente una ficha (por request o por el <select>)
        if ($user->rol->nombre === 'SUPERADMIN') {
            if ($requestedFichaId) {
                return \App\Models\Ficha::where('id', $requestedFichaId)
                    ->whereIn('tipo', ['Cliente','cliente'])
                    ->value('id');
            }
            // Si no manda, no forzamos nada (podrás poner un <select> de fichas en el create de SUPERADMIN)
            return null;
        }

        // ADMIN: la ficha debe ser de su empresa y tipo Cliente
        return \App\Models\Ficha::where('id_emp', $user->id_emp)
            ->whereIn('tipo', ['Cliente','cliente'])
            ->where('estado', 1)
            ->orderByDesc('id')
            ->value('id'); // la más reciente
    }

    /** Carga definiciones de grupos solo de ESA ficha. */
    private function loadClienteGroupDefsForFicha(?int $idFicha): \Illuminate\Support\Collection
    {
        if (!$idFicha) return collect();
        return \App\Models\FichaGroupDef::where('entity_type','cliente')
            ->where('id_ficha', $idFicha)
            ->where('is_active', 1)
            ->orderBy('id')
            ->get();
    }

    /** Carga valores existentes (listas y relaciones) del cliente, scoping por id_ficha. */
    private function loadClienteFichaValues(int $clienteId, ?int $idFicha): array
    {
        if (!$idFicha) return ['lists'=>collect(), 'rels'=>collect()];

        $lists = \App\Models\FichaListItem::where('entity_type','cliente')
            ->where('entity_id', $clienteId)
            ->where('id_ficha', $idFicha)
            ->orderBy('sort_order')->get()
            ->groupBy('group_code');

        $rels  = \App\Models\FichaRelationLink::where('entity_type','cliente')
            ->where('entity_id', $clienteId)
            ->where('id_ficha', $idFicha)
            ->get()->groupBy('group_code');

        return ['lists'=>$lists, 'rels'=>$rels];
    }

    /** Guarda valores LIST/REL del cliente, scoping por id_ficha (versión robusta). */
    private function saveFichaValuesForCliente(int $clienteId, \Illuminate\Http\Request $r): void
    {
        $entityType = 'cliente';
        $idFicha = \App\Models\Cliente::where('id',$clienteId)->value('id_ficha');

        // Borrar SOLO dentro de esta ficha
        \App\Models\FichaListItem::where('entity_type',$entityType)->where('entity_id',$clienteId)
            ->when($idFicha, fn($q)=>$q->where('id_ficha',$idFicha))->delete();
        \App\Models\FichaRelationLink::where('entity_type',$entityType)->where('entity_id',$clienteId)
            ->when($idFicha, fn($q)=>$q->where('id_ficha',$idFicha))->delete();

        // Defs de esa ficha
        $defs    = $this->loadClienteGroupDefsForFicha($idFicha);
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
                        'entity_id'   => $clienteId,
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
                        'entity_id'           => $clienteId,
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
