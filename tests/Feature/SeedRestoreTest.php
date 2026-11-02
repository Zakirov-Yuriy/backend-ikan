<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\HomePage;
use App\Models\HomeRow;
use App\Models\Photo;
use App\Models\Restaurant;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/*
 * Случай с прода 02.11: при обновлении пропала папка public/media, а потом
 * photos:broken --delete удалил все записи о фото. Повторный db:seed должен
 * вернуть файлы и записи, не трогая тексты, которые правили в админке.
 */
class SeedRestoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_restores_missing_photos_and_files_keeping_edited_texts(): void
    {
        Storage::fake('media');
        $this->seed(DatabaseSeeder::class);
        $this->assertTrue(Storage::disk('media')->exists('home/scenario-01.jpg'));
        $before = Photo::query()->count();
        $this->assertGreaterThan(20, $before);

        /* Правки из админки, которые должны пережить повторный сид */
        $row = HomeRow::query()->ofKind(HomeRow::KIND_SCENARIO)->orderBy('sort_order')->first();
        $row->update(['title' => 'Своё жильё в Сербии']);
        HomePage::query()->first()->update(['hero_title' => 'Другой заголовок']);

        /* Беда: папка пропала, записи удалены */
        Storage::fake('media');
        Photo::query()->delete();
        $this->assertSame(0, Photo::query()->count());

        $this->seed(DatabaseSeeder::class);

        $this->assertSame($before, Photo::query()->count());
        $this->assertTrue(Storage::disk('media')->exists('home/scenario-01.jpg'));
        $this->assertTrue(Storage::disk('media')->exists('apartments/a4-card.jpg'));
        $this->assertSame(7, Apartment::query()->where('slug', 'a4')->first()->photos()->count());
        $this->assertTrue(Restaurant::current()->photos()->where('role', Photo::ROLE_GALLERY)->exists());
        $this->assertTrue(HomePage::query()->first()->photos()->where('role', Photo::ROLE_CONSULTATION)->exists());
        $this->assertSame(2, $row->fresh()->photos()->count(), 'фото и широкий кадр первой строки вернулись');
        $this->assertSame('Своё жильё в Сербии', $row->fresh()->title);
        $this->assertSame('Другой заголовок', HomePage::query()->first()->hero_title);

        /* Ещё один запуск ничего не дублирует */
        $this->seed(DatabaseSeeder::class);
        $this->assertSame($before, Photo::query()->count());
    }

    public function test_photos_broken_refuses_to_delete_when_folder_is_empty(): void
    {
        Storage::fake('media');
        $this->seed(DatabaseSeeder::class);
        $count = Photo::query()->count();

        Storage::fake('media');
        $this->artisan('photos:broken --delete')
            ->expectsOutputToContain('Папка public/media пуста')
            ->assertFailed();
        $this->assertSame($count, Photo::query()->count(), 'записи целы');
    }
}
