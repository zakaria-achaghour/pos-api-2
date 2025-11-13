<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePermissionRequest;
use App\Http\Requests\Admin\UpdatePermissionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionController extends Controller
{
    protected PermissionRegistrar $permissionRegistrar;

    public function __construct(PermissionRegistrar $permissionRegistrar)
    {
        $this->permissionRegistrar = $permissionRegistrar;
    }

    /**
     * @OA\Get(
     *     path="/api/admin/permissions",
     *     tags={"Admin - Permissions"},
     *     summary="List all permissions",
     *     description="Get paginated list of permissions. SuperAdmin only.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by permission name",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page (default: all)",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permissions retrieved successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="view-menu"),
     *                 @OA\Property(property="guard_name", type="string", example="api"),
     *                 @OA\Property(property="created_at", type="string"),
     *                 @OA\Property(property="updated_at", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - SuperAdmin only")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = Permission::where('guard_name', 'api');

        if ($request->has('search')) {
            // Use whereRaw for case-insensitive search that works with both PostgreSQL and SQLite
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%']);
            });
        }

        $query->orderBy('name');

        // If per_page is specified, paginate; otherwise return all
        if ($request->has('per_page')) {
            $permissions = $query->paginate($request->per_page);
        } else {
            $permissions = $query->get();
        }

        return response()->json($permissions);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/permissions",
     *     tags={"Admin - Permissions"},
     *     summary="Create a new permission",
     *     description="Create a new permission. SuperAdmin only.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="manage-inventory")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Permission created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=42),
     *             @OA\Property(property="name", type="string", example="manage-inventory"),
     *             @OA\Property(property="guard_name", type="string", example="api")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=403, description="Forbidden - SuperAdmin only")
     * )
     */
    public function store(StorePermissionRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $permission = Permission::create([
                'name' => $request->name,
                'guard_name' => 'api',
            ]);

            $this->permissionRegistrar->forgetCachedPermissions();

            DB::commit();

            return response()->json($permission, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create permission',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/admin/permissions/{id}",
     *     tags={"Admin - Permissions"},
     *     summary="Get a specific permission",
     *     description="Retrieve details of a specific permission. SuperAdmin only.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Permission retrieved successfully"),
     *     @OA\Response(response=404, description="Permission not found"),
     *     @OA\Response(response=403, description="Forbidden - SuperAdmin only")
     * )
     */
    public function show(Permission $permission): JsonResponse
    {
        return response()->json($permission);
    }

    /**
     * @OA\Put(
     *     path="/api/admin/permissions/{id}",
     *     tags={"Admin - Permissions"},
     *     summary="Update a permission",
     *     description="Update permission name. SuperAdmin only.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="manage-full-inventory")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Permission updated successfully"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=403, description="Forbidden - SuperAdmin only")
     * )
     */
    public function update(UpdatePermissionRequest $request, Permission $permission): JsonResponse
    {
        try {
            DB::beginTransaction();

            $permission->update([
                'name' => $request->name,
            ]);

            $this->permissionRegistrar->forgetCachedPermissions();

            DB::commit();

            return response()->json($permission);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update permission',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/permissions/{id}",
     *     tags={"Admin - Permissions"},
     *     summary="Delete a permission",
     *     description="Delete a permission. SuperAdmin only.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Permission deleted successfully"),
     *     @OA\Response(response=422, description="Cannot delete - permission is in use"),
     *     @OA\Response(response=403, description="Forbidden - SuperAdmin only")
     * )
     */
    public function destroy(Permission $permission): JsonResponse
    {
        // Check if permission is assigned to any role
        $rolesCount = DB::table('role_has_permissions')
            ->where('permission_id', $permission->id)
            ->count();

        if ($rolesCount > 0) {
            return response()->json([
                'message' => "Cannot delete permission. It is assigned to {$rolesCount} role(s)"
            ], 422);
        }

        try {
            DB::beginTransaction();

            $permission->delete();
            $this->permissionRegistrar->forgetCachedPermissions();

            DB::commit();

            return response()->json([
                'message' => 'Permission deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to delete permission',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
