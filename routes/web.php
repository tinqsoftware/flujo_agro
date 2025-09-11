<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\FichaController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\TipoFlujoController;
use App\Http\Controllers\Ejecucion;
use App\Http\Controllers\{
    FormTypeController, FormController,
    FormGroupController, FormFieldController,
    FormFieldSourceController, FormFieldFormulaController,
    FormRunController, PdfRenderController, DataSourceApiController
};

Route::get('/', function () {
    return view('welcome');
});

Auth::routes(['register' => false]);

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');


// Rutas para todos los usuarios autenticados (Dashboard único)
Route::middleware(['auth', 'role:SUPERADMIN,ADMINISTRADOR,ADMINISTRATIVO'])
    ->group(function () {
    Route::get('/dashboard', [SuperAdminController::class, 'dashboard'])->name('dashboard');

    Route::resource('form-types', App\Http\Controllers\FormTypeController::class)->except(['show']);
    Route::resource('forms', App\Http\Controllers\FormController::class)->except(['show']);

    // APIs para selects dinámicos
    Route::get('datasource/options', [DataSourceApiController::class,'options'])->name('datasource.options');


    
    // Gestión de Empresas (solo SUPERADMIN)
    Route::middleware('role:SUPERADMIN')->group(function () {
        Route::get('/empresas', [SuperAdminController::class, 'empresas'])->name('empresas');
        Route::get('/empresas/create', [SuperAdminController::class, 'createEmpresa'])->name('empresas.create');
        Route::post('/empresas', [SuperAdminController::class, 'storeEmpresa'])->name('empresas.store');
        Route::get('/empresas/{empresa}/show', [SuperAdminController::class, 'showEmpresa'])->name('empresas.show');
        Route::get('/empresas/{empresa}/edit', [SuperAdminController::class, 'editEmpresa'])->name('empresas.edit');
        Route::put('/empresas/{empresa}', [SuperAdminController::class, 'updateEmpresa'])->name('empresas.update');
        Route::delete('/empresas/{empresa}', [SuperAdminController::class, 'destroyEmpresa'])->name('empresas.destroy');
        Route::patch('/empresas/{empresa}/toggle-estado', [SuperAdminController::class, 'toggleEmpresaEstado'])->name('empresas.toggle-estado');

        // Builder (solo SuperAdmin)
        Route::prefix('forms/{form}')->group(function () {
            Route::post('groups',        [FormGroupController::class,'store'])->name('forms.groups.store');
            Route::put('groups/{group}', [FormGroupController::class,'update'])->name('forms.groups.update');
            Route::delete('groups/{group}', [FormGroupController::class,'destroy'])->name('forms.groups.destroy');

            Route::post('fields',        [FormFieldController::class,'store'])->name('forms.fields.store');
            Route::put('fields/{field}', [FormFieldController::class,'update'])->name('forms.fields.update');
            Route::delete('fields/{field}', [FormFieldController::class,'destroy'])->name('forms.fields.destroy');

            Route::post('fields/{field}/source',  [FormFieldSourceController::class,'upsert'])->name('forms.fields.source.upsert');
            Route::delete('fields/{field}/source',[FormFieldSourceController::class,'destroy'])->name('forms.fields.source.destroy');

            Route::post('fields/{field}/formula',  [FormFieldFormulaController::class,'upsert'])->name('forms.fields.formula.upsert');
            Route::delete('fields/{field}/formula',[FormFieldFormulaController::class,'destroy'])->name('forms.fields.formula.destroy');

            Route::resource('pdf-templates', PdfTemplateController::class)->except(['show','edit','create','index']);
            Route::post('pdf-templates/{template}/elements', [PdfElementController::class,'upsert'])->name('forms.pdf.elements.upsert');
        });

    });
    
    // Gestión de Roles (solo SUPERADMIN)
    Route::middleware('role:SUPERADMIN,ADMINISTRADOR,ADMINISTRATIVO')->group(function () {
        Route::get('/roles', [SuperAdminController::class, 'roles'])->name('roles');
        Route::get('/roles/create', [SuperAdminController::class, 'createRol'])->name('roles.create');
        Route::post('/roles', [SuperAdminController::class, 'storeRol'])->name('roles.store');
        Route::get('/roles/{rol}/edit', [SuperAdminController::class, 'editRol'])->name('roles.edit');
        Route::put('/roles/{rol}', [SuperAdminController::class, 'updateRol'])->name('roles.update');
        Route::delete('/roles/{rol}', [SuperAdminController::class, 'destroyRol'])->name('roles.destroy');
        Route::patch('/roles/{rol}/toggle-estado', [SuperAdminController::class, 'toggleRolEstado'])->name('roles.toggle-estado');

        //fichas
        //Route::resource('fichas', FichaController::class)->only(['index','create','store','show']);
        Route::resource('fichas', FichaController::class)->only(['index','create','store']);

        Route::get('/fichas/{ficha}', [FichaController::class, 'show'])->name('fichas.show');
        //Route::post('/fichas', [FichaController::class, 'storeFicha'])->name('fichas.store');
        Route::get('/fichas/{ficha}/edit', [FichaController::class, 'edit'])->name('fichas.edit');
        Route::put('/fichas/{ficha}', [FichaController::class, 'update'])->name('fichas.update');
        Route::delete('/fichas/{ficha}', [FichaController::class, 'destroy'])->name('fichas.destroy');
        Route::patch('/fichas/{ficha}/toggle-estado', [FichaController::class, 'toggleEstado'])->name('fichas.toggle-estado');

        // AJAX
        Route::get('/fichas/flujos-by-empresa', [FichaController::class, 'flujosByEmpresa'])->name('fichas.flujosByEmpresa');
        Route::get('/fichas/etapas-by-flujo', [FichaController::class, 'etapasByFlujo'])->name('fichas.etapasByFlujo');
        Route::get('/fichas/check-tipo-disponible', [FichaController::class, 'checkTipoDisponible'])->name('fichas.checkTipoDisponible');


        //CLIENTES
        Route::resource('clientes', ClienteController::class)->only(['show','index','create','store','edit','update','destroy']);
        // AJAX para campos dinámicos de la ficha tipo Cliente
        Route::get('/clientes/atributos-by-empresa', [ClienteController::class, 'atributosByEmpresa'])
            ->name('clientes.atributosByEmpresa');


        //PRODUCTOS
        Route::resource('productos', ProductoController::class)->parameters(['productos' => 'producto'])
        ->only(['index','create','store','edit','update','destroy']);
        // AJAX para campos dinámicos de la ficha tipo Cliente
        Route::get('/productos/atributos-by-empresa', [ProductoController::class, 'atributosByEmpresa'])
            ->name('productos.atributosByEmpresa');

        //PROVEEDORES
        Route::resource('proveedores', ProveedorController::class)->parameters(['proveedores' => 'proveedor'])->only(['index','create','store','edit','update','destroy']);
        // AJAX para campos dinámicos de la ficha tipo Cliente
        Route::get('/proveedores/atributos-by-empresa', [ProveedorController::class, 'atributosByEmpresa'])
            ->name('proveedores.atributosByEmpresa');

        // TIPOS DE FLUJO
        Route::resource('tipo-flujo', TipoFlujoController::class)
            ->only(['index','create','store','edit','update','destroy']);


        // TIPOS DE FLUJO (ya lo tienes con CRUD). Aquí solo ajax para el combo por empresa
        Route::get('/tipo-flujo/by-empresa', [\App\Http\Controllers\FlujoController::class, 'tiposByEmpresa'])
            ->name('tipo_flujo.byEmpresa');

        // FLUJOS
        Route::resource('flujos', \App\Http\Controllers\FlujoController::class)
            ->only(['index','create','store','edit','update','destroy']);
        
        // AJAX para cambiar estado de elementos en flujos
        Route::patch('/flujos/etapas/{etapa}/toggle-estado', [\App\Http\Controllers\FlujoController::class, 'toggleEtapaEstado'])
            ->name('flujos.etapas.toggle-estado');
        Route::patch('/flujos/tareas/{tarea}/toggle-estado', [\App\Http\Controllers\FlujoController::class, 'toggleTareaEstado'])
            ->name('flujos.tareas.toggle-estado');
        Route::patch('/flujos/documentos/{documento}/toggle-estado', [\App\Http\Controllers\FlujoController::class, 'toggleDocumentoEstado'])
            ->name('flujos.documentos.toggle-estado');

        // AJAX para gestión de formularios en etapas
        Route::get('/flujos/forms-by-empresa', [\App\Http\Controllers\FlujoController::class, 'formsByEmpresa'])
            ->name('flujos.forms.byEmpresa');
        Route::get('/flujos/form-preview/{form}', [\App\Http\Controllers\FlujoController::class, 'formPreview'])
            ->name('flujos.form.preview');
        Route::post('/flujos/etapas/{etapa}/associate-form', [\App\Http\Controllers\FlujoController::class, 'associateForm'])
            ->name('flujos.etapas.associateForm');
        Route::delete('/flujos/etapas/{etapa}/remove-form/{form}', [\App\Http\Controllers\FlujoController::class, 'removeForm'])
            ->name('flujos.etapas.removeForm');

        // EJECUCIÓN DE FLUJOS
        Route::resource('ejecucion', Ejecucion::class)->only(['index']);
        
        // Rutas específicas para mostrar flujos y ejecuciones
        Route::get('ejecucion/flujo/{flujo}', [Ejecucion::class, 'showFlujo'])->name('ejecucion.flujo.show');
        Route::get('ejecucion/detalle/{detalleFlujo}', [Ejecucion::class, 'showDetalle'])->name('ejecucion.detalle.show');
        
        // Mantener compatibilidad con la ruta antigua (redirige a la nueva estructura)
        Route::get('ejecucion/{flujo}', [Ejecucion::class, 'show'])->name('ejecucion.show');
        Route::get('ejecucion/{flujo}/configurar', [Ejecucion::class, 'configurar'])->name('ejecucion.configurar');
        Route::get('ejecucion/{flujo}/previsualizar', [Ejecucion::class, 'previsualizar'])->name('ejecucion.previsualizar');
        Route::post('ejecucion/{flujo}/crear', [Ejecucion::class, 'crearEjecucion'])->name('ejecucion.crear');
        
        // Nueva ruta que usa detalle_flujo_id en lugar de flujo_id
        Route::get('ejecucion/detalle/{detalleFlujo}/ejecutar', [Ejecucion::class, 'ejecutarDetalle'])->name('ejecucion.detalle.ejecutar');
        
        // Mantener la ruta antigua para compatibilidad (redirige a nueva estructura)
        Route::get('ejecucion/{flujo}/ejecutar', [Ejecucion::class, 'ejecutar'])->name('ejecucion.ejecutar');
        
        // AJAX para ejecución - actualizar para usar detalle_flujo_id
        Route::post('ejecucion/detalle/{detalleFlujo}/iniciar', [Ejecucion::class, 'iniciarProceso'])->name('ejecucion.detalle.iniciar');
        Route::post('ejecucion/detalle/tarea/actualizar', [Ejecucion::class, 'actualizarTarea'])->name('ejecucion.detalle.tarea.actualizar');
        Route::post('ejecucion/detalle/documento/validar', [Ejecucion::class, 'validarDocumento'])->name('ejecucion.detalle.documento.validar');
        Route::post('ejecucion/detalle/etapa/grabar', [Ejecucion::class, 'grabarEtapa'])->name('ejecucion.detalle.etapa.grabar');
        Route::post('ejecucion/detalle/documento/subir', [Ejecucion::class, 'subirDocumento'])->name('ejecucion.detalle.documento.subir');
        Route::post('ejecucion/detalle/documento/{documento}/eliminar', [Ejecucion::class, 'eliminarDocumento'])->name('ejecucion.detalle.documento.eliminar');
        Route::get('ejecucion/detalle/{detalleFlujo}/progreso', [Ejecucion::class, 'progreso'])->name('ejecucion.detalle.progreso');
        
        // Rutas para pausar, reactivar y cancelar ejecuciones
        Route::post('ejecucion/detalle/{detalleFlujo}/pausar', [Ejecucion::class, 'pausarEjecucion'])->name('ejecucion.detalle.pausar');
        Route::post('ejecucion/detalle/{detalleFlujo}/reactivar', [Ejecucion::class, 'reactivarEjecucion'])->name('ejecucion.detalle.reactivar');
        Route::post('ejecucion/detalle/{detalleFlujo}/cancelar', [Ejecucion::class, 'cancelarEjecucion'])->name('ejecucion.detalle.cancelar');
        
        // Ruta para previsualizar flujo específico de una ejecución
        Route::get('ejecucion/detalle/{detalleFlujo}/previsualizar', [Ejecucion::class, 'previsualizarDetalle'])->name('ejecucion.detalle.previsualizar');
        
        // Ruta para re-ejecutar flujo (crear nueva ejecución completa)
        Route::post('ejecucion/{flujo}/re-ejecutar', [Ejecucion::class, 'reEjecutarFlujo'])->name('ejecucion.re-ejecutar');

    
        // Gestión de Usuarios (SUPERADMIN y ADMINISTRADOR)
        Route::get('/usuarios', [SuperAdminController::class, 'usuarios'])->name('usuarios');
        Route::get('/usuarios/create', [AdminController::class, 'createUsuario'])->name('usuarios.create');
        Route::post('/usuarios', [AdminController::class, 'storeUsuario'])->name('usuarios.store');
        Route::get('/usuarios/{usuario}/edit', [AdminController::class, 'editUsuario'])->name('usuarios.edit');
        Route::put('/usuarios/{usuario}', [AdminController::class, 'updateUsuario'])->name('usuarios.update');
        Route::delete('/usuarios/{usuario}', [AdminController::class, 'destroyUsuario'])->name('usuarios.destroy');
        Route::patch('/usuarios/{usuario}/toggle-estado', [AdminController::class, 'toggleUsuarioEstado'])->name('usuarios.toggle-estado');

            // Ejecución en flujo (admins hacia abajo)
        Route::resource('form-runs', App\Http\Controllers\FormRunController::class)->only(['index','create','store','edit','update','destroy']);
        Route::post('form-runs/{run}/submit',   [FormRunController::class,'submit'])->name('form-runs.submit');
        Route::post('form-runs/{run}/approve',  [FormRunController::class,'approve'])->name('form-runs.approve');
        Route::get ('form-runs/{run}/pdf/{template}', [PdfRenderController::class,'show'])->name('form-runs.pdf');

    });
    
    // Perfil de usuario (todos)
    Route::get('/perfil', [UserController::class, 'perfil'])->name('perfil');
    Route::put('/perfil', [UserController::class, 'updatePerfil'])->name('perfil.update');
});
