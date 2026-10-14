<?php

use App\Http\Controllers\Api\ApartmentController;
use App\Http\Controllers\Api\ComplexController;
use App\Http\Controllers\Api\RestaurantController;
use Illuminate\Support\Facades\Route;

/*
 * API для сайта, только чтение. Адреса начинаются с /api.
 * Ответы в JSON, обёрнуты в { "data": ... }.
 */
Route::get('/complexes', [ComplexController::class, 'index']);
Route::get('/apartments', [ApartmentController::class, 'index']);
Route::get('/apartments/{slug}', [ApartmentController::class, 'show']);
Route::get('/restaurant', [RestaurantController::class, 'show']);
