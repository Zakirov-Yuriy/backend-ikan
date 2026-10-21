<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Просмотры страниц сайта для раздела «Статистика» (21.10). Без cookie и без
 * адреса посетителя: visitor это обезличенный код, он меняется каждый день.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_views', function (Blueprint $table) {
            $table->id();
            $table->date('day')->index();
            $table->string('visitor', 16);
            $table->string('path', 255);
            $table->string('screen', 30)->index();
            $table->foreignId('apartment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('from_screen', 30)->nullable();
            $table->string('referrer_host', 100)->nullable();
            $table->string('device', 10);
            $table->timestamp('created_at')->nullable();
            $table->index(['day', 'visitor']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_views');
    }
};
