<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApartmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\Complex;
use App\Models\Photo;
use Illuminate\View\View;

/* Главная админки: сколько чего в базе */
class HomeController extends Controller
{
    public function index(): View
    {
        return view('admin.home', [
            'complexes' => Complex::query()->count(),
            'apartments' => Apartment::query()->count(),
            'building' => Apartment::query()->where('status', ApartmentStatus::Building)->count(),
            'ready' => Apartment::query()->where('status', ApartmentStatus::Ready)->count(),
            'photos' => Photo::query()->count(),
            'recent' => Apartment::query()->with('complex')->latest('updated_at')->limit(5)->get(),
        ]);
    }
}
