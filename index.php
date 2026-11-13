<?php
require_once 'inc/db_connect.php';
$trending = $pdo->query("SELECT * FROM products ORDER BY id DESC LIMIT 3")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>I Mobile</title>
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
                    <li><a href="index.php" class="active">Home</a></li>
                    <li><a href="items.php">Items</a></li>
                    <li><a href="about.php">About</a></li>
                    <li><a href="signin.php">LogIn</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <section class="hero">
            <div class="container">
                <h1>Welcome to <span style="color: red;">I Mobile.lk</span></h1>
                <p>YOUR PHONE, YOUR STYLE. FIND YOUR STYLE WITH US</p>
                <a href="items.php" class="btn">Shop Now</a>
            </div>
        </section>

        <section class="trending">
            <div class="container">
                <h2>Trending This Week</h2>
                <div class="product-list">
                    <?php foreach ($trending as $product): ?>
                        <div class="product">
                            <img src="assets/images/<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                            <h3><?= htmlspecialchars($product['name']) ?></h3>
                            <p>LKR <?= number_format($product['price'], 2) ?></p>
                        
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
            btn.addEventListener('click', () => alert('Added to cart! (Demo)'));
        });
    </script>
</body>
</html>