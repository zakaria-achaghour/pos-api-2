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
                    'image_url' => $itemData['image_url'] ?? null,
                ]);
            }
        }
    }

    private function getItemsForCategory(Restaurant $restaurant, Category $category): array
    {
        return match([$restaurant->subdomain, $category->name]) {
            ['golden-fork', 'Appetizers'] => [
                ['name' => 'Calamari Rings', 'description' => 'Crispy fried squid with marinara', 'price' => 12.99, 'ingredients' => ['squid', 'flour', 'breadcrumbs', 'marinara sauce', 'lemon'], 'allergens' => ['shellfish', 'gluten'], 'image_url' => 'https://images.unsplash.com/photo-1599488615731-7e5c2823ff28?w=500'],
                ['name' => 'Buffalo Wings', 'description' => '8 pieces with blue cheese dip', 'price' => 14.99, 'ingredients' => ['chicken wings', 'buffalo sauce', 'blue cheese', 'celery', 'carrots'], 'allergens' => ['dairy'], 'image_url' => 'https://images.unsplash.com/photo-1567620832903-9fc6debc209f?w=500'],
                ['name' => 'Stuffed Mushrooms', 'description' => 'Crab and cream cheese stuffed', 'price' => 11.99, 'ingredients' => ['mushrooms', 'crab meat', 'cream cheese', 'breadcrumbs', 'parsley'], 'allergens' => ['shellfish', 'dairy', 'gluten'], 'image_url' => 'https://images.unsplash.com/photo-1625938144755-652e08e359b7?w=500'],
            ],
            ['golden-fork', 'Main Courses'] => [
                ['name' => 'Grilled Salmon', 'description' => 'Atlantic salmon with lemon herb butter', 'price' => 26.99, 'ingredients' => ['salmon', 'butter', 'lemon', 'herbs', 'garlic', 'asparagus'], 'allergens' => ['fish', 'dairy'], 'image_url' => 'https://images.unsplash.com/photo-1485921325833-c519f76c4927?w=500'],
                ['name' => 'Chicken Parmesan', 'description' => 'Breaded chicken with marinara and mozzarella', 'price' => 22.99, 'ingredients' => ['chicken breast', 'breadcrumbs', 'marinara sauce', 'mozzarella', 'parmesan', 'pasta'], 'allergens' => ['gluten', 'dairy'], 'image_url' => 'https://images.unsplash.com/photo-1632778149955-e80f8ceca2e8?w=500'],
                ['name' => 'Beef Tenderloin', 'description' => '8oz tenderloin with garlic mashed potatoes', 'price' => 34.99, 'ingredients' => ['beef tenderloin', 'potatoes', 'garlic', 'butter', 'cream', 'herbs'], 'allergens' => ['dairy'], 'image_url' => 'https://images.unsplash.com/photo-1558030006-45067198663e?w=500'],
            ],
            ['golden-fork', 'Beverages'] => [
                ['name' => 'House Wine (Glass)', 'description' => 'Red or white wine selection', 'price' => 8.99, 'ingredients' => ['wine grapes', 'sulfites'], 'allergens' => ['sulfites'], 'image_url' => 'https://images.unsplash.com/photo-1510812431401-41d2bd2722f3?w=500'],
                ['name' => 'Craft Beer', 'description' => 'Local brewery selection', 'price' => 6.99, 'ingredients' => ['water', 'barley', 'hops', 'yeast'], 'allergens' => ['gluten'], 'image_url' => 'https://images.unsplash.com/photo-1535958636474-b021ee887b13?w=500'],
                ['name' => 'Fresh Juice', 'description' => 'Orange, apple, or cranberry', 'price' => 4.99, 'ingredients' => ['fresh fruit'], 'allergens' => [], 'image_url' => 'https://images.unsplash.com/photo-1613478223719-2ab802602423?w=500'],
            ],
            
            ['bella-vista', 'Pizza'] => [
                ['name' => 'Margherita', 'description' => 'Tomato, mozzarella, fresh basil', 'price' => 16.99, 'ingredients' => ['pizza dough', 'tomato sauce', 'mozzarella', 'basil', 'olive oil'], 'allergens' => ['gluten', 'dairy'], 'image_url' => 'https://images.unsplash.com/photo-1574071318508-1cdbab80d002?w=500'],
                ['name' => 'Pepperoni', 'description' => 'Classic pepperoni with mozzarella', 'price' => 18.99, 'ingredients' => ['pizza dough', 'tomato sauce', 'mozzarella', 'pepperoni'], 'allergens' => ['gluten', 'dairy', 'pork'], 'image_url' => 'https://images.unsplash.com/photo-1628840042765-356cda07504e?w=500'],
                ['name' => 'Quattro Stagioni', 'description' => 'Four seasons pizza with varied toppings', 'price' => 22.99, 'ingredients' => ['pizza dough', 'tomato sauce', 'mozzarella', 'mushrooms', 'artichokes', 'ham', 'olives'], 'allergens' => ['gluten', 'dairy'], 'image_url' => 'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?w=500'],
            ],
            ['bella-vista', 'Pasta'] => [
                ['name' => 'Spaghetti Carbonara', 'description' => 'Eggs, pancetta, parmesan cheese', 'price' => 19.99, 'ingredients' => ['spaghetti', 'eggs', 'pancetta', 'parmesan', 'black pepper'], 'allergens' => ['gluten', 'dairy', 'eggs'], 'image_url' => 'https://images.unsplash.com/photo-1612874742237-6526221588e3?w=500'],
                ['name' => 'Fettuccine Alfredo', 'description' => 'Creamy parmesan sauce', 'price' => 17.99, 'ingredients' => ['fettuccine', 'heavy cream', 'parmesan', 'butter', 'garlic'], 'allergens' => ['gluten', 'dairy'], 'image_url' => 'https://images.unsplash.com/photo-1645112411341-6c4fd023714a?w=500'],
                ['name' => 'Lasagna della Casa', 'description' => 'Traditional meat and cheese lasagna', 'price' => 21.99, 'ingredients' => ['lasagna noodles', 'ground beef', 'ricotta', 'mozzarella', 'tomato sauce', 'herbs'], 'allergens' => ['gluten', 'dairy'], 'image_url' => 'https://images.unsplash.com/photo-1574868291534-18430db83fca?w=500'],
            ],
            
            ['sakura-sushi', 'Sashimi'] => [
                ['name' => 'Salmon Sashimi', 'description' => '6 pieces of fresh Atlantic salmon', 'price' => 18.99, 'ingredients' => ['salmon', 'soy sauce', 'wasabi', 'ginger'], 'allergens' => ['fish', 'soy'], 'image_url' => 'https://images.unsplash.com/photo-1534482421-64566f976cfa?w=500'],
                ['name' => 'Tuna Sashimi', 'description' => '6 pieces of yellowfin tuna', 'price' => 22.99, 'ingredients' => ['tuna', 'soy sauce', 'wasabi', 'ginger'], 'allergens' => ['fish', 'soy'], 'image_url' => 'https://images.unsplash.com/photo-1579584425555-c3ce17fd4351?w=500'],
                ['name' => 'Mixed Sashimi', 'description' => 'Chef selection of 12 pieces', 'price' => 32.99, 'ingredients' => ['salmon', 'tuna', 'yellowtail', 'soy sauce', 'wasabi', 'ginger'], 'allergens' => ['fish', 'soy'], 'image_url' => 'https://images.unsplash.com/photo-1611143669185-af224c5e3252?w=500'],
            ],
            ['sakura-sushi', 'Maki Rolls'] => [
                ['name' => 'California Roll', 'description' => 'Crab, avocado, cucumber', 'price' => 12.99, 'ingredients' => ['sushi rice', 'nori', 'crab', 'avocado', 'cucumber', 'sesame seeds'], 'allergens' => ['shellfish', 'sesame'], 'image_url' => 'https://images.unsplash.com/photo-1579871494447-9811cf80d66c?w=500'],
                ['name' => 'Spicy Tuna Roll', 'description' => 'Spicy tuna with cucumber', 'price' => 14.99, 'ingredients' => ['sushi rice', 'nori', 'tuna', 'cucumber', 'spicy mayo', 'sriracha'], 'allergens' => ['fish', 'eggs', 'soy'], 'image_url' => 'https://images.unsplash.com/photo-1559339352-11d035aa65de?w=500'],
                ['name' => 'Dragon Roll', 'description' => 'Eel and cucumber topped with avocado', 'price' => 18.99, 'ingredients' => ['sushi rice', 'nori', 'eel', 'cucumber', 'avocado', 'eel sauce'], 'allergens' => ['fish', 'soy'], 'image_url' => 'https://images.unsplash.com/photo-1617196034496-64ac796002bb?w=500'],
            ],
            
            ['cafe-lumiere', 'Breakfast'] => [
                ['name' => 'Croissant Benedict', 'description' => 'Poached eggs on buttery croissant', 'price' => 14.99, 'ingredients' => ['croissant', 'eggs', 'ham', 'hollandaise sauce', 'butter'], 'allergens' => ['gluten', 'eggs', 'dairy'], 'image_url' => 'https://images.unsplash.com/photo-1600093463592-8e36ae95ef56?w=500'],
                ['name' => 'French Toast', 'description' => 'Brioche with maple syrup and berries', 'price' => 12.99, 'ingredients' => ['brioche', 'eggs', 'milk', 'maple syrup', 'berries', 'powdered sugar'], 'allergens' => ['gluten', 'eggs', 'dairy'], 'image_url' => 'https://images.unsplash.com/photo-1484723091739-30a097e8f929?w=500'],
                ['name' => 'Quiche Lorraine', 'description' => 'Traditional bacon and gruyere quiche', 'price' => 13.99, 'ingredients' => ['pie crust', 'eggs', 'cream', 'bacon', 'gruyere cheese', 'onions'], 'allergens' => ['gluten', 'eggs', 'dairy'], 'image_url' => 'https://images.unsplash.com/photo-1647102398947-f37a0c10e685?w=500'],
            ],
            ['cafe-lumiere', 'Coffee & Tea'] => [
                ['name' => 'Espresso', 'description' => 'Single shot French roast', 'price' => 3.99, 'ingredients' => ['espresso beans', 'water'], 'allergens' => [], 'image_url' => 'https://images.unsplash.com/photo-1510707577719-ae7c14805e3a?w=500'],
                ['name' => 'Café au Lait', 'description' => 'Coffee with steamed milk', 'price' => 4.99, 'ingredients' => ['coffee', 'milk'], 'allergens' => ['dairy'], 'image_url' => 'https://images.unsplash.com/photo-1585463230804-7e6347108d64?w=500'],
                ['name' => 'French Press', 'description' => 'Full pot of premium coffee', 'price' => 6.99, 'ingredients' => ['coffee beans', 'water'], 'allergens' => [], 'image_url' => 'https://images.unsplash.com/photo-1447933601403-0c6688de566e?w=500'],
            ],
            
            default => [
                ['name' => 'House Special', 'description' => 'Chef recommendation', 'price' => 19.99, 'ingredients' => ['seasonal ingredients'], 'allergens' => [], 'image_url' => 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=500'],
                ['name' => 'Daily Soup', 'description' => 'Ask your server', 'price' => 7.99, 'ingredients' => ['vegetables', 'broth', 'herbs'], 'allergens' => [], 'image_url' => 'https://images.unsplash.com/photo-1547592166-23acbe3a624b?w=500'],
                ['name' => 'House Salad', 'description' => 'Mixed greens with house dressing', 'price' => 9.99, 'ingredients' => ['mixed greens', 'tomatoes', 'cucumbers', 'carrots', 'dressing'], 'allergens' => [], 'image_url' => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=500'],
            ],
        };
    }
}