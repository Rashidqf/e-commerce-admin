<?php
/**
 * config.php
 * Application-wide configuration constants.
 */

// ---------------------------------------------------
// Database Credentials
// ---------------------------------------------------
define('DB_HOST', 'localhost');
define('DB_NAME', 'ecommerce-admin');
define('DB_USER', 'root');
define('DB_PASS', '');

// ---------------------------------------------------
// Application Settings
// ---------------------------------------------------
define('SITE_NAME', 'ShopAdmin');
define('BASE_URL', '/ecommerce-admin'); // must match the folder under htdocs

// ---------------------------------------------------
// Upload Paths (relative to this file's directory)
// ---------------------------------------------------
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('PRODUCT_UPLOAD_DIR', UPLOAD_DIR . 'products/');
define('CATEGORY_UPLOAD_DIR', UPLOAD_DIR . 'categories/');
define('PROFILE_UPLOAD_DIR', UPLOAD_DIR . 'admins/');

// ---------------------------------------------------
// Pagination
// ---------------------------------------------------
define('PER_PAGE', 10);

// ---------------------------------------------------
// Session
// ---------------------------------------------------
define('SESSION_NAME', 'shopadmin_session');

// ---------------------------------------------------
// Error Reporting (set display to 0 in production)
// ---------------------------------------------------
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// ---------------------------------------------------
// Time zone
// ---------------------------------------------------
date_default_timezone_set('UTC');
