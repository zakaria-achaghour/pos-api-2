<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Infrastructure\Tenancy\Tenant;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class MenuItemImageUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Setup tenant and user
        $this->user = User::factory()->create();
        $this->restaurant = \App\Models\Restaurant::create([
            'name' => 'Test Restaurant',
            'subdomain' => 'test-restaurant',
            'email' => 'test@example.com',
            'currency' => 'USD',
        ]);
        
        // Assign role
        $role = Role::firstOrCreate(['name' => 'Owner', 'guard_name' => 'api']);
        $this->user->assignRole($role);
        
        // Set tenant context

        $this->user->update(['restaurant_id' => $this->restaurant->id]);
        
        // Create category
        $this->category = Category::create([
            'name' => 'Test Category',
            'restaurant_id' => $this->restaurant->id,
        ]);
    }

    public function test_can_upload_image_when_creating_menu_item()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('pizza.jpg');

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/items', [
                'name' => 'Pizza',
                'price' => 10.99,
                'category_id' => $this->category->id,
                'image' => $file,
            ]);

        $response->assertStatus(201);
        
        $item = MenuItem::first();
        $this->assertNotNull($item->image_url);
        
        // Check if file exists in storage
        // The path in storage is relative to the disk root
        $path = str_replace('/storage/', '', parse_url($item->image_url, PHP_URL_PATH));
        Storage::disk('public')->assertExists($path);
    }

    public function test_can_update_menu_item_image()
    {
        Storage::fake('public');

        $item = MenuItem::create([
            'name' => 'Old Pizza',
            'price' => 10.99,
            'category_id' => $this->category->id,
            'restaurant_id' => $this->restaurant->id,
        ]);

        $file = UploadedFile::fake()->image('new_pizza.jpg');

        $response = $this->actingAs($this->user, 'api')
            ->putJson("/api/items/{$item->id}", [
                'image' => $file,
            ]);

        $response->assertStatus(200);
        
        $item->refresh();
        $this->assertNotNull($item->image_url);
        
        $path = str_replace('/storage/', '', parse_url($item->image_url, PHP_URL_PATH));
        Storage::disk('public')->assertExists($path);
    }
}
