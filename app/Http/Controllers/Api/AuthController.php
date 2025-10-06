<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(Request $r): JsonResponse
    {
        $data = $r->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'restaurant_id' => 'required|integer|exists:restaurants,id',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email'=> $data['email'],
            'password' => Hash::make($data['password']),
            'restaurant_id' => $data['restaurant_id'],
        ]);

        // Assign default role
        $user->assignRole('Cashier');

        $token = auth('api')->login($user);

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user->only('id','name','email','restaurant_id'),
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60
        ], 201);
    }

    public function login(Request $r): JsonResponse
    {
        $r->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (! $token = auth('api')->attempt($r->only('email','password'))) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        return $this->respondWithToken($token);
    }

    public function me(): JsonResponse
    {
        $user = auth('api')->user();
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'restaurant_id' => $user->restaurant_id,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name')
        ]);
    }

    public function logout(): JsonResponse
    {
        auth('api')->logout();
        return response()->json(['message' => 'Successfully logged out']);
    }

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
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'restaurant_id' => $user->restaurant_id,
                'roles' => $user->getRoleNames()
            ]
        ]);
    }
}
