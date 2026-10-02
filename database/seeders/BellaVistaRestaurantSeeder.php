<?php

namespace Database\Seeders;

class BellaVistaRestaurantSeeder extends CompleteRestaurantSeeder
{
    protected function definition(): array
    {
        return [
            'slug' => 'bella-vista',
            'name' => 'Bella Vista Pizzeria',
            'city' => 'Rabat',
            'address' => '24 Avenue Fal Ould Oumeir',
            'cuisine' => 'Italian',
            'menu' => [
                'Antipasti' => [
                    ['Bruschetta', 35],
                    ['Caprese Salad', 45],
                    ['Arancini', 40],
                ],
                'Pizza' => [
                    ['Margherita Pizza', 65],
                    ['Four Cheese Pizza', 85],
                    ['Vegetarian Pizza', 75],
                ],
                'Pasta' => [
                    ['Penne Arrabbiata', 65],
                    ['Pesto Linguine', 75],
                    ['Beef Lasagna', 90],
                ],
                'Desserts and Drinks' => [
                    ['Tiramisu', 40],
                    ['Espresso', 18],
                    ['Italian Lemon Soda', 25],
                ],
            ],
        ];
    }
}
