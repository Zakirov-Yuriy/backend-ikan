<?php

namespace Tests\Feature;

use App\Mail\LeadMail;
use App\Models\Lead;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/* Заявки: приём с сайта, уведомления, список и настройки в админке */
class LeadsTest extends TestCase
{
    use RefreshDatabase;

    private array $payload = [
        'name' => 'Анна',
        'phone' => '+7 (999) 000-11-22',
        'interest' => 'Квартира',
        'source' => 'popup',
        'page' => '/dlya-sebya',
        'consent_terms' => true,
        'consent_privacy' => true,
    ];

    public function test_api_accepts_lead_and_notifies(): void
    {
        Mail::fake();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        Setting::set(Setting::NOTIFY_EMAILS, 'a@example.com, b@example.com');
        Setting::set(Setting::TELEGRAM_CHAT_ID, '123');
        config(['services.telegram.token' => 'token']);

        $this->postJson('/api/requests', $this->payload, ['X-Forwarded-For' => '10.1.2.3', 'User-Agent' => 'Test'])
            ->assertCreated()
            ->assertJsonStructure(['data' => ['id']]);

        $lead = Lead::query()->firstOrFail();
        $this->assertSame('Анна', $lead->name);
        $this->assertSame('popup', $lead->source);
        $this->assertSame('/dlya-sebya', $lead->page);
        $this->assertSame('10.1.2.3', $lead->ip);
        $this->assertTrue($lead->isNew());

        Mail::assertSent(LeadMail::class, fn (LeadMail $mail) => $mail->hasTo('a@example.com') && $mail->hasTo('b@example.com') && $mail->lead->is($lead));
        Http::assertSent(fn ($request) => str_contains($request->url(), 'bottoken/sendMessage') && $request['chat_id'] === '123' && str_contains($request['text'], 'Анна'));
    }

    public function test_api_validation_honeypot_and_throttle(): void
    {
        Mail::fake();
        $this->postJson('/api/requests', ['name' => '', 'phone' => 'abc', 'source' => 'x'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'phone', 'source', 'consent_terms', 'consent_privacy']);
        $this->postJson('/api/requests', [...$this->payload, 'consent_privacy' => false])->assertUnprocessable();

        $this->postJson('/api/requests', [...$this->payload, 'website' => 'spam'])->assertCreated();
        $this->assertDatabaseCount('leads', 0);

        /* Без адресов и чата заявка всё равно сохраняется */
        $this->postJson('/api/requests', $this->payload)->assertCreated();
        $this->assertDatabaseCount('leads', 1);
        Mail::assertNothingSent();

        /* Лимит считается по адресу посетителя из X-Forwarded-For */
        for ($i = 0; $i < 20; $i++) {
            $this->postJson('/api/requests', $this->payload, ['X-Forwarded-For' => '10.0.0.9'])->assertCreated();
        }
        $this->postJson('/api/requests', $this->payload, ['X-Forwarded-For' => '10.0.0.9'])->assertStatus(429);
        $this->postJson('/api/requests', $this->payload, ['X-Forwarded-For' => '10.0.0.10'])->assertCreated();
    }

    public function test_mail_failure_does_not_block_lead(): void
    {
        Setting::set(Setting::NOTIFY_EMAILS, 'a@example.com');
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => '127.0.0.1', 'mail.mailers.smtp.port' => 1]);
        $this->postJson('/api/requests', $this->payload)->assertCreated();
        $this->assertDatabaseCount('leads', 1);
    }

    /* Число цифр в номере по стране (16.10): Россия, СНГ, Сербия; слишком короткий и бесконечный нет */
    public function test_phone_length_by_country(): void
    {
        Mail::fake();
        foreach (['+7 999 123-45-67', '8 999 123 45 67', '9991234567', '+381 64 123 45 67', '+381 11 123 4567', '+375 29 123 45 67', '+998 90 123 45 67', '+7 701 123 45 67'] as $i => $phone) {
            $this->postJson('/api/requests', [...$this->payload, 'name' => "Номер {$i}", 'phone' => $phone])->assertCreated();
        }
        foreach (['+7 999 123', '+7 999 123 45 678', '+381 6', '12345', str_repeat('9', 40)] as $phone) {
            $this->postJson('/api/requests', [...$this->payload, 'phone' => $phone])->assertStatus(422)
                ->assertJsonValidationErrors(['phone' => 'Проверьте номер телефона']);
        }
    }

    /* Три галочки (16.10): соглашение и политика, персональные данные обязательны, реклама по желанию */
    public function test_consents_and_ads(): void
    {
        Mail::fake();
        Setting::set(Setting::NOTIFY_EMAILS, 'a@example.com');
        $this->postJson('/api/requests', $this->payload)->assertCreated();
        $this->assertFalse(Lead::query()->latest('id')->first()->consent_ads);

        $this->postJson('/api/requests', [...$this->payload, 'name' => 'Реклама', 'consent_ads' => true])->assertCreated();
        $this->assertTrue(Lead::query()->where('name', 'Реклама')->first()->consent_ads);

        $without = $this->payload;
        unset($without['consent_privacy']);
        $this->postJson('/api/requests', $without)->assertStatus(422)->assertJsonValidationErrors('consent_privacy');
        $this->postJson('/api/requests', [...$this->payload, 'consent_terms' => false])->assertStatus(422)->assertJsonValidationErrors('consent_terms');
        $this->postJson('/api/requests', [...$this->payload, 'consent_ads' => 'abc'])->assertStatus(422)->assertJsonValidationErrors('consent_ads');

        $this->actingAs(User::factory()->create());
        $this->get('/admin/leads')->assertOk()->assertSee('Реклама')->assertSee('согласен');
        Mail::assertSent(LeadMail::class, fn ($mail) => str_contains($mail->render(), 'согласен на рекламные'));
    }

    public function test_admin_list_toggle_delete_and_settings(): void
    {
        Mail::fake();
        $this->actingAs(User::factory()->create());
        Lead::create([...$this->payload, 'status' => 'new']);
        Lead::create([...$this->payload, 'name' => 'Борис', 'source' => 'callback', 'status' => 'done']);

        $this->get('/admin/leads')->assertOk()->assertSee('Анна')->assertSee('Борис')->assertSee('новых: 1');
        $this->get('/admin/leads?status=new')->assertOk()->assertSee('Анна')->assertDontSee('Борис');
        $this->get('/admin')->assertOk()->assertSee('Новые заявки');

        $lead = Lead::query()->where('name', 'Анна')->firstOrFail();
        $this->post("/admin/leads/{$lead->id}/toggle")->assertRedirect();
        $this->assertSame('done', $lead->fresh()->status);
        $this->delete("/admin/leads/{$lead->id}")->assertRedirect('/admin/leads');
        $this->assertDatabaseMissing('leads', ['id' => $lead->id]);

        $this->get('/admin/settings')->assertOk()->assertSee('Почта для уведомлений')->assertSee('Телефон на сайте')->assertSee('+7 (991) 255-29-00');
        $phone = ['phone' => '+7 (991) 255-29-00'];
        $this->put('/admin/settings', $phone + ['notify_emails' => 'x@example.com, bad', 'telegram_chat_id' => ''])->assertSessionHasErrors();
        $this->put('/admin/settings', $phone + ['notify_emails' => 'x@example.com; y@example.com', 'telegram_chat_id' => 'abc'])->assertSessionHasErrors('telegram_chat_id');
        $this->put('/admin/settings', ['phone' => 'позвоните', 'notify_emails' => '', 'telegram_chat_id' => ''])->assertSessionHasErrors('phone');
        $this->put('/admin/settings', ['phone' => '+7 12', 'notify_emails' => '', 'telegram_chat_id' => ''])->assertSessionHasErrors();
        $this->put('/admin/settings', $phone + ['notify_emails' => 'x@example.com; y@example.com', 'telegram_chat_id' => '-100123'])->assertRedirect('/admin/settings');
        $this->assertSame(['x@example.com', 'y@example.com'], Setting::notifyEmails());
        $this->assertSame('-100123', Setting::get(Setting::TELEGRAM_CHAT_ID));

        /* Телефон уходит на сайт через /api/site: как написан и как ссылка */
        $this->getJson('/api/site')->assertOk()->assertJsonPath('data.phone.label', '+7 (991) 255-29-00')->assertJsonPath('data.phone.href', 'tel:+79912552900');
        $this->put('/admin/settings', ['phone' => '+381 64 123 45 67', 'notify_emails' => '', 'telegram_chat_id' => ''])->assertRedirect('/admin/settings');
        $this->getJson('/api/site')->assertJsonPath('data.phone.label', '+381 64 123 45 67')->assertJsonPath('data.phone.href', 'tel:+381641234567');
        $this->put('/admin/settings', $phone + ['notify_emails' => 'x@example.com; y@example.com', 'telegram_chat_id' => '-100123']);

        $this->post('/admin/settings/test')->assertRedirect('/admin/settings')->assertSessionHas('success');
        Mail::assertSent(LeadMail::class, fn (LeadMail $mail) => $mail->test === true);

        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => [['message' => ['chat' => ['id' => 42, 'first_name' => 'Анна', 'username' => 'anna']]]]])]);
        config(['services.telegram.token' => 'token']);
        $this->post('/admin/settings/telegram-chats')->assertRedirect('/admin/settings')->assertSessionHas('telegram_chats');
        $this->assertSame([['id' => 42, 'title' => 'Анна (@anna)']], session('telegram_chats'));
    }
}
