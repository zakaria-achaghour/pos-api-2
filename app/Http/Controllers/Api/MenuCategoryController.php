<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMenuCategoryRequest;
use App\Models\MenuCategory;
use Illuminate\Http\Request;
use Infrastructure\Tenancy\Tenant;

class MenuCategoryController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/categories",
     *     tags={"Menu Categories"},
     *     summary="Get list of menu categories",
     *     description="Retrieve paginated list of menu categories for the authenticated restaurant",
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
     *     @OA\Response(
     *         response=200,
     *         description="List of menu categories retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/MenuCategory")
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
        return MenuCategory::where('restaurant_id', Tenant::id())->orderBy('name')->paginate();
    }

    /**
     * @OA\Post(
     *     path="/api/categories",
     *     tags={"Menu Categories"},
     *     summary="Create new menu category",
     *     description="Create a new menu category for the authenticated restaurant",
     *     security={{"bearer_token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Menu category data",
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Appetizers"),
     *             @OA\Property(property="description", type="string", example="Light meals to start"),
     *             @OA\Property(property="display_order", type="integer", example=1),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Menu category created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/MenuCategory")
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
    public function store(StoreMenuCategoryRequest $request) {
        return response()->json(MenuCategory::create($request->validated()), 201);
    }

    /**
     * @OA\Get(
     *     path="/api/categories/{id}",
     *     tags={"Menu Categories"},
     *     summary="Get specific menu category",
     *     description="Retrieve details of a specific menu category",
     *     security={{"bearer_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Menu category ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Menu category details retrieved successfully",
     *         @OA\JsonContent(ref="#/components/schemas/MenuCategory")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Menu category not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function show(MenuCategory $category)
    {
        abort_unless($category->restaurant_id === Tenant::id(), 404);
        return $category;
    }

    /**
     * @OA\Put(
     *     path="/api/categories/{id}",
     *     tags={"Menu Categories"},
     *     summary="Update menu category",
     *     description="Update an existing menu category",
     *     security={{"bearer_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Menu category ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Updated menu category data",
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Updated Appetizers"),
     *             @OA\Property(property="description", type="string", example="Updated description"),
     *             @OA\Property(property="display_order", type="integer", example=2),
     *             @OA\Property(property="is_active", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Menu category updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/MenuCategory")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Menu category not found",
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
    public function update(UpdateMenuCategoryRequest $request, MenuCategory $category)
    {
        abort_unless($category->restaurant_id === Tenant::id(), 404);
        $category->update($request->validated());
        return $category;
    }

    /**
     * @OA\Delete(
     *     path="/api/categories/{id}",
     *     tags={"Menu Categories"},
     *     summary="Delete menu category",
     *     description="Delete an existing menu category",
     *     security={{"bearer_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Menu category ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Menu category deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Menu category not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function destroy(MenuCategory $category)
    {
        abort_unless($category->restaurant_id === Tenant::id(), 404);
        $category->delete();
        return response()->noContent();
    }
}
