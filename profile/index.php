<?php
/**
 * profile/index.php
 * Admin profile: update name, email, password, and profile image.
 */
require_once __DIR__ . '/../includes/auth.php';
require_login();

$pdo    = db();
$admin  = current_admin();

$errors = [];

// Handle profile update (name, email, phone, address, image)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'profile') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid session token.';
    } else {
        $name    = trim($_POST['name'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $phone   = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');

        if ($name === '')  $errors[] = 'Name is required.';
        if ($email === '') $errors[] = 'Email is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format.';

        // Check email uniqueness (exclude current)
        $chk = $pdo->prepare('SELECT COUNT(*) FROM admins WHERE email = ? AND id != ?');
        $chk->execute([$email, $_SESSION['admin_id']]);
        if ($chk->fetchColumn() > 0) {
            $errors[] = 'Email already in use by another admin.';
        }

        // Handle profile image
        $profileImage = $admin['profile_image'];
        if (!empty($_FILES['profile_image']['name']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg','jpeg','png','webp','gif'];
            $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) {
                $errors[] = 'Profile image: only JPG, PNG, WEBP, GIF allowed.';
            } elseif ($_FILES['profile_image']['size'] > 2 * 1024 * 1024) {
                $errors[] = 'Profile image: file too large (max 2 MB).';
            } else {
                if (!is_dir(PROFILE_UPLOAD_DIR)) mkdir(PROFILE_UPLOAD_DIR, 0775, true);
                $newName = uniqid('admin_', true) . '.' . $ext;
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], PROFILE_UPLOAD_DIR . $newName)) {
                    if ($profileImage && file_exists(PROFILE_UPLOAD_DIR . $profileImage)) {
                        unlink(PROFILE_UPLOAD_DIR . $profileImage);
                    }
                    $profileImage = $newName;
                } else {
                    $errors[] = 'Profile image: failed to upload.';
                }
            }
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare('UPDATE admins SET name=?, email=?, phone=?, address=?, profile_image=? WHERE id=?');
            $stmt->execute([$name, $email, $phone, $address, $profileImage, $_SESSION['admin_id']]);
            $_SESSION['admin_name']  = $name;
            $_SESSION['admin_email'] = $email;
            $_SESSION['admin_image'] = $profileImage;
            set_flash('success', 'Profile updated successfully.');
            redirect('/profile/');
        }
    }
}

// Handle password update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'password') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid session token.';
    } else {
        $current    = $_POST['current_password'] ?? '';
        $new        = $_POST['new_password'] ?? '';
        $confirm    = $_POST['confirm_password'] ?? '';

        if (!password_verify($current, $admin['password'])) {
            $errors[] = 'Current password is incorrect.';
        } elseif (strlen($new) < 6) {
            $errors[] = 'New password must be at least 6 characters.';
        } elseif ($new !== $confirm) {
            $errors[] = 'New passwords do not match.';
        } else {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE admins SET password=? WHERE id=?');
            $stmt->execute([$hashed, $_SESSION['admin_id']]);
            set_flash('success', 'Password changed successfully.');
            redirect('/profile/');
        }
    }
}

// Refresh admin data after updates
$admin = current_admin();

$pageTitle = 'Admin Profile';
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">My Profile</h4>
</div>

<?php if ($errors): ?>
    <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="row g-3">
    <!-- Profile Info -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-person me-1"></i> Profile Information</h6></div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="profile">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="<?= e($admin['name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" value="<?= e($admin['email']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" value="<?= e($admin['phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" class="form-control" value="<?= e($admin['address'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Profile Image</label>
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <?php if ($admin['profile_image']): ?>
                                    <img src="<?= e(BASE_URL) ?>/uploads/admins/<?= e($admin['profile_image']) ?>" class="rounded-circle" width="64" height="64" style="object-fit:cover;">
                                <?php else: ?>
                                    <div class="bg-light rounded-circle d-flex align-items-center justify-content-center" style="width:64px;height:64px;">
                                        <i class="bi bi-person-fill fs-2 text-muted"></i>
                                    </div>
                                <?php endif; ?>
                                <input type="file" name="profile_image" class="form-control" accept="image/*">
                            </div>
                            <small class="text-muted">JPG, PNG, WEBP, GIF. Max 2 MB.</small>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary mt-3"><i class="bi bi-check-lg me-1"></i> Save Changes</button>
                </form>
            </div>
        </div>

        <!-- Change Password -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-key me-1"></i> Change Password</h6></div>
            <div class="card-body">
                <form method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="password">
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" required minlength="6">
                        <small class="text-muted">Minimum 6 characters.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-shield-lock me-1"></i> Update Password</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Summary Card -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body">
                <?php if ($admin['profile_image']): ?>
                    <img src="<?= e(BASE_URL) ?>/uploads/admins/<?= e($admin['profile_image']) ?>" class="rounded-circle mb-3" width="100" height="100" style="object-fit:cover;">
                <?php else: ?>
                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:100px;height:100px;">
                        <i class="bi bi-person-fill fs-1 text-muted"></i>
                    </div>
                <?php endif; ?>
                <h5 class="mb-1"><?= e($admin['name']) ?></h5>
                <p class="text-muted small mb-2"><?= e($admin['email']) ?></p>
                <p class="small text-muted mb-1"><i class="bi bi-telephone me-1"></i> <?= e($admin['phone'] ?: 'N/A') ?></p>
                <p class="small text-muted mb-0"><i class="bi bi-calendar me-1"></i> Joined <?= e(date('M j, Y', strtotime($admin['created_at']))) ?></p>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
