<?php
// filepath: database/seeders/RestaurantSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Restaurant;

class RestaurantSeeder extends Seeder
{
    public function run(): void
    {
        $restaurants = [
            [
                'name' => 'The Golden Fork',
                'subdomain' => 'golden-fork',
                'address' => '123 Main Street, Downtown',
                'phone' => '+1-555-0101',
                'email' => 'info@goldenfork.com',
                'timezone' => 'America/New_York',
                'currency' => 'USD',
                'tax_rate' => 8.25,
                'is_active' => true,
                'settings' => json_encode([
                    'theme' => 'gold',
                    'language' => 'en',
                    'receipt_footer' => 'Thank you for dining with us!',
                    'auto_print_kitchen' => true,
                ]),
            ],
            [
                'name' => 'Bella Vista Pizzeria',
                'subdomain' => 'bella-vista',
                'address' => '456 Oak Avenue, Little Italy',
                'phone' => '+1-555-0202',
                'email' => 'orders@bellavista.com',
                'timezone' => 'America/Los_Angeles',
                'currency' => 'USD',
                'tax_rate' => 9.50,
                'is_active' => true,
                'settings' => json_encode([
                    'theme' => 'italian',
                    'language' => 'en',
                    'receipt_footer' => 'Grazie! Come back soon!',
                    'auto_print_kitchen' => true,
                ]),
            ],
            [
                'name' => 'Sakura Sushi Bar',
                'subdomain' => 'sakura-sushi',
                'address' => '789 Cherry Blossom Lane, Japantown',
                'phone' => '+1-555-0303',
                'email' => 'hello@sakurasushi.com',
                'timezone' => 'America/Chicago',
                'currency' => 'USD',
                'tax_rate' => 7.75,
                'is_active' => true,
                'settings' => json_encode([
                    'theme' => 'japanese',
                    'language' => 'en',
                    'receipt_footer' => 'Arigato gozaimasu!',
                    'auto_print_kitchen' => false,
                ]),
            ],
            [
                'name' => 'Café Lumière',
                'subdomain' => 'cafe-lumiere',
                'address' => '321 French Quarter, Arts District',
                'phone' => '+1-555-0404',
                'email' => 'bonjour@cafelumiere.com',
                'timezone' => 'America/New_York',
                'currency' => 'USD',
                'tax_rate' => 8.00,
                'is_active' => true,
                'settings' => json_encode([
                    'theme' => 'french',
                    'language' => 'en',
                    'receipt_footer' => 'Merci beaucoup!',
                    'auto_print_kitchen' => true,
                ]),
            ],
        ];

        foreach ($restaurants as $restaurant) {
            Restaurant::create($restaurant);
        }
    }
}