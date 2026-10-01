<?php

namespace Tests\Feature;

use App\Models\{Category, MenuItem, Order, Restaurant, Staff, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function actor(): User
    {
        $restaurant = Restaurant::create(['name' => 'Test', 'subdomain' => 'workflow', 'tax_rate' => 10, 'service_charge' => 5]);
        return User::factory()->create(['restaurant_id' => $restaurant->id]);
    }

    public function test_tableless_order_discount_is_recalculated_and_preserved_on_close(): void
    {
        $user = $this->actor();
        $category = Category::create(['restaurant_id' => $user->restaurant_id, 'name' => 'Food']);
        $item = MenuItem::create(['restaurant_id' => $user->restaurant_id, 'category_id' => $category->id, 'name' => 'Pasta', 'price' => 20]);
        $id = $this->actingAs($user, 'api')->postJson('/api/orders', [
            'type' => 'takeout', 'discount_amount' => 3,
            'items' => [['menu_item_id' => $item->id, 'quantity' => 1]],
        ])->assertCreated()->json('id');
        $order = Order::findOrFail($id);
        $this->assertNull($order->table_id);
        $this->assertEquals(20, $order->total);
        $this->putJson("/api/orders/$id", ['discount_amount' => 5])->assertOk();
        $this->assertEquals(18, $order->fresh()->total);
        $this->postJson("/api/orders/$id/close", ['payment_method' => 'card'])->assertOk();
        $this->assertEquals(18, $order->fresh()->total);
    }

    public function test_payment_applies_discount_before_computing_change_and_can_clear_it(): void
    {
        $user = $this->actor();
        $category = Category::create(['restaurant_id' => $user->restaurant_id, 'name' => 'Food']);
        $item = MenuItem::create(['restaurant_id' => $user->restaurant_id, 'category_id' => $category->id, 'name' => 'Pasta', 'price' => 20]);
        $id = $this->actingAs($user, 'api')->postJson('/api/orders', ['table_id' => null, 'type' => 'delivery', 'items' => [['menu_item_id' => $item->id, 'quantity' => 1]]])->assertCreated()->json('id');
        $this->patchJson("/api/orders/$id/payment", ['payment_method' => 'cash', 'payment_status' => 'pending', 'discount_amount' => 5])->assertOk();
        $this->assertEquals(18, Order::find($id)->total);
        $this->patchJson("/api/orders/$id/payment", ['payment_method' => 'cash', 'payment_status' => 'completed', 'discount_amount' => 0, 'amount_received' => 30])
            ->assertOk()->assertJsonPath('change_due', 7);
        $this->assertEquals(23, Order::find($id)->total);
        $this->postJson('/api/orders', ['discount_amount' => -1])->assertUnprocessable();
    }

    public function test_search_active_pagination_and_mine_are_applied_before_pagination(): void
    {
        $user = $this->actor();
        $otherRestaurant = Restaurant::create(['name' => 'Other', 'subdomain' => 'other']);
        Order::create(['restaurant_id' => $otherRestaurant->id, 'notes' => 'Needle']);
        $staff = Staff::create(['restaurant_id' => $user->restaurant_id, 'user_id' => $user->id,
            'employee_id' => 'W1', 'first_name' => 'Test', 'last_name' => 'Waiter', 'email' => $user->email,
            'position' => 'Waiter', 'department' => 'Service', 'hourly_rate' => 10, 'hire_date' => '2026-01-01']);
        foreach (['pending', 'accepted', 'preparing', 'ready', 'served', 'completed', 'cancelled'] as $status) {
            Order::create(['restaurant_id' => $user->restaurant_id, 'waiter_id' => $staff->id, 'notes' => 'Needle', 'status' => $status]);
        }
        Order::create(['restaurant_id' => $user->restaurant_id, 'notes' => 'Needle']);
        Order::create(['restaurant_id' => $user->restaurant_id, 'waiter_id' => $staff->id, 'notes' => 'Different']);
        $this->actingAs($user, 'api')->getJson('/api/orders?status=active&mine=1&search=NEEDLE&per_page=2')
            ->assertOk()->assertJsonPath('total', 5)->assertJsonPath('per_page', 2)->assertJsonCount(2, 'data');
        $this->getJson('/api/orders?per_page=101')->assertUnprocessable();
        $this->getJson('/api/orders?status=bogus')->assertUnprocessable();
        $withoutStaff = User::factory()->create(['restaurant_id' => $user->restaurant_id]);
        $this->actingAs($withoutStaff, 'api')->getJson('/api/orders?mine=1')->assertOk()->assertJsonPath('total', 0);
    }

    public function test_waiter_creation_uses_staff_id_instead_of_user_id(): void
    {
        $user = $this->actor();
        $user->assignRole(\Spatie\Permission\Models\Role::findOrCreate('Waiter', 'api'));
        $staff = Staff::create(['id' => 99, 'restaurant_id' => $user->restaurant_id, 'user_id' => $user->id,
            'employee_id' => 'W99', 'first_name' => 'Test', 'last_name' => 'Waiter', 'email' => $user->email,
            'position' => 'Waiter', 'department' => 'Service', 'hourly_rate' => 10, 'hire_date' => '2026-01-01']);
        $this->actingAs($user, 'api')->postJson('/api/orders', ['type' => 'takeout'])
            ->assertCreated()->assertJsonPath('waiter_id', $staff->id);
        $this->getJson('/api/orders?mine=1')->assertOk()->assertJsonPath('total', 1);
    }

    public function test_token_outside_refresh_window_is_rejected(): void
    {
        $user = $this->actor();
        $token = auth('api')->login($user);
        app('tymon.jwt.validators.payload')->setRefreshTTL(60);
        $this->travel(61)->minutes();
        auth('api')->forgetUser();
        $this->withToken($token)->postJson('/api/refresh')->assertUnauthorized();
        $this->travelBack();
    }

    public function test_expired_jwt_can_refresh_but_missing_invalid_and_disabled_tokens_cannot(): void
    {
        $user = $this->actor();
        $token = auth('api')->setTTL(1)->login($user);
        $this->travel(2)->minutes();
        auth('api')->forgetUser();
        $this->withToken($token)->getJson('/api/me')->assertUnauthorized();
        auth('api')->forgetUser();
        $response = $this->withToken($token)->postJson('/api/refresh')->assertOk();
        $fresh = $response->json('access_token');
        $this->assertNotEmpty($fresh);
        $this->assertNotEquals($token, $fresh);
        auth('api')->forgetUser();
        $this->withToken($fresh)->getJson('/api/me')->assertOk()->assertJsonPath('id', $user->id);
        auth('api')->forgetUser();
        $this->withToken('invalid')->postJson('/api/refresh')->assertUnauthorized();
        auth('api')->forgetUser();
        $this->withHeaders(['Authorization' => ''])->postJson('/api/refresh')->assertUnauthorized();
        $user->update(['is_active' => false]);
        auth('api')->forgetUser();
        $this->withToken($fresh)->postJson('/api/refresh')->assertUnauthorized();
        $this->travelBack();
    }
}
