<?php

namespace Database\Seeders;

use App\Models\MenuCategory;
use App\Models\MenuItem;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Restaurant;
use App\Models\Table;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class BaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
      // 1. Restaurant
        $restaurant = Restaurant::create([
            'name'    => 'Demo Resto',
            'address' => '123 Main Street',
            'phone'   => '123-456-7890',
        ]);

        // 2. Roles (guard "api")
        foreach (['Owner','Manager','Cashier','Waiter'] as $role) {
            Role::findOrCreate($role, 'api');
        }

        // 3. Owner user
        $owner = User::create([
            'name'         => 'Demo Owner',
            'email'        => 'owner@demo.com',
            'password'     => Hash::make('password'),
            'restaurant_id'=> $restaurant->id,
        ]);
        $owner->assignRole('Owner');

        // 4. Tables
        Table::create([
            'restaurant_id' => $restaurant->id,
            'name'          => 'Table 1',
            'capacity'      => 4,
        ]);
        Table::create([
            'restaurant_id' => $restaurant->id,
            'name'          => 'Table 2',
            'capacity'      => 2,
        ]);

        // 5. Menu categories
        $drinks = MenuCategory::create([
            'restaurant_id' => $restaurant->id,
            'name'          => 'Drinks',
            'description'   => 'Cold and hot beverages',
        ]);
        $food = MenuCategory::create([
            'restaurant_id' => $restaurant->id,
            'name'          => 'Food',
            'description'   => 'Main dishes',
        ]);

        // 6. Menu items
        MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'category_id'   => $drinks->id,
            'name'          => 'Coffee',
            'price'         => 12.50,
            'is_active'     => true,
        ]);
        MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'category_id'   => $drinks->id,
            'name'          => 'Tea',
            'price'         => 10.00,
            'is_active'     => true,
        ]);
        MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'category_id'   => $food->id,
            'name'          => 'Burger',
            'price'         => 45.00,
            'is_active'     => true,
        ]);
    }
}
