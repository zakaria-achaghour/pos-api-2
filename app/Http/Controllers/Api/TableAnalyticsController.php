<?php
// filepath: app/Http/Controllers/Api/TableAnalyticsController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Table;
use App\Models\TableAnalytics;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Infrastructure\Tenancy\Tenant;
use Illuminate\Support\Facades\DB;

class TableAnalyticsController extends Controller
{
    public function __construct()
    {
        // $this->middleware(['role:Owner|Manager']);
    }

    /**
     * @OA\Get(
     *     path="/api/tables/analytics",
     *     tags={"Table Analytics"},
     *     summary="Get table analytics",
     *     description="Retrieve analytics data for all tables including occupancy and revenue metrics",
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
     *         description="Table analytics retrieved successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="table_id", type="integer"),
     *                 @OA\Property(property="table_number", type="string"),
     *                 @OA\Property(property="total_revenue", type="number", format="float"),
     *                 @OA\Property(property="total_orders", type="integer"),
     *                 @OA\Property(property="occupancy_rate", type="number", format="float"),
     *                 @OA\Property(property="average_order_value", type="number", format="float")
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
    public function index(Request $request): JsonResponse
    {
        $period = $request->get('period', 'today');
        $startDate = $this->getStartDate($period);
        $endDate = now()->endOfDay();

        $analytics = TableAnalytics::with('table')
            ->where('restaurant_id', Tenant::id())
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderBy('date', 'desc')
            ->get()
            ->groupBy('table_id')
            ->map(function ($tableAnalytics) {
                $table = $tableAnalytics->first()->table;
                return [
                    'table_id' => $table->id,
                    'table_number' => $table->number,
                    'capacity' => $table->capacity,
                    'total_seatings' => $tableAnalytics->sum('total_seatings'),
                    'total_revenue' => $tableAnalytics->sum('total_revenue'),
                    'average_revenue_per_seating' => $tableAnalytics->sum('total_seatings') > 0 ? 
                        $tableAnalytics->sum('total_revenue') / $tableAnalytics->sum('total_seatings') : 0,
                    'average_occupancy_rate' => $tableAnalytics->avg('occupancy_rate'),
                    'average_duration' => $tableAnalytics->avg('average_duration_minutes'),
                ];
            });

        return response()->json($analytics->values());
    }

    /**
     * @OA\Get(
     *     path="/api/tables/{table}/analytics",
     *     tags={"Table Analytics"},
     *     summary="Get analytics for specific table",
     *     description="Retrieve detailed analytics data for a specific table",
     *     security={{"bearer_token": {}}},
     *     @OA\Parameter(
     *         name="table",
     *         in="path",
     *         description="Table ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="period",
     *         in="query",
     *         description="Time period for analytics",
     *         required=false,
     *         @OA\Schema(type="string", enum={"today", "week", "month"}, example="week")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Table analytics retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="table", ref="#/components/schemas/Table"),
     *             @OA\Property(property="analytics", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="daily_orders", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="summary", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Table not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function show(Table $table, Request $request): JsonResponse
    {
        // $this->authorize('view', $table);
        
        abort_unless($table->restaurant_id === Tenant::id(), 404);
        
        $period = $request->get('period', 'week');
        $startDate = $this->getStartDate($period);
        $endDate = now()->endOfDay();

        $analytics = TableAnalytics::where('table_id', $table->id)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderBy('date')
            ->get();

        $dailyOrders = Order::where('restaurant_id', Tenant::id())
            ->where('table_id', $table->id)
            ->where('status', 'paid')
            ->whereBetween('placed_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(placed_at) as date'),
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(total) as revenue'),
                DB::raw('AVG(total) as average_order_value')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'table' => $table,
            'analytics' => $analytics,
            'daily_orders' => $dailyOrders,
            'summary' => [
                'total_revenue' => $analytics->sum('total_revenue'),
                'total_seatings' => $analytics->sum('total_seatings'),
                'average_occupancy_rate' => $analytics->avg('occupancy_rate'),
                'average_duration' => $analytics->avg('average_duration_minutes'),
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/tables/occupancy-rates",
     *     tags={"Table Analytics"},
     *     summary="Get table occupancy rates",
     *     description="Retrieve occupancy rates for all tables",
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
     *         description="Table occupancy rates retrieved successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="table_id", type="integer"),
     *                 @OA\Property(property="table_number", type="string"),
     *                 @OA\Property(property="capacity", type="integer"),
     *                 @OA\Property(property="status", type="string"),
     *                 @OA\Property(property="orders_count", type="integer"),
     *                 @OA\Property(property="revenue", type="number", format="float"),
     *                 @OA\Property(property="occupancy_rate", type="number", format="float"),
     *                 @OA\Property(property="last_occupied", type="string", format="date-time", nullable=true)
     *             )
     *         )
     *     )
     * )
     */
    public function occupancyRates(Request $request): JsonResponse
    {
        $period = $request->get('period', 'today');
        $startDate = $this->getStartDate($period);

        $occupancyData = Table::with(['orders' => function ($query) use ($startDate) {
                $query->where('placed_at', '>=', $startDate)
                      ->where('status', 'paid');
            }])
            ->where('restaurant_id', Tenant::id())
            ->get()
            ->map(function ($table) use ($startDate) {
                $orders = $table->orders;
                $totalMinutesInPeriod = now()->diffInMinutes($startDate);
                
                // Calculate total occupied time (simplified)
                $totalOccupiedMinutes = $orders->count() * 60; // Assume 1 hour average per order
                
                $occupancyRate = $totalMinutesInPeriod > 0 ? 
                    min(100, ($totalOccupiedMinutes / $totalMinutesInPeriod) * 100) : 0;

                return [
                    'table_id' => $table->id,
                    'table_number' => $table->number,
                    'capacity' => $table->capacity,
                    'status' => $table->status,
                    'orders_count' => $orders->count(),
                    'revenue' => $orders->sum('total'),
                    'occupancy_rate' => round($occupancyRate, 2),
                    'last_occupied' => $table->last_occupied_at?->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json($occupancyData);
    }

    /**
     * @OA\Get(
     *     path="/api/tables/revenue-per-table",
     *     tags={"Table Analytics"},
     *     summary="Get revenue per table",
     *     description="Retrieve revenue analytics grouped by table",
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
     *         description="Revenue per table retrieved successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="table_id", type="integer"),
     *                 @OA\Property(property="table_number", type="string"),
     *                 @OA\Property(property="capacity", type="integer"),
     *                 @OA\Property(property="order_count", type="integer"),
     *                 @OA\Property(property="total_revenue", type="number", format="float"),
     *                 @OA\Property(property="average_order_value", type="number", format="float"),
     *                 @OA\Property(property="revenue_per_seat", type="number", format="float")
     *             )
     *         )
     *     )
     * )
     */
    public function revenuePerTable(Request $request): JsonResponse
    {
        $period = $request->get('period', 'today');
        $startDate = $this->getStartDate($period);

        $revenueData = DB::table('orders')
            ->join('tables', 'orders.table_id', '=', 'tables.id')
            ->where('orders.restaurant_id', Tenant::id())
            ->where('orders.status', 'paid')
            ->where('orders.placed_at', '>=', $startDate)
            ->select(
                'tables.id as table_id',
                'tables.number as table_number',
                'tables.capacity',
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(orders.total) as total_revenue'),
                DB::raw('AVG(orders.total) as average_order_value'),
                DB::raw('SUM(orders.total) / tables.capacity as revenue_per_seat')
            )
            ->groupBy('tables.id', 'tables.number', 'tables.capacity')
            ->orderBy('total_revenue', 'desc')
            ->get();

        return response()->json($revenueData);
    }

    /**
     * @OA\Put(
     *     path="/api/tables/layout",
     *     tags={"Table Analytics"},
     *     summary="Update table layout",
     *     description="Update the grid positions of tables for layout management",
     *     security={{"bearer_token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="tables",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", description="Table ID"),
     *                     @OA\Property(property="grid_x", type="integer", description="X coordinate in grid"),
     *                     @OA\Property(property="grid_y", type="integer", description="Y coordinate in grid")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Table layout updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Table layout updated successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function updateLayout(Request $request): JsonResponse
    {
        $request->validate([
            'tables' => 'required|array',
            'tables.*.id' => 'required|exists:tables,id',
            'tables.*.grid_x' => 'required|integer|min:0',
            'tables.*.grid_y' => 'required|integer|min:0',
        ]);

        DB::transaction(function () use ($request) {
            foreach ($request->tables as $tableData) {
                Table::where('restaurant_id', Tenant::id())
                    ->where('id', $tableData['id'])
                    ->update([
                        'grid_x' => $tableData['grid_x'],
                        'grid_y' => $tableData['grid_y'],
                    ]);
            }
        });

        return response()->json(['message' => 'Table layout updated successfully']);
    }

    private function getStartDate(string $period): \Carbon\Carbon
    {
        return match($period) {
            'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            default => now()->startOfDay(),
        };
    }
}