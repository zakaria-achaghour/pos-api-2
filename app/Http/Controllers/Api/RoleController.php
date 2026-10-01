<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;

/**
 * @OA\Tag(
 *     name="Roles",
 *     description="Role operations for staff management"
 * )
 */
class RoleController extends Controller
{
    /**
     * Get assignable roles for staff creation
     * 
     * Returns roles that can be assigned to staff members (excludes Owner and SuperAdmin)
     *
     * @OA\Get(
     *     path="/api/roles/assignable",
     *     summary="Get assignable roles",
     *     description="Get list of roles that can be assigned to staff (excludes Owner and SuperAdmin)",
     *     operationId="getAssignableRoles",
     *     tags={"Roles"},
     *     security={{"bearer_token":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of assignable roles",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=3),
     *                 @OA\Property(property="name", type="string", example="Manager"),
     *                 @OA\Property(property="guard_name", type="string", example="api")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function assignable(): JsonResponse
    {
        $roles = Role::where('guard_name', 'api')
            ->whereNotIn('name', ['Owner', 'SuperAdmin'])
            ->orderBy('name')
            ->get(['id', 'name', 'guard_name']);

        return response()->json($roles);
    }

    /**
     * Get all roles for filtering
     * 
     * Returns all roles including Owner and SuperAdmin for filtering purposes
     *
     * @OA\Get(
     *     path="/api/roles",
     *     summary="Get all roles",
     *     description="Get list of all roles for filtering and display purposes",
     *     operationId="getAllRoles",
     *     tags={"Roles"},
     *     security={{"bearer_token":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of all roles",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="SuperAdmin"),
     *                 @OA\Property(property="guard_name", type="string", example="api")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $roles = Role::where('guard_name', 'api')
            ->orderBy('name')
            ->get(['id', 'name', 'guard_name']);

        return response()->json($roles);
    }
}
