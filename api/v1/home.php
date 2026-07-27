<?php
/**
 * GET /api/v1/home.php
 * Home payload: categories, popular products, latest products.
 */
require_once __DIR__ . '/bootstrap.php';

api_require_method('GET');

$pdo = api_db();

$catStmt = $pdo->query(
    "SELECT id, title, description, image, status
     FROM categories
     WHERE status = 'active'
     ORDER BY title ASC"
);
$categories = [];
foreach ($catStmt->fetchAll() as $c) {
    $categories[] = [
        'id'          => (int) $c['id'],
        'title'       => $c['title'],
        'description' => $c['description'],
        'image'       => api_category_image_url($c['image']),
    ];
}

$productSelect = "SELECT p.*, c.title AS category_title
                  FROM products p
                  INNER JOIN categories c ON c.id = p.category_id
                  WHERE p.status = 'active'";

$popularStmt = $pdo->query($productSelect . ' ORDER BY p.view_count DESC, p.id DESC LIMIT 8');
$popular = array_map('api_format_product', $popularStmt->fetchAll());

$latestStmt = $pdo->query($productSelect . ' ORDER BY p.created_at DESC, p.id DESC LIMIT 8');
$latest = array_map('api_format_product', $latestStmt->fetchAll());

api_json_ok([
    'categories'        => $categories,
    'popular_products'  => $popular,
    'latest_products'   => $latest,
], 'Home data loaded.');
