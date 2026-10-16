<?php

namespace Tests\Feature\Api;

use App\Models\Apartment;
use App\Models\HomePage;
use Database\Seeders\SiteContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/* Проверка API сайта на данных, загруженных с сайта */
class SiteApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SiteContentSeeder::class);
    }

    public function test_complexes_in_order(): void
    {
        $this->getJson('/api/complexes')
            ->assertOk()
            ->assertJsonPath('data.*.name', ['L1', 'L2', 'L3', 'L4']);
    }

    public function test_apartment_cards_with_photos_and_complex(): void
    {
        $response = $this->getJson('/api/apartments')->assertOk();
        $response->assertJsonPath('data.*.slug', ['a4', 'a6', 'a10']);
        $response->assertJsonPath('data.0.complex.slug', 'l1');
        $response->assertJsonPath('data.2.complex.slug', 'l2');
        $response->assertJsonPath('data.0.price', 75500);
        $response->assertJsonPath('data.0.area', 28);
        $response->assertJsonPath('data.0.status_label', 'Строится');
        $response->assertJsonPath('data.0.photos.card.url', config('app.url').'/media/apartments/a4-card.jpg');
        $response->assertJsonPath('data.0.photos.card_mobile.width', 369);
        $response->assertJsonPath('data.0.photos.card_mobile.height', 261);
        $response->assertJsonPath('data.0.photos.card_mobile.url', config('app.url').'/media/apartments/a4-card-mobile.jpg');
        $response->assertJsonPath('data.0.url', '/apartamenty/a4');
    }

    public function test_filters(): void
    {
        $this->getJson('/api/apartments?catalog=renting')->assertOk()->assertJsonCount(3, 'data');
        $this->getJson('/api/apartments?complex=l2')->assertOk()->assertJsonPath('data.*.slug', ['a10']);
        $this->getJson('/api/apartments?complex=l3')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/apartments?status=ready')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/apartments?status=building')->assertOk()->assertJsonCount(3, 'data');
        $this->getJson('/api/apartments?catalog=unknown')->assertUnprocessable();
        $this->getJson('/api/apartments?complex=l9')->assertUnprocessable();
    }

    public function test_apartment_page(): void
    {
        $response = $this->getJson('/api/apartments/a4')->assertOk();
        $response->assertJsonPath('data.meta.title', 'Апартамент A4 в SVETOGORSKA 27, Златибор: ИКАН');
        $response->assertJsonPath('data.description_short', 'Студия в проекте SVETOGORSKA 27, Златибор.');
        $response->assertJsonCount(7, 'data.about.features');
        $response->assertJsonCount(3, 'data.data_items');
        $response->assertJsonPath('data.data_items.0.label', 'Меблировка');
        $response->assertJsonCount(4, 'data.gallery');
        $response->assertJsonPath('data.gallery.0.alt', 'Кухня с мойкой и навесными шкафами');
        $response->assertJsonPath('data.photo_mobile.url', config('app.url').'/media/apartments/a4-mobile.jpg');

        /* У A6 страницы на сайте не было: галерея пустая, но ответ есть */
        $this->getJson('/api/apartments/a6')->assertOk()->assertJsonCount(0, 'data.gallery');
        $this->getJson('/api/apartments/nope')->assertNotFound();
    }

    public function test_restaurant(): void
    {
        $response = $this->getJson('/api/restaurant')->assertOk();
        $response->assertJsonPath('data.title', 'Арендуйте ресторан');
        $response->assertJsonPath('data.area', 100);
        $response->assertJsonPath('data.info.purpose', 'Ресторанный бизнес');
        $response->assertJsonCount(7, 'data.about.features');
        $response->assertJsonPath('data.about.features.0.label', 'Назначение');
        $response->assertJsonCount(4, 'data.gallery');
        $response->assertJsonPath('data.photo_mobile.url', config('app.url').'/media/restaurant/photo-mobile.jpg');
    }

    public function test_home(): void
    {
        $response = $this->getJson('/api/home')->assertOk();
        $response->assertJsonPath('data.hero.title', 'Пространство для жизни и инвестиций.');
        $response->assertJsonPath('data.hero.primary_label', 'Смотреть объекты');
        $response->assertJsonCount(3, 'data.hero.side_items');
        $response->assertJsonCount(5, 'data.scenarios');
        $response->assertJsonPath('data.scenarios.0.link.href', '/dlya-sebya');
        $response->assertJsonPath('data.scenarios.0.photo_wide.url', config('app.url').'/media/home/scenario-01-wide.jpg');
        $response->assertJsonPath('data.scenarios.1.photo_wide', null);
        $response->assertJsonPath('data.scenarios.4.photo.url', config('app.url').'/media/home/scenario-05.jpg');
        $response->assertJsonCount(4, 'data.about.features');
        $response->assertJsonPath('data.about.features.1.icon', 'shield');
        $response->assertJsonCount(4, 'data.directions.items');
        $response->assertJsonPath('data.directions.items.3.href', '/investitsii');
        /* «Актуальные объекты»: апартаменты с галочкой, в порядке списка апартаментов */
        $response->assertJsonCount(3, 'data.objects.items');
        $response->assertJsonPath('data.objects.items.0.slug', 'a4');
        $response->assertJsonPath('data.objects.items.0.photos.card.url', config('app.url').'/media/apartments/a4-card.jpg');
        $response->assertJsonPath('data.objects.all.label', 'Весь каталог');
        $response->assertJsonPath('data.objects.all.href', '/dlya-sebya');
        $response->assertJsonPath('data.consultation.photo.url', config('app.url').'/media/home/consultation.jpg');

        Apartment::query()->where('slug', 'a6')->update(['show_home' => false]);
        $this->getJson('/api/home')->assertJsonCount(2, 'data.objects.items');

        /* Не больше трёх (16.10): четыре с галочкой, на главной первые три по порядку */
        Apartment::query()->update(['show_home' => true]);
        $this->artisan('demo:apartments', ['complex' => 'l3', '--count' => 2])->assertSuccessful();
        Apartment::query()->update(['show_home' => true]);
        Apartment::query()->where('slug', 'a4')->update(['sort_order' => 100]);
        $slugs = collect($this->getJson('/api/home')->assertJsonCount(3, 'data.objects.items')->json('data.objects.items'))->pluck('slug')->all();
        $this->assertNotContains('a4', $slugs);
        $this->assertSame(Apartment::query()->ordered()->limit(3)->pluck('slug')->all(), $slugs);

        /* Без галочек: первые три из общего списка */
        Apartment::query()->update(['show_home' => false]);
        $slugs = collect($this->getJson('/api/home')->assertJsonCount(3, 'data.objects.items')->json('data.objects.items'))->pluck('slug')->all();
        $this->assertSame(Apartment::query()->ordered()->limit(3)->pluck('slug')->all(), $slugs);
    }

    public function test_pages(): void
    {
        $this->getJson('/api/pages/living')->assertOk()
            ->assertJsonPath('data.title', 'Квартиры для собственного проживания')
            ->assertJsonPath('data.url', '/dlya-sebya')
            ->assertJsonPath('data.consultation.title', 'Подберём объект под ваши цели', 'пустой заголовок: как на главной')
            ->assertJsonPath('data.consultation.title_mobile', 'Подберём жильё для вашей семьи');
        $this->getJson('/api/pages/newbuilds')->assertOk()
            ->assertJsonPath('data.consultation.title', 'Войти в проект на выгодном этапе');
        $this->getJson('/api/pages/nope')->assertNotFound();
    }

    public function test_seeder_runs_twice_without_duplicates(): void
    {
        $this->seed(SiteContentSeeder::class);
        $this->getJson('/api/complexes')->assertJsonCount(4, 'data');
        $this->getJson('/api/apartments')->assertJsonCount(3, 'data');
        $this->getJson('/api/apartments/a4')->assertJsonCount(4, 'data.gallery');
        $this->getJson('/api/home')->assertJsonCount(5, 'data.scenarios')->assertJsonCount(4, 'data.directions.items');
        $this->assertSame(1, HomePage::query()->count());

        /* У кого «Весь каталог» ещё вёл в никуда, после повторной загрузки ведёт на список */
        HomePage::query()->update(['objects_all_href' => '#', 'hero_title' => 'Своё']);
        $this->seed(SiteContentSeeder::class);
        $this->getJson('/api/home')->assertJsonPath('data.objects.all.href', '/dlya-sebya')->assertJsonPath('data.hero.title', 'Своё');

        /* Старый заголовок по умолчанию заменяется, свой из админки остаётся */
        HomePage::query()->update(['hero_title' => 'Пространство для жизни.']);
        $this->seed(SiteContentSeeder::class);
        $this->getJson('/api/home')->assertJsonPath('data.hero.title', 'Пространство для жизни и инвестиций.');

        /* Телефон по умолчанию, пока в настройках не задан */
        $this->getJson('/api/site')->assertOk()->assertJsonPath('data.phone.href', 'tel:+79912552900');
    }

    /* Файлы в public/media отдаёт веб-сервер, не Laravel; здесь проверка, что они на месте */
    public function test_photo_files_are_copied_to_media_disk(): void
    {
        foreach (['apartments/a4-card.jpg', 'apartments/a4-01.jpg', 'restaurant/photo-mobile.jpg', 'home/consultation.jpg'] as $path) {
            $this->assertTrue(Storage::disk('media')->exists($path), $path);
        }
        $this->assertSame(
            config('app.url').'/media/apartments/a4-card.jpg',
            Storage::disk('media')->url('apartments/a4-card.jpg'),
        );
    }
}
