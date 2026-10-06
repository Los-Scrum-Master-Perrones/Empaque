<?php

use App\Http\Controllers\Api\ActividadController as ApiActividadController;
use App\Http\Controllers\Api\AppUpdateController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DocumentoEmpaqueController;
use App\Http\Controllers\Api\EmpleadoController as ApiEmpleadoController;
use App\Http\Controllers\Api\EmpleadoHoraOrdinariaController as ApiEmpleadoHoraOrdinariaController;
use App\Http\Controllers\Api\VinetaController as ApiVinetaController;
use App\Http\Controllers\Api\VinetaRegistroController as ApiVinetaRegistroController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/login', [AuthController::class, 'login']);
Route::get('/vinetas-registradas', [ApiVinetaRegistroController::class, 'feed'])
    ->name('api.vinetas-registradas');
Route::get('/registros-erp', [ApiVinetaRegistroController::class, 'feedErp'])
    ->name('api.registros-erp');
Route::get('/documentos-empaques', [DocumentoEmpaqueController::class, 'index'])
    ->name('api.documentos-empaques');
Route::post('/registrar-vineta-erp', [DocumentoEmpaqueController::class, 'registrarVinetaErp'])
    ->name('api.registrar-vineta-erp');

Route::get('/app-update', [AppUpdateController::class, 'check'])->name('api.app.update');
Route::get('/app/latest', [AppUpdateController::class, 'check']);
Route::get('/app/download/{file?}', [AppUpdateController::class, 'download'])->name('api.app.download');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/user', [AuthController::class, 'me']);
    Route::post('/user/photo', [AuthController::class, 'updatePhoto']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/vinetas/scan', [ApiVinetaController::class, 'scan']);
    Route::get('/vinetas/seguimiento', [ApiVinetaRegistroController::class, 'seguimiento']);
    Route::get('/vinetas/{vineta}/actividades', [ApiVinetaController::class, 'actividades']);
    Route::post('/vinetas/{vineta}/registros', [ApiVinetaRegistroController::class, 'store']);
    Route::get('/vineta-registros', [ApiVinetaRegistroController::class, 'index']);
    Route::post('/vineta-registros', [ApiVinetaRegistroController::class, 'store']);
    Route::patch('/vineta-registros/{vinetaRegistro}', [ApiVinetaRegistroController::class, 'update']);
    Route::get('/actividades/search', [ApiActividadController::class, 'search']);
    Route::get('/empleados/seguimiento', [ApiVinetaRegistroController::class, 'seguimientoEmpleado']);
    Route::get('/empleados/horas-ordinarias/resumen', [ApiEmpleadoHoraOrdinariaController::class, 'resumenEmpleados']);
    Route::get('/empleados/{empleado}/resumen-diario', [ApiVinetaRegistroController::class, 'resumenDiarioEmpleado']);
    Route::get('/empleados/{empleado}/horas-ordinarias', [ApiEmpleadoHoraOrdinariaController::class, 'index']);
    Route::post('/empleados/{empleado}/horas-ordinarias', [ApiEmpleadoHoraOrdinariaController::class, 'store']);
    Route::patch('/empleados/{empleado}/horas-ordinarias/{horaOrdinaria}', [ApiEmpleadoHoraOrdinariaController::class, 'update']);
    Route::delete('/empleados/{empleado}/horas-ordinarias/{horaOrdinaria}', [ApiEmpleadoHoraOrdinariaController::class, 'destroy']);
    Route::post('/empleados/{empleado}/jornada-laboral', [ApiEmpleadoHoraOrdinariaController::class, 'distributeJornada']);
    Route::delete('/empleados/{empleado}/jornada-laboral', [ApiEmpleadoHoraOrdinariaController::class, 'destroyJornada']);
    Route::post('/empleados/jornada-laboral/global', [ApiEmpleadoHoraOrdinariaController::class, 'distributeGlobal']);
    Route::delete('/empleados/jornada-laboral/global', [ApiEmpleadoHoraOrdinariaController::class, 'destroyGlobal']);
    Route::post('/empleados/horas-ordinarias/global', [ApiEmpleadoHoraOrdinariaController::class, 'storeGlobal']);
    Route::post('/empleados/lookup', [ApiEmpleadoController::class, 'lookup']);
});
