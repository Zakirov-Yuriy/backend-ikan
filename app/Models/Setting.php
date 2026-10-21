<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/* Настройки админки, ключ и значение */
class Setting extends Model
{
    public const NOTIFY_EMAILS = 'notify_emails';

    public const TELEGRAM_CHAT_ID = 'telegram_chat_id';

    /* Битрикс24 (21.10): ссылка входящего вебхука, что создавать, ответственный, воронка и этап сделки */
    public const BITRIX_WEBHOOK = 'bitrix_webhook';

    public const BITRIX_ENTITY = 'bitrix_entity';

    public const BITRIX_ASSIGNED = 'bitrix_assigned';

    public const BITRIX_CATEGORY = 'bitrix_category';

    public const BITRIX_STAGE = 'bitrix_stage';

    public const BITRIX_LEAD = 'lead';

    public const BITRIX_DEAL = 'deal';

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

    /* Что создавать в Битрикс24: лид (по умолчанию) или сделку с контактом */
    public static function bitrixEntity(): string
    {
        $value = (string) static::get(self::BITRIX_ENTITY, (string) config('services.bitrix24.entity', self::BITRIX_LEAD));

        return $value === self::BITRIX_DEAL ? self::BITRIX_DEAL : self::BITRIX_LEAD;
    }

    /* Номер ответственного сотрудника в Битрикс24, null: тот, кто создал вебхук */
    public static function bitrixAssigned(): ?int
    {
        $value = (int) static::get(self::BITRIX_ASSIGNED, (string) config('services.bitrix24.assigned', ''));

        return $value > 0 ? $value : null;
    }

    /* Номер воронки сделок, null: общая воронка (номер 0) */
    public static function bitrixCategory(): ?int
    {
        $value = trim((string) static::get(self::BITRIX_CATEGORY, ''));

        return $value === '' ? null : (int) $value;
    }

    /** @return string[] адреса для уведомлений о заявках */
    public static function notifyEmails(): array
    {
        $raw = (string) static::get(self::NOTIFY_EMAILS, '');
        $list = preg_split('/[\s,;]+/', $raw) ?: [];

        return array_values(array_filter(array_map('trim', $list), fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL)));
    }
}
