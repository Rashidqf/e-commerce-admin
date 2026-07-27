<?php
/**
 * GET /api/v1/categories.php
 * List active categories (optional ?id= for single).
 */
require_once __DIR__ . '/bootstrap.php';

api_require_method('GET');

$pdo = api_db();
$id  = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id > 0) {
    $stmt = $pdo->prepare(
        "SELECT id, title, description, image, status, created_at
         FROM categories
         WHERE id = ? AND status = 'active'
         LIMIT 1"
    );
    $stmt->execute([$id]);
    $c = $stmt->fetch();
    if (!$c) {
        api_json_error('Category not found.', 404);
    }

    $countStmt = $pdo->prepare(
        "SELECT COUNT(*) FROM products WHERE category_id = ? AND status = 'active'"
    );
    $countStmt->execute([$id]);

    api_json_ok([
        'id'             => (int) $c['id'],
        'title'          => $c['title'],
        'description'    => $c['description'],
        'image'          => api_category_image_url($c['image']),
        'product_count'  => (int) $countStmt->fetchColumn(),
        'created_at'     => $c['created_at'],
    ], 'Category loaded.');
}

$stmt = $pdo->query(
    "SELECT c.id, c.title, c.description, c.image, c.created_at,
            (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.status = 'active') AS product_count
     FROM categories c
     WHERE c.status = 'active'
     ORDER BY c.title ASC"
);

$items = [];
foreach ($stmt->fetchAll() as $c) {
    $items[] = [
        'id'            => (int) $c['id'],
        'title'         => $c['title'],
        'description'   => $c['description'],
        'image'         => api_category_image_url($c['image']),
        'product_count' => (int) $c['product_count'],
        'created_at'    => $c['created_at'],
    ];
}

api_json_ok($items, 'Categories loaded.');
