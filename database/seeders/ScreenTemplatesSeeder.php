<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Screen;
use Illuminate\Support\Facades\DB;

class ScreenTemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates 10 default screen templates with the same format as the main screen
     */
    public function run(): void
    {
        $screens = [
            ['name' => 'Main Hall Screen', 'location' => 'Main Entrance Hall', 'status' => 'active'],
            ['name' => 'Cafeteria Screen', 'location' => 'School Cafeteria', 'status' => 'active'],
            ['name' => 'Library Screen', 'location' => 'Main Library', 'status' => 'active'],
            ['name' => 'Admin Office Screen', 'location' => 'Administrative Office', 'status' => 'active'],
            ['name' => 'Gymnasium Screen', 'location' => 'Sports Gymnasium', 'status' => 'active'],
            ['name' => 'Science Lab Screen', 'location' => 'Science Laboratory', 'status' => 'active'],
            ['name' => 'Auditorium Screen', 'location' => 'Main Auditorium', 'status' => 'active'],
            ['name' => 'Teachers Lounge Screen', 'location' => 'Teachers Lounge', 'status' => 'active'],
            ['name' => 'North Wing Screen', 'location' => 'North Wing Corridor', 'status' => 'active'],
            ['name' => 'South Wing Screen', 'location' => 'South Wing Corridor', 'status' => 'active'],
        ];

        foreach ($screens as $screenData) {
            // Check if screen with this name already exists
            $existing = Screen::where('name', $screenData['name'])->first();

            if (!$existing) {
                // Generate unique code
                $uniqueCode = Screen::generateUniqueCode($screenData['name']);

                Screen::create([
                    'name' => $screenData['name'],
                    'location' => $screenData['location'],
                    'unique_code' => $uniqueCode,
                    'status' => $screenData['status'],
                ]);

                $this->command->info("Created screen: {$screenData['name']} with code: {$uniqueCode}");
            } else {
                $this->command->info("Screen '{$screenData['name']}' already exists. Skipping...");
            }
        }

        $this->command->info('');
        $this->command->info('✓ Screen templates seeder completed!');
        $this->command->info('All screens use the same format as the main screen at http://10.46.211.253:8000/');
        $this->command->info('Access individual screens at: /display/{unique_code}');
    }
}
