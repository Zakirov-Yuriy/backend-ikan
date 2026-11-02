<?php

namespace App\Console\Commands;

use App\Models\Photo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/*
 * Битые фотографии (02.11): записи в базе, у которых файла на диске media
 * нет. Такие появлялись, когда PHP не мог писать в public/media: в админке
 * фото «есть», на сайте нет. Показать: php artisan photos:broken.
 * Удалить записи (файлов и так нет): php artisan photos:broken --delete.
 */
class PhotosBroken extends Command
{
    protected $signature = 'photos:broken {--delete : удалить записи без файла}';

    protected $description = 'Фотографии без файла на диске: показать или удалить записи';

    public function handle(): int
    {
        $disk = Storage::disk('media');
        $broken = Photo::query()->with('owner')->orderBy('id')->get()
            ->filter(fn (Photo $photo) => $photo->path === '' || $photo->path === '0' || ! $disk->exists($photo->path));

        if ($broken->isEmpty()) {
            $this->info('Битых фотографий нет.');

            return self::SUCCESS;
        }

        $this->table(
            ['id', 'Владелец', 'Роль', 'Путь'],
            $broken->map(fn (Photo $photo) => [
                $photo->id,
                class_basename($photo->owner_type).' #'.$photo->owner_id.(($label = $photo->owner?->name ?? $photo->owner?->title) ? ' '.$label : ''),
                $photo->role,
                $photo->path,
            ])->all(),
        );

        if (! $this->option('delete')) {
            $this->line('Удалить записи: php artisan photos:broken --delete');

            return self::SUCCESS;
        }

        foreach ($broken as $photo) {
            $photo->delete();
        }
        $this->info("Удалено записей: {$broken->count()}.");

        return self::SUCCESS;
    }
}
