# API Testing Guide

For an automated login, order and logout check, start Docker and run
`py -3 tests\auth-integration.py` from the project directory. It creates and
removes a temporary user, product and order.

## Quick Test Commands

### 1. Test Register User
```bash
curl -X POST http://localhost:8000/wp-json/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123",
    "first_name": "John",
    "last_name": "Doe",
    "tlf": "+45123456789"
  }'

# Expected Response (201):
{
  "user": {
    "userID": 1,
    "first_name": "John",
    "last_name": "Doe",
    "email": "test@example.com",
    "tlf": "+45123456789",
    "is_employee": false
  }
}
```

### 2. Test Login User
```bash
curl -X POST http://localhost:8000/wp-json/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123"
  }'

# Expected Response (200):
{
  "user": {
    "userID": 1,
    "first_name": "John",
    "last_name": "Doe",
    "email": "test@example.com",
    "tlf": "+45123456789",
    "is_employee": false
  },
  "token": "abc123def456..."
}
```

### 3. Test Get Products (Public)
```bash
curl http://localhost:8000/wp-json/api/products

# Expected Response (200):
{
  "products": [
    {
      "product_id": 1,
      "name": "Sample Product",
      "rating": 4.50,
      "price": 99.99,
      "description": "...",
      "mærke": "Brand",
      "attributeValues": []
    }
  ]
}
```

### 4. Test Create Product (Authenticated)
```bash
# Note: Save token from login response
TOKEN="abc123def456..."

curl -X POST http://localhost:8000/wp-json/api/products \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "name": "New Product",
    "description": "Product description",
    "price": 49.99,
    "rating": 4.0,
    "mærke": "New Brand"
  }'

# Expected Response (201):
{
  "product": {
    "product_id": 2,
    "name": "New Product",
    "rating": 4.0,
    "price": 49.99,
    "description": "Product description",
    "mærke": "New Brand"
  }
}
```

### 5. Test Get Single Product
```bash
curl http://localhost:8000/wp-json/api/products/1

# Expected Response (200):
{
  "product": {
    "product_id": 1,
    "name": "Sample Product",
    ...
  }
}
```

### 6. Test Create Attribute (Authenticated)
```bash
TOKEN="abc123def456..."

curl -X POST http://localhost:8000/wp-json/api/attributes-values \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "attributes_name": "Color",
    "description": "Product color",
    "data_type": "string"
  }'

# Expected Response (201):
{
  "attributesValue": {
    "attributes_id": 1,
    "attributes_name": "Color",
    "description": "Product color",
    "data_type": "string"
  }
}
```

### 7. Test Create Order (Authenticated)
```bash
TOKEN="abc123def456..."
USER_ID=1

curl -X POST http://localhost:8000/wp-json/api/orders/$USER_ID \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "product_id": 1,
    "quantity": 2
  }'

# Expected Response (201):
{
  "order": {
    "order_id": 1,
    "uid": 1,
    "product_id": 1,
    "quantity": 2
  }
}
```

### 8. Test Get User Orders (Authenticated)
```bash
TOKEN="abc123def456..."
USER_ID=1

curl -H "Authorization: Bearer $TOKEN" \
  http://localhost:8000/wp-json/api/orders/$USER_ID

# Expected Response (200):
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

### 9. Test Add Item to Order (Authenticated)
```bash
TOKEN="abc123def456..."
ORDER_ID=1

curl -X POST http://localhost:8000/wp-json/api/orders/$ORDER_ID/items \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "product_id": 2,
    "quantity": 1
  }'

# Expected Response (201):
{
  "orderItem": {
    "product_id": 2,
    "amount": 1
  }
}
```

### 10. Test Error Handling (Bad Request)
```bash
curl -X POST http://localhost:8000/wp-json/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "invalid"
  }'

# Expected Response (400):
{
  "message": "Email and password are required."
}
```

### 11. Test Error Handling (Not Found)
```bash
curl http://localhost:8000/wp-json/api/products/9999

# Expected Response (404):
{
  "message": "Product not found"
}
```

### 12. Test Error Handling (Conflict)
```bash
curl -X POST http://localhost:8000/wp-json/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123",
    "first_name": "Jane",
    "last_name": "Doe",
    "tlf": "+45987654321"
  }'

# Expected Response (409):
{
  "message": "Email already exists."
}
```

---

## Postman Collection

```json
{
  "info": {
    "name": "WordPress Webshop API",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "item": [
    {
      "name": "Auth",
      "item": [
        {
          "name": "Register",
          "request": {
            "method": "POST",
            "url": "{{base_url}}/api/register",
            "body": {
              "mode": "raw",
              "raw": "{\"email\": \"test@example.com\", \"password\": \"password123\", \"first_name\": \"John\", \"last_name\": \"Doe\", \"tlf\": \"+45123456789\"}"
            }
          }
        },
        {
          "name": "Login",
          "request": {
            "method": "POST",
            "url": "{{base_url}}/api/login",
            "body": {
              "mode": "raw",
              "raw": "{\"email\": \"test@example.com\", \"password\": \"password123\"}"
            }
          }
        },
        {
          "name": "Logout",
          "request": {
            "method": "POST",
            "url": "{{base_url}}/api/logout",
            "header": [
              {
                "key": "Authorization",
                "value": "Bearer {{token}}"
              }
            ]
          }
        }
      ]
    },
    {
      "name": "Products",
      "item": [
        {
          "name": "List Products",
          "request": {
            "method": "GET",
            "url": "{{base_url}}/api/products"
          }
        },
        {
          "name": "Get Product",
          "request": {
            "method": "GET",
            "url": "{{base_url}}/api/products/1"
          }
        },
        {
          "name": "Create Product",
          "request": {
            "method": "POST",
            "url": "{{base_url}}/api/products",
            "header": [
              {
                "key": "Authorization",
                "value": "Bearer {{token}}"
              }
            ],
            "body": {
              "mode": "raw",
              "raw": "{\"name\": \"New Product\", \"description\": \"...\", \"price\": 49.99, \"rating\": 4.0, \"mærke\": \"Brand\"}"
            }
          }
        }
      ]
    }
  ],
  "variable": [
    {
      "key": "base_url",
      "value": "http://localhost:8000/wp-json"
    },
    {
      "key": "token",
      "value": ""
    }
  ]
}
```

---

## JavaScript/Fetch Examples

### Register
```javascript
fetch('/wp-json/api/register', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    email: 'user@example.com',
    password: 'password123',
    first_name: 'John',
    last_name: 'Doe',
    tlf: '+45123456789'
  })
})
.then(r => r.json())
.then(data => console.log(data));
```

### Login
```javascript
fetch('/wp-json/api/login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    email: 'user@example.com',
    password: 'password123'
  })
})
.then(r => r.json())
.then(data => {
  localStorage.setItem('token', data.token);
  console.log(data.user);
});
```

### Get Products (with token)
```javascript
fetch('/wp-json/api/products', {
  headers: {
    'Authorization': `Bearer ${localStorage.getItem('token')}`
  }
})
.then(r => r.json())
.then(data => console.log(data.products));
```

---

## Common Error Codes Reference

| Code | Message | Cause |
|------|---------|-------|
| 400 | Required field missing | Incomplete request body |
| 401 | Invalid credentials | Wrong email/password |
| 403 | Unauthorized access | Token required or invalid |
| 404 | Not found | Resource doesn't exist |
| 409 | Already exists | Email/unique constraint violated |

---

## Environment Variables for Testing

Create `.env.test` file:
```env
API_BASE_URL=http://localhost:8000/wp-json/api
TEST_EMAIL=test@example.com
TEST_PASSWORD=test_password_123
TEST_PRODUCT_ID=1
TEST_USER_ID=1
```

---

## Notes

- Replace `localhost:8000` with your actual domain
- Save token from login response and use in subsequent requests
- All POST/PUT endpoints require `Content-Type: application/json`
- Timestamps are in MySQL format: `YYYY-MM-DD HH:MM:SS`
- Prices are decimal with 2 decimal places
- Ratings are decimal with 2 decimal places
