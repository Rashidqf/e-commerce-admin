<?php
/**
 * products/manage_inventory.php
 * Inventory management, tracking, and audit trail
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login();

$pdo = db();
$product_id = (int)($_GET['product_id'] ?? $_POST['product_id'] ?? 0);

// Verify product exists
$stmt = $pdo->prepare('SELECT id, name, quantity FROM products WHERE id = ? LIMIT 1');
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    set_flash('error', 'Product not found.');
    redirect('/products/');
}

$errors = [];
$success = '';

// Handle inventory adjustment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid session token.';
    } else {
        $new_quantity = (int)($_POST['new_quantity'] ?? -1);
        $reason = trim($_POST['reason'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        
        if ($new_quantity < 0) {
            $errors[] = 'Please enter a valid quantity.';
        } elseif ($reason === '') {
            $errors[] = 'Reason for adjustment is required.';
        } else {
            if (update_product_inventory($product_id, $new_quantity, $reason)) {
                if ($notes) {
                    $stmt = $pdo->prepare('UPDATE inventory_logs SET notes = ? WHERE product_id = ? ORDER BY id DESC LIMIT 1');
                    $stmt->execute([$notes, $product_id]);
                }
                
                $success = 'Inventory adjusted successfully.';
                
                // Refresh product data
                $stmt = $pdo->prepare('SELECT quantity FROM products WHERE id = ?');
                $stmt->execute([$product_id]);
                $product = $stmt->fetch();
            } else {
                $errors[] = 'Failed to update inventory.';
            }
        }
    }
}

// Get inventory history
$stmt = $pdo->prepare(
    'SELECT * FROM inventory_logs 
     WHERE product_id = ? 
     ORDER BY created_at DESC 
     LIMIT 50'
);
$stmt->execute([$product_id]);
$logs = $stmt->fetchAll();

// Get variant inventory
$stmt = $pdo->prepare(
    'SELECT id, name, sku, quantity, price FROM product_variants 
     WHERE product_id = ? AND status = "active" 
     ORDER BY created_at ASC'
);
$stmt->execute([$product_id]);
$variants = $stmt->fetchAll();

$pageTitle = 'Manage Inventory - ' . e($product['name']);
include __DIR__ . '/../includes/header.php';
?>

<div class="mb-3">
    <a href="<?= e(BASE_URL) ?>/products/edit.php?id=<?= $product_id ?>" class="btn btn-outline-secondary btn-sm">&larr; Back to Product</a>
</div>

<h4 class="mb-4">Inventory Management: <?= e($product['name']) ?></h4>

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
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white"><h6 class="mb-0">Current Stock</h6></div>
            <div class="card-body">
                <div class="mb-4">
                    <label class="form-label">Main Product Stock</label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-light"><strong><?= number_format($product['quantity']) ?></strong></span>
                        <span class="input-group-text">units</span>
                    </div>
                </div>
                
                <?php if ($variants): ?>
                    <hr>
                    <label class="form-label">Variant Stock</label>
                    <div class="list-group list-group-sm">
                        <?php foreach ($variants as $variant): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?= e($variant['name']) ?></strong>
                                    <br><small class="text-muted"><?= e($variant['sku']) ?></small>
                                </div>
                                <span class="badge <?= $variant['quantity'] > 0 ? 'bg-success' : 'bg-danger' ?> rounded-pill">
                                    <?= number_format($variant['quantity']) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white"><h6 class="mb-0">Adjust Inventory</h6></div>
            <div class="card-body">
                <form method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="product_id" value="<?= $product_id ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">New Quantity <span class="text-danger">*</span></label>
                        <input type="number" name="new_quantity" class="form-control" value="<?= $product['quantity'] ?>" min="0" required>
                        <small class="text-muted">Enter the new total quantity</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Reason <span class="text-danger">*</span></label>
                        <select name="reason" class="form-select" required>
                            <option value="">— Select Reason —</option>
                            <option value="stock_count">Physical Stock Count</option>
                            <option value="restock">Restock/Purchase</option>
                            <option value="return">Customer Return</option>
                            <option value="damage">Damaged/Defective</option>
                            <option value="loss">Loss/Theft</option>
                            <option value="adjustment">Manual Adjustment</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Optional notes about this adjustment..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Update Inventory
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if ($logs): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <h6 class="mb-0">Inventory History (Last 50 Entries)</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Reason</th>
                            <th>Quantity Change</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td>
                                    <small><?= date('M d, Y H:i', strtotime($log['created_at'])) ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark"><?= e(str_replace('_', ' ', ucfirst($log['reason']))) ?></span>
                                </td>
                                <td>
                                    <span class="<?= $log['quantity_change'] > 0 ? 'text-success' : 'text-danger' ?>">
                                        <?= $log['quantity_change'] > 0 ? '+' : '' ?><?= number_format($log['quantity_change']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($log['notes']): ?>
                                        <small><?= e($log['notes']) ?></small>
                                    <?php else: ?>
                                        <em class="text-muted">—</em>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle me-2"></i>
        No inventory history available yet. Changes will appear here.
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
