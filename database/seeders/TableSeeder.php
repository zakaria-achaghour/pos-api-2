<?php
// filepath: database/seeders/TableSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Table;
use App\Models\Restaurant;

class TableSeeder extends Seeder
{
    public function run(): void
    {
        $restaurants = Restaurant::all();

        foreach ($restaurants as $restaurant) {
            $this->createTablesForRestaurant($restaurant);
        }
    }

    private function createTablesForRestaurant(Restaurant $restaurant): void
    {
        $tableCount = match($restaurant->subdomain) {
            'golden-fork' => 20,  // Upscale restaurant
            'bella-vista' => 15,  // Medium pizzeria
            'sakura-sushi' => 12, // Intimate sushi bar
            'cafe-lumiere' => 18, // Busy cafe
            default => 15,
        };

        for ($i = 1; $i <= $tableCount; $i++) {
            $capacity = match(true) {
                $i <= 6 => 2,      // Small tables
                $i <= 12 => 4,     // Medium tables
                $i <= 16 => 6,     // Large tables
                default => 8,      // Extra large tables
            };

            $section = match(true) {
                $i <= 5 => 'Main Dining',
                $i <= 10 => 'Window Section',
                $i <= 15 => 'Patio',
                default => 'Private Dining',
            };

            $shape = match(true) {
                $capacity == 2 => 'round',
                $capacity == 4 => 'square',
                $capacity == 6 => 'rectangular',
                default => 'oval',
            };

            $floor = match(true) {
                $i <= 10 => 1,
                $i <= 18 => 2,
                default => 3,
            };

            $features = [];
            if ($section === 'Window Section') {
                $features[] = 'Window View';
            }
            if ($section === 'Private Dining') {
                $features[] = 'VIP Section';
                $features[] = 'Private';
            }
            if ($section === 'Patio') {
                $features[] = 'Outdoor Seating';
            }
            if ($capacity >= 6) {
                $features[] = 'Large Group';
            }

            Table::create([
                'restaurant_id' => $restaurant->id,
                'number' => sprintf('T%02d', $i),
                'capacity' => $capacity,
                'status' => 'available',
                'section' => $section,
                'shape' => $shape,
                'location' => [
                    'section' => $section,
                    'floor' => $floor,
                    'area' => match($section) {
                        'Main Dining' => 'Central',
                        'Window Section' => 'East Wing',
                        'Patio' => 'Garden',
                        'Private Dining' => 'West Wing',
                        default => 'General',
                    }
                ],
                'features' => !empty($features) ? $features : null,
                'grid_x' => ($i - 1) % 5,
                'grid_y' => intval(($i - 1) / 5),
                'qr_code' => 'QR-' . $restaurant->subdomain . '-T' . sprintf('%02d', $i),
            ]);
        }
    }
}