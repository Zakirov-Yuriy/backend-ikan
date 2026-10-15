<?php

namespace App\Support;

use App\Models\Apartment;
use App\Models\Page;

/*
 * Куда может вести ссылка на сайте: страницы сайта и апартаменты.
 * Используется в выпадающих списках админки («Ссылка ведёт на»).
 */
class SitePages
{
    /** @return array<string, array<string, string>> группа => [адрес => название] */
    public static function groups(): array
    {
        $pages = ['/' => 'Главная'];
        foreach (Page::PAGES as $page) {
            $pages[$page['path']] = $page['name'];
        }
        $pages['/investitsii'] = 'Инвестиции в строительство';
        $pages['/restoran'] = 'Ресторан';

        $apartments = [];
        foreach (Apartment::query()->ordered()->get(['slug', 'name']) as $apartment) {
            $apartments[$apartment->siteUrl()] = $apartment->name;
        }

        return array_filter([
            'Страницы сайта' => $pages,
            'Апартаменты' => $apartments,
        ]);
    }

    /* Название страницы по адресу, для списков в админке */
    public static function label(?string $href): string
    {
        foreach (self::groups() as $options) {
            if (isset($options[$href])) {
                return $options[$href];
            }
        }

        return (string) $href;
    }

    /* Правило для адреса ссылки: путь на сайте, якорь или полный адрес */
    public static function rule(): string
    {
        return 'regex:/^(\/|#|https?:\/\/)/';
    }
}
