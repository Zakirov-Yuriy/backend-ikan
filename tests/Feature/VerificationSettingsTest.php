<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\SettingsController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
 * Подтверждение сайта в Яндекс Вебмастере и Google Search Console (05.11):
 * коды в «Настройках», отдача сайту через /api/site. Саша вставляет строку
 * <meta ...> целиком или только код, сайт ставит мета-тег на все страницы.
 */
class VerificationSettingsTest extends TestCase
{
    use RefreshDatabase;

    private array $form = [
        'phone' => '+7 (991) 255-29-00',
        'phone_2' => '',
        'notify_emails' => '',
        'telegram_chat_id' => '',
    ];

    public function test_no_codes_by_default(): void
    {
        $this->getJson('/api/site')->assertOk()
            ->assertJsonPath('data.verification.yandex', null)
            ->assertJsonPath('data.verification.google', null);
    }

    public function test_whole_meta_line_is_reduced_to_the_code(): void
    {
        $this->assertSame('1234567890abcdef', SettingsController::verificationCode('<meta name="yandex-verification" content="1234567890abcdef" />'));
        $this->assertSame('1234567890abcdef', SettingsController::verificationCode("  <meta name='yandex-verification' content='1234567890abcdef'>\n"));
        $this->assertSame('Ab-9_x.Y', SettingsController::verificationCode('<meta name="google-site-verification" content="Ab-9_x.Y">'));
        $this->assertSame('1234567890abcdef', SettingsController::verificationCode('1234567890abcdef'));
        $this->assertSame('', SettingsController::verificationCode('   '));

        /* Не код: текст с пробелами, кириллица, слишком короткая строка, скрипт Метрики */
        $this->assertNull(SettingsController::verificationCode('подтверждение сайта'));
        $this->assertNull(SettingsController::verificationCode('abc'));
        $this->assertNull(SettingsController::verificationCode('<script>ym(123)</script>'));
    }

    public function test_admin_sets_codes_and_site_gets_them(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get('/admin/settings')->assertOk()->assertSee('Яндекс Вебмастер')->assertSee('Google Search Console');

        $this->put('/admin/settings', $this->form + [
            'yandex_verification' => '<meta name="yandex-verification" content="1234567890abcdef" />',
            'google_verification' => 'G-code_0123',
        ])->assertRedirect('/admin/settings')->assertSessionHasNoErrors();

        $this->getJson('/api/site')
            ->assertJsonPath('data.verification.yandex', '1234567890abcdef')
            ->assertJsonPath('data.verification.google', 'G-code_0123');
        /* В форме остаётся сам код, а не вставленная строка */
        $this->get('/admin/settings')->assertSee('value="1234567890abcdef"', false);

        /* Не код: ошибка с подсказкой, старое значение не трогаем */
        $this->put('/admin/settings', $this->form + ['yandex_verification' => 'какой-то текст'])
            ->assertSessionHasErrors();
        $this->getJson('/api/site')->assertJsonPath('data.verification.yandex', '1234567890abcdef');

        /* Очистили: тегов нет */
        $this->put('/admin/settings', $this->form + ['yandex_verification' => '', 'google_verification' => ''])
            ->assertSessionHasNoErrors();
        $this->getJson('/api/site')
            ->assertJsonPath('data.verification.yandex', null)
            ->assertJsonPath('data.verification.google', null);
    }
}
