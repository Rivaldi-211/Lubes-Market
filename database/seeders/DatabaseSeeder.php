<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ZonaPengirimanSeeder::class,
            OpsiPackingSeeder::class,
            BumdesDemoSeeder::class,
            RekeningBankSeeder::class,
        ]);
    }
}
