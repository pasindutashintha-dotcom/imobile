<?php
session_start();
require_once 'inc/db_connect.php';

$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
if (!$product_id) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();
if (!$product) {
    header('Location: index.php');
    exit;
}

// Pre‑fill name if user is logged in
$customer_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Place Order</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .order-container { max-width: 500px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        .order-container h2 { margin-bottom: 20px; }
        .product-preview { display: flex; gap: 20px; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 20px; }
        .product-preview img { width: 100px; height: 100px; object-fit: contain; }
        .product-preview .info h3 { margin: 0; }
        .product-preview .price { color: #e94560; font-weight: bold; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 5px; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .form-group textarea { height: 80px; resize: vertical; }
        .btn-submit { background: #e94560; color: #fff; padding: 12px 30px; border: none; border-radius: 30px; cursor: pointer; font-weight: bold; }
        .btn-submit:hover { background: #c72a48; }
        .error { color: red; }
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
        <div class="order-container">
            <h2><i class="fas fa-shopping-cart"></i> Place Order</h2>

            <div class="product-preview">
                <img src="assets/images/<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                <div class="info">
                    <h3><?= htmlspecialchars($product['name']) ?></h3>
                    <p><?= htmlspecialchars($product['specs']) ?></p>
                    <span class="price">LKR <?= number_format($product['price'], 2) ?></span>
                    <p>Stock: <?= $product['stock'] ?></p>
                </div>
            </div>

            <form id="orderForm" action="place_order.php" method="POST">
                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                <input type="hidden" name="price" value="<?= $product['price'] ?>">

                <div class="form-group">
                    <label for="customer_name">Your Full Name *</label>
                    <input type="text" id="customer_name" name="customer_name" value="<?= htmlspecialchars($customer_name) ?>" required>
                </div>

                <div class="form-group">
                    <label for="customer_phone">Phone Number *</label>
                    <input type="text" id="customer_phone" name="customer_phone" placeholder="071-2345678" required>
                </div>

                <div class="form-group">
                    <label for="customer_address">Delivery Address *</label>
                    <textarea id="customer_address" name="customer_address" rows="3" placeholder="Enter your full address" required></textarea>
                </div>

                <div class="form-group">
                    <label for="quantity">Quantity *</label>
                    <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?= $product['stock'] ?>" required>
                </div>

                <button type="submit" class="btn-submit"><i class="fas fa-check-circle"></i> Confirm Order</button>
                <a href="javascript:history.back()" class="btn" style="background:#6c757d; margin-left:10px;">Cancel</a>
            </form>
        </div>
    </div>

    <script>
        // Prevent double submission by disabling the submit button after first click
        (function(){
            var form = document.getElementById('orderForm');
            if (!form) return;
            form.addEventListener('submit', function(e){
                var btn = form.querySelector('button[type="submit"]');
                if (btn) {
                    btn.disabled = true;
                    btn.textContent = 'Processing...';
                }
            });
        })();
    </script>

    <footer>
        <div class="container"><p>&copy; 2026 I Mobile.lk. All rights reserved.</p></div>
    </footer>
</body>
</html>