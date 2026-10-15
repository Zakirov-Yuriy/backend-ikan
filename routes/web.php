<?php

use App\Http\Controllers\Admin\ApartmentController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\ComplexController;
use App\Http\Controllers\Admin\HomeController;
use App\Http\Controllers\Admin\RestaurantController;
use Illuminate\Support\Facades\Route;

/* Корень бэкенда ведёт в админку */
Route::redirect('/', '/admin');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AuthController::class, 'show'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login.attempt');
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware('auth')->group(function () {
        Route::get('/', [HomeController::class, 'index'])->name('home');

        Route::post('complexes/sort', [ComplexController::class, 'sort'])->name('complexes.sort');
        Route::resource('complexes', ComplexController::class)->except('show');

        Route::post('apartments/sort', [ApartmentController::class, 'sort'])->name('apartments.sort');
        Route::resource('apartments', ApartmentController::class)->except('show');

        Route::get('restaurant', [RestaurantController::class, 'edit'])->name('restaurant.edit');
        Route::put('restaurant', [RestaurantController::class, 'update'])->name('restaurant.update');
    });
});
