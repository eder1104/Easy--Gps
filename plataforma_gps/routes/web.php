<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\GpsApiController;

// Grupo de rutas V1 (Versión 1)
Route::prefix('v1')->group(function () {
    
    // Ruta: GET /api/v1/vehiculos
    Route::get('/vehiculos', [GpsApiController::class, 'index']);
    
    // Ruta: GET /api/v1/vehiculos/{id}
    Route::get('/vehiculos/{id}', [GpsApiController::class, 'show']);
    
});