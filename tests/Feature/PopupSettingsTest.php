<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
 * Автоматическое окно заявки (Саша, 21.11): включение и задержка в «Настройках»,
 * отдача сайту через /api/site, заявка из такого окна с источником popup_auto.
 */
class PopupSettingsTest extends TestCase
{
    use RefreshDatabase;

    private array $form = [
        'phone' => '+7 (991) 255-29-00',
        'phone_2' => '',
        'notify_emails' => '',
        'telegram_chat_id' => '',
    ];

    public function test_default_is_on_after_ten_seconds(): void
    {
        $this->getJson('/api/site')->assertOk()
            ->assertJsonPath('data.popup.enabled', true)
            ->assertJsonPath('data.popup.delay', 10);
    }

    public function test_admin_changes_delay_and_turns_off(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get('/admin/settings')->assertOk()->assertSee('name="popup_delay"', false)->assertSee('name="popup_enabled"', false);

        $this->put('/admin/settings', $this->form + ['popup_enabled' => '1', 'popup_delay' => '20'])->assertSessionHasNoErrors();
        $this->getJson('/api/site')->assertJsonPath('data.popup.enabled', true)->assertJsonPath('data.popup.delay', 20);

        /* Галочка снята: выключено, задержка остаётся */
        $this->put('/admin/settings', $this->form + ['popup_delay' => '20'])->assertSessionHasNoErrors();
        $this->getJson('/api/site')->assertJsonPath('data.popup.enabled', false)->assertJsonPath('data.popup.delay', 20);

        /* Не число, ноль, слишком много: ошибка */
        foreach (['0', '601', 'десять', '-5'] as $bad) {
            $this->put('/admin/settings', $this->form + ['popup_enabled' => '1', 'popup_delay' => $bad])->assertSessionHasErrors('popup_delay');
        }
        /* Форма без поля (старый тест или другая форма): задержка по умолчанию */
        $this->put('/admin/settings', $this->form + ['popup_enabled' => '1'])->assertSessionHasNoErrors();
        $this->getJson('/api/site')->assertJsonPath('data.popup.delay', 10);
    }

    public function test_lead_from_auto_popup(): void
    {
        $this->postJson('/api/requests', [
            'name' => 'Анна', 'phone' => '+7 999 123-45-67', 'source' => Lead::SOURCE_AUTO, 'page' => '/dlya-sebya',
            'consent_terms' => true, 'consent_privacy' => true, 'consent_ads' => false,
        ])->assertCreated();
        $lead = Lead::query()->latest('id')->firstOrFail();
        $this->assertSame('popup_auto', $lead->source);
        $this->assertSame('Окно «Оставить заявку», открылось само', $lead->sourceLabel());

        $this->actingAs(User::factory()->create());
        $this->get('/admin/leads')->assertOk()->assertSee('открылось само');
    }
}
