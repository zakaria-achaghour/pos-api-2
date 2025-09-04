<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReportController extends Controller
{
     // GET /api/reports/summary?date=YYYY-MM-DD
    public function summary(Request $request)
    {
        $date = $request->validate(['date' => 'required|date'])['date'];
        $rid  = Tenant::id();

        // totals by method
        $by = DB::table('payments')
            ->join('orders','payments.order_id','=','orders.id')
            ->where('orders.restaurant_id',$rid)
            ->whereDate('payments.paid_at',$date)
            ->select('method', DB::raw('SUM(amount)::numeric(12,2) as total'))
            ->groupBy('method')
            ->pluck('total','method');

        // orders count (paid)
        $ordersCount = DB::table('orders')
            ->where('restaurant_id',$rid)
            ->where('status','paid')
            ->whereDate('closed_at',$date)
            ->count();

        $total = array_sum(array_map('floatval', $by->toArray()));

        return response()->json([
            'date'         => $date,
            'orders_count' => $ordersCount,
            'total_sales'  => number_format($total, 2, '.', ''),
            'by_method'    => [
                'cash' => $by['cash'] ?? '0.00',
                'card' => $by['card'] ?? '0.00',
                'other'=> $by['other'] ?? '0.00',
            ],
        ]);
    }
}
