<?php

namespace App\Console\Commands;

use App\Enums\ApartmentStatus;
use App\Models\Apartment;
use App\Models\Complex;
use App\Models\Photo;
use App\Services\PhotoStore;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/*
 * Тестовые апартаменты для проверки сайта без ручного ввода в админке.
 * php artisan demo:apartments l3            два апартамента в ЖК L3
 * php artisan demo:apartments l3 --count=3  три
 * php artisan demo:apartments --remove      убрать все тестовые
 * Тестовые узнаются по адресу: он начинается с demo-. Данные собраны из
 * A4, A6 и A10, фото карточки из них же, галерея из фото A4 в своём порядке
 * у каждого апартамента; всё копируется в папку апартамента.
 */
class DemoApartments extends Command
{
    protected $signature = 'demo:apartments {complex? : Адрес ЖК в админке, например l3} {--count=2 : Сколько апартаментов добавить} {--remove : Удалить все тестовые апартаменты}';

    protected $description = 'Добавить тестовые апартаменты в ЖК или удалить их';

    /* Варианты карточек: тип, площадь, цена, этаж, фото */
    private const VARIANTS = [
        ['Студия', 30, 79000, 'Приземье', 'a4'],
        ['Однокомнатный', 38, 99500, '1-й этаж', 'a6'],
        ['Однокомнатный', 41, 104000, '2-й этаж', 'a10'],
        ['Двухкомнатный', 55, 135000, '3-й этаж', 'a4'],
    ];

    /* Характеристики A4, из них у каждого апартамента свой набор */
    private const FEATURES = [
        'Дневная зона с обеденной зоной', 'Кухня', 'Ванная комната', 'Французский балкон',
        'Вид на природу', 'Полностью меблирован', 'НДС включён',
    ];

    /* Галерея A4 с подписями */
    private const GALLERY = [
        ['a4-01.jpg', 'Кухня с мойкой и навесными шкафами'],
        ['a4-02.jpg', 'Гостиная с окном и журнальным столиком'],
        ['a4-03.jpg', 'Спальня с двуспальной кроватью'],
        ['a4-04.jpg', 'Гостиная с диваном и креслом'],
    ];

    public function handle(PhotoStore $photos): int
    {
        if ($this->option('remove')) {
            $demo = Apartment::query()->where('slug', 'like', 'demo-%')->get();
            foreach ($demo as $apartment) {
                $photos->deleteAll($apartment);
                $apartment->delete();
            }
            $this->info('Удалено тестовых апартаментов: '.$demo->count().'.');

            return self::SUCCESS;
        }

        $key = mb_strtolower(trim((string) $this->argument('complex')));
        $complex = $key === '' ? null : Complex::query()->where('slug', $key)->first();
        if (! $complex) {
            $this->error('Укажите ЖК по адресу из админки, например: php artisan demo:apartments l3');
            $this->line('Есть: '.Complex::query()->orderBy('sort_order')->pluck('slug')->implode(', '));

            return self::FAILURE;
        }

        $count = max(1, min(10, (int) $this->option('count')));
        $code = mb_strtoupper($complex->slug);
        $sort = (int) Apartment::query()->max('sort_order');
        $disk = Storage::disk('media');

        for ($i = 1; $i <= $count; $i++) {
            $n = Apartment::query()->where('slug', 'like', 'demo-'.$complex->slug.'-%')->count() + 1;
            [$type, $area, $price, $floor, $source] = self::VARIANTS[($n - 1) % count(self::VARIANTS)];
            $name = "Апартамент {$code}-{$n}";
            $slug = "demo-{$complex->slug}-{$n}";

            $apartment = Apartment::query()->create([
                'slug' => $slug,
                'name' => $name,
                'complex_id' => $complex->id,
                'status' => $n % 2 === 0 ? ApartmentStatus::Ready : ApartmentStatus::Building,
                'show_living' => true,
                'show_renting' => true,
                'show_newbuilds' => true,
                'show_home' => false,
                'city' => 'Златибор',
                'floor' => $floor,
                'type' => $type,
                'area' => $area,
                'price' => $price,
                'card_text' => "{$type}, {$area} м². Тестовый апартамент для проверки сайта.",
                'description' => "Апартамент в жилом комплексе {$complex->name}, Zlatibor, Сербия: {$type} площадью {$area} м², {$floor}. Тестовые данные, собранные из A4, A6 и A10.",
                'description_short' => "{$type} в ЖК {$complex->name}, Златибор.",
                'meta_title' => "{$name} в ЖК {$complex->name}, Златибор: ИКАН",
                'info_note' => 'Тестовый апартамент для проверки сайта. Цена включает НДС.',
                'about_title' => "{$name} в ЖК {$complex->name}",
                'about_text' => "Апартамент типа «{$type}» площадью {$area} м² расположен на уровне «{$floor}».",
                'features' => array_values(array_slice(array_merge(
                    array_slice(self::FEATURES, $n % count(self::FEATURES)),
                    array_slice(self::FEATURES, 0, $n % count(self::FEATURES)),
                ), 0, 5)),
                'data_items' => [
                    ['label' => 'Меблировка', 'value' => $n % 2 ? 'Полностью меблирован' : 'Частично меблирован'],
                    ['label' => 'НДС', 'value' => 'Включён в стоимость'],
                    ['label' => 'Статус наличия', 'value' => 'Требует подтверждения'],
                ],
                'consultation_title' => 'Получить консультацию по объекту',
                'sort_order' => ++$sort,
            ]);

            foreach ([Photo::ROLE_CARD => "{$source}-card.jpg", Photo::ROLE_CARD_MOBILE => "{$source}-card-mobile.jpg"] as $role => $file) {
                $from = 'apartments/'.$file;
                if (! $disk->exists($from)) {
                    continue;
                }
                $to = "apartments/{$slug}/{$role}.jpg";
                $disk->copy($from, $to);
                $size = @getimagesize($disk->path($to)) ?: [null, null];
                $apartment->photos()->create([
                    'role' => $role,
                    'path' => $to,
                    'alt' => $name,
                    'width' => $size[0],
                    'height' => $size[1],
                    'sort_order' => 1,
                ]);
            }

            /* Галерея: фото A4, сдвинутые на номер апартамента; фото для телефона: первое из них */
            $order = array_merge(array_slice(self::GALLERY, $n % 4), array_slice(self::GALLERY, 0, $n % 4));
            foreach ($order as $k => [$file, $alt]) {
                $from = 'apartments/'.$file;
                if (! $disk->exists($from)) {
                    continue;
                }
                $to = "apartments/{$slug}/gallery-".($k + 1).'.jpg';
                $disk->copy($from, $to);
                $size = @getimagesize($disk->path($to)) ?: [null, null];
                $apartment->photos()->create([
                    'role' => Photo::ROLE_GALLERY,
                    'path' => $to,
                    'alt' => $alt,
                    'width' => $size[0],
                    'height' => $size[1],
                    'sort_order' => $k + 1,
                ]);
                if ($k === 0) {
                    $mobile = "apartments/{$slug}/mobile.jpg";
                    $disk->copy($from, $mobile);
                    $msize = @getimagesize($disk->path($mobile)) ?: [null, null];
                    $apartment->photos()->create([
                        'role' => Photo::ROLE_MOBILE,
                        'path' => $mobile,
                        'alt' => $alt,
                        'width' => $msize[0],
                        'height' => $msize[1],
                        'sort_order' => 1,
                    ]);
                }
            }

            $this->info("Добавлен «{$name}» в ЖК {$complex->name}: {$type}, {$area} м², ".number_format($price, 0, '', ' ').' €.');
        }

        $this->line('Убрать тестовые: php artisan demo:apartments --remove');

        return self::SUCCESS;
    }
}
