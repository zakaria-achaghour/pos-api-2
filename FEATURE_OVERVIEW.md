# 🎯 POS API Documentation - Complete Feature Overview

## ✅ Interactive API Explorer

Your Swagger UI provides a fully interactive experience where you can:

### 🔴 **"Try It Out" Functionality**
- **Real API Testing**: Click any endpoint → "Try it out" → Fill parameters → "Execute"
- **Live Responses**: See actual API responses with real data
- **Parameter Testing**: Test different filters, pagination, and search parameters
- **Request Body Builder**: Interactive forms for POST/PUT requests

### 🔵 **Visual Interface Features**
- **Collapsible Sections**: Organize endpoints by logical groups
- **Search Functionality**: Quickly find specific endpoints
- **Model Explorer**: Click on schema references to see detailed structures
- **Response Code Tabs**: Switch between different response scenarios

## 🔐 Authentication Support - JWT Bearer Token Integration

### **Complete Authentication Flow**
```json
// 1. Login Request
POST /api/login
{
  "email": "admin@goldenfork.com",
  "password": "password123"
}

// 2. Response with JWT Token
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "token_type": "bearer",
  "expires_in": 3600,
  "user": { "id": 1, "name": "Admin User", ... }
}

// 3. Use Token in Swagger UI
// Click "Authorize" button and enter: Bearer YOUR_TOKEN_HERE
```

### **Security Integration Features**
- **🔒 Authorize Button**: One-click authentication for all endpoints
- **🔑 Bearer Token Support**: Automatic token inclusion in requests
- **🚫 Protected Endpoints**: Clear indication of which endpoints require authentication
- **⏰ Token Expiry Handling**: Refresh token functionality documented

## 📊 Complete Request/Response Examples

### **Request Examples for Every Endpoint**

#### **Staff Management Examples**
```json
// Create Staff Request
POST /api/staff
{
  "first_name": "John",
  "last_name": "Doe", 
  "email": "john.doe@restaurant.com",
  "phone": "+1234567890",
  "position": "Waiter",
  "hourly_rate": 15.50,
  "status": "active"
}

// Staff Filter Examples
GET /api/staff?status=active&position=Waiter&search=John&page=1&per_page=10
```

#### **Order Management Examples**
```json
// Create Order Request
POST /api/orders
{
  "table_id": 5,
  "waiter_id": 2,
  "notes": "Customer allergic to nuts",
  "special_requests": "Extra sauce on the side"
}

// Add Order Item Request
POST /api/orders/123/items
{
  "menu_item_id": 15,
  "quantity": 2,
  "special_instructions": "Medium rare",
  "price": 25.99
}
```

### **Response Examples with All Status Codes**

#### **Success Responses (200/201)**
```json
// Staff Creation Success (201)
{
  "id": 15,
  "first_name": "John",
  "last_name": "Doe",
  "email": "john.doe@restaurant.com",
  "position": "Waiter",
  "status": "active",
  "created_at": "2024-01-15T10:30:00Z",
  "updated_at": "2024-01-15T10:30:00Z"
}

// Paginated Staff List (200)
{
  "current_page": 1,
  "data": [
    { "id": 1, "first_name": "Alice", ... },
    { "id": 2, "first_name": "Bob", ... }
  ],
  "last_page": 3,
  "per_page": 15,
  "total": 42,
  "next_page_url": "http://localhost:8080/api/staff?page=2"
}
```

#### **Error Responses (400/401/404/422)**
```json
// Validation Error (422)
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."],
    "hourly_rate": ["The hourly rate must be a number."]
  }
}

// Authentication Error (401)
{
  "message": "Unauthenticated."
}

// Not Found Error (404)
{
  "message": "Staff member not found.",
  "error": "RESOURCE_NOT_FOUND"
}
```

## 🏗️ Data Model Schemas - Exact Data Structures

### **Complete Schema Definitions**

#### **User Schema**
```json
{
  "type": "object",
  "properties": {
    "id": { "type": "integer", "example": 1 },
    "name": { "type": "string", "example": "John Doe" },
    "email": { "type": "string", "format": "email", "example": "john@example.com" },
    "email_verified_at": { "type": "string", "format": "date-time", "nullable": true },
    "restaurant_id": { "type": "integer", "example": 1 },
    "created_at": { "type": "string", "format": "date-time" },
    "updated_at": { "type": "string", "format": "date-time" }
  }
}
```

#### **Staff Schema**
```json
{
  "type": "object",
  "required": ["first_name", "last_name", "email", "position"],
  "properties": {
    "id": { "type": "integer", "example": 1 },
    "first_name": { "type": "string", "example": "John" },
    "last_name": { "type": "string", "example": "Doe" },
    "email": { "type": "string", "format": "email" },
    "phone": { "type": "string", "example": "+1234567890" },
    "position": { "type": "string", "enum": ["Manager", "Waiter", "Chef", "Cashier"] },
    "hourly_rate": { "type": "number", "format": "float", "example": 15.50 },
    "status": { "type": "string", "enum": ["active", "inactive", "terminated"] },
    "hire_date": { "type": "string", "format": "date" },
    "restaurant_id": { "type": "integer" }
  }
}
```

#### **Order Schema with Relationships**
```json
{
  "type": "object",
  "properties": {
    "id": { "type": "integer", "example": 123 },
    "order_number": { "type": "string", "example": "ORD-2024-001" },
    "table_id": { "type": "integer", "example": 5 },
    "waiter_id": { "type": "integer", "example": 2 },
    "status": { 
      "type": "string", 
      "enum": ["pending", "confirmed", "preparing", "ready", "served", "paid", "cancelled"] 
    },
    "subtotal": { "type": "number", "format": "float", "example": 45.99 },
    "tax_amount": { "type": "number", "format": "float", "example": 3.68 },
    "total_amount": { "type": "number", "format": "float", "example": 49.67 },
    "notes": { "type": "string", "nullable": true },
    "items": {
      "type": "array",
      "items": { "$ref": "#/components/schemas/OrderItem" }
    },
    "table": { "$ref": "#/components/schemas/Table" },
    "waiter": { "$ref": "#/components/schemas/Staff" }
  }
}
```

## ❌ Error Response Documentation

### **Comprehensive Error Handling**

#### **HTTP Status Code Reference**
- **400 Bad Request**: Invalid request format
- **401 Unauthorized**: Missing or invalid authentication token
- **403 Forbidden**: Insufficient permissions for action
- **404 Not Found**: Requested resource doesn't exist
- **422 Unprocessable Entity**: Validation errors
- **429 Too Many Requests**: Rate limit exceeded
- **500 Internal Server Error**: Server-side error

#### **Error Response Structure**
```json
{
  "message": "Human-readable error description",
  "error": "ERROR_CODE_CONSTANT", 
  "errors": {
    "field_name": ["Specific field validation errors"]
  },
  "debug": {
    "trace_id": "uuid-for-debugging",
    "timestamp": "2024-01-15T10:30:00Z"
  }
}
```

#### **Authentication Error Examples**
```json
// Missing Token (401)
{
  "message": "Unauthenticated.",
  "error": "AUTHENTICATION_REQUIRED"
}

// Invalid Token (401) 
{
  "message": "Token has expired.",
  "error": "TOKEN_EXPIRED"
}

// Insufficient Permissions (403)
{
  "message": "This action is unauthorized.",
  "error": "INSUFFICIENT_PERMISSIONS"
}
```

## 🏷️ Organized by Logical Tags for Easy Navigation

### **Tag Structure & Organization**

#### **🔑 Authentication**
- User registration and login
- Token management and refresh
- Password reset functionality
- User profile management

#### **🏢 Admin - Restaurants (SuperAdmin)**
- Multi-tenant restaurant management
- Restaurant creation and configuration
- SuperAdmin exclusive operations

#### **👥 Staff Management**
- Employee CRUD operations
- Position and role management
- Scheduling and attendance
- Performance tracking

#### **🪑 Tables**
- Table configuration and layout
- Capacity and status management
- Table assignment and reservations

#### **📋 Orders**
- Order lifecycle management
- Item addition and modification
- Payment processing integration
- Kitchen ticket generation

#### **📊 Reports & Analytics**
- Sales reporting
- Staff performance metrics
- Table utilization analytics
- Revenue tracking

### **Navigation Features**
- **🔍 Search**: Find endpoints instantly
- **📁 Collapsible Sections**: Organize by feature area
- **🏷️ Color-Coded Tags**: Visual organization
- **📖 Alphabetical Sorting**: Easy browsing
- **🔗 Cross-References**: Schema linking between endpoints

## 🚀 Ready to Test!

### **Quick Start Testing Workflow**
1. **Open Documentation**: http://localhost:8080/api/documentation
2. **Authenticate**: POST /api/login → Copy token → Click "Authorize"
3. **Explore Endpoints**: Try any endpoint with "Try it out"
4. **Test Data Models**: Create/update resources to see schemas in action
5. **Handle Errors**: Test invalid data to see error responses

### **Advanced Testing Features**
- **Real-time Response Inspection**: See actual API behavior
- **Parameter Validation**: Test edge cases and validation rules
- **Authentication Testing**: Verify token handling and expiry
- **Performance Monitoring**: Check response times and data sizes
- **Error Scenario Testing**: Validate error handling and messages

## 🎉 All Features Complete!

✅ **Interactive API Explorer** - Full "Try it out" functionality  
✅ **Authentication Support** - JWT Bearer token integration  
✅ **Complete Examples** - Request/response for every endpoint  
✅ **Data Model Schemas** - Exact structure definitions  
✅ **Error Documentation** - Comprehensive error handling  
✅ **Logical Organization** - Tag-based navigation system  

**Your POS API documentation is production-ready and developer-friendly!** 🚀