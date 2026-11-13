<?php
session_start();
require_once 'inc/db_connect.php';

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if (!$order_id) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch();
if (!$order) {
    header('Location: index.php');
    exit;
}

$items = $pdo->prepare("
    SELECT oi.*, p.name 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
");
$items->execute([$order_id]);
$order_items = $items->fetchAll();
// Send email notification to admin about this order using PHPMailer
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/src/PHPMailer.php';
require_once __DIR__ . '/inc/src/SMTP.php';
require_once __DIR__ . '/inc/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$adminEmail = (defined('ADMIN_EMAIL') && ADMIN_EMAIL) ? ADMIN_EMAIL : 'admin@imobile.lk';
try {
    $mail = new PHPMailer(true);
    $mail->CharSet = 'UTF-8';
    // If SMTP is configured, use it
    if (defined('SMTP_HOST') && SMTP_HOST) {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = defined('SMTP_SECURE') ? SMTP_SECURE : 'tls';
        $mail->Port = defined('SMTP_PORT') ? SMTP_PORT : 587;
    }
    $mail->setFrom(defined('MAIL_FROM') ? MAIL_FROM : 'noreply@imobile.lk', defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'I Mobile.lk');
    $mail->addAddress($adminEmail);
    $mail->isHTML(true);
    $mail->Subject = 'New Order Placed: #' . htmlspecialchars($order['order_number']);

    $body = '<h2>New Order Received</h2>';
    $body .= '<p><strong>Order #:</strong> ' . htmlspecialchars($order['order_number']) . '</p>';
    $body .= '<p><strong>Name:</strong> ' . htmlspecialchars($order['customer_name']) . '</p>';
    $body .= '<p><strong>Phone:</strong> ' . htmlspecialchars($order['customer_phone']) . '</p>';
    $body .= '<p><strong>Address:</strong> ' . nl2br(htmlspecialchars($order['customer_address'])) . '</p>';
    $body .= '<h3>Items</h3><ul>';
    foreach ($order_items as $it) {
        $line = htmlspecialchars($it['name']) . ' x ' . (int)$it['quantity'] . ' = LKR ' . number_format($it['price'] * $it['quantity'], 2);
        $body .= '<li>' . $line . '</li>';
    }
    $body .= '</ul>';
    $body .= '<p><strong>Total:</strong> LKR ' . number_format($order['total_amount'], 2) . '</p>';

    $mail->Body = $body;
    // Send (will use PHP mail() by default unless SMTP is configured)
    $mail->send();
} catch (Exception $e) {
    error_log('Order confirmation email error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Confirmation</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .confirmation { max-width:600px; margin:40px auto; background:#fff; padding:30px; border-radius:10px; box-shadow:0 5px 20px rgba(0,0,0,0.1); }
        .confirmation h2 { color:#28a745; }
        .confirmation ul { list-style:none; padding:0; }
        .confirmation ul li { padding:5px 0; border-bottom:1px solid #eee; }
        .total { font-weight:bold; font-size:1.2rem; margin-top:10px; }
    </style>
</head>
<body>
    <header>
        <div class="container header-container">
            <div class="logo"><i class="fas fa-mobile-alt"></i> I Mobile.lk</div>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="items.php">Items</a></li>
                    <li><a href="about.php">About</a></li>
                    <li><a href="signin.php">LogIn</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="confirmation">
            <h2><i class="fas fa-check-circle" style="color:green;"></i> Order Placed!</h2>
            <p><strong>Order #:</strong> <?= htmlspecialchars($order['order_number']) ?></p>
            <p><strong>Name:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
            <p><strong>Phone:</strong> <?= htmlspecialchars($order['customer_phone']) ?></p>
            <p><strong>Address:</strong> <?= nl2br(htmlspecialchars($order['customer_address'])) ?></p>
            <h3>Items</h3>
            <ul>
                <?php foreach ($order_items as $item): ?>
                    <li><?= htmlspecialchars($item['name']) ?> x <?= $item['quantity'] ?> = LKR <?= number_format($item['price'] * $item['quantity'], 2) ?></li>
                <?php endforeach; ?>
            </ul>
            <div class="total">Total: LKR <?= number_format($order['total_amount'], 2) ?></div>
            <a href="index.php" class="btn" style="margin-top:15px; display:inline-block;">Continue Shopping</a>
        </div>
    </div>

    <footer> <div class="container"><p>&copy; 2026 I Mobile.lk. All rights reserved.</p></div></footer>
</body>
</html>