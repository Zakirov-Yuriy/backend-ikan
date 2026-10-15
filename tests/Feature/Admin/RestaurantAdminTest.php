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

/* Админка: страница «Ресторан» */
class RestaurantAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private function upload(string $name): UploadedFile
    {
        $extension = pathinfo($name, PATHINFO_EXTENSION);
        $copy = tempnam(sys_get_temp_dir(), 'upl');
        copy(base_path('tests/Fixtures/photo.'.$extension), $copy);

        return new UploadedFile($copy, $name, mime_content_type($copy) ?: null, null, true);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SiteContentSeeder::class);
        $this->user = User::factory()->create();
    }

    public function test_form_shows_current_values(): void
    {
        $this->actingAs($this->user);
        $this->get('/admin/restaurant')
            ->assertOk()
            ->assertSee('Арендуйте ресторан')
            ->assertSee('Ресторанный бизнес')
            ->assertSee('restaurant/photo-01.jpg')
            ->assertSee('Открыть на сайте');
        $this->get('/admin')->assertOk()->assertSee('Ресторан');
    }

    public function test_update_texts_pairs_and_photos(): void
    {
        $this->actingAs($this->user);
        $restaurant = Restaurant::current();
        $gallery = $restaurant->photos->where('role', Photo::ROLE_GALLERY)->values();
        $mobile = $restaurant->photo(Photo::ROLE_MOBILE);

        $this->put('/admin/restaurant', [
            'title' => 'Ресторан в аренду',
            'name' => 'Ресторан',
            'lead' => 'Новый подзаголовок',
            'status' => 'Готов',
            'area' => '120.5',
            'price_text' => '',
            'info_floor' => 'Приземье',
            'features' => [['label' => 'Назначение', 'value' => 'Кафе'], ['label' => '', 'value' => '']],
            'data_items' => [['label' => 'Формат', 'value' => 'Аренда']],
            'photo_order' => [$gallery[3]->id, $gallery[0]->id, $gallery[1]->id, $gallery[2]->id],
            'photo_alt' => [$gallery[3]->id => 'Зал'],
            'delete_photo' => [$gallery[2]->id],
            'photo_mobile' => $this->upload('m.jpg'),
            'gallery' => [$this->upload('new.png')],
        ])->assertRedirect('/admin/restaurant')->assertSessionHasNoErrors();

        $data = $this->getJson('/api/restaurant')->assertOk()->json('data');
        $this->assertSame('Ресторан в аренду', $data['title']);
        $this->assertSame('Новый подзаголовок', $data['lead']);
        $this->assertSame(120.5, $data['area']);
        $this->assertNull($data['price_text']);
        $this->assertSame([['label' => 'Назначение', 'value' => 'Кафе']], $data['about']['features']);
        $this->assertSame([['label' => 'Формат', 'value' => 'Аренда']], $data['data_items']);
        $this->assertCount(4, $data['gallery']);
        $this->assertSame('Зал', $data['gallery'][0]['alt']);
        $this->assertStringContainsString('restaurant/photo-04.jpg', $data['gallery'][0]['url']);
        $this->assertStringContainsString('/media/restaurant/gallery-', $data['gallery'][3]['url']);
        $this->assertStringEndsWith('.png', $data['gallery'][3]['url']);
        $this->assertStringContainsString('/media/restaurant/mobile-', $data['photo_mobile']['url']);
        $this->assertFalse(Storage::disk('media')->exists($mobile->path));
        $this->assertFalse(Storage::disk('media')->exists($gallery[2]->path));

        $this->put('/admin/restaurant', ['title' => '', 'name' => 'x'])->assertSessionHasErrors('title');
    }

    public function test_form_without_record_creates_it(): void
    {
        $this->actingAs($this->user);
        Restaurant::query()->delete();
        $this->get('/admin/restaurant')->assertOk()->assertSee('Записи ресторана ещё нет');
        $this->getJson('/api/restaurant')->assertNotFound();
        $this->put('/admin/restaurant', ['title' => 'Арендуйте ресторан', 'name' => 'Ресторан'])->assertRedirect('/admin/restaurant');
        $this->getJson('/api/restaurant')->assertOk()->assertJsonPath('data.title', 'Арендуйте ресторан');
    }
}
