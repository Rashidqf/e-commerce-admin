<?php
/**
 * POST /api/v1/orders.php
 * Guest checkout — Bank Transfer. Client sends cart items.
 *
 * Body JSON:
 * {
 *   "name": "...",
 *   "email": "...",
 *   "phone": "...",
 *   "shipping_address": "...",
 *   "payment_method": "Bank Transfer",
 *   "items": [{ "product_id": 1, "quantity": 2 }]
 * }
 */
require_once __DIR__ . '/bootstrap.php';

api_require_method('POST');

$body = api_read_json_body();

$name    = trim((string) ($body['name'] ?? ''));
$email   = trim((string) ($body['email'] ?? ''));
$phone   = trim((string) ($body['phone'] ?? ''));
$address = trim((string) ($body['shipping_address'] ?? ''));
$method  = trim((string) ($body['payment_method'] ?? 'Bank Transfer'));
$items   = $body['items'] ?? null;

$errors = [];

if ($name === '') {
    $errors['name'] = 'Name is required.';
}
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Valid email is required.';
}
if ($address === '') {
    $errors['shipping_address'] = 'Shipping address is required.';
}
if (!is_array($items) || count($items) === 0) {
    $errors['items'] = 'At least one cart item is required.';
}
if (strcasecmp($method, 'Bank Transfer') !== 0) {
    $errors['payment_method'] = 'Only Bank Transfer is supported.';
}

if ($errors) {
    api_json_error('Validation failed.', 422, $errors);
}

$normalized = [];
foreach ($items as $i => $item) {
    $pid = (int) ($item['product_id'] ?? 0);
    $qty = (int) ($item['quantity'] ?? 0);
    if ($pid <= 0 || $qty <= 0) {
        api_json_error("Invalid product_id or quantity at items[$i].", 422);
    }
    if (isset($normalized[$pid])) {
        $normalized[$pid] += $qty;
    } else {
        $normalized[$pid] = $qty;
    }
}

$pdo = api_db();

try {
    $pdo->beginTransaction();

    // Find or create guest customer by email
    $custStmt = $pdo->prepare('SELECT id, status FROM customers WHERE email = ? LIMIT 1');
    $custStmt->execute([$email]);
    $customer = $custStmt->fetch();

    if ($customer) {
        if ($customer['status'] !== 'active') {
            throw new RuntimeException('Customer account is inactive.', 403);
        }
        $customerId = (int) $customer['id'];
        $pdo->prepare(
            'UPDATE customers SET name = ?, phone = ?, address = ? WHERE id = ?'
        )->execute([$name, $phone !== '' ? $phone : null, $address, $customerId]);
    } else {
        $pdo->prepare(
            'INSERT INTO customers (name, email, phone, address, status) VALUES (?, ?, ?, ?, ?)'
        )->execute([$name, $email, $phone !== '' ? $phone : null, $address, 'active']);
        $customerId = (int) $pdo->lastInsertId();
    }

    $lineRows = [];
    $orderTotal = 0.0;

    $prodStmt = $pdo->prepare(
        "SELECT id, name, main_image, price, sale_price, quantity, status
         FROM products WHERE id = ? FOR UPDATE"
    );

    foreach ($normalized as $productId => $qty) {
        $prodStmt->execute([$productId]);
        $product = $prodStmt->fetch();

        if (!$product || $product['status'] !== 'active') {
            throw new RuntimeException("Product #$productId is not available.", 400);
        }
        if ((int) $product['quantity'] < $qty) {
            throw new RuntimeException(
                "Insufficient stock for \"{$product['name']}\". Available: {$product['quantity']}.",
                400
            );
        }

        $unit = api_effective_price($product);
        $lineTotal = round($unit * $qty, 2);
        $orderTotal += $lineTotal;

        $lineRows[] = [
            'product_id'    => (int) $product['id'],
            'product_name'  => $product['name'],
            'product_image' => $product['main_image'],
            'quantity'      => $qty,
            'price'         => $unit,
            'total'         => $lineTotal,
        ];
    }

    $orderTotal = round($orderTotal, 2);
    $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

    $pdo->prepare(
        'INSERT INTO orders (customer_id, order_number, total, payment_method, payment_status, order_status, shipping_address)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    )->execute([
        $customerId,
        $orderNumber,
        $orderTotal,
        'Bank Transfer',
        'pending',
        'pending',
        $address,
    ]);
    $orderId = (int) $pdo->lastInsertId();

    $itemInsert = $pdo->prepare(
        'INSERT INTO order_items (order_id, product_id, product_name, product_image, quantity, price, total)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stockUpdate = $pdo->prepare(
        'UPDATE products SET quantity = quantity - ? WHERE id = ? AND quantity >= ?'
    );

    foreach ($lineRows as $line) {
        $itemInsert->execute([
            $orderId,
            $line['product_id'],
            $line['product_name'],
            $line['product_image'],
            $line['quantity'],
            $line['price'],
            $line['total'],
        ]);

        $stockUpdate->execute([$line['quantity'], $line['product_id'], $line['quantity']]);
        if ($stockUpdate->rowCount() === 0) {
            throw new RuntimeException('Stock update failed. Please try again.', 409);
        }
    }

    $pdo->commit();

    api_json_ok([
        'order_id'       => $orderId,
        'order_number'   => $orderNumber,
        'total'          => $orderTotal,
        'payment_method' => 'Bank Transfer',
        'payment_status' => 'pending',
        'order_status'   => 'pending',
        'items'          => array_map(static function ($line) {
            return [
                'product_id'   => $line['product_id'],
                'product_name' => $line['product_name'],
                'quantity'     => $line['quantity'],
                'price'        => $line['price'],
                'total'        => $line['total'],
            ];
        }, $lineRows),
    ], 'Order placed successfully. Please complete bank transfer.', null, 201);
} catch (RuntimeException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $code = (int) $e->getCode();
    if ($code < 400 || $code > 599) {
        $code = 400;
    }
    api_json_error($e->getMessage(), $code);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Order API error: ' . $e->getMessage());
    api_json_error('Could not place order. Please try again.', 500);
}
