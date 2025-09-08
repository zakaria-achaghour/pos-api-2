<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\{PlaceOrderRequest, AddItemRequest, UpdateItemRequest, CloseOrderRequest};
use App\Models\{Order, OrderItem, MenuItem, Table};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Infrastructure\Tenancy\Tenant;

class OrderController extends Controller
{

    public function __construct() {
        // $this->middleware(['role:Owner|Manager'])->only(['store','update','destroy']); // for menu/tables
        // For OrderController:
        // $this->middleware(['role:Owner|Manager|Cashier|Waiter'])->only(['store','addItem','updateItem','removeItem','close']);
    }

    public function index(Request $request)
    {
        $q = Order::where('restaurant_id', Tenant::id())
                ->with(['table','user'])
                ->orderByDesc('id');

        if ($request->has('status')) {
            $q->where('status', $request->query('status'));
        }

        return $q->paginate();
    }

    public function store(PlaceOrderRequest $request)
    {
        $tableId = $request->validated()['table_id'] ?? null;

        if ($tableId) {
            abort_unless(
                Table::where('id', $tableId)->where('restaurant_id', Tenant::id())->exists(),
                404
            );
        }

        $order = Order::create([
            'restaurant_id' => Tenant::id(),
            'table_id'      => $tableId,
            'user_id'       => auth('api')->id(),
            'order_number'  => now()->format('Ymd-His-') . strtoupper(str()->random(4)),
            'status'        => 'open',
            'total'         => 0,
            'placed_at'     => now(),
        ]);

        return response()->json($order->load(['table','user']), 201);
    }

    public function show(Order $order)
    {
        $this->assertTenant($order);
        return $order->load(['items.menuItem','payments','table','user']);
    }

    public function addItem(Order $order, AddItemRequest $r)
    {
        $this->assertTenant($order);
        abort_unless($order->status === 'open', 422, 'Order is not open');

        $data = $r->validated();

        $menuItem = MenuItem::where('id', $data['menu_item_id'])
                    ->where('restaurant_id', Tenant::id())
                    ->where('is_active', true)->firstOrFail();

        DB::transaction(function () use ($order, $menuItem, $data) {
            OrderItem::create([
                'order_id'    => $order->id,
                'menu_item_id'=> $menuItem->id,
                'quantity'    => $data['quantity'],
                'price'       => $menuItem->price, // snapshot
                'notes'       => $data['notes'] ?? null,
            ]);

            $sum = OrderItem::where('order_id', $order->id)
                    ->selectRaw('COALESCE(SUM(price * quantity),0) as s')
                    ->value('s') ?? 0;

            $order->update(['total' => $sum]);
        });

        return $order->fresh()->load('items.menuItem');
    }

    public function updateItem(Order $order, OrderItem $orderItem, UpdateItemRequest $request)
    {
        $this->assertTenant($order);
        abort_unless($order->status === 'open', 422, 'Order is not open');
        abort_unless($orderItem->order_id === $order->id, 404);

        DB::transaction(function () use ($order, $orderItem, $r) {
            $orderItem->update($request->validated());

            $sum = OrderItem::where('order_id', $order->id)
                    ->selectRaw('COALESCE(SUM(price * quantity),0) as s')
                    ->value('s') ?? 0;

            $order->update(['total' => $sum]);
        });

        return $order->fresh()->load('items.menuItem');
    }

    public function removeItem(Order $order, OrderItem $orderItem)
    {
        $this->assertTenant($order);
        abort_unless($order->status === 'open', 422, 'Order is not open');
        abort_unless($orderItem->order_id === $order->id, 404);

        DB::transaction(function () use ($order, $orderItem) {
            $orderItem->delete();

            $sum = OrderItem::where('order_id', $order->id)
                    ->selectRaw('COALESCE(SUM(price * quantity),0) as s')
                    ->value('s') ?? 0;

            $order->update(['total' => $sum]);
        });

        return response()->json($order->fresh()->load('items.menuItem'));
    }

    public function close(Order $order, CloseOrderRequest $request)
    {
        $this->assertTenant($order);
        abort_unless($order->status === 'open', 422, 'Order already closed');

        $data = $request->validated();

        return DB::transaction(function () use ($order, $data) {
            // recompute total server-side
            $sum = OrderItem::where('order_id', $order->id)
                    ->selectRaw('COALESCE(SUM(price * quantity),0) as s')
                    ->value('s') ?? 0;

            abort_unless(abs($sum - $data['amount']) < 0.01, 422, 'Amount mismatch');

            $order->payments()->create([
                'amount' => $sum,
                'method' => $data['method'],
                'paid_at'=> now(),
            ]);

            $order->update([
                'status'   => 'paid',
                'total'    => $sum,
                'closed_at'=> now(),
            ]);

            return $order->fresh()->load(['items.menuItem','payments']);
        });
    }

    private function assertTenant(Order $order): void
    {
        abort_unless($order->restaurant_id === Tenant::id(), 404);
    }
}
