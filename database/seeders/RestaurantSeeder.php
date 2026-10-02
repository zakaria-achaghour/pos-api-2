<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RestaurantSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            GoldenForkRestaurantSeeder::class,
            BellaVistaRestaurantSeeder::class,
            SakuraSushiRestaurantSeeder::class,
            CafeLumiereRestaurantSeeder::class,
            AtlasKitchenRestaurantSeeder::class,
        ]);
    }
}
