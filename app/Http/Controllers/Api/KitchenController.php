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
        $this->middleware(['role:Kitchen|Manager|Owner']);
    }

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