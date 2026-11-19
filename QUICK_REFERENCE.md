# API Quick Reference Card

## Base URL
```
/wp-json/api
```

---

## Authentication

### Register
```
POST /register
{ email, password, first_name, last_name, tlf }
→ 201 { user: {...} }
```

### Login
```
POST /login
{ email, password }
→ 200 { user: {...}, token: "..." }
```

### Logout
```
POST /logout
Authorization: Bearer {token}
→ 200 { message: "..." }
```

---

## Products

### List
```
GET /products
→ 200 { products: [...] }
```

### Get One
```
GET /products/{id}
→ 200 { product: {...} }
``` 

### Create
```
POST /products
Auth: Bearer {token}
{ name, description, price, rating?, mærke? }
→ 201 { product: {...} }
```

### Update
```
PUT /products/{id}
Auth: Bearer {token}
{ name, description, price, rating?, mærke? }
→ 200 { product: {...} }
```

### Delete
```
DELETE /products/{id}
Auth: Bearer {token}
→ 200 { message: "..." }
```

---

## Attributes

### List
```
GET /attributes-values
→ 200 { attributesValues: [...] }
```

### Get One
```
GET /attributes-values/{id}
→ 200 { attributesValue: {...} }
```

### Create
```
POST /attributes-values
Auth: Bearer {token}
{ attributes_name, description?, data_type }
→ 201 { attributesValue: {...} }
```

### Update
```
PUT /attributes-values/{id}
Auth: Bearer {token}
{ attributes_name, description?, data_type }
→ 200 { attributesValue: {...} }
```

### Delete
```
DELETE /attributes-values/{id}
Auth: Bearer {token}
→ 200 { message: "..." }
```

---

## Orders

### List User Orders
```
GET /orders/{uid}
Auth: Bearer {token}
→ 200 { orderItems: [...] }
```

### Create Order
```
POST /orders/{uid}
Auth: Bearer {token}
{ product_id, quantity }
→ 201 { order: {...} }
```

### Add Item to Order
```
POST /orders/{order_id}/items
Auth: Bearer {token}
{ product_id, quantity }
→ 201/200 { orderItem: {...} }

Note: quantity=0 removes item
```

---

## Status Codes

| Code | Usage |
|------|-------|
| 200 | Success (GET, PUT, POST success) |
| 201 | Created (POST success) |
| 400 | Bad request / validation error |
| 401 | Invalid credentials |
| 403 | Unauthorized / forbidden access |
| 404 | Resource not found |
| 409 | Conflict (duplicate email, etc.) |

---

## Response Format

### Success List
```json
{
  "products": [
    { product_id, name, ... }
  ]
}
```

### Success Single
```json
{
  "product": {
    "product_id": 1,
    "name": "...",
    ...
  }
}
```

### Error
```json
{
  "message": "Error description"
}
```

---

## Common Fields

### Product
```
product_id, name, description, price, rating, mærke, attributeValues
```

### User
```
userID, first_name, last_name, email, tlf, is_employee
```

### Order
```
order_id, uid, order_date, total_amount, status, orderItems
```

### OrderItem
```
product_id, amount
```

### Attribute
```
attributes_id, attributes_name, description, data_type
```

---

## Example Workflow

```bash
# 1. Register
POST /register
{ email, password, first_name, last_name, tlf }

# 2. Login
POST /login
{ email, password }
→ Save token

# 3. Create Product (admin)
POST /products
{ name, price, ... }

# 4. Create Order
POST /orders/{uid}
{ product_id: 1, quantity: 2 }

# 5. Add Item
POST /orders/{order_id}/items
{ product_id: 2, quantity: 1 }

# 6. Get Orders
GET /orders/{uid}
→ View all orders with items
```

---

## Errors Reference

| Error | Code | Cause |
|-------|------|-------|
| "Email and password required" | 400 | Missing field |
| "Invalid email address" | 400 | Bad email format |
| "Invalid credentials" | 401 | Wrong password |
| "Email already exists" | 409 | Duplicate registration |
| "Product not found" | 404 | Invalid product_id |
| "Unauthorized access" | 403 | Wrong user/admin |
| "Order not found" | 404 | Invalid order_id |

---

## Notes

- All times: `YYYY-MM-DD HH:MM:SS` format
- All prices: 2 decimal places
- All ratings: 2 decimal places
- Quantities: integers only
- Status values: lowercase ("pending", "completed", etc.)
- Tokens: JWT bearer tokens from `/login`; `/logout` revokes the submitted token
- Phone: Danish format (+45...)

---

## Files

- **API_ENDPOINTS.md** - Detailed examples
- **API_TESTING.md** - Testing guide
- **README_API_UPDATES.md** - Quick start
- **API_ALIGNMENT_CHANGES.md** - What changed
- **STRUCTURE_COMPARISON.md** - Laravel comparison
