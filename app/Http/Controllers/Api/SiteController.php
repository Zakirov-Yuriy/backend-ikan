<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class SiteController extends Controller
{
    /* Общие данные сайта из настроек: пока только телефон */
    public function show(): JsonResponse
    {
        $phone = Setting::phone();

        return response()->json(['data' => [
            'phone' => ['label' => $phone, 'href' => Setting::phoneHref($phone)],
        ]]);
    }
}
