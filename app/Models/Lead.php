<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/* Заявка с сайта */
class Lead extends Model
{
    public const SOURCE_CALLBACK = 'callback';

    public const SOURCE_POPUP = 'popup';

    /* Окно заявки открылось само через заданное в настройках время (21.11) */
    public const SOURCE_AUTO = 'popup_auto';

    public const STATUS_NEW = 'new';

    public const STATUS_DONE = 'done';

    protected $fillable = [
        'name', 'phone', 'email', 'interest', 'source', 'page',
        'consent_terms', 'consent_privacy', 'consent_ads', 'ip', 'user_agent', 'status',
    ];

    protected function casts(): array
    {
        return [
            'consent_terms' => 'boolean',
            'consent_privacy' => 'boolean',
            'consent_ads' => 'boolean',
        ];
    }

    public function scopeFresh(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_NEW);
    }

    /* Подпись источника для админки и уведомлений */
    public function sourceLabel(): string
    {
        return match ($this->source) {
            self::SOURCE_POPUP => 'Окно «Оставить заявку»',
            self::SOURCE_AUTO => 'Окно «Оставить заявку», открылось само',
            default => 'Форма «Заказать обратный звонок»',
        };
    }

    public function isNew(): bool
    {
        return $this->status === self::STATUS_NEW;
    }
}
