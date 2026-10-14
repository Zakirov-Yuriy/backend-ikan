<?php

namespace Tests\Feature\Api;

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

    public function test_seeder_runs_twice_without_duplicates(): void
    {
        $this->seed(SiteContentSeeder::class);
        $this->getJson('/api/complexes')->assertJsonCount(4, 'data');
        $this->getJson('/api/apartments')->assertJsonCount(3, 'data');
        $this->getJson('/api/apartments/a4')->assertJsonCount(4, 'data.gallery');
    }

    /* Файлы в public/media отдаёт веб-сервер, не Laravel; здесь проверка, что они на месте */
    public function test_photo_files_are_copied_to_media_disk(): void
    {
        foreach (['apartments/a4-card.jpg', 'apartments/a4-01.jpg', 'restaurant/photo-mobile.jpg'] as $path) {
            $this->assertTrue(Storage::disk('media')->exists($path), $path);
        }
        $this->assertSame(
            config('app.url').'/media/apartments/a4-card.jpg',
            Storage::disk('media')->url('apartments/a4-card.jpg'),
        );
    }
}
