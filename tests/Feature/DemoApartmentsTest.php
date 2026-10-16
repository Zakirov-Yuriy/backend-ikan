<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\Photo;
use Database\Seeders\SiteContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/* Команда demo:apartments: тестовые апартаменты в ЖК и их удаление */
class DemoApartmentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_adds_and_removes_demo_apartments(): void
    {
        $this->seed(SiteContentSeeder::class);

        $this->artisan('demo:apartments')->assertFailed();
        $this->artisan('demo:apartments', ['complex' => 'nope'])->assertFailed();

        $this->artisan('demo:apartments', ['complex' => 'L3'])
            ->expectsOutputToContain('Апартамент L3-1')
            ->expectsOutputToContain('Апартамент L3-2')
            ->assertSuccessful();
        $this->artisan('demo:apartments', ['complex' => 'l3', '--count' => 1])->expectsOutputToContain('Апартамент L3-3')->assertSuccessful();

        $demo = Apartment::query()->where('slug', 'like', 'demo-%')->orderBy('id')->get();
        $this->assertSame(['demo-l3-1', 'demo-l3-2', 'demo-l3-3'], $demo->pluck('slug')->all());
        $this->assertSame('ready', $demo[1]->status->value);

        /* Список «для себя»: апартаменты L3 есть, у каждого свои фото */
        $cards = $this->getJson('/api/apartments?catalog=living&complex=l3')->assertOk()->json('data');
        $this->assertCount(3, $cards);
        $this->assertStringContainsString('/media/apartments/demo-l3-1/card.jpg', $cards[0]['photos']['card']['url']);

        /* Галереи в разном порядке, все файлы на месте */
        $first = $this->getJson('/api/apartments/demo-l3-1')->assertOk()->json('data.gallery');
        $second = $this->getJson('/api/apartments/demo-l3-2')->json('data.gallery');
        $this->assertCount(4, $first);
        $this->assertNotSame($first[0]['alt'], $second[0]['alt']);
        foreach (Photo::query()->where('path', 'like', 'apartments/demo-%')->get() as $photo) {
            $this->assertTrue(Storage::disk('media')->exists($photo->path), $photo->path);
        }

        $this->artisan('demo:apartments', ['--remove' => true])->expectsOutputToContain('Удалено тестовых апартаментов: 3')->assertSuccessful();
        $this->assertSame(0, Apartment::query()->where('slug', 'like', 'demo-%')->count());
        $this->assertFalse(Storage::disk('media')->exists('apartments/demo-l3-1/card.jpg'));
        $this->assertTrue(Storage::disk('media')->exists('apartments/a4-card.jpg'), 'исходные фото A4 не тронуты');
        $this->assertSame(3, Apartment::query()->count());
    }
}
