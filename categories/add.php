<?php
/**
 * categories/add.php
 * Add a new category.
 */
require_once __DIR__ . '/../includes/auth.php';
require_login();

$pdo = db();

$errors = [];
$old   = ['title' => '', 'description' => '', 'status' => 'active'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid session token.';
    } else {
        $old    = array_merge($old, $_POST);
        $title  = trim($_POST['title'] ?? '');
        $desc   = trim($_POST['description'] ?? '');
        $status = ($_POST['status'] ?? 'active') === 'active' ? 'active' : 'inactive';

        if ($title === '') $errors[] = 'Category title is required.';

        // Handle image
        $image = null;
        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $image = upload_category_image($_FILES['image'], $errors);
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare('INSERT INTO categories (title, description, image, status) VALUES (?,?,?,?)');
            $stmt->execute([$title, $desc, $image, $status]);
            set_flash('success', 'Category added successfully.');
            redirect('/categories/');
        }
    }
}

function upload_category_image(array $file, array &$errors): ?string
{
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) { $errors[] = 'Only JPG, PNG, WEBP, GIF allowed.'; return null; }
    if ($file['size'] > 2 * 1024 * 1024) { $errors[] = 'File too large (max 2 MB).'; return null; }
    if (!is_dir(CATEGORY_UPLOAD_DIR)) mkdir(CATEGORY_UPLOAD_DIR, 0775, true);
    $name = uniqid('cat_', true) . '.' . $ext;
    if (move_uploaded_file($file['tmp_name'], CATEGORY_UPLOAD_DIR . $name)) return $name;
    $errors[] = 'Failed to upload image.';
    return null;
}

$pageTitle = 'Add Category';
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Add Category</h4>
    <a href="<?= e(BASE_URL) ?>/categories/" class="btn btn-outline-secondary btn-sm">&larr; Back</a>
</div>

<?php if ($errors): ?>
    <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" style="max-width:600px;">
    <?= csrf_field() ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Title <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control" value="<?= e($old['title']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3"><?= e($old['description']) ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Image</label>
                <input type="file" name="image" class="form-control" accept="image/*">
            </div>
            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="active" <?= $old['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $old['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
        </div>
    </div>
    <div class="mt-3">
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Save Category</button>
        <a href="<?= e(BASE_URL) ?>/categories/" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>
