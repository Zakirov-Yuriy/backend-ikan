<?php

namespace App\Services;

use App\Models\Apartment;
use App\Models\Photo;
use App\Models\Restaurant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/*
 * Загрузка и удаление фотографий объекта на диске media.
 * Файл кладётся в папку объекта: apartments/a4/card-k3j9x2.jpg.
 * Роли с одной фотографией (card, card_mobile, mobile) заменяются,
 * галерея (gallery) дополняется в конец.
 */
class PhotoStore
{
    public function folder(Apartment|Restaurant $owner): string
    {
        return $owner instanceof Apartment ? 'apartments/'.$owner->slug : 'restaurant';
    }

    /* Ставит одну фотографию роли, старую удаляет с диска */
    public function replace(Apartment|Restaurant $owner, string $role, UploadedFile $file, ?string $alt = null): Photo
    {
        foreach ($owner->photos()->where('role', $role)->get() as $old) {
            $this->delete($old);
        }

        return $this->add($owner, $role, $file, $alt, 1);
    }

    /* Добавляет фотографию в галерею в конец */
    public function append(Apartment|Restaurant $owner, UploadedFile $file, ?string $alt = null): Photo
    {
        $last = (int) $owner->photos()->where('role', Photo::ROLE_GALLERY)->max('sort_order');

        return $this->add($owner, Photo::ROLE_GALLERY, $file, $alt, $last + 1);
    }

    public function delete(Photo $photo): void
    {
        Storage::disk('media')->delete($photo->path);
        $photo->delete();
    }

    /* Удаляет все фотографии объекта вместе с папкой */
    public function deleteAll(Apartment|Restaurant $owner): void
    {
        foreach ($owner->photos()->get() as $photo) {
            $this->delete($photo);
        }
        Storage::disk('media')->deleteDirectory($this->folder($owner));
    }

    private function add(Apartment|Restaurant $owner, string $role, UploadedFile $file, ?string $alt, int $sort): Photo
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
        if ($extension === 'jpeg') {
            $extension = 'jpg';
        }
        $name = $role.'-'.Str::lower(Str::random(6)).'.'.$extension;
        $path = Storage::disk('media')->putFileAs($this->folder($owner), $file, $name);
        $size = @getimagesize(Storage::disk('media')->path($path)) ?: [null, null];

        return $owner->photos()->create([
            'role' => $role,
            'path' => $path,
            'alt' => $alt ?: null,
            'width' => $size[0],
            'height' => $size[1],
            'sort_order' => $sort,
        ]);
    }
}
