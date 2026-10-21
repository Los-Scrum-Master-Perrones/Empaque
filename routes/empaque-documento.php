<?php

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
});
