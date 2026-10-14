<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /* Заполняет базу тем, что сейчас записано в файлах сайта */
    public function run(): void
    {
        $this->call(SiteContentSeeder::class);
    }
}
