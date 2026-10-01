<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/register",
     *     tags={"Authentication"},
     *     summary="Register a new user",
     *     description="Create a Cashier account. Requires SuperAdmin, Owner or Manager; non-SuperAdmins can only register users in their own restaurant.",
     *     security={{"bearer_token":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password","restaurant_id"},
     *             @OA\Property(property="name", type="string", maxLength=120, example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="password", type="string", minLength=6, example="password123"),
     *             @OA\Property(property="restaurant_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User registered successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User registered successfully"),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="restaurant_id", type="integer", example=1)
     *             ),
     *             @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."),
     *             @OA\Property(property="token_type", type="string", example="bearer"),
     *             @OA\Property(property="expires_in", type="integer", example=7200)
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Not allowed to register users for this restaurant"),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function register(Request $r): JsonResponse
    {
        $data = $r->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'restaurant_id' => 'required|integer|exists:restaurants,id',
        ]);

        $actor = auth('api')->user();
        if (! $actor->hasRole('SuperAdmin') && (int) $data['restaurant_id'] !== (int) $actor->restaurant_id) {
            return response()->json([
                'message' => 'You can only register users for your own restaurant.'
            ], 403);
        }

        $user = User::create([
            'name' => $data['name'],
            'email'=> $data['email'],
            'password' => Hash::make($data['password']),
            'restaurant_id' => $data['restaurant_id'],
        ]);

        // Assign default role
        $user->assignRole('Cashier');

        // Issue a token for the new account without switching the caller's session
        $token = JWTAuth::fromUser($user);

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $this->transformUserWithBranding($user),
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/login",
     *     tags={"Authentication"},
     *     summary="User login",
     *     description="Authenticate user and return JWT token",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="owner@golden-fork.com"),
     *             @OA\Property(property="password", type="string", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Login successful"),
     *             @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."),
     *             @OA\Property(property="token_type", type="string", example="bearer"),
     *             @OA\Property(property="expires_in", type="integer", example=7200),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Owner of The Golden Fork"),
     *                 @OA\Property(property="email", type="string", example="owner@golden-fork.com"),
     *                 @OA\Property(property="restaurant_id", type="integer", example=1),
     *                 @OA\Property(property="roles", type="array", @OA\Items(type="string"), example={"Owner"})
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid credentials")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function login(Request $r): JsonResponse
    {
        $r->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Check if user is soft-deleted before attempting login
        $user = User::withTrashed()->where('email', $r->email)->first();
        if ($user && $user->trashed()) {
            return response()->json([
                'message' => 'This account has been deleted. Please contact your administrator.'
            ], 403);
        }

        if (! $token = auth('api')->attempt($r->only('email','password'))) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // Check if user is active
        $user = auth('api')->user();
        if (!$user->is_active) {
            auth('api')->logout();
            return response()->json([
                'message' => 'Your account is currently inactive. Please contact your manager.'
            ], 403);
        }

        return $this->respondWithToken($token);
    }

    /**
     * @OA\Get(
     *     path="/api/me",
     *     tags={"Authentication"},
     *     summary="Get current user information",
     *     description="Retrieve authenticated user details including roles and permissions",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User information retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Owner of The Golden Fork"),
     *             @OA\Property(property="email", type="string", example="owner@golden-fork.com"),
     *             @OA\Property(property="restaurant_id", type="integer", example=1),
     *             @OA\Property(property="roles", type="array", @OA\Items(type="string"), example={"Owner"}),
     *             @OA\Property(property="permissions", type="array", @OA\Items(type="string"), example={"manage-menu", "view-menu", "create-orders"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function me(): JsonResponse
    {
        $user = auth('api')->user();
        return response()->json($this->transformUserWithBranding($user, true));
    }

    /**
     * @OA\Post(
     *     path="/api/logout",
     *     tags={"Authentication"},
     *     summary="User logout",
     *     description="Logout user and invalidate JWT token",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successfully logged out",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Successfully logged out")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function logout(): JsonResponse
    {
        auth('api')->logout();
        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * @OA\Post(
     *     path="/api/refresh",
     *     tags={"Authentication"},
     *     summary="Refresh JWT token",
     *     description="Refresh the current JWT token to extend session",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Token refreshed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Login successful"),
     *             @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."),
     *             @OA\Property(property="token_type", type="string", example="bearer"),
     *             @OA\Property(property="expires_in", type="integer", example=7200),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Owner of The Golden Fork"),
     *                 @OA\Property(property="email", type="string", example="owner@golden-fork.com"),
     *                 @OA\Property(property="restaurant_id", type="integer", example=1),
     *                 @OA\Property(property="roles", type="array", @OA\Items(type="string"), example={"Owner"})
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function refresh(): JsonResponse
    {
        return $this->respondWithToken(auth('api')->refresh());
    }

    protected function respondWithToken($token): JsonResponse
    {
        $user = auth('api')->user();
        return response()->json([
            'message' => 'Login successful',
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => auth('api')->factory()->getTTL() * 60,
            'user' => $this->transformUserWithBranding($user)
        ]);
    }
}
