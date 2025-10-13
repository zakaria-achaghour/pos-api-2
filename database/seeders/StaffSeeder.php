<?php
// filepath: database/seeders/StaffSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Staff;
use App\Models\Restaurant;

class StaffSeeder extends Seeder
{
    public function run(): void
    {
        $restaurants = Restaurant::all();

        foreach ($restaurants as $restaurant) {
            $this->createStaffForRestaurant($restaurant);
        }
    }

    private function createStaffForRestaurant(Restaurant $restaurant): void
    {
        $positions = [
            ['position' => 'Manager', 'count' => 1, 'hourly_rate' => 25.00],
            ['position' => 'Head Chef', 'count' => 1, 'hourly_rate' => 22.00],
            ['position' => 'Sous Chef', 'count' => 1, 'hourly_rate' => 18.00],
            ['position' => 'Line Cook', 'count' => 2, 'hourly_rate' => 15.00],
            ['position' => 'Server', 'count' => 4, 'hourly_rate' => 12.00],
            ['position' => 'Bartender', 'count' => 2, 'hourly_rate' => 14.00],
            ['position' => 'Host/Hostess', 'count' => 2, 'hourly_rate' => 11.00],
            ['position' => 'Busser', 'count' => 2, 'hourly_rate' => 10.00],
        ];

        $counter = 1;
        foreach ($positions as $positionData) {
            for ($i = 1; $i <= $positionData['count']; $i++) {
                Staff::create([
                    'restaurant_id' => $restaurant->id,
                    'employee_id' => $restaurant->subdomain . '-' . sprintf('%03d', $counter),
                    'first_name' => fake()->firstName(),
                    'last_name' => fake()->lastName(),
                    'email' => fake()->unique()->safeEmail(),
                    'phone' => fake()->phoneNumber(),
                    'position' => $positionData['position'],
                    'department' => $this->getDepartment($positionData['position']),
                    'hourly_rate' => $positionData['hourly_rate'],
                    'hire_date' => fake()->dateTimeBetween('-2 years', '-1 month'),
                    'status' => 'active',
                    'emergency_contact_name' => fake()->name(),
                    'emergency_contact_phone' => fake()->phoneNumber(),
                ]);
                $counter++;
            }
        }
    }

    private function getDepartment(string $position): string
    {
        return match($position) {
            'Manager' => 'Management',
            'Head Chef', 'Sous Chef', 'Line Cook' => 'Kitchen',
            'Server', 'Bartender' => 'Service',
            'Host/Hostess', 'Busser' => 'Front of House',
            default => 'General',
        };
    }
}