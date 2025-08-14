<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:ADMINISTRATIVO,ADMINISTRADOR,SUPERADMIN']);
    }

    public function dashboard()
    {
        $user = \App\Models\User::find(Auth::id());
        $empresa = $user->empresa;
        
        if (!$empresa) {
            return redirect()->route('login')->with('error', 'Usuario sin empresa asignada');
        }
        
        // Estadísticas básicas para usuario administrativo
        $tareasAsignadas = 0; // Por implementar cuando existan modelos de tareas
        $documentosPendientes = 0; // Por implementar
        $flujosEnProceso = 0; // Por implementar
        $notificaciones = 0; // Por implementar
        
        return view('user.dashboard', compact(
            'empresa',
            'tareasAsignadas',
            'documentosPendientes',
            'flujosEnProceso',
            'notificaciones'
        ));
    }

    public function perfil()
    {
        $user = Auth::user();
        return view('superadmin.perfil', compact('user'));
    }

    public function updatePerfil(Request $request)
    {
        $user = \App\Models\User::find(Auth::id());
        
        $request->validate([
            'nombres' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'celular' => 'nullable|string|max:15',
            'password' => 'nullable|min:8|confirmed'
        ]);

        $userData = [
            'name' => $request->nombres . ' ' . $request->apellidos,
            'nombres' => $request->nombres,
            'apellidos' => $request->apellidos,
            'email' => $request->email,
            'celular' => $request->celular
        ];

        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->password);
        }

        $user->update($userData);

        return redirect()->route('perfil')->with('success', 'Perfil actualizado exitosamente');
    }
}
