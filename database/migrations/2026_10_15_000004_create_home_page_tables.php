<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Тексты главной страницы и вступления страниц списков, которые правят в
 * админке. Главная: одна запись home_pages с текстами блоков, строки
 * направлений и карточки «Наши направления» в home_rows, «Актуальные
 * объекты» это апартаменты с галочкой show_home. Списки: pages по ключу
 * living, renting, newbuilds.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_pages', function (Blueprint $table) {
            $table->id();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();

            /* Первый экран */
            $table->string('hero_eyebrow')->nullable();
            $table->string('hero_title');
            $table->string('hero_title_accent')->nullable();
            $table->text('hero_lead')->nullable();
            $table->string('hero_primary_label', 100);
            $table->string('hero_secondary_label', 100);
            $table->string('hero_side_label')->nullable();
            $table->json('hero_side_items')->nullable();

            /* «О компании» */
            $table->string('about_eyebrow')->nullable();
            $table->string('about_title')->nullable();
            $table->text('about_text')->nullable();
            $table->string('about_features_label')->nullable();
            $table->json('about_features')->nullable();

            /* «Наши направления»: подпись над карточками */
            $table->string('directions_label')->nullable();

            /* «Актуальные объекты» */
            $table->string('objects_eyebrow')->nullable();
            $table->string('objects_title')->nullable();
            $table->string('objects_all_label', 100)->nullable();
            $table->string('objects_all_href')->nullable();
            $table->string('objects_more_label', 100)->nullable();

            /* «Персональный подбор» */
            $table->string('consultation_eyebrow')->nullable();
            $table->string('consultation_title')->nullable();
            $table->text('consultation_text')->nullable();
            $table->string('consultation_button_label', 100)->nullable();

            $table->timestamps();
        });

        /* Строки направлений (scenario) и карточки «Наши направления» (direction) */
        Schema::create('home_rows', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 20)->index();
            $table->string('title');
            $table->text('text')->nullable();
            $table->string('link_label', 150)->nullable();
            $table->string('link_href')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique();
            $table->string('title');
            $table->text('lead')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->string('consultation_title')->nullable();
            $table->string('consultation_title_mobile')->nullable();
            $table->timestamps();
        });

        Schema::table('apartments', function (Blueprint $table) {
            $table->boolean('show_home')->default(false)->after('show_newbuilds');
        });
    }

    public function down(): void
    {
        Schema::table('apartments', function (Blueprint $table) {
            $table->dropColumn('show_home');
        });
        Schema::dropIfExists('pages');
        Schema::dropIfExists('home_rows');
        Schema::dropIfExists('home_pages');
    }
};
