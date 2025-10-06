<?php
// filepath: database/seeders/CategorySeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Restaurant;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $restaurants = Restaurant::all();

        foreach ($restaurants as $restaurant) {
            $this->createCategoriesForRestaurant($restaurant);
        }
    }

    private function createCategoriesForRestaurant(Restaurant $restaurant): void
    {
        $categories = $this->getCategoriesForRestaurant($restaurant);

        foreach ($categories as $categoryData) {
            Category::create([
                'restaurant_id' => $restaurant->id,
                'name' => $categoryData['name'],
                'description' => $categoryData['description'],
                'sort_order' => $categoryData['sort_order'],
                'is_active' => true,
            ]);
        }
    }

    private function getCategoriesForRestaurant(Restaurant $restaurant): array
    {
        return match($restaurant->subdomain) {
            'golden-fork' => [
                ['name' => 'Appetizers', 'description' => 'Start your meal right', 'sort_order' => 1],
                ['name' => 'Soups & Salads', 'description' => 'Fresh and healthy options', 'sort_order' => 2],
                ['name' => 'Main Courses', 'description' => 'Our signature dishes', 'sort_order' => 3],
                ['name' => 'Steaks & Grills', 'description' => 'Premium cuts and grills', 'sort_order' => 4],
                ['name' => 'Desserts', 'description' => 'Sweet endings', 'sort_order' => 5],
                ['name' => 'Beverages', 'description' => 'Drinks and refreshments', 'sort_order' => 6],
            ],
            'bella-vista' => [
                ['name' => 'Antipasti', 'description' => 'Traditional Italian starters', 'sort_order' => 1],
                ['name' => 'Pizza', 'description' => 'Wood-fired traditional pizzas', 'sort_order' => 2],
                ['name' => 'Pasta', 'description' => 'Fresh homemade pasta', 'sort_order' => 3],
                ['name' => 'Risotto', 'description' => 'Creamy Italian rice dishes', 'sort_order' => 4],
                ['name' => 'Dolci', 'description' => 'Italian desserts', 'sort_order' => 5],
                ['name' => 'Beverages', 'description' => 'Italian wines and drinks', 'sort_order' => 6],
            ],
            'sakura-sushi' => [
                ['name' => 'Appetizers', 'description' => 'Japanese starters', 'sort_order' => 1],
                ['name' => 'Sashimi', 'description' => 'Fresh raw fish', 'sort_order' => 2],
                ['name' => 'Nigiri', 'description' => 'Hand-pressed sushi', 'sort_order' => 3],
                ['name' => 'Maki Rolls', 'description' => 'Traditional and specialty rolls', 'sort_order' => 4],
                ['name' => 'Hot Dishes', 'description' => 'Cooked Japanese specialties', 'sort_order' => 5],
                ['name' => 'Beverages', 'description' => 'Sake, tea, and Japanese drinks', 'sort_order' => 6],
            ],
            'cafe-lumiere' => [
                ['name' => 'Breakfast', 'description' => 'Start your day French style', 'sort_order' => 1],
                ['name' => 'Lunch', 'description' => 'Light French lunch options', 'sort_order' => 2],
                ['name' => 'Pastries', 'description' => 'Fresh baked French pastries', 'sort_order' => 3],
                ['name' => 'Coffee & Tea', 'description' => 'Premium coffee and tea selection', 'sort_order' => 4],
                ['name' => 'Desserts', 'description' => 'French desserts and sweets', 'sort_order' => 5],
                ['name' => 'Wine', 'description' => 'French wine selection', 'sort_order' => 6],
            ],
            default => [
                ['name' => 'Appetizers', 'description' => 'Starters', 'sort_order' => 1],
                ['name' => 'Main Courses', 'description' => 'Main dishes', 'sort_order' => 2],
                ['name' => 'Desserts', 'description' => 'Sweet treats', 'sort_order' => 3],
                ['name' => 'Beverages', 'description' => 'Drinks', 'sort_order' => 4],
            ],
        };
    }
}