<?php

use App\Http\Controllers\EmpaqueComparativoErpController;
use App\Http\Controllers\VinetaRegistroController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de empaque (documento)
|--------------------------------------------------------------------------
|
| Se cargan aparte de routes/web.php para no mezclarlas con el resto.
|
*/

Route::middleware(['auth', 'verified'])->group(function () {
    // Cambia TODAS las viñetas de un documento a por tarea / por hora y deja
    // las horas del ERP en 0.
    Route::post('/vinetas-registradas/documento/{documento}/modo-pago', [VinetaRegistroController::class, 'cambiarModoPagoDocumento'])
        ->name('vineta-registros.documento.modo-pago');

    // Comparativo ERP vs sistema (bajo Costos de empaque).
    Route::get('/costos-empaque/comparativo', [EmpaqueComparativoErpController::class, 'index'])
        ->name('costos-empaque.comparativo');

    Route::post('/costos-empaque/comparativo/descargar', [EmpaqueComparativoErpController::class, 'descargar'])
        ->name('costos-empaque.comparativo.descargar');
});
