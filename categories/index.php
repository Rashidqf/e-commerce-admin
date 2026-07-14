<?php
/**
 * categories/index.php
 * Category list with search, pagination, and delete.
 */
require_once __DIR__ . '/../includes/auth.php';
require_login();

$pdo = db();

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Check if products use this category
    $chk = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id = ?');
    $chk->execute([$id]);
    if ($chk->fetchColumn() > 0) {
        set_flash('danger', 'Cannot delete: products exist in this category. Remove or reassign them first.');
    } else {
        $stmt = $pdo->prepare('SELECT image FROM categories WHERE id = ?');
        $stmt->execute([$id]);
        $img = $stmt->fetchColumn();
        if ($img && file_exists(CATEGORY_UPLOAD_DIR . $img)) {
            unlink(CATEGORY_UPLOAD_DIR . $img);
        }
        $del = $pdo->prepare('DELETE FROM categories WHERE id = ?');
        $del->execute([$id]);
        set_flash('success', 'Category deleted successfully.');
    }
    redirect('/categories/');
}

// Search & Pagination
$search = trim($_GET['search'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * PER_PAGE;

$where  = '';
$params = [];
if ($search !== '') {
    $where  = 'WHERE title LIKE ?';
    $params = ["%$search%"];
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM categories $where");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$pages = max(1, ceil($total / PER_PAGE));

$stmt = $pdo->prepare("SELECT * FROM categories $where ORDER BY created_at DESC LIMIT " . PER_PAGE . " OFFSET $offset");
$stmt->execute($params);
$categories = $stmt->fetchAll();

$pageTitle = 'Categories';
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Categories</h4>
    <a href="<?= e(BASE_URL) ?>/categories/add.php" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Add Category
    </a>
</div>

<form method="GET" class="mb-3">
    <div class="input-group" style="max-width:400px;">
        <input type="text" name="search" class="form-control" placeholder="Search categories..." value="<?= e($search) ?>">
        <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i></button>
        <?php if ($search): ?>
            <a href="<?= e(BASE_URL) ?>/categories/" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
        <?php endif; ?>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light small text-muted">
                    <tr>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($categories)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No categories found.</td></tr>
                <?php else: foreach ($categories as $c): ?>
                    <tr>
                        <td>
                            <?php if ($c['image']): ?>
                                <img src="<?= e(BASE_URL) ?>/uploads/categories/<?= e($c['image']) ?>" width="40" height="40" class="rounded object-fit-cover">
                            <?php else: ?>
                                <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width:40px;height:40px;"><i class="bi bi-image text-muted"></i></div>
                            <?php endif; ?>
                        </td>
                        <td class="fw-semibold"><?= e($c['title']) ?></td>
                        <td class="small text-muted" style="max-width:300px;"><?= e(mb_strimwidth($c['description'] ?? '', 0, 80, '…')) ?></td>
                        <td><span class="badge bg-<?= e($c['status'] === 'active' ? 'success' : 'secondary') ?>"><?= e(ucfirst($c['status'])) ?></span></td>
                        <td class="small text-muted"><?= e(date('M j, Y', strtotime($c['created_at']))) ?></td>
                        <td class="text-end">
                            <a href="<?= e(BASE_URL) ?>/categories/edit.php?id=<?= (int)$c['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                            <a href="<?= e(BASE_URL) ?>/categories/?delete=<?= (int)$c['id'] ?>"
                               class="btn btn-sm btn-outline-danger" title="Delete"
                               onclick="return confirm('Delete this category?')">
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
