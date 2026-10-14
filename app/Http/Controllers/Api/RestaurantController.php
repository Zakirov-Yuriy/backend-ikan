<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RestaurantResource;
use App\Models\Restaurant;

class RestaurantController extends Controller
{
    /* Страница «Ресторан», одна запись */
    public function show(): RestaurantResource
    {
        return new RestaurantResource(Restaurant::current() ?? abort(404));
    }
}
