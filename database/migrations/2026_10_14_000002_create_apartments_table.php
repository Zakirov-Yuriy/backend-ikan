<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Апартаменты. Поля собраны со страниц сайта: карточка в списках, страница
 * апартамента (верх, таблица «Информация», блоки «Об объекте» и «Данные»).
 * Фотографии в таблице photos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apartments', function (Blueprint $table) {
            $table->id();
            /* Последняя часть адреса страницы: /apartamenty/a4 */
            $table->string('slug')->unique();
            /* «Апартамент A4» */
            $table->string('name');
            $table->foreignId('complex_id')->constrained()->restrictOnDelete();
            /* building или ready, см. App\Enums\ApartmentStatus */
            $table->string('status', 20)->default('building');

            /* В каких списках сайта показывать карточку */
            $table->boolean('show_living')->default(true);
            $table->boolean('show_renting')->default(true);
            $table->boolean('show_newbuilds')->default(true);

            /* Карточка и верх страницы */
            $table->string('city')->nullable();        /* Златибор */
            $table->string('floor')->nullable();       /* Приземье, 1-й этаж */
            $table->string('type')->nullable();        /* Студия, Однокомнатный */
            $table->decimal('area', 8, 2)->nullable(); /* м² */
            $table->unsignedInteger('price')->nullable(); /* евро, без копеек */
            $table->text('card_text')->nullable();     /* текст в карточке списка */

            /* Страница апартамента */
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->text('description')->nullable();       /* под заголовком, компьютер */
            $table->text('description_short')->nullable(); /* то же для телефона */
            $table->text('info_note')->nullable();         /* примечание под таблицей «Информация» */
            $table->string('about_title')->nullable();     /* блок «Об объекте» */
            $table->text('about_text')->nullable();
            $table->json('features')->nullable();          /* список характеристик, строки */
            $table->json('data_items')->nullable();        /* блок «Данные»: [{label, value}] */
            $table->string('consultation_title')->nullable();

            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['complex_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apartments');
    }
};
