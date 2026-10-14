<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Страница «Ресторан». Объект один, строка в таблице одна; таблица нужна,
 * чтобы менять страницу из админки теми же средствами, что апартаменты.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurants', function (Blueprint $table) {
            $table->id();
            $table->string('title');            /* «Арендуйте ресторан» */
            $table->text('lead')->nullable();   /* подзаголовок */
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();

            /* Верх страницы */
            $table->string('status')->nullable();      /* «Готов» */
            $table->string('name');                    /* «Ресторан», заголовок карточки */
            $table->string('city')->nullable();
            $table->string('floor')->nullable();
            $table->string('type')->nullable();        /* «Ресторан» */
            $table->decimal('area', 8, 2)->nullable(); /* м² */
            $table->string('price_text')->nullable();  /* «Узнать», «По запросу» */
            $table->text('description')->nullable();

            /* Таблица «Информация о ресторане» */
            $table->string('info_type')->nullable();     /* «Коммерческое помещение» */
            $table->string('info_purpose')->nullable();  /* «Ресторанный бизнес» */
            $table->string('info_price')->nullable();    /* «По запросу» */
            $table->string('info_floor')->nullable();    /* «Уточняется» */
            $table->text('info_note')->nullable();

            /* Блоки «Об объекте» и «Данные» */
            $table->string('about_title')->nullable();
            $table->text('about_text')->nullable();
            $table->json('features')->nullable();   /* [{label, value}] */
            $table->json('data_items')->nullable(); /* [{label, value}] */
            $table->string('consultation_title')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurants');
    }
};
