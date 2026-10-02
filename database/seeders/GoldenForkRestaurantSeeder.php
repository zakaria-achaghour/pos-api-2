<?php

namespace Database\Seeders;

class GoldenForkRestaurantSeeder extends CompleteRestaurantSeeder
{
    protected function definition(): array
    {
        return [
            'slug' => 'golden-fork',
            'name' => 'The Golden Fork',
            'city' => 'Casablanca',
            'address' => '12 Boulevard Zerktouni',
            'cuisine' => 'International',
            'menu' => [
                'Starters' => [
                    ['Garden Salad', 35],
                    ['Tomato Soup', 30],
                    ['Garlic Bread', 25],
                ],
                'Main Courses' => [
                    ['Grilled Chicken', 85],
                    ['Classic Burger', 70],
                    ['Mushroom Pasta', 75],
                ],
                'Desserts' => [
                    ['Chocolate Cake', 35],
                    ['Fruit Salad', 30],
                    ['Vanilla Ice Cream', 25],
                ],
                'Drinks' => [
                    ['Fresh Orange Juice', 25],
                    ['Lemonade', 20],
                    ['Mineral Water', 15],
                ],
            ],
        ];
    }
}
