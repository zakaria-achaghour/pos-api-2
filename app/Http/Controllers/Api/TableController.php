<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RestoreTableRequest;
use App\Http\Requests\StoreTableRequest;
use App\Http\Requests\UpdateTableRequest;
use App\Models\Table;
use Illuminate\Http\Request;
use Infrastructure\Tenancy\Tenant;

/**
 * @OA\Tag(
 *     name="Tables",
 *     description="Table management operations for restaurant"
 * )
 */
class TableController extends Controller
{

    public function __construct() {
        // $this->middleware(['role:Owner|Manager'])->only(['store','update','destroy']);
    }

    /**
     * @OA\Get(
     *     path="/api/tables",
     *     tags={"Tables"},
     *     summary="Get list of tables",
     *     description="Retrieve a paginated list of tables for the current restaurant",
     *     security={{"bearer_token": {}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=100, example=15)
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by table status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"available", "occupied", "reserved", "maintenance", "out-of-order"})
     *     ),
     *     @OA\Parameter(
     *         name="section",
     *         in="query",
     *         description="Filter by table section",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="capacity",
     *         in="query",
     *         description="Filter by exact table capacity",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1)
     *     ),
     *     @OA\Parameter(
     *         name="min_capacity",
     *         in="query",
     *         description="Filter by minimum table capacity",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1)
     *     ),
     *     @OA\Parameter(
     *         name="max_capacity",
     *         in="query",
     *         description="Filter by maximum table capacity",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1)
     *     ),
     *     @OA\Parameter(
     *         name="shape",
     *         in="query",
     *         description="Filter by table shape",
     *         required=false,
     *         @OA\Schema(type="string", enum={"round", "square", "rectangular", "oval"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tables retrieved successfully",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/PaginatedResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(
     *                         property="data",
     *                         type="array",
     *                         @OA\Items(ref="#/components/schemas/Table")
     *                     )
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function index(Request $request)
    {
        try {
            $query = Table::where('restaurant_id', Tenant::id());
            
            // Handle soft deletes
            if ($request->boolean('only_deleted')) {
                $query->onlyTrashed();
            } elseif ($request->boolean('with_deleted')) {
                $query->withTrashed();
            }
            
            // Filter by status if provided
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            
            // Filter by section if provided
            if ($request->has('section')) {
                $query->where('section', $request->section);
            }
            
            // Filter by capacity if provided (exact match)
            if ($request->has('capacity')) {
                $query->where('capacity', $request->capacity);
            }
            
            // Filter by minimum capacity
            if ($request->has('min_capacity')) {
                $query->where('capacity', '>=', $request->min_capacity);
            }
            
            // Filter by maximum capacity
            if ($request->has('max_capacity')) {
                $query->where('capacity', '<=', $request->max_capacity);
            }
            
            // Filter by shape if provided
            if ($request->has('shape')) {
                $query->where('shape', $request->shape);
            }
            
            // Order by table number
            $query->orderBy('number');
            
            // Handle pagination
            $perPage = $request->input('per_page', 15);
            $perPage = min(max($perPage, 1), 100); // Limit between 1 and 100
            
            $tables = $query->paginate($perPage);
            
            return response()->json($tables);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/tables",
     *     tags={"Tables"},
     *     summary="Create a new table",
     *     description="Create a new table for the restaurant",
     *     security={{"bearer_token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/StoreTableRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Table created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Table")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request - Validation failed",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Insufficient permissions",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function store(StoreTableRequest  $request)
    {
         $data = $request->validated();
        // restaurant_id set by trait
        return response()->json(Table::create($data), 201);
    }

    /**
     * @OA\Get(
     *     path="/api/tables/{id}",
     *     tags={"Tables"},
     *     summary="Get a specific table",
     *     description="Retrieve details of a specific table",
     *     security={{"bearer_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Table ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Table retrieved successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Table")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Table not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function show(Table $table)
    {
        abort_unless($table->restaurant_id === Tenant::id(), 404);
        return $table;
    }

    /**
     * @OA\Put(
     *     path="/api/tables/{id}",
     *     tags={"Tables"},
     *     summary="Update a table",
     *     description="Update details of a specific table",
     *     security={{"bearer_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Table ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/UpdateTableRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Table updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Table")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request - Validation failed",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Insufficient permissions",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Table not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function update(UpdateTableRequest $request, Table $table) {
        abort_unless($table->restaurant_id === Tenant::id(), 404);
        $table->update($request->validated());
        return $table;
    }

    /**
     * @OA\Delete(
     *     path="/api/tables/{id}",
     *     tags={"Tables"},
     *     summary="Delete a table",
     *     description="Delete a specific table from the restaurant",
     *     security={{"bearer_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Table ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Table deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Insufficient permissions",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Table not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function destroy(Table $table) {
        abort_unless($table->restaurant_id === Tenant::id(), 404);
        $table->delete();
        return response()->noContent();
    }

    /**
     * @OA\Post(
     *     path="/api/tables/{id}/restore",
     *     tags={"Tables"},
     *     summary="Restore a soft-deleted table",
     *     description="Restore a previously deleted table",
     *     security={{"bearer_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Table ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Table restored successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Table restored successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Table")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Table not found"
     *     )
     * )
     */
    public function restore(int $id)
    {
        $table = Table::withTrashed()->findOrFail($id);
        abort_unless($table->restaurant_id === Tenant::id(), 404);
        
        $table->restore();

        return response()->json([
            'message' => 'Table restored successfully',
            'data' => $table->fresh()
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/tables/{id}/force",
     *     tags={"Tables"},
     *     summary="Permanently delete a table",
     *     description="Permanently delete a table from the database (cannot be undone)",
     *     security={{"bearer_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Table ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Table permanently deleted"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Table not found"
     *     )
     * )
     */
    public function forceDelete(int $id)
    {
        $table = Table::withTrashed()->findOrFail($id);
        abort_unless($table->restaurant_id === Tenant::id(), 404);
        
        $table->forceDelete();

        return response()->noContent();
    }

    /**
     * @OA\Put(
     *     path="/api/tables/{table}/status",
     *     tags={"Tables"},
     *     summary="Update table status",
     *     description="Update the status of a specific table",
     *     security={{"bearer_token": {}}},
     *     @OA\Parameter(
     *         name="table",
     *         in="path",
     *         description="Table ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 enum={"available", "occupied", "reserved", "maintenance", "out-of-order"},
     *                 description="New status for the table",
     *                 example="occupied"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Table status updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Table status updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Table")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Table not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     * 
     * @OA\Patch(
     *     path="/api/tables/{table}/status",
     *     tags={"Tables"},
     *     summary="Update table status",
     *     description="Update the status of a specific table",
     *     security={{"bearer_token": {}}},
     *     @OA\Parameter(
     *         name="table",
     *         in="path",
     *         description="Table ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 enum={"available", "occupied", "reserved", "maintenance", "out-of-order"},
     *                 description="New status for the table",
     *                 example="occupied"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Table status updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Table status updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Table")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Table not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function updateStatus(Request $request, Table $table)
    {
        abort_unless($table->restaurant_id === Tenant::id(), 404);

        $validated = $request->validate([
            'status' => 'required|in:available,occupied,reserved,maintenance,out-of-order'
        ]);

        $oldStatus = $table->status;
        $table->update(['status' => $validated['status']]);

        // Fire event for real-time updates
        event(new \App\Events\TableStatusChanged($table, $oldStatus, $validated['status']));

        return response()->json([
            'message' => 'Table status updated successfully',
            'data' => $table->fresh()
        ]);
    }
}
