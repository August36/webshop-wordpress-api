# API Alignment Changes - WordPress to Laravel API Structure

This document summarizes all changes made to align the WordPress API with the Laravel API structure.

## Overview

The WordPress Webshop API has been updated to match the structure, naming conventions, and response formats of the SnudensParadisLaravelAPI.

---

## 1. API Routes Changes

### Previous Routes (myshop/v1):
- `GET /myshop/v1/products`
- `GET /myshop/v1/products/{id}`
- `POST /myshop/v1/users/register`
- `GET /myshop/v1/users/me`
- `POST /myshop/v1/orders`
- `GET /myshop/v1/orders`
- `GET /myshop/v1/orders/{id}`

### New Routes (api):
- `GET /api/products` - List all products
- `GET /api/products/{id}` - Get single product
- `POST /api/products` - Create product (admin only)
- `PUT /api/products/{id}` - Update product (admin only)
- `DELETE /api/products/{id}` - Delete product (admin only)
- `POST /api/register` - Register new user
- `POST /api/login` - Login user
- `POST /api/logout` - Logout user (authenticated)
- `GET /api/orders/{uid}` - Get user's orders
- `POST /api/orders/{uid}` - Create new order
- `POST /api/orders/{order_id}/items` - Add/update/remove items in order
- `GET /api/attributes-values` - List all attributes
- `GET /api/attributes-values/{id}` - Get single attribute
- `POST /api/attributes-values` - Create attribute (admin only)
- `PUT /api/attributes-values/{id}` - Update attribute (admin only)
- `DELETE /api/attributes-values/{id}` - Delete attribute (admin only)

---

## 2. Response Format Changes

### Products

**Old Format:**
```json
{
  "ProductID": 1,
  "Name": "Product Name",
  "Rating": 4.5,
  "Price": 99.99,
  "Description": "...",
  "Brand": "...",
  "Attributes": [...]
}
```

**New Format:**
```json
{
  "products": [
    {
      "product_id": 1,
      "name": "Product Name",
      "rating": 4.5,
      "price": 99.99,
      "description": "...",
      "mærke": "...",
      "attributeValues": [...]
    }
  ]
}
```

**Single Product:**
```json
{
  "product": {
    "product_id": 1,
    "name": "Product Name",
    ...
  }
}
```

### Users

**Old Register Response:**
```json
{
  "UID": 1,
  "Username": "...",
  "Email": "...",
  "Firstname": "...",
  "Lastname": "...",
  "Phone": "...",
  "IsEmployee": false
}
```

**New Register Response:**
```json
{
  "user": {
    "userID": 1,
    "first_name": "...",
    "last_name": "...",
    "email": "...",
    "tlf": "...",
    "is_employee": false
  }
}
```

**Login Response:**
```json
{
  "user": {...},
  "token": "..."
}
```

### Orders

**Old Format:**
```json
{
  "OrderID": 1,
  "UserID": 1,
  "TotalAmount": 299.97,
  "Status": "Pending",
  "Items": [...]
}
```

**New Format:**
```json
{
  "orderItems": [
    {
      "order_id": 1,
      "uid": 1,
      "order_date": "2026-11-19 10:00:00",
      "total_amount": 299.97,
      "status": "pending",
      "orderItems": [
        {
          "product_id": 1,
          "amount": 2
        }
      ]
    }
  ]
}
```

---

## 3. Field Naming Changes

### Database Field Names (via API responses):
- `ProductID` → `product_id`
- `Name` → `name`
- `Rating` → `rating`
- `Price` → `price`
- `Description` → `description`
- `Brand` → `mærke` (Danish for "brand")
- `Attributes` → `attributeValues`
- `AttributesID` → `attributes_id`
- `AttributesName` → `attributes_name`
- `Value` → `value`
- `MinValue` → `min_value`
- `MaxValue` → `max_value`
- `UID` → `userID` (in user object) / `uid` (in order context)
- `Username` → Removed, using email instead
- `Firstname` → `first_name`
- `Lastname` → `last_name`
- `Phone` → `tlf` (Danish abbreviation)
- `IsEmployee` → `is_employee`
- `OrderID` → `order_id`
- `UserID` → `uid`
- `TotalAmount` → `total_amount`
- `Status` → `status`
- `Items` → `orderItems`
- `OrderDate` → `order_date`

---

## 4. Authentication Changes

### Previous:
- Login endpoint: `/myshop/v1/users/register`
- No explicit login endpoint
- User session-based via WordPress

### New:
- Register endpoint: `/api/register`
- Login endpoint: `/api/login`
- Logout endpoint: `/api/logout`
- JWT bearer tokens backed by revocable WordPress sessions
- Maintains backward compatibility with WordPress auth

---

## 5. Error Response Standardization

### All Errors Now Use Standard Format:
```json
{
  "message": "Error description"
}
```

**Common Status Codes:**
- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `409` - Conflict (e.g., email already exists)

---

## 6. Files Modified

1. **api/users.php** - Updated authentication endpoints and response format
2. **api/products.php** - Updated product endpoints with new field names and response wrapping
3. **api/orders.php** - Restructured order endpoints to use UID and new response format
4. **api/attributes.php** - NEW FILE - Created attributes values API endpoints
5. **webshop-api.php** - Added require for new attributes.php file

---

## 7. Database Schema (Conceptual - WordPress still uses Post Meta)

While WordPress doesn't require schema migrations, the API now represents data in alignment with Laravel's structure:

### Products Table Equivalent
- product_id (post ID)
- name (post_title)
- description (post_content)
- price (post_meta)
- rating (post_meta)
- mærke/brand (post_meta)

### Orders Table Equivalent
- order_id (post ID)
- uid (post_meta: user_id)
- order_date (post_meta)
- total_amount (post_meta)
- status (post_meta: order_status)
- orderItems (post_meta: order_items array)

### Order Items (via Order Meta Array)
- product_id
- amount

### Attributes Table Equivalent
- attributes_id (post ID)
- attributes_name (post_title)
- description (post_content)
- data_type (post_meta)

---

## 8. Order Item Management

### New Create Order Endpoint:
```
POST /api/orders/{uid}
{
  "product_id": 1,
  "quantity": 2
}
```

### New Add/Update Item Endpoint:
```
POST /api/orders/{order_id}/items
{
  "product_id": 1,
  "quantity": 3
}
```

**Features:**
- Automatically calculates total_amount based on product prices
- Supports quantity 0 to remove items
- Returns 201 on item creation, 200 on update/delete

---

## 9. Breaking Changes

The following changes will break existing clients:

1. **Route Namespace:** `/myshop/v1/` → `/api/`
2. **Field Names:** All field names now use snake_case instead of PascalCase
3. **Response Wrapping:** All responses now wrapped in singular/plural keys (e.g., `{"product": {...}}`)
4. **Authentication:** Login now at `/api/login` instead of built-in WordPress endpoints
5. **User Registration:** No longer uses `username`, uses `email` as identifier
6. **Order Routes:** `/orders` → `/orders/{uid}` for GET; `/orders/{order_id}/items` for adding items
7. **Status Values:** "Pending" → "pending" (lowercase)

---

## 10. Migration Guide for Clients

To migrate existing clients to the new API:

1. Update all route URLs from `/myshop/v1/` to `/api/`
2. Update field names to snake_case
3. Unwrap responses from their singular/plural keys
4. Update login flow to use new `/api/login` endpoint with email
5. Update order creation to use `/api/orders/{uid}` with new payload format
6. Update error handling to look for `message` field in error responses

---

## 11. Compatibility Notes

- WordPress authentication and user management still function normally
- Post meta continues to store product and order data
- Existing admin interfaces remain unchanged
- The JWT authentication plugin and `JWT_AUTH_SECRET_KEY` are required for API login.
- The old `/jwt-auth/v1/token` login is disabled; existing JWTs must be replaced via `/api/login`.

---

## 12. Future Improvements

Recommended enhancements for full Laravel API parity:

1. Review token expiration settings
2. Add API rate limiting
3. Add more detailed error codes
4. Add pagination support
5. Add filtering and sorting capabilities
6. Add proper database migrations/versioning documentation
7. Add API versioning strategy (e.g., `/api/v1/`)
8. Add comprehensive API documentation (OpenAPI/Swagger)
