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

    /**
     * @OA\Get(
     *     path="/api/staff",
     *     tags={"Staff Management"},
     *     summary="List all staff members",
     *     description="Retrieve paginated list of staff members for the authenticated restaurant",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by staff status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"active", "inactive", "terminated"})
     *     ),
     *     @OA\Parameter(
     *         name="position",
     *         in="query",
     *         description="Filter by staff position",
     *         required=false,
     *         @OA\Schema(type="string", example="Manager")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Staff list retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="restaurant_id", type="integer", example=1),
     *                     @OA\Property(property="employee_id", type="string", example="golden-fork-001"),
     *                     @OA\Property(property="first_name", type="string", example="John"),
     *                     @OA\Property(property="last_name", type="string", example="Doe"),
     *                     @OA\Property(property="email", type="string", example="john.doe@example.com"),
     *                     @OA\Property(property="phone", type="string", example="+1-555-0123"),
     *                     @OA\Property(property="position", type="string", example="Manager"),
     *                     @OA\Property(property="department", type="string", example="Management"),
     *                     @OA\Property(property="hourly_rate", type="number", format="float", example=25.00),
     *                     @OA\Property(property="hire_date", type="string", format="date", example="2024-01-15"),
     *                     @OA\Property(property="status", type="string", example="active"),
     *                     @OA\Property(property="emergency_contact_name", type="string", example="Jane Doe"),
     *                     @OA\Property(property="emergency_contact_phone", type="string", example="+1-555-0124")
     *                 )
     *             ),
     *             @OA\Property(property="per_page", type="integer", example=15),
     *             @OA\Property(property="total", type="integer", example=50),
     *             @OA\Property(property="last_page", type="integer", example=4)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Insufficient permissions",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="This action is unauthorized.")
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/staff",
     *     tags={"Staff Management"},
     *     summary="Create a new staff member",
     *     description="Add a new staff member to the restaurant",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"first_name","last_name","email","position","department","hourly_rate","hire_date","status"},
     *             @OA\Property(property="employee_id", type="string", example="golden-fork-015"),
     *             @OA\Property(property="first_name", type="string", example="John"),
     *             @OA\Property(property="last_name", type="string", example="Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
     *             @OA\Property(property="phone", type="string", example="+1-555-0123"),
     *             @OA\Property(property="position", type="string", example="Server"),
     *             @OA\Property(property="department", type="string", example="Service"),
     *             @OA\Property(property="hourly_rate", type="number", format="float", example=15.50),
     *             @OA\Property(property="hire_date", type="string", format="date", example="2024-12-01"),
     *             @OA\Property(property="status", type="string", enum={"active", "inactive", "terminated"}, example="active"),
     *             @OA\Property(property="emergency_contact_name", type="string", example="Jane Doe"),
     *             @OA\Property(property="emergency_contact_phone", type="string", example="+1-555-0124")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Staff member created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=15),
     *             @OA\Property(property="restaurant_id", type="integer", example=1),
     *             @OA\Property(property="employee_id", type="string", example="golden-fork-015"),
     *             @OA\Property(property="first_name", type="string", example="John"),
     *             @OA\Property(property="last_name", type="string", example="Doe"),
     *             @OA\Property(property="email", type="string", example="john.doe@example.com"),
     *             @OA\Property(property="phone", type="string", example="+1-555-0123"),
     *             @OA\Property(property="position", type="string", example="Server"),
     *             @OA\Property(property="department", type="string", example="Service"),
     *             @OA\Property(property="hourly_rate", type="number", format="float", example=15.50),
     *             @OA\Property(property="hire_date", type="string", format="date", example="2024-12-01"),
     *             @OA\Property(property="status", type="string", example="active"),
     *             @OA\Property(property="emergency_contact_name", type="string", example="Jane Doe"),
     *             @OA\Property(property="emergency_contact_phone", type="string", example="+1-555-0124"),
     *             @OA\Property(property="created_at", type="string", format="datetime"),
     *             @OA\Property(property="updated_at", type="string", format="datetime")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Insufficient permissions",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="This action is unauthorized.")
     *         )
     *     )
     * )
     */
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
