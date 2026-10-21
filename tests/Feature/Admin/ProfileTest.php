<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/* Профиль пользователя админки (21.10) */
class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create(['name' => 'Администратор', 'email' => 'email@ik-ikan.ru', 'password' => 'old-password-1']);
    }

    public function test_guest_is_sent_to_login(): void
    {
        $this->get('/admin/profile')->assertRedirect('/admin/login');
        $this->get('/admin/profile/edit')->assertRedirect('/admin/login');
        $this->put('/admin/profile', [])->assertRedirect('/admin/login');
    }

    public function test_menu_and_pages(): void
    {
        $this->actingAs($this->user());
        $this->get('/admin')->assertOk()->assertSee('Мой профиль')->assertSee('Настройки профиля')->assertSee('Выйти');
        $this->get('/admin/profile')->assertOk()->assertSee('Администратор')->assertSee('email@ik-ikan.ru')->assertDontSee('old-password-1');
        $this->get('/admin/profile/edit')->assertOk()->assertSee('value="email@ik-ikan.ru"', false)->assertSee('Текущий пароль');
    }

    public function test_name_without_password(): void
    {
        $user = $this->user();
        $this->actingAs($user)->put('/admin/profile', ['name' => 'Саша', 'email' => 'email@ik-ikan.ru'])
            ->assertRedirect('/admin/profile')->assertSessionHas('success', 'Профиль сохранён.');
        $this->assertSame('Саша', $user->fresh()->name);
    }

    public function test_email_and_password_need_current_password(): void
    {
        $user = $this->user();
        $this->actingAs($user);
        $base = ['name' => 'Администратор', 'email' => 'email@ik-ikan.ru'];

        $this->put('/admin/profile', ['email' => 'new@ik-ikan.ru'] + $base)->assertSessionHasErrors('current_password');
        $this->put('/admin/profile', ['email' => 'new@ik-ikan.ru', 'current_password' => 'wrong'] + $base)->assertSessionHasErrors('current_password');
        $this->put('/admin/profile', ['password' => 'new-password-1', 'password_confirmation' => 'other-pass-1', 'current_password' => 'old-password-1'] + $base)->assertSessionHasErrors('password');
        $this->put('/admin/profile', ['password' => 'short', 'password_confirmation' => 'short', 'current_password' => 'old-password-1'] + $base)->assertSessionHasErrors('password');
        $this->assertSame('email@ik-ikan.ru', $user->fresh()->email);
        $this->assertTrue(Hash::check('old-password-1', $user->fresh()->password));

        $this->put('/admin/profile', [
            'name' => 'Администратор', 'email' => ' New@IK-ikan.ru ',
            'password' => 'new-password-1', 'password_confirmation' => 'new-password-1', 'current_password' => 'old-password-1',
        ])->assertRedirect('/admin/profile')->assertSessionHas('success', fn ($m) => str_contains($m, 'new@ik-ikan.ru') && str_contains($m, 'Пароль изменён'));

        $fresh = $user->fresh();
        $this->assertSame('new@ik-ikan.ru', $fresh->email);
        $this->assertTrue(Hash::check('new-password-1', $fresh->password));

        // вход с новыми данными работает, со старыми нет
        Auth::logout();
        $this->post('/admin/login', ['email' => 'email@ik-ikan.ru', 'password' => 'old-password-1'])->assertSessionHasErrors('email');
        $this->post('/admin/login', ['email' => 'new@ik-ikan.ru', 'password' => 'new-password-1'])->assertRedirect('/admin');
    }

    public function test_email_must_be_free(): void
    {
        User::factory()->create(['email' => 'other@ik-ikan.ru']);
        $this->actingAs($this->user())->put('/admin/profile', ['name' => 'А', 'email' => 'other@ik-ikan.ru', 'current_password' => 'old-password-1'])
            ->assertSessionHasErrors(['email' => 'Эта почта уже занята другим пользователем админки.']);
    }
}
