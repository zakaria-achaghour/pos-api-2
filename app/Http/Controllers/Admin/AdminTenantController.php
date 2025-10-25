<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\Table;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Infrastructure\Tenancy\Tenant;
use Tymon\JWTAuth\Facades\JWTAuth;

class AdminTenantController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/admin/restaurants",
     *     tags={"Admin - Restaurants"},
     *     summary="List all restaurants",
     *     description="Retrieve paginated list of all restaurants (SuperAdmin only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by restaurant name, city, or address",
     *         required=false,
     *         @OA\Schema(type="string", example="Golden Fork")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by restaurant status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"active", "inactive", "suspended"})
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page (max 100)",
     *         required=false,
     *         @OA\Schema(type="integer", example=20, minimum=1, maximum=100)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Restaurants list retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="The Golden Fork"),
     *                     @OA\Property(property="slug", type="string", example="golden-fork"),
     *                     @OA\Property(property="address", type="string", example="123 Main Street, Downtown"),
     *                     @OA\Property(property="city", type="string", example="New York"),
     *                     @OA\Property(property="country", type="string", example="USA"),
     *                     @OA\Property(property="phone", type="string", example="+1-555-0101"),
     *                     @OA\Property(property="email", type="string", example="info@goldenfork.com"),
     *                     @OA\Property(property="cuisine_type", type="string", example="American"),
     *                     @OA\Property(property="status", type="string", example="active"),
     *                     @OA\Property(property="subscription_type", type="string", example="premium"),
     *                     @OA\Property(property="created_at", type="string", format="datetime"),
     *                     @OA\Property(property="updated_at", type="string", format="datetime")
     *                 )
     *             ),
     *             @OA\Property(property="per_page", type="integer", example=20),
     *             @OA\Property(property="total", type="integer", example=4),
     *             @OA\Property(property="last_page", type="integer", example=1)
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
     *         description="Forbidden - SuperAdmin access required",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="This action is unauthorized.")
     *         )
     *     )
     * )
     */
   public function listRestaurants(Request $r) {
        $q = Restaurant::query();
        
        // Handle search parameter
        if ($search = $r->query('search') ?: $r->query('q')) {
            $q->where(function($query) use ($search) {
                $query->where('name','ilike',"%{$search}%")
                      ->orWhere('city','ilike',"%{$search}%")
                      ->orWhere('address','ilike',"%{$search}%");
            });
        }
        
        // Handle status filter
        if ($status = $r->query('status')) {
            $q->where('status', $status);
        }
        
        // Handle per_page parameter
        $perPage = $r->query('per_page', 20);
        $perPage = min($perPage, 100); // Max 100 per page
        
        return $q->orderBy('id','desc')->paginate($perPage);
    }

    public function createRestaurant(Request $r) {
        $data = $r->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'currency' => 'nullable|string|size:3',
            'timezone' => 'nullable|string|max:50',
            'status' => 'nullable|in:active,inactive,suspended',
            'owner_name' => 'required|string|max:255',
            'owner_email' => 'required|email',
            'subscription_type' => 'nullable|in:basic,premium,enterprise',
        ]);

        try {
            // Create restaurant with default values
            $restaurant = Restaurant::create([
                'name' => $data['name'],
                'slug' => $data['slug'] ?: \Str::slug($data['name']),
                'address' => $data['address'] ?? '',
                'city' => $data['city'] ?? '',
                'country' => $data['country'] ?? 'USA',
                'currency' => $data['currency'] ?? 'USD',
                'timezone' => $data['timezone'] ?? 'UTC',
                'status' => $data['status'] ?? 'active',
                'is_active' => true,
            ]);
            
            return response()->json([
                'message' => 'Restaurant created successfully',
                'restaurant' => $restaurant,
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create restaurant',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function showRestaurant(Restaurant $restaurant) {
        return response()->json($restaurant->load(['users' => function($q) {
            $q->whereHas('roles', function($roleQuery) {
                $roleQuery->where('name', 'Owner');
            });
        }]));
    }

    public function updateRestaurant(Request $r, Restaurant $restaurant) {
        $data = $r->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:255|unique:restaurants,slug,' . $restaurant->id,
            'address' => 'sometimes|string',
            'city' => 'sometimes|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'sometimes|string|max:100',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'cuisine_type' => 'nullable|string|max:100',
            'currency' => 'sometimes|string|size:3|in:USD,EUR,GBP,MAD,CAD,AUD',
            'timezone' => 'sometimes|string|max:50',
            'tax_rate' => 'nullable|numeric|between:0,100',
            'service_charge' => 'nullable|numeric|between:0,100',
            'status' => 'sometimes|in:active,inactive,suspended',
            'subscription_type' => 'sometimes|in:basic,premium,enterprise',
            'subscription_start' => 'nullable|date',
            'subscription_end' => 'nullable|date|after:subscription_start',
        ]);

        $restaurant->update($data);
        
        return response()->json([
            'message' => 'Restaurant updated successfully',
            'restaurant' => $restaurant->fresh()
        ]);
    }

    public function deleteRestaurant(Restaurant $restaurant) {
        // Check if restaurant has data that would prevent deletion
        $hasOrders = Tenant::with($restaurant->id, fn() => Order::exists());
        
        if ($hasOrders) {
            return response()->json([
                'message' => 'Cannot delete restaurant with existing orders. Set status to inactive instead.'
            ], 422);
        }
        
        DB::beginTransaction();
        try {
            // Delete associated users
            User::where('restaurant_id', $restaurant->id)->delete();
            
            // Delete restaurant
            $restaurant->delete();
            
            DB::commit();
            
            return response()->json([
                'message' => 'Restaurant deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Failed to delete restaurant',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getRestaurantStats(Restaurant $restaurant) {
        return Tenant::with($restaurant->id, function () {
            $today = now()->toDateString();
            $thisMonth = now()->format('Y-m');
            
            return [
                'total_orders' => Order::count(),
                'orders_today' => Order::whereDate('placed_at', $today)->count(),
                'orders_this_month' => Order::whereDate('placed_at', 'like', $thisMonth.'%')->count(),
                'total_revenue' => DB::table('payments')
                    ->join('orders','orders.id','=','payments.order_id')
                    ->sum('amount'),
                'revenue_today' => DB::table('payments')
                    ->join('orders','orders.id','=','payments.order_id')
                    ->whereDate('payments.paid_at', $today)
                    ->sum('amount'),
                'revenue_this_month' => DB::table('payments')
                    ->join('orders','orders.id','=','payments.order_id')
                    ->whereDate('payments.paid_at', 'like', $thisMonth.'%')
                    ->sum('amount'),
                'total_tables' => Table::count(),
                'total_menu_items' => MenuItem::count(),
                'total_staff' => User::count(),
                'active_staff' => User::whereHas('roles', function($q) {
                    $q->whereIn('name', ['Manager', 'Cashier', 'Waiter', 'Kitchen']);
                })->count(),
            ];
        });
    }

    public function overview(Restaurant $restaurant) {
        return Tenant::with($restaurant->id, function () {
            return [
                'tables' => Table::count(),
                'categories' => Category::count(),
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
        return Tenant::with($restaurant->id, fn() => Category::orderBy('name')->paginate(100));
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
