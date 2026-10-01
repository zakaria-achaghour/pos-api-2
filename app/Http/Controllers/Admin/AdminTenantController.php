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
     *     security={{"bearer_token":{}}},
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
            'description' => 'nullable|string',
            'subdomain' => 'nullable|string|max:255|unique:restaurants,subdomain',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'license_number' => 'nullable|string|max:100',
            'tax_number' => 'nullable|string|max:100',
            'cuisine_type' => 'nullable|string|max:100',
            'currency' => 'nullable|string|size:3|in:USD,EUR,GBP,MAD,CAD,AUD',
            'timezone' => 'nullable|string|max:50',
            'tax_rate' => 'nullable|numeric|between:0,100',
            'service_charge' => 'nullable|numeric|between:0,100',
            'status' => 'nullable|in:active,inactive,suspended',
            'subscription_type' => 'nullable|in:basic,premium,enterprise',
            'subscription_plan' => 'nullable|in:basic,premium,enterprise', // alias for subscription_type
            'subscription_start' => 'nullable|date',
            'subscription_end' => 'nullable|date|after:subscription_start',
            'owner_name' => 'required|string|max:255',
            'owner_email' => 'required|email|unique:users,email',
            'owner_phone' => 'nullable|string|max:20',
            'owner_password' => 'nullable|string|min:6',
        ]);

        DB::beginTransaction();
        try {
            // Create restaurant with all provided fields
            $restaurant = Restaurant::create([
                'name' => $data['name'],
                'subdomain' => $data['subdomain'] ?? \Str::slug($data['name']),
                'address' => $data['address'] ?? '',
                'city' => $data['city'] ?? '',
                'postal_code' => $data['postal_code'] ?? null,
                'country' => $data['country'] ?? 'USA',
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'website' => $data['website'] ?? null,
                'cuisine_type' => $data['cuisine_type'] ?? null,
                'currency' => $data['currency'] ?? 'USD',
                'timezone' => $data['timezone'] ?? 'UTC',
                'tax_rate' => $data['tax_rate'] ?? 0,
                'service_charge' => $data['service_charge'] ?? 0,
                'status' => $data['status'] ?? 'active',
                'subscription_type' => $data['subscription_type'] ?? $data['subscription_plan'] ?? 'basic',
                'subscription_start' => $data['subscription_start'] ?? now(),
                'subscription_end' => $data['subscription_end'] ?? null,
                'is_active' => true,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            // Create owner user
            $owner = User::create([
                'name' => $data['owner_name'],
                'email' => $data['owner_email'],
                'phone' => $data['owner_phone'] ?? null,
                'password' => Hash::make($data['owner_password'] ?? 'password123'),
                'restaurant_id' => $restaurant->id,
                'is_active' => true,
            ]);

            // Assign Owner role
            $ownerRole = \Spatie\Permission\Models\Role::where('name', 'Owner')
                ->where('guard_name', 'api')
                ->first();
            
            if ($ownerRole) {
                $owner->assignRole($ownerRole);
            }

            DB::commit();
            
            return response()->json([
                'message' => 'Restaurant and owner created successfully',
                'restaurant' => $restaurant->fresh(),
                'owner' => $owner->fresh(['roles']),
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
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
            'subdomain' => 'sometimes|string|max:255|unique:restaurants,subdomain,' . $restaurant->id,
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
        return Tenant::with($restaurant->id, function () use ($restaurant) {
            $today = now()->toDateString();
            $thisMonth = [now()->startOfMonth(), now()->endOfMonth()];

            return [
                'total_orders' => Order::count(),
                'orders_today' => Order::whereDate('placed_at', $today)->count(),
                'orders_this_month' => Order::whereBetween('placed_at', $thisMonth)->count(),
                'total_revenue' => $this->paymentsQuery($restaurant)->sum('payments.amount'),
                'revenue_today' => $this->paymentsQuery($restaurant)
                    ->whereDate('payments.paid_at', $today)
                    ->sum('payments.amount'),
                'revenue_this_month' => $this->paymentsQuery($restaurant)
                    ->whereBetween('payments.paid_at', $thisMonth)
                    ->sum('payments.amount'),
                'total_tables' => Table::count(),
                'total_menu_items' => MenuItem::count(),
                'total_staff' => $restaurant->users()->count(),
                'active_staff' => $restaurant->users()->whereHas('roles', function($q) {
                    $q->whereIn('name', ['Manager', 'Cashier', 'Waiter', 'Kitchen']);
                })->count(),
            ];
        });
    }

    public function overview(Restaurant $restaurant) {
        return Tenant::with($restaurant->id, function () use ($restaurant) {
            return [
                'tables' => Table::count(),
                'categories' => Category::count(),
                'items' => MenuItem::count(),
                'orders_today' => Order::whereDate('placed_at', now()->toDateString())->count(),
                'sales_today' => $this->paymentsQuery($restaurant)
                    ->whereDate('payments.paid_at', now()->toDateString())
                    ->sum('payments.amount'),
            ];
        });
    }

    public function tables(Restaurant $restaurant) {
        return Tenant::with($restaurant->id, fn() => Table::orderBy('number')->paginate(50));
    }

    public function categories(Restaurant $restaurant) {
        return Tenant::with($restaurant->id, fn() => Category::orderBy('name')->paginate(100));
    }

    public function items(Restaurant $restaurant) {
        return Tenant::with($restaurant->id, fn() => MenuItem::with('category')->orderBy('name')->paginate(100));
    }

    public function orders(Restaurant $restaurant, Request $r) {
        return Tenant::with($restaurant->id, function () use ($r) {
            $q = Order::with(['table','waiter','cashier:id,name'])->orderByDesc('id');
            if ($status = $r->query('status')) $q->where('status', $status);
            if ($date = $r->query('date')) $q->whereDate('placed_at', $date);
            return $q->paginate(50);
        });
    }

    public function dailySummary(Restaurant $restaurant, Request $r) {
        $date = $r->validate(['date'=>'required|date'])['date'];
        return Tenant::with($restaurant->id, function () use ($restaurant, $date) {
            $by = $this->paymentsQuery($restaurant)
                ->whereDate('payments.paid_at', $date)
                ->select('payments.method', DB::raw('SUM(payments.amount) as total'))
                ->groupBy('payments.method')
                ->pluck('total','method');
            $ordersCount = Order::where('status','completed')->whereDate('paid_at',$date)->count();
            $total = array_sum(array_map('floatval', $by->toArray()));
            $money = fn ($value) => number_format((float) $value, 2, '.', '');
            return [
                'date' => $date,
                'orders_count' => $ordersCount,
                'total_sales'  => $money($total),
                'by_method'    => [
                    'cash'   => $money($by['cash'] ?? 0),
                    'card'   => $money($by['card'] ?? 0),
                    'mobile' => $money($by['mobile'] ?? 0),
                    'other'  => $money($by['other'] ?? 0),
                ],
            ];
        });
    }

    /**
     * Payments joined to their orders, limited to one restaurant.
     * Query builder calls are not covered by the Eloquent tenant scope.
     */
    private function paymentsQuery(Restaurant $restaurant)
    {
        return DB::table('payments')
            ->join('orders', 'orders.id', '=', 'payments.order_id')
            ->where('orders.restaurant_id', $restaurant->id);
    }

    // Optional: Impersonate a tenant user -> returns a short-lived JWT
    public function impersonate(User $user) {
        // extra safety: only allow impersonating tenant users (not other SuperAdmins)
        if ($user->hasRole('SuperAdmin')) abort(403,'Cannot impersonate SuperAdmin');
        $token = JWTAuth::fromUser($user);
        return response()->json([
            'impersonation' => true,
            'user' => $this->transformUserWithBranding($user),
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ]);
    }
}
