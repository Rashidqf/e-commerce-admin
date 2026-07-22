<?php
/**
 * products/manage_variants.php
 * Manage product variants and attributes
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login();

$pdo = db();
$product_id = (int)($_GET['product_id'] ?? $_POST['product_id'] ?? 0);

// Verify product exists
$stmt = $pdo->prepare('SELECT id, name, price, quantity FROM products WHERE id = ? LIMIT 1');
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    set_flash('error', 'Product not found.');
    redirect('/products/');
}

$errors = [];
$success = '';

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid session token.';
    } elseif (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'add_variant') {
            $name = trim($_POST['variant_name'] ?? '');
            $sku = trim($_POST['variant_sku'] ?? '');
            $price = (float)($_POST['variant_price'] ?? 0);
            $sale_price = $_POST['variant_sale_price'] !== '' ? (float)$_POST['variant_sale_price'] : null;
            $quantity = (int)($_POST['variant_quantity'] ?? 0);
            $attributes_json = trim($_POST['attributes_json'] ?? '{}');
            
            if ($name === '') {
                $errors[] = 'Variant name is required.';
            } elseif ($sku === '') {
                $errors[] = 'Variant SKU is required.';
            } elseif ($price <= 0) {
                $errors[] = 'Price must be greater than zero.';
            } else {
                // Check SKU uniqueness
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM product_variants WHERE sku = ?');
                $stmt->execute([$sku]);
                if ($stmt->fetchColumn() > 0) {
                    $errors[] = 'SKU already exists for another variant.';
                } else {
                    try {
                        $json_decode = json_decode($attributes_json, true, 2, JSON_THROW_ON_ERROR);
                        
                        $stmt = $pdo->prepare(
                            'INSERT INTO product_variants (product_id, name, sku, price, sale_price, quantity, attributes, status)
                             VALUES (?, ?, ?, ?, ?, ?, ?, "active")'
                        );
                        $stmt->execute([$product_id, $name, $sku, $price, $sale_price, $quantity, $attributes_json]);
                        $success = 'Variant added successfully.';
                        
                        // Log inventory
                        $variant_id = (int)$pdo->lastInsertId();
                        log_inventory_movement($product_id, $quantity, 'variant_created', $variant_id, "Variant: $name");
                    } catch (Exception $e) {
                        $errors[] = 'Invalid JSON format for attributes.';
                    }
                }
            }
        } elseif ($action === 'delete_variant') {
            $variant_id = (int)($_POST['variant_id'] ?? 0);
            
            // Verify variant belongs to this product
            $stmt = $pdo->prepare('SELECT quantity FROM product_variants WHERE id = ? AND product_id = ?');
            $stmt->execute([$variant_id, $product_id]);
            $variant = $stmt->fetch();
            
            if (!$variant) {
                $errors[] = 'Variant not found.';
            } else {
                $stmt = $pdo->prepare('DELETE FROM product_variants WHERE id = ?');
                if ($stmt->execute([$variant_id])) {
                    log_inventory_movement($product_id, -$variant['quantity'], 'variant_deleted', $variant_id);
                    $success = 'Variant deleted successfully.';
                } else {
                    $errors[] = 'Failed to delete variant.';
                }
            }
        }
    }
}

// Get variants
$stmt = $pdo->prepare('SELECT * FROM product_variants WHERE product_id = ? ORDER BY created_at ASC');
$stmt->execute([$product_id]);
$variants = $stmt->fetchAll();

// Get attributes
$stmt = $pdo->prepare('SELECT * FROM product_attributes WHERE product_id = ?');
$stmt->execute([$product_id]);
$attributes = $stmt->fetchAll();

$pageTitle = 'Manage Variants - ' . e($product['name']);
include __DIR__ . '/../includes/header.php';
?>

<div class="mb-3">
    <a href="<?= e(BASE_URL) ?>/products/edit.php?id=<?= $product_id ?>" class="btn btn-outline-secondary btn-sm">&larr; Back to Product</a>
</div>

<h4 class="mb-4">Product Variants & Attributes: <?= e($product['name']) ?></h4>

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
            <div class="card-header bg-white"><h6 class="mb-0">Add New Variant</h6></div>
            <div class="card-body">
                <form method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add_variant">
                    <input type="hidden" name="product_id" value="<?= $product_id ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Variant Name <span class="text-danger">*</span></label>
                        <input type="text" name="variant_name" class="form-control" placeholder="e.g., Large Red, Medium Blue" required>
                        <small class="text-muted">Describe the variant (size, color, material)</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">SKU <span class="text-danger">*</span></label>
                        <input type="text" name="variant_sku" class="form-control" placeholder="e.g., PROD-001-RED" required>
                        <small class="text-muted">Must be unique</small>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Price <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="variant_price" class="form-control" value="<?= $product['price'] ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Sale Price</label>
                            <input type="number" step="0.01" min="0" name="variant_sale_price" class="form-control">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Stock Quantity</label>
                        <input type="number" min="0" name="variant_quantity" class="form-control" value="0">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Attributes (JSON Format)</label>
                        <textarea name="attributes_json" class="form-control" rows="4" placeholder='{"size": "Large", "color": "Red"}'>{}</textarea>
                        <small class="text-muted">Enter JSON format attributes for this variant</small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i> Add Variant
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white"><h6 class="mb-0">Variant Guidelines</h6></div>
            <div class="card-body small">
                <ul class="mb-0">
                    <li>Variants represent different versions of the same product</li>
                    <li>Each variant has its own SKU, price, and inventory</li>
                    <li>Use JSON format for attributes: <code>{"size":"L","color":"Red"}</code></li>
                    <li>Variants allow customers to choose options</li>
                    <li>Useful for size/color/material variations</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php if ($variants): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <h6 class="mb-0">Current Variants (<?= count($variants) ?>)</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>SKU</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Attributes</th>
                            <th style="width: 100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($variants as $variant): ?>
                            <tr>
                                <td><?= e($variant['name']) ?></td>
                                <td><code><?= e($variant['sku']) ?></code></td>
                                <td>
                                    <?php if ($variant['sale_price']): ?>
                                        <del>$<?= number_format($variant['price'], 2) ?></del>
                                        <strong>$<?= number_format($variant['sale_price'], 2) ?></strong>
                                    <?php else: ?>
                                        $<?= number_format($variant['price'], 2) ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= $variant['quantity'] > 0 ? 'bg-success' : 'bg-danger' ?>">
                                        <?= $variant['quantity'] ?>
                                    </span>
                                </td>
                                <td>
                                    <small><?= e(substr($variant['attributes'], 0, 30)) ?>...</small>
                                </td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete_variant">
                                        <input type="hidden" name="product_id" value="<?= $product_id ?>">
                                        <input type="hidden" name="variant_id" value="<?= $variant['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this variant?');">
                                            <i class="bi bi-trash me-1"></i> Delete
                                        </button>
                                    </form>
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
        No variants added yet. Create your first variant to offer product options to customers.
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
