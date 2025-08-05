<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\User;
use App\Models\Rol;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SuperAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:SUPERADMIN,ADMINISTRADOR,ADMINISTRATIVO']);
    }

    public function dashboard()
    {
        $user = Auth::user();
        $rol = $user->rol->nombre;
        
        // Estadísticas básicas sin datos sintéticos
        $empresasRegistradas = Empresa::count();
        $usuariosActivos = User::where('estado', true)->count();
        $usuariosTotal = User::count();
        $rolesActivos = Rol::where('estado', true)->count();
        
        // Datos específicos según el rol
        if ($rol === 'SUPERADMIN') {
            $empresas = Empresa::with(['userAdmin'])->orderBy('created_at', 'desc')->paginate(5);
            $usuariosRecientes = User::with(['rol', 'empresa'])->orderBy('created_at', 'desc')->limit(5)->get();
        } elseif ($rol === 'ADMINISTRADOR') {
            $empresas = collect();
            if ($user->empresa) {
                $empresas = collect([$user->empresa]);
            }
            $usuariosRecientes = User::with(['rol', 'empresa'])
                                   ->where('id_emp', $user->id_emp)
                                   ->orderBy('created_at', 'desc')
                                   ->limit(5)
                                   ->get();
        } else {
            // ADMINISTRATIVO
            $empresas = collect();
            if ($user->empresa) {
                $empresas = collect([$user->empresa]);
            }
            $usuariosRecientes = collect();
        }
        
        return view('superadmin.dashboard', compact(
            'empresasRegistradas', 
            'usuariosActivos', 
            'usuariosTotal',
            'rolesActivos',
            'empresas',
            'usuariosRecientes',
            'rol'
        ));
    }

    public function empresas()
    {
        $empresas = Empresa::with(['userAdmin', 'userCreate'])->paginate(10);
        return view('superadmin.empresas.index', compact('empresas'));
    }

    public function createEmpresa()
    {
        $administradores = User::whereHas('rol', function($query) {
            $query->where('nombre', 'ADMINISTRADOR');
        })->get();
        
        return view('superadmin.empresas.create', compact('administradores'));
    }

    public function storeEmpresa(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'id_user_admin' => 'required|exists:users,id',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('logos', 'public');
        }

        Empresa::create([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'id_user_admin' => $request->id_user_admin,
            'id_user_create' => Auth::id(),
            'fecha_inicio' => now(),
            'ruta_logo' => $logoPath,
            'estado' => true,
            'editable' => $request->has('editable')
        ]);

        return redirect()->route('superadmin.empresas')->with('success', 'Empresa creada exitosamente');
    }

    public function editEmpresa(Empresa $empresa)
    {
        $administradores = User::whereHas('rol', function($query) {
            $query->where('nombre', 'ADMINISTRADOR');
        })->get();
        
        return view('superadmin.empresas.edit', compact('empresa', 'administradores'));
    }

    public function updateEmpresa(Request $request, Empresa $empresa)
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

        return redirect()->route('superadmin.empresas')->with('success', 'Empresa actualizada exitosamente');
    }

    public function destroyEmpresa(Empresa $empresa)
    {
        if ($empresa->ruta_logo) {
            Storage::disk('public')->delete($empresa->ruta_logo);
        }
        
        $empresa->delete();
        
        return redirect()->route('superadmin.empresas')->with('success', 'Empresa eliminada exitosamente');
    }

    public function toggleEmpresaEstado(Empresa $empresa)
    {
        $empresa->update(['estado' => !$empresa->estado]);
        
        $estado = $empresa->estado ? 'activada' : 'desactivada';
        return response()->json([
            'success' => true,
            'message' => "Empresa {$estado} exitosamente"
        ]);
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
        
        $usuarios = $query->orderBy('created_at', 'desc')->paginate(15)->appends($request->query());
        
        // Para los filtros
        $empresas = Empresa::where('estado', true)->orderBy('nombre')->get();
        $roles = Rol::where('estado', true)->orderBy('nombre')->get();
        
        return view('superadmin.usuarios.index', compact('usuarios', 'empresas', 'roles'));
    }

    public function roles()
    {
        $roles = Rol::orderBy('created_at', 'desc')->paginate(10);
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

        return redirect()->route('superadmin.roles')->with('success', 'Rol creado exitosamente');
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

        return redirect()->route('superadmin.roles')->with('success', 'Rol actualizado exitosamente');
    }

    public function destroyRol(Rol $rol)
    {
        // Verificar que no hay usuarios con este rol
        if ($rol->users()->count() > 0) {
            return redirect()->route('superadmin.roles')->with('error', 'No se puede eliminar el rol porque tiene usuarios asignados');
        }
        
        $rol->delete();
        
        return redirect()->route('superadmin.roles')->with('success', 'Rol eliminado exitosamente');
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
