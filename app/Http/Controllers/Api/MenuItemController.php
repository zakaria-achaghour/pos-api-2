<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMenuItemRequest;
use App\Http\Requests\UpdateMenuItemRequest;
use App\Models\Category;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Infrastructure\Tenancy\Tenant;
use Illuminate\Support\Facades\Storage;

class MenuItemController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/items",
     *     tags={"Menu Items"},
     *     summary="Get list of menu items",
     *     description="Retrieve paginated list of menu items for the authenticated restaurant",
     *     security={{"bearer_token": {}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Alias for per_page - Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", example=50)
     *     ),
     *     @OA\Parameter(
     *         name="category_id",
     *         in="query",
     *         description="Filter by category ID",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="is_available",
     *         in="query",
     *         description="Filter by availability status (true/false or 1/0)",
     *         required=false,
     *         @OA\Schema(type="string", example="true")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search items by name",
     *         required=false,
     *         @OA\Schema(type="string", example="Caesar")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of menu items retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/MenuItem")
     *             ),
     *             @OA\Property(property="last_page", type="integer", example=3),
     *             @OA\Property(property="per_page", type="integer", example=15),
     *             @OA\Property(property="total", type="integer", example=35)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function index()
    {
        $perPage = request('per_page', request('limit', 15));
        
        $query = MenuItem::where('restaurant_id', Tenant::id())->with('category');
        
        // Filter by category
        if (request()->filled('category_id')) {
            // Ensure the category belongs to tenant
            abort_unless(
                Category::where('id', request('category_id'))
                ->where('restaurant_id', Tenant::id())->exists(), 404
            );
            $query->where('category_id', request('category_id'));
        }
        
        // Filter by is_available
        if (request()->has('is_available')) {
            $isAvailable = filter_var(request('is_available'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($isAvailable !== null) {
                $query->where('is_available', $isAvailable);
            }
        }
        
        // Search by name
        if (request()->filled('search')) {
            $query->where('name', 'ILIKE', '%' . request('search') . '%');
        }
        
        return $query->orderBy('name')->paginate($perPage);
    }

    /**
     * @OA\Post(
     *     path="/api/items",
     *     tags={"Menu Items"},
     *     summary="Create new menu item",
     *     description="Create a new menu item for the authenticated restaurant",
     *     security={{"bearer_token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Menu item data",
     *         @OA\JsonContent(
     *             required={"name", "price", "category_id"},
     *             @OA\Property(property="name", type="string", example="Caesar Salad"),
     *             @OA\Property(property="description", type="string", example="Fresh romaine lettuce with caesar dressing"),
     *             @OA\Property(property="price", type="number", format="float", example=12.99),
     *             @OA\Property(property="category_id", type="integer", example=1),
     *             @OA\Property(property="image_url", type="string", example="https://example.com/image.jpg"),
     *             @OA\Property(property="is_available", type="boolean", example=true),
     *             @OA\Property(property="preparation_time", type="integer", example=15),
     *             @OA\Property(property="calories", type="integer", example=250),
     *             @OA\Property(property="allergens", type="array", @OA\Items(type="string"), example={"gluten", "dairy"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Menu item created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/MenuItem")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Category not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function store(StoreMenuItemRequest $request)
    {
        $data = $request->validated();

        // Make sure category belongs to tenant
        abort_unless(
            Category::where('id', $data['category_id'])
            ->where('restaurant_id', Tenant::id())->exists(), 404
        );

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('menu-items', 'public');
            $data['image_url'] = Storage::url($path);
        }

        return response()->json(MenuItem::create($data), 201);
    }

    /**
     * @OA\Get(
     *     path="/api/items/{id}",
     *     tags={"Menu Items"},
     *     summary="Get specific menu item",
     *     description="Retrieve details of a specific menu item with category information",
     *     security={{"bearer_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Menu item ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Menu item details retrieved successfully",
     *         @OA\JsonContent(ref="#/components/schemas/MenuItem")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Menu item not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function show(MenuItem $item)
    {
        abort_unless($item->restaurant_id === Tenant::id(), 404);
        return $item->load('category');
    }

    /**
     * @OA\Put(
     *     path="/api/items/{id}",
     *     tags={"Menu Items"},
     *     summary="Update menu item",
     *     description="Update an existing menu item",
     *     security={{"bearer_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Menu item ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Updated menu item data",
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Updated Caesar Salad"),
     *             @OA\Property(property="description", type="string", example="Updated description"),
     *             @OA\Property(property="price", type="number", format="float", example=14.99),
     *             @OA\Property(property="category_id", type="integer", example=1),
     *             @OA\Property(property="is_available", type="boolean", example=false),
     *             @OA\Property(property="preparation_time", type="integer", example=20)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Menu item updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/MenuItem")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Menu item not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function update(UpdateMenuItemRequest $request, MenuItem $item)
    {
        abort_unless($item->restaurant_id === Tenant::id(), 404);
        $data = $request->validated();

        if (isset($data['category_id'])) {
            abort_unless(
                Category::where('id', $data['category_id'])
                ->where('restaurant_id', Tenant::id())->exists(), 404
            );
        }

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($item->image_url) {
                $oldPath = str_replace('/storage/', '', parse_url($item->image_url, PHP_URL_PATH));
                Storage::disk('public')->delete($oldPath);
            }

            $path = $request->file('image')->store('menu-items', 'public');
            $data['image_url'] = Storage::url($path);
        }

        $item->update($data);
        return $item->load('category');
    }

    /**
     * @OA\Delete(
     *     path="/api/items/{id}",
     *     tags={"Menu Items"},
     *     summary="Delete menu item",
     *     description="Delete an existing menu item",
     *     security={{"bearer_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Menu item ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Menu item deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Menu item not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function destroy(MenuItem $item)
    {
        abort_unless($item->restaurant_id === Tenant::id(), 404);
        $item->delete();
        return response()->noContent();
    }
}
