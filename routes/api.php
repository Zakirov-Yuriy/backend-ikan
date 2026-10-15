<?php

use App\Http\Controllers\Api\ApartmentController;
use App\Http\Controllers\Api\ComplexController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\RestaurantController;
use Illuminate\Support\Facades\Route;

/*
 * API для сайта. Адреса начинаются с /api, ответы в JSON, обёрнуты в
 * { "data": ... }. Чтение объектов и приём заявок с форм.
 */
Route::get('/complexes', [ComplexController::class, 'index']);
Route::get('/apartments', [ApartmentController::class, 'index']);
Route::get('/apartments/{slug}', [ApartmentController::class, 'show']);
Route::get('/restaurant', [RestaurantController::class, 'show']);

/* Заявка с формы сайта, не больше 20 в минуту с одного адреса посетителя */
Route::post('/requests', [LeadController::class, 'store'])->middleware('throttle:20,1');
