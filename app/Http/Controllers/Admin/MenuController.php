<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Route;

/* Подсветка пункта меню по имени текущего маршрута, как в админке ЛИДЛЕ */
class MenuController extends Controller
{
    /** @param  string|array<int, string>  $prefixes  имена маршрутов без последней части: admin.complexes */
    public static function active(string|array $prefixes): bool
    {
        $current = (string) Route::currentRouteName();
        foreach ((array) $prefixes as $prefix) {
            if ($current === $prefix || str_starts_with($current, $prefix.'.')) {
                return true;
            }
        }

        return false;
    }
}
