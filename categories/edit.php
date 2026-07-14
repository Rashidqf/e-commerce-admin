<?php
/**
 * categories/edit.php
 * Edit an existing category.
 */
require_once __DIR__ . '/../includes/auth.php';
require_login();

$pdo = db();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { set_flash('danger', 'Invalid category ID.'); redirect('/categories/'); }

$stmt = $pdo->prepare('SELECT * FROM categories WHERE id = ?');
$stmt->execute([$id]);
$category = $stmt->fetch();
if (!$category) { set_flash('danger', 'Category not found.'); redirect('/categories/'); }

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid session token.';
    } else {
        $title  = trim($_POST['title'] ?? '');
        $desc   = trim($_POST['description'] ?? '');
        $status = ($_POST['status'] ?? 'active') === 'active' ? 'active' : 'inactive';

        if ($title === '') $errors[] = 'Category title is required.';

        $image = $category['image'];
        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg','jpeg','png','webp','gif'];
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) { $errors[] = 'Only JPG, PNG, WEBP, GIF allowed.'; }
            elseif ($_FILES['image']['size'] > 2 * 1024 * 1024) { $errors[] = 'File too large (max 2 MB).'; }
            else {
                if (!is_dir(CATEGORY_UPLOAD_DIR)) mkdir(CATEGORY_UPLOAD_DIR, 0775, true);
                $newName = uniqid('cat_', true) . '.' . $ext;
                if (move_uploaded_file($_FILES['image']['tmp_name'], CATEGORY_UPLOAD_DIR . $newName)) {
                    if ($image && file_exists(CATEGORY_UPLOAD_DIR . $image)) {
                        unlink(CATEGORY_UPLOAD_DIR . $image);
                    }
                    $image = $newName;
                } else {
                    $errors[] = 'Failed to upload image.';
                }
            }
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare('UPDATE categories SET title=?, description=?, image=?, status=? WHERE id=?');
            $stmt->execute([$title, $desc, $image, $status, $id]);
            set_flash('success', 'Category updated successfully.');
            redirect('/categories/edit.php?id=' . $id);
        }
    }
    // Refresh
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE id = ?');
    $stmt->execute([$id]);
    $category = $stmt->fetch();
}

$pageTitle = 'Edit Category';
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Edit Category</h4>
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
                <input type="text" name="title" class="form-control" value="<?= e($category['title']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3"><?= e($category['description']) ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Image</label>
                <?php if ($category['image']): ?>
                    <div class="mb-2">
                        <img src="<?= e(BASE_URL) ?>/uploads/categories/<?= e($category['image']) ?>" class="rounded" width="80" height="80" style="object-fit:cover;">
                    </div>
                <?php endif; ?>
                <input type="file" name="image" class="form-control" accept="image/*">
                <small class="text-muted">Upload a new image to replace the current one.</small>
            </div>
            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="active" <?= $category['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $category['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
        </div>
    </div>
    <div class="mt-3">
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Update Category</button>
        <a href="<?= e(BASE_URL) ?>/categories/" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>
