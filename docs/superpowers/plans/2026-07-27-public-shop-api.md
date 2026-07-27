# Public Shop API Implementation Plan

> **For agentic workers:** Inline execution in this session.

**Goal:** Ship public JSON APIs for home, categories, products (filter + detail + popular), and guest Bank Transfer orders.

**Architecture:** `api/v1/` PHP endpoints sharing `bootstrap.php` (CORS, JSON helpers, safe DB). Reuse existing MySQL tables; add `products.view_count`.

**Tech Stack:** Core PHP 8, PDO, MySQL

## Global Constraints

- No Composer; no frameworks
- Guest checkout only; payment_method fixed to Bank Transfer
- Client-side cart; one POST creates the order
- Response envelope: `{ success, message, data, meta? }`
- Active products/categories only on public reads

### Task 1: Schema + API bootstrap

- Alter `products.view_count`; update `database.sql`
- Create `api/v1/bootstrap.php` with CORS, json_ok/json_error, product image URL helpers

### Task 2: Catalog endpoints

- `home.php`, `categories.php`, `products.php`, `product.php`

### Task 3: Orders endpoint

- `POST orders.php` with guest customer upsert, stock check, transaction

### Task 4: Smoke-test via PHP CLI / curl against localhost
