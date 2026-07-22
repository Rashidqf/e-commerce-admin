<?php
/**
 * helpers.php
 * Common utility functions for products, inventory, ratings, analytics
 */

require_once __DIR__ . '/video_utilities.php';

// =====================================================
// PRODUCT HELPERS
// =====================================================

/**
 * Get product with all related data
 * 
 * @param int $product_id Product ID
 * @return array|null Product with videos, variants, images, ratings
 */
function get_full_product(int $product_id): ?array
{
    $pdo = db();
    
    // Get product
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ? LIMIT 1');
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        return null;
    }
    
    // Get videos
    $stmt = $pdo->prepare('SELECT * FROM product_videos WHERE product_id = ? ORDER BY sort_order ASC, created_at ASC');
    $stmt->execute([$product_id]);
    $product['videos'] = $stmt->fetchAll();
    
    // Get images
    $stmt = $pdo->prepare('SELECT * FROM product_images WHERE product_id = ? ORDER BY created_at ASC');
    $stmt->execute([$product_id]);
    $product['images'] = $stmt->fetchAll();
    
    // Get attributes
    $stmt = $pdo->prepare('SELECT * FROM product_attributes WHERE product_id = ?');
    $stmt->execute([$product_id]);
    $product['attributes'] = $stmt->fetchAll();
    
    // Get variants
    $stmt = $pdo->prepare('SELECT * FROM product_variants WHERE product_id = ? AND status = "active" ORDER BY created_at ASC');
    $stmt->execute([$product_id]);
    $product['variants'] = $stmt->fetchAll();
    
    // Get ratings summary
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) as total, AVG(rating) as avg_rating, 
                SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
         FROM product_ratings WHERE product_id = ? AND status = "approved"'
    );
    $stmt->execute([$product_id]);
    $product['ratings_summary'] = $stmt->fetch();
    
    // Get average inventory
    $product['total_inventory'] = (int)$product['quantity'];
    $stmt = $pdo->prepare('SELECT COALESCE(SUM(quantity), 0) as total FROM product_variants WHERE product_id = ? AND status = "active"');
    $stmt->execute([$product_id]);
    $variant_stock = $stmt->fetch();
    $product['total_inventory'] += $variant_stock['total'];
    
    return $product;
}

/**
 * Get product average rating
 * 
 * @param int $product_id Product ID
 * @return float Average rating
 */
function get_product_rating(int $product_id): float
{
    $stmt = db()->prepare('SELECT AVG(rating) as avg FROM product_ratings WHERE product_id = ? AND status = "approved"');
    $stmt->execute([$product_id]);
    $result = $stmt->fetch();
    return round($result['avg'] ?? 0, 1);
}

/**
 * Get product review count
 * 
 * @param int $product_id Product ID
 * @return int Review count
 */
function get_product_review_count(int $product_id): int
{
    $stmt = db()->prepare('SELECT COUNT(*) as count FROM product_ratings WHERE product_id = ? AND status = "approved"');
    $stmt->execute([$product_id]);
    return (int)$stmt->fetch()['count'];
}

// =====================================================
// INVENTORY HELPERS
// =====================================================

/**
 * Log inventory change (stock movement)
 * 
 * @param int $product_id Product ID
 * @param int $quantity_change Quantity change (positive or negative)
 * @param string $reason Reason for change (purchase, return, adjustment, stock_count, etc.)
 * @param int $reference_id Optional reference ID (order ID, etc.)
 * @param string $notes Optional notes
 * @return int|false Last insert ID or false
 */
function log_inventory_movement(int $product_id, int $quantity_change, string $reason, int $reference_id = 0, string $notes = ''): int|false
{
    $stmt = db()->prepare(
        'INSERT INTO inventory_logs (product_id, quantity_change, reason, reference_id, notes) 
         VALUES (?, ?, ?, ?, ?)'
    );
    
    try {
        $stmt->execute([$product_id, $quantity_change, $reason, $reference_id ?: null, $notes ?: null]);
        return (int)db()->lastInsertId();
    } catch (Exception $e) {
        error_log('Inventory log error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get inventory level for a product
 * 
 * @param int $product_id Product ID
 * @return int Current inventory quantity
 */
function get_product_inventory(int $product_id): int
{
    $stmt = db()->prepare('SELECT quantity FROM products WHERE id = ?');
    $stmt->execute([$product_id]);
    return (int)($stmt->fetch()['quantity'] ?? 0);
}

/**
 * Update product inventory
 * 
 * @param int $product_id Product ID
 * @param int $new_quantity New quantity
 * @param string $reason Reason for update
 * @return bool Success
 */
function update_product_inventory(int $product_id, int $new_quantity, string $reason = 'manual_adjustment'): bool
{
    $old_quantity = get_product_inventory($product_id);
    $change = $new_quantity - $old_quantity;
    
    if ($change === 0) {
        return true; // No change needed
    }
    
    $stmt = db()->prepare('UPDATE products SET quantity = ? WHERE id = ?');
    if ($stmt->execute([$new_quantity, $product_id])) {
        log_inventory_movement($product_id, $change, $reason);
        return true;
    }
    
    return false;
}

/**
 * Check if product is low in stock
 * 
 * @param int $product_id Product ID
 * @param int $threshold Low stock threshold (default 10)
 * @return bool True if low stock
 */
function is_low_stock(int $product_id, int $threshold = 10): bool
{
    return get_product_inventory($product_id) <= $threshold;
}

/**
 * Get low stock products
 * 
 * @param int $threshold Low stock threshold
 * @return array Products with low stock
 */
function get_low_stock_products(int $threshold = 10): array
{
    $stmt = db()->prepare(
        'SELECT id, name, sku, quantity FROM products 
         WHERE quantity <= ? AND status = "active" 
         ORDER BY quantity ASC'
    );
    $stmt->execute([$threshold]);
    return $stmt->fetchAll();
}

// =====================================================
// DISCOUNT & PRICING HELPERS
// =====================================================

/**
 * Calculate final product price with discounts
 * 
 * @param int $product_id Product ID
 * @param int $quantity Quantity (for bulk discounts)
 * @return float Final price
 */
function calculate_product_price(int $product_id, int $quantity = 1): float
{
    $stmt = db()->prepare('SELECT price, sale_price FROM products WHERE id = ?');
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        return 0;
    }
    
    $price = $product['sale_price'] ?? $product['price'];
    
    // Check for quantity-based discounts
    $stmt = db()->prepare(
        'SELECT value, type FROM product_discounts 
         WHERE product_id = ? AND status = "active"
         AND (start_date IS NULL OR start_date <= NOW())
         AND (expiry_date IS NULL OR expiry_date > NOW())
         AND (min_quantity <= ? AND (max_quantity IS NULL OR max_quantity >= ?))
         ORDER BY value DESC LIMIT 1'
    );
    $stmt->execute([$product_id, $quantity, $quantity]);
    $discount = $stmt->fetch();
    
    if ($discount) {
        if ($discount['type'] === 'percentage') {
            $price -= $price * ($discount['value'] / 100);
        } else {
            $price -= $discount['value'];
        }
    }
    
    return max(0, $price);
}

/**
 * Apply coupon code and get discount amount
 * 
 * @param string $code Coupon code
 * @param float $order_total Order total before discount
 * @return array|false ['coupon_id' => int, 'discount' => float, 'code' => string] or false
 */
function apply_coupon_code(string $code, float $order_total): array|false
{
    $stmt = db()->prepare(
        'SELECT id, type, value, min_amount, max_uses, used_count, status
         FROM coupon_codes 
         WHERE code = ? AND status = "active"
         AND (start_date IS NULL OR start_date <= NOW())
         AND (expiry_date IS NULL OR expiry_date > NOW())'
    );
    $stmt->execute([strtoupper(trim($code))]);
    $coupon = $stmt->fetch();
    
    if (!$coupon) {
        return false;
    }
    
    // Check max uses
    if ($coupon['max_uses'] && $coupon['used_count'] >= $coupon['max_uses']) {
        return false;
    }
    
    // Check minimum amount
    if ($coupon['min_amount'] && $order_total < $coupon['min_amount']) {
        return false;
    }
    
    // Calculate discount
    $discount = $coupon['type'] === 'percentage' 
        ? $order_total * ($coupon['value'] / 100)
        : $coupon['value'];
    
    return [
        'coupon_id' => (int)$coupon['id'],
        'discount'  => round($discount, 2),
        'code'      => strtoupper(trim($code))
    ];
}

// =====================================================
// ANALYTICS HELPERS
// =====================================================

/**
 * Update daily sales analytics
 * Typically called when order is completed
 * 
 * @param string $date Date (YYYY-MM-DD)
 * @return bool Success
 */
function update_daily_analytics(string $date = null): bool
{
    $date = $date ?? date('Y-m-d');
    
    $pdo = db();
    
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) as total_orders, 
                SUM(total) as total_revenue,
                SUM(COALESCE((SELECT SUM(quantity) FROM order_items WHERE order_id = orders.id), 0)) as total_items_sold
         FROM orders 
         WHERE DATE(created_at) = ? AND order_status IN ("processing", "shipped", "delivered") AND payment_status = "paid"'
    );
    $stmt->execute([$date]);
    $data = $stmt->fetch();
    
    $total_orders = (int)($data['total_orders'] ?? 0);
    $total_revenue = (float)($data['total_revenue'] ?? 0);
    $total_items = (int)($data['total_items_sold'] ?? 0);
    $avg_order_value = $total_orders > 0 ? $total_revenue / $total_orders : 0;
    
    $stmt = $pdo->prepare(
        'INSERT INTO analytics_daily_sales (date, total_orders, total_revenue, total_items_sold, avg_order_value)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE 
         total_orders = VALUES(total_orders),
         total_revenue = VALUES(total_revenue),
         total_items_sold = VALUES(total_items_sold),
         avg_order_value = VALUES(avg_order_value),
         updated_at = NOW()'
    );
    
    return $stmt->execute([$date, $total_orders, $total_revenue, $total_items, round($avg_order_value, 2)]);
}

/**
 * Get dashboard statistics
 * 
 * @return array Dashboard stats
 */
function get_dashboard_stats(): array
{
    $pdo = db();
    
    $stats = [];
    
    // Total orders
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM orders');
    $stats['total_orders'] = (int)$stmt->fetch()['count'];
    
    // Total revenue
    $stmt = $pdo->query('SELECT COALESCE(SUM(total), 0) as total FROM orders WHERE payment_status = "paid"');
    $stats['total_revenue'] = (float)$stmt->fetch()['total'];
    
    // Total customers
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM customers');
    $stats['total_customers'] = (int)$stmt->fetch()['count'];
    
    // Total products
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM products');
    $stats['total_products'] = (int)$stmt->fetch()['count'];
    
    // Low stock products
    $stats['low_stock_count'] = count(get_low_stock_products());
    
    // Pending orders
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM orders WHERE order_status = "pending"');
    $stats['pending_orders'] = (int)$stmt->fetch()['count'];
    
    // Pending reviews
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM product_ratings WHERE status = "pending"');
    $stats['pending_reviews'] = (int)$stmt->fetch()['count'];
    
    // Today's revenue
    $stmt = $pdo->query('SELECT COALESCE(SUM(total), 0) as total FROM orders WHERE DATE(created_at) = CURDATE() AND payment_status = "paid"');
    $stats['todays_revenue'] = (float)$stmt->fetch()['total'];
    
    // This month orders
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM orders WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())');
    $stats['month_orders'] = (int)$stmt->fetch()['count'];
    
    return $stats;
}

/**
 * Get top selling products
 * 
 * @param int $limit Number of products
 * @param string $period Period (day, week, month, year)
 * @return array Top products
 */
function get_top_selling_products(int $limit = 5, string $period = 'month'): array
{
    $date_filter = match($period) {
        'day'   => 'DATE(o.created_at) = CURDATE()',
        'week'  => 'o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)',
        'month' => 'MONTH(o.created_at) = MONTH(NOW()) AND YEAR(o.created_at) = YEAR(NOW())',
        'year'  => 'YEAR(o.created_at) = YEAR(NOW())',
        default => 'TRUE'
    };
    
    $query = sprintf(
        'SELECT p.id, p.name, p.sku, SUM(oi.quantity) as total_sold, SUM(oi.total) as total_revenue
         FROM order_items oi
         JOIN products p ON oi.product_id = p.id
         JOIN orders o ON oi.order_id = o.id
         WHERE %s
         GROUP BY p.id
         ORDER BY total_sold DESC
         LIMIT %d',
        $date_filter,
        $limit
    );
    
    return db()->query($query)->fetchAll();
}
