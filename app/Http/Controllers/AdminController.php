<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Rol;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:ADMINISTRADOR,SUPERADMIN']);
    }

    public function dashboard()
    {
        $user = Auth::user();
        
        // Si es superadmin, puede ver todas las empresas
        if ($user->rol->nombre === 'SUPERADMIN') {
            $empresa = null;
            $flujosActivos = 0; // Por implementar
            $productos = 0; // Por implementar
            $clientesActivos = 0; // Por implementar
            $etapasCompletadas = 0; // Por implementar
        } else {
            // Si es administrador, solo ve su empresa
            $empresa = $user->empresa;
            if (!$empresa) {
                return redirect()->route('login')->with('error', 'Usuario sin empresa asignada');
            }
            
            $flujosActivos = 0; // Por implementar cuando existan modelos de flujos
            $productos = 0; // Por implementar cuando existan modelos de productos
            $clientesActivos = 0; // Por implementar cuando existan modelos de clientes
            $etapasCompletadas = 0; // Por implementar cuando existan modelos de etapas
        }
        
        return view('admin.dashboard', compact(
            'empresa',
            'flujosActivos',
            'productos', 
            'clientesActivos',
            'etapasCompletadas'
        ));
    }

    public function usuarios(Request $request)
    {
        $user = Auth::user();
        
        $query = User::with(['rol', 'empresa']);
        
        if ($user->rol->nombre === 'SUPERADMIN') {
            // SuperAdmin puede ver todos los usuarios
        } else {
            // Administrador solo ve usuarios de su empresa
            $query->where('id_emp', $user->id_emp);
        }
        
        // Filtro por búsqueda
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nombres', 'LIKE', "%{$search}%")
                  ->orWhere('apellidos', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }
        
        // Filtro por estado
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }
        
        $usuarios = $query->paginate(10)->appends($request->query());
        
        return view('admin.usuarios.index', compact('usuarios'));
    }

    public function createUsuario()
    {
        $user = Auth::user();
        $roles = Rol::where('estado', true)->get();
        
        if ($user->rol->nombre === 'SUPERADMIN') {
            $empresas = Empresa::where('estado', true)->get();
        } else {
            $empresas = Empresa::where('id', $user->id_emp)->get();
        }
        
        return view('superadmin.usuarios.create', compact('roles', 'empresas'));
    }

    public function storeUsuario(Request $request)
    {
        $user = Auth::user();
        
        $rules = [
            'nombres' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'sexo' => 'required|in:M,F',
            'celular' => 'nullable|string|max:15',
            'id_rol' => 'required|exists:rol,id'
        ];

        // Solo superadmin puede asignar empresa
        if ($user->rol->nombre === 'SUPERADMIN') {
            $rules['id_emp'] = 'required|exists:empresa,id';
        }

        $request->validate($rules);

        $userData = [
            'name' => $request->nombres . ' ' . $request->apellidos,
            'nombres' => $request->nombres,
            'apellidos' => $request->apellidos,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'sexo' => $request->sexo,
            'celular' => $request->celular,
            'id_rol' => $request->id_rol,
            'id_user_create' => Auth::id(),
            'estado' => true
        ];

        // Asignar empresa
        if ($user->rol->nombre === 'SUPERADMIN') {
            $userData['id_emp'] = $request->id_emp;
        } else {
            $userData['id_emp'] = $user->id_emp;
        }

        User::create($userData);

        return redirect()->route('superadmin.usuarios')->with('success', 'Usuario creado exitosamente');
    }

    public function editUsuario(User $usuario)
    {
        $user = Auth::user();
        
        // Verificar permisos
        if ($user->rol->nombre !== 'SUPERADMIN' && $usuario->id_emp !== $user->id_emp) {
            abort(403, 'No tienes permisos para editar este usuario');
        }

        $roles = Rol::where('estado', true)->get();
        
        if ($user->rol->nombre === 'SUPERADMIN') {
            $empresas = Empresa::where('estado', true)->get();
        } else {
            $empresas = Empresa::where('id', $user->id_emp)->get();
        }
        
        return view('superadmin.usuarios.edit', compact('usuario', 'roles', 'empresas'));
    }

    public function updateUsuario(Request $request, User $usuario)
    {
        $user = Auth::user();
        
        // Verificar permisos
        if ($user->rol->nombre !== 'SUPERADMIN' && $usuario->id_emp !== $user->id_emp) {
            abort(403, 'No tienes permisos para editar este usuario');
        }

        $rules = [
            'nombres' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$usuario->id,
            'sexo' => 'required|in:M,F',
            'celular' => 'nullable|string|max:15',
            'id_rol' => 'required|exists:rol,id'
        ];

        if ($user->rol->nombre === 'SUPERADMIN') {
            $rules['id_emp'] = 'required|exists:empresa,id';
        }

        if ($request->filled('password')) {
            $rules['password'] = 'min:8|confirmed';
        }

        $request->validate($rules);

        $userData = [
            'name' => $request->nombres . ' ' . $request->apellidos,
            'nombres' => $request->nombres,
            'apellidos' => $request->apellidos,
            'email' => $request->email,
            'sexo' => $request->sexo,
            'celular' => $request->celular,
            'id_rol' => $request->id_rol,
            'estado' => $request->has('estado')
        ];

        if ($user->rol->nombre === 'SUPERADMIN') {
            $userData['id_emp'] = $request->id_emp;
        }

        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->password);
        }

        $usuario->update($userData);

        return redirect()->route('superadmin.usuarios')->with('success', 'Usuario actualizado exitosamente');
    }

    public function destroyUsuario(User $usuario)
    {
        $user = Auth::user();
        
        // Verificar permisos
        if ($user->rol->nombre !== 'SUPERADMIN' && $usuario->id_emp !== $user->id_emp) {
            abort(403, 'No tienes permisos para eliminar este usuario');
        }

        // No permitir eliminar al usuario logueado
        if ($usuario->id === $user->id) {
            return redirect()->route('superadmin.usuarios')->with('error', 'No puedes eliminar tu propio usuario');
        }

        $usuario->delete();
        
        return redirect()->route('superadmin.usuarios')->with('success', 'Usuario eliminado exitosamente');
    }

    public function toggleUsuarioEstado(Request $request, User $usuario)
    {
        $user = Auth::user();
        
        // Verificar permisos
        if ($user->rol->nombre !== 'SUPERADMIN' && $usuario->id_emp !== $user->id_emp) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para cambiar el estado de este usuario'
            ], 403);
        }

        // No permitir desactivar al usuario logueado
        if ($usuario->id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes cambiar tu propio estado'
            ], 400);
        }

        $nuevoEstado = $request->input('estado', !$usuario->estado);
        $usuario->update(['estado' => $nuevoEstado]);
        
        $estadoTexto = $nuevoEstado ? 'activado' : 'desactivado';
        
        return response()->json([
            'success' => true,
            'message' => "Usuario {$estadoTexto} exitosamente",
            'estado' => $nuevoEstado
        ]);
    }
}
