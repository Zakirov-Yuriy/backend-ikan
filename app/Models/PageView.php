<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
 * Просмотр страницы сайта (21.10). screen это экран сайта, from_screen
 * экран, с которого человек пришёл по ссылке внутри сайта (например, с
 * какого списка открыл карточку апартамента), referrer_host сайт, с
 * которого пришли со стороны (поиск, мессенджер), только для первой страницы.
 */
class PageView extends Model
{
    public const UPDATED_AT = null;

    public const DEVICE_MOBILE = 'mobile';

    public const DEVICE_TABLET = 'tablet';

    public const DEVICE_DESKTOP = 'desktop';

    public const DEVICES = [
        self::DEVICE_MOBILE => 'Телефон',
        self::DEVICE_TABLET => 'Планшет',
        self::DEVICE_DESKTOP => 'Компьютер',
    ];

    /* Устройство по строке браузера. iPad с iPadOS 13 и новее представляется компьютером Mac и считается компьютером */
    public static function deviceOf(string $agent): string
    {
        if (preg_match('/iPad|Tablet|PlayBook|Silk|Kindle/i', $agent) || (preg_match('/Android/i', $agent) && ! preg_match('/Mobi/i', $agent))) {
            return self::DEVICE_TABLET;
        }

        return preg_match('/Mobi|iPhone|iPod|Android|Windows Phone/i', $agent) ? self::DEVICE_MOBILE : self::DEVICE_DESKTOP;
    }

    /* Экраны сайта и их подписи в админке, по порядку меню сайта */
    public const SCREENS = [
        'home' => 'Главная',
        'living' => 'Для себя',
        'renting' => 'Для аренды',
        'newbuilds' => 'Новостройки',
        'investments' => 'Инвестиции',
        'restaurant' => 'Ресторан',
        'apartment' => 'Страница апартамента',
        'apartment_photos' => 'Фото апартамента',
        'document' => 'Документы',
    ];

    protected $fillable = ['day', 'visitor', 'path', 'screen', 'apartment_id', 'from_screen', 'referrer_host', 'device'];

    protected function casts(): array
    {
        return ['day' => 'date'];
    }

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    public static function screenLabel(?string $screen): string
    {
        return self::SCREENS[$screen] ?? 'Другое';
    }

    /**
     * Экран по адресу страницы. Для страниц апартамента ещё адрес (slug).
     * Неизвестные адреса (опечатки, старые ссылки) не считаются.
     *
     * @return array{0: string, 1: ?string}|null
     */
    public static function screenOf(string $path): ?array
    {
        $path = '/'.trim(strtok($path, '?#') ?: '', '/');
        $fixed = [
            '/' => 'home', '/dlya-sebya' => 'living', '/dlya-arendy' => 'renting',
            '/novostroyki' => 'newbuilds', '/investitsii' => 'investments',
            '/restoran' => 'restaurant', '/restoran/foto' => 'restaurant',
        ];
        if (isset($fixed[$path])) {
            return [$fixed[$path], null];
        }
        if (preg_match('#^/apartamenty/([a-z0-9-]+)(/foto)?$#', $path, $m)) {
            return [isset($m[2]) ? 'apartment_photos' : 'apartment', $m[1]];
        }
        if (preg_match('#^/dokumenty/[a-z0-9-]+$#', $path)) {
            return ['document', null];
        }

        return null;
    }

    /* Откуда пришли со стороны: понятное имя по сайту-источнику */
    public static function sourceLabel(?string $host): string
    {
        if ($host === null || $host === '') {
            return 'Прямой заход или закладка';
        }
        $rules = [
            '/(^|\.)(yandex\.[a-z.]+|ya\.ru|dzen\.ru)$/' => 'Яндекс',
            '/(^|\.)google\.[a-z.]+$/' => 'Google',
            '/(^|\.)(t\.me|telegram\.org|telegram\.me)$/' => 'Telegram',
            '/(^|\.)(vk\.com|vk\.ru|m\.vk\.com)$/' => 'ВКонтакте',
            '/(^|\.)(whatsapp\.com|wa\.me)$/' => 'WhatsApp',
            '/(^|\.)(bing\.com)$/' => 'Bing',
            '/(^|\.)(mail\.ru)$/' => 'Mail.ru',
        ];
        foreach ($rules as $pattern => $label) {
            if (preg_match($pattern, $host)) {
                return $label;
            }
        }

        return $host;
    }
}
