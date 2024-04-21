<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\CardController; // Asegúrate de que el namespace sea correcto
use App\Http\Controllers\Api\V1\ItemController;
use App\Http\Controllers\Api\V1\SpanishCardController;
use App\Http\Controllers\Api\V1\SpanishItemController;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;

// Prefijo para la versión de la API, todas las rutas dentro de este grupo tendrán 'v1' antes
Route::prefix('v1')->namespace('Api\V1')->group(function () {
    Route::apiResource('cards', CardController::class);
    Route::apiResource('spanish_cards', SpanishCardController::class);
    Route::apiResource('items', ItemController::class);
    Route::apiResource('spanish_items', SpanishItemController::class);
    
});

