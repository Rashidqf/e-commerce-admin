<?php
/**
 * auth.php
 * Session bootstrap, authentication helpers, and security utilities.
 */

require_once __DIR__ . '/database.php';

// ---------------------------------------------------
// Start a secure session (once)
// ---------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ---------------------------------------------------
// CSRF token helpers
// ---------------------------------------------------

/** Generate (if needed) and return the CSRF token. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Verify a supplied CSRF token. */
function csrf_verify(?string $token): bool
{
    return !empty($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

/** Render a hidden CSRF input for forms. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

// ---------------------------------------------------
// Authentication helpers
// ---------------------------------------------------

/** Check if an admin is logged in. */
function is_logged_in(): bool
{
    return !empty($_SESSION['admin_id']);
}

/** Require login; redirect to login page if not authenticated. */
function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

/** Attempt to log in an admin by email + password. */
function attempt_login(string $email, string $password): bool
{
    $stmt = db()->prepare('SELECT id, name, email, password, profile_image FROM admins WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id']    = (int) $admin['id'];
        $_SESSION['admin_name']  = $admin['name'];
        $_SESSION['admin_email'] = $admin['email'];
        $_SESSION['admin_image'] = $admin['profile_image'];
        return true;
    }

    return false;
}

/** Log out the current admin. */
function logout(): void
{
    $_SESSION = [];
    session_destroy();
}

/** Get the current admin's record from the database. */
function current_admin(): ?array
{
    if (!is_logged_in()) {
        return null;
    }
    $stmt = db()->prepare('SELECT * FROM admins WHERE id = ? LIMIT 1');
    $stmt->execute([$_SESSION['admin_id']]);
    return $stmt->fetch() ?: null;
}

// ---------------------------------------------------
// Security / output helpers
// ---------------------------------------------------

/** Escape output for HTML context (XSS protection). */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Redirect helper. */
function redirect(string $path): void
{
    header('Location: ' . BASE_URL . '/' . ltrim($path, '/'));
    exit;
}

/** Flash message helpers (one-time session messages). */
function set_flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flash(): array
{
    $flash = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flash;
}
