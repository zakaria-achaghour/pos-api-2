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
 * @OA\Tag(
 *     name="Menu Categories",
 *     description="Menu category management (CRUD operations)"
 * )
 * 
 * @OA\Tag(
 *     name="Menu Items",
 *     description="Menu item management (CRUD operations)"
 * )
 * 
 * @OA\Tag(
 *     name="Kitchen Management",
 *     description="Kitchen ticket management and operations"
 * )
 * 
 * @OA\Tag(
 *     name="Attendance",
 *     description="Staff attendance tracking and reporting"
 * )
 * 
 * @OA\Tag(
 *     name="Schedules",
 *     description="Staff scheduling and shift management"
 * )
 * 
 * @OA\Tag(
 *     name="Reports",
 *     description="Business reporting and analytics"
 * )
 * 
 * @OA\Tag(
 *     name="Analytics",
 *     description="Dashboard metrics and analytics"
 * )
 * 
 * @OA\Tag(
 *     name="Table Analytics",
 *     description="Table-specific analytics and metrics"
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
 * 
 * @OA\Schema(
 *     schema="MenuCategory",
 *     type="object",
 *     required={"name"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Appetizers"),
 *     @OA\Property(property="description", type="string", example="Light meals to start"),
 *     @OA\Property(property="display_order", type="integer", example=1),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="restaurant_id", type="integer", example=1),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *     schema="MenuItem",
 *     type="object",
 *     required={"name", "price", "category_id"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Caesar Salad"),
 *     @OA\Property(property="description", type="string", example="Fresh romaine lettuce with caesar dressing"),
 *     @OA\Property(property="price", type="number", format="float", example=12.99),
 *     @OA\Property(property="category_id", type="integer", example=1),
 *     @OA\Property(property="image_url", type="string", example="https://example.com/image.jpg"),
 *     @OA\Property(property="is_available", type="boolean", example=true),
 *     @OA\Property(property="preparation_time", type="integer", example=15),
 *     @OA\Property(property="calories", type="integer", example=250),
 *     @OA\Property(property="allergens", type="array", @OA\Items(type="string"), example={"gluten", "dairy"}),
 *     @OA\Property(property="restaurant_id", type="integer", example=1),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="category", ref="#/components/schemas/MenuCategory")
 * )
 * 
 * @OA\Schema(
 *     schema="KitchenTicket",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="order_id", type="integer", example=123),
 *     @OA\Property(property="ticket_number", type="string", example="KT-001"),
 *     @OA\Property(property="status", type="string", enum={"pending", "preparing", "ready"}, example="pending"),
 *     @OA\Property(property="priority", type="string", enum={"normal", "rush", "urgent"}, example="normal"),
 *     @OA\Property(property="assigned_chef_id", type="integer", example=5),
 *     @OA\Property(property="cooking_station", type="string", example="Grill Station"),
 *     @OA\Property(property="preparation_time", type="integer", example=25),
 *     @OA\Property(property="estimated_completion", type="string", format="date-time"),
 *     @OA\Property(property="special_instructions", type="string", example="No onions"),
 *     @OA\Property(property="restaurant_id", type="integer", example=1),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="order", ref="#/components/schemas/Order"),
 *     @OA\Property(property="assignedChef", ref="#/components/schemas/Staff")
 * )
 * 
 * @OA\Schema(
 *     schema="Attendance",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="staff_id", type="integer", example=5),
 *     @OA\Property(property="clock_in", type="string", format="date-time"),
 *     @OA\Property(property="clock_out", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="hours_worked", type="number", format="float", example=8.5),
 *     @OA\Property(property="break_minutes", type="integer", example=30),
 *     @OA\Property(property="notes", type="string", example="Worked overtime"),
 *     @OA\Property(property="restaurant_id", type="integer", example=1),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="staff", ref="#/components/schemas/Staff")
 * )
 * 
 * @OA\Schema(
 *     schema="Schedule",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="staff_id", type="integer", example=5),
 *     @OA\Property(property="date", type="string", format="date", example="2024-01-15"),
 *     @OA\Property(property="start_time", type="string", format="time", example="09:00:00"),
 *     @OA\Property(property="end_time", type="string", format="time", example="17:00:00"),
 *     @OA\Property(property="shift_type", type="string", enum={"morning", "afternoon", "evening", "night"}, example="morning"),
 *     @OA\Property(property="status", type="string", enum={"scheduled", "confirmed", "cancelled"}, example="scheduled"),
 *     @OA\Property(property="notes", type="string", example="Training shift"),
 *     @OA\Property(property="restaurant_id", type="integer", example=1),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="staff", ref="#/components/schemas/Staff")
 * )
 * 
 * @OA\Schema(
 *     schema="Analytics",
 *     type="object",
 *     @OA\Property(property="total_sales", type="number", format="float", example=15420.50),
 *     @OA\Property(property="total_orders", type="integer", example=342),
 *     @OA\Property(property="average_order_value", type="number", format="float", example=45.12),
 *     @OA\Property(property="peak_hours", type="array", @OA\Items(type="integer"), example={12, 13, 19, 20}),
 *     @OA\Property(property="top_items", type="array", @OA\Items(ref="#/components/schemas/MenuItem")),
 *     @OA\Property(property="staff_performance", type="array", @OA\Items(type="object"))
 * )
 */
abstract class Controller
{
    use \Illuminate\Foundation\Auth\Access\AuthorizesRequests;
}