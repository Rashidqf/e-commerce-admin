<?php
/**
 * header.php
 * Reusable HTML head, top navigation bar, and flash messages.
 * Expects $pageTitle to be set before including.
 */
require_once __DIR__ . '/auth.php';
require_login();

$pageTitle = $pageTitle ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> &middot; <?= e(SITE_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(BASE_URL) ?>/assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="wrapper d-flex">

    <!-- Sidebar -->
    <?php include __DIR__ . '/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content flex-grow-1" id="mainContent">
        <!-- Top Bar -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark px-4 py-2 sticky-top">
            <button class="btn btn-outline-light btn-sm d-lg-none" type="button" onclick="toggleSidebar()">
                <i class="bi bi-list"></i>
            </button>
            <span class="navbar-brand ms-2 mb-0 h6"><?= e($pageTitle) ?></span>
            <div class="dropdown ms-auto">
                <a href="#" class="d-flex align-items-center text-light text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                    <img src="<?= e(BASE_URL) ?>/assets/images/avatar.png" alt="avatar" class="rounded-circle me-2" width="32" height="32">
                    <span class="d-none d-sm-inline"><?= e($_SESSION['admin_name'] ?? 'Admin') ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="<?= e(BASE_URL) ?>/profile/"><i class="bi bi-person me-1"></i> Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="<?= e(BASE_URL) ?>/logout.php"><i class="bi bi-box-arrow-right me-1"></i> Logout</a></li>
                </ul>
            </div>
        </nav>

        <!-- Page Content -->
        <main class="p-4">
            <?php foreach (get_flash() as $f): ?>
                <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show" role="alert">
                    <?= e($f['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endforeach; ?>
