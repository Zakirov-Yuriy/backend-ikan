<?php

namespace Tests\Feature\Admin;

use App\Models\Apartment;
use App\Models\Complex;
use App\Models\Photo;
use App\Models\User;
use Database\Seeders\SiteContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/* Админка: вход, ЖК, апартаменты с фотографиями, порядок */
class AdminTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SiteContentSeeder::class);
        $this->user = User::factory()->create(['email' => 'admin@example.com']);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/apartments')->assertRedirect('/admin/login');
        $this->get('/')->assertRedirect('/admin');
        $this->get('/admin/login')->assertOk()->assertSee('Войти');
    }

    public function test_login_and_logout(): void
    {
        $this->post('/admin/login', ['email' => 'admin@example.com', 'password' => 'wrong'])
            ->assertRedirect()
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post('/admin/login', ['email' => 'admin@example.com', 'password' => 'password'])
            ->assertRedirect('/admin');
        $this->assertAuthenticatedAs($this->user);

        $this->get('/admin')->assertOk()->assertSee('Жилые комплексы')->assertSee('Апартамент A4');

        $this->post('/admin/logout')->assertRedirect('/admin/login');
        $this->assertGuest();
    }

    public function test_admin_user_command_creates_and_updates(): void
    {
        $this->artisan('admin:user', ['email' => 'anna@example.com'])
            ->expectsQuestion('Пароль (не меньше 8 знаков)', 'secret-pass-1')
            ->expectsQuestion('Повторите пароль', 'secret-pass-1')
            ->assertSuccessful();
        $this->assertDatabaseHas('users', ['email' => 'anna@example.com', 'name' => 'Администратор']);

        $this->artisan('admin:user', ['email' => 'anna@example.com'])
            ->expectsQuestion('Пароль (не меньше 8 знаков)', 'short')
            ->assertFailed();
        $this->artisan('admin:user', ['email' => 'anna@example.com'])
            ->expectsQuestion('Пароль (не меньше 8 знаков)', 'another-pass-2')
            ->expectsQuestion('Повторите пароль', 'mismatch-pass')
            ->assertFailed();
        $this->artisan('admin:user', ['email' => 'not-an-email'])->assertFailed();
        $this->artisan('admin:user', ['email' => 'anna@example.com', '--password' => 'from-option-3'])->assertSuccessful();
        $this->artisan('admin:user', ['email' => 'anna@example.com', '--password' => 'short'])->assertFailed();
        $this->post('/admin/login', ['email' => 'anna@example.com', 'password' => 'from-option-3'])->assertRedirect('/admin');
    }

    public function test_complex_crud_and_sort(): void
    {
        $this->actingAs($this->user);
        $this->get('/admin/complexes')->assertOk()->assertSeeInOrder(['L1', 'L2', 'L3', 'L4']);

        $this->post('/admin/complexes', ['name' => 'L5', 'slug' => ''])->assertRedirect('/admin/complexes');
        $l5 = Complex::query()->where('slug', 'l5')->firstOrFail();
        $this->assertSame(5, $l5->sort_order);

        $this->post('/admin/complexes', ['name' => 'Дубль', 'slug' => 'l5'])->assertSessionHasErrors('slug');
        $this->post('/admin/complexes', ['name' => '', 'slug' => 'x'])->assertSessionHasErrors('name');

        $this->put("/admin/complexes/{$l5->id}", ['name' => 'L5 новый', 'slug' => 'l5'])->assertRedirect('/admin/complexes');
        $this->assertSame('L5 новый', $l5->fresh()->name);

        $l1 = Complex::query()->where('slug', 'l1')->firstOrFail();
        $this->delete("/admin/complexes/{$l1->id}")->assertSessionHasErrors();
        $this->assertDatabaseHas('complexes', ['slug' => 'l1']);

        $this->post('/admin/complexes/sort', ['order' => [$l5->id, $l1->id]])->assertRedirect();
        $this->assertSame(1, $l5->fresh()->sort_order);
        $this->assertSame(2, $l1->fresh()->sort_order);
        $this->getJson('/api/complexes')->assertJsonPath('data.0.slug', 'l5');

        $this->delete("/admin/complexes/{$l5->id}")->assertRedirect('/admin/complexes');
        $this->assertDatabaseMissing('complexes', ['slug' => 'l5']);
    }

    public function test_apartment_create_update_photos_and_delete(): void
    {
        $this->actingAs($this->user);
        $l2 = Complex::query()->where('slug', 'l2')->firstOrFail();

        $this->get('/admin/apartments')->assertOk()->assertSeeInOrder(['Апартамент A4', 'Апартамент A6', 'Апартамент A10']);
        $this->get('/admin/apartments?complex='.$l2->id)->assertOk()->assertSee('Апартамент A10')->assertDontSee('Апартамент A4');
        $this->get('/admin/apartments?search=A6')->assertOk()->assertSee('Апартамент A6')->assertDontSee('Апартамент A10');
        $this->get('/admin/apartments/create?complex='.$l2->id)->assertOk()->assertSee('Новый апартамент');

        $response = $this->post('/admin/apartments', [
            'name' => 'Апартамент A7',
            'slug' => '',
            'complex_id' => $l2->id,
            'status' => 'ready',
            'show_living' => '1',
            'show_newbuilds' => '1',
            'floor' => '3-й этаж',
            'type' => 'Двухкомнатный',
            'area' => '52.5',
            'price' => '120000',
            'features_text' => "Кухня\n\n  Балкон \n",
            'data_items' => [['label' => 'НДС', 'value' => 'Включён'], ['label' => '', 'value' => '']],
            'photo_card' => UploadedFile::fake()->image('card.jpg', 800, 600),
            'gallery' => [UploadedFile::fake()->image('g1.jpg', 800, 600), UploadedFile::fake()->image('g2.png', 800, 600)],
        ]);
        $apartment = Apartment::query()->where('slug', 'apartament-a7')->firstOrFail();
        $response->assertRedirect("/admin/apartments/{$apartment->id}/edit");

        $this->assertSame('ready', $apartment->status->value);
        $this->assertTrue($apartment->show_living);
        $this->assertFalse($apartment->show_renting);
        $this->assertSame(['Кухня', 'Балкон'], $apartment->features);
        $this->assertSame([['label' => 'НДС', 'value' => 'Включён']], $apartment->data_items);
        $this->assertSame(4, $apartment->sort_order);
        $this->assertCount(3, $apartment->photos);
        $card = $apartment->photo(Photo::ROLE_CARD);
        $this->assertStringStartsWith('apartments/apartament-a7/card-', $card->path);
        $this->assertTrue(Storage::disk('media')->exists($card->path));

        $gallery = $apartment->photos->where('role', Photo::ROLE_GALLERY)->values();
        $this->assertSame(['g1', 'g2'], $gallery->map(fn ($p) => pathinfo($p->path, PATHINFO_EXTENSION) === 'jpg' ? 'g1' : 'g2')->all());

        $this->getJson('/api/apartments/apartament-a7')
            ->assertOk()
            ->assertJsonPath('data.complex.slug', 'l2')
            ->assertJsonPath('data.area', 52.5)
            ->assertJsonCount(2, 'data.gallery');

        /* Правка: новый адрес, порядок галереи, подпись, удаление фото карточки, замена */
        $this->put("/admin/apartments/{$apartment->id}", [
            'name' => 'Апартамент A7',
            'slug' => 'a7',
            'complex_id' => $l2->id,
            'status' => 'building',
            'show_renting' => '1',
            'photo_order' => [$gallery[1]->id, $gallery[0]->id],
            'photo_alt' => [$gallery[1]->id => 'Гостиная'],
            'delete_photo' => [$card->id],
            'photo_mobile' => UploadedFile::fake()->image('m.webp', 400, 600),
        ])->assertRedirect("/admin/apartments/{$apartment->id}/edit");

        $apartment = $apartment->fresh();
        $this->assertSame('a7', $apartment->slug);
        $this->assertFalse(Storage::disk('media')->exists($card->path));
        $this->assertFalse(Storage::disk('media')->exists('apartments/apartament-a7/'.basename($gallery[0]->path)));
        $data = $this->getJson('/api/apartments/a7')->assertOk()->json('data');
        $this->assertNull($data['photos']['card']);
        $this->assertSame(['Гостиная', ''], array_column($data['gallery'], 'alt'));
        $this->assertStringContainsString('/media/apartments/a7/gallery-', $data['gallery'][0]['url']);
        $this->assertStringContainsString('/media/apartments/a7/mobile-', $data['photo_mobile']['url']);
        $this->assertTrue(Storage::disk('media')->exists(Photo::query()->where('role', Photo::ROLE_GALLERY)->where('owner_id', $apartment->id)->first()->path));

        /* Ошибки формы */
        $this->put("/admin/apartments/{$apartment->id}", ['name' => '', 'slug' => 'a4', 'complex_id' => 999, 'status' => 'nope'])
            ->assertSessionHasErrors(['name', 'slug', 'complex_id', 'status']);
        $this->post('/admin/apartments', ['name' => 'X', 'complex_id' => $l2->id, 'status' => 'ready', 'photo_card' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf')])
            ->assertSessionHasErrors('photo_card');

        /* Порядок и удаление */
        $a4 = Apartment::query()->where('slug', 'a4')->firstOrFail();
        $this->post('/admin/apartments/sort', ['order' => [$apartment->id, $a4->id]])->assertRedirect();
        $this->assertSame(1, $apartment->fresh()->sort_order);
        $this->getJson('/api/apartments')->assertJsonPath('data.0.slug', 'a7');

        $paths = $apartment->photos()->pluck('path');
        $this->delete("/admin/apartments/{$apartment->id}")->assertRedirect('/admin/apartments');
        $this->assertDatabaseMissing('apartments', ['slug' => 'a7']);
        foreach ($paths as $path) {
            $this->assertFalse(Storage::disk('media')->exists($path));
        }
        $this->assertFalse(Storage::disk('media')->exists('apartments/a7'));
        $this->getJson('/api/apartments/a7')->assertNotFound();
    }

    public function test_validation_messages_are_russian(): void
    {
        $this->actingAs($this->user);
        $this->from('/admin/complexes/create')->post('/admin/complexes', ['name' => ''])
            ->assertRedirect('/admin/complexes/create');
        $this->get('/admin/complexes/create')->assertSee('Поле «название» обязательно');
    }
}
