<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/* Заявка с сайта */
class Lead extends Model
{
    public const SOURCE_CALLBACK = 'callback';

    public const SOURCE_POPUP = 'popup';

    public const STATUS_NEW = 'new';

    public const STATUS_DONE = 'done';

    protected $fillable = [
        'name', 'phone', 'interest', 'source', 'page',
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
        return $this->source === self::SOURCE_POPUP ? 'Окно «Оставить заявку»' : 'Форма «Заказать обратный звонок»';
    }

    public function isNew(): bool
    {
        return $this->status === self::STATUS_NEW;
    }
}
