<?php
session_start();
require_once 'inc/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$product_id = (int)$_POST['product_id'];
$quantity = (int)$_POST['quantity'];
$price = (float)$_POST['price'];
$customer_name = trim($_POST['customer_name']);
$customer_phone = trim($_POST['customer_phone']);
$customer_address = trim($_POST['customer_address']);

// Validate
if ($product_id <= 0 || $quantity <= 0 || empty($customer_name) || empty($customer_phone) || empty($customer_address)) {
    $_SESSION['order_error'] = 'Please fill all fields correctly.';
    header('Location: order_form.php?product_id=' . $product_id);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();
if (!$product) {
    $_SESSION['order_error'] = 'Product not found.';
    header('Location: index.php');
    exit;
}
if ($quantity > $product['stock']) {
    $_SESSION['order_error'] = 'Not enough stock available.';
    header('Location: order_form.php?product_id=' . $product_id);
    exit;
}

// Compute totals and order number
$total = $price * $quantity;
$order_number = 'ORD-' . date('Ymd') . '-' . random_int(1000, 9999);

// Use the logged-in user if available; otherwise fall back to the first existing user.
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if ($user_id <= 0) {
    $defaultUser = $pdo->query("SELECT id FROM users ORDER BY id LIMIT 1")->fetchColumn();
    $user_id = $defaultUser ? (int)$defaultUser : 1;
}

// Create a signature for this order to prevent duplicate submissions (double-click/refresh)
// Note: signature no longer includes user_id since orders won't store it
$order_signature = hash('sha256', $product_id . '|' . $quantity . '|' . $total . '|' . $customer_phone . '|' . $customer_address);

// If the same order was just placed in this session, redirect to its confirmation instead of inserting again
if (isset($_SESSION['last_order_signature']) && $_SESSION['last_order_signature'] === $order_signature) {
    $existing_order_id = isset($_SESSION['last_order_id']) ? (int)$_SESSION['last_order_id'] : 0;
    if ($existing_order_id) {
        header('Location: order_confirmation.php?order_id=' . $existing_order_id);
        exit;
    }
}

// Server-side duplicate check: look for an order with same phone & total in the last 2 minutes
$dupCheck = $pdo->prepare("SELECT id FROM orders WHERE customer_phone = ? AND total_amount = ? AND created_at >= (NOW() - INTERVAL 2 MINUTE) LIMIT 1");
$dupCheck->execute([$customer_phone, $total]);
if ($existing = $dupCheck->fetch()) {
    // Prevent duplicate insertion from rapid double-submits
    $_SESSION['order_success'] = "Order placed successfully! Order #" . htmlspecialchars($order_number);
    $_SESSION['last_order_signature'] = $order_signature;
    $_SESSION['last_order_id'] = (int)$existing['id'];
    header('Location: order_confirmation.php?order_id=' . (int)$existing['id']);
    exit;
}

try {
    $pdo->beginTransaction();

    $userColumnExists = $pdo->query("SHOW COLUMNS FROM orders LIKE 'user_id'")->fetch();
    if ($userColumnExists) {
        $stmt = $pdo->prepare(
            "INSERT INTO orders (user_id, customer_name, customer_phone, customer_address, order_number, total_amount, status)
             VALUES (?, ?, ?, ?, ?, ?, 'pending')"
        );
        $stmt->execute([$user_id, $customer_name, $customer_phone, $customer_address, $order_number, $total]);
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO orders (customer_name, customer_phone, customer_address, order_number, total_amount, status)
             VALUES (?, ?, ?, ?, ?, 'pending')"
        );
        $stmt->execute([$customer_name, $customer_phone, $customer_address, $order_number, $total]);
    }
    $order_id = $pdo->lastInsertId();

    // Insert order items
    $stmt = $pdo->prepare("
        INSERT INTO order_items (order_id, product_id, quantity, price)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$order_id, $product_id, $quantity, $price]);

    // Update stock
    $new_stock = $product['stock'] - $quantity;
    $stmt = $pdo->prepare("UPDATE products SET stock = ? WHERE id = ?");
    $stmt->execute([$new_stock, $product_id]);

    $pdo->commit();

    $_SESSION['order_success'] = "Order placed successfully! Order #$order_number";
    // Save signature and inserted order id in session to avoid accidental duplicates
    $_SESSION['last_order_signature'] = $order_signature;
    $_SESSION['last_order_id'] = $order_id;
    header('Location: order_confirmation.php?order_id=' . $order_id);
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    error_log('Order insert failed: ' . $e->getMessage());
    $_SESSION['order_error'] = 'Failed to place order. Please try again.';
    header('Location: order_form.php?product_id=' . $product_id);
    exit;
}
?>