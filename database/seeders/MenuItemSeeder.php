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
                    'is_active' => true,
                    'preparation_time' => $itemData['prep_time'] ?? rand(10, 30),
                    'ingredients' => $itemData['ingredients'] ?? null,
                    'allergens' => $itemData['allergens'] ?? null,
                ]);
            }
        }
    }

    private function getItemsForCategory(Restaurant $restaurant, Category $category): array
    {
        return match([$restaurant->subdomain, $category->name]) {
            ['golden-fork', 'Appetizers'] => [
                ['name' => 'Calamari Rings', 'description' => 'Crispy fried squid with marinara', 'price' => 12.99, 'ingredients' => ['squid', 'flour', 'breadcrumbs', 'marinara sauce', 'lemon'], 'allergens' => ['shellfish', 'gluten']],
                ['name' => 'Buffalo Wings', 'description' => '8 pieces with blue cheese dip', 'price' => 14.99, 'ingredients' => ['chicken wings', 'buffalo sauce', 'blue cheese', 'celery', 'carrots'], 'allergens' => ['dairy']],
                ['name' => 'Stuffed Mushrooms', 'description' => 'Crab and cream cheese stuffed', 'price' => 11.99, 'ingredients' => ['mushrooms', 'crab meat', 'cream cheese', 'breadcrumbs', 'parsley'], 'allergens' => ['shellfish', 'dairy', 'gluten']],
            ],
            ['golden-fork', 'Main Courses'] => [
                ['name' => 'Grilled Salmon', 'description' => 'Atlantic salmon with lemon herb butter', 'price' => 26.99, 'ingredients' => ['salmon', 'butter', 'lemon', 'herbs', 'garlic', 'asparagus'], 'allergens' => ['fish', 'dairy']],
                ['name' => 'Chicken Parmesan', 'description' => 'Breaded chicken with marinara and mozzarella', 'price' => 22.99, 'ingredients' => ['chicken breast', 'breadcrumbs', 'marinara sauce', 'mozzarella', 'parmesan', 'pasta'], 'allergens' => ['gluten', 'dairy']],
                ['name' => 'Beef Tenderloin', 'description' => '8oz tenderloin with garlic mashed potatoes', 'price' => 34.99, 'ingredients' => ['beef tenderloin', 'potatoes', 'garlic', 'butter', 'cream', 'herbs'], 'allergens' => ['dairy']],
            ],
            ['golden-fork', 'Beverages'] => [
                ['name' => 'House Wine (Glass)', 'description' => 'Red or white wine selection', 'price' => 8.99, 'ingredients' => ['wine grapes', 'sulfites'], 'allergens' => ['sulfites']],
                ['name' => 'Craft Beer', 'description' => 'Local brewery selection', 'price' => 6.99, 'ingredients' => ['water', 'barley', 'hops', 'yeast'], 'allergens' => ['gluten']],
                ['name' => 'Fresh Juice', 'description' => 'Orange, apple, or cranberry', 'price' => 4.99, 'ingredients' => ['fresh fruit'], 'allergens' => []],
            ],
            
            ['bella-vista', 'Pizza'] => [
                ['name' => 'Margherita', 'description' => 'Tomato, mozzarella, fresh basil', 'price' => 16.99, 'ingredients' => ['pizza dough', 'tomato sauce', 'mozzarella', 'basil', 'olive oil'], 'allergens' => ['gluten', 'dairy']],
                ['name' => 'Pepperoni', 'description' => 'Classic pepperoni with mozzarella', 'price' => 18.99, 'ingredients' => ['pizza dough', 'tomato sauce', 'mozzarella', 'pepperoni'], 'allergens' => ['gluten', 'dairy', 'pork']],
                ['name' => 'Quattro Stagioni', 'description' => 'Four seasons pizza with varied toppings', 'price' => 22.99, 'ingredients' => ['pizza dough', 'tomato sauce', 'mozzarella', 'mushrooms', 'artichokes', 'ham', 'olives'], 'allergens' => ['gluten', 'dairy']],
            ],
            ['bella-vista', 'Pasta'] => [
                ['name' => 'Spaghetti Carbonara', 'description' => 'Eggs, pancetta, parmesan cheese', 'price' => 19.99, 'ingredients' => ['spaghetti', 'eggs', 'pancetta', 'parmesan', 'black pepper'], 'allergens' => ['gluten', 'dairy', 'eggs']],
                ['name' => 'Fettuccine Alfredo', 'description' => 'Creamy parmesan sauce', 'price' => 17.99, 'ingredients' => ['fettuccine', 'heavy cream', 'parmesan', 'butter', 'garlic'], 'allergens' => ['gluten', 'dairy']],
                ['name' => 'Lasagna della Casa', 'description' => 'Traditional meat and cheese lasagna', 'price' => 21.99, 'ingredients' => ['lasagna noodles', 'ground beef', 'ricotta', 'mozzarella', 'tomato sauce', 'herbs'], 'allergens' => ['gluten', 'dairy']],
            ],
            
            ['sakura-sushi', 'Sashimi'] => [
                ['name' => 'Salmon Sashimi', 'description' => '6 pieces of fresh Atlantic salmon', 'price' => 18.99, 'ingredients' => ['salmon', 'soy sauce', 'wasabi', 'ginger'], 'allergens' => ['fish', 'soy']],
                ['name' => 'Tuna Sashimi', 'description' => '6 pieces of yellowfin tuna', 'price' => 22.99, 'ingredients' => ['tuna', 'soy sauce', 'wasabi', 'ginger'], 'allergens' => ['fish', 'soy']],
                ['name' => 'Mixed Sashimi', 'description' => 'Chef selection of 12 pieces', 'price' => 32.99, 'ingredients' => ['salmon', 'tuna', 'yellowtail', 'soy sauce', 'wasabi', 'ginger'], 'allergens' => ['fish', 'soy']],
            ],
            ['sakura-sushi', 'Maki Rolls'] => [
                ['name' => 'California Roll', 'description' => 'Crab, avocado, cucumber', 'price' => 12.99, 'ingredients' => ['sushi rice', 'nori', 'crab', 'avocado', 'cucumber', 'sesame seeds'], 'allergens' => ['shellfish', 'sesame']],
                ['name' => 'Spicy Tuna Roll', 'description' => 'Spicy tuna with cucumber', 'price' => 14.99, 'ingredients' => ['sushi rice', 'nori', 'tuna', 'cucumber', 'spicy mayo', 'sriracha'], 'allergens' => ['fish', 'eggs', 'soy']],
                ['name' => 'Dragon Roll', 'description' => 'Eel and cucumber topped with avocado', 'price' => 18.99, 'ingredients' => ['sushi rice', 'nori', 'eel', 'cucumber', 'avocado', 'eel sauce'], 'allergens' => ['fish', 'soy']],
            ],
            
            ['cafe-lumiere', 'Breakfast'] => [
                ['name' => 'Croissant Benedict', 'description' => 'Poached eggs on buttery croissant', 'price' => 14.99, 'ingredients' => ['croissant', 'eggs', 'ham', 'hollandaise sauce', 'butter'], 'allergens' => ['gluten', 'eggs', 'dairy']],
                ['name' => 'French Toast', 'description' => 'Brioche with maple syrup and berries', 'price' => 12.99, 'ingredients' => ['brioche', 'eggs', 'milk', 'maple syrup', 'berries', 'powdered sugar'], 'allergens' => ['gluten', 'eggs', 'dairy']],
                ['name' => 'Quiche Lorraine', 'description' => 'Traditional bacon and gruyere quiche', 'price' => 13.99, 'ingredients' => ['pie crust', 'eggs', 'cream', 'bacon', 'gruyere cheese', 'onions'], 'allergens' => ['gluten', 'eggs', 'dairy']],
            ],
            ['cafe-lumiere', 'Coffee & Tea'] => [
                ['name' => 'Espresso', 'description' => 'Single shot French roast', 'price' => 3.99, 'ingredients' => ['espresso beans', 'water'], 'allergens' => []],
                ['name' => 'Café au Lait', 'description' => 'Coffee with steamed milk', 'price' => 4.99, 'ingredients' => ['coffee', 'milk'], 'allergens' => ['dairy']],
                ['name' => 'French Press', 'description' => 'Full pot of premium coffee', 'price' => 6.99, 'ingredients' => ['coffee beans', 'water'], 'allergens' => []],
            ],
            
            default => [
                ['name' => 'House Special', 'description' => 'Chef recommendation', 'price' => 19.99, 'ingredients' => ['seasonal ingredients'], 'allergens' => []],
                ['name' => 'Daily Soup', 'description' => 'Ask your server', 'price' => 7.99, 'ingredients' => ['vegetables', 'broth', 'herbs'], 'allergens' => []],
                ['name' => 'House Salad', 'description' => 'Mixed greens with house dressing', 'price' => 9.99, 'ingredients' => ['mixed greens', 'tomatoes', 'cucumbers', 'carrots', 'dressing'], 'allergens' => []],
            ],
        };
    }
}