<?php

namespace Database\Seeders;

use App\Models\Apartment;
use App\Models\HomePage;
use App\Models\HomeRow;
use App\Models\Page;
use App\Models\Photo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/*
 * Тексты главной страницы и вступления списков, как они были записаны в
 * файлах сайта (content/home.ts, living.ts, renting.ts, newbuilds.ts).
 * Фотографии главной копируются из database/seeders/media/home.
 *
 * Запуск: php artisan db:seed --class=HomeSeeder. Повторный запуск ничего
 * не трогает: если главная уже есть, правки из админки остаются.
 */
class HomeSeeder extends Seeder
{
    public function run(): void
    {
        $this->copyMedia();
        $this->pages();
        $this->objects();

        if (HomePage::query()->exists()) {
            /* До 15.10 13:37 «Весь каталог» никуда не вёл; теперь адрес решён */
            HomePage::query()->where('objects_all_href', '#')->update(['objects_all_href' => '/dlya-sebya']);
            /* Правка Саши 16.10: заголовок, если его ещё не меняли в админке */
            HomePage::query()->where('hero_title', 'Пространство для жизни.')->update(['hero_title' => 'Пространство для жизни и инвестиций.']);
            $this->command?->info('Главная уже заполнена, тексты не тронуты.');

            return;
        }

        $this->homePage();
        $this->scenarios();
        $this->directions();
    }

    private function copyMedia(): void
    {
        $from = database_path('seeders/media/home');
        $disk = Storage::disk('media');
        foreach (File::files($from) as $file) {
            $disk->put('home/'.$file->getFilename(), File::get($file->getPathname()));
        }
    }

    private function homePage(): void
    {
        $home = HomePage::query()->create([
            'meta_title' => 'ИКАН: недвижимость и инвестиции в Сербии',
            'meta_description' => 'Подбираем квартиры для собственного проживания, сдачи в аренду и инвестиций в строительство.',

            'hero_eyebrow' => 'Недвижимость в Сербии',
            /* Правка Саши 16.10: «Пространство для жизни и инвестиций» */
            'hero_title' => 'Пространство для жизни и инвестиций.',
            'hero_title_accent' => 'Актив для будущего.',
            'hero_lead' => 'Подбираем квартиры для собственного проживания, сдачи в аренду и инвестиций в строительство.',
            'hero_primary_label' => 'Смотреть объекты',
            'hero_secondary_label' => 'Обсудить задачу',
            'hero_side_label' => 'Работаем на вашей стороне',
            'hero_side_items' => ['Локальная экспертиза', 'Юридическая проверка', 'Сопровождение сделки'],

            'about_eyebrow' => 'О компании',
            'about_title' => "Недвижимость и инвестиции в\u{a0}Сербии",
            'about_text' => 'Подбираем квартиры для собственного проживания, сдачи в аренду и инвестирования в строительство.',
            'about_features_label' => 'Почему инвестируют в Сербию',
            'about_features' => [
                ['icon' => 'chart', 'label' => 'Высокий туристический спрос'],
                ['icon' => 'shield', 'label' => 'Европейское качество'],
                ['icon' => 'key', 'label' => 'Рост стоимости активов'],
                ['icon' => 'chart', 'label' => "Доходность до\u{a0}30% годовых"],
            ],

            'directions_label' => 'Наши направления',

            'objects_eyebrow' => 'Проверенная недвижимость',
            'objects_title' => 'Актуальные объекты',
            'objects_all_label' => 'Весь каталог',
            /* «Весь каталог» ведёт на «Квартиры для собственного проживания» (Анна, 15.10 13:37) */
            'objects_all_href' => '/dlya-sebya',
            'objects_more_label' => 'Подробнее',

            'consultation_eyebrow' => 'Персональный подбор',
            'consultation_title' => 'Подберём объект под ваши цели',
            'consultation_text' => 'Подберём объект или инвестиционную стратегию под ваш бюджет и цели.',
            'consultation_button_label' => 'Получить консультацию',
        ]);
        $home->photos()->create([
            'role' => Photo::ROLE_CONSULTATION,
            'path' => 'home/consultation.jpg',
            'alt' => '',
            'sort_order' => 1,
        ]);
    }

    private function scenarios(): void
    {
        $rows = [
            [
                'title' => 'Покупка жилья для себя',
                'text' => 'Подберём готовую квартиру для собственного проживания в подходящем районе Сербии.',
                'link_label' => 'Смотреть квартиры для жизни',
                'link_href' => '/dlya-sebya',
                'photo' => ['home/scenario-01.jpg', 'Гостиная с креслом и кухней'],
                'photo_wide' => ['home/scenario-01-wide.jpg', 'Гостиная с креслом и кухней'],
            ],
            [
                'title' => 'Покупка жилья для сдачи',
                'text' => 'Найдём ликвидную квартиру для сдачи в аренду и стабильного дохода в евро.',
                'link_label' => 'Смотреть объекты для аренды',
                'link_href' => '/dlya-arendy',
                'photo' => ['home/scenario-02.jpg', 'Гостиная с телевизором и журнальным столиком'],
            ],
            [
                'title' => 'Покупка и перепродажа недвижимости',
                'text' => 'Приобретите недвижимость на перспективном этапе и продайте дороже после роста её стоимости.',
                'link_label' => 'Выбрать объект для инвестиций',
                'link_href' => '/novostroyki',
                'photo' => ['home/scenario-03.jpg', 'Вид с балкона на Златибор'],
            ],
            [
                'title' => 'Инвестиции в недвижимость',
                'text' => 'Инвестируйте определённую сумму в перспективные проекты и участвуйте в росте стоимости недвижимости.',
                'link_label' => 'Узнать об инвестициях',
                'link_href' => '/investitsii',
                'photo' => ['home/scenario-04.jpg', 'Кухня и выход на балкон'],
            ],
            [
                'title' => 'Ресторан в аренду',
                'text' => 'Готовое пространство для вашего ресторанного бизнеса. Узнайте условия аренды и подробности предложения.',
                'link_label' => 'Подробнее о ресторане',
                'link_href' => '/restoran',
                'photo' => ['home/scenario-05.jpg', 'Обеденный стол со стульями'],
            ],
        ];
        $this->rows(HomeRow::KIND_SCENARIO, $rows);
    }

    private function directions(): void
    {
        $rows = [
            [
                'title' => 'Жильё для себя',
                'text' => 'Покупка квартиры для собственного проживания',
                'link_href' => '/dlya-sebya',
                'photo' => ['home/direction-01.jpg', ''],
            ],
            [
                'title' => 'Жильё для сдачи',
                'text' => 'Покупка квартиры с арендным потенциалом',
                'link_href' => '/dlya-arendy',
                'photo' => ['home/direction-02.jpg', ''],
            ],
            [
                'title' => 'Новостройки',
                'text' => 'На этапе строительства и готовые объекты',
                'link_href' => '/novostroyki',
                'photo' => ['home/direction-03.jpg', ''],
            ],
            [
                'title' => 'Инвестирование',
                'text' => '12% годовых · от $5 000 · в валюте',
                'link_href' => '/investitsii',
                'photo' => ['home/direction-04.jpg', ''],
            ],
        ];
        $this->rows(HomeRow::KIND_DIRECTION, $rows);
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    private function rows(string $kind, array $rows): void
    {
        foreach ($rows as $i => $row) {
            $item = HomeRow::query()->create([
                'kind' => $kind,
                'title' => $row['title'],
                'text' => $row['text'],
                'link_label' => $row['link_label'] ?? null,
                'link_href' => $row['link_href'],
                'sort_order' => $i + 1,
            ]);
            foreach ([Photo::ROLE_PHOTO, Photo::ROLE_PHOTO_WIDE] as $role) {
                if (isset($row[$role])) {
                    $item->photos()->create([
                        'role' => $role,
                        'path' => $row[$role][0],
                        'alt' => $row[$role][1],
                        'sort_order' => 1,
                    ]);
                }
            }
        }
    }

    /* Вступления списков; если запись уже есть, не трогается */
    private function pages(): void
    {
        $pages = [
            'living' => [
                'title' => 'Квартиры для собственного проживания',
                'lead' => 'Покупка проверенного жилья для жизни — от готовых квартир до семейных апартаментов.',
                'meta_title' => 'Квартиры для собственного проживания в Сербии: ИКАН',
                'meta_description' => 'Покупка проверенного жилья для жизни: от готовых квартир до семейных апартаментов.',
                'consultation_title' => null,
                'consultation_title_mobile' => 'Подберём жильё для вашей семьи',
            ],
            'renting' => [
                'title' => 'Квартиры для сдачи в аренду',
                'lead' => 'Покупка ликвидного жилья в Сербии со стабильным арендным доходом.',
                'meta_title' => 'Квартиры для сдачи в аренду в Сербии: ИКАН',
                'meta_description' => 'Покупка ликвидного жилья в Сербии со стабильным арендным доходом.',
                'consultation_title' => 'Подберём квартиру для арендного дохода',
                'consultation_title_mobile' => 'Подберём жильё для вашей семьи',
            ],
            'newbuilds' => [
                'title' => 'Новостройки в Сербии',
                'lead' => 'Квартиры на стадии строительства и готовые объекты от надёжных девелоперов.',
                'meta_title' => 'Новостройки в Сербии: ИКАН',
                'meta_description' => 'Квартиры на стадии строительства и готовые объекты от надёжных девелоперов.',
                'consultation_title' => 'Войти в проект на выгодном этапе',
                'consultation_title_mobile' => null,
            ],
        ];
        foreach ($pages as $key => $data) {
            Page::query()->firstOrCreate(['key' => $key], $data);
        }
    }

    /* «Актуальные объекты»: три апартамента, если галочку ещё никому не ставили */
    private function objects(): void
    {
        if (Apartment::query()->onHome()->exists()) {
            return;
        }
        Apartment::query()->whereIn('slug', ['a4', 'a6', 'a10'])->update(['show_home' => true]);
    }
}
