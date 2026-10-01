<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    protected PermissionRegistrar $permissionRegistrar;

    public function __construct(PermissionRegistrar $permissionRegistrar)
    {
        $this->permissionRegistrar = $permissionRegistrar;
    }

    /**
     * @OA\Get(
     *     path="/api/admin/roles",
     *     tags={"Admin - Roles"},
     *     summary="List all roles",
     *     description="Get paginated list of roles with permissions and user counts. SuperAdmin only.",
     *     security={{"bearer_token":{}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by role name",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Roles retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=7),
     *                     @OA\Property(property="name", type="string", example="Inventory Manager"),
     *                     @OA\Property(property="guard_name", type="string", example="api"),
     *                     @OA\Property(property="permissions", type="array", @OA\Items(type="string")),
     *                     @OA\Property(property="users_count", type="integer", example=4),
     *                     @OA\Property(property="created_at", type="string"),
     *                     @OA\Property(property="updated_at", type="string")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - SuperAdmin only")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = Role::where('guard_name', 'api')
            ->with(['permissions:id,name'])
            ->withCount('users');

        if ($request->has('search')) {
            // Use whereRaw for case-insensitive search that works with both PostgreSQL and SQLite
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%']);
            });
        }

        $perPage = $request->input('per_page', 15);
        $roles = $query->orderBy('name')->paginate($perPage);

        // Transform permissions to array of names
        $roles->getCollection()->transform(function ($role) {
            $role->permissions = $role->permissions->pluck('name')->toArray();
            return $role;
        });

        return response()->json($roles);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/roles",
     *     tags={"Admin - Roles"},
     *     summary="Create a new role",
     *     description="Create a new role with permissions. SuperAdmin only.",
     *     security={{"bearer_token":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Inventory Manager"),
     *             @OA\Property(property="permissions", type="array", @OA\Items(type="string", example="view-menu"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Role created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=7),
     *             @OA\Property(property="name", type="string", example="Inventory Manager"),
     *             @OA\Property(property="guard_name", type="string", example="api"),
     *             @OA\Property(property="permissions", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="users_count", type="integer", example=0)
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=403, description="Forbidden - SuperAdmin only")
     * )
     */
    public function store(StoreRoleRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $role = Role::create([
                'name' => $request->name,
                'guard_name' => 'api',
            ]);

            if ($request->has('permissions')) {
                $role->syncPermissions($request->permissions);
            }

            $this->permissionRegistrar->forgetCachedPermissions();

            DB::commit();

            $role->load(['permissions:id,name']);
            $role->loadCount('users');
            $role->permissions = $role->permissions->pluck('name')->toArray();

            return response()->json($role, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create role',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/admin/roles/{id}",
     *     tags={"Admin - Roles"},
     *     summary="Get a specific role",
     *     description="Retrieve details of a specific role. SuperAdmin only.",
     *     security={{"bearer_token":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Role retrieved successfully"),
     *     @OA\Response(response=404, description="Role not found"),
     *     @OA\Response(response=403, description="Forbidden - SuperAdmin only")
     * )
     */
    public function show(Role $role): JsonResponse
    {
        $role->load(['permissions:id,name']);
        $role->loadCount('users');
        $role->permissions = $role->permissions->pluck('name')->toArray();

        return response()->json($role);
    }

    /**
     * @OA\Put(
     *     path="/api/admin/roles/{id}",
     *     tags={"Admin - Roles"},
     *     summary="Update a role",
     *     description="Update role name and permissions. Cannot update SuperAdmin role. SuperAdmin only.",
     *     security={{"bearer_token":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Senior Manager"),
     *             @OA\Property(property="permissions", type="array", @OA\Items(type="string"))
     *         )
     *     ),
     *     @OA\Response(response=200, description="Role updated successfully"),
     *     @OA\Response(response=422, description="Validation error or cannot update SuperAdmin"),
     *     @OA\Response(response=403, description="Forbidden - SuperAdmin only")
     * )
     */
    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        // Prevent updating SuperAdmin role
        if ($role->name === 'SuperAdmin') {
            return response()->json([
                'message' => 'Cannot modify SuperAdmin role'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $role->update([
                'name' => $request->name,
            ]);

            if ($request->has('permissions')) {
                $role->syncPermissions($request->permissions);
            }

            $this->permissionRegistrar->forgetCachedPermissions();

            DB::commit();

            $role->load(['permissions:id,name']);
            $role->loadCount('users');
            $role->permissions = $role->permissions->pluck('name')->toArray();

            return response()->json($role);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update role',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/roles/{id}",
     *     tags={"Admin - Roles"},
     *     summary="Delete a role",
     *     description="Delete a role. Cannot delete SuperAdmin or roles with assigned users. SuperAdmin only.",
     *     security={{"bearer_token":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Role deleted successfully"),
     *     @OA\Response(response=422, description="Cannot delete - role has users or is SuperAdmin"),
     *     @OA\Response(response=403, description="Forbidden - SuperAdmin only")
     * )
     */
    public function destroy(Role $role): JsonResponse
    {
        // Prevent deleting SuperAdmin role
        if ($role->name === 'SuperAdmin') {
            return response()->json([
                'message' => 'Cannot delete SuperAdmin role'
            ], 422);
        }

        // Check if role has users assigned
        $usersCount = DB::table('model_has_roles')
            ->where('role_id', $role->id)
            ->count();

        if ($usersCount > 0) {
            return response()->json([
                'message' => "Cannot delete role. It is assigned to {$usersCount} user(s)"
            ], 422);
        }

        try {
            DB::beginTransaction();

            $role->delete();
            $this->permissionRegistrar->forgetCachedPermissions();

            DB::commit();

            return response()->json([
                'message' => 'Role deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to delete role',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/admin/roles/{id}/users",
     *     tags={"Admin - Roles"},
     *     summary="Get users with a specific role",
     *     description="List all users assigned to a role. SuperAdmin only.",
     *     security={{"bearer_token":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Users retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="role", type="string", example="Manager"),
     *             @OA\Property(property="users_count", type="integer", example=4),
     *             @OA\Property(property="users", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="email", type="string")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Role not found"),
     *     @OA\Response(response=403, description="Forbidden - SuperAdmin only")
     * )
     */
    public function users(Role $role): JsonResponse
    {
        $users = $role->users()->select('id', 'name', 'email', 'restaurant_id')->get();

        return response()->json([
            'role' => $role->name,
            'users_count' => $users->count(),
            'users' => $users,
        ]);
    }
}
