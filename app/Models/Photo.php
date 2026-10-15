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

    protected $fillable = ['role', 'path', 'alt', 'width', 'height', 'sort_order'];

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    /* Полный адрес файла для сайта */
    public function url(): string
    {
        return Storage::disk('media')->url($this->path);
    }

    /*
     * Ширина и высота в точках. Считаются по файлу один раз и запоминаются;
     * getimagesize встроен в PHP и не требует GD.
     *
     * @return array{0: int, 1: int}|null
     */
    public function size(): ?array
    {
        if ($this->width && $this->height) {
            return [$this->width, $this->height];
        }
        $file = Storage::disk('media')->path($this->path);
        $size = is_file($file) ? @getimagesize($file) : false;
        if (! $size) {
            return null;
        }
        $this->forceFill(['width' => $size[0], 'height' => $size[1]])->saveQuietly();

        return [$size[0], $size[1]];
    }
}
