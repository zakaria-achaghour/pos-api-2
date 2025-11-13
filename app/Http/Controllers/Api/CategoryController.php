<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use Infrastructure\Tenancy\Tenant;

class CategoryController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/categories",
     *     tags={"Categories"},
     *     summary="Get list of categories",
     *     description="Retrieve paginated list of categories for the authenticated restaurant",
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
     *         name="is_active",
     *         in="query",
     *         description="Filter by active status (true/false or 1/0)",
     *         required=false,
     *         @OA\Schema(type="string", example="true")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search categories by name",
     *         required=false,
     *         @OA\Schema(type="string", example="Pizza")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(ref="#/components/schemas/Category")
     *             ),
     *             @OA\Property(property="links", type="object"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index()
    {
        $perPage = request('per_page', request('limit', 15));
        
        $query = Category::where('restaurant_id', Tenant::id());

        // Filter by is_active
        if (request()->has('is_active')) {
            $isActive = filter_var(request('is_active'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($isActive !== null) {
                $query->where('is_active', $isActive);
            }
        }

        // Search by name
        if (request()->filled('search')) {
            $query->where('name', 'ILIKE', '%' . request('search') . '%');
        }

        return $query->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * @OA\Post(
     *     path="/api/categories",
     *     tags={"Categories"},
     *     summary="Create a new category",
     *     description="Create a new category for the authenticated restaurant",
     *     security={{"bearer_token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", maxLength=120, example="Appetizers"),
     *             @OA\Property(property="description", type="string", maxLength=200, example="Starters and small plates"),
     *             @OA\Property(property="is_active", type="boolean", example=true),
     *             @OA\Property(property="sort_order", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Category created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Category")
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreCategoryRequest $request)
    {
        return response()->json(Category::create($request->validated()), 201);
    }

    /**
     * @OA\Get(
     *     path="/api/categories/{id}",
     *     tags={"Categories"},
     *     summary="Get a specific category",
     *     description="Retrieve a specific category by ID",
     *     security={{"bearer_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Category ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(ref="#/components/schemas/Category")
     *     ),
     *     @OA\Response(response=404, description="Category not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function show(Category $category)
    {
        return response()->json($category->load(['menuItems', 'creator:id,name,email', 'updater:id,name,email']));
    }

    /**
     * @OA\Put(
     *     path="/api/categories/{id}",
     *     tags={"Categories"},
     *     summary="Update a category",
     *     description="Update an existing category",
     *     security={{"bearer_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Category ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", maxLength=120, example="Appetizers"),
     *             @OA\Property(property="description", type="string", maxLength=200, example="Starters and small plates"),
     *             @OA\Property(property="is_active", type="boolean", example=true),
     *             @OA\Property(property="sort_order", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Category updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Category")
     *     ),
     *     @OA\Response(response=404, description="Category not found"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $category->update($request->validated());
        return response()->json($category);
    }

    /**
     * @OA\Delete(
     *     path="/api/categories/{id}",
     *     tags={"Categories"},
     *     summary="Delete a category",
     *     description="Delete an existing category",
     *     security={{"bearer_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Category ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=204, description="Category deleted successfully"),
     *     @OA\Response(response=404, description="Category not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function destroy(Category $category)
    {
        $category->delete();
        return response()->json(null, 204);
    }
}
