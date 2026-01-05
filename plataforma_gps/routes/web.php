<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GpsController;

Route::get('/', [GpsController::class, 'index'])->name('index');
Route::get('/coche/{id}', [GpsController::class, 'show'])->name('coche.detalle');