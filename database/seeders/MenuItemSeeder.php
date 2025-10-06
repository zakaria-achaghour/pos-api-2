<?php
// filepath: database/seeders/MenuItemSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MenuItem;
use App\Models\Category;
use App\Models\Restaurant;

class MenuItemSeeder extends Seeder
{
    public function run(): void
    {
        $restaurants = Restaurant::all();

        foreach ($restaurants as $restaurant) {
            $this->createMenuItemsForRestaurant($restaurant);
        }
    }

    private function createMenuItemsForRestaurant(Restaurant $restaurant): void
    {
        $categories = Category::where('restaurant_id', $restaurant->id)->get();
        
        foreach ($categories as $category) {
            $items = $this->getItemsForCategory($restaurant, $category);
            
            foreach ($items as $itemData) {
                MenuItem::create([
                    'restaurant_id' => $restaurant->id,
                    'category_id' => $category->id,
                    'name' => $itemData['name'],
                    'description' => $itemData['description'],
                    'price' => $itemData['price'],
                    'cost' => $itemData['cost'] ?? $itemData['price'] * 0.3,
                    'is_available' => true,
                    'preparation_time' => $itemData['prep_time'] ?? rand(10, 30),
                ]);
            }
        }
    }

    private function getItemsForCategory(Restaurant $restaurant, Category $category): array
    {
        return match([$restaurant->subdomain, $category->name]) {
            ['golden-fork', 'Appetizers'] => [
                ['name' => 'Calamari Rings', 'description' => 'Crispy fried squid with marinara', 'price' => 12.99],
                ['name' => 'Buffalo Wings', 'description' => '8 pieces with blue cheese dip', 'price' => 14.99],
                ['name' => 'Stuffed Mushrooms', 'description' => 'Crab and cream cheese stuffed', 'price' => 11.99],
            ],
            ['golden-fork', 'Main Courses'] => [
                ['name' => 'Grilled Salmon', 'description' => 'Atlantic salmon with lemon herb butter', 'price' => 26.99],
                ['name' => 'Chicken Parmesan', 'description' => 'Breaded chicken with marinara and mozzarella', 'price' => 22.99],
                ['name' => 'Beef Tenderloin', 'description' => '8oz tenderloin with garlic mashed potatoes', 'price' => 34.99],
            ],
            ['golden-fork', 'Beverages'] => [
                ['name' => 'House Wine (Glass)', 'description' => 'Red or white wine selection', 'price' => 8.99],
                ['name' => 'Craft Beer', 'description' => 'Local brewery selection', 'price' => 6.99],
                ['name' => 'Fresh Juice', 'description' => 'Orange, apple, or cranberry', 'price' => 4.99],
            ],
            
            ['bella-vista', 'Pizza'] => [
                ['name' => 'Margherita', 'description' => 'Tomato, mozzarella, fresh basil', 'price' => 16.99],
                ['name' => 'Pepperoni', 'description' => 'Classic pepperoni with mozzarella', 'price' => 18.99],
                ['name' => 'Quattro Stagioni', 'description' => 'Four seasons pizza with varied toppings', 'price' => 22.99],
            ],
            ['bella-vista', 'Pasta'] => [
                ['name' => 'Spaghetti Carbonara', 'description' => 'Eggs, pancetta, parmesan cheese', 'price' => 19.99],
                ['name' => 'Fettuccine Alfredo', 'description' => 'Creamy parmesan sauce', 'price' => 17.99],
                ['name' => 'Lasagna della Casa', 'description' => 'Traditional meat and cheese lasagna', 'price' => 21.99],
            ],
            
            ['sakura-sushi', 'Sashimi'] => [
                ['name' => 'Salmon Sashimi', 'description' => '6 pieces of fresh Atlantic salmon', 'price' => 18.99],
                ['name' => 'Tuna Sashimi', 'description' => '6 pieces of yellowfin tuna', 'price' => 22.99],
                ['name' => 'Mixed Sashimi', 'description' => 'Chef selection of 12 pieces', 'price' => 32.99],
            ],
            ['sakura-sushi', 'Maki Rolls'] => [
                ['name' => 'California Roll', 'description' => 'Crab, avocado, cucumber', 'price' => 12.99],
                ['name' => 'Spicy Tuna Roll', 'description' => 'Spicy tuna with cucumber', 'price' => 14.99],
                ['name' => 'Dragon Roll', 'description' => 'Eel and cucumber topped with avocado', 'price' => 18.99],
            ],
            
            ['cafe-lumiere', 'Breakfast'] => [
                ['name' => 'Croissant Benedict', 'description' => 'Poached eggs on buttery croissant', 'price' => 14.99],
                ['name' => 'French Toast', 'description' => 'Brioche with maple syrup and berries', 'price' => 12.99],
                ['name' => 'Quiche Lorraine', 'description' => 'Traditional bacon and gruyere quiche', 'price' => 13.99],
            ],
            ['cafe-lumiere', 'Coffee & Tea'] => [
                ['name' => 'Espresso', 'description' => 'Single shot French roast', 'price' => 3.99],
                ['name' => 'Café au Lait', 'description' => 'Coffee with steamed milk', 'price' => 4.99],
                ['name' => 'French Press', 'description' => 'Full pot of premium coffee', 'price' => 6.99],
            ],
            
            default => [
                ['name' => 'House Special', 'description' => 'Chef recommendation', 'price' => 19.99],
                ['name' => 'Daily Soup', 'description' => 'Ask your server', 'price' => 7.99],
                ['name' => 'House Salad', 'description' => 'Mixed greens with house dressing', 'price' => 9.99],
            ],
        };
    }
}