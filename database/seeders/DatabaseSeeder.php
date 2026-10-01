<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Master seeder — runs all seeders in dependency order.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RbacSeeder::class,
            UserLevelsSeeder::class,
            SettingsSeeder::class,
            AdminSeeder::class,
        ]);
    }
}
