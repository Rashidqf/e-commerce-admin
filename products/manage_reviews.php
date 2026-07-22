<?php
/**
 * products/manage_reviews.php
 * Manage customer reviews and ratings
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login();

$pdo = db();
$product_id = (int)($_GET['product_id'] ?? 0);

// Verify product exists
$stmt = $pdo->prepare('SELECT id, name FROM products WHERE id = ? LIMIT 1');
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    set_flash('error', 'Product not found.');
    redirect('/products/');
}

$errors = [];
$success = '';

// Handle review actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid session token.';
    } elseif (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'approve_review') {
            $review_id = (int)($_POST['review_id'] ?? 0);
            
            $stmt = $pdo->prepare('UPDATE product_ratings SET status = "approved" WHERE id = ? AND product_id = ?');
            if ($stmt->execute([$review_id, $product_id])) {
                $success = 'Review approved.';
                update_daily_analytics();
            } else {
                $errors[] = 'Failed to approve review.';
            }
        } elseif ($action === 'reject_review') {
            $review_id = (int)($_POST['review_id'] ?? 0);
            
            $stmt = $pdo->prepare('UPDATE product_ratings SET status = "rejected" WHERE id = ? AND product_id = ?');
            if ($stmt->execute([$review_id, $product_id])) {
                $success = 'Review rejected.';
            } else {
                $errors[] = 'Failed to reject review.';
            }
        } elseif ($action === 'delete_review') {
            $review_id = (int)($_POST['review_id'] ?? 0);
            
            $stmt = $pdo->prepare('DELETE FROM product_ratings WHERE id = ? AND product_id = ?');
            if ($stmt->execute([$review_id, $product_id])) {
                $success = 'Review deleted.';
            } else {
                $errors[] = 'Failed to delete review.';
            }
        }
    }
}

// Get ratings summary
$stmt = $pdo->prepare(
    'SELECT COUNT(*) as total, AVG(rating) as avg_rating, 
            SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
            SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
            SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
            SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
            SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
     FROM product_ratings WHERE product_id = ?'
);
$stmt->execute([$product_id]);
$summary = $stmt->fetch();

// Get all reviews with filters
$status_filter = $_GET['status'] ?? 'all';
$where = 'WHERE pr.product_id = ?';
$params = [$product_id];

if ($status_filter !== 'all') {
    $where .= ' AND pr.status = ?';
    $params[] = $status_filter;
}

$stmt = $pdo->prepare(
    "SELECT pr.*, c.name as customer_name, c.email as customer_email, o.order_number
     FROM product_ratings pr
     LEFT JOIN customers c ON pr.customer_id = c.id
     LEFT JOIN orders o ON pr.order_id = o.id
     $where
     ORDER BY pr.created_at DESC"
);
$stmt->execute($params);
$reviews = $stmt->fetchAll();

// Count by status
$stmt = $pdo->prepare('SELECT status, COUNT(*) as count FROM product_ratings WHERE product_id = ? GROUP BY status');
$stmt->execute([$product_id]);
$status_counts = [];
foreach ($stmt->fetchAll() as $row) {
    $status_counts[$row['status']] = $row['count'];
}

$pageTitle = 'Manage Reviews - ' . e($product['name']);
include __DIR__ . '/../includes/header.php';
?>

<div class="mb-3">
    <a href="<?= e(BASE_URL) ?>/products/edit.php?id=<?= $product_id ?>" class="btn btn-outline-secondary btn-sm">&larr; Back to Product</a>
</div>

<h4 class="mb-4">Customer Reviews: <?= e($product['name']) ?></h4>

<?php if ($errors): ?>
    <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= e($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-0">Average Rating</p>
                        <h3 class="mb-0">
                            <?php if ($summary['avg_rating']): ?>
                                <?= number_format($summary['avg_rating'], 1) ?><small>/5.0</small>
                            <?php else: ?>
                                <small class="text-muted">No ratings</small>
                            <?php endif; ?>
                        </h3>
                    </div>
                    <div class="text-end">
                        <p class="text-muted mb-1">Total Reviews</p>
                        <h4 class="mb-0"><?= $summary['total'] ?? 0 ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <p class="text-muted mb-2"><small>Rating Distribution</small></p>
                <?php for ($i = 5; $i >= 1; $i--): ?>
                    <?php $count = $summary[$i . '_star'] ?? 0; ?>
                    <div class="d-flex align-items-center mb-1">
                        <small style="width: 30px;"><?= $i ?> <i class="bi bi-star-fill text-warning"></i></small>
                        <div class="progress flex-grow-1 mx-2" style="height: 16px;">
                            <div class="progress-bar" style="width: <?= $summary['total'] > 0 ? ($count / $summary['total'] * 100) : 0 ?>%"></div>
                        </div>
                        <small style="width: 30px;" class="text-end"><?= $count ?></small>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
    </div>
</div>

<!-- Filter Tabs -->
<div class="btn-group mb-3" role="group">
    <a href="?product_id=<?= $product_id ?>&status=all" class="btn btn-sm <?= $status_filter === 'all' ? 'btn-primary' : 'btn-outline-primary' ?>">
        All (<?= ($status_counts['pending'] ?? 0) + ($status_counts['approved'] ?? 0) + ($status_counts['rejected'] ?? 0) ?>)
    </a>
    <a href="?product_id=<?= $product_id ?>&status=pending" class="btn btn-sm <?= $status_filter === 'pending' ? 'btn-primary' : 'btn-outline-primary' ?>">
        Pending (<?= $status_counts['pending'] ?? 0 ?>)
    </a>
    <a href="?product_id=<?= $product_id ?>&status=approved" class="btn btn-sm <?= $status_filter === 'approved' ? 'btn-primary' : 'btn-outline-primary' ?>">
        Approved (<?= $status_counts['approved'] ?? 0 ?>)
    </a>
    <a href="?product_id=<?= $product_id ?>&status=rejected" class="btn btn-sm <?= $status_filter === 'rejected' ? 'btn-primary' : 'btn-outline-primary' ?>">
        Rejected (<?= $status_counts['rejected'] ?? 0 ?>)
    </a>
</div>

<!-- Reviews List -->
<?php if ($reviews): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <?php foreach ($reviews as $review): ?>
                <div class="border-bottom pb-3 mb-3" style="<?= $review === end($reviews) ? 'border-bottom: none;' : '' ?>">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h6 class="mb-0">
                                <?php for ($i = 0; $i < 5; $i++): ?>
                                    <i class="bi <?= $i < $review['rating'] ? 'bi-star-fill text-warning' : 'bi-star text-muted' ?>" style="font-size: 14px;"></i>
                                <?php endfor; ?>
                                <span class="ms-2"><?= e($review['title'] ?? 'Review') ?></span>
                            </h6>
                            <small class="text-muted">
                                by <?= e($review['customer_name'] ?? 'Anonymous') ?>
                                <?php if ($review['order_number']): ?>
                                    • Order: <code><?= e($review['order_number']) ?></code>
                                <?php endif; ?>
                            </small>
                        </div>
                        <span class="badge <?php
                            echo match($review['status']) {
                                'approved' => 'bg-success',
                                'rejected' => 'bg-danger',
                                'pending' => 'bg-warning text-dark',
                                default => 'bg-secondary'
                            };
                        ?>"><?= ucfirst($review['status']) ?></span>
                    </div>
                    
                    <?php if ($review['review']): ?>
                        <p class="mb-2"><?= e(substr($review['review'], 0, 200)) ?><?= strlen($review['review']) > 200 ? '...' : '' ?></p>
                    <?php endif; ?>
                    
                    <small class="text-muted d-block mb-2">
                        <?= date('M d, Y H:i', strtotime($review['created_at'])) ?>
                    </small>
                    
                    <?php if ($review['status'] !== 'approved'): ?>
                        <form method="POST" style="display: inline;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="product_id" value="<?= $product_id ?>">
                            <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                            
                            <?php if ($review['status'] === 'pending'): ?>
                                <input type="hidden" name="action" value="approve_review">
                                <button type="submit" class="btn btn-sm btn-outline-success">
                                    <i class="bi bi-check-lg me-1"></i> Approve
                                </button>
                                
                                <input type="hidden" name="action" value="reject_review">
                                <button type="submit" class="btn btn-sm btn-outline-danger" formaction="" onclick="this.form.action = ''; const input = this.previousElementSibling.previousElementSibling.previousElementSibling; input.value = 'reject_review';">
                                    <i class="bi bi-x-lg me-1"></i> Reject
                                </button>
                            <?php endif; ?>
                            
                            <input type="hidden" name="action" value="delete_review">
                            <button type="submit" class="btn btn-sm btn-outline-secondary" onclick="return confirm('Delete this review?');">
                                <i class="bi bi-trash me-1"></i> Delete
                            </button>
                        </form>
                    <?php else: ?>
                        <form method="POST" style="display: inline;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete_review">
                            <input type="hidden" name="product_id" value="<?= $product_id ?>">
                            <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-secondary" onclick="return confirm('Delete this review?');">
                                <i class="bi bi-trash me-1"></i> Delete
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle me-2"></i>
        No reviews found for this filter.
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
