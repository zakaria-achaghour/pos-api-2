<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Staff;
use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Infrastructure\Tenancy\Tenant;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function dashboardMetrics(Request $request): JsonResponse
    {
        $period = $request->get('period', 'today');
        $startDate = $this->getStartDate($period);

        $orders = Order::where('restaurant_id', Tenant::id())
            ->where('placed_at', '>=', $startDate);

        $metrics = [
            'total_revenue' => $orders->clone()->where('status', 'paid')->sum('total'),
            'total_orders' => $orders->clone()->count(),
            'paid_orders' => $orders->clone()->where('status', 'paid')->count(),
            'cancelled_orders' => $orders->clone()->where('status', 'cancelled')->count(),
            'average_order_value' => $orders->clone()->where('status', 'paid')->avg('total') ?? 0,
            'active_staff' => Staff::where('restaurant_id', Tenant::id())
                ->whereHas('attendances', fn($q) => $q->whereNull('clock_out'))
                ->count(),
            'occupied_tables' => Table::where('restaurant_id', Tenant::id())
                ->where('status', 'occupied')
                ->count(),
            'available_tables' => Table::where('restaurant_id', Tenant::id())
                ->where('status', 'available')
                ->count(),
        ];

        return response()->json($metrics);
    }

    public function salesCharts(Request $request): JsonResponse
    {
        $period = $request->get('period', 'today');
        $startDate = $this->getStartDate($period);

        // Hourly sales for today
        if ($period === 'today') {
            $hourlySales = Order::where('restaurant_id', Tenant::id())
                ->where('status', 'paid')
                ->where('placed_at', '>=', $startDate)
                ->select(
                    DB::raw('EXTRACT(HOUR FROM placed_at) as hour'),
                    DB::raw('SUM(total) as revenue'),
                    DB::raw('COUNT(*) as orders')
                )
                ->groupBy('hour')
                ->orderBy('hour')
                ->get();
        } else {
            // Daily sales for week/month
            $hourlySales = Order::where('restaurant_id', Tenant::id())
                ->where('status', 'paid')
                ->where('placed_at', '>=', $startDate)
                ->select(
                    DB::raw('DATE(placed_at) as date'),
                    DB::raw('SUM(total) as revenue'),
                    DB::raw('COUNT(*) as orders')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get();
        }

        // Payment method breakdown
        $paymentMethods = Order::where('restaurant_id', Tenant::id())
            ->where('status', 'paid')
            ->where('placed_at', '>=', $startDate)
            ->select('payment_method', DB::raw('SUM(total) as total'))
            ->groupBy('payment_method')
            ->get();

        return response()->json([
            'hourly_sales' => $hourlySales,
            'payment_methods' => $paymentMethods,
        ]);
    }

    public function topItems(Request $request): JsonResponse
    {
        $period = $request->get('period', 'today');
        $limit = $request->get('limit', 10);
        $startDate = $this->getStartDate($period);

        $topItems = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id')
            ->where('orders.restaurant_id', Tenant::id())
            ->where('orders.status', 'paid')
            ->where('orders.placed_at', '>=', $startDate)
            ->select(
                'menu_items.name',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.quantity * order_items.unit_price) as total_revenue')
            )
            ->groupBy('menu_items.id', 'menu_items.name')
            ->orderBy('total_quantity', 'desc')
            ->limit($limit)
            ->get();

        return response()->json($topItems);
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