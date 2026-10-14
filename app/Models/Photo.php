<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

/* Фотография апартамента или ресторана, см. миграцию photos */
class Photo extends Model
{
    public const ROLE_CARD = 'card';

    public const ROLE_CARD_MOBILE = 'card_mobile';

    public const ROLE_GALLERY = 'gallery';

    public const ROLE_MOBILE = 'mobile';

    protected $fillable = ['role', 'path', 'alt', 'sort_order'];

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    /* Полный адрес файла для сайта */
    public function url(): string
    {
        return Storage::disk('media')->url($this->path);
    }
}
