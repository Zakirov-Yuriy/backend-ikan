<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

/*
 * Создаёт или обновляет пользователя админки.
 * php artisan admin:user zakcoyote@gmail.com
 * Пароль спрашивается в терминале и на экране не показывается.
 */
class AdminUser extends Command
{
    protected $signature = 'admin:user {email : Почта для входа} {--name= : Имя, по умолчанию «Администратор»} {--password= : Пароль, если скрытый ввод в терминале не работает}';

    protected $description = 'Создать пользователя админки или сменить ему пароль';

    public function handle(): int
    {
        $email = mb_strtolower(trim($this->argument('email')));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Это не похоже на почту: '.$email);

            return self::FAILURE;
        }

        $password = $this->option('password');
        if ($password === null) {
            $password = $this->secret('Пароль (не меньше 8 знаков)');
            if (mb_strlen((string) $password) < 8) {
                $this->error('Пароль короче 8 знаков, ничего не сохранено.');

                return self::FAILURE;
            }
            if ($this->secret('Повторите пароль') !== $password) {
                $this->error('Пароли не совпали, ничего не сохранено.');

                return self::FAILURE;
            }
        } elseif (mb_strlen($password) < 8) {
            $this->error('Пароль короче 8 знаков, ничего не сохранено.');

            return self::FAILURE;
        }

        $user = User::query()->firstOrNew(['email' => $email]);
        $created = ! $user->exists;
        $user->name = $this->option('name') ?: ($user->name ?: 'Администратор');
        $user->password = Hash::make($password);
        $user->save();

        $this->info($created ? "Пользователь {$email} создан." : "Пароль пользователя {$email} обновлён.");

        return self::SUCCESS;
    }
}
