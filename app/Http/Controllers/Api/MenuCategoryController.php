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
     * Display a listing of the resource.
     */
    public function index()
    {
        return MenuCategory::where('restaurant_id', Tenant::id())->orderBy('name')->paginate();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMenuCategoryRequest $request) {
        return response()->json(MenuCategory::create($request->validated()), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(MenuCategory $category)
    {
        abort_unless($category->restaurant_id === Tenant::id(), 404);
        return $category;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMenuCategoryRequest $request, MenuCategory $category)
    {
        abort_unless($category->restaurant_id === Tenant::id(), 404);
        $category->update($request->validated());
        return $category;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MenuCategory $category)
    {
        abort_unless($category->restaurant_id === Tenant::id(), 404);
        $category->delete();
        return response()->noContent();
    }
}
