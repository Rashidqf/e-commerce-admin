<?php
/**
 * products/manage_videos.php
 * Manage product videos (add, edit, delete)
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/video_utilities.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login();

$pdo = db();
$product_id = (int)($_GET['product_id'] ?? $_POST['product_id'] ?? 0);

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

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid session token.';
    } elseif (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'add_video') {
            $url = trim($_POST['url'] ?? '');
            $title = trim($_POST['title'] ?? '');
            $sort_order = (int)($_POST['sort_order'] ?? 0);
            
            if ($url === '') {
                $errors[] = 'Video URL is required.';
            } elseif (!is_valid_video_url($url)) {
                $errors[] = 'Invalid video URL. Please provide a valid YouTube, Facebook, or TikTok video URL.';
            } else {
                $detection = detect_video_platform($url);
                
                $stmt = $pdo->prepare(
                    'INSERT INTO product_videos (product_id, url, platform, title, sort_order) VALUES (?, ?, ?, ?, ?)'
                );
                if ($stmt->execute([$product_id, $url, $detection['platform'], $title ?: null, $sort_order])) {
                    $success = 'Video added successfully.';
                } else {
                    $errors[] = 'Failed to add video.';
                }
            }
        } elseif ($action === 'delete_video') {
            $video_id = (int)($_POST['video_id'] ?? 0);
            
            // Verify video belongs to this product
            $stmt = $pdo->prepare('SELECT id FROM product_videos WHERE id = ? AND product_id = ? LIMIT 1');
            $stmt->execute([$video_id, $product_id]);
            
            if (!$stmt->fetch()) {
                $errors[] = 'Video not found.';
            } else {
                $stmt = $pdo->prepare('DELETE FROM product_videos WHERE id = ?');
                if ($stmt->execute([$video_id])) {
                    $success = 'Video deleted successfully.';
                } else {
                    $errors[] = 'Failed to delete video.';
                }
            }
        } elseif ($action === 'update_order') {
            $video_ids = $_POST['video_ids'] ?? [];
            $sort_orders = $_POST['sort_orders'] ?? [];
            
            foreach ($video_ids as $idx => $vid) {
                $vid = (int)$vid;
                $order = (int)($sort_orders[$idx] ?? 0);
                
                $stmt = $pdo->prepare('UPDATE product_videos SET sort_order = ? WHERE id = ? AND product_id = ?');
                $stmt->execute([$order, $vid, $product_id]);
            }
            $success = 'Video order updated.';
        }
    }
}

// Get videos
$stmt = $pdo->prepare('SELECT * FROM product_videos WHERE product_id = ? ORDER BY sort_order ASC, created_at ASC');
$stmt->execute([$product_id]);
$videos = $stmt->fetchAll();

$pageTitle = 'Manage Videos - ' . e($product['name']);
include __DIR__ . '/../includes/header.php';
?>

<div class="mb-3">
    <a href="<?= e(BASE_URL) ?>/products/edit.php?id=<?= $product_id ?>" class="btn btn-outline-secondary btn-sm">&larr; Back to Product</a>
</div>

<h4 class="mb-4">Manage Videos: <?= e($product['name']) ?></h4>

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
            <div class="card-header bg-white"><h6 class="mb-0">Add New Video</h6></div>
            <div class="card-body">
                <form method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add_video">
                    <input type="hidden" name="product_id" value="<?= $product_id ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Video URL <span class="text-danger">*</span></label>
                        <input type="text" name="url" class="form-control" placeholder="https://youtube.com/watch?v=..." required>
                        <small class="text-muted d-block mt-2">
                            Supported platforms: YouTube, Facebook Video, TikTok
                        </small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Video Title</label>
                        <input type="text" name="title" class="form-control" placeholder="Optional: Title for this video">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Display Order</label>
                        <input type="number" name="sort_order" class="form-control" value="0" min="0">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i> Add Video
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white"><h6 class="mb-0">Video Guidelines</h6></div>
            <div class="card-body small">
                <ul class="mb-0">
                    <li><strong>YouTube:</strong> Full URL or youtu.be shortened link</li>
                    <li><strong>Facebook:</strong> Video permalink from facebook.com</li>
                    <li><strong>TikTok:</strong> Video link from tiktok.com</li>
                    <li>Videos will be embedded directly on the product page</li>
                    <li>You can add up to 5 videos per product</li>
                    <li>Videos are displayed in order by sort order</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php if ($videos): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <h6 class="mb-0">Current Videos (<?= count($videos) ?>/5)</h6>
        </div>
        <div class="card-body">
            <?php if (count($videos) >= 5): ?>
                <div class="alert alert-info mb-3">
                    <i class="bi bi-info-circle me-2"></i>
                    You've reached the maximum of 5 videos. Delete a video to add another.
                </div>
            <?php endif; ?>
            
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;">Order</th>
                            <th>Platform</th>
                            <th>Title</th>
                            <th>Preview</th>
                            <th style="width: 100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($videos as $video): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-light text-dark"><?= $video['sort_order'] ?></span>
                                </td>
                                <td>
                                    <i class="bi <?= e(get_platform_icon($video['platform'])) ?> me-1"></i>
                                    <?= e(get_platform_name($video['platform'])) ?>
                                </td>
                                <td><?= $video['title'] ? e($video['title']) : '<em class="text-muted">No title</em>' ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#videoPreview<?= $video['id'] ?>">
                                        <i class="bi bi-eye me-1"></i> Preview
                                    </button>
                                </td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete_video">
                                        <input type="hidden" name="product_id" value="<?= $product_id ?>">
                                        <input type="hidden" name="video_id" value="<?= $video['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this video?');">
                                            <i class="bi bi-trash me-1"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            
                            <!-- Video Preview Modal -->
                            <div class="modal fade" id="videoPreview<?= $video['id'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h6 class="modal-title">
                                                <i class="bi <?= e(get_platform_icon($video['platform'])) ?> me-1"></i>
                                                <?= e($video['title'] ?? 'Video Preview') ?>
                                            </h6>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="ratio ratio-16x9">
                                                <?= generate_video_embed($video['url'], $video['platform'], ['width' => '100%', 'height' => '100%', 'class' => 'w-100']) ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle me-2"></i>
        No videos added yet. Add your first video using the form above.
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
