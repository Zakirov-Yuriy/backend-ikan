<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/* Яндекс Метрика (02.11): номер счётчика и вебвизор в «Настройках», отдача сайту через /api/site */
class MetrikaSettingsTest extends TestCase
{
    use RefreshDatabase;

    private array $form = [
        'phone' => '+7 (991) 255-29-00',
        'phone_2' => '',
        'notify_emails' => '',
        'telegram_chat_id' => '',
    ];

    public function test_no_counter_by_default(): void
    {
        $this->getJson('/api/site')->assertOk()
            ->assertJsonPath('data.metrika.id', null)
            ->assertJsonPath('data.metrika.webvisor', false);
    }

    public function test_admin_sets_counter_and_webvisor(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get('/admin/settings')->assertOk()->assertSee('Яндекс Метрика')->assertSee('request_open');

        $this->put('/admin/settings', $this->form + ['metrika_id' => '12345678', 'metrika_webvisor' => '1'])
            ->assertRedirect('/admin/settings')->assertSessionHasNoErrors();
        $this->getJson('/api/site')
            ->assertJsonPath('data.metrika.id', 12345678)
            ->assertJsonPath('data.metrika.webvisor', true);
        $this->get('/admin/settings')->assertSee('value="12345678"', false);

        /* Не число: ошибка с подсказкой */
        $this->put('/admin/settings', $this->form + ['metrika_id' => 'ym(123)'])
            ->assertSessionHasErrors('metrika_id');

        /* Очистили: счётчика нет, вебвизор выключен */
        $this->put('/admin/settings', $this->form + ['metrika_id' => ''])->assertSessionHasNoErrors();
        $this->getJson('/api/site')
            ->assertJsonPath('data.metrika.id', null)
            ->assertJsonPath('data.metrika.webvisor', false);
    }
}
