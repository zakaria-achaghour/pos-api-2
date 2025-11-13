<?php
// filepath: database/seeders/StaffSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Staff;
use App\Models\Restaurant;
use App\Models\User;
use Spatie\Permission\Models\Role;

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
        // Get assignable roles (exclude Owner and SuperAdmin)
        $assignableRoles = Role::where('guard_name', 'api')
            ->whereNotIn('name', ['Owner', 'SuperAdmin'])
            ->pluck('name')
            ->toArray();

        // Get users for this restaurant with assignable roles
        $users = User::where('restaurant_id', $restaurant->id)
            ->whereHas('roles', function($query) use ($assignableRoles) {
                $query->whereIn('name', $assignableRoles);
            })
            ->get();

        $counter = 1;
        
        // Create staff records for each user (excluding owners)
        foreach ($users as $user) {
            // Determine position based on role
            $role = $user->roles->first()?->name;
            $position = match($role) {
                'Manager' => 'Manager',
                'Kitchen' => fake()->randomElement(['Head Chef', 'Sous Chef', 'Line Cook']),
                'Waiter' => fake()->randomElement(['Server', 'Bartender']),
                'Cashier' => 'Cashier',
                default => 'General Staff'
            };
            
            $hourlyRate = match($role) {
                'Manager' => fake()->randomFloat(2, 22, 28),
                'Kitchen' => fake()->randomFloat(2, 15, 22),
                'Waiter' => fake()->randomFloat(2, 12, 16),
                'Cashier' => fake()->randomFloat(2, 13, 17),
                default => 10.00
            };

            Staff::create([
                'restaurant_id' => $restaurant->id,
                'user_id' => $user->id,
                'employee_id' => $restaurant->subdomain . '-' . sprintf('%03d', $counter),
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'email' => $user->email,
                'phone' => fake()->phoneNumber(),
                'position' => $position,
                'department' => $this->getDepartment($position),
                'hourly_rate' => $hourlyRate,
                'hire_date' => fake()->dateTimeBetween('-2 years', '-1 month'),
                'status' => 'active',
                'emergency_contact_name' => fake()->name(),
                'emergency_contact_phone' => fake()->phoneNumber(),
            ]);
            
            $counter++;
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