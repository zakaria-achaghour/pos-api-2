<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
     *         @OA\Schema(type="string", enum={"available", "occupied", "reserved", "maintenance"})
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
    public function index()
    {
        try {
            $tables = Table::where('restaurant_id', Tenant::id())->orderBy('number')->paginate();
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
}
