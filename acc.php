<?php
require_once 'inc/db_connect.php';
$stmt = $pdo->prepare("SELECT * FROM products WHERE category_id = (SELECT id FROM categories WHERE slug = 'accessories')");
$stmt->execute();
$accessories = $stmt->fetchAll();
?>
<!-- then loop over $accessories in the product grid -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accessories</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <header>
        <div class="container header-container">
            <div class="logo">
                <i class="fas fa-mobile-alt"></i>
                <span>I Mobile.lk</span>
            </div>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="items.php" class="active">Items</a></li>
                    <li><a href="about.php">About</a></li>
                    <li><a href="signin.php">LogIn</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <section class="page-header">
            <div class="container">
                <h1>Our Accessories</h1>
                <p>Complete your phone with the best accessories</p>
            </div>
        </section>

        <section class="products">
            <div class="container">
                <div class="product-grid">
                    <?php foreach ($accessories as $item): ?>
                        <div class="product-card">
                            <img src="assets/images/<?= htmlspecialchars($item['image_url']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                            <h3><?= htmlspecialchars($item['name']) ?></h3>
                            <p class="specs"><?= htmlspecialchars($item['specs']) ?></p>
                            <p class="price">LKR <?= number_format($item['price'], 2) ?></p>
                            <a href="order_form.php?product_id=<?= $item['id'] ?>" class="btn-buy">Order</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2026 I Mobile.lk. All rights reserved.</p>
        </div>
    </footer>

    <script>
        document.querySelectorAll('.btn-buy').forEach(btn => {
            btn.addEventListener('click', () => alert('✅ Item added to cart!'));
        });
    </script>
</body>
</html>