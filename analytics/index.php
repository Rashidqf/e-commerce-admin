<?php
/**
 * analytics/index.php
 * Customer and sales analytics dashboard
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login();

$pdo = db();

// Get dashboard statistics
$stats = get_dashboard_stats();

// Get top selling products
$top_products = get_top_selling_products(10, 'month');

// Get daily sales data for chart
$stmt = $pdo->query(
    'SELECT date, total_orders, total_revenue, total_items_sold, avg_order_value
     FROM analytics_daily_sales
     WHERE date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
     ORDER BY date ASC'
);
$daily_data = $stmt->fetchAll();

// Get customer segments
$stmt = $pdo->query(
    'SELECT customer_type, COUNT(*) as count FROM analytics_customer_segments GROUP BY customer_type'
);
$segments = [];
foreach ($stmt->fetchAll() as $row) {
    $segments[$row['customer_type']] = $row['count'];
}

// Get low stock products
$low_stock = get_low_stock_products(10);

$pageTitle = 'Analytics & Reports';
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4>Analytics & Reports</h4>
    <a href="<?= e(BASE_URL) ?>/dashboard.php" class="btn btn-outline-secondary btn-sm">&larr; Back to Dashboard</a>
</div>

<!-- Key Metrics -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="text-muted mb-0">Total Revenue</h6>
                <h3 class="mb-0">$<?= number_format($stats['total_revenue'], 2) ?></h3>
                <small class="text-success">Today: $<?= number_format($stats['todays_revenue'], 2) ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="text-muted mb-0">Total Orders</h6>
                <h3 class="mb-0"><?= number_format($stats['total_orders']) ?></h3>
                <small class="text-info">This month: <?= number_format($stats['month_orders']) ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="text-muted mb-0">Total Customers</h6>
                <h3 class="mb-0"><?= number_format($stats['total_customers']) ?></h3>
                <small class="text-muted">Registered customers</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="text-muted mb-0">Active Products</h6>
                <h3 class="mb-0"><?= number_format($stats['total_products']) ?></h3>
                <small class="text-warning"><?= number_format($stats['low_stock_count']) ?> low stock</small>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- Daily Sales Chart -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0">Sales Overview (Last 30 Days)</h6>
            </div>
            <div class="card-body">
                <?php if ($daily_data): ?>
                    <canvas id="salesChart" height="80"></canvas>
                    <script>
                        const ctx = document.getElementById('salesChart').getContext('2d');
                        const dates = <?= json_encode(array_column($daily_data, 'date')) ?>;
                        const revenue = <?= json_encode(array_map(function($d) { return (float)$d['total_revenue']; }, $daily_data)) ?>;
                        const orders = <?= json_encode(array_map(function($d) { return (int)$d['total_orders']; }, $daily_data)) ?>;
                        
                        new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: dates,
                                datasets: [
                                    {
                                        label: 'Revenue ($)',
                                        data: revenue,
                                        borderColor: '#28a745',
                                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                                        tension: 0.3,
                                        yAxisID: 'y'
                                    },
                                    {
                                        label: 'Orders',
                                        data: orders,
                                        borderColor: '#007bff',
                                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                                        tension: 0.3,
                                        yAxisID: 'y1'
                                    }
                                ]
                            },
                            options: {
                                responsive: true,
                                interaction: { mode: 'index', intersect: false },
                                scales: {
                                    y: {
                                        type: 'linear',
                                        display: true,
                                        position: 'left',
                                        title: { display: true, text: 'Revenue ($)' }
                                    },
                                    y1: {
                                        type: 'linear',
                                        display: true,
                                        position: 'right',
                                        title: { display: true, text: 'Orders' },
                                        grid: { drawOnChartArea: false }
                                    }
                                }
                            }
                        });
                    </script>
                <?php else: ?>
                    <p class="text-muted mb-0">No sales data available yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Customer Segments -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0">Customer Segments</h6>
            </div>
            <div class="card-body">
                <?php if ($segments): ?>
                    <canvas id="segmentChart" height="200"></canvas>
                    <script>
                        const segmentCtx = document.getElementById('segmentChart').getContext('2d');
                        const labels = <?= json_encode(array_map(fn($k) => ucfirst($k), array_keys($segments))) ?>;
                        const data = <?= json_encode(array_values($segments)) ?>;
                        
                        new Chart(segmentCtx, {
                            type: 'doughnut',
                            data: {
                                labels: labels,
                                datasets: [{
                                    data: data,
                                    backgroundColor: ['#28a745', '#17a2b8', '#ffc107', '#6c757d']
                                }]
                            },
                            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
                        });
                    </script>
                <?php else: ?>
                    <p class="text-muted mb-0">No customer data available yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Top Selling Products -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0">Top Selling Products (This Month)</h6>
            </div>
            <div class="card-body">
                <?php if ($top_products): ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th>Units Sold</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_products as $product): ?>
                                    <tr>
                                        <td>
                                            <a href="<?= e(BASE_URL) ?>/products/edit.php?id=<?= $product['id'] ?>">
                                                <?= e($product['name']) ?>
                                            </a>
                                        </td>
                                        <td><code><?= e($product['sku']) ?></code></td>
                                        <td><?= number_format($product['total_sold']) ?></td>
                                        <td>$<?= number_format($product['total_revenue'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">No sales data available yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Low Stock Alerts -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0">
                    <i class="bi bi-exclamation-triangle-fill text-warning me-1"></i>
                    Low Stock Products
                </h6>
            </div>
            <div class="card-body">
                <?php if ($low_stock): ?>
                    <div class="list-group list-group-sm">
                        <?php foreach (array_slice($low_stock, 0, 5) as $product): ?>
                            <a href="<?= e(BASE_URL) ?>/products/manage_inventory.php?product_id=<?= $product['id'] ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?= e($product['name']) ?></strong>
                                        <br><small class="text-muted"><?= e($product['sku']) ?></small>
                                    </div>
                                    <span class="badge bg-danger"><?= number_format($product['quantity']) ?></span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($low_stock) > 5): ?>
                        <small class="text-muted d-block mt-2">
                            +<?= count($low_stock) - 5 ?> more products with low stock
                        </small>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-success mb-0"><i class="bi bi-check-circle me-1"></i> All products well stocked!</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js dependency -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1"></script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
