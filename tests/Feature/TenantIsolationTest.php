<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\Table;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Restaurant $restaurantA;
    private Restaurant $restaurantB;
    private User $ownerA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->restaurantA = $this->makeRestaurant('alpha');
        $this->restaurantB = $this->makeRestaurant('bravo');
        $this->ownerA = $this->makeUser($this->restaurantA, 'Owner');
    }

    public function test_order_of_another_restaurant_is_not_found(): void
    {
        $orderB = $this->makeOrder($this->restaurantB);

        $this->actingAs($this->ownerA, 'api')->getJson("/api/orders/{$orderB->id}")->assertNotFound();
        $this->actingAs($this->ownerA, 'api')->putJson("/api/orders/{$orderB->id}", ['notes' => 'x'])->assertNotFound();
        $this->actingAs($this->ownerA, 'api')->patchJson("/api/orders/{$orderB->id}/status", ['status' => 'cancelled'])->assertNotFound();
        $this->actingAs($this->ownerA, 'api')->postJson("/api/orders/{$orderB->id}/close", ['payment_method' => 'cash'])->assertNotFound();

        $this->assertSame('pending', Order::withoutGlobalScope('tenant')->find($orderB->id)->status);
    }

    public function test_own_order_is_visible(): void
    {
        $orderA = $this->makeOrder($this->restaurantA);

        $this->actingAs($this->ownerA, 'api')
            ->getJson("/api/orders/{$orderA->id}")
            ->assertOk()
            ->assertJsonPath('id', $orderA->id);
    }

    public function test_category_of_another_restaurant_is_not_found(): void
    {
        $categoryB = Category::create(['restaurant_id' => $this->restaurantB->id, 'name' => 'Drinks']);

        $this->actingAs($this->ownerA, 'api')->getJson("/api/categories/{$categoryB->id}")->assertNotFound();
        $this->actingAs($this->ownerA, 'api')->putJson("/api/categories/{$categoryB->id}", ['name' => 'Hacked'])->assertNotFound();
        $this->actingAs($this->ownerA, 'api')->deleteJson("/api/categories/{$categoryB->id}")->assertNotFound();

        $this->assertSame('Drinks', Category::withoutGlobalScope('tenant')->find($categoryB->id)->name);
    }

    public function test_cannot_create_order_on_another_restaurants_table(): void
    {
        $tableB = Table::create(['restaurant_id' => $this->restaurantB->id, 'number' => 'B1', 'capacity' => 2]);

        $this->actingAs($this->ownerA, 'api')
            ->postJson('/api/orders', ['table_id' => $tableB->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('table_id');
    }

    public function test_order_totals_placed_at_and_mobile_payment(): void
    {
        $table = Table::create(['restaurant_id' => $this->restaurantA->id, 'number' => 'A1', 'capacity' => 4]);
        $item = $this->makeMenuItem($this->restaurantA, 10.00);

        $orderId = $this->actingAs($this->ownerA, 'api')
            ->postJson('/api/orders', [
                'table_id' => $table->id,
                'items' => [['menu_item_id' => $item->id, 'quantity' => 2]],
            ])
            ->assertCreated()
            ->json('id');

        $order = Order::find($orderId);
        $this->assertNotNull($order->placed_at);
        // 20.00 subtotal + 10% tax + 5% service charge
        $this->assertEquals(20.00, (float) $order->subtotal);
        $this->assertEquals(2.00, (float) $order->tax_amount);
        $this->assertEquals(1.00, (float) $order->service_charge_amount);
        $this->assertEquals(23.00, (float) $order->total);

        $this->actingAs($this->ownerA, 'api')
            ->patchJson("/api/orders/{$orderId}/payment", [
                'payment_method' => 'mobile',
                'payment_status' => 'completed',
            ])
            ->assertOk()
            ->assertJsonPath('payment.method', 'mobile');

        $this->assertSame('completed', $order->fresh()->status);
        $this->assertSame('available', $table->fresh()->status);
    }

    public function test_close_order_completes_it(): void
    {
        $order = $this->makeOrder($this->restaurantA);

        $this->actingAs($this->ownerA, 'api')
            ->postJson("/api/orders/{$order->id}/close", ['payment_method' => 'cash'])
            ->assertOk();

        $order->refresh();
        $this->assertSame('completed', $order->status);
        $this->assertSame(1, $order->payments()->count());
    }

    public function test_accepting_order_after_itemised_order_creates_kitchen_ticket(): void
    {
        $table = Table::create(['restaurant_id' => $this->restaurantA->id, 'number' => 'A1', 'capacity' => 4]);
        $item = $this->makeMenuItem($this->restaurantA, 5.00);

        // Creates a "KT-..." ticket
        $this->actingAs($this->ownerA, 'api')
            ->postJson('/api/orders', [
                'table_id' => $table->id,
                'items' => [['menu_item_id' => $item->id, 'quantity' => 1]],
            ])
            ->assertCreated();

        $orderId = $this->actingAs($this->ownerA, 'api')
            ->postJson('/api/orders', ['table_id' => $table->id])
            ->assertCreated()
            ->json('id');

        $this->actingAs($this->ownerA, 'api')
            ->patchJson("/api/orders/{$orderId}/status", ['status' => 'accepted'])
            ->assertOk();

        $this->assertNotNull(Order::find($orderId)->kitchenTicket);
    }

    public function test_kitchen_serve_marks_order_served(): void
    {
        $table = Table::create(['restaurant_id' => $this->restaurantA->id, 'number' => 'A1', 'capacity' => 4]);
        $item = $this->makeMenuItem($this->restaurantA, 5.00);

        $orderId = $this->actingAs($this->ownerA, 'api')
            ->postJson('/api/orders', [
                'table_id' => $table->id,
                'items' => [['menu_item_id' => $item->id, 'quantity' => 1]],
            ])
            ->json('id');
        $ticketId = Order::find($orderId)->kitchenTicket->id;

        foreach (['start', 'complete', 'serve'] as $step) {
            $this->actingAs($this->ownerA, 'api')
                ->postJson("/api/kitchen/tickets/{$ticketId}/{$step}")
                ->assertOk();
        }

        $this->assertSame('served', Order::find($orderId)->status);
    }

    public function test_reports_count_completed_orders_of_own_restaurant_only(): void
    {
        $this->makeOrder($this->restaurantA, ['status' => 'completed', 'total' => 50]);
        $this->makeOrder($this->restaurantA, ['status' => 'pending', 'total' => 999]);
        $this->makeOrder($this->restaurantB, ['status' => 'completed', 'total' => 70]);

        $this->actingAs($this->ownerA, 'api')
            ->getJson('/api/reports/summary')
            ->assertOk()
            ->assertJsonPath('sales.total_orders', 1)
            ->assertJsonPath('orders.paid_orders', 1);

        $revenue = $this->actingAs($this->ownerA, 'api')->getJson('/api/reports/summary')->json('sales.total_revenue');
        $this->assertEquals(50, (float) $revenue);
    }

    public function test_attendance_index_route_is_not_shadowed_by_staff_show(): void
    {
        $this->actingAs($this->ownerA, 'api')
            ->getJson('/api/staff/attendance')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_table_can_be_marked_out_of_order(): void
    {
        $table = Table::create(['restaurant_id' => $this->restaurantA->id, 'number' => 'A1', 'capacity' => 4]);

        $this->actingAs($this->ownerA, 'api')
            ->patchJson("/api/tables/{$table->id}/status", ['status' => 'out-of-order'])
            ->assertOk();

        $this->assertSame('out-of-order', $table->fresh()->status);
    }

    public function test_register_requires_admin_of_same_restaurant(): void
    {
        $payload = fn (Restaurant $restaurant, string $email) => [
            'name' => 'New Cashier',
            'email' => $email,
            'password' => 'secret123',
            'restaurant_id' => $restaurant->id,
        ];
        Role::findOrCreate('Cashier', 'api');

        $this->postJson('/api/register', $payload($this->restaurantA, 'anon@example.com'))->assertUnauthorized();

        $this->actingAs($this->ownerA, 'api')
            ->postJson('/api/register', $payload($this->restaurantB, 'cross@example.com'))
            ->assertForbidden();

        $this->actingAs($this->ownerA, 'api')
            ->postJson('/api/register', $payload($this->restaurantA, 'ok@example.com'))
            ->assertCreated();

        $this->assertDatabaseMissing('users', ['email' => 'cross@example.com']);
        $this->assertDatabaseHas('users', ['email' => 'ok@example.com', 'restaurant_id' => $this->restaurantA->id]);
    }

    public function test_admin_restaurant_views_only_show_that_restaurant(): void
    {
        $superAdmin = User::factory()->create(['restaurant_id' => null]);
        $superAdmin->assignRole(Role::findOrCreate('SuperAdmin', 'api'));

        Table::create(['restaurant_id' => $this->restaurantA->id, 'number' => 'A1', 'capacity' => 4]);
        Table::create(['restaurant_id' => $this->restaurantB->id, 'number' => 'B1', 'capacity' => 4]);
        Table::create(['restaurant_id' => $this->restaurantB->id, 'number' => 'B2', 'capacity' => 4]);

        $this->actingAs($superAdmin, 'api')
            ->getJson("/api/admin/restaurants/{$this->restaurantA->id}/tables")
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.number', 'A1');

        $this->actingAs($superAdmin, 'api')
            ->getJson("/api/admin/restaurants/{$this->restaurantB->id}/overview")
            ->assertOk()
            ->assertJsonPath('tables', 2);

        $this->actingAs($superAdmin, 'api')
            ->getJson("/api/admin/restaurants/{$this->restaurantA->id}/orders")
            ->assertOk();
    }

    private function makeRestaurant(string $subdomain): Restaurant
    {
        return Restaurant::create([
            'name' => ucfirst($subdomain),
            'subdomain' => $subdomain,
            'tax_rate' => 10,
            'service_charge' => 5,
        ]);
    }

    private function makeUser(Restaurant $restaurant, string $role): User
    {
        $user = User::factory()->create(['restaurant_id' => $restaurant->id]);
        $user->assignRole(Role::findOrCreate($role, 'api'));

        return $user;
    }

    private function makeMenuItem(Restaurant $restaurant, float $price): MenuItem
    {
        $category = Category::create(['restaurant_id' => $restaurant->id, 'name' => 'Food']);

        return MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'category_id' => $category->id,
            'name' => 'Dish',
            'price' => $price,
        ]);
    }

    private function makeOrder(Restaurant $restaurant, array $attributes = []): Order
    {
        $table = Table::create([
            'restaurant_id' => $restaurant->id,
            'number' => 'T' . uniqid(),
            'capacity' => 4,
        ]);

        return Order::create(array_merge([
            'restaurant_id' => $restaurant->id,
            'table_id' => $table->id,
            'status' => 'pending',
        ], $attributes));
    }
}
