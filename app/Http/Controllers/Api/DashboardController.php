<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Infrastructure\Tenancy\Tenant;

class DashboardController extends Controller
{
    public function overview(Request $request): JsonResponse
    {
        $user = $request->user('api');
        $restaurantId = Tenant::id();
        abort_unless($restaurantId, 400, 'Restaurant context is required.');

        $now = now()->setTimezone($user->restaurant?->timezone ?? config('app.timezone'));
        $period = $request->get('period', 'today');
        $allowedPeriods = ['today', 'week', 'month'];
        if (!in_array($period, $allowedPeriods, true)) {
            $period = 'today';
        }

        [$start, $end] = match ($period) {
            'week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            default => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
        };

        $cacheKey = sprintf('dashboard:%d:%s:%s', $restaurantId, $period, $start->format('Y-m-d'));        

        $payload = Cache::remember($cacheKey, 60, function () use ($restaurantId, $start, $end, $user, $now, $period) {
            $baseQuery = Order::where('restaurant_id', $restaurantId)
                ->whereBetween('paid_at', [$start, $end])
                ->where('status', 'completed');

            $ordersToday = (clone $baseQuery)->count();
            $salesToday = (clone $baseQuery)->sum('total');
            $avgTicket = $ordersToday > 0 ? round($salesToday / $ordersToday, 2) : 0.0;

            $paymentRows = (clone $baseQuery)
                ->select('payment_method', DB::raw('SUM(total) as total_amount'))
                ->groupBy('payment_method')
                ->get();

            $methods = [];
            $otherTotal = 0.0;

            foreach ($paymentRows as $row) {
                $method = $row->payment_method ?? 'other';
                $total = (float) $row->total_amount;

                if (in_array($method, ['cash', 'card'])) {
                    $methods[] = [
                        'method' => $method,
                        'total' => round($total, 2),
                    ];
                } else {
                    $otherTotal += $total;
                }
            }

            if ($otherTotal > 0) {
                $methods[] = [
                    'method' => 'other',
                    'total' => round($otherTotal, 2),
                ];
            }

            return [
                'period' => $period,
                'date' => $now->toDateString(),
                'currency' => optional($user->restaurant)->currency ?? 'USD',
                'sales_today' => round((float) $salesToday, 2),
                'orders_today' => $ordersToday,
                'avg_ticket' => $avgTicket,
                'payment_methods' => $methods,
            ];
        });

        return response()->json($payload);
    }
}
