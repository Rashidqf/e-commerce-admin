<?php
/**
 * GET /api/v1/products.php
 * Shop product list with filters and pagination.
 *
 * Query: category_id, search, min_price, max_price,
 *        sort=newest|price_asc|price_desc|popular, page, per_page
 */
require_once __DIR__ . '/bootstrap.php';

api_require_method('GET');

$pdo = api_db();

$categoryId = isset($_GET['category_id']) ? (int) $_GET['category_id'] : 0;
$search     = trim((string) ($_GET['search'] ?? ''));
$minPrice   = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? (float) $_GET['min_price'] : null;
$maxPrice   = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (float) $_GET['max_price'] : null;
$sort       = strtolower(trim((string) ($_GET['sort'] ?? 'newest')));
$page       = max(1, (int) ($_GET['page'] ?? 1));
$perPage    = (int) ($_GET['per_page'] ?? 12);
$perPage    = min(50, max(1, $perPage));
$offset     = ($page - 1) * $perPage;

$where  = ["p.status = 'active'"];
$params = [];

if ($categoryId > 0) {
    $where[]  = 'p.category_id = ?';
    $params[] = $categoryId;
}

if ($search !== '') {
    $where[]  = '(p.name LIKE ? OR p.sku LIKE ? OR p.short_description LIKE ?)';
    $like     = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

// Effective price for filtering: COALESCE(sale_price, price)
$eff = 'COALESCE(p.sale_price, p.price)';

if ($minPrice !== null) {
    $where[]  = "$eff >= ?";
    $params[] = $minPrice;
}

if ($maxPrice !== null) {
    $where[]  = "$eff <= ?";
    $params[] = $maxPrice;
}

$whereSql = implode(' AND ', $where);

switch ($sort) {
    case 'price_asc':
        $orderSql = "$eff ASC, p.id ASC";
        break;
    case 'price_desc':
        $orderSql = "$eff DESC, p.id DESC";
        break;
    case 'popular':
        $orderSql = 'p.view_count DESC, p.id DESC';
        break;
    case 'newest':
    default:
        $sort     = 'newest';
        $orderSql = 'p.created_at DESC, p.id DESC';
        break;
}

$countSql = "SELECT COUNT(*) FROM products p WHERE $whereSql";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

$listSql = "SELECT p.*, c.title AS category_title
            FROM products p
            INNER JOIN categories c ON c.id = p.category_id
            WHERE $whereSql
            ORDER BY $orderSql
            LIMIT $perPage OFFSET $offset";

$listStmt = $pdo->prepare($listSql);
$listStmt->execute($params);
$items = array_map('api_format_product', $listStmt->fetchAll());

$totalPages = $total > 0 ? (int) ceil($total / $perPage) : 0;

api_json_ok($items, 'Products loaded.', [
    'page'        => $page,
    'per_page'    => $perPage,
    'total'       => $total,
    'total_pages' => $totalPages,
    'sort'        => $sort,
    'filters'     => [
        'category_id' => $categoryId > 0 ? $categoryId : null,
        'search'      => $search !== '' ? $search : null,
        'min_price'   => $minPrice,
        'max_price'   => $maxPrice,
    ],
]);
