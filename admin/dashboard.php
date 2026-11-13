<?php
require_once '../inc/session.php';
if (!isLoggedIn() || !isAdmin()) {
    redirect('../pages/index.php');
}
require_once '../inc/db_connect.php';

// Fetch stats
$totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalOrders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalRevenue = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE status != 'cancelled'")->fetchColumn();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <header><!-- include your header here --></header>
    <div class="dashboard-wrapper">
        <aside class="sidebar"><!-- sidebar with links --></aside>
        <main class="dashboard-main">
            <h1>Dashboard</h1>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-box"></i></div>
                    <div class="stat-number"><?= $totalProducts ?></div>
                    <div class="stat-label">Products</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-shopping-bag"></i></div>
                    <div class="stat-number"><?= $totalOrders ?></div>
                    <div class="stat-label">Orders</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
                    <div class="stat-number">LKR <?= number_format($totalRevenue, 2) ?></div>
                    <div class="stat-label">Revenue</div>
                </div>
            </div>
            <!-- Add more sections (recent orders, low stock) -->
        </main>
    </div>
    <footer>...</footer>
</body>
</html>