<?php

namespace App\Models;

use App\Enums\ApartmentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/* Апартамент: карточка в списках и своя страница на сайте */
class Apartment extends Model
{
    /* Каталоги сайта и поле, которое отвечает за показ в каждом */
    public const CATALOGS = [
        'living' => 'show_living',
        'renting' => 'show_renting',
        'newbuilds' => 'show_newbuilds',
    ];

    protected $fillable = [
        'slug', 'name', 'complex_id', 'status',
        'show_living', 'show_renting', 'show_newbuilds', 'show_home',
        'city', 'floor', 'type', 'area', 'price', 'card_text',
        'meta_title', 'meta_description', 'description', 'description_short',
        'info_note', 'about_title', 'about_text', 'features', 'data_items',
        'consultation_title', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'status' => ApartmentStatus::class,
            'show_living' => 'boolean',
            'show_renting' => 'boolean',
            'show_newbuilds' => 'boolean',
            'show_home' => 'boolean',
            'area' => 'decimal:2',
            'price' => 'integer',
            'features' => 'array',
            'data_items' => 'array',
        ];
    }

    public function complex(): BelongsTo
    {
        return $this->belongsTo(Complex::class);
    }

    public function photos(): MorphMany
    {
        return $this->morphMany(Photo::class, 'owner')->orderBy('sort_order')->orderBy('id');
    }

    /* Первая фотография с данной ролью (card, card_mobile, mobile) */
    public function photo(string $role): ?Photo
    {
        return $this->photos->firstWhere('role', $role);
    }

    /* Карточки блока «Актуальные объекты» на главной */
    public function scopeOnHome(Builder $query): Builder
    {
        return $query->where('show_home', true);
    }

    /* Отбор по каталогу сайта: living, renting, newbuilds */
    public function scopeInCatalog(Builder $query, string $catalog): Builder
    {
        return $query->where(self::CATALOGS[$catalog], true);
    }

    /* Порядок карточек в списках: по ЖК, потом по sort_order */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /* Адрес страницы на сайте */
    public function siteUrl(): string
    {
        return '/apartamenty/'.$this->slug;
    }
}
