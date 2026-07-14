<?php
/**
 * dashboard.php
 * Overview: totals for products, categories, customers, orders,
 * total revenue, recent orders, and latest products.
 */
require_once __DIR__ . '/includes/auth.php';
require_login();

$pdo = db();

// Totals
$totalProducts    = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
$totalCategories  = (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
$totalCustomers   = (int) $pdo->query('SELECT COUNT(*) FROM customers')->fetchColumn();
$totalOrders      = (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();

// Total revenue (sum of paid orders)
$stmt = $pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE payment_status = 'paid'");
$totalRevenue = (float) $stmt->fetchColumn();

// Recent orders (latest 5)
$recentOrders = $pdo->query(
    "SELECT o.id, o.order_number, o.total, o.order_status, o.payment_status, o.created_at,
            c.name AS customer_name
     FROM orders o
     JOIN customers c ON o.customer_id = c.id
     ORDER BY o.created_at DESC
     LIMIT 5"
)->fetchAll();

// Latest products (latest 5)
$latestProducts = $pdo->query(
    "SELECT id, name, sku, price, sale_price, main_image, status
     FROM products
     ORDER BY created_at DESC
     LIMIT 5"
)->fetchAll();

$statCards = [
    ['Total Products',   $totalProducts,   'bi-box-seam',   'primary'],
    ['Total Categories', $totalCategories, 'bi-tags',       'success'],
    ['Total Customers',  $totalCustomers,  'bi-people',     'info'],
    ['Total Orders',     $totalOrders,     'bi-receipt',    'warning'],
    ['Total Revenue',    '$' . number_format($totalRevenue, 2), 'bi-cash-coin', 'danger'],
];

$pageTitle = 'Dashboard';
include __DIR__ . '/includes/header.php';
?>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <?php foreach ($statCards as $card): ?>
        <div class="col-6 col-lg-3 col-xl-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-3 bg-<?= e($card[3]) ?> bg-opacity-10 p-3 me-3">
                        <i class="bi <?= e($card[2]) ?> fs-3 text-<?= e($card[3]) ?>"></i>
                    </div>
                    <div>
                        <div class="text-muted small"><?= e($card[0]) ?></div>
                        <div class="fs-5 fw-bold"><?= e($card[1]) ?></div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-3">
    <!-- Recent Orders -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-receipt me-1"></i> Recent Orders</h6>
                <a href="<?= e(BASE_URL) ?>/orders/" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light small text-muted">
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($recentOrders)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">No orders yet.</td></tr>
                        <?php else: foreach ($recentOrders as $o): ?>
                            <tr>
                                <td><a href="<?= e(BASE_URL) ?>/orders/view.php?id=<?= (int)$o['id'] ?>" class="text-decoration-none fw-semibold"><?= e($o['order_number']) ?></a></td>
                                <td><?= e($o['customer_name']) ?></td>
                                <td>$<?= e(number_format($o['total'], 2)) ?></td>
                                <td><span class="badge bg-<?= e($o['order_status'] === 'delivered' ? 'success' : ($o['order_status'] === 'cancelled' ? 'danger' : 'warning')) ?>"><?= e(ucfirst($o['order_status'])) ?></span></td>
                                <td class="small text-muted"><?= e(date('M j, Y', strtotime($o['created_at']))) ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Latest Products -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-box-seam me-1"></i> Latest Products</h6>
                <a href="<?= e(BASE_URL) ?>/products/" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light small text-muted">
                            <tr>
                                <th>Product</th>
                                <th>SKU</th>
                                <th>Price</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($latestProducts)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">No products yet.</td></tr>
                        <?php else: foreach ($latestProducts as $p): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if ($p['main_image']): ?>
                                            <img src="<?= e(BASE_URL) ?>/uploads/products/<?= e($p['main_image']) ?>" width="36" height="36" class="rounded me-2 object-fit-cover">
                                        <?php else: ?>
                                            <div class="bg-light rounded me-2 d-flex align-items-center justify-content-center" style="width:36px;height:36px;"><i class="bi bi-image text-muted"></i></div>
                                        <?php endif; ?>
                                        <span class="small"><?= e($p['name']) ?></span>
                                    </div>
                                </td>
                                <td class="small"><?= e($p['sku']) ?></td>
                                <td class="small">
                                    <?php if ($p['sale_price']): ?>
                                        <span class="text-danger">$<?= e(number_format($p['sale_price'], 2)) ?></span>
                                        <del class="text-muted">$<?= e(number_format($p['price'], 2)) ?></del>
                                    <?php else: ?>
                                        $<?= e(number_format($p['price'], 2)) ?>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-<?= e($p['status'] === 'active' ? 'success' : 'secondary') ?>"><?= e(ucfirst($p['status'])) ?></span></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
