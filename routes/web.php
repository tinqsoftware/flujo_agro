<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\UserController;

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

// Redirección después del login
Route::get('/dashboard', function () {
    $user = Auth::user();
    if ($user && $user->rol) {
        switch (strtoupper($user->rol->nombre)) {
            case 'SUPERADMIN':
                return redirect()->route('superadmin.dashboard');
            case 'ADMINISTRADOR':
                return redirect()->route('superadmin.dashboard');
            case 'ADMINISTRATIVO':
                return redirect()->route('superadmin.dashboard');
            default:
                return redirect('/home');
        }
    }
    return redirect('/home');
})->middleware('auth');

// Rutas para todos los usuarios autenticados (Dashboard único)
Route::prefix('superadmin')->name('superadmin.')->middleware(['auth', 'role:SUPERADMIN,ADMINISTRADOR,ADMINISTRATIVO'])->group(function () {
    Route::get('/dashboard', [SuperAdminController::class, 'dashboard'])->name('dashboard');
    
    // Gestión de Empresas (solo SUPERADMIN)
    Route::middleware('role:SUPERADMIN')->group(function () {
        Route::get('/empresas', [SuperAdminController::class, 'empresas'])->name('empresas');
        Route::get('/empresas/create', [SuperAdminController::class, 'createEmpresa'])->name('empresas.create');
        Route::post('/empresas', [SuperAdminController::class, 'storeEmpresa'])->name('empresas.store');
        Route::get('/empresas/{empresa}/edit', [SuperAdminController::class, 'editEmpresa'])->name('empresas.edit');
        Route::put('/empresas/{empresa}', [SuperAdminController::class, 'updateEmpresa'])->name('empresas.update');
        Route::delete('/empresas/{empresa}', [SuperAdminController::class, 'destroyEmpresa'])->name('empresas.destroy');
        Route::patch('/empresas/{empresa}/toggle-estado', [SuperAdminController::class, 'toggleEmpresaEstado'])->name('empresas.toggle-estado');
    });
    
    // Gestión de Roles (solo SUPERADMIN)
    Route::middleware('role:SUPERADMIN')->group(function () {
        Route::get('/roles', [SuperAdminController::class, 'roles'])->name('roles');
        Route::get('/roles/create', [SuperAdminController::class, 'createRol'])->name('roles.create');
        Route::post('/roles', [SuperAdminController::class, 'storeRol'])->name('roles.store');
        Route::get('/roles/{rol}/edit', [SuperAdminController::class, 'editRol'])->name('roles.edit');
        Route::put('/roles/{rol}', [SuperAdminController::class, 'updateRol'])->name('roles.update');
        Route::delete('/roles/{rol}', [SuperAdminController::class, 'destroyRol'])->name('roles.destroy');
        Route::patch('/roles/{rol}/toggle-estado', [SuperAdminController::class, 'toggleRolEstado'])->name('roles.toggle-estado');
    });
    
    // Gestión de Usuarios (SUPERADMIN y ADMINISTRADOR)
    Route::middleware('role:SUPERADMIN,ADMINISTRADOR')->group(function () {
        Route::get('/usuarios', [SuperAdminController::class, 'usuarios'])->name('usuarios');
        Route::get('/usuarios/create', [AdminController::class, 'createUsuario'])->name('usuarios.create');
        Route::post('/usuarios', [AdminController::class, 'storeUsuario'])->name('usuarios.store');
        Route::get('/usuarios/{usuario}/edit', [AdminController::class, 'editUsuario'])->name('usuarios.edit');
        Route::put('/usuarios/{usuario}', [AdminController::class, 'updateUsuario'])->name('usuarios.update');
        Route::delete('/usuarios/{usuario}', [AdminController::class, 'destroyUsuario'])->name('usuarios.destroy');
        Route::patch('/usuarios/{usuario}/toggle-estado', [AdminController::class, 'toggleUsuarioEstado'])->name('usuarios.toggle-estado');
    });
    
    // Perfil de usuario (todos)
    Route::get('/perfil', [UserController::class, 'perfil'])->name('perfil');
    Route::put('/perfil', [UserController::class, 'updatePerfil'])->name('perfil.update');
});
