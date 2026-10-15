<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\HomeResource;
use App\Models\HomePage;

class HomeController extends Controller
{
    /* Главная страница целиком; 404, пока HomeSeeder не запускали */
    public function show(): HomeResource
    {
        return new HomeResource(HomePage::current() ?? abort(404));
    }
}
