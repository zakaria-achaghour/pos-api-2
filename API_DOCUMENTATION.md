# API Documentation

## Swagger/OpenAPI Documentation

The complete API documentation for this POS system is available via Swagger UI at:

**🔗 [http://localhost:8080/api/documentation](http://localhost:8080/api/documentation)**

## What's Included

The API documentation includes:

- **Authentication endpoints** - Register, login, logout, token refresh
- **Restaurant management** - SuperAdmin operations for managing restaurants
- **Staff management** - CRUD operations for staff members
- **Table management** - Restaurant table operations
- **Order management** - Order processing and item management
- **Request/Response schemas** - Complete data models
- **Authentication examples** - JWT Bearer token usage
- **Interactive testing** - Try API endpoints directly from the documentation

## How to Use

1. **Access the documentation**: Open [http://localhost:8080/api/documentation](http://localhost:8080/api/documentation)
2. **Authenticate**: Use the `/api/auth/login` endpoint to get a JWT token
3. **Authorize**: Click the "Authorize" button and add `Bearer YOUR_TOKEN`
4. **Test endpoints**: Use the "Try it out" feature to test API calls

## Authentication

Most endpoints require JWT authentication. Include the token in the Authorization header:

```
Authorization: Bearer YOUR_JWT_TOKEN_HERE
```

## API Base URL

```
http://localhost:8080/api
```

## Available Endpoints

- `POST /api/auth/register` - Register new user
- `POST /api/auth/login` - Login and get JWT token
- `POST /api/auth/logout` - Logout
- `GET /api/staff` - Get staff list
- `GET /api/tables` - Get restaurant tables
- `GET /api/orders` - Get orders
- And many more...

For complete endpoint documentation, visit the Swagger UI link above.