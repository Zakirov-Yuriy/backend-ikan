<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ComplexResource;
use App\Models\Complex;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ComplexController extends Controller
{
    /* Все ЖК по порядку вкладок, включая пустые */
    public function index(): AnonymousResourceCollection
    {
        return ComplexResource::collection(
            Complex::query()->orderBy('sort_order')->orderBy('id')->get()
        );
    }
}
