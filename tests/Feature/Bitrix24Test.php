<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/* Заявки в Битрикс24 через входящий вебхук (21.10). Ответы Битрикс24 подставлены */
class Bitrix24Test extends TestCase
{
    use RefreshDatabase;

    private const HOOK = 'https://ikan.bitrix24.ru/rest/1/secret123';

    private array $payload = [
        'name' => 'Анна',
        'phone' => '+7 999 000-11-22',
        'interest' => 'Студия для себя',
        'source' => 'popup',
        'page' => '/apartamenty/a4',
        'consent_terms' => true,
        'consent_privacy' => true,
        'consent_ads' => true,
    ];

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function fakeBitrix(): void
    {
        Http::fake([
            'ikan.bitrix24.ru/rest/1/secret123/crm.lead.add.json' => Http::response(['result' => 101]),
            'ikan.bitrix24.ru/rest/1/secret123/crm.contact.add.json' => Http::response(['result' => 55]),
            'ikan.bitrix24.ru/rest/1/secret123/crm.deal.add.json' => Http::response(['result' => 202]),
            'ikan.bitrix24.ru/rest/1/secret123/crm.settings.mode.get.json' => Http::response(['result' => 2]),
            '*' => Http::response(['ok' => true]),
        ]);
    }

    public function test_without_webhook_nothing_is_sent(): void
    {
        Http::fake();
        $this->postJson('/api/requests', $this->payload)->assertCreated();
        Http::assertNothingSent();
    }

    public function test_lead_with_all_fields(): void
    {
        $this->fakeBitrix();
        Setting::set(Setting::BITRIX_WEBHOOK, self::HOOK.'/');
        Setting::set(Setting::BITRIX_ASSIGNED, '7');

        $this->postJson('/api/requests', $this->payload)->assertCreated();

        Http::assertSent(function (Request $request) {
            if ($request->url() !== self::HOOK.'/crm.lead.add.json') {
                return false;
            }
            $f = $request['fields'];

            return $f['TITLE'] === 'Заявка с сайта ИКАН: Анна'
                && $f['NAME'] === 'Анна'
                && $f['PHONE'] === [['VALUE' => '+7 999 000-11-22', 'VALUE_TYPE' => 'WORK']]
                && $f['SOURCE_ID'] === 'WEB'
                && $f['ASSIGNED_BY_ID'] === 7
                && str_contains($f['COMMENTS'], 'Студия для себя')
                && str_contains($f['COMMENTS'], '/apartamenty/a4')
                && str_contains($f['COMMENTS'], 'Реклама: согласен');
        });
        Http::assertNotSent(fn (Request $r) => str_contains($r->url(), 'crm.deal.add'));
    }

    public function test_deal_and_contact(): void
    {
        $this->fakeBitrix();
        Setting::set(Setting::BITRIX_WEBHOOK, self::HOOK);
        Setting::set(Setting::BITRIX_ENTITY, Setting::BITRIX_DEAL);
        Setting::set(Setting::BITRIX_CATEGORY, '3');
        Setting::set(Setting::BITRIX_STAGE, 'C3:NEW');

        $this->postJson('/api/requests', [...$this->payload, 'consent_ads' => false])->assertCreated();

        Http::assertSent(fn (Request $r) => $r->url() === self::HOOK.'/crm.contact.add.json' && $r['fields']['NAME'] === 'Анна');
        Http::assertSent(function (Request $r) {
            if ($r->url() !== self::HOOK.'/crm.deal.add.json') {
                return false;
            }
            $f = $r['fields'];

            return $f['CONTACT_ID'] === 55 && $f['CATEGORY_ID'] === 3 && $f['STAGE_ID'] === 'C3:NEW'
                && ! isset($f['ASSIGNED_BY_ID']) && str_contains($f['COMMENTS'], 'Реклама: без согласия');
        });
        Http::assertNotSent(fn (Request $r) => str_contains($r->url(), 'crm.lead.add'));
    }

    public function test_bitrix_error_keeps_the_lead(): void
    {
        Http::fake(['*' => Http::response(['error' => 'INVALID_CREDENTIALS', 'error_description' => 'Invalid request credentials'], 401)]);
        Setting::set(Setting::BITRIX_WEBHOOK, self::HOOK);

        $this->postJson('/api/requests', $this->payload)->assertCreated();
        $this->assertSame(1, Lead::query()->count());
    }

    public function test_webhook_from_env_when_admin_is_empty(): void
    {
        $this->fakeBitrix();
        config(['services.bitrix24.webhook' => self::HOOK]);

        $this->postJson('/api/requests', $this->payload)->assertCreated();
        Http::assertSent(fn (Request $r) => $r->url() === self::HOOK.'/crm.lead.add.json');
    }

    public function test_admin_settings_check_and_test(): void
    {
        $this->fakeBitrix();
        $this->actingAs(User::factory()->create());
        $this->get('/admin/settings')->assertOk()->assertSee('Битрикс24: заявки в CRM')->assertSee('Проверить Битрикс24');

        $form = ['phone' => '+7 (991) 255-29-00', 'notify_emails' => '', 'telegram_chat_id' => '', 'bitrix_entity' => 'lead'];
        $this->put('/admin/settings', $form + ['bitrix_webhook' => 'http://ikan.bitrix24.ru/rest/1/abc/'])->assertSessionHasErrors('bitrix_webhook');
        $this->put('/admin/settings', $form + ['bitrix_webhook' => 'https://ikan.bitrix24.ru/crm/'])->assertSessionHasErrors('bitrix_webhook');
        $this->put('/admin/settings', $form + ['bitrix_webhook' => self::HOOK.'/', 'bitrix_stage' => 'новый'])->assertSessionHasErrors('bitrix_stage');
        $this->put('/admin/settings', $form + ['bitrix_webhook' => self::HOOK.'/', 'bitrix_assigned' => '7'])->assertSessionHasNoErrors();
        $this->assertSame(self::HOOK.'/', Setting::get(Setting::BITRIX_WEBHOOK));
        $this->assertSame(7, Setting::bitrixAssigned());

        $this->post('/admin/settings/bitrix-check')->assertRedirect('/admin/settings')
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'связь есть') && str_contains($m, 'простой'));
        $this->post('/admin/settings/test')->assertSessionHas('success', fn ($m) => str_contains($m, 'Битрикс24: создан лид №101'));
        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'crm.lead.add') && str_starts_with($r['fields']['TITLE'], 'Проверка из админки'));
    }
}
