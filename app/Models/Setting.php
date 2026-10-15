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
