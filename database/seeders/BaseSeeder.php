<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Restaurant;
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
       $r = Restaurant::create(['name' => 'Demo Resto']);

        // roles using 'api' guard
        foreach (['Owner','Manager','Cashier','Waiter'] as $role) {
            Role::findOrCreate($role, 'api');
        }

        $owner = User::create([
            'name' => 'Demo Owner',
            'email' => 'owner@demo.com',
            'password' => Hash::make('password'),
            'restaurant_id' => $r->id,
        ]);
        $owner->assignRole('Owner');
    }
}
