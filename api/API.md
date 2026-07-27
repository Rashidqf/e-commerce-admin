# Public Shop API — AI / Frontend Reference

Use this document to build a storefront that consumes the ShopAdmin public JSON APIs.

- **Base URL (local):** `http://localhost/ecommerce-admin/api/v1`
- **Content-Type:** `application/json`
- **Auth:** None (public + guest checkout)
- **CORS:** `Access-Control-Allow-Origin: *`
- **Cart:** Client-side only — send items in `POST /orders.php` at checkout
- **Payment:** `Bank Transfer` only (no gateway)

---

## Response envelope

### Success

```json
{
  "success": true,
  "message": "OK",
  "data": {},
  "meta": {}
}
```

`meta` is present only on paginated list endpoints (e.g. products).

### Error

```json
{
  "success": false,
  "message": "Human readable error",
  "data": null,
  "errors": {
    "field_name": "Field-specific message"
  }
}
```

`errors` is optional (used mainly for validation `422`).

### Common HTTP status codes

| Code | Meaning |
|------|---------|
| 200 | OK |
| 201 | Created (order placed) |
| 400 | Bad request / business rule (e.g. out of stock) |
| 404 | Not found |
| 405 | Wrong HTTP method |
| 422 | Validation failed |
| 500 | Server error |

---

## Shared product object (list / home)

```json
{
  "id": 1,
  "category_id": 1,
  "category_title": "Electronics",
  "name": "Wireless Mouse",
  "sku": "WM-001",
  "price": 25.0,
  "sale_price": 19.99,
  "effective_price": 19.99,
  "quantity": 100,
  "in_stock": true,
  "short_description": "Ergonomic wireless mouse",
  "main_image": "http://localhost/ecommerce-admin/uploads/products/example.jpg",
  "view_count": 3,
  "created_at": "2026-07-15 10:00:00"
}
```

Notes:

- `effective_price` = `sale_price` if set, otherwise `price` (use this for display/cart totals)
- `main_image` may be `null`
- Only **active** products are returned

### Product detail extras

Same fields as above, plus:

```json
{
  "long_description": "Full product description...",
  "gallery": [
    { "id": 1, "image": "http://localhost/ecommerce-admin/uploads/products/g1.jpg" }
  ]
}
```

---

## Endpoints

### 1. API discovery

`GET /index.php`

**Expected response `200`:**

```json
{
  "success": true,
  "message": "API ready.",
  "data": {
    "name": "ShopAdmin Public Shop API",
    "version": "v1",
    "endpoints": [
      {
        "method": "GET",
        "url": "http://localhost/ecommerce-admin/api/v1/home.php",
        "desc": "Home: categories, popular, latest"
      },
      {
        "method": "GET",
        "url": "http://localhost/ecommerce-admin/api/v1/categories.php",
        "desc": "Active categories (?id= optional)"
      },
      {
        "method": "GET",
        "url": "http://localhost/ecommerce-admin/api/v1/products.php",
        "desc": "Products with filters & pagination"
      },
      {
        "method": "GET",
        "url": "http://localhost/ecommerce-admin/api/v1/product.php",
        "desc": "Product detail (?id= required)"
      },
      {
        "method": "POST",
        "url": "http://localhost/ecommerce-admin/api/v1/orders.php",
        "desc": "Guest order (Bank Transfer)"
      }
    ]
  }
}
```

---

### 2. Home page data

`GET /home.php`

Use for the storefront homepage.

**Expected response `200`:**

```json
{
  "success": true,
  "message": "Home data loaded.",
  "data": {
    "categories": [
      {
        "id": 1,
        "title": "Electronics",
        "description": "Electronic gadgets and devices",
        "image": null
      }
    ],
    "popular_products": [
      {
        "id": 1,
        "category_id": 1,
        "category_title": "Electronics",
        "name": "Wireless Mouse",
        "sku": "WM-001",
        "price": 25.0,
        "sale_price": 19.99,
        "effective_price": 19.99,
        "quantity": 99,
        "in_stock": true,
        "short_description": "Ergonomic wireless mouse",
        "main_image": null,
        "view_count": 5,
        "created_at": "2026-07-15 10:00:00"
      }
    ],
    "latest_products": []
  }
}
```

- `popular_products`: top 8 by `view_count`
- `latest_products`: top 8 by `created_at`

---

### 3. Categories list / single

`GET /categories.php`  
`GET /categories.php?id=1`

**List expected response `200`:**

```json
{
  "success": true,
  "message": "Categories loaded.",
  "data": [
    {
      "id": 1,
      "title": "Electronics",
      "description": "Electronic gadgets and devices",
      "image": null,
      "product_count": 2,
      "created_at": "2026-07-15 10:00:00"
    }
  ]
}
```

**Single expected response `200`:**

```json
{
  "success": true,
  "message": "Category loaded.",
  "data": {
    "id": 1,
    "title": "Electronics",
    "description": "Electronic gadgets and devices",
    "image": null,
    "product_count": 2,
    "created_at": "2026-07-15 10:00:00"
  }
}
```

**Error `404`:**

```json
{
  "success": false,
  "message": "Category not found.",
  "data": null
}
```

---

### 4. Products list (shop + filters)

`GET /products.php`

#### Query parameters

| Param | Type | Default | Description |
|-------|------|---------|-------------|
| `category_id` | int | — | Filter by category |
| `search` | string | — | Search name, SKU, short_description |
| `min_price` | number | — | Min effective price |
| `max_price` | number | — | Max effective price |
| `sort` | string | `newest` | `newest` \| `price_asc` \| `price_desc` \| `popular` |
| `page` | int | `1` | Page number |
| `per_page` | int | `12` | Max `50` |

#### Example URLs

```
GET /products.php
GET /products.php?category_id=1&sort=price_asc
GET /products.php?search=mouse&page=1&per_page=12
GET /products.php?min_price=10&max_price=50&sort=popular
```

**Expected response `200`:**

```json
{
  "success": true,
  "message": "Products loaded.",
  "data": [
    {
      "id": 1,
      "category_id": 1,
      "category_title": "Electronics",
      "name": "Wireless Mouse",
      "sku": "WM-001",
      "price": 25.0,
      "sale_price": 19.99,
      "effective_price": 19.99,
      "quantity": 99,
      "in_stock": true,
      "short_description": "Ergonomic wireless mouse",
      "main_image": null,
      "view_count": 5,
      "created_at": "2026-07-15 10:00:00"
    }
  ],
  "meta": {
    "page": 1,
    "per_page": 12,
    "total": 4,
    "total_pages": 1,
    "sort": "newest",
    "filters": {
      "category_id": null,
      "search": null,
      "min_price": null,
      "max_price": null
    }
  }
}
```

---

### 5. Product detail

`GET /product.php?id=1`

- Required query: `id` (int)
- Increments `view_count` by 1 on each successful request

**Expected response `200`:**

```json
{
  "success": true,
  "message": "Product loaded.",
  "data": {
    "id": 1,
    "category_id": 1,
    "category_title": "Electronics",
    "name": "Wireless Mouse",
    "sku": "WM-001",
    "price": 25.0,
    "sale_price": 19.99,
    "effective_price": 19.99,
    "quantity": 99,
    "in_stock": true,
    "short_description": "Ergonomic wireless mouse",
    "main_image": null,
    "view_count": 6,
    "created_at": "2026-07-15 10:00:00",
    "long_description": "A high-precision wireless mouse with USB receiver and long battery life.",
    "gallery": [
      {
        "id": 1,
        "image": "http://localhost/ecommerce-admin/uploads/products/gallery1.jpg"
      }
    ]
  }
}
```

**Error `422` (missing id):**

```json
{
  "success": false,
  "message": "Product id is required.",
  "data": null
}
```

**Error `404`:**

```json
{
  "success": false,
  "message": "Product not found.",
  "data": null
}
```

---

### 6. Place guest order (checkout)

`POST /orders.php`  
Header: `Content-Type: application/json`

Cart is **not** stored on the server. Frontend keeps cart locally, then posts full cart here.

#### Request body

```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "phone": "555-0101",
  "shipping_address": "123 Main Street, Springfield",
  "payment_method": "Bank Transfer",
  "items": [
    { "product_id": 1, "quantity": 2 },
    { "product_id": 3, "quantity": 1 }
  ]
}
```

#### Field rules

| Field | Required | Rules |
|-------|----------|--------|
| `name` | yes | non-empty string |
| `email` | yes | valid email; creates or updates customer by email |
| `phone` | no | string |
| `shipping_address` | yes | non-empty string |
| `payment_method` | yes | must be exactly `Bank Transfer` (case-insensitive) |
| `items` | yes | non-empty array |
| `items[].product_id` | yes | existing active product |
| `items[].quantity` | yes | integer > 0; must be ≤ stock |

Server behavior:

1. Find/create customer by email  
2. Lock products, check stock  
3. Create order + order_items (price snapshot = effective price)  
4. Decrement stock  
5. Return order number (`payment_status` / `order_status` = `pending`)

**Expected response `201`:**

```json
{
  "success": true,
  "message": "Order placed successfully. Please complete bank transfer.",
  "data": {
    "order_id": 3,
    "order_number": "ORD-20260727-97DFF2",
    "total": 39.98,
    "payment_method": "Bank Transfer",
    "payment_status": "pending",
    "order_status": "pending",
    "items": [
      {
        "product_id": 1,
        "product_name": "Wireless Mouse",
        "quantity": 2,
        "price": 19.99,
        "total": 39.98
      }
    ]
  }
}
```

**Validation error `422`:**

```json
{
  "success": false,
  "message": "Validation failed.",
  "data": null,
  "errors": {
    "name": "Name is required.",
    "email": "Valid email is required.",
    "shipping_address": "Shipping address is required.",
    "items": "At least one cart item is required.",
    "payment_method": "Only Bank Transfer is supported."
  }
}
```

**Stock / availability error `400` (example):**

```json
{
  "success": false,
  "message": "Insufficient stock for \"Wireless Mouse\". Available: 1.",
  "data": null
}
```

**Wrong method `405`:**

```json
{
  "success": false,
  "message": "Method not allowed. Use POST.",
  "data": null
}
```

---

## Frontend integration notes (for AI)

1. **Base path:** prepend `http://localhost/ecommerce-admin/api/v1` (or production equivalent).
2. **Home page:** call `home.php` once for categories + popular + latest.
3. **Shop page:** call `products.php` with filters; render pagination from `meta`.
4. **Product page:** call `product.php?id=`; show gallery + long description.
5. **Cart:** store `{ product_id, quantity, name, effective_price, main_image }` in localStorage; never trust client prices for billing — server recalculates on order.
6. **Checkout:** POST guest form + cart items to `orders.php`; show `order_number` and instruct user to pay via bank transfer.
7. **Images:** use returned absolute URLs; handle `null` with a placeholder.
8. Always check `success === true` before using `data`.

---

## Quick curl examples

```bash
curl "http://localhost/ecommerce-admin/api/v1/home.php"

curl "http://localhost/ecommerce-admin/api/v1/products.php?sort=popular&per_page=8"

curl "http://localhost/ecommerce-admin/api/v1/product.php?id=1"

curl -X POST "http://localhost/ecommerce-admin/api/v1/orders.php" \
  -H "Content-Type: application/json" \
  -d "{\"name\":\"John Doe\",\"email\":\"john@example.com\",\"phone\":\"555-0101\",\"shipping_address\":\"123 Main St\",\"payment_method\":\"Bank Transfer\",\"items\":[{\"product_id\":1,\"quantity\":1}]}"
```
