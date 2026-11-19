# WordPress Webshop API - Endpoint Reference

## Base URL
```
/wp-json/api
```

---

## Authentication Endpoints

### Register User
```
POST /api/register
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password123",
  "first_name": "John",
  "last_name": "Doe",
  "tlf": "+45123456789"
}

Response (201):
{
  "user": {
    "userID": 1,
    "first_name": "John",
    "last_name": "Doe",
    "email": "user@example.com",
    "tlf": "+45123456789",
    "is_employee": false
  }
}
```

### Login User
```
POST /api/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password123"
}

Response (200):
{
  "user": {
    "userID": 1,
    "first_name": "John",
    "last_name": "Doe",
    "email": "user@example.com",
    "tlf": "+45123456789",
    "is_employee": false
  },
  "token": "abc123def456..."
}
```

### Logout User
```
POST /api/logout
Authorization: Bearer {token}

Response (200):
{
  "message": "Logged out successfully"
}
```

---

## Product Endpoints

### List All Products
```
GET /api/products

Response (200):
{
  "products": [
    {
      "product_id": 1,
      "name": "Product Name",
      "rating": 4.5,
      "price": 99.99,
      "description": "Product description",
      "mærke": "Brand Name",
      "attributeValues": [
        {
          "attributes_id": 1,
          "attributes_name": "Color",
          "value": "Red",
          "min_value": null,
          "max_value": null
        }
      ]
    }
  ]
}
```

### Get Single Product
```
GET /api/products/{product_id}

Response (200):
{
  "product": {
    "product_id": 1,
    "name": "Product Name",
    "rating": 4.5,
    "price": 99.99,
    "description": "Product description",
    "mærke": "Brand Name",
    "attributeValues": [...]
  }
}
```

### Create Product (Admin)
```
POST /api/products
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "New Product",
  "description": "Product description",
  "price": 99.99,
  "rating": 4.5,
  "mærke": "Brand Name",
  "attribute_values": [
    {
      "attributes_id": 1,
      "value": "Red",
      "min_value": null,
      "max_value": null
    }
  ]
}

Response (201):
{
  "product": {
    "product_id": 2,
    "name": "New Product",
    "rating": 4.5,
    "price": 99.99,
    "description": "Product description",
    "mærke": "Brand Name"
  }
}
```

### Update Product (Admin)
```
PUT /api/products/{product_id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Updated Name",
  "description": "Updated description",
  "price": 109.99,
  "rating": 4.8,
  "mærke": "New Brand"
}

Response (200):
{
  "product": {...}
}
```

### Delete Product (Admin)
```
DELETE /api/products/{product_id}
Authorization: Bearer {token}

Response (200):
{
  "message": "Product deleted successfully"
}
```

---

## Attributes Endpoints

### List All Attributes
```
GET /api/attributes-values

Response (200):
{
  "attributesValues": [
    {
      "attributes_id": 1,
      "attributes_name": "Color",
      "description": "Product color",
      "data_type": "string"
    }
  ]
}
```

### Get Single Attribute
```
GET /api/attributes-values/{attribute_id}

Response (200):
{
  "attributesValue": {
    "attributes_id": 1,
    "attributes_name": "Color",
    "description": "Product color",
    "data_type": "string"
  }
}
```

### Create Attribute (Admin)
```
POST /api/attributes-values
Authorization: Bearer {token}
Content-Type: application/json

{
  "attributes_name": "Size",
  "description": "Product size",
  "data_type": "string"
}

Response (201):
{
  "attributesValue": {
    "attributes_id": 2,
    "attributes_name": "Size",
    "description": "Product size",
    "data_type": "string"
  }
}
```

### Update Attribute (Admin)
```
PUT /api/attributes-values/{attribute_id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "attributes_name": "Size Updated",
  "description": "Updated description",
  "data_type": "string"
}

Response (200):
{
  "attributesValue": {...}
}
```

### Delete Attribute (Admin)
```
DELETE /api/attributes-values/{attribute_id}
Authorization: Bearer {token}

Response (200):
{
  "message": "Attribute value deleted successfully"
}
```

---

## Order Endpoints

### Get User Orders
```
GET /api/orders/{user_id}
Authorization: Bearer {token}

Response (200):
{
  "orderItems": [
    {
      "order_id": 1,
      "uid": 1,
      "order_date": "2026-11-19 10:30:00",
      "total_amount": 199.98,
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

### Create Order
```
POST /api/orders/{user_id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "product_id": 1,
  "quantity": 2
}

Response (201):
{
  "order": {
    "order_id": 1,
    "uid": 1,
    "product_id": 1,
    "quantity": 2
  }
}
```

### Add/Update Item in Order
```
POST /api/orders/{order_id}/items
Authorization: Bearer {token}
Content-Type: application/json

{
  "product_id": 2,
  "quantity": 3
}

Response (201 for new item, 200 for update):
{
  "orderItem": {
    "product_id": 2,
    "amount": 3
  }
}
```

**Note:** Send `quantity: 0` to remove an item from order.

---

## Error Responses

### 400 Bad Request
```json
{
  "message": "Name, price, and description are required."
}
```

### 401 Unauthorized
```json
{
  "message": "Invalid credentials"
}
```

### 403 Forbidden
```json
{
  "message": "Unauthorized access"
}
```

### 404 Not Found
```json
{
  "message": "Product not found"
}
```

### 409 Conflict
```json
{
  "message": "Email already exists."
}
```

---

## Status Codes Summary

| Code | Meaning |
|------|---------|
| 200 | Success |
| 201 | Created |
| 400 | Bad Request |
| 401 | Unauthorized |
| 403 | Forbidden |
| 404 | Not Found |
| 409 | Conflict |

---

## Notes

- All endpoints use `/wp-json/api/` as the base path
- All POST/PUT endpoints require `Content-Type: application/json`
- Authenticated endpoints require a valid token from `/api/login`
- User can only access their own orders (uid must match current user)
- Admin-only endpoints currently check for `is_user_logged_in()` - implement proper role checking for production
- Order items are managed via the `/api/orders/{order_id}/items` endpoint
- Quantity in orders is called `amount` in the response
