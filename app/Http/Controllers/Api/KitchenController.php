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
     *     description="Retrieve list of kitchen tickets with optional filtering",
     *     security={{"bearer_token": {}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by ticket status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"pending", "preparing", "ready"})
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
     *     @OA\Response(
     *         response=200,
     *         description="Kitchen tickets retrieved successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/KitchenTicket")
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

        $tickets = $query->orderByRaw("
            CASE priority 
                WHEN 'urgent' THEN 1 
                WHEN 'rush' THEN 2 
                WHEN 'normal' THEN 3 
            END
        ")->orderBy('created_at')->get();

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
        $this->authorize('view', $kitchenTicket);
        
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
        $this->authorize('update', $kitchenTicket);
        
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
     *     description="Mark a kitchen ticket as started and update order status to preparing",
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
     *         description="Order preparation started",
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
        $this->authorize('update', $kitchenTicket);
        
        if ($kitchenTicket->status !== 'pending') {
            return response()->json([
                'message' => 'Ticket cannot be started in current status'
            ], 422);
        }

        $kitchenTicket->markAsStarted();
        
        event(new OrderStatusUpdated($kitchenTicket->order, 'preparing'));

        return response()->json([
            'message' => 'Order preparation started',
            'ticket' => $kitchenTicket->fresh()
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
        $this->authorize('update', $kitchenTicket);
        
        if ($kitchenTicket->status !== 'preparing') {
            return response()->json([
                'message' => 'Ticket cannot be completed in current status'
            ], 422);
        }

        $kitchenTicket->markAsCompleted();
        
        event(new OrderStatusUpdated($kitchenTicket->order, 'ready'));

        return response()->json([
            'message' => 'Order completed and ready for serving',
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
        $this->authorize('update', $kitchenTicket);
        
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