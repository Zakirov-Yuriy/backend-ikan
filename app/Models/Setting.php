<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/* Настройки админки, ключ и значение */
class Setting extends Model
{
    public const NOTIFY_EMAILS = 'notify_emails';

    public const TELEGRAM_CHAT_ID = 'telegram_chat_id';

    /* Телефон на сайте: шапка, подвал, кнопки «Позвонить», тексты форм */
    public const PHONE = 'phone';

    /* Пока в админке не задан другой: телефон из макета подвала */
    public const DEFAULT_PHONE = '+7 (991) 255-29-00';

    /* Второй телефон (Саша, 16.10). Пустое поле в админке: на сайте один телефон */
    public const PHONE_2 = 'phone_2';

    public const DEFAULT_PHONE_2 = '+7 (991) 587-24-57';

    /*
     * Ссылки иконок в подвале, порядок как в макете. Пустое поле в админке:
     * иконка на сайте не показывается.
     */
    public const SOCIALS = [
        'max' => ['key' => 'social_max', 'label' => 'MAX', 'default' => 'https://max.ru/u/f9LHodD0cOKw4NzKJl7VwikboHE9YRgKRd8GrqCCE2A1VsGgQ56KcSluPHc'],
        'telegram' => ['key' => 'social_telegram', 'label' => 'Telegram', 'default' => 'https://t.me/ikan_serbia'],
        'vk' => ['key' => 'social_vk', 'label' => 'ВКонтакте', 'default' => 'https://vk.ru/probizvip'],
    ];

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['key', 'value'];

    public static function get(string $key, ?string $default = null): ?string
    {
        $row = static::query()->find($key);

        return $row?->value ?? $default;
    }

    public static function set(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /* Телефон как он написан на сайте */
    public static function phone(): string
    {
        $phone = trim((string) static::get(self::PHONE, ''));

        return $phone !== '' ? $phone : self::DEFAULT_PHONE;
    }

    /* Второй телефон как он написан на сайте, пустая строка если не нужен */
    public static function phone2(): string
    {
        return trim((string) static::get(self::PHONE_2, self::DEFAULT_PHONE_2));
    }

    /** @return string[] телефоны сайта: главный и второй, если задан */
    public static function phones(): array
    {
        return array_values(array_filter([static::phone(), static::phone2()], fn ($phone) => $phone !== ''));
    }

    /** @return array<string, string> ссылки иконок по ключу (max, telegram, vk), пустая строка если иконка не нужна */
    public static function socials(): array
    {
        return array_map(fn ($item) => trim((string) static::get($item['key'], $item['default'])), self::SOCIALS);
    }

    /* Адрес для ссылки «позвонить»: только цифры и плюс */
    public static function phoneHref(?string $phone = null): string
    {
        return 'tel:'.preg_replace('/[^\d+]/', '', $phone ?? static::phone());
    }

    /** @return string[] адреса для уведомлений о заявках */
    public static function notifyEmails(): array
    {
        $raw = (string) static::get(self::NOTIFY_EMAILS, '');
        $list = preg_split('/[\s,;]+/', $raw) ?: [];

        return array_values(array_filter(array_map('trim', $list), fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL)));
    }
}
