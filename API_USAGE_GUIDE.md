# 🚀 POS System API Documentation - Usage Guide

## 📖 Overview
Your POS System API documentation is now fully interactive with comprehensive testing capabilities. Access it at: **http://localhost:8080/api/documentation**

## 🔐 Authentication Flow (JWT Bearer Token)

### Step 1: Register or Login
1. **Navigate to "Authentication" section**
2. **Click on "POST /api/register"** to create a new account
3. **Or click "POST /api/login"** to authenticate with existing credentials

### Step 2: Get Your JWT Token
```json
// Example login request:
{
  "email": "admin@goldenfork.com",
  "password": "password123"
}

// Response will include:
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "token_type": "bearer",
  "expires_in": 3600,
  "user": { ... }
}
```

### Step 3: Authorize in Swagger UI
1. **Click the "Authorize" button** (🔒 icon) at the top of the page
2. **Enter:** `Bearer YOUR_TOKEN_HERE` (include "Bearer " prefix)
3. **Click "Authorize"** - now all secured endpoints are accessible

## 🎯 Interactive Testing Features

### 1. **Try It Out** Button
- Click "Try it out" on any endpoint
- Fill in parameters and request body
- Click "Execute" to send real requests
- See live responses with status codes

### 2. **Complete Request/Response Examples**
Each endpoint shows:
- **Request parameters** with types and examples
- **Request body schemas** with required fields
- **Response codes** (200, 400, 401, 404, etc.)
- **Response schemas** with example data

### 3. **Data Model Schemas**
Scroll down to see complete schemas for:
- **User** - Authentication and user data
- **Restaurant** - Restaurant information
- **Staff** - Employee management
- **Table** - Table management
- **Order** - Order processing
- **OrderItem** - Individual order items
- **PaginatedResponse** - List responses
- **ErrorResponse** - Error handling

## 📂 API Organization by Tags

### 🔑 Authentication
- `POST /api/register` - Create new user account
- `POST /api/login` - Authenticate and get JWT token
- `POST /api/logout` - Logout and invalidate token
- `POST /api/refresh` - Refresh JWT token
- `GET /api/me` - Get current user info

### 🏢 Admin - Restaurants (SuperAdmin Only)
- `GET /api/admin/restaurants` - List all restaurants
- `POST /api/admin/restaurants` - Create new restaurant
- `PUT /api/admin/restaurants/{id}` - Update restaurant
- `DELETE /api/admin/restaurants/{id}` - Delete restaurant

### 👥 Staff Management
- `GET /api/staff` - List staff with filtering
- `POST /api/staff` - Create new staff member
- `GET /api/staff/{id}` - Get staff details
- `PUT /api/staff/{id}` - Update staff member
- `DELETE /api/staff/{id}` - Delete staff member

### 🪑 Tables
- `GET /api/tables` - List all tables
- `POST /api/tables` - Create new table
- `GET /api/tables/{id}` - Get table details
- `PUT /api/tables/{id}` - Update table
- `DELETE /api/tables/{id}` - Delete table

### 📋 Orders
- `GET /api/orders` - List orders with filters
- `POST /api/orders` - Create new order
- `GET /api/orders/{id}` - Get order details
- `POST /api/orders/{id}/items` - Add item to order

## 🔄 Testing Workflow Example

### 1. Authentication Test:
```bash
1. Go to Authentication → POST /api/login
2. Click "Try it out"
3. Use test credentials:
   {
     "email": "admin@goldenfork.com", 
     "password": "password123"
   }
4. Copy the access_token from response
5. Click "Authorize" and paste: Bearer YOUR_TOKEN
```

### 2. Staff Management Test:
```bash
1. Go to Staff Management → GET /api/staff
2. Click "Try it out" 
3. Add filter parameters (optional):
   - status: "active"
   - position: "Waiter"
4. Click "Execute"
5. See paginated staff list response
```

### 3. Table Operations Test:
```bash
1. Go to Tables → POST /api/tables
2. Click "Try it out"
3. Fill request body:
   {
     "number": "T10",
     "capacity": 4,
     "status": "available"
   }
4. Click "Execute"
5. See created table response
```

### 4. Order Processing Test:
```bash
1. Go to Orders → POST /api/orders
2. Click "Try it out"
3. Fill request body:
   {
     "table_id": 1,
     "waiter_id": 2,
     "notes": "Customer allergic to nuts"
   }
4. Click "Execute"
5. See created order with ID
```

## 📊 Response Codes Reference

- **200** - Success
- **201** - Created successfully  
- **204** - No content (successful deletion)
- **400** - Bad request (validation errors)
- **401** - Unauthorized (missing/invalid token)
- **403** - Forbidden (insufficient permissions)
- **404** - Not found
- **422** - Unprocessable entity
- **500** - Internal server error

## 🔍 Advanced Features

### Filtering & Pagination
Most list endpoints support:
- **Pagination**: `?page=1&per_page=15`
- **Filtering**: `?status=active&position=Waiter`
- **Search**: `?search=John`
- **Date ranges**: `?date_from=2024-01-01&date_to=2024-01-31`

### Error Response Format
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

### Pagination Response Format
```json
{
  "current_page": 1,
  "data": [...],
  "last_page": 5,
  "per_page": 15,
  "total": 73,
  "next_page_url": "http://localhost:8080/api/staff?page=2"
}
```

## 🎯 Pro Tips

1. **Use the search function** (Ctrl+F) to quickly find specific endpoints
2. **Bookmark the documentation URL** for easy access
3. **Test authentication first** before trying protected endpoints
4. **Check response schemas** to understand data structures
5. **Use the "Models" section** to see all available data schemas
6. **Copy curl commands** from the documentation for external testing

## 🚀 Ready to Go!

Your API documentation is now fully functional with:
- ✅ Interactive testing capabilities
- ✅ JWT authentication integration  
- ✅ Complete request/response examples
- ✅ Comprehensive data schemas
- ✅ Error handling documentation
- ✅ Organized navigation by features

**Start exploring at: http://localhost:8080/api/documentation** 🎉