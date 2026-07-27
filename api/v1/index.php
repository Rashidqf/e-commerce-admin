<?php
/**
 * GET /api/v1/index.php
 * API discovery — lists available public endpoints.
 */
require_once __DIR__ . '/bootstrap.php';

api_require_method('GET');

$base = api_app_url() . '/api/v1';

api_json_ok([
    'name'    => SITE_NAME . ' Public Shop API',
    'version' => 'v1',
    'endpoints' => [
        ['method' => 'GET',  'url' => $base . '/home.php',       'desc' => 'Home: categories, popular, latest'],
        ['method' => 'GET',  'url' => $base . '/categories.php', 'desc' => 'Active categories (?id= optional)'],
        ['method' => 'GET',  'url' => $base . '/products.php',   'desc' => 'Products with filters & pagination'],
        ['method' => 'GET',  'url' => $base . '/product.php',    'desc' => 'Product detail (?id= required)'],
        ['method' => 'POST', 'url' => $base . '/orders.php',     'desc' => 'Guest order (Bank Transfer)'],
    ],
], 'API ready.');
