<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\CardController; // Asegúrate de que el namespace sea correcto
use App\Http\Controllers\Api\V1\ItemController;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;

// Prefijo para la versión de la API, todas las rutas dentro de este grupo tendrán 'v1' antes
Route::prefix('v1')->namespace('Api\V1')->group(function () {
    Route::apiResource('cards', CardController::class);
    Route::apiResource('items', ItemController::class);
});

Route::get('/images/{filename}', function ($filename) {
    $path = storage_path('app/public/' . $filename);

    if (!File::exists($path)) {
        abort(404);
    }

    $file = File::get($path);
    $type = File::mimeType($path);

    $response = Response::make($file, 200);
    $response->header("Content-Type", $type);

    return $response;
});
