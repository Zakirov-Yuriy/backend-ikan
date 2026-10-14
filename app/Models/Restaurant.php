<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/* Страница «Ресторан»: одна запись */
class Restaurant extends Model
{
    protected $fillable = [
        'title', 'lead', 'meta_title', 'meta_description',
        'status', 'name', 'city', 'floor', 'type', 'area', 'price_text', 'description',
        'info_type', 'info_purpose', 'info_price', 'info_floor', 'info_note',
        'about_title', 'about_text', 'features', 'data_items', 'consultation_title',
    ];

    protected function casts(): array
    {
        return [
            'area' => 'decimal:2',
            'features' => 'array',
            'data_items' => 'array',
        ];
    }

    public function photos(): MorphMany
    {
        return $this->morphMany(Photo::class, 'owner')->orderBy('sort_order')->orderBy('id');
    }

    public function photo(string $role): ?Photo
    {
        return $this->photos->firstWhere('role', $role);
    }

    /* Единственная запись; создаётся загрузкой данных с сайта */
    public static function current(): ?self
    {
        return static::query()->with('photos')->orderBy('id')->first();
    }
}
