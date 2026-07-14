<?php
/**
 * logout.php
 * Destroy the session and redirect to login.
 */
require_once __DIR__ . '/includes/auth.php';
logout();
header('Location: ' . BASE_URL . '/login.php');
exit;
