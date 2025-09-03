<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function register(Request $r)
    {
        $data = $r->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email',
            'password' => 'required|min:6',
            'restaurant_id' => 'required|integer|exists:restaurants,id',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email'=> $data['email'],
            'password' => Hash::make($data['password']),
            'restaurant_id' => $data['restaurant_id'],
        ]);

        return response()->json($user->only('id','name','email','restaurant_id'), 201);
    }

    public function login(Request $r)
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

    // GET /api/me
    public function me()
    {
        return response()->json(auth('api')->user()->only('id','name','email','restaurant_id'));
    }

    // POST /api/logout
    public function logout()
    {
        auth('api')->logout();
        return response()->json(['message' => 'Logged out']);
    }

    // POST /api/refresh
    public function refresh()
    {
        return $this->respondWithToken(auth('api')->refresh());
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => auth('api')->factory()->getTTL() * 60, // seconds
            'user'         => auth('api')->user()->only('id','name','email','restaurant_id'),
        ]);
    }
}
