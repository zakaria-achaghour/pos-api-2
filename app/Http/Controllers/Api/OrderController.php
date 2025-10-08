<?php
// filepath: app/Http/Controllers/Api/OrderController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\MenuItem;
use App\Models\KitchenTicket;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Infrastructure\Tenancy\Tenant;
use App\Events\OrderStatusUpdated;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Orders",
 *     description="Order management operations for restaurant"
 * )
 */
class OrderController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/orders",
     *     tags={"Orders"},
     *     summary="Get list of orders",
     *     description="Retrieve a paginated list of orders with filtering options",
     *     security={{"bearer_token": {}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, example=1)
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by order status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"open", "preparing", "ready", "served", "paid", "cancelled"})
     *     ),
     *     @OA\Parameter(
     *         name="table_id",
     *         in="query",
     *         description="Filter by table ID",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="waiter_id",
     *         in="query",
     *         description="Filter by waiter ID",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="date_from",
     *         in="query",
     *         description="Filter orders from this date",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2024-01-15")
     *     ),
     *     @OA\Parameter(
     *         name="date_to",
     *         in="query",
     *         description="Filter orders until this date",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2024-01-20")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Orders retrieved successfully",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/PaginatedResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(
     *                         property="data",
     *                         type="array",
     *                         @OA\Items(ref="#/components/schemas/Order")
     *                     )
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = Order::with(['table', 'waiter', 'orderItems.menuItem'])
            ->where('restaurant_id', Tenant::id());

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('table_id')) {
            $query->where('table_id', $request->table_id);
        }

        if ($request->has('waiter_id')) {
            $query->where('waiter_id', $request->waiter_id);
        }

        if ($request->has('date_from')) {
            $query->where('placed_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('placed_at', '<=', $request->date_to);
        }

        $orders = $query->orderBy('placed_at', 'desc')->paginate();

        return response()->json($orders);
    }

    /**
     * @OA\Post(
     *     path="/api/orders",
     *     tags={"Orders"},
     *     summary="Create a new order",
     *     description="Create a new order with optional items",
     *     security={{"bearer_token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/CreateOrderRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Order created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Order")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request - Validation failed",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function store(CreateOrderRequest $request): JsonResponse
    {
        $data = $request->validated();
        
        $order = DB::transaction(function () use ($data) {
            $order = Order::create($data);
            
            // Create kitchen ticket if order has items
            if (!empty($data['items'])) {
                $this->addItemsToOrder($order, $data['items']);
                $this->createKitchenTicket($order);
            }
            
            return $order;
        });

        $order->load(['table', 'waiter', 'orderItems.menuItem']);

        event(new OrderStatusUpdated($order, 'placed'));

        return response()->json($order, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/orders/{id}",
     *     tags={"Orders"},
     *     summary="Get a specific order",
     *     description="Retrieve details of a specific order with related data",
     *     security={{"bearer_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Order ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order retrieved successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Order")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Insufficient permissions",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function show(Order $order): JsonResponse
    {
        $this->authorize('view', $order);
        
        $order->load([
            'table',
            'waiter',
            'orderItems.menuItem.category',
            'kitchenTicket.assignedChef'
        ]);

        return response()->json($order);
    }

    /**
     * @OA\Post(
     *     path="/api/orders/{id}/items",
     *     tags={"Orders"},
     *     summary="Add item to order",
     *     description="Add a menu item to an existing order",
     *     security={{"bearer_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Order ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/AddOrderItemRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Item added to order successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Item added to order"),
     *             @OA\Property(property="order_item", ref="#/components/schemas/OrderItem"),
     *             @OA\Property(property="order_total", type="number", format="decimal", example="28.05")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request - Validation failed",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Insufficient permissions",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order or menu item not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function addItem(Request $request, Order $order): JsonResponse
    {
        $this->authorize('update', $order);
        
        $request->validate([
            'menu_item_id' => 'required|exists:menu_items,id',
            'quantity' => 'required|integer|min:1',
            'special_instructions' => 'nullable|string|max:500',
        ]);

        $menuItem = MenuItem::where('restaurant_id', Tenant::id())
            ->findOrFail($request->menu_item_id);

        $orderItem = $order->orderItems()->create([
            'menu_item_id' => $menuItem->id,
            'quantity' => $request->quantity,
            'unit_price' => $menuItem->price,
            'special_instructions' => $request->special_instructions,
        ]);

        $this->recalculateOrderTotal($order);

        $orderItem->load('menuItem');

        return response()->json([
            'message' => 'Item added to order',
            'order_item' => $orderItem,
            'order_total' => $order->fresh()->total
        ]);
    }

    public function updateItem(Request $request, Order $order, OrderItem $orderItem): JsonResponse
    {
        $this->authorize('update', $order);
        
        if ($orderItem->order_id !== $order->id) {
            return response()->json(['message' => 'Order item not found'], 404);
        }

        $request->validate([
            'quantity' => 'required|integer|min:1',
            'special_instructions' => 'nullable|string|max:500',
        ]);

        $orderItem->update($request->only(['quantity', 'special_instructions']));
        $this->recalculateOrderTotal($order);

        return response()->json([
            'message' => 'Item updated',
            'order_item' => $orderItem->fresh('menuItem'),
            'order_total' => $order->fresh()->total
        ]);
    }

    public function removeItem(Order $order, OrderItem $orderItem): JsonResponse
    {
        $this->authorize('update', $order);
        
        if ($orderItem->order_id !== $order->id) {
            return response()->json(['message' => 'Order item not found'], 404);
        }

        $orderItem->delete();
        $this->recalculateOrderTotal($order);

        return response()->json([
            'message' => 'Item removed from order',
            'order_total' => $order->fresh()->total
        ]);
    }

    public function close(Request $request, Order $order): JsonResponse
    {
        $this->authorize('update', $order);
        
        $request->validate([
            'payment_method' => 'required|in:cash,card,mobile',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
        ]);

        if ($order->status !== 'open') {
            return response()->json([
                'message' => 'Order cannot be closed in current status'
            ], 422);
        }

        $order->update([
            'status' => 'paid',
            'payment_method' => $request->payment_method,
            'discount_amount' => $request->discount_amount ?? 0,
            'tax_amount' => $request->tax_amount ?? 0,
            'paid_at' => now(),
        ]);

        $this->recalculateOrderTotal($order);

        // Update table status if applicable
        if ($order->table) {
            $order->table->update(['status' => 'available']);
        }

        event(new OrderStatusUpdated($order, 'paid'));

        return response()->json([
            'message' => 'Order closed successfully',
            'order' => $order->fresh()
        ]);
    }

    private function addItemsToOrder(Order $order, array $items): void
    {
        foreach ($items as $item) {
            $menuItem = MenuItem::where('restaurant_id', Tenant::id())
                ->findOrFail($item['menu_item_id']);

            $order->orderItems()->create([
                'menu_item_id' => $menuItem->id,
                'quantity' => $item['quantity'],
                'unit_price' => $menuItem->price,
                'special_instructions' => $item['special_instructions'] ?? null,
            ]);
        }

        $this->recalculateOrderTotal($order);
    }

    private function createKitchenTicket(Order $order): void
    {
        $ticketNumber = 'KT-' . $order->id . '-' . now()->format('His');
        
        KitchenTicket::create([
            'restaurant_id' => $order->restaurant_id,
            'order_id' => $order->id,
            'ticket_number' => $ticketNumber,
            'priority' => $order->priority,
            'special_instructions' => $order->notes,
        ]);
    }

    private function recalculateOrderTotal(Order $order): void
    {
        $subtotal = $order->orderItems()->sum(DB::raw('quantity * unit_price'));
        $total = $subtotal + $order->tax_amount - $order->discount_amount;
        
        $order->update([
            'subtotal' => $subtotal,
            'total' => max(0, $total), // Ensure total is not negative
        ]);
    }
}