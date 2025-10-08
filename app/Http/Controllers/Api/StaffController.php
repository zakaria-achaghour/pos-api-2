<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStaffRequest;
use App\Http\Requests\UpdateStaffRequest;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Infrastructure\Tenancy\Tenant;

class StaffController extends Controller
{
    // Remove the constructor with middleware - we'll handle this in routes
    // public function __construct()
    // {
    //     $this->middleware(['role:Owner|Manager'])->except(['index', 'show']);
    // }

    public function index(Request $request): JsonResponse
    {
        try {
            $query = Staff::where('restaurant_id', Tenant::id());

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('position')) {
                $query->where('position', $request->position);
            }

            $staff = $query->orderBy('first_name')->paginate();

            return response()->json($staff);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    public function store(StoreStaffRequest $request): JsonResponse
    {
        $data = $request->validated();
        // Set tenant restaurant context
        $data['restaurant_id'] = Tenant::id();

        $staff = Staff::create($data);
        $staff->load(['attendances']);

        return response()->json($staff, 201);
    }

    public function show(Staff $staff): JsonResponse
    {
        $this->authorize('view', $staff);
        
        $staff->load([
            'attendances' => fn($q) => $q->latest()->limit(10),
            'schedules' => fn($q) => $q->where('date', '>=', now()->startOfWeek())->orderBy('date'),
        ]);

        return response()->json($staff);
    }

    public function update(UpdateStaffRequest $request, Staff $staff): JsonResponse
    {
        $this->authorize('update', $staff);
        
        $data = $request->validated();

        $staff->update($data);
        $staff->load(['attendances']);

        return response()->json($staff);
    }

    public function destroy(Staff $staff): JsonResponse
    {
        $this->authorize('delete', $staff);
        
        $staff->delete();

        return response()->json(['message' => 'Staff member deleted successfully']);
    }

    public function performance(Request $request): JsonResponse
    {
        $period = $request->get('period', 'week');
        $startDate = match($period) {
            'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            default => now()->startOfWeek(),
        };

        $staff = Staff::with(['attendances', 'assignedOrders'])
            ->where('restaurant_id', Tenant::id())
            ->where('status', 'active')
            ->get()
            ->map(function ($member) use ($startDate) {
                $attendances = $member->attendances()
                    ->where('clock_in', '>=', $startDate)
                    ->whereNotNull('clock_out')
                    ->get();

                $orders = $member->assignedOrders()
                    ->where('placed_at', '>=', $startDate)
                    ->where('status', 'paid')
                    ->get();

                return [
                    'id' => $member->id,
                    'name' => $member->full_name,
                    'position' => $member->position,
                    'photo_url' => null,
                    'hours_worked' => $attendances->sum('hours_worked'),
                    'orders_completed' => $orders->count(),
                    'revenue_generated' => $orders->sum('total'),
                    'average_order_value' => $orders->avg('total') ?? 0,
                ];
            });

        return response()->json($staff);
    }
}
