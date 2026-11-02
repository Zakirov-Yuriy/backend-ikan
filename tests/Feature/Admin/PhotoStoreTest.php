<?php

namespace Tests\Feature\Admin;

use App\Models\Photo;
use App\Models\Restaurant;
use App\Models\User;
use Database\Seeders\SiteContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/*
 * Фото не записалось на диск (02.11): на проде папка public/media была без
 * прав на запись, и в базе появлялись записи с путём «0». Теперь форма
 * показывает ошибку, а команда photos:broken находит и удаляет старые
 * битые записи.
 */
class PhotoStoreTest extends TestCase
{
    use RefreshDatabase;

    private function upload(string $name): UploadedFile
    {
        $copy = tempnam(sys_get_temp_dir(), 'upl');
        copy(base_path('tests/Fixtures/photo.jpg'), $copy);

        return new UploadedFile($copy, $name, 'image/jpeg', null, true);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SiteContentSeeder::class);
        $this->actingAs(User::factory()->create());
    }

    public function test_unwritable_media_folder_gives_error_instead_of_broken_record(): void
    {
        Storage::fake('media');
        /* Файл с именем папки ресторана: создать папку и записать в неё нельзя */
        Storage::disk('media')->put('restaurant', 'not a folder');
        $before = Photo::query()->count();

        $this->from('/admin/restaurant')->put('/admin/restaurant', [
            'title' => 'Арендуйте ресторан',
            'name' => 'Ресторан',
            'lead' => 'Тексты сохраняются',
            'gallery' => [$this->upload('zal.jpg')],
        ])->assertRedirect('/admin/restaurant')
            ->assertSessionHasErrors();

        $errors = session('errors')->all();
        $this->assertStringContainsString('не удалось сохранить на сервере', $errors[0]);
        $this->assertStringContainsString('public/media/restaurant', $errors[0]);
        $this->assertSame($before, Photo::query()->count(), 'записи с пустым путём быть не должно');
        $this->assertSame('Тексты сохраняются', Restaurant::current()?->lead);
    }

    public function test_photos_broken_command_lists_and_deletes(): void
    {
        Storage::fake('media');
        $restaurant = Restaurant::current();
        $ok = $restaurant->photos()->create(['role' => Photo::ROLE_GALLERY, 'path' => 'restaurant/ok.jpg', 'sort_order' => 1]);
        Storage::disk('media')->put('restaurant/ok.jpg', 'jpg');
        $zero = $restaurant->photos()->create(['role' => Photo::ROLE_MOBILE, 'path' => '0', 'sort_order' => 1]);
        $missing = $restaurant->photos()->create(['role' => Photo::ROLE_GALLERY, 'path' => 'restaurant/gone.jpg', 'sort_order' => 2]);

        $this->artisan('photos:broken')
            ->expectsOutputToContain('restaurant/gone.jpg')
            ->expectsOutputToContain('--delete')
            ->assertSuccessful();
        $this->assertTrue(Photo::query()->whereKey($zero->id)->exists(), 'без --delete ничего не удаляется');

        $this->artisan('photos:broken --delete')
            ->expectsOutputToContain('Удалено записей: 2')
            ->assertSuccessful();
        $this->assertTrue(Photo::query()->whereKey($ok->id)->exists());
        $this->assertFalse(Photo::query()->whereKey($zero->id)->exists());
        $this->assertFalse(Photo::query()->whereKey($missing->id)->exists());

        $this->artisan('photos:broken')->expectsOutputToContain('Битых фотографий нет')->assertSuccessful();
    }
}
