<?php
// filepath: database/seeders/UserSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Restaurant;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $restaurants = Restaurant::all();

        // Create SuperAdmin (no restaurant association)
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@pos.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'restaurant_id' => null,
        ]);
        $superAdmin->assignRole('SuperAdmin');

        // Create users for each restaurant
        foreach ($restaurants as $restaurant) {
            $this->createUsersForRestaurant($restaurant);
        }
    }

    private function createUsersForRestaurant(Restaurant $restaurant): void
    {
        $restaurantSlug = str_replace('-', '', $restaurant->subdomain);
        
        // Owner
        $owner = User::create([
            'name' => "Owner of {$restaurant->name}",
            'email' => "owner@{$restaurant->subdomain}.com",
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'restaurant_id' => $restaurant->id,
        ]);
        $owner->assignRole('Owner');

        // Manager
        $manager = User::create([
            'name' => "Manager of {$restaurant->name}",
            'email' => "manager@{$restaurant->subdomain}.com",
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'restaurant_id' => $restaurant->id,
        ]);
        $manager->assignRole('Manager');

        // Cashiers
        for ($i = 1; $i <= 2; $i++) {
            $cashier = User::create([
                'name' => "Cashier {$i} - {$restaurant->name}",
                'email' => "cashier{$i}@{$restaurant->subdomain}.com",
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'restaurant_id' => $restaurant->id,
            ]);
            $cashier->assignRole('Cashier');
        }

        // Waiters
        for ($i = 1; $i <= 3; $i++) {
            $waiter = User::create([
                'name' => "Waiter {$i} - {$restaurant->name}",
                'email' => "waiter{$i}@{$restaurant->subdomain}.com",
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'restaurant_id' => $restaurant->id,
            ]);
            $waiter->assignRole('Waiter');
        }

        // Kitchen Staff
        for ($i = 1; $i <= 2; $i++) {
            $kitchen = User::create([
                'name' => "Chef {$i} - {$restaurant->name}",
                'email' => "chef{$i}@{$restaurant->subdomain}.com",
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'restaurant_id' => $restaurant->id,
            ]);
            $kitchen->assignRole('Kitchen');
        }
    }
}