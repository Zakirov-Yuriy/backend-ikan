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
     * подвала, null если иконку не показывать. metrika: счётчик Яндекс
     * Метрики (02.11), id null, если счётчик не задан в «Настройках».
     */
    public function show(): JsonResponse
    {
        $phone = Setting::phone();
        $socials = array_map(fn ($url) => $url !== '' ? $url : null, Setting::socials());

        return response()->json(['data' => [
            'phone' => ['label' => $phone, 'href' => Setting::phoneHref($phone)],
            'phones' => array_map(fn ($item) => ['label' => $item, 'href' => Setting::phoneHref($item)], Setting::phones()),
            'socials' => $socials,
            /* Коды подтверждения для мета-тегов в head сайта (05.11), null если не заданы */
            'verification' => [
                'yandex' => ($y = trim((string) Setting::get(Setting::YANDEX_VERIFICATION, ''))) !== '' ? $y : null,
                'google' => ($g = trim((string) Setting::get(Setting::GOOGLE_VERIFICATION, ''))) !== '' ? $g : null,
            ],
            'metrika' => [
                'id' => ($id = trim((string) Setting::get(Setting::METRIKA_ID, ''))) !== '' ? (int) $id : null,
                'webvisor' => Setting::get(Setting::METRIKA_WEBVISOR, '') === '1',
            ],
        ]]);
    }
}
