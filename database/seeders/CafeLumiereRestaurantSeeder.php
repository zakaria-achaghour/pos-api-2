<?php

namespace Database\Seeders;

class CafeLumiereRestaurantSeeder extends CompleteRestaurantSeeder
{
    protected function definition(): array
    {
        return [
            'slug' => 'cafe-lumiere',
            'name' => 'Café Lumière',
            'city' => 'Marrakech',
            'address' => '16 Rue de la Liberté',
            'cuisine' => 'French',
            'menu' => [
                'Breakfast' => [
                    ['Butter Croissant', 20],
                    ['Omelette Toast', 45],
                    ['Granola Bowl', 40],
                ],
                'Lunch' => [
                    ['Croque Monsieur', 60],
                    ['Quiche Lorraine', 55],
                    ['Nicoise Salad', 65],
                ],
                'Patisserie' => [
                    ['Lemon Tart', 35],
                    ['Chocolate Eclair', 30],
                    ['Almond Financier', 25],
                ],
                'Coffee and Tea' => [
                    ['Cappuccino', 28],
                    ['Cafe Latte', 30],
                    ['Earl Grey Tea', 25],
                ],
            ],
        ];
    }
}
