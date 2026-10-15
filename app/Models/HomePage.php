<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/* Тексты главной страницы сайта: одна запись */
class HomePage extends Model
{
    /* Значки блока «Почему инвестируют в Сербию», нарисованы на сайте */
    public const ICONS = [
        'chart' => 'График',
        'shield' => 'Щит',
        'key' => 'Ключ',
    ];

    protected $fillable = [
        'meta_title', 'meta_description',
        'hero_eyebrow', 'hero_title', 'hero_title_accent', 'hero_lead',
        'hero_primary_label', 'hero_secondary_label', 'hero_side_label', 'hero_side_items',
        'about_eyebrow', 'about_title', 'about_text', 'about_features_label', 'about_features',
        'directions_label',
        'objects_eyebrow', 'objects_title', 'objects_all_label', 'objects_all_href', 'objects_more_label',
        'consultation_eyebrow', 'consultation_title', 'consultation_text', 'consultation_button_label',
    ];

    protected function casts(): array
    {
        return [
            'hero_side_items' => 'array',
            'about_features' => 'array',
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

    /* Единственная запись; создаётся загрузкой данных (HomeSeeder) */
    public static function current(): ?self
    {
        return static::query()->with('photos')->orderBy('id')->first();
    }
}
