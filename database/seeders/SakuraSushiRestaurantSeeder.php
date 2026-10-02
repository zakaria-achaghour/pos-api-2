<?php

namespace Database\Seeders;

class SakuraSushiRestaurantSeeder extends CompleteRestaurantSeeder
{
    protected function definition(): array
    {
        return [
            'slug' => 'sakura-sushi',
            'name' => 'Sakura Sushi Bar',
            'city' => 'Tangier',
            'address' => '8 Avenue Mohammed VI',
            'cuisine' => 'Japanese',
            'menu' => [
                'Starters' => [
                    ['Miso Soup', 30],
                    ['Edamame', 35],
                    ['Vegetable Gyoza', 45],
                ],
                'Sushi' => [
                    ['Salmon Nigiri', 65],
                    ['California Roll', 70],
                    ['Avocado Maki', 50],
                ],
                'Hot Dishes' => [
                    ['Chicken Teriyaki', 90],
                    ['Vegetable Ramen', 80],
                    ['Shrimp Tempura', 95],
                ],
                'Desserts and Drinks' => [
                    ['Mochi', 40],
                    ['Matcha Tea', 30],
                    ['Yuzu Lemonade', 35],
                ],
            ],
        ];
    }
}
