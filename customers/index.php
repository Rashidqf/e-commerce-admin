<?php
/**
 * customers/index.php
 * Customer list with search, pagination, and totals.
 */
require_once __DIR__ . '/../includes/auth.php';
require_login();

$pdo = db();

$search = trim($_GET['search'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * PER_PAGE;

$where  = '';
$params = [];
if ($search !== '') {
    $where  = 'WHERE c.name LIKE ? OR c.email LIKE ? OR c.phone LIKE ?';
    $like   = "%$search%";
    $params = [$like, $like, $like];
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM customers c $where");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$pages = max(1, ceil($total / PER_PAGE));

$stmt = $pdo->prepare(
    "SELECT c.*,
            (SELECT COUNT(*) FROM orders WHERE customer_id = c.id) AS total_orders,
            (SELECT COALESCE(SUM(total),0) FROM orders WHERE customer_id = c.id AND payment_status = 'paid') AS total_spent
     FROM customers c
     $where
     ORDER BY c.created_at DESC
     LIMIT " . PER_PAGE . " OFFSET $offset"
);
$stmt->execute($params);
$customers = $stmt->fetchAll();

$pageTitle = 'Customers';
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Customers</h4>
</div>

<form method="GET" class="mb-3">
    <div class="input-group" style="max-width:400px;">
        <input type="text" name="search" class="form-control" placeholder="Search name, email, phone..." value="<?= e($search) ?>">
        <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i></button>
        <?php if ($search): ?>
            <a href="<?= e(BASE_URL) ?>/customers/" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
        <?php endif; ?>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light small text-muted">
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Orders</th>
                        <th>Total Spent</th>
                        <th>Registered</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($customers)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No customers found.</td></tr>
                <?php else: foreach ($customers as $c): ?>
                    <tr>
                        <td class="fw-semibold"><?= e($c['name']) ?></td>
                        <td class="small"><?= e($c['email']) ?></td>
                        <td class="small"><?= e($c['phone'] ?: '—') ?></td>
                        <td><?= (int)$c['total_orders'] ?></td>
                        <td class="fw-semibold">$<?= e(number_format($c['total_spent'], 2)) ?></td>
                        <td class="small text-muted"><?= e(date('M j, Y', strtotime($c['created_at']))) ?></td>
                        <td class="text-end">
                            <a href="<?= e(BASE_URL) ?>/customers/view.php?id=<?= (int)$c['id'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye me-1"></i> View
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
