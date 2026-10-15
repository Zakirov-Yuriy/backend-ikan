<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PageResource;
use App\Models\Page;

class PageController extends Controller
{
    /* Вступление страницы списка: /api/pages/living, renting, newbuilds */
    public function show(string $key): PageResource
    {
        return new PageResource(Page::forKey($key) ?? abort(404));
    }
}
