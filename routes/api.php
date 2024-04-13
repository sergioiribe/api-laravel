<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\CardController; // Asegúrate de que el namespace sea correcto
use App\Http\Controllers\Api\V1\ItemController;

// Prefijo para la versión de la API, todas las rutas dentro de este grupo tendrán 'v1' antes
Route::prefix('v1')->namespace('Api\V1')->group(function () {
    Route::apiResource('cards', CardController::class);
    Route::apiResource('items', ItemController::class);
});