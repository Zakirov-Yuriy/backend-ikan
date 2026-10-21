<?php

use App\Http\Controllers\Api\ApartmentController;
use App\Http\Controllers\Api\ComplexController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\PageController;
use App\Http\Controllers\Api\PageViewController;
use App\Http\Controllers\Api\RestaurantController;
use App\Http\Controllers\Api\SiteController;
use Illuminate\Support\Facades\Route;

/*
 * API для сайта. Адреса начинаются с /api, ответы в JSON, обёрнуты в
 * { "data": ... }. Чтение объектов, текстов главной и списков, приём заявок.
 */
Route::get('/site', [SiteController::class, 'show']);
Route::get('/home', [HomeController::class, 'show']);
Route::get('/pages/{key}', [PageController::class, 'show']);
Route::get('/complexes', [ComplexController::class, 'index']);
Route::get('/apartments', [ApartmentController::class, 'index']);
Route::get('/apartments/{slug}', [ApartmentController::class, 'show']);
Route::get('/restaurant', [RestaurantController::class, 'show']);

/* Заявка с формы сайта, не больше 20 в минуту с одного адреса посетителя */
Route::post('/requests', [LeadController::class, 'store'])->middleware('throttle:20,1');

/* Просмотр страницы сайта для «Статистики» (21.10), не больше 120 в минуту с одного адреса */
Route::post('/views', [PageViewController::class, 'store'])->middleware('throttle:120,1');
