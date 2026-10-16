<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/* Два телефона и ссылки иконок подвала: значения по умолчанию, админка, /api/site (Саша, 16.10) */
class SiteContactsTest extends TestCase
{
    use RefreshDatabase;

    private array $form = [
        'phone' => '+7 (991) 255-29-00',
        'phone_2' => '+7 (991) 587-24-57',
        'social_max' => 'https://max.ru/u/abc',
        'social_telegram' => 'https://t.me/ikan_serbia',
        'social_vk' => 'https://vk.ru/probizvip',
        'notify_emails' => '',
        'telegram_chat_id' => '',
    ];

    public function test_defaults_before_admin_saves(): void
    {
        $this->getJson('/api/site')->assertOk()
            ->assertJsonPath('data.phone.label', '+7 (991) 255-29-00')
            ->assertJsonPath('data.phones.0.href', 'tel:+79912552900')
            ->assertJsonPath('data.phones.1.label', '+7 (991) 587-24-57')
            ->assertJsonPath('data.phones.1.href', 'tel:+79915872457')
            ->assertJsonCount(2, 'data.phones')
            ->assertJsonPath('data.socials.telegram', 'https://t.me/ikan_serbia')
            ->assertJsonPath('data.socials.vk', 'https://vk.ru/probizvip')
            ->assertJsonPath('data.socials.max', fn ($url) => str_starts_with($url, 'https://max.ru/u/'));
    }

    public function test_admin_changes_and_clears(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get('/admin/settings')->assertOk()->assertSee('Второй телефон')->assertSee('+7 (991) 587-24-57')
            ->assertSee('https://t.me/ikan_serbia')->assertSee('ВКонтакте');

        $this->put('/admin/settings', ['phone_2' => 'звоните'] + $this->form)->assertSessionHasErrors('phone_2');
        $this->put('/admin/settings', ['phone_2' => '+7 12'] + $this->form)->assertSessionHasErrors();
        $this->put('/admin/settings', ['social_vk' => 'vk.ru/probizvip'] + $this->form)->assertSessionHasErrors('social_vk');
        $this->put('/admin/settings', ['social_max' => 'javascript:alert(1)'] + $this->form)->assertSessionHasErrors('social_max');

        $this->put('/admin/settings', ['phone_2' => '+381 64 000 11 22', 'social_max' => 'https://max.ru/u/xyz'] + $this->form)
            ->assertRedirect('/admin/settings')->assertSessionHasNoErrors();
        $this->getJson('/api/site')
            ->assertJsonPath('data.phones.1.label', '+381 64 000 11 22')
            ->assertJsonPath('data.phones.1.href', 'tel:+381640001122')
            ->assertJsonPath('data.socials.max', 'https://max.ru/u/xyz');

        $this->put('/admin/settings', ['phone_2' => '', 'social_vk' => ''] + $this->form)->assertSessionHasNoErrors();
        $this->getJson('/api/site')
            ->assertJsonCount(1, 'data.phones')
            ->assertJsonPath('data.socials.vk', null)
            ->assertJsonPath('data.socials.telegram', 'https://t.me/ikan_serbia');
        $this->get('/admin/settings')->assertOk();
    }
}
