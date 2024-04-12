<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\CardController; // Asegúrate de que el namespace sea correcto

// Prefijo para la versión de la API, todas las rutas dentro de este grupo tendrán 'v1' antes
Route::prefix('v1')->group(function () {
    // Ruta para obtener la lista de todas las tarjetas (index)
    Route::get('/cards', [CardController::class, 'index']);

    // Ruta para crear una nueva tarjeta (store)
    Route::post('/cards', [CardController::class, 'store']);

    // Ruta para obtener una tarjeta específica (show)
    Route::get('/cards/{card}', [CardController::class, 'show']);

    // Ruta para actualizar una tarjeta específica (update)
    Route::put('/cards/{card}', [CardController::class, 'update']);
    Route::patch('/cards/{card}', [CardController::class, 'update']);

    // Ruta para eliminar una tarjeta específica (destroy)
    Route::delete('/cards/{card}', [CardController::class, 'destroy']);
});
