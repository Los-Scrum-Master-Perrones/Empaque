<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\CostosEmpaqueController;
use App\Http\Controllers\EstadisticoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmpleadoController;
use App\Http\Controllers\VinetaController;
use App\Http\Controllers\VinetaPorOrdenController;
use App\Http\Controllers\VinetaPendienteController;
use App\Http\Controllers\VinetaRegistroController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
});

/*
|--------------------------------------------------------------------------
| Rutas protegidas
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {

    
// Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware('permission:dashboard.ver')
    ->name('dashboard');

    // Perfil
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');

    // Usuarios
    Route::get('/usuarios', [UserController::class, 'index'])
        ->middleware('permission:usuarios.ver')
        ->name('usuarios.index');

    Route::get('/usuarios/crear', [UserController::class, 'create'])
        ->middleware('permission:usuarios.crear')
        ->name('usuarios.create');

    Route::post('/usuarios', [UserController::class, 'store'])
        ->middleware('permission:usuarios.crear')
        ->name('usuarios.store');

    Route::get('/usuarios/{user}/editar', [UserController::class, 'edit'])
        ->middleware('permission:usuarios.editar')
        ->name('usuarios.edit');

    Route::put('/usuarios/{user}', [UserController::class, 'update'])
        ->middleware('permission:usuarios.editar')
        ->name('usuarios.update');

    Route::patch('/usuarios/{user}/estado', [UserController::class, 'toggleStatus'])
        ->middleware('permission:usuarios.editar')
        ->name('usuarios.toggle-status');

  // Roles
Route::get('/roles', [RoleController::class, 'index'])
    ->middleware('permission:roles.ver')
    ->name('roles.index');

Route::get('/roles/crear', [RoleController::class, 'create'])
    ->middleware('permission:roles.crear')
    ->name('roles.create');

Route::post('/roles', [RoleController::class, 'store'])
    ->middleware('permission:roles.crear')
    ->name('roles.store');

    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])
    ->middleware('permission:roles.eliminar')
    ->name('roles.destroy');

Route::get('/roles/{role}/permisos', [RoleController::class, 'show'])
    ->middleware('permission:roles.ver')
    ->name('roles.show');

Route::get('/roles/{role}/permisos/editar', [RoleController::class, 'editPermissions'])
    ->middleware('permission:roles.editar')
    ->name('roles.permissions.edit');

Route::put('/roles/{role}/permisos', [RoleController::class, 'updatePermissions'])
    ->middleware('permission:roles.editar')
    ->name('roles.permissions.update');

    // Permisos
    Route::get('/permisos', [PermissionController::class, 'index'])
        ->middleware('permission:roles.ver')
        ->name('permisos.index');

        // Catalogos
        
        Route::get('/catalogos/productos', [CatalogoController::class, 'productos'])
    ->middleware('permission:productos.ver')
    ->name('catalogos.productos.index');

Route::post('/catalogos/productos/sincronizar', [CatalogoController::class, 'sincronizar'])
    ->middleware('permission:productos.sincronizar')
    ->name('catalogos.productos.sincronizar');

    Route::get('/catalogos/productos/{producto}', [CatalogoController::class, 'showProducto'])
    ->middleware('permission:productos.ver')
    ->name('catalogos.productos.show');

Route::post('/catalogos/productos/{producto}/actividades/{actividad}/toggle', [CatalogoController::class, 'toggleActividadProducto'])
    ->middleware('permission:productos.sincronizar')
    ->name('catalogos.productos.actividades.toggle');

    Route::get('/catalogos/marcas', [CatalogoController::class, 'marcas'])
    ->middleware('permission:marcas.ver')
    ->name('catalogos.marcas.index');

Route::get('/catalogos/vitolas', [CatalogoController::class, 'vitolas'])
    ->middleware('permission:vitolas.ver')
    ->name('catalogos.vitolas.index');

Route::get('/catalogos/capas', [CatalogoController::class, 'capas'])
    ->middleware('permission:capas.ver')
    ->name('catalogos.capas.index');

Route::get('/catalogos/actividades', [CatalogoController::class, 'actividades'])
    ->middleware('permission:actividades.ver')
    ->name('catalogos.actividades.index');

Route::post('/catalogos/actividades/sincronizar', [CatalogoController::class, 'sincronizarActividades'])
    ->middleware('permission:productos.sincronizar')
    ->name('catalogos.actividades.sincronizar');


    Route::get('/catalogos/empresas', [CatalogoController::class, 'empresas'])
    ->middleware('permission:catalogos.ver')
    ->name('catalogos.empresas.index');

Route::get('/catalogos/presentaciones', [CatalogoController::class, 'presentaciones'])
    ->middleware('permission:catalogos.ver')
    ->name('catalogos.presentaciones.index');

Route::get('/catalogos/tipos-empaque', [CatalogoController::class, 'tipoEmpaques'])
    ->middleware('permission:catalogos.ver')
    ->name('catalogos.tipo-empaques.index');
});

Route::middleware(['auth'])->group(function () {
    $blockSupervisor = \App\Http\Middleware\BlockReadOnlySupervisor::class;

    Route::get('/empleados', [EmpleadoController::class, 'index'])
        ->name('empleados.index');

    Route::post('/empleados/sincronizar', [EmpleadoController::class, 'sincronizar'])
        ->middleware($blockSupervisor)
        ->name('empleados.sincronizar');

    Route::get('/vinetas', [VinetaController::class, 'index'])
        ->name('vinetas.index');

    Route::get('/vinetas-por-orden', [VinetaPorOrdenController::class, 'index'])
        ->name('vinetas-por-orden.index');

    Route::post('/vinetas-por-orden', [VinetaPorOrdenController::class, 'store'])
        ->middleware($blockSupervisor)
        ->name('vinetas-por-orden.store');

    Route::get('/vinetas-por-orden/siguiente-info', [VinetaPorOrdenController::class, 'siguienteInfo'])
        ->name('vinetas-por-orden.siguiente-info');

    Route::get('/vinetas-pendientes', [VinetaPendienteController::class, 'index'])
        ->name('vinetas-pendientes.index');

    Route::get('/vinetas-registradas', [VinetaRegistroController::class, 'index'])
        ->name('vineta-registros.index');

    Route::get('/vinetas-registradas/exportar', [VinetaRegistroController::class, 'export'])
        ->name('vineta-registros.export');

    Route::get('/vinetas-registradas/reporte-semanal', [VinetaRegistroController::class, 'exportReporteSemanal'])
        ->name('vineta-registros.reporte-semanal');

    Route::get('/vinetas-registradas/documentos', [VinetaRegistroController::class, 'documentosPorFecha'])
        ->name('vineta-registros.documentos');

    Route::get('/vinetas-registradas/empleado', [VinetaRegistroController::class, 'empleado'])
        ->name('vineta-registros.empleado');

    Route::get('/vinetas-registradas/seguimiento/{vineta}', [VinetaRegistroController::class, 'seguimiento'])
        ->name('vineta-registros.seguimiento');

    Route::post('/vinetas-registradas/horas-ordinarias', [VinetaRegistroController::class, 'storeHoraOrdinaria'])
        ->middleware($blockSupervisor)
        ->name('vineta-registros.horas-ordinarias.store');

    Route::patch('/vinetas-registradas/horas-ordinarias/{horaOrdinaria}', [VinetaRegistroController::class, 'updateHoraOrdinaria'])
        ->middleware($blockSupervisor)
        ->name('vineta-registros.horas-ordinarias.update');

    Route::delete('/vinetas-registradas/horas-ordinarias/{horaOrdinaria}', [VinetaRegistroController::class, 'destroyHoraOrdinaria'])
        ->middleware($blockSupervisor)
        ->name('vineta-registros.horas-ordinarias.destroy');

    Route::patch('/vinetas-registradas/{vinetaRegistro}', [VinetaRegistroController::class, 'update'])
        ->middleware($blockSupervisor)
        ->name('vineta-registros.update');

    Route::delete('/vinetas-registradas/{vinetaRegistro}', [VinetaRegistroController::class, 'destroy'])
        ->middleware($blockSupervisor)
        ->name('vineta-registros.destroy');

    Route::get('/estadistico', [EstadisticoController::class, 'index'])
        ->name('estadistico.index');

    Route::get('/estadistico/detalle-actividad', [EstadisticoController::class, 'detalleActividad'])
        ->name('estadistico.detalle-actividad');

    Route::get('/costos-empaque', [CostosEmpaqueController::class, 'index'])
        ->middleware($blockSupervisor)
        ->name('costos-empaque.index');

    Route::get('/vinetas/notificaciones', [VinetaController::class, 'notificaciones'])
        ->middleware($blockSupervisor)
        ->name('vinetas.notificaciones');

    Route::post('/vinetas/sincronizar', [VinetaController::class, 'sincronizar'])
        ->middleware($blockSupervisor)
        ->name('vinetas.sincronizar');
});

Route::fallback(function () {
    if (auth()->check()) {
        return response()->view('errors.404', [
            'forceLayout' => true,
        ], 404);
    }

    return response()->view('errors.404', [
        'forceLayout' => false,
    ], 404);
});




require __DIR__.'/auth.php';
