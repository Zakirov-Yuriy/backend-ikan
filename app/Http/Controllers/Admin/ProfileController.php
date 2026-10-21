<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/*
 * Профиль пользователя админки (Анна, 21.10): экран «Мой профиль» и правка
 * имени, почты для входа и пароля. Почту и пароль можно сменить только
 * с текущим паролем, имя без него.
 */
class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        return view('admin.profile.show', ['user' => $request->user()]);
    }

    public function edit(Request $request): View
    {
        return view('admin.profile.edit', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);
        $emailChanged = $request->input('email') !== $user->email;
        $passwordChanged = filled($request->input('password'));

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'max:100', 'confirmed'],
            'current_password' => [Rule::requiredIf($emailChanged || $passwordChanged), 'nullable', 'current_password'],
        ], [
            'name.required' => 'Укажите имя.',
            'email.required' => 'Укажите почту.',
            'email.email' => 'Почта указана неверно.',
            'email.unique' => 'Эта почта уже занята другим пользователем админки.',
            'password.min' => 'Новый пароль должен быть не короче 8 знаков.',
            'password.confirmed' => 'Новый пароль и повтор не совпадают.',
            'current_password.required' => 'Чтобы сменить почту или пароль, введите текущий пароль.',
            'current_password.current_password' => 'Текущий пароль неверный.',
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];
        if ($passwordChanged) {
            $user->password = $data['password'];
            // «Запомнить меня» на других устройствах перестаёт действовать
            $user->setRememberToken(Str::random(60));
        }
        $user->save();

        $message = 'Профиль сохранён.';
        if ($emailChanged) {
            $message .= ' Теперь вход по почте '.$user->email.'.';
        }
        if ($passwordChanged) {
            $message .= ' Пароль изменён.';
        }

        return redirect()->route('admin.profile.show')->with('success', $message);
    }
}
