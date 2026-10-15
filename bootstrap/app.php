<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        /* Без входа любая страница админки ведёт на форму входа */
        $middleware->redirectGuestsTo('/admin/login');
        $middleware->redirectUsersTo('/admin');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        /* Слишком большая отправка (лимит post_max_size в PHP): назад к форме с текстом */
        $exceptions->render(function (PostTooLargeException $e, Request $request) {
            if ($request->is('api/*')) {
                return null;
            }

            return back()->withErrors('Файлы слишком большие для одной отправки (сервер принимает до 8 МБ за раз). Выберите меньше файлов и повторите.');
        });
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
