<?php
/**
 * sidebar.php
 * Reusable sidebar navigation.
 * Highlights the active link based on the current request URI.
 */

$nav = [
    ['Dashboard',  'dashboard.php', 'bi-speedometer2'],
    ['Products',   'products/',     'bi-box-seam'],
    ['Categories', 'categories/',   'bi-tags'],
    ['Orders',     'orders/',       'bi-receipt'],
    ['Customers',  'customers/',    'bi-people'],
    ['Coupons',    'coupons/',      'bi-percent'],
    ['Analytics',  'analytics/',    'bi-graph-up'],
    ['Profile',    'profile/',       'bi-person-circle'],
];

// Determine the active section from the request URI
$uri    = $_SERVER['REQUEST_URI'] ?? '';
$base   = BASE_URL;
$stripped = str_replace($base, '', $uri);
$parts  = explode('/', trim($stripped, '/'));
$activeKey = $parts[0] ?? '';
?>
<!-- Sidebar -->
<aside class="sidebar bg-dark text-white" id="sidebar">
    <div class="sidebar-header p-4 text-center border-bottom border-secondary">
        <h5 class="mb-0"><i class="bi bi-shop"></i> <?= e(SITE_NAME) ?></h5>
        <small class="text-secondary">Admin Panel</small>
    </div>
    <nav class="nav flex-column p-3">
        <?php foreach ($nav as $item): ?>
            <?php
                $linkKey = rtrim(str_replace('.php', '', $item[1]), '/');
                $isActive = ($activeKey === $linkKey);
            ?>
            <a href="<?= e(BASE_URL) ?>/<?= e($item[1]) ?>"
               class="nav-link text-white-50 <?= $isActive ? 'active-link' : '' ?>">
                <i class="bi <?= e($item[2]) ?> me-2"></i> <?= e($item[0]) ?>
            </a>
        <?php endforeach; ?>
    </nav>
</aside>
