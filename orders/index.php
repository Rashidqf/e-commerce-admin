<?php
/**
 * orders/index.php
 * Order list with search, pagination, and status filter.
 */
require_once __DIR__ . '/../includes/auth.php';
require_login();

$pdo = db();

// Search & Pagination
$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * PER_PAGE;

$where  = 'WHERE 1=1';
$params = [];
if ($search !== '') {
    $where  .= ' AND (o.order_number LIKE ? OR c.name LIKE ? OR c.email LIKE ?)';
    $like   = "%$search%";
    $params = array_merge($params, [$like, $like, $like]);
}
if ($status !== '') {
    $where  .= ' AND o.order_status = ?';
    $params[] = $status;
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM orders o JOIN customers c ON o.customer_id = c.id $where");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$pages = max(1, ceil($total / PER_PAGE));

$stmt = $pdo->prepare(
    "SELECT o.id, o.order_number, o.total, o.payment_status, o.order_status, o.created_at,
            c.name AS customer_name
     FROM orders o
     JOIN customers c ON o.customer_id = c.id
     $where
     ORDER BY o.created_at DESC
     LIMIT " . PER_PAGE . " OFFSET $offset"
);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$statusColors = [
    'pending'     => 'warning',
    'processing'   => 'info',
    'shipped'      => 'primary',
    'delivered'    => 'success',
    'cancelled'    => 'danger',
];
$payColors = [
    'pending'  => 'warning',
    'paid'     => 'success',
    'failed'   => 'danger',
    'refunded' => 'secondary',
];

$pageTitle = 'Orders';
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Orders</h4>
</div>

<form method="GET" class="row g-2 mb-3" style="max-width:700px;">
    <div class="col-md-5">
        <input type="text" name="search" class="form-control" placeholder="Search order #, customer..." value="<?= e($search) ?>">
    </div>
    <div class="col-md-4">
        <select name="status" class="form-select">
            <option value="">All Statuses</option>
            <?php foreach (['pending','processing','shipped','delivered','cancelled'] as $s): ?>
                <option value="<?= e($s) ?>" <?= $status === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <button class="btn btn-outline-primary w-100"><i class="bi bi-search me-1"></i> Filter</button>
        <a href="<?= e(BASE_URL) ?>/orders/" class="btn btn-outline-secondary btn-sm mt-1 w-100">Clear</a>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light small text-muted">
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Order Status</th>
                        <th>Date</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($orders)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No orders found.</td></tr>
                <?php else: foreach ($orders as $o): ?>
                    <tr>
                        <td class="fw-semibold"><?= e($o['order_number']) ?></td>
                        <td><?= e($o['customer_name']) ?></td>
                        <td>$<?= e(number_format($o['total'], 2)) ?></td>
                        <td><span class="badge bg-<?= e($payColors[$o['payment_status']] ?? 'secondary') ?>"><?= e(ucfirst($o['payment_status'])) ?></span></td>
                        <td><span class="badge bg-<?= e($statusColors[$o['order_status']] ?? 'secondary') ?>"><?= e(ucfirst($o['order_status'])) ?></span></td>
                        <td class="small text-muted"><?= e(date('M j, Y g:i A', strtotime($o['created_at']))) ?></td>
                        <td class="text-end">
                            <a href="<?= e(BASE_URL) ?>/orders/view.php?id=<?= (int)$o['id'] ?>" class="btn btn-sm btn-outline-primary" title="View">
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
                <a class="page-link" href="?page=<?= $i ?>&search=<?= e(urlencode($search)) ?>&status=<?= e($status) ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
