<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/*
 * Строка главной страницы. Два вида:
 * scenario: строка направления с номером, текстом, ссылкой и фотографией справа;
 * direction: карточка «Наши направления» с фотографией на всю карточку.
 */
class HomeRow extends Model
{
    public const KIND_SCENARIO = 'scenario';

    public const KIND_DIRECTION = 'direction';

    public const KINDS = [
        self::KIND_SCENARIO => 'Строка направления',
        self::KIND_DIRECTION => 'Карточка «Наши направления»',
    ];

    protected $fillable = ['kind', 'title', 'text', 'link_label', 'link_href', 'sort_order'];

    public function photos(): MorphMany
    {
        return $this->morphMany(Photo::class, 'owner')->orderBy('sort_order')->orderBy('id');
    }

    public function photo(string $role): ?Photo
    {
        return $this->photos->firstWhere('role', $role);
    }

    public function scopeOfKind(Builder $query, string $kind): Builder
    {
        return $query->where('kind', $kind);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function isScenario(): bool
    {
        return $this->kind === self::KIND_SCENARIO;
    }

    public function kindLabel(): string
    {
        return self::KINDS[$this->kind] ?? $this->kind;
    }
}
