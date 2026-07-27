# Public Shop JSON API Design

**Date:** 2026-07-27  
**Status:** Approved  
**Stack:** Core PHP + MySQL (PDO), no Composer

## Decisions

- JSON APIs only (no storefront UI yet)
- Guest checkout only (no customer login)
- Client-side cart; checkout via single `POST /orders`
- Payment: Bank Transfer only (no gateway)
- Approach: `api/v1/*.php` endpoints sharing bootstrap helpers

## Schema change

Add to `products`:

- `view_count` INT UNSIGNED NOT NULL DEFAULT 0

Incremented when product detail is fetched.

## Endpoints

Base path: `/ecommerce-admin/api/v1/`

| Method | Path | Purpose |
|--------|------|---------|
| GET | `home.php` | Categories + popular + latest products |
| GET | `categories.php` | Active categories list |
| GET | `products.php` | Filtered/paginated product list |
| GET | `product.php?id=` | Product detail + gallery; bumps `view_count` |
| POST | `orders.php` | Guest place order (Bank Transfer) |

### Product filters (`products.php`)

Query params: `category_id`, `search`, `min_price`, `max_price`, `sort` (`newest` \| `price_asc` \| `price_desc` \| `popular`), `page`, `per_page` (max 50).

Only `status = active` products. Effective price = `sale_price` if set, else `price`.

### Order body (`orders.php`)

```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "phone": "555-0101",
  "shipping_address": "123 Main St",
  "payment_method": "Bank Transfer",
  "items": [{ "product_id": 1, "quantity": 2 }]
}
```

Flow: validate → find/create customer by email → load products → check stock → transaction create order + items → decrement stock → return `order_number`.

## Response envelope

```json
{ "success": true, "message": "...", "data": {}, "meta": { "page": 1, "per_page": 12, "total": 40, "total_pages": 4 } }
```

Errors: HTTP 4xx/5xx with `success: false` and `message`.

## Cross-cutting

- CORS headers for future frontends
- Absolute image URLs via `BASE_URL` + `/uploads/...`
- PDO prepared statements; no admin session required
- `database.php` `die()` on DB fail must not run for API — API bootstrap catches connection errors as JSON

## Out of scope

Customer auth, server cart, payment gateway, HTML shop pages, admin APIs.
