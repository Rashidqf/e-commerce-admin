<?php
/**
 * customers/view.php
 * Customer detail: profile, address, and previous orders.
 */
require_once __DIR__ . '/../includes/auth.php';
require_login();

$pdo = db();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { set_flash('danger', 'Invalid customer ID.'); redirect('/customers/'); }

$stmt = $pdo->prepare('SELECT * FROM customers WHERE id = ?');
$stmt->execute([$id]);
$customer = $stmt->fetch();
if (!$customer) { set_flash('danger', 'Customer not found.'); redirect('/customers/'); }

// Fetch orders
$ordersStmt = $pdo->prepare(
    'SELECT o.id, o.order_number, o.total, o.payment_status, o.order_status, o.created_at
     FROM orders o
     WHERE o.customer_id = ?
     ORDER BY o.created_at DESC'
);
$ordersStmt->execute([$id]);
$orders = $ordersStmt->fetchAll();

$totalSpent = 0;
foreach ($orders as $o) {
    if ($o['payment_status'] === 'paid') $totalSpent += (float)$o['total'];
}

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

$pageTitle = 'Customer Detail';
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Customer Detail</h4>
    <a href="<?= e(BASE_URL) ?>/customers/" class="btn btn-outline-secondary btn-sm">&larr; Back</a>
</div>

<div class="row g-3">
    <!-- Profile -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-person me-1"></i> Profile</h6></div>
            <div class="card-body text-center">
                <?php if ($customer['profile_image']): ?>
                    <img src="<?= e(BASE_URL) ?>/uploads/admins/<?= e($customer['profile_image']) ?>" class="rounded-circle mb-3" width="80" height="80" style="object-fit:cover;">
                <?php else: ?>
                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:80px;height:80px;">
                        <i class="bi bi-person-fill fs-1 text-muted"></i>
                    </div>
                <?php endif; ?>
                <h5 class="mb-1"><?= e($customer['name']) ?></h5>
                <p class="text-muted small mb-2"><?= e($customer['email']) ?></p>
                <p class="small text-muted mb-1"><i class="bi bi-telephone me-1"></i> <?= e($customer['phone'] ?: 'N/A') ?></p>
                <p class="small text-muted mb-0"><i class="bi bi-calendar me-1"></i> Joined <?= e(date('M j, Y', strtotime($customer['created_at']))) ?></p>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-3">
            <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-geo-alt me-1"></i> Address</h6></div>
            <div class="card-body">
                <p class="mb-0"><?= e($customer['address'] ?: 'No address on file.') ?></p>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-3">
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6 border-end">
                        <div class="fs-4 fw-bold text-primary"><?= count($orders) ?></div>
                        <div class="small text-muted">Total Orders</div>
                    </div>
                    <div class="col-6">
                        <div class="fs-4 fw-bold text-success">$<?= e(number_format($totalSpent, 2)) ?></div>
                        <div class="small text-muted">Total Spent</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Orders -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-receipt me-1"></i> Previous Orders</h6></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light small text-muted">
                            <tr>
                                <th>Order #</th>
                                <th>Total</th>
                                <th>Payment</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th class="text-end">View</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($orders)): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">No orders yet.</td></tr>
                        <?php else: foreach ($orders as $o): ?>
                            <tr>
                                <td class="fw-semibold"><?= e($o['order_number']) ?></td>
                                <td>$<?= e(number_format($o['total'], 2)) ?></td>
                                <td><span class="badge bg-<?= e($payColors[$o['payment_status']] ?? 'secondary') ?>"><?= e(ucfirst($o['payment_status'])) ?></span></td>
                                <td><span class="badge bg-<?= e($statusColors[$o['order_status']] ?? 'secondary') ?>"><?= e(ucfirst($o['order_status'])) ?></span></td>
                                <td class="small text-muted"><?= e(date('M j, Y', strtotime($o['created_at']))) ?></td>
                                <td class="text-end">
                                    <a href="<?= e(BASE_URL) ?>/orders/view.php?id=<?= (int)$o['id'] ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
