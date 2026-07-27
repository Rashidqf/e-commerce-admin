<?php
/**
 * api/v1/bootstrap.php
 * Shared bootstrap for public shop JSON APIs.
 */

require_once dirname(__DIR__, 2) . '/includes/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

/**
 * PDO connection for API (JSON error on failure).
 */
function api_db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            error_log('API DB connection failed: ' . $e->getMessage());
            api_json_error('Database connection error.', 500);
        }
    }

    return $pdo;
}

function api_json_ok($data = null, string $message = 'OK', ?array $meta = null, int $code = 200): void
{
    http_response_code($code);
    $payload = [
        'success' => true,
        'message' => $message,
        'data'    => $data,
    ];
    if ($meta !== null) {
        $payload['meta'] = $meta;
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function api_json_error(string $message, int $code = 400, $errors = null): void
{
    http_response_code($code);
    $payload = [
        'success' => false,
        'message' => $message,
        'data'    => null,
    ];
    if ($errors !== null) {
        $payload['errors'] = $errors;
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function api_app_url(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return rtrim($scheme . '://' . $host . BASE_URL, '/');
}

function api_product_image_url(?string $filename): ?string
{
    if ($filename === null || $filename === '') {
        return null;
    }
    return api_app_url() . '/uploads/products/' . ltrim($filename, '/');
}

function api_category_image_url(?string $filename): ?string
{
    if ($filename === null || $filename === '') {
        return null;
    }
    return api_app_url() . '/uploads/categories/' . ltrim($filename, '/');
}

/** Effective selling price (sale_price if set, else price). */
function api_effective_price(array $product): float
{
    if (isset($product['sale_price']) && $product['sale_price'] !== null && $product['sale_price'] !== '') {
        return (float) $product['sale_price'];
    }
    return (float) $product['price'];
}

/**
 * Normalize a product row for public JSON.
 */
function api_format_product(array $row, bool $includeLong = false): array
{
    $formatted = [
        'id'                => (int) $row['id'],
        'category_id'       => (int) $row['category_id'],
        'category_title'    => $row['category_title'] ?? null,
        'name'              => $row['name'],
        'sku'               => $row['sku'],
        'price'             => (float) $row['price'],
        'sale_price'        => $row['sale_price'] !== null ? (float) $row['sale_price'] : null,
        'effective_price'   => api_effective_price($row),
        'quantity'          => (int) $row['quantity'],
        'in_stock'          => ((int) $row['quantity']) > 0,
        'short_description' => $row['short_description'],
        'main_image'        => api_product_image_url($row['main_image'] ?? null),
        'view_count'        => (int) ($row['view_count'] ?? 0),
        'created_at'        => $row['created_at'] ?? null,
    ];

    if ($includeLong) {
        $formatted['long_description'] = $row['long_description'] ?? null;
        $formatted['gallery'] = $row['gallery'] ?? [];
    }

    return $formatted;
}

function api_read_json_body(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return [];
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        api_json_error('Invalid JSON body.', 400);
    }
    return $data;
}

function api_require_method(string $method): void
{
    if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== strtoupper($method)) {
        api_json_error('Method not allowed. Use ' . strtoupper($method) . '.', 405);
    }
}
