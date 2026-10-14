<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Фотографии апартаментов и ресторана. Файлы лежат на диске media
 * (папка public/media), в таблице путь внутри него.
 *
 * role: card (карточка в списке, компьютер), card_mobile (карточка, телефон),
 * gallery (страница объекта, по порядку sort_order), mobile (страница, телефон).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photos', function (Blueprint $table) {
            $table->id();
            $table->morphs('owner');
            $table->string('role', 20);
            $table->string('path');
            $table->string('alt')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['owner_type', 'owner_id', 'role', 'sort_order'], 'photos_owner_role_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photos');
    }
};
