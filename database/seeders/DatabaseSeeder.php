<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();

        // Uncomment to seed demo environment for DCMS/Gambling Commission demos
        // $this->call(DemoEnvironmentSeeder::class);

        // Uncomment to seed data from CSV files
        // $this->call(CsvDataSeeder::class);
    }
}
