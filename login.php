<?php
/**
 * login.php
 * Admin login page with CSRF protection and session-based authentication.
 */
require_once __DIR__ . '/includes/auth.php';

// Redirect if already logged in
if (is_logged_in()) {
    redirect('/dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid session token. Please try again.';
    } elseif ($email === '' || $password === '') {
        $error = 'Please enter both email and password.';
    } else {
        if (attempt_login($email, $password)) {
            set_flash('success', 'Welcome back, ' . e($_SESSION['admin_name']) . '!');
            redirect('/dashboard.php');
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login &middot; <?= e(SITE_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(BASE_URL) ?>/assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-dark d-flex align-items-center vh-100">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">
                <div class="card shadow-lg border-0 rounded-4">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="bi bi-shop fs-1 text-dark"></i>
                            <h4 class="mt-2 mb-0"><?= e(SITE_NAME) ?></h4>
                            <p class="text-muted small">Admin Panel Login</p>
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-danger py-2 small"><?= e($error) ?></div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <?= csrf_field() ?>
                            <div class="mb-3">
                                <label class="form-label small text-muted">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                                    <input type="email" name="email" class="form-control" required autofocus
                                           value="<?= e($_POST['email'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label small text-muted">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                                    <input type="password" name="password" class="form-control" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-dark w-100 py-2">
                                <i class="bi bi-box-arrow-in-right me-1"></i> Login
                            </button>
                        </form>

                        <p class="text-center text-muted small mt-4 mb-0">
                            Default: <strong>admin@example.com</strong> / <strong>admin123</strong>
                        </p>
                    </div>
                </div>
                <p class="text-center text-white-50 small mt-3">&copy; <?= date('Y') ?> <?= e(SITE_NAME) ?></p>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
