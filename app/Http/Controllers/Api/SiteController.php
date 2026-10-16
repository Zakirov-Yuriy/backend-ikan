<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class SiteController extends Controller
{
    /*
     * Общие данные сайта из настроек. phone: главный телефон (кнопки
     * «Позвонить»), phones: все телефоны для текста, socials: ссылки иконок
     * подвала, null если иконку не показывать.
     */
    public function show(): JsonResponse
    {
        $phone = Setting::phone();
        $socials = array_map(fn ($url) => $url !== '' ? $url : null, Setting::socials());

        return response()->json(['data' => [
            'phone' => ['label' => $phone, 'href' => Setting::phoneHref($phone)],
            'phones' => array_map(fn ($item) => ['label' => $item, 'href' => Setting::phoneHref($item)], Setting::phones()),
            'socials' => $socials,
        ]]);
    }
}
