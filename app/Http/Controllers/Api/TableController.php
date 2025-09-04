<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTableRequest;
use App\Http\Requests\UpdateTableRequest;
use App\Models\Table;
use Illuminate\Http\Request;
use Infrastructure\Tenancy\Tenant;

class TableController extends Controller
{

    public function __construct() {
        $this->middleware(['role:Owner|Manager'])->only(['store','update','destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
          return Table::where('restaurant_id', Tenant::id())->orderBy('name')->paginate();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTableRequest  $request)
    {
         $data = $request->validated();
        // restaurant_id set by trait
        return response()->json(Table::create($data), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Table $table)
    {
        abort_unless($table->restaurant_id === Tenant::id(), 404);
        return $table;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTableRequest $request, Table $table) {
        abort_unless($table->restaurant_id === Tenant::id(), 404);
        $table->update($request->validated());
        return $table;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Table $table) {
        abort_unless($table->restaurant_id === Tenant::id(), 404);
        $table->delete();
        return response()->noContent();
    }
}
