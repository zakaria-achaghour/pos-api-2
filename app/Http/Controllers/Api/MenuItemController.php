<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Infrastructure\Tenancy\Tenant;

class MenuItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $q = MenuItem::where('restaurant_id', Tenant::id())->with('category');
        if (request('category_id')) {
            // ensure the category belongs to tenant
            abort_unless(
                MenuCategory::where('id', request('category_id'))
                ->where('restaurant_id', Tenant::id())->exists(), 404
            );
            $q->where('category_id', request('category_id'));
        }
        return $q->orderBy('name')->paginate();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMenuItemRequest  $request)
    {
         $data = $request->validated();

        // Make sure category belongs to tenant
        abort_unless(
            MenuCategory::where('id', $data['category_id'])
            ->where('restaurant_id', Tenant::id())->exists(), 404
        );

        return response()->json(MenuItem::create($data), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(MenuItem $item)
    {
        abort_unless($item->restaurant_id === Tenant::id(), 404);
        return $item->load('category');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMenuItemRequest  $request,  MenuItem $item)
    {
         abort_unless($item->restaurant_id === Tenant::id(), 404);
        $data = $r->validated();

        if (isset($data['category_id'])) {
            abort_unless(
                MenuCategory::where('id', $data['category_id'])
                ->where('restaurant_id', Tenant::id())->exists(), 404
            );
        }

        $item->update($data);
        return $item->load('category');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MenuItem $item)
    {
         abort_unless($item->restaurant_id === Tenant::id(), 404);
        $item->delete();
        return response()->noContent();
    }
}
