<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\GpsApiController;
use App\Http\Controllers\Api\V1\AuthController;

Route::prefix('v1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/vehiculos', [GpsApiController::class, 'index']);
        Route::get('/vehiculos/{id}', [GpsApiController::class, 'show']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});