<?php
// filepath: app/Http/Controllers/Api/ReportController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Staff;
use App\Models\MenuItem;
use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Infrastructure\Tenancy\Tenant;
use Illuminate\Support\Facades\DB;
use App\Services\ReportService;

class ReportController extends Controller
{
    public function __construct(private ReportService $reportService)
    {
        // $this->middleware(['role:Owner|Manager']);
    }

    public function summary(Request $request): JsonResponse
    {
        $period = $request->get('period', 'today');
        $startDate = $this->getStartDate($period);
        $endDate = $request->get('end_date') ? 
            \Carbon\Carbon::parse($request->get('end_date'))->endOfDay() : 
            now()->endOfDay();

        $summary = [
            'period' => $period,
            'date_range' => [
                'start' => $startDate->format('Y-m-d H:i:s'),
                'end' => $endDate->format('Y-m-d H:i:s'),
            ],
            'sales' => $this->getSalesSummary($startDate, $endDate),
            'orders' => $this->getOrdersSummary($startDate, $endDate),
            'staff' => $this->getStaffSummary($startDate, $endDate),
            'top_items' => $this->getTopItems($startDate, $endDate, 10),
            'payment_methods' => $this->getPaymentMethodBreakdown($startDate, $endDate),
        ];

        return response()->json($summary);
    }

    public function sales(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'nullable|in:today,week,month,custom',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'group_by' => 'nullable|in:hour,day,week,month',
        ]);

        $period = $request->get('period', 'today');
        $groupBy = $request->get('group_by', 'hour');
        
        $startDate = $request->get('start_date') ? 
            \Carbon\Carbon::parse($request->get('start_date'))->startOfDay() :
            $this->getStartDate($period);
            
        $endDate = $request->get('end_date') ? 
            \Carbon\Carbon::parse($request->get('end_date'))->endOfDay() : 
            now()->endOfDay();

        $salesData = $this->reportService->getSalesReport($startDate, $endDate, $groupBy);

        return response()->json([
            'period' => $period,
            'group_by' => $groupBy,
            'date_range' => [
                'start' => $startDate->format('Y-m-d H:i:s'),
                'end' => $endDate->format('Y-m-d H:i:s'),
            ],
            'data' => $salesData,
        ]);
    }

    public function items(Request $request): JsonResponse
    {
        $period = $request->get('period', 'today');
        $limit = $request->get('limit', 20);
        $sort = $request->get('sort', 'quantity'); // quantity, revenue
        
        $startDate = $this->getStartDate($period);

        $items = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id')
            ->join('categories', 'menu_items.category_id', '=', 'categories.id')
            ->where('orders.restaurant_id', Tenant::id())
            ->where('orders.status', 'paid')
            ->where('orders.placed_at', '>=', $startDate)
            ->select(
                'menu_items.id',
                'menu_items.name',
                'menu_items.price',
                'categories.name as category_name',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.quantity * order_items.unit_price) as total_revenue'),
                DB::raw('AVG(order_items.unit_price) as average_price'),
                DB::raw('COUNT(DISTINCT orders.id) as order_count')
            )
            ->groupBy('menu_items.id', 'menu_items.name', 'menu_items.price', 'categories.name')
            ->orderBy($sort === 'revenue' ? 'total_revenue' : 'total_quantity', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'period' => $period,
            'sort_by' => $sort,
            'items' => $items,
        ]);
    }

    public function staff(Request $request): JsonResponse
    {
        $period = $request->get('period', 'today');
        $startDate = $this->getStartDate($period);

        $staffPerformance = Staff::with(['attendances', 'assignedOrders'])
            ->where('restaurant_id', Tenant::id())
            ->where('status', 'active')
            ->get()
            ->map(function ($staff) use ($startDate) {
                $attendances = $staff->attendances()
                    ->where('clock_in', '>=', $startDate)
                    ->whereNotNull('clock_out')
                    ->get();

                $orders = $staff->assignedOrders()
                    ->where('placed_at', '>=', $startDate)
                    ->where('status', 'paid')
                    ->get();

                return [
                    'id' => $staff->id,
                    'name' => $staff->full_name,
                    'position' => $staff->position,
                    'hours_worked' => $attendances->sum('hours_worked'),
                    'days_worked' => $attendances->count(),
                    'orders_served' => $orders->count(),
                    'revenue_generated' => $orders->sum('total'),
                    'average_order_value' => $orders->avg('total') ?? 0,
                    'hourly_revenue' => $attendances->sum('hours_worked') > 0 ? 
                        $orders->sum('total') / $attendances->sum('hours_worked') : 0,
                ];
            })
            ->sortByDesc('revenue_generated')
            ->values();

        return response()->json([
            'period' => $period,
            'staff_performance' => $staffPerformance,
        ]);
    }

    public function export(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:sales,items,staff',
            'format' => 'required|in:pdf,excel',
            'period' => 'nullable|in:today,week,month,custom',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $type = $request->get('type');
        $format = $request->get('format');
        $period = $request->get('period', 'today');
        
        $startDate = $request->get('start_date') ? 
            \Carbon\Carbon::parse($request->get('start_date'))->startOfDay() :
            $this->getStartDate($period);
            
        $endDate = $request->get('end_date') ? 
            \Carbon\Carbon::parse($request->get('end_date'))->endOfDay() : 
            now()->endOfDay();

        $filePath = $this->reportService->exportReport($type, $format, $startDate, $endDate);

        return response()->json([
            'message' => 'Report generated successfully',
            'download_url' => asset('storage/' . $filePath),
            'file_path' => $filePath,
        ]);
    }

    private function getSalesSummary($startDate, $endDate): array
    {
        $orders = Order::where('restaurant_id', Tenant::id())
            ->where('status', 'paid')
            ->whereBetween('placed_at', [$startDate, $endDate]);

        return [
            'total_revenue' => $orders->clone()->sum('total'),
            'total_orders' => $orders->clone()->count(),
            'average_order_value' => $orders->clone()->avg('total') ?? 0,
            'total_tax' => $orders->clone()->sum('tax_amount'),
            'total_discounts' => $orders->clone()->sum('discount_amount'),
        ];
    }

    private function getOrdersSummary($startDate, $endDate): array
    {
        $allOrders = Order::where('restaurant_id', Tenant::id())
            ->whereBetween('placed_at', [$startDate, $endDate]);

        return [
            'total_orders' => $allOrders->clone()->count(),
            'paid_orders' => $allOrders->clone()->where('status', 'paid')->count(),
            'cancelled_orders' => $allOrders->clone()->where('status', 'cancelled')->count(),
            'refunded_orders' => $allOrders->clone()->where('status', 'refunded')->count(),
        ];
    }

    private function getStaffSummary($startDate, $endDate): array
    {
        return [
            'total_staff' => Staff::where('restaurant_id', Tenant::id())->where('status', 'active')->count(),
            'active_staff' => Staff::where('restaurant_id', Tenant::id())
                ->whereHas('attendances', fn($q) => $q->whereNull('clock_out'))
                ->count(),
            'total_hours_worked' => DB::table('attendances')
                ->join('staff', 'attendances.staff_id', '=', 'staff.id')
                ->where('staff.restaurant_id', Tenant::id())
                ->whereBetween('attendances.clock_in', [$startDate, $endDate])
                ->whereNotNull('attendances.clock_out')
                ->sum('attendances.hours_worked') ?? 0,
        ];
    }

    private function getTopItems($startDate, $endDate, $limit): array
    {
        return DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id')
            ->where('orders.restaurant_id', Tenant::id())
            ->where('orders.status', 'paid')
            ->whereBetween('orders.placed_at', [$startDate, $endDate])
            ->select(
                'menu_items.name',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.quantity * order_items.unit_price) as total_revenue')
            )
            ->groupBy('menu_items.id', 'menu_items.name')
            ->orderBy('total_quantity', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    private function getPaymentMethodBreakdown($startDate, $endDate): array
    {
        return Order::where('restaurant_id', Tenant::id())
            ->where('status', 'paid')
            ->whereBetween('placed_at', [$startDate, $endDate])
            ->select('payment_method', DB::raw('SUM(total) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('payment_method')
            ->get()
            ->toArray();
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