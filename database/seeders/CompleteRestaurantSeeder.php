<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\CashierShift;
use App\Models\Category;
use App\Models\KitchenTicket;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Restaurant;
use App\Models\Staff;
use App\Models\Table;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Infrastructure\Tenancy\Tenant;
use RuntimeException;

abstract class CompleteRestaurantSeeder extends Seeder
{
    /** Restaurant details and category => [[dish, price], ...] menu. */
    abstract protected function definition(): array;

    public function run(): void
    {
        $this->call(RoleSeeder::class);

        DB::transaction(function () {
            $definition = $this->definition();
            $restaurant = Restaurant::firstOrCreate(['subdomain' => $definition['slug']], [
                'name' => $definition['name'],
                'address' => $definition['address'],
                'city' => $definition['city'],
                'country' => 'Morocco',
                'cuisine_type' => $definition['cuisine'],
                'email' => 'info@'.$definition['slug'].'.com',
                'timezone' => 'Africa/Casablanca',
                'currency' => 'MAD',
                'tax_rate' => 10,
                'service_charge' => 5,
                'status' => 'active',
                'is_active' => true,
                'settings' => ['language' => 'en', 'receipt_footer' => 'Thank you for visiting!'],
            ]);

            Tenant::with($restaurant->id, function () use ($restaurant, $definition) {
                $staff = $this->seedTeam($restaurant);
                $tables = $this->seedTables($restaurant);
                $menu = $this->seedMenu($restaurant, $definition['menu']);
                $now = CarbonImmutable::now();

                // Stable fixture identifiers mean re-running tomorrow adds no duplicate history.
                for ($day = 7; $day >= 1; $day--) {
                    for ($slot = 0; $slot < 3; $slot++) {
                        $this->seedOrder($restaurant, $staff, $menu, $tables[$slot],
                            "history-$day-$slot", 'completed', 'dine-in',
                            $now->subDays($day)->startOfDay()->addHours(12 + $slot * 2));
                    }
                }

                foreach (['dine-in', 'takeout', 'delivery'] as $index => $type) {
                    $this->seedOrder($restaurant, $staff, $menu, $type === 'dine-in' ? $tables[5] : null,
                        "today-$type", 'completed', $type, $now->subHours(2)->addMinutes($index * 10));
                }
                foreach (['pending', 'accepted', 'preparing', 'ready', 'served'] as $index => $status) {
                    $this->seedOrder($restaurant, $staff, $menu, $tables[$index],
                        "live-$status", $status, 'dine-in', $now->subMinutes(10 + $index * 10));
                }
                $this->seedOrder($restaurant, $staff, $menu, null,
                    'cancelled', 'cancelled', 'takeout', $now->subHours(3));

                $this->seedAttendanceAndShifts($restaurant, $staff, $now);
            });
        });
    }

    private function seedTeam(Restaurant $restaurant): array
    {
        $accounts = ['admin' => 'Owner', 'owner' => 'Owner', 'manager' => 'Manager', 'cashier1' => 'Cashier',
            'cashier2' => 'Cashier', 'waiter1' => 'Waiter', 'waiter2' => 'Waiter',
            'waiter3' => 'Waiter', 'chef1' => 'Kitchen', 'chef2' => 'Kitchen'];
        $staff = [];
        $password = Hash::make(config('app.demo_password', 'password123'));

        foreach ($accounts as $alias => $role) {
            $email = "$alias@{$restaurant->subdomain}.com";
            $user = User::withTrashed()->firstOrCreate(['email' => $email], [
                'name' => ($alias === 'admin' ? 'Restaurant Admin' : ucfirst($alias)).' - '.$restaurant->name,
                'restaurant_id' => $restaurant->id,
                'password' => $password,
                'email_verified_at' => now(),
                'is_active' => true,
            ]);
            if ($user->restaurant_id !== $restaurant->id || $user->trashed()) {
                throw new RuntimeException("Seed account $email is deleted or belongs to another restaurant.");
            }
            if ($user->roles()->doesntExist()) {
                $user->assignRole($role);
            } elseif (! $user->hasRole($role) || $user->hasRole('SuperAdmin')) {
                throw new RuntimeException("Seed account $email has an incompatible role; resolve it before seeding.");
            }
            if ($role === 'Owner') {
                continue;
            }
            $staff[$alias] = Staff::withTrashed()->firstOrCreate([
                'restaurant_id' => $restaurant->id, 'user_id' => $user->id,
            ], [
                'employee_id' => $restaurant->subdomain.'-'.$alias,
                'first_name' => ucfirst($alias), 'last_name' => 'Demo', 'email' => $email,
                'position' => $role === 'Kitchen' ? 'Chef' : $role,
                'department' => match ($role) {
                    'Kitchen' => 'Kitchen', 'Manager' => 'Management', default => 'Service'
                },
                'hourly_rate' => $role === 'Manager' ? 80 : 40,
                'hire_date' => now()->subMonths(6)->toDateString(), 'status' => 'active',
            ]);
            if ($staff[$alias]->trashed()) {
                throw new RuntimeException("Seed staff record $email is deleted.");
            }
        }

        return $staff;
    }

    private function seedTables(Restaurant $restaurant): array
    {
        $tables = [];
        for ($number = 1; $number <= 10; $number++) {
            $table = Table::withTrashed()->firstOrCreate([
                'restaurant_id' => $restaurant->id, 'number' => sprintf('T%02d', $number),
            ], [
                'capacity' => $number % 3 === 0 ? 6 : 4,
                'status' => match ($number) {
                    9 => 'reserved', 10 => 'out-of-order', default => 'available'
                },
                'section' => $number <= 6 ? 'Main Hall' : 'Terrace',
                'shape' => $number % 2 === 0 ? 'round' : 'square',
                'grid_x' => ($number - 1) % 5, 'grid_y' => intdiv($number - 1, 5),
            ]);
            if ($table->trashed()) {
                throw new RuntimeException("Seed table {$table->number} is deleted.");
            }
            $tables[] = $table;
        }

        return $tables;
    }

    private function seedMenu(Restaurant $restaurant, array $categories): array
    {
        $menu = [];
        foreach ($categories as $name => $dishes) {
            $category = Category::withTrashed()->firstOrCreate([
                'restaurant_id' => $restaurant->id, 'name' => $name,
            ], ['description' => "$name at {$restaurant->name}", 'sort_order' => count($menu), 'is_active' => true]);
            if ($category->trashed()) {
                throw new RuntimeException("Seed category $name is deleted.");
            }
            foreach ($dishes as [$dish, $price]) {
                $menu[] = MenuItem::firstOrCreate([
                    'restaurant_id' => $restaurant->id, 'category_id' => $category->id, 'name' => $dish,
                ], [
                    'price' => $price, 'cost' => round($price * 0.35, 2),
                    'description' => "Freshly prepared $dish", 'preparation_time' => 15,
                    'is_available' => true, 'is_active' => true,
                ]);
            }
        }

        return $menu;
    }

    private function seedOrder(Restaurant $restaurant, array $staff, array $menu, ?Table $table,
        string $key, string $status, string $type, CarbonImmutable $placedAt): void
    {
        $notes = "[demo:{$restaurant->subdomain}:$key]";
        if (Order::where('restaurant_id', $restaurant->id)->where('notes', $notes)->exists()) {
            return; // Preserve orders that have been worked on through the POS.
        }
        $offset = crc32($key) % count($menu);
        $items = [[$menu[$offset], 2], [$menu[($offset + 1) % count($menu)], 1]];
        $subtotal = round(array_sum(array_map(fn ($row) => (float) $row[0]->price * $row[1], $items)), 2);
        $tax = round($subtotal * $restaurant->tax_rate / 100, 2);
        $service = round($subtotal * $restaurant->service_charge / 100, 2);
        $discount = $status === 'completed' ? 5 : 0;
        $total = round(max(0, $subtotal + $tax + $service - $discount), 2);
        $paidAt = $status === 'completed' ? $placedAt->addMinutes(45) : null;
        $method = ['cash', 'card', 'mobile'][$offset % 3];
        $order = Order::create([
            'restaurant_id' => $restaurant->id, 'table_id' => $table?->id,
            'waiter_id' => $staff['waiter'.(1 + $offset % 3)]->id,
            'type' => $type, 'status' => $status, 'priority' => $status === 'ready' ? 'rush' : 'normal',
            'subtotal' => $subtotal, 'tax_amount' => $tax, 'service_charge_amount' => $service,
            'discount_amount' => $discount, 'total' => $total,
            'payment_method' => $paidAt ? $method : null, 'placed_at' => $placedAt, 'paid_at' => $paidAt,
            'paid_by' => $paidAt ? $staff['cashier1']->user_id : null, 'notes' => $notes,
        ]);
        $itemState = match ($status) {
            'preparing' => 'preparing', 'ready' => 'ready', 'served', 'completed' => 'served', default => 'pending',
        };
        OrderItem::withoutEvents(function () use ($order, $items, $itemState) {
            foreach ($items as [$item, $quantity]) {
                OrderItem::create(['order_id' => $order->id, 'menu_item_id' => $item->id,
                    'quantity' => $quantity, 'unit_price' => $item->price, 'state' => $itemState]);
            }
        });
        if ($status !== 'cancelled') {
            KitchenTicket::create([
                'restaurant_id' => $restaurant->id, 'order_id' => $order->id,
                'ticket_number' => 'DEMO-'.$order->id, 'status' => $itemState,
                'priority' => $order->priority, 'assigned_chef_id' => $staff['chef1']->id,
                'cooking_station' => 'Main Kitchen',
                'started_at' => $itemState !== 'pending' ? $placedAt->addMinutes(2) : null,
                'completed_at' => in_array($itemState, ['ready', 'served']) ? $placedAt->addMinutes(15) : null,
                'bumped_at' => $itemState === 'served' ? $placedAt->addMinutes(18) : null,
                'preparation_time' => in_array($itemState, ['ready', 'served']) ? 13 : null,
            ]);
        }
        if ($paidAt) {
            Payment::create(['order_id' => $order->id, 'amount' => $total, 'method' => $method,
                'paid_at' => $paidAt, 'transaction_id' => 'DEMO-'.$order->id]);
        } elseif ($status !== 'cancelled' && $table) {
            $table->update(['status' => 'occupied']);
        }
    }

    private function seedAttendanceAndShifts(Restaurant $restaurant, array $staff, CarbonImmutable $now): void
    {
        foreach ($staff as $member) {
            // Clock-out edits notes, so those notes cannot identify fixtures reliably on rerun.
            if (Attendance::where('restaurant_id', $restaurant->id)->where('staff_id', $member->id)->exists()) {
                continue;
            }
            foreach (['previous', 'current'] as $period) {
                $clockIn = $period === 'previous' ? $now->subDay()->startOfDay()->addHours(9) : $now->subHours(4);
                Attendance::firstOrCreate([
                    'restaurant_id' => $restaurant->id, 'staff_id' => $member->id, 'notes' => "[demo:$period]",
                ], ['clock_in' => $clockIn, 'clock_out' => $period === 'previous' ? $clockIn->addHours(8) : null,
                    'break_minutes' => $period === 'previous' ? 30 : 0, 'hours_worked' => $period === 'previous' ? 7.5 : null]);
            }
        }
        if (CashierShift::where('restaurant_id', $restaurant->id)->where('user_id', $staff['cashier1']->user_id)->exists()) {
            return;
        }
        foreach (['previous', 'current'] as $period) {
            $opened = $period === 'previous' ? $now->subDay()->startOfDay()->addHours(9) : $now->subHours(4);
            $closed = $period === 'previous' ? $opened->addHours(8) : null;
            $cash = Order::where('restaurant_id', $restaurant->id)->where('status', 'completed')
                ->where('payment_method', 'cash')->whereBetween('paid_at', [$opened, $closed ?? $now])->sum('total');
            CashierShift::firstOrCreate([
                'restaurant_id' => $restaurant->id, 'user_id' => $staff['cashier1']->user_id, 'notes' => "[demo:$period]",
            ], ['opened_at' => $opened, 'closed_at' => $closed, 'opening_amount' => 500,
                'closing_amount' => $closed ? round(500 + $cash, 2) : null]);
        }
    }
}
