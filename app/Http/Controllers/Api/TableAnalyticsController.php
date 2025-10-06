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
        $this->middleware(['role:Owner|Manager']);
    }

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

    public function show(Table $table, Request $request): JsonResponse
    {
        $this->authorize('view', $table);
        
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