<?php

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
        // Create default admin user
        \App\User::create([
            'username' => 'admin',
            'password' => 'adminpassword',
            'role' => 'admin',
            'features_activated' => false,
        ]);

        // Create default superuser
        \App\User::create([
            'username' => 'superuser',
            'password' => 'superpassword',
            'role' => 'superuser',
            'features_activated' => true,
        ]);

        echo "Default users created:\n";
        echo "  - admin / adminpassword (features_activated: false)\n";
        echo "  - superuser / superpassword (features_activated: true)\n";
    }
}
