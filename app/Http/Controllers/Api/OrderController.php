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
use App\Models\Table;
use Barryvdh\DomPDF\Facade\Pdf;
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
     *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string"), description="Search ID (including ORD- prefix), notes, table number or menu item name"),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", minimum=1, maximum=100, default=15)),
     *     @OA\Parameter(name="mine", in="query", @OA\Schema(type="boolean"), description="Only orders assigned to the authenticated user's staff record"),
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
     *         @OA\Schema(type="string", enum={"active", "pending", "accepted", "preparing", "ready", "served", "completed", "cancelled"})
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filter by order type",
     *         required=false,
     *         @OA\Schema(type="string", enum={"dine-in", "takeout", "delivery"})
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
        $request->validate([
            'search' => 'nullable|string|max:200',
            'status' => 'nullable|in:active,pending,accepted,preparing,ready,served,completed,cancelled',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'mine' => 'sometimes|boolean',
        ]);

        $query = Order::with(['table', 'waiter', 'orderItems.menuItem'])
            ->where('restaurant_id', Tenant::id());

        if ($request->status === 'active') {
            $query->whereNotIn('status', ['completed', 'cancelled']);
        } elseif ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->boolean('mine')) {
            $query->whereHas('waiter', fn ($waiter) => $waiter
                ->where('restaurant_id', Tenant::id())
                ->where('user_id', $request->user('api')->id));
        }

        if ($request->filled('search')) {
            $term = trim($request->string('search')->toString());
            $query->where(function ($search) use ($term) {
                $search->whereRaw("LOWER(COALESCE(notes, '')) LIKE ?", ['%'.mb_strtolower($term).'%'])
                    ->orWhereHas('table', fn ($table) => $table->whereRaw('LOWER(number) LIKE ?', ['%'.mb_strtolower($term).'%']))
                    ->orWhereHas('orderItems.menuItem', fn ($item) => $item->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($term).'%']));
                $id = preg_replace('/^(?:ORD-|#)/i', '', $term);
                if (ctype_digit($id)) {
                    $search->orWhere('orders.id', (int) $id);
                }
            });
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
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

        $orders = $query->orderBy('placed_at', 'desc')->orderBy('id', 'desc')->paginate($request->integer('per_page', 15))->withQueryString();

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
        if (empty($data['waiter_id']) && $request->user('api')->hasRole('Waiter')) {
            $data['waiter_id'] = \App\Models\Staff::where('restaurant_id', Tenant::id())
                ->where('user_id', $request->user('api')->id)->value('id');
        }

        $order = DB::transaction(function () use ($data) {
            $order = Order::create($data);
            
            // Mark table as occupied when order is created
            if (!empty($data['table_id'])) {
                Table::where('id', $data['table_id'])
                    ->update(['status' => 'occupied']);
            }
            
            // Create kitchen ticket if order has items
            if (!empty($data['items'])) {
                $this->addItemsToOrder($order, $data['items']);
                $this->createKitchenTicket($order);
            }
            
            $this->recalculateOrderTotal($order);

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
        // Operational endpoint - just check if user is authenticated
        if (!auth()->check()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        
        $order->load([
            'table',
            'waiter',
            'orderItems.menuItem.category',
            'kitchenTicket.assignedChef'
        ]);

        return response()->json($order);
    }

    /**
     * @OA\Put(
     *     path="/api/orders/{order}",
     *     tags={"Orders"},
     *     summary="Update an order",
     *     description="Update order details such as waiter, type, status, priority, or notes",
     *     security={{"bearer_token": {}}},
     *     @OA\Parameter(
     *         name="order",
     *         in="path",
     *         description="Order ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=286)
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="waiter_id", type="integer", example=5, description="ID of the assigned waiter"),
     *             @OA\Property(property="type", type="string", enum={"dine-in", "takeout", "delivery"}, example="dine-in"),
     *             @OA\Property(property="status", type="string", enum={"pending", "accepted", "preparing", "ready", "served", "completed", "cancelled"}, example="accepted"),
     *             @OA\Property(property="priority", type="string", enum={"normal", "rush", "urgent"}, example="normal"),
     *             @OA\Property(property="notes", type="string", example="Customer has allergies", description="Order notes or special instructions")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Order updated successfully"),
     *             @OA\Property(property="order", ref="#/components/schemas/Order")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function update(UpdateOrderRequest $request, Order $order): JsonResponse
    {
        // Operational endpoint - just check if user is authenticated
        if (!auth()->check()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Prevent editing if order is completed or cancelled
        if (in_array($order->status, ['completed', 'cancelled'])) {
            return response()->json([
                'message' => 'Cannot update a completed or cancelled order',
                'current_status' => $order->status
            ], 422);
        }

        // Update only the fields that are present in the request
        $order->update($request->validated());
        if ($request->has('discount_amount')) {
            $this->recalculateOrderTotal($order);
        }

        // If status changed, trigger event
        if ($request->has('status') && $request->status !== $order->getOriginal('status')) {
            event(new OrderStatusUpdated($order, $request->status));
        }

        return response()->json([
            'message' => 'Order updated successfully',
            'order' => $order->fresh(['table', 'waiter', 'orderItems.menuItem'])
        ]);
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
        // Operational endpoint - just check if user is authenticated
        if (!auth()->check()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        
        // Prevent editing if order is already being prepared
        if (in_array($order->status, ['preparing', 'ready', 'served', 'completed'])) {
            return response()->json([
                'message' => 'Cannot modify order. Order is already being prepared or has been completed.',
                'current_status' => $order->status
            ], 422);
        }

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
        // Operational endpoint - just check if user is authenticated
        if (!auth()->check()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        
        // Prevent editing if order is already being prepared
        if (in_array($order->status, ['preparing', 'ready', 'served', 'completed'])) {
            return response()->json([
                'message' => 'Cannot modify order. Order is already being prepared or has been completed.',
                'current_status' => $order->status
            ], 422);
        }

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
        if (!auth()->check()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        
        // Prevent editing if order is already being prepared
        if (in_array($order->status, ['preparing', 'ready', 'served', 'completed'])) {
            return response()->json([
                'message' => 'Cannot modify order. Order is already being prepared or has been completed.',
                'current_status' => $order->status
            ], 422);
        }

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
        if (!auth()->check()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        
        $request->validate([
            'payment_method' => 'required|in:cash,card,mobile',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
        ]);

        if (in_array($order->status, ['completed', 'cancelled'])) {
            return response()->json([
                'message' => 'Order cannot be closed in current status',
                'current_status' => $order->status
            ], 422);
        }

        DB::transaction(function () use ($request, $order) {
            $order->update([
                'status' => 'completed',
                'payment_method' => $request->payment_method,
                'discount_amount' => $request->discount_amount ?? $order->discount_amount ?? 0,
                'tax_amount' => $request->tax_amount ?? 0,
                'paid_at' => now(),
                'paid_by' => auth()->id(),
            ]);

            $this->recalculateOrderTotal($order);
            $order->refresh();

            // Update table status if applicable
            if ($order->table) {
                $order->table->update(['status' => 'available']);
            }

            $this->syncPaymentRecord($order, [
                'amount' => $order->total,
                'method' => $order->payment_method,
                'paid_at' => $order->paid_at,
            ]);
        });

        event(new OrderStatusUpdated($order, 'completed'));

        return response()->json([
            'message' => 'Order closed successfully',
            'order' => $order->fresh()
        ]);
    }

    /**
     * @OA\Patch(
     *     path="/api/orders/{order}/payment",
     *     tags={"Orders"},
     *     summary="Update payment information for an order",
     *     description="Update payment details including payment method, status, amount received, and tip",
     *     security={{"bearer_token": {}}},
     *     @OA\Parameter(
     *         name="order",
     *         in="path",
     *         description="Order ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"payment_method", "payment_status"},
     *             @OA\Property(property="payment_method", type="string", enum={"cash", "card", "mobile"}, example="cash"),
     *             @OA\Property(property="discount_amount", type="number", minimum=0, description="Absolute discount; recalculates order total"),
     *             @OA\Property(property="payment_status", type="string", enum={"pending", "completed", "failed", "refunded"}, example="completed"),
     *             @OA\Property(property="amount_received", type="number", format="float", example=500.00, description="Amount received from customer"),
     *             @OA\Property(property="tip_amount", type="number", format="float", example=13.52, description="Tip amount"),
     *             @OA\Property(property="transaction_id", type="string", example="TXN123456", description="Payment transaction ID")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Payment updated successfully"),
     *             @OA\Property(property="order", ref="#/components/schemas/Order"),
     *             @OA\Property(property="payment", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="order_id", type="integer", example=1),
     *                 @OA\Property(property="amount", type="number", example=500.00),
     *                 @OA\Property(property="method", type="string", example="cash"),
     *                 @OA\Property(property="transaction_id", type="string", example="TXN123456"),
     *                 @OA\Property(property="paid_at", type="string", format="date-time", example="2024-01-15T10:30:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function updatePayment(Request $request, Order $order): JsonResponse
    {
        // Operational endpoint - just check if user is authenticated
        if (!auth()->check()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $validated = $request->validate([
            'payment_method' => 'required|in:cash,card,mobile',
            'payment_status' => 'required|in:pending,completed,failed,refunded',
            'amount_received' => 'nullable|numeric|min:0',
            'tip_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'sometimes|numeric|min:0',
            'transaction_id' => 'nullable|string|max:255',
        ]);

        // Calculate total with tip if provided
        $tipAmount = $validated['tip_amount'] ?? 0;
        $totalWithTip = 0;

        $payment = DB::transaction(function () use ($validated, $order, $tipAmount, &$totalWithTip) {
            if (array_key_exists('discount_amount', $validated)) {
                $order->update(['discount_amount' => $validated['discount_amount']]);
                $this->recalculateOrderTotal($order);
                $order->refresh();
            }
            $totalWithTip = round($order->total + $tipAmount, 2);
            // Update order payment details
            $order->update([
                'payment_method' => $validated['payment_method'],
            ]);

            // If payment status is completed, mark order as paid
            if ($validated['payment_status'] === 'completed') {
                $order->update([
                    'status' => 'completed',
                    'paid_at' => now(),
                    'paid_by' => auth()->id(),
                ]);

                // Update table status if applicable
                if ($order->table) {
                    $order->table->update(['status' => 'available']);
                }
            }

            // Create or update payment record
            return $this->syncPaymentRecord($order->fresh(), [
                'amount' => $validated['amount_received'] ?? $totalWithTip,
                'method' => $validated['payment_method'],
                'transaction_id' => $validated['transaction_id'] ?? null,
                'paid_at' => $validated['payment_status'] === 'completed' ? now() : null,
            ]);
        });

        if ($validated['payment_status'] === 'completed') {
            event(new OrderStatusUpdated($order, 'completed'));
        }

        return response()->json([
            'message' => 'Payment updated successfully',
            'order' => $order->fresh(['table', 'waiter', 'orderItems.menuItem']),
            'payment' => $payment,
            'change_due' => max(0, ($validated['amount_received'] ?? 0) - $totalWithTip),
        ]);
    }

    /**
     * @OA\Patch(
     *     path="/api/orders/{order}/status",
     *     tags={"Orders"},
     *     summary="Update order status",
     *     description="Update the status of an order. When status changes to 'accepted', a kitchen ticket is automatically created for the order if it doesn't already have one.",
     *     security={{"bearer_token": {}}},
     *     @OA\Parameter(
     *         name="order",
     *         in="path",
     *         description="Order ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=286)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 enum={"pending", "accepted", "preparing", "ready", "served", "completed", "cancelled"},
     *                 example="accepted",
     *                 description="New status for the order. Setting status to 'accepted' automatically creates a kitchen ticket."
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order status updated successfully. If status was changed to 'accepted', a kitchen ticket has been created automatically.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Order status updated successfully"),
     *             @OA\Property(property="order", ref="#/components/schemas/Order")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        // Operational endpoint - just check if user is authenticated
        if (!auth()->check()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $validated = $request->validate([
            'status' => 'required|in:pending,accepted,preparing,ready,served,completed,cancelled',
        ]);

        $oldStatus = $order->status;
        $newStatus = $validated['status'];

        // Prevent invalid status transitions (optional business logic)
        // You can customize this based on your business rules
        if ($oldStatus === 'completed' || $oldStatus === 'cancelled') {
            return response()->json([
                'message' => 'Cannot update status of a completed or cancelled order',
                'current_status' => $oldStatus
            ], 422);
        }

        // Update the order status
        $order->update([
            'status' => $newStatus,
        ]);

        // If order is accepted, create a kitchen ticket
        if ($newStatus === 'accepted' && !$order->kitchenTicket) {
            $this->createKitchenTicket($order);
        }

        // If order is completed, mark it as paid if not already
        if ($newStatus === 'completed') {
            if (!$order->paid_at) {
                $order->update(['paid_at' => now()]);
            }

            $order->refresh();

            if ($order->payment_method && !$order->payments()->exists()) {
                $this->syncPaymentRecord($order, [
                    'amount' => $order->total,
                    'method' => $order->payment_method,
                    'paid_at' => $order->paid_at,
                ]);
            }
        }

        // If order is cancelled, free up the table
        if ($newStatus === 'cancelled' && $order->table) {
            $order->table->update(['status' => 'available']);
        }

        // Trigger event for status change
        event(new OrderStatusUpdated($order, $newStatus));

        return response()->json([
            'message' => 'Order status updated successfully',
            'order' => $order->fresh(['table', 'waiter', 'orderItems.menuItem']),
            'previous_status' => $oldStatus,
            'new_status' => $newStatus,
        ]);
    }

    /**
     * Provide a printable receipt (HTML or PDF) for the given order.
     */
    public function receipt(Request $request, Order $order)
    {
        if (!auth()->check()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $user = auth()->user();
        $tenantId = Tenant::id();

        if ($tenantId && $order->restaurant_id !== $tenantId && !$user->hasRole('SuperAdmin')) {
            abort(404);
        }

        $order->loadMissing([
            'restaurant',
            'table',
            'waiter',
            'orderItems.menuItem',
        ]);

        $restaurant = $order->restaurant;
        $company = [
            'name' => $restaurant->name ?? config('app.name', 'Restaurant'),
            'address' => collect([
                $restaurant->address ?? null,
                $restaurant->city ?? null,
                $restaurant->country ?? null,
            ])->filter()->implode(', '),
            'phone' => $restaurant->phone ?? null,
            'logo' => $restaurant->logo_url ?? null,
        ];

        $data = [
            'order' => $order,
            'company' => $company,
            'tableLabel' => $order->table?->number
                ? 'Table ' . $order->table->number
                : ($order->type ? ucfirst($order->type) : 'Takeaway'),
            'printedAt' => now()->setTimezone($restaurant->timezone ?? config('app.timezone')),
            'currency' => $restaurant->currency ?? 'USD',
            'taxAmount' => (float) ($order->tax_amount ?? 0),
            'serviceCharge' => (float) data_get($order, 'service_charge_amount', $restaurant->service_charge ?? 0),
            'discountAmount' => (float) ($order->discount_amount ?? 0),
            'autoPrint' => $request->boolean('auto_print'),
        ];

        if ($request->get('format') === 'pdf') {
            $pdf = Pdf::loadView('receipts.default', $data);
            $filename = sprintf('receipt-%s.pdf', $order->order_number ?? $order->id);

            return $pdf->stream($filename, ['Attachment' => false]);
        }

        return response()->view('receipts.default', $data);
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
                'removed_ingredients' => $item['removed_ingredients'] ?? null,
                'added_extras' => $item['added_extras'] ?? null,
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
            'priority' => $order->priority ?? 'normal',
            'special_instructions' => $order->notes,
        ]);
    }

    private function recalculateOrderTotal(Order $order): void
    {
        $order->loadMissing('restaurant');
        $subtotal = $order->orderItems()->sum(DB::raw('quantity * unit_price'));
        $discount = $order->discount_amount ?? 0;

        $taxRate = $order->restaurant?->tax_rate ?? 0;
        $serviceRate = $order->restaurant?->service_charge ?? 0;

        $taxAmount = round($subtotal * ($taxRate / 100), 2);
        $serviceChargeAmount = round($subtotal * ($serviceRate / 100), 2);
        
        $order->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'service_charge_amount' => $serviceChargeAmount,
            'total' => max(0, $subtotal + $taxAmount + $serviceChargeAmount - $discount),
        ]);
    }

    private function syncPaymentRecord(Order $order, array $payload): ?\App\Models\Payment
    {
        $amount = $payload['amount'] ?? $order->total;
        $method = $payload['method'] ?? $order->payment_method;

        if ($amount === null || $method === null) {
            return null;
        }

        return $order->payments()->updateOrCreate(
            ['order_id' => $order->id],
            [
                'amount' => $amount,
                'method' => $method,
                'transaction_id' => $payload['transaction_id'] ?? null,
                'paid_at' => $payload['paid_at'] ?? now(),
            ]
        );
    }
}
