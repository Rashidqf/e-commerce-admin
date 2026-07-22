<?php
/**
 * products/add.php
 * Add a new product with main image + multiple gallery images.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login();

$pdo = db();

// Fetch categories for dropdown
$categories = $pdo->query("SELECT id, title FROM categories WHERE status = 'active' ORDER BY title")->fetchAll();

$errors = [];
$old    = ['name' => '', 'category_id' => '', 'sku' => '', 'price' => '', 'sale_price' => '', 'quantity' => '', 'short_description' => '', 'long_description' => '', 'status' => 'active'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid session token.';
    } else {
        $old = array_merge($old, $_POST);
        $name        = trim($_POST['name'] ?? '');
        $category_id = (int)($_POST['category_id'] ?? 0);
        $sku         = trim($_POST['sku'] ?? '');
        $price       = (float)($_POST['price'] ?? 0);
        $salePrice   = $_POST['sale_price'] !== '' ? (float)$_POST['sale_price'] : null;
        $quantity    = (int)($_POST['quantity'] ?? 0);
        $shortDesc   = trim($_POST['short_description'] ?? '');
        $longDesc    = trim($_POST['long_description'] ?? '');
        $status      = ($_POST['status'] ?? 'active') === 'active' ? 'active' : 'inactive';

        if ($name === '')      $errors[] = 'Product name is required.';
        if ($category_id <= 0) $errors[] = 'Please select a category.';
        if ($sku === '')       $errors[] = 'SKU is required.';
        if ($price <= 0)       $errors[] = 'Price must be greater than zero.';

        // Check SKU uniqueness
        $chk = $pdo->prepare('SELECT COUNT(*) FROM products WHERE sku = ?');
        $chk->execute([$sku]);
        if ($chk->fetchColumn() > 0) {
            $errors[] = 'SKU already exists. Please use a different SKU.';
        }

        // Handle main image upload
        $mainImage = null;
        if (!empty($_FILES['main_image']['name']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
            $mainImage = upload_image($_FILES['main_image'], PRODUCT_UPLOAD_DIR, $errors, 'main_image');
        }

        // Handle gallery images
        $galleryImages = [];
        if (!empty($_FILES['gallery_images']['name'][0])) {
            foreach ($_FILES['gallery_images']['name'] as $i => $n) {
                $file = [
                    'name'     => $_FILES['gallery_images']['name'][$i],
                    'type'     => $_FILES['gallery_images']['type'][$i],
                    'tmp_name' => $_FILES['gallery_images']['tmp_name'][$i],
                    'error'    => $_FILES['gallery_images']['error'][$i],
                    'size'     => $_FILES['gallery_images']['size'][$i],
                ];
                $img = upload_image($file, PRODUCT_UPLOAD_DIR, $errors, 'gallery_images');
                if ($img) $galleryImages[] = $img;
            }
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare(
                'INSERT INTO products (category_id, name, sku, price, sale_price, quantity, short_description, long_description, main_image, status)
                 VALUES (?,?,?,?,?,?,?,?,?,?)'
            );
            $stmt->execute([$category_id, $name, $sku, $price, $salePrice, $quantity, $shortDesc, $longDesc, $mainImage, $status]);
            $productId = (int) $pdo->lastInsertId();

            // Insert gallery images
            if ($galleryImages) {
                $gi = $pdo->prepare('INSERT INTO product_images (product_id, image) VALUES (?,?)');
                foreach ($galleryImages as $img) {
                    $gi->execute([$productId, $img]);
                }
            }

            set_flash('success', 'Product added successfully.');
            redirect('/products/');
        }
    }
}

/**
 * Upload an image safely.
 */
function upload_image(array $file, string $dir, array &$errors, string $label): ?string
{
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed)) {
        $errors[] = ucfirst($label) . ': only JPG, PNG, WEBP, GIF allowed.';
        return null;
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        $errors[] = ucfirst($label) . ': file too large (max 2 MB).';
        return null;
    }

    if (!is_dir($dir)) mkdir($dir, 0775, true);

    $newName = uniqid('img_', true) . '.' . $ext;
    if (move_uploaded_file($file['tmp_name'], $dir . $newName)) {
        return $newName;
    }

    $errors[] = ucfirst($label) . ': failed to upload.';
    return null;
}

$pageTitle = 'Add Product';
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Add Product</h4>
    <a href="<?= e(BASE_URL) ?>/products/" class="btn btn-outline-secondary btn-sm">&larr; Back</a>
</div>

<?php if ($errors): ?>
    <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white"><h6 class="mb-0">Basic Information</h6></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Product Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="<?= e($old['name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category <span class="text-danger">*</span></label>
                        <select name="category_id" class="form-select" required>
                            <option value="">— Select —</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= (int)$c['id'] ?>" <?= (int)$old['category_id'] === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">SKU <span class="text-danger">*</span></label>
                        <input type="text" name="sku" class="form-control" value="<?= e($old['sku']) ?>" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Price ($) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="price" class="form-control" value="<?= e($old['price']) ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Sale Price ($)</label>
                            <input type="number" step="0.01" min="0" name="sale_price" class="form-control" value="<?= e($old['sale_price']) ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Quantity</label>
                        <input type="number" min="0" name="quantity" class="form-control" value="<?= e($old['quantity']) ?>">
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
        </div>

        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white"><h6 class="mb-0">Description & Images</h6></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Short Description</label>
                        <textarea name="short_description" class="form-control" rows="2"><?= e($old['short_description']) ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Long Description</label>
                        <textarea name="long_description" class="form-control" rows="4"><?= e($old['long_description']) ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Main Image</label>
                        <input type="file" name="main_image" class="form-control" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Gallery Images (multiple)</label>
                        <input type="file" name="gallery_images[]" class="form-control" accept="image/*" multiple>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="alert alert-info mt-3 mb-3">
        <i class="bi bi-info-circle me-2"></i>
        <strong>Note:</strong> After creating the product, you can manage product videos, variants, and attributes from the product edit page.
    </div>
    
    <div class="mt-3">
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Save Product</button>
        <a href="<?= e(BASE_URL) ?>/products/" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>
