<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* Жилые комплексы (ЖК): вкладки и подписи групп в списках квартир на сайте */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complexes', function (Blueprint $table) {
            $table->id();
            /* Короткое имя для адресов и API: l1, l2 */
            $table->string('slug')->unique();
            /* Название на сайте: L1, L2 */
            $table->string('name');
            /* Порядок вкладок, меньше раньше */
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complexes');
    }
};
