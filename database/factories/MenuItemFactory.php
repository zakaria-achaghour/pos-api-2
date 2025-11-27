<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;

class MenuItemFactory extends Factory
{
    protected $model = MenuItem::class;

    public function definition(): array
    {
        return [
            'restaurant_id' => Restaurant::factory(),
            'category_id' => Category::factory(),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'price' => $this->faker->randomFloat(2, 5, 50),
            'cost' => $this->faker->randomFloat(2, 1, 15),
            'is_available' => true,
            'is_active' => true,
            'preparation_time' => $this->faker->numberBetween(5, 45),
            'image_url' => $this->faker->imageUrl(640, 480, 'food', true),
            'allergens' => $this->faker->randomElements(['gluten', 'dairy', 'nuts', 'soy', 'eggs'], 2),
            'ingredients' => $this->faker->words(5),
        ];
    }
}
