<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* Заявки с форм сайта: «Заказать обратный звонок» в подвале и окно «Оставить заявку» */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('phone', 50);
            $table->text('interest')->nullable();      /* «Что вас интересует», только в окне */
            $table->string('source', 20);              /* callback (подвал) или popup (окно) */
            $table->string('page')->nullable();        /* адрес страницы сайта, с которой отправлено */
            $table->boolean('consent_terms')->default(false);
            $table->boolean('consent_privacy')->default(false);
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('status', 20)->default('new'); /* new или done */
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
