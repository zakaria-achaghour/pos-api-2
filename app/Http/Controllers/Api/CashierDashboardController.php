<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashierShift;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Infrastructure\Tenancy\Tenant;

class CashierDashboardController extends Controller
{
    public function today(Request $request): JsonResponse
    {
        $user = $request->user('api');
        $restaurantId = Tenant::id();
        abort_unless($restaurantId, 400, 'Restaurant context is required.');

        $canViewAll = $user->hasAnyRole(['Owner', 'Manager', 'SuperAdmin']);
        $date = now()->setTimezone(config('app.timezone'))->toDateString();

        $ordersQuery = Order::with(['table', 'waiter'])
            ->where('restaurant_id', $restaurantId)
            ->whereNotNull('paid_at')
            ->whereDate('paid_at', $date);

        if (!$canViewAll) {
            $ordersQuery->where('paid_by', $user->id);
        }

        $orders = (clone $ordersQuery)
            ->orderByDesc('paid_at')
            ->get(['id', 'table_id', 'total', 'payment_method', 'paid_at', 'paid_by']);

        $methodBreakdown = (clone $ordersQuery)
            ->select('payment_method',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total) as total_amount'))
            ->groupBy('payment_method')
            ->get();

        $totals = [
            'overall' => $orders->sum(fn ($order) => (float) $order->total),
            'by_method' => $methodBreakdown->map(function ($row) {
                return [
                    'method' => $row->payment_method,
                    'count' => (int) $row->count,
                    'total' => (float) $row->total_amount,
                ];
            })->values(),
        ];

        $currentShift = CashierShift::where('restaurant_id', $restaurantId)
            ->where('user_id', $user->id)
            ->whereNull('closed_at')
            ->first();

        $currency = optional($user->restaurant)->currency ?? 'USD';

        return response()->json([
            'date' => $date,
            'currency' => $currency,
            'can_view_all' => $canViewAll,
            'current_shift' => $currentShift,
            'totals' => $totals,
            'orders' => $orders->map(function (Order $order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number ?? sprintf('#%s', $order->id),
                    'table' => $order->table?->number,
                    'total' => (float) $order->total,
                    'payment_method' => $order->payment_method,
                    'paid_at' => optional($order->paid_at)->toIso8601String(),
                ];
            }),
        ]);
    }
}
