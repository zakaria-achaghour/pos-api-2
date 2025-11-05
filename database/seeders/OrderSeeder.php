<?php
// filepath: database/seeders/OrderSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Restaurant;
use App\Models\Table;
use App\Models\MenuItem;
use App\Models\Staff;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $restaurants = Restaurant::all();

        foreach ($restaurants as $restaurant) {
            $this->createOrdersForRestaurant($restaurant);
        }
    }

    private function createOrdersForRestaurant(Restaurant $restaurant): void
    {
        $tables = Table::where('restaurant_id', $restaurant->id)->get();
        $menuItems = MenuItem::where('restaurant_id', $restaurant->id)->get();
        $waiters = Staff::where('restaurant_id', $restaurant->id)
            ->where('department', 'Service')
            ->get();

        // Create orders for the last 30 days
        for ($day = 30; $day >= 0; $day--) {
            $date = now()->subDays($day);
            $ordersPerDay = rand(5, 15);

            for ($i = 0; $i < $ordersPerDay; $i++) {
                $table = $tables->random();
                $waiter = $waiters->random();
                
                $order = Order::create([
                    'restaurant_id' => $restaurant->id,
                    'table_id' => $table->id,
                    'waiter_id' => $waiter->id,
                    'status' => fake()->randomElement(['paid', 'paid', 'paid', 'cancelled']),
                    'priority' => fake()->randomElement(['normal', 'normal', 'rush']),
                    'subtotal' => 0,
                    'tax_amount' => 0,
                    'discount_amount' => rand(0, 10),
                    'total' => 0,
                    'payment_method' => fake()->randomElement(['cash', 'card', 'mobile']),
                    'notes' => fake()->optional(0.3)->sentence(),
                    'placed_at' => $date->addMinutes(rand(0, 1439)),
                    'paid_at' => $date->addMinutes(rand(30, 90)),
                ]);

                // Add random items to order
                $itemCount = rand(1, 5);
                $subtotal = 0;

                for ($j = 0; $j < $itemCount; $j++) {
                    $menuItem = $menuItems->random();
                    $quantity = rand(1, 3);

                    OrderItem::create([
                        'order_id' => $order->id,
                        'menu_item_id' => $menuItem->id,
                        'quantity' => $quantity,
                        'unit_price' => $menuItem->price,
                        'special_instructions' => fake()->optional(0.2)->sentence(),
                    ]);

                    $subtotal += $menuItem->price * $quantity;
                }

                // Update order totals
                $taxAmount = $subtotal * ($restaurant->tax_rate / 100);
                $total = $subtotal + $taxAmount - $order->discount_amount;

                $order->update([
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'total' => max(0, $total),
                ]);
            }
        }
    }
}