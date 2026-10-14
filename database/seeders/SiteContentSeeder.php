<?php

namespace Database\Seeders;

use App\Enums\ApartmentStatus;
use App\Models\Apartment;
use App\Models\Complex;
use App\Models\Photo;
use App\Models\Restaurant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/*
 * Загружает в базу то, что сейчас записано в файлах сайта (content/*.ts):
 * четыре ЖК, апартаменты A4, A6, A10 и страницу ресторана. Фотографии
 * копируются из database/seeders/media на диск media (public/media).
 *
 * Запуск повторный безопасен: записи ищутся по slug и обновляются, лишние
 * не удаляются. Полная пересборка: php artisan migrate:fresh --seed.
 */
class SiteContentSeeder extends Seeder
{
    /* Общий конец текста карточек, как на сайте */
    private const BY_PRESENTATION = 'Цена и характеристики указаны по презентации проекта SVETOGORSKA 27.';

    public function run(): void
    {
        $this->copyMedia();
        $complexes = $this->complexes();
        $this->apartments($complexes);
        $this->restaurant();
    }

    /* Копирует картинки в public/media, существующие перезаписывает */
    private function copyMedia(): void
    {
        $from = database_path('seeders/media');
        $disk = Storage::disk('media');
        foreach (File::allFiles($from) as $file) {
            $relative = str_replace('\\', '/', $file->getRelativePathname());
            $disk->put($relative, File::get($file->getPathname()));
        }
    }

    /** @return array<string, Complex> */
    private function complexes(): array
    {
        $result = [];
        foreach (['l1' => 'L1', 'l2' => 'L2', 'l3' => 'L3', 'l4' => 'L4'] as $i => $name) {
            $result[$i] = Complex::updateOrCreate(
                ['slug' => $i],
                ['name' => $name, 'sort_order' => count($result) + 1],
            );
        }

        return $result;
    }

    /** @param array<string, Complex> $complexes */
    private function apartments(array $complexes): void
    {
        $common = [
            'status' => ApartmentStatus::Building,
            'show_living' => true,
            'show_renting' => true,
            'show_newbuilds' => true,
            'city' => 'Златибор',
            'consultation_title' => 'Получить консультацию по объекту',
        ];

        $a4 = Apartment::updateOrCreate(['slug' => 'a4'], $common + [
            'name' => 'Апартамент A4',
            'complex_id' => $complexes['l1']->id,
            'floor' => 'Приземье',
            'type' => 'Студия',
            'area' => 28,
            'price' => 75500,
            'card_text' => 'Студия, 28 м². '.self::BY_PRESENTATION,
            'meta_title' => 'Апартамент A4 в SVETOGORSKA 27, Златибор: ИКАН',
            'meta_description' => 'Студия 28 м² на уровне «Приземье» в жилом комплексе SVETOGORSKA 27 в Златиборе. Полностью меблирована, цена 75 500 € с НДС.',
            'description' => 'Компактная студия в жилом комплексе SVETOGORSKA 27, расположенном в Zlatibor, Сербия. Продуманное пространство, современный интерьер и уютная атмосфера создают комфортные условия для проживания и отдыха.',
            'description_short' => 'Студия в проекте SVETOGORSKA 27, Златибор.',
            'info_note' => 'Цена включает НДС. Апартамент полностью меблирован. Актуальный статус наличия необходимо подтвердить перед сделкой.',
            'about_title' => 'Апартамент A4 в SVETOGORSKA 27',
            'about_text' => 'Апартамент типа «студия» площадью 28 м² расположен на уровне «Приземье». В презентации показаны интерьер и планировка конкретного апартамента.',
            'features' => [
                'Дневная зона с обеденной зоной',
                'Кухня',
                'Ванная комната',
                'Французский балкон',
                'Вид на природу',
                'Полностью меблирован',
                'НДС включён',
            ],
            'data_items' => [
                ['label' => 'Меблировка', 'value' => 'Полностью меблирован'],
                ['label' => 'НДС', 'value' => 'Включён в стоимость'],
                ['label' => 'Статус наличия', 'value' => 'Требует подтверждения'],
            ],
            'sort_order' => 1,
        ]);
        $this->photos($a4, [
            [Photo::ROLE_CARD, 'apartments/a4-card.jpg', 'Гостиная с диваном, телевизором и обеденным столом'],
            [Photo::ROLE_CARD_MOBILE, 'apartments/a4-card-mobile.jpg', 'Гостиная с диваном, телевизором и обеденным столом'],
            [Photo::ROLE_GALLERY, 'apartments/a4-01.jpg', 'Кухня с мойкой и навесными шкафами'],
            [Photo::ROLE_GALLERY, 'apartments/a4-02.jpg', 'Гостиная с окном и журнальным столиком'],
            [Photo::ROLE_GALLERY, 'apartments/a4-03.jpg', 'Спальня с двуспальной кроватью'],
            [Photo::ROLE_GALLERY, 'apartments/a4-04.jpg', 'Гостиная с диваном и креслом'],
            [Photo::ROLE_MOBILE, 'apartments/a4-mobile.jpg', 'Гостиная с окном и журнальным столиком'],
        ]);

        /* У A6 и A10 на сайте есть только карточки: макетов страниц не было */
        $a6 = Apartment::updateOrCreate(['slug' => 'a6'], $common + [
            'name' => 'Апартамент A6',
            'complex_id' => $complexes['l1']->id,
            'floor' => '1-й этаж',
            'type' => 'Однокомнатный',
            'area' => 37,
            'price' => 98500,
            'card_text' => 'Однокомнатный, 37 м². '.self::BY_PRESENTATION,
            'sort_order' => 2,
        ]);
        $this->photos($a6, [
            [Photo::ROLE_CARD, 'apartments/a6-card.jpg', 'Прихожая со шкафом, кухня и вид на гостиную'],
            [Photo::ROLE_CARD_MOBILE, 'apartments/a6-card-mobile.jpg', 'Прихожая со шкафом, кухня и вид на гостиную'],
        ]);

        $a10 = Apartment::updateOrCreate(['slug' => 'a10'], $common + [
            'name' => 'Апартамент A10',
            'complex_id' => $complexes['l2']->id,
            'floor' => '2-й этаж',
            'type' => 'Однокомнатный',
            'area' => 37,
            'price' => 98500,
            'card_text' => 'Однокомнатный, 37 м². '.self::BY_PRESENTATION,
            'sort_order' => 3,
        ]);
        $this->photos($a10, [
            [Photo::ROLE_CARD, 'apartments/a10-card.jpg', 'Гостиная с диваном, креслом и журнальным столиком'],
            [Photo::ROLE_CARD_MOBILE, 'apartments/a10-card-mobile.jpg', 'Гостиная с диваном, креслом и журнальным столиком'],
        ]);
    }

    private function restaurant(): void
    {
        $restaurant = Restaurant::query()->orderBy('id')->first() ?? new Restaurant;
        $restaurant->fill([
            'title' => 'Арендуйте ресторан',
            'lead' => 'Готовое пространство для вашего ресторанного бизнеса. Узнайте условия аренды и подробности предложений.',
            'meta_title' => 'Ресторан в аренду в Златиборе: ИКАН',
            'meta_description' => 'Готовое пространство для вашего ресторанного бизнеса. Узнайте условия аренды и подробности предложений.',
            'status' => 'Готов',
            'name' => 'Ресторан',
            'city' => 'Златибор',
            'floor' => 'Приземье',
            'type' => 'Ресторан',
            'area' => 100,
            'price_text' => 'Узнать',
            'description' => 'Возможность открыть или развивать ресторанный бизнес в SVETOGORSKA 27. Пространство предназначено для предпринимателей и инвесторов, заинтересованных в аренде помещения под ресторанный проект в Zlatibor.',
            'info_type' => 'Коммерческое помещение',
            'info_purpose' => 'Ресторанный бизнес',
            'info_price' => 'По запросу',
            'info_floor' => 'Приземье',
            'info_note' => 'Условия аренды и характеристики помещения необходимо уточнить у представителя проекта.',
            'about_title' => 'Помещение под ресторан',
            'about_text' => 'Коммерческое пространство в Zlatibor, Сербия, для предпринимателей, планирующих открыть собственный ресторан или развивать существующий ресторанный бизнес.',
            'features' => [
                ['label' => 'Назначение', 'value' => 'Ресторанный бизнес'],
                ['label' => 'Формат', 'value' => 'Коммерческая аренда'],
                ['label' => 'Расположение', 'value' => 'SVETOGORSKA 27, Zlatibor'],
                ['label' => 'Условия аренды', 'value' => 'По запросу'],
                ['label' => 'Площадь помещения', 'value' => 'Уточняется'],
                ['label' => 'Стоимость аренды', 'value' => 'По запросу'],
                ['label' => 'Доступность', 'value' => 'Требует подтверждения'],
            ],
            'data_items' => [
                ['label' => 'Назначение', 'value' => 'Ресторан'],
                ['label' => 'Формат', 'value' => 'Аренда для бизнеса'],
                ['label' => 'Условия аренды', 'value' => 'Уточняются'],
            ],
            'consultation_title' => 'Получить консультацию по объекту',
        ])->save();
        $this->photos($restaurant, [
            [Photo::ROLE_GALLERY, 'restaurant/photo-01.jpg', 'Кухня с мойкой и навесными шкафами'],
            [Photo::ROLE_GALLERY, 'restaurant/photo-02.jpg', 'Гостиная с окном и журнальным столиком'],
            [Photo::ROLE_GALLERY, 'restaurant/photo-03.jpg', 'Спальня с двуспальной кроватью'],
            [Photo::ROLE_GALLERY, 'restaurant/photo-04.jpg', 'Гостиная с диваном и креслом'],
            [Photo::ROLE_MOBILE, 'restaurant/photo-mobile.jpg', 'Гостиная с окном и журнальным столиком'],
        ]);
    }

    /**
     * Записывает фотографии объекта заново в указанном порядке.
     *
     * @param  array<int, array{0: string, 1: string, 2: string}>  $rows  [роль, путь, подпись]
     */
    private function photos(Apartment|Restaurant $owner, array $rows): void
    {
        $owner->photos()->delete();
        foreach ($rows as $i => [$role, $path, $alt]) {
            $owner->photos()->create([
                'role' => $role,
                'path' => $path,
                'alt' => $alt,
                'sort_order' => $i + 1,
            ]);
        }
    }
}
