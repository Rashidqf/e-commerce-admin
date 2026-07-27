<?php
/**
 * GET /api/v1/product.php?id=
 * Product detail + gallery. Increments view_count.
 */
require_once __DIR__ . '/bootstrap.php';

api_require_method('GET');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    api_json_error('Product id is required.', 422);
}

$pdo = api_db();

$stmt = $pdo->prepare(
    "SELECT p.*, c.title AS category_title
     FROM products p
     INNER JOIN categories c ON c.id = p.category_id
     WHERE p.id = ? AND p.status = 'active'
     LIMIT 1"
);
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    api_json_error('Product not found.', 404);
}

$pdo->prepare('UPDATE products SET view_count = view_count + 1 WHERE id = ?')->execute([$id]);
$product['view_count'] = (int) $product['view_count'] + 1;

$imgStmt = $pdo->prepare(
    'SELECT id, image FROM product_images WHERE product_id = ? ORDER BY id ASC'
);
$imgStmt->execute([$id]);
$gallery = [];
foreach ($imgStmt->fetchAll() as $img) {
    $gallery[] = [
        'id'    => (int) $img['id'],
        'image' => api_product_image_url($img['image']),
    ];
}
$product['gallery'] = $gallery;

api_json_ok(api_format_product($product, true), 'Product loaded.');
