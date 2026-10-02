<?php

namespace Database\Seeders;

class AtlasKitchenRestaurantSeeder extends CompleteRestaurantSeeder
{
    protected function definition(): array
    {
        return [
            'slug' => 'atlas-kitchen',
            'name' => 'Atlas Kitchen',
            'city' => 'Fes',
            'address' => '5 Avenue Hassan II',
            'cuisine' => 'Moroccan',
            'menu' => [
                'Starters' => [
                    ['Zaalouk', 30],
                    ['Harira Soup', 25],
                    ['Taktouka', 30],
                ],
                'Tagines' => [
                    ['Chicken Lemon Tagine', 80],
                    ['Beef Prune Tagine', 100],
                    ['Vegetable Tagine', 65],
                ],
                'Traditional Dishes' => [
                    ['Friday Couscous', 85],
                    ['Chicken Pastilla', 90],
                    ['Kefta Brochettes', 80],
                ],
                'Desserts and Drinks' => [
                    ['Mint Tea', 20],
                    ['Orange Cinnamon Salad', 30],
                    ['Almond Briouat', 35],
                ],
            ],
        ];
    }
}
