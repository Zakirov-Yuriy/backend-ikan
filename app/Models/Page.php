<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/* Вступление страницы списка: заголовок, подзаголовок, для поисковиков, блок подбора */
class Page extends Model
{
    /* Ключ страницы, её название в админке и адрес на сайте */
    public const PAGES = [
        'living' => ['name' => 'Квартиры для собственного проживания', 'path' => '/dlya-sebya'],
        'renting' => ['name' => 'Квартиры для сдачи в аренду', 'path' => '/dlya-arendy'],
        'newbuilds' => ['name' => 'Новостройки в Сербии', 'path' => '/novostroyki'],
    ];

    protected $fillable = [
        'key', 'title', 'lead', 'meta_title', 'meta_description',
        'consultation_title', 'consultation_title_mobile',
    ];

    public function name(): string
    {
        return self::PAGES[$this->key]['name'] ?? $this->key;
    }

    public function siteUrl(): string
    {
        return self::PAGES[$this->key]['path'] ?? '/';
    }

    public static function forKey(string $key): ?self
    {
        return static::query()->where('key', $key)->first();
    }
}
