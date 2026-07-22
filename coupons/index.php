<?php
/**
 * coupons/index.php
 * Manage coupon codes and discounts
 */
require_once __DIR__ . '/../includes/auth.php';
require_login();

$pdo = db();

$errors = [];
$success = '';

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid session token.';
    } elseif (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'add_coupon') {
            $code = strtoupper(trim($_POST['code'] ?? ''));
            $type = $_POST['type'] ?? '';
            $value = (float)($_POST['value'] ?? 0);
            $max_uses = $_POST['max_uses'] !== '' ? (int)$_POST['max_uses'] : null;
            $min_amount = $_POST['min_amount'] !== '' ? (float)$_POST['min_amount'] : null;
            $expiry_date = $_POST['expiry_date'] !== '' ? $_POST['expiry_date'] . ' 23:59:59' : null;
            
            if ($code === '') {
                $errors[] = 'Coupon code is required.';
            } elseif (!in_array($type, ['fixed', 'percentage'])) {
                $errors[] = 'Invalid discount type.';
            } elseif ($value <= 0) {
                $errors[] = 'Discount value must be greater than zero.';
            } else {
                // Check code uniqueness
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM coupon_codes WHERE code = ?');
                $stmt->execute([$code]);
                if ($stmt->fetchColumn() > 0) {
                    $errors[] = 'This coupon code already exists.';
                } else {
                    $stmt = $pdo->prepare(
                        'INSERT INTO coupon_codes (code, type, value, max_uses, min_amount, expiry_date, status)
                         VALUES (?, ?, ?, ?, ?, ?, "active")'
                    );
                    if ($stmt->execute([$code, $type, $value, $max_uses, $min_amount, $expiry_date])) {
                        $success = 'Coupon created successfully.';
                    } else {
                        $errors[] = 'Failed to create coupon.';
                    }
                }
            }
        } elseif ($action === 'update_coupon') {
            $coupon_id = (int)($_POST['coupon_id'] ?? 0);
            $status = $_POST['status'] ?? 'active';
            
            $stmt = $pdo->prepare('UPDATE coupon_codes SET status = ? WHERE id = ?');
            if ($stmt->execute([$status, $coupon_id])) {
                $success = 'Coupon updated.';
            } else {
                $errors[] = 'Failed to update coupon.';
            }
        } elseif ($action === 'delete_coupon') {
            $coupon_id = (int)($_POST['coupon_id'] ?? 0);
            
            $stmt = $pdo->prepare('DELETE FROM coupon_codes WHERE id = ?');
            if ($stmt->execute([$coupon_id])) {
                $success = 'Coupon deleted.';
            } else {
                $errors[] = 'Failed to delete coupon.';
            }
        }
    }
}

// Get coupons
$stmt = $pdo->query(
    'SELECT * FROM coupon_codes ORDER BY status DESC, created_at DESC'
);
$coupons = $stmt->fetchAll();

$pageTitle = 'Manage Coupons';
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Coupon Codes</h4>
    <a href="<?= e(BASE_URL) ?>/dashboard.php" class="btn btn-outline-secondary btn-sm">&larr; Dashboard</a>
</div>

<?php if ($errors): ?>
    <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= e($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white"><h6 class="mb-0">Create New Coupon</h6></div>
            <div class="card-body">
                <form method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add_coupon">
                    
                    <div class="mb-3">
                        <label class="form-label">Coupon Code <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control" placeholder="e.g., SUMMER20" required>
                        <small class="text-muted">Will be converted to uppercase</small>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Type <span class="text-danger">*</span></label>
                            <select name="type" class="form-select" required>
                                <option value="">— Select —</option>
                                <option value="fixed">Fixed Amount ($)</option>
                                <option value="percentage">Percentage (%)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Discount Value <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="value" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Min. Order Amount ($)</label>
                        <input type="number" step="0.01" min="0" name="min_amount" class="form-control">
                        <small class="text-muted">Leave empty for no minimum</small>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Max Uses</label>
                            <input type="number" min="1" name="max_uses" class="form-control">
                            <small class="text-muted">Leave empty for unlimited</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Expiry Date</label>
                            <input type="date" name="expiry_date" class="form-control">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-plus-lg me-1"></i> Create Coupon
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white"><h6 class="mb-0">Active Coupons (<?= count($coupons) ?>)</h6></div>
            <div class="card-body">
                <?php if ($coupons): ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Code</th>
                                    <th>Discount</th>
                                    <th>Usage</th>
                                    <th>Expires</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($coupons as $coupon): ?>
                                    <tr>
                                        <td><strong><?= e($coupon['code']) ?></strong></td>
                                        <td>
                                            <?php if ($coupon['type'] === 'percentage'): ?>
                                                <?= number_format($coupon['value']) ?>%
                                            <?php else: ?>
                                                $<?= number_format($coupon['value'], 2) ?>
                                            <?php endif; ?>
                                            <?php if ($coupon['min_amount']): ?>
                                                <br><small class="text-muted">Min: $<?= number_format($coupon['min_amount'], 2) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small>
                                                <?= number_format($coupon['used_count']) ?>
                                                <?php if ($coupon['max_uses']): ?>
                                                    / <?= number_format($coupon['max_uses']) ?>
                                                <?php else: ?>
                                                    / ∞
                                                <?php endif; ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?php if ($coupon['expiry_date']): ?>
                                                <small><?= date('M d, Y', strtotime($coupon['expiry_date'])) ?></small>
                                            <?php else: ?>
                                                <small class="text-muted">—</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge <?= $coupon['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>">
                                                <?= ucfirst($coupon['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="update_coupon">
                                                <input type="hidden" name="coupon_id" value="<?= $coupon['id'] ?>">
                                                <input type="hidden" name="status" value="<?= $coupon['status'] === 'active' ? 'inactive' : 'active' ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-secondary">
                                                    <?= $coupon['status'] === 'active' ? 'Disable' : 'Enable' ?>
                                                </button>
                                            </form>
                                            
                                            <form method="POST" style="display: inline;">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="delete_coupon">
                                                <input type="hidden" name="coupon_id" value="<?= $coupon['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this coupon?');">
                                                    Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">No coupons created yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
