<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        /* Постраничная навигация в админке в стиле Bootstrap 5, как шаблон */
        Paginator::useBootstrapFive();
    }
}
