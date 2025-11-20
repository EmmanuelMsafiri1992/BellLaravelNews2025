<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create(); // Uncomment if you want to seed default users as well

        // Seed users first
        $this->call(AdminUserSeeder::class);

        // Seed screen templates
        $this->call(ScreenTemplatesSeeder::class);
    }
}
