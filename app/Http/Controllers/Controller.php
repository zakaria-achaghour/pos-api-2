<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="POS System API Documentation",
 *     version="1.0.0",
 *     description="Comprehensive Point of Sale System API for multi-tenant restaurant management with authentication, orders, staff, and more."
 * )
 * 
 * @OA\Server(
 *     url="http://localhost:8080",
 *     description="Local Development Server"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="bearer_token",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="JWT Bearer token authentication. Add 'Bearer ' before your token."
 * )
 * 
 * @OA\Tag(
 *     name="Authentication",
 *     description="User authentication endpoints (login, register, logout)"
 * )
 * 
 * @OA\Tag(
 *     name="Admin - Restaurants",
 *     description="Restaurant management for SuperAdmin users"
 * )
 * 
 * @OA\Tag(
 *     name="Staff Management",
 *     description="Staff CRUD operations and management"
 * )
 * 
 * @OA\Tag(
 *     name="Tables",
 *     description="Restaurant table management"
 * )
 * 
 * @OA\Tag(
 *     name="Orders",
 *     description="Order processing and management"
 * )
 * 
 * @OA\Schema(
 *     schema="PaginatedResponse",
 *     type="object",
 *     @OA\Property(property="current_page", type="integer", example=1),
 *     @OA\Property(property="data", type="array", @OA\Items(type="object")),
 *     @OA\Property(property="last_page", type="integer", example=5),
 *     @OA\Property(property="per_page", type="integer", example=15),
 *     @OA\Property(property="total", type="integer", example=100)
 * )
 * 
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     type="object",
 *     @OA\Property(property="message", type="string", example="Error message"),
 *     @OA\Property(property="errors", type="object", nullable=true)
 * )
 * 
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="John Doe"),
 *     @OA\Property(property="email", type="string", example="john@example.com"),
 *     @OA\Property(property="restaurant_id", type="integer", example=1)
 * )
 * 
 * @OA\Schema(
 *     schema="Restaurant",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Golden Fork"),
 *     @OA\Property(property="address", type="string", example="123 Main St"),
 *     @OA\Property(property="phone", type="string", example="+1234567890")
 * )
 * 
 * @OA\Schema(
 *     schema="Staff",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="John Doe"),
 *     @OA\Property(property="position", type="string", example="Waiter"),
 *     @OA\Property(property="status", type="string", example="active")
 * )
 * 
 * @OA\Schema(
 *     schema="Table",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="number", type="string", example="T01"),
 *     @OA\Property(property="capacity", type="integer", example=4),
 *     @OA\Property(property="status", type="string", example="available")
 * )
 * 
 * @OA\Schema(
 *     schema="Order",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="table_id", type="integer", example=1),
 *     @OA\Property(property="status", type="string", example="open"),
 *     @OA\Property(property="total", type="number", example=25.50)
 * )
 * 
 * @OA\Schema(
 *     schema="OrderItem",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="quantity", type="integer", example=2),
 *     @OA\Property(property="unit_price", type="number", example=12.50)
 * )
 * 
 * @OA\Schema(
 *     schema="StoreTableRequest",
 *     type="object",
 *     @OA\Property(property="number", type="string", example="T01"),
 *     @OA\Property(property="capacity", type="integer", example=4)
 * )
 * 
 * @OA\Schema(
 *     schema="UpdateTableRequest",
 *     type="object",
 *     @OA\Property(property="number", type="string", example="T01"),
 *     @OA\Property(property="capacity", type="integer", example=4)
 * )
 * 
 * @OA\Schema(
 *     schema="CreateOrderRequest",
 *     type="object",
 *     @OA\Property(property="table_id", type="integer", example=1),
 *     @OA\Property(property="waiter_id", type="integer", example=1)
 * )
 * 
 * @OA\Schema(
 *     schema="AddOrderItemRequest",
 *     type="object",
 *     @OA\Property(property="menu_item_id", type="integer", example=1),
 *     @OA\Property(property="quantity", type="integer", example=2)
 * )
 */
abstract class Controller
{
    //
}