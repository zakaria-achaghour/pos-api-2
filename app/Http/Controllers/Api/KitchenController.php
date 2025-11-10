<?php
// filepath: app/Http/Controllers/Api/KitchenController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KitchenTicket;
use App\Models\Order;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Infrastructure\Tenancy\Tenant;
use App\Events\OrderStatusUpdated;

class KitchenController extends Controller
{
    public function __construct()
    {
        // $this->middleware(['role:Kitchen|Manager|Owner']);
    }

    /**
     * @OA\Get(
     *     path="/api/kitchen/tickets",
     *     tags={"Kitchen Management"},
     *     summary="Get kitchen tickets",
     *     description="Retrieve list of kitchen tickets with optional filtering and pagination",
     *     security={{"bearer_token": {}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by ticket status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"pending", "preparing", "ready", "served"})
     *     ),
     *     @OA\Parameter(
     *         name="priority",
     *         in="query",
     *         description="Filter by ticket priority",
     *         required=false,
     *         @OA\Schema(type="string", enum={"normal", "rush", "urgent"})
     *     ),
     *     @OA\Parameter(
     *         name="cooking_station",
     *         in="query",
     *         description="Filter by cooking station",
     *         required=false,
     *         @OA\Schema(type="string", example="Grill Station")
     *     ),
     *     @OA\Parameter(
     *         name="date",
     *         in="query",
     *         description="Filter by specific date (YYYY-MM-DD format)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2025-11-10")
     *     ),
     *     @OA\Parameter(
     *         name="date_from",
     *         in="query",
     *         description="Filter from date (YYYY-MM-DD format)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2025-11-01")
     *     ),
     *     @OA\Parameter(
     *         name="date_to",
     *         in="query",
     *         description="Filter to date (YYYY-MM-DD format)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2025-11-10")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15, example=20)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", default=1, example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Kitchen tickets retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/KitchenTicket")),
     *             @OA\Property(property="first_page_url", type="string"),
     *             @OA\Property(property="from", type="integer"),
     *             @OA\Property(property="last_page", type="integer"),
     *             @OA\Property(property="last_page_url", type="string"),
     *             @OA\Property(property="next_page_url", type="string", nullable=true),
     *             @OA\Property(property="path", type="string"),
     *             @OA\Property(property="per_page", type="integer"),
     *             @OA\Property(property="prev_page_url", type="string", nullable=true),
     *             @OA\Property(property="to", type="integer"),
     *             @OA\Property(property="total", type="integer")
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
        $perPage = $request->get('per_page', 15);
        
        $query = KitchenTicket::with(['order.table', 'order.orderItems.menuItem', 'assignedChef'])
            ->where('restaurant_id', Tenant::id());

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->has('cooking_station')) {
            $query->where('cooking_station', $request->cooking_station);
        }

        // Filter by specific date
        if ($request->has('date')) {
            $date = \Carbon\Carbon::parse($request->date);
            $query->whereDate('created_at', $date);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $dateFrom = \Carbon\Carbon::parse($request->date_from)->startOfDay();
            $query->where('created_at', '>=', $dateFrom);
        }

        if ($request->has('date_to')) {
            $dateTo = \Carbon\Carbon::parse($request->date_to)->endOfDay();
            $query->where('created_at', '<=', $dateTo);
        }

        $tickets = $query->orderByRaw("
            CASE priority 
                WHEN 'urgent' THEN 1 
                WHEN 'rush' THEN 2 
                WHEN 'normal' THEN 3 
            END
        ")->orderBy('created_at')->paginate($perPage);

        return response()->json($tickets);
    }

    /**
     * @OA\Get(
     *     path="/api/kitchen/tickets/{kitchenTicket}",
     *     tags={"Kitchen Management"},
     *     summary="Get specific kitchen ticket",
     *     description="Retrieve details of a specific kitchen ticket with order and chef information",
     *     security={{"bearer_token": {}}},
     *     @OA\Parameter(
     *         name="kitchenTicket",
     *         in="path",
     *         description="Kitchen ticket ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Kitchen ticket details retrieved successfully",
     *         @OA\JsonContent(ref="#/components/schemas/KitchenTicket")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Kitchen ticket not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function show(KitchenTicket $kitchenTicket): JsonResponse
    {
        // $this->authorize('view', $kitchenTicket);
        
        abort_unless($kitchenTicket->restaurant_id === Tenant::id(), 404);
        
        $kitchenTicket->load([
            'order.table',
            'order.orderItems.menuItem.category',
            'assignedChef'
        ]);

        return response()->json($kitchenTicket);
    }

    /**
     * @OA\Post(
     *     path="/api/kitchen/tickets/{kitchenTicket}/assign",
     *     tags={"Kitchen Management"},
     *     summary="Assign kitchen ticket to chef",
     *     description="Assign a kitchen ticket to a specific chef and optionally set cooking station",
     *     security={{"bearer_token": {}}},
     *     @OA\Parameter(
     *         name="kitchenTicket",
     *         in="path",
     *         description="Kitchen ticket ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Assignment data",
     *         @OA\JsonContent(
     *             required={"chef_id"},
     *             @OA\Property(property="chef_id", type="integer", example=5, description="ID of the chef to assign"),
     *             @OA\Property(property="cooking_station", type="string", example="Grill Station", description="Cooking station name")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ticket assigned successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Ticket assigned successfully"),
     *             @OA\Property(property="ticket", ref="#/components/schemas/KitchenTicket")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function assign(Request $request, KitchenTicket $kitchenTicket): JsonResponse
    {
        // $this->authorize('update', $kitchenTicket);
        
        abort_unless($kitchenTicket->restaurant_id === Tenant::id(), 404);
        
        $request->validate([
            'chef_id' => 'required|exists:staff,id',
            'cooking_station' => 'nullable|string|max:50',
        ]);

        $chef = Staff::where('restaurant_id', Tenant::id())
            ->where('id', $request->chef_id)
            ->firstOrFail();

        $kitchenTicket->update([
            'assigned_chef_id' => $chef->id,
            'cooking_station' => $request->cooking_station,
        ]);

        $kitchenTicket->load(['assignedChef', 'order']);

        return response()->json([
            'message' => 'Ticket assigned successfully',
            'ticket' => $kitchenTicket
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/kitchen/tickets/{kitchenTicket}/start",
     *     tags={"Kitchen Management"},
     *     summary="Start ticket preparation",
     *     description="Mark a kitchen ticket as started and update order status to preparing. Automatically assigns the ticket to the authenticated user (chef) who starts it.",
     *     security={{"bearer_token": {}}},
     *     @OA\Parameter(
     *         name="kitchenTicket",
     *         in="path",
     *         description="Kitchen ticket ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order preparation started and ticket assigned to current user",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Order preparation started"),
     *             @OA\Property(property="ticket", ref="#/components/schemas/KitchenTicket")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Ticket cannot be started in current status",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function start(KitchenTicket $kitchenTicket): JsonResponse
    {
        // $this->authorize('update', $kitchenTicket);
        
        abort_unless($kitchenTicket->restaurant_id === Tenant::id(), 404);
        
        if ($kitchenTicket->status !== 'pending') {
            return response()->json([
                'message' => 'Ticket cannot be started in current status'
            ], 422);
        }

        // Auto-assign ticket to the authenticated user's staff record if not already assigned
        if (!$kitchenTicket->assigned_chef_id && auth()->check()) {
            $currentUser = auth()->user();
            
            // Try to find staff record linked to this user
            $staff = Staff::where('restaurant_id', Tenant::id())
                ->where('user_id', $currentUser->id)
                ->first();
            
            // If no direct link exists, try to find staff by email match
            if (!$staff) {
                $staff = Staff::where('restaurant_id', Tenant::id())
                    ->where('email', $currentUser->email)
                    ->first();
            }
            
            if ($staff) {
                $kitchenTicket->update(['assigned_chef_id' => $staff->id]);
            }
        }

        $kitchenTicket->markAsStarted();
        
        // Update order status to preparing
        $kitchenTicket->order->update(['status' => 'preparing']);
        
        // Update all order items to preparing state
        $kitchenTicket->order->orderItems()->update(['state' => 'preparing']);
        
        event(new OrderStatusUpdated($kitchenTicket->order, 'preparing'));

        return response()->json([
            'message' => 'Order preparation started',
            'ticket' => $kitchenTicket->fresh(['assignedChef', 'order'])
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/kitchen/tickets/{kitchenTicket}/complete",
     *     tags={"Kitchen Management"},
     *     summary="Complete ticket preparation",
     *     description="Mark a kitchen ticket as completed and order as ready for serving",
     *     security={{"bearer_token": {}}},
     *     @OA\Parameter(
     *         name="kitchenTicket",
     *         in="path",
     *         description="Kitchen ticket ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order completed and ready for serving",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Order completed and ready for serving"),
     *             @OA\Property(property="ticket", ref="#/components/schemas/KitchenTicket")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Ticket cannot be completed in current status",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function complete(KitchenTicket $kitchenTicket): JsonResponse
    {
        // $this->authorize('update', $kitchenTicket);
        
        abort_unless($kitchenTicket->restaurant_id === Tenant::id(), 404);
        
        if ($kitchenTicket->status !== 'preparing') {
            return response()->json([
                'message' => 'Ticket cannot be completed in current status'
            ], 422);
        }

        $kitchenTicket->markAsCompleted();
        
        // Update order status to ready
        $kitchenTicket->order->update(['status' => 'ready']);
        
        // Update all order items to ready state
        $kitchenTicket->order->orderItems()->update(['state' => 'ready']);
        
        event(new OrderStatusUpdated($kitchenTicket->order, 'ready'));

        return response()->json([
            'message' => 'Order completed and ready for serving',
            'ticket' => $kitchenTicket->fresh()
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/kitchen/tickets/{kitchenTicket}/serve",
     *     tags={"Kitchen Management"},
     *     summary="Mark ticket as served",
     *     description="Mark a kitchen ticket as served when food has been delivered to customer",
     *     security={{"bearer_token": {}}},
     *     @OA\Parameter(
     *         name="kitchenTicket",
     *         in="path",
     *         description="Kitchen ticket ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order marked as served",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Order has been served"),
     *             @OA\Property(property="ticket", ref="#/components/schemas/KitchenTicket")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Ticket cannot be served in current status",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function serve(KitchenTicket $kitchenTicket): JsonResponse
    {
        abort_unless($kitchenTicket->restaurant_id === Tenant::id(), 404);
        
        if ($kitchenTicket->status !== 'ready') {
            return response()->json([
                'message' => 'Ticket can only be served when ready'
            ], 422);
        }

        $kitchenTicket->markAsServed();
        
        // Update all order items to served state
        $kitchenTicket->order->orderItems()->update(['state' => 'served']);
        
        event(new OrderStatusUpdated($kitchenTicket->order, 'served'));

        return response()->json([
            'message' => 'Order has been served',
            'ticket' => $kitchenTicket->fresh()
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/kitchen/tickets/{kitchenTicket}/priority",
     *     tags={"Kitchen Management"},
     *     summary="Update ticket priority",
     *     description="Update the priority level of a kitchen ticket",
     *     security={{"bearer_token": {}}},
     *     @OA\Parameter(
     *         name="kitchenTicket",
     *         in="path",
     *         description="Kitchen ticket ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Priority data",
     *         @OA\JsonContent(
     *             required={"priority"},
     *             @OA\Property(property="priority", type="string", enum={"normal", "rush", "urgent"}, example="urgent")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Priority updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Priority updated successfully"),
     *             @OA\Property(property="ticket", ref="#/components/schemas/KitchenTicket")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function updatePriority(Request $request, KitchenTicket $kitchenTicket): JsonResponse
    {
        // $this->authorize('update', $kitchenTicket);
        
        abort_unless($kitchenTicket->restaurant_id === Tenant::id(), 404);
        
        $request->validate([
            'priority' => 'required|in:normal,rush,urgent',
        ]);

        $kitchenTicket->update([
            'priority' => $request->priority
        ]);

        return response()->json([
            'message' => 'Priority updated successfully',
            'ticket' => $kitchenTicket
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/kitchen/analytics",
     *     tags={"Kitchen Management"},
     *     summary="Get kitchen analytics",
     *     description="Retrieve kitchen performance analytics and metrics",
     *     security={{"bearer_token": {}}},
     *     @OA\Parameter(
     *         name="period",
     *         in="query",
     *         description="Time period for analytics",
     *         required=false,
     *         @OA\Schema(type="string", enum={"today", "week", "month"}, example="today")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Kitchen analytics retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="total_tickets", type="integer", example=45),
     *             @OA\Property(property="completed_tickets", type="integer", example=38),
     *             @OA\Property(property="pending_tickets", type="integer", example=5),
     *             @OA\Property(property="preparing_tickets", type="integer", example=2),
     *             @OA\Property(property="average_prep_time", type="number", format="float", example=18.5),
     *             @OA\Property(
     *                 property="chef_performance",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="chef_id", type="integer"),
     *                     @OA\Property(property="chef_name", type="string"),
     *                     @OA\Property(property="tickets_completed", type="integer"),
     *                     @OA\Property(property="average_prep_time", type="number", format="float")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="station_utilization",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="cooking_station", type="string"),
     *                     @OA\Property(property="ticket_count", type="integer"),
     *                     @OA\Property(property="avg_prep_time", type="number", format="float")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function analytics(Request $request): JsonResponse
    {
        $period = $request->get('period', 'today');
        $startDate = match($period) {
            'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            default => now()->startOfDay(),
        };

        $tickets = KitchenTicket::where('restaurant_id', Tenant::id())
            ->where('created_at', '>=', $startDate);

        $analytics = [
            'total_tickets' => $tickets->clone()->count(),
            'completed_tickets' => $tickets->clone()->where('status', 'ready')->count(),
            'pending_tickets' => $tickets->clone()->where('status', 'pending')->count(),
            'preparing_tickets' => $tickets->clone()->where('status', 'preparing')->count(),
            'average_prep_time' => $tickets->clone()
                ->whereNotNull('preparation_time')
                ->avg('preparation_time') ?? 0,
            'chef_performance' => $this->getChefPerformance($startDate),
            'station_utilization' => $this->getStationUtilization($startDate),
        ];

        return response()->json($analytics);
    }

    private function getChefPerformance($startDate)
    {
        return KitchenTicket::with('assignedChef')
            ->where('restaurant_id', Tenant::id())
            ->where('created_at', '>=', $startDate)
            ->whereNotNull('assigned_chef_id')
            ->whereNotNull('preparation_time')
            ->get()
            ->groupBy('assigned_chef_id')
            ->map(function ($tickets, $chefId) {
                $chef = $tickets->first()->assignedChef;
                return [
                    'chef_id' => $chefId,
                    'chef_name' => $chef->full_name,
                    'tickets_completed' => $tickets->count(),
                    'average_prep_time' => $tickets->avg('preparation_time'),
                    'total_prep_time' => $tickets->sum('preparation_time'),
                ];
            })->values();
    }

    private function getStationUtilization($startDate)
    {
        return KitchenTicket::where('restaurant_id', Tenant::id())
            ->where('created_at', '>=', $startDate)
            ->whereNotNull('cooking_station')
            ->selectRaw('cooking_station, COUNT(*) as ticket_count, AVG(preparation_time) as avg_prep_time')
            ->groupBy('cooking_station')
            ->get();
    }
}