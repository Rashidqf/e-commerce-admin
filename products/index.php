<?php
/**
 * products/index.php
 * Product list with search, pagination, and delete action.
 */
require_once __DIR__ . '/../includes/auth.php';
require_login();

$pdo = db();

// --- Handle delete (GET with confirmation) ---
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    // Fetch image names for cleanup
    $stmt = $pdo->prepare('SELECT main_image FROM products WHERE id = ?');
    $stmt->execute([$id]);
    $img = $stmt->fetchColumn();
    if ($img && file_exists(PRODUCT_UPLOAD_DIR . $img)) {
        unlink(PRODUCT_UPLOAD_DIR . $img);
    }
    // Gallery images
    $gimgs = $pdo->prepare('SELECT image FROM product_images WHERE product_id = ?');
    $gimgs->execute([$id]);
    foreach ($gimgs->fetchAll() as $g) {
        if (file_exists(PRODUCT_UPLOAD_DIR . $g['image'])) {
            unlink(PRODUCT_UPLOAD_DIR . $g['image']);
        }
    }
    $del = $pdo->prepare('DELETE FROM products WHERE id = ?');
    $del->execute([$id]);
    set_flash('success', 'Product deleted successfully.');
    redirect('/products/');
}

// --- Search & Pagination ---
$search   = trim($_GET['search'] ?? '');
$page     = max(1, (int)($_GET['page'] ?? 1));
$offset   = ($page - 1) * PER_PAGE;

$where  = '';
$params = [];
if ($search !== '') {
    $where   = 'WHERE p.name LIKE ? OR p.sku LIKE ?';
    $like    = "%$search%";
    $params  = [$like, $like];
}

// Count
$countSql = "SELECT COUNT(*) FROM products p $where";
$stmt     = $pdo->prepare($countSql);
$stmt->execute($params);
$total    = (int) $stmt->fetchColumn();
$pages    = max(1, ceil($total / PER_PAGE));

// Fetch
$sql = "SELECT p.*, c.title AS category_title
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        $where
        ORDER BY p.created_at DESC
        LIMIT " . PER_PAGE . " OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$pageTitle = 'Products';
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Products</h4>
    <a href="<?= e(BASE_URL) ?>/products/add.php" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Add Product
    </a>
</div>

<!-- Search -->
<form method="GET" class="mb-3">
    <div class="input-group" style="max-width:400px;">
        <input type="text" name="search" class="form-control" placeholder="Search by name or SKU..."
               value="<?= e($search) ?>">
        <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i></button>
        <?php if ($search): ?>
            <a href="<?= e(BASE_URL) ?>/products/" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
        <?php endif; ?>
    </div>
</form>

<!-- Products Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light small text-muted">
                    <tr>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>SKU</th>
                        <th>Price</th>
                        <th>Sale Price</th>
                        <th>Qty</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($products)): ?>
                    <tr><td colspan="10" class="text-center text-muted py-4">No products found.</td></tr>
                <?php else: foreach ($products as $p): ?>
                    <tr>
                        <td>
                            <?php if ($p['main_image']): ?>
                                <img src="<?= e(BASE_URL) ?>/uploads/products/<?= e($p['main_image']) ?>" width="40" height="40" class="rounded object-fit-cover">
                            <?php else: ?>
                                <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width:40px;height:40px;"><i class="bi bi-image text-muted"></i></div>
                            <?php endif; ?>
                        </td>
                        <td class="fw-semibold"><?= e($p['name']) ?></td>
                        <td class="small"><?= e($p['category_title'] ?? '—') ?></td>
                        <td class="small"><?= e($p['sku']) ?></td>
                        <td>$<?= e(number_format($p['price'], 2)) ?></td>
                        <td>
                            <?php if ($p['sale_price']): ?>
                                <span class="text-danger">$<?= e(number_format($p['sale_price'], 2)) ?></span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?= (int)$p['quantity'] ?></td>
                        <td><span class="badge bg-<?= e($p['status'] === 'active' ? 'success' : 'secondary') ?>"><?= e(ucfirst($p['status'])) ?></span></td>
                        <td class="small text-muted"><?= e(date('M j, Y', strtotime($p['created_at']))) ?></td>
                        <td class="text-end">
                            <a href="<?= e(BASE_URL) ?>/products/edit.php?id=<?= (int)$p['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                            <a href="<?= e(BASE_URL) ?>/products/?delete=<?= (int)$p['id'] ?>"
                               class="btn btn-sm btn-outline-danger" title="Delete"
                               onclick="return confirm('Delete this product? This cannot be undone.')">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Pagination -->
<?php if ($pages > 1): ?>
<nav class="mt-3">
    <ul class="pagination justify-content-center">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>&search=<?= e(urlencode($search)) ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
