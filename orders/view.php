<?php
/**
 * orders/view.php
 * Order detail: customer info, shipping address, items, totals,
 * payment info, and status update form.
 */
require_once __DIR__ . '/../includes/auth.php';
require_login();

$pdo = db();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { set_flash('danger', 'Invalid order ID.'); redirect('/orders/'); }

// Fetch order + customer
$stmt = $pdo->prepare(
    'SELECT o.*, c.name AS customer_name, c.email AS customer_email, c.phone AS customer_phone
     FROM orders o
     JOIN customers c ON o.customer_id = c.id
     WHERE o.id = ?'
);
$stmt->execute([$id]);
$order = $stmt->fetch();
if (!$order) { set_flash('danger', 'Order not found.'); redirect('/orders/'); }

// Fetch order items
$itemsStmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
$itemsStmt->execute([$id]);
$items = $itemsStmt->fetchAll();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        set_flash('danger', 'Invalid session token.');
    } else {
        $orderStatus  = $_POST['order_status']  ?? $order['order_status'];
        $paymentStatus = $_POST['payment_status'] ?? $order['payment_status'];

        $validOrder   = ['pending','processing','shipped','delivered','cancelled'];
        $validPayment = ['pending','paid','failed','refunded'];

        if (in_array($orderStatus, $validOrder) && in_array($paymentStatus, $validPayment)) {
            $upd = $pdo->prepare('UPDATE orders SET order_status=?, payment_status=? WHERE id=?');
            $upd->execute([$orderStatus, $paymentStatus, $id]);
            set_flash('success', 'Order status updated successfully.');
        } else {
            set_flash('danger', 'Invalid status value.');
        }
        redirect('/orders/view.php?id=' . $id);
    }
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

$pageTitle = 'Order Detail';
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Order <?= e($order['order_number']) ?></h4>
    <a href="<?= e(BASE_URL) ?>/orders/" class="btn btn-outline-secondary btn-sm">&larr; Back</a>
</div>

<div class="row g-3">
    <!-- Left: Customer & Shipping -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-person me-1"></i> Customer</h6></div>
            <div class="card-body">
                <p class="mb-1"><strong><?= e($order['customer_name']) ?></strong></p>
                <p class="mb-1 small text-muted"><i class="bi bi-envelope me-1"></i> <?= e($order['customer_email']) ?></p>
                <p class="mb-0 small text-muted"><i class="bi bi-telephone me-1"></i> <?= e($order['customer_phone'] ?: 'N/A') ?></p>
            </div>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-geo-alt me-1"></i> Shipping Address</h6></div>
            <div class="card-body">
                <p class="mb-0"><?= e($order['shipping_address']) ?></p>
            </div>
        </div>
    </div>

    <!-- Right: Items & Status -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-box-seam me-1"></i> Ordered Products</h6></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light small text-muted">
                            <tr>
                                <th>Image</th>
                                <th>Product</th>
                                <th>Qty</th>
                                <th>Price</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td>
                                    <?php if ($item['product_image']): ?>
                                        <img src="<?= e(BASE_URL) ?>/uploads/products/<?= e($item['product_image']) ?>" width="40" height="40" class="rounded object-fit-cover">
                                    <?php else: ?>
                                        <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width:40px;height:40px;"><i class="bi bi-image text-muted"></i></div>
                                    <?php endif; ?>
                                </td>
                                <td class="small"><?= e($item['product_name']) ?></td>
                                <td><?= (int)$item['quantity'] ?></td>
                                <td>$<?= e(number_format($item['price'], 2)) ?></td>
                                <td class="fw-semibold">$<?= e(number_format($item['total'], 2)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="4" class="text-end fw-semibold">Grand Total</td>
                                <td class="fw-bold text-primary">$<?= e(number_format($order['total'], 2)) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Payment & Status -->
        <div class="row g-3">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-credit-card me-1"></i> Payment Info</h6></div>
                    <div class="card-body">
                        <p class="mb-2"><span class="text-muted small">Method:</span> <strong><?= e($order['payment_method']) ?></strong></p>
                        <p class="mb-0"><span class="text-muted small">Status:</span>
                            <span class="badge bg-<?= e($payColors[$order['payment_status']] ?? 'secondary') ?>"><?= e(ucfirst($order['payment_status'])) ?></span>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-flag me-1"></i> Order Status</h6></div>
                    <div class="card-body">
                        <p class="mb-2"><span class="text-muted small">Current:</span>
                            <span class="badge bg-<?= e($statusColors[$order['order_status']] ?? 'secondary') ?>"><?= e(ucfirst($order['order_status'])) ?></span>
                        </p>
                        <p class="mb-0 small text-muted">Ordered on <?= e(date('M j, Y g:i A', strtotime($order['created_at']))) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Update Status Form -->
        <div class="card border-0 shadow-sm mt-3">
            <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-pencil-square me-1"></i> Update Status</h6></div>
            <div class="card-body">
                <form method="POST">
                    <?= csrf_field() ?>
                    <div class="row g-3 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label small text-muted">Order Status</label>
                            <select name="order_status" class="form-select">
                                <?php foreach (['pending','processing','shipped','delivered','cancelled'] as $s): ?>
                                    <option value="<?= e($s) ?>" <?= $order['order_status'] === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small text-muted">Payment Status</label>
                            <select name="payment_status" class="form-select">
                                <?php foreach (['pending','paid','failed','refunded'] as $s): ?>
                                    <option value="<?= e($s) ?>" <?= $order['payment_status'] === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Update</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
