<?php

namespace App\Services;

use App\Exceptions\PhotoStoreException;
use App\Models\Apartment;
use App\Models\HomePage;
use App\Models\HomeRow;
use App\Models\Photo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\Flysystem\FilesystemException;

/*
 * Загрузка и удаление фотографий объекта на диске media.
 * Файл кладётся в папку объекта: apartments/a4/card-k3j9x2.jpg.
 * Роли с одной фотографией (card, card_mobile, mobile, photo, photo_wide,
 * consultation) заменяются, галерея (gallery) дополняется в конец.
 */
class PhotoStore
{
    /* Папка владельца на диске media: апартамент, ресторан, главная, строка главной */
    public function folder(Model $owner): string
    {
        return match (true) {
            $owner instanceof Apartment => 'apartments/'.$owner->slug,
            $owner instanceof HomeRow => 'home/'.$owner->kind.'-'.$owner->id,
            $owner instanceof HomePage => 'home',
            default => 'restaurant',
        };
    }

    /* Ставит одну фотографию роли, старую удаляет с диска */
    public function replace(Model $owner, string $role, UploadedFile $file, ?string $alt = null): Photo
    {
        foreach ($owner->photos()->where('role', $role)->get() as $old) {
            $this->delete($old);
        }

        return $this->add($owner, $role, $file, $alt, 1);
    }

    /* Добавляет фотографию в галерею в конец */
    public function append(Model $owner, UploadedFile $file, ?string $alt = null): Photo
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
    public function deleteAll(Model $owner): void
    {
        foreach ($owner->photos()->get() as $photo) {
            $this->delete($photo);
        }
        Storage::disk('media')->deleteDirectory($this->folder($owner));
    }

    private function add(Model $owner, string $role, UploadedFile $file, ?string $alt, int $sort): Photo
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
        if ($extension === 'jpeg') {
            $extension = 'jpg';
        }
        $name = $role.'-'.Str::lower(Str::random(6)).'.'.$extension;
        $folder = $this->folder($owner);
        try {
            $path = Storage::disk('media')->putFileAs($folder, $file, $name);
        } catch (FilesystemException) {
            /* Папку нельзя создать: та же причина, нет прав */
            $path = false;
        }
        if ($path === false || ! Storage::disk('media')->exists($path)) {
            /* Обычно нет прав на запись: папка public/media принадлежит не тому пользователю, от которого работает PHP */
            throw new PhotoStoreException(
                "Фото «{$file->getClientOriginalName()}» не удалось сохранить на сервере: нет доступа на запись в папку public/media/{$folder}. "
                .'Тексты формы сохранены, это фото нет. Сообщите разработчику или администратору сервера.'
            );
        }
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
