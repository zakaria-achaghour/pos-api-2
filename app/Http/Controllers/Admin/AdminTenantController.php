<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\Table;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Infrastructure\Tenancy\Tenant;
use Tymon\JWTAuth\Facades\JWTAuth;

class AdminTenantController extends Controller
{
   public function listRestaurants(Request $r) {
        $q = Restaurant::query();
        if ($search = $r->query('q')) {
            $q->where('name','ilike',"%{$search}%");
        }
        return $q->orderBy('id','desc')->paginate(20);
    }

    public function overview(Restaurant $restaurant) {
        return Tenant::with($restaurant->id, function () {
            return [
                'tables' => Table::count(),
                'categories' => MenuCategory::count(),
                'items' => MenuItem::count(),
                'orders_today' => Order::whereDate('placed_at', now()->toDateString())->count(),
                'sales_today' => DB::table('payments')
                    ->join('orders','orders.id','=','payments.order_id')
                    ->whereDate('payments.paid_at', now()->toDateString())
                    ->sum('amount'),
            ];
        });
    }

    public function tables(Restaurant $restaurant) {
        return Tenant::with($restaurant->id, fn() => Table::orderBy('name')->paginate(50));
    }

    public function categories(Restaurant $restaurant) {
        return Tenant::with($restaurant->id, fn() => MenuCategory::orderBy('name')->paginate(100));
    }

    public function items(Restaurant $restaurant) {
        return Tenant::with($restaurant->id, fn() => MenuItem::with('category')->orderBy('name')->paginate(100));
    }

    public function orders(Restaurant $restaurant, Request $r) {
        return Tenant::with($restaurant->id, function () use ($r) {
            $q = Order::with(['table','user'])->orderByDesc('id');
            if ($status = $r->query('status')) $q->where('status', $status);
            if ($date = $r->query('date')) $q->whereDate('placed_at', $date);
            return $q->paginate(50);
        });
    }

    public function dailySummary(Restaurant $restaurant, Request $r) {
        $date = $r->validate(['date'=>'required|date'])['date'];
        return Tenant::with($restaurant->id, function () use ($date) {
            $by = DB::table('payments')
                ->join('orders','payments.order_id','=','orders.id')
                ->whereDate('payments.paid_at', $date)
                ->select('method', DB::raw('SUM(amount)::numeric(12,2) total'))
                ->groupBy('method')
                ->pluck('total','method');
            $ordersCount = Order::where('status','paid')->whereDate('closed_at',$date)->count();
            $total = array_sum(array_map('floatval', $by->toArray()));
            return [
                'date' => $date,
                'orders_count' => $ordersCount,
                'total_sales'  => number_format($total,2,'.',''),
                'by_method'    => [
                    'cash' => $by['cash'] ?? '0.00',
                    'card' => $by['card'] ?? '0.00',
                    'other'=> $by['other'] ?? '0.00',
                ],
            ];
        });
    }

    // Optional: Impersonate a tenant user -> returns a short-lived JWT
    public function impersonate(User $user) {
        // extra safety: only allow impersonating tenant users (not other SuperAdmins)
        if ($user->hasRole('SuperAdmin')) abort(403,'Cannot impersonate SuperAdmin');
        $token = JWTAuth::fromUser($user);
        return response()->json([
            'impersonation' => true,
            'user' => $user->only('id','name','email','restaurant_id'),
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ]);
    }
}
