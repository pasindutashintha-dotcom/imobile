<?php
require_once '../inc/session.php';
require_once '../inc/db_connect.php';
require_once '../inc/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../pages/index.php');
}

$report_type = isset($_GET['type']) ? $_GET['type'] : 'sales';
$days = isset($_GET['days']) ? (int)$_GET['days'] : 30;

// Get report data based on type
$report_data = [];
$report_title = '';

switch ($report_type) {
    case 'sales':
        $report_data = getSalesReport($pdo, $days);
        $report_title = 'Sales Report - Last ' . $days . ' Days';
        $sales_by_status = getSalesByStatus($pdo);
        $summary = getOverallSummary($pdo, $days);
        break;
    
    case 'top-products':
        $report_data = getTopProductsBySales($pdo, 20);
        $report_title = 'Top Products by Sales';
        break;
    
    case 'inventory':
        $report_data = getInventoryReport($pdo);
        $report_title = 'Inventory Report';
        $low_stock = getLowStockProducts($pdo, 10);
        break;
    
    case 'revenue':
        $report_data = getRevenueReport($pdo, $days);
        $report_title = 'Revenue Report - Last ' . $days . ' Days';
        $revenue_by_status = getRevenueByStatus($pdo);
        $summary = getOverallSummary($pdo, $days);
        break;
    
    case 'category':
        $report_data = getCategoryPerformance($pdo);
        $report_title = 'Category Performance Report';
        break;
    
    case 'monthly':
        $report_data = getMonthlyPerformance($pdo, 12);
        $report_title = 'Monthly Performance - Last 12 Months';
        $summary = getOverallSummary($pdo, 365);
        break;
    
    default:
        $report_data = getSalesReport($pdo, $days);
        $report_title = 'Sales Report';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($report_title) ?> - I Mobile.lk</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../db-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .report-container {
            padding: 20px;
            background: white;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e94560;
        }
        .report-title {
            font-size: 1.8rem;
            color: #333;
            margin: 0;
        }
        .report-filters {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .report-filters select,
        .report-filters button {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        .report-filters button {
            background: #e94560;
            color: white;
            border: none;
            cursor: pointer;
            font-weight: bold;
        }
        .report-filters button:hover {
            background: #c72a48;
        }
        .nav-menu {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .nav-menu a {
            padding: 8px 15px;
            background: #f0f0f0;
            border-radius: 5px;
            text-decoration: none;
            color: #333;
            border: 2px solid transparent;
            transition: all 0.3s;
        }
        .nav-menu a.active,
        .nav-menu a:hover {
            background: #e94560;
            color: white;
            border-color: #e94560;
        }
        .stats-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin: 10px 0;
            text-align: center;
        }
        .stats-box h4 {
            margin: 0 0 10px;
            font-size: 0.9rem;
            opacity: 0.9;
        }
        .stats-box .value {
            font-size: 1.8rem;
            font-weight: bold;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .report-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }
        .report-table thead {
            background: #f5f5f5;
            border-bottom: 2px solid #e94560;
        }
        .report-table th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
        }
        .report-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }
        .report-table tbody tr:hover {
            background: #f9f9f9;
        }
        .chart-container {
            position: relative;
            width: 100%;
            height: 400px;
            margin: 20px 0;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
            font-size: 1.1rem;
        }
        .export-btn {
            padding: 10px 20px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            margin-left: 10px;
        }
        .export-btn:hover {
            background: #218838;
        }
    </style>
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
                    <li><a href="../index.php">Home</a></li>
                    <li><a href="../items.php">Items</a></li>
                    <li><a href="../about.php">About</a></li>
                    <li><a href="../auth/logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="dashboard-wrapper">
        <aside class="sidebar">
            <div class="sidebar-brand">
                <h2><i class="fas fa-user-shield"></i> Admin</h2>
            </div>
            <ul class="sidebar-nav">
                <li><a href="../db.php"><i class="fas fa-th-large"></i> Dashboard</a></li>
                <li><a href="reports.php" class="active"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li><a href="../admin/products.php"><i class="fas fa-box"></i> Products</a></li>
                <li><a href="../admin/dashboard.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
                <li><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <main class="dashboard-main">
            <div class="report-container">
                <div class="report-header">
                    <h1 class="report-title">
                        <i class="fas fa-chart-bar" style="color: #e94560; margin-right: 10px;"></i>
                        <?= htmlspecialchars($report_title) ?>
                    </h1>
                </div>

                <!-- Report Navigation Menu -->
                <div class="nav-menu">
                    <a href="?type=sales" class="<?= $report_type === 'sales' ? 'active' : '' ?>">
                        <i class="fas fa-line-chart"></i> Sales
                    </a>
                    <a href="?type=revenue" class="<?= $report_type === 'revenue' ? 'active' : '' ?>">
                        <i class="fas fa-money-bill"></i> Revenue
                    </a>
                    <a href="?type=top-products" class="<?= $report_type === 'top-products' ? 'active' : '' ?>">
                        <i class="fas fa-star"></i> Top Products
                    </a>
                    <a href="?type=inventory" class="<?= $report_type === 'inventory' ? 'active' : '' ?>">
                        <i class="fas fa-boxes"></i> Inventory
                    </a>
                    <a href="?type=category" class="<?= $report_type === 'category' ? 'active' : '' ?>">
                        <i class="fas fa-folder"></i> Categories
                    </a>
                    <a href="?type=monthly" class="<?= $report_type === 'monthly' ? 'active' : '' ?>">
                        <i class="fas fa-calendar"></i> Monthly
                    </a>
                </div>

                <!-- Filters -->
                <?php if (in_array($report_type, ['sales', 'revenue'])): ?>
                <div class="report-filters">
                    <label>Time Period:</label>
                    <select onchange="location.href='?type=<?= $report_type ?>&days=' + this.value;">
                        <option value="7" <?= $days === 7 ? 'selected' : '' ?>>Last 7 Days</option>
                        <option value="30" <?= $days === 30 ? 'selected' : '' ?>>Last 30 Days</option>
                        <option value="90" <?= $days === 90 ? 'selected' : '' ?>>Last 90 Days</option>
                        <option value="365" <?= $days === 365 ? 'selected' : '' ?>>Last Year</option>
                    </select>
                </div>
                <?php endif; ?>

                <!-- Summary Stats (for Sales and Revenue reports) -->
                <?php if (isset($summary) && !empty($summary)): ?>
                <div class="stats-grid">
                    <div class="stats-box">
                        <h4>Total Orders</h4>
                        <div class="value"><?= (int)$summary['total_orders'] ?></div>
                    </div>
                    <div class="stats-box">
                        <h4>Total Revenue</h4>
                        <div class="value">LKR <?= number_format($summary['total_revenue'], 0) ?></div>
                    </div>
                    <div class="stats-box">
                        <h4>Average Order Value</h4>
                        <div class="value">LKR <?= number_format($summary['avg_order_value'], 0) ?></div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Sales by Status (for Sales report) -->
                <?php if ($report_type === 'sales' && isset($sales_by_status)): ?>
                <h3>Orders by Status</h3>
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Count</th>
                            <th>Revenue</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sales_by_status as $row): ?>
                        <tr>
                            <td>
                                <span class="status-badge <?= strtolower($row['status']) ?>">
                                    <?= ucfirst($row['status']) ?>
                                </span>
                            </td>
                            <td><?= $row['count'] ?></td>
                            <td>LKR <?= number_format($row['total_amount'] ?? 0, 2) ?></td>
                            <td><?= $row['percentage'] ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <hr style="margin: 30px 0;">
                <?php endif; ?>

                <!-- Low Stock Alert (for Inventory report) -->
                <?php if ($report_type === 'inventory' && isset($low_stock) && count($low_stock) > 0): ?>
                <h3 style="color: #dc3545;"><i class="fas fa-exclamation-triangle"></i> Low Stock Alert</h3>
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Stock</th>
                            <th>Price</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($low_stock as $product): ?>
                        <tr>
                            <td><?= htmlspecialchars($product['name']) ?></td>
                            <td><strong><?= $product['stock'] ?></strong></td>
                            <td>LKR <?= number_format($product['price'], 2) ?></td>
                            <td>
                                <span class="status-badge <?= $product['stock'] == 0 ? 'cancelled' : 'pending' ?>">
                                    <?= $product['stock'] == 0 ? 'Out of Stock' : 'Low Stock' ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <hr style="margin: 30px 0;">
                <?php endif; ?>

                <!-- Main Report Table -->
                <h3>Detailed Report</h3>
                <?php if (count($report_data) > 0): ?>
                <table class="report-table">
                    <thead>
                        <tr>
                            <?php
                            // Dynamic table headers based on report type
                            if ($report_type === 'sales' || $report_type === 'revenue' || $report_type === 'monthly'):
                                echo '<th>Date</th><th>Orders</th><th>Revenue</th>';
                                if ($report_type === 'revenue'):
                                    echo '<th>Min Order</th><th>Max Order</th><th>Avg Order</th>';
                                endif;
                                echo '<th>Avg Order Value</th>';
                            elseif ($report_type === 'top-products'):
                                echo '<th>Product</th><th>Times Ordered</th><th>Qty Sold</th><th>Revenue</th><th>% of Sales</th>';
                            elseif ($report_type === 'inventory'):
                                echo '<th>Product Name</th><th>Price</th><th>Stock</th><th>Status</th><th>Inventory Value</th>';
                            elseif ($report_type === 'category'):
                                echo '<th>Category</th><th>Orders</th><th>Quantity</th><th>Revenue</th><th>Avg Price</th><th>Products</th>';
                            endif;
                            ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report_data as $row): ?>
                        <tr>
                            <?php
                            if ($report_type === 'sales' || $report_type === 'revenue' || $report_type === 'monthly'):
                                $date = isset($row['report_date']) ? $row['report_date'] : (isset($row['month']) ? $row['month'] : '');
                                echo '<td>' . htmlspecialchars($date) . '</td>';
                                echo '<td>' . (int)($row['order_count'] ?? 0) . '</td>';
                                echo '<td>LKR ' . number_format($row['daily_revenue'] ?? $row['revenue'] ?? 0, 2) . '</td>';
                                if ($report_type === 'revenue'):
                                    echo '<td>LKR ' . number_format($row['min_order'] ?? 0, 2) . '</td>';
                                    echo '<td>LKR ' . number_format($row['max_order'] ?? 0, 2) . '</td>';
                                    echo '<td>LKR ' . number_format($row['avg_order'] ?? 0, 2) . '</td>';
                                endif;
                                echo '<td>LKR ' . number_format($row['avg_order_value'] ?? 0, 2) . '</td>';
                            elseif ($report_type === 'top-products'):
                                echo '<td>' . htmlspecialchars($row['name']) . '</td>';
                                echo '<td>' . (int)$row['times_ordered'] . '</td>';
                                echo '<td>' . (int)$row['total_quantity'] . '</td>';
                                echo '<td>LKR ' . number_format($row['total_revenue'], 2) . '</td>';
                                echo '<td>' . $row['revenue_percentage'] . '%</td>';
                            elseif ($report_type === 'inventory'):
                                echo '<td>' . htmlspecialchars($row['name']) . '</td>';
                                echo '<td>LKR ' . number_format($row['price'], 2) . '</td>';
                                echo '<td><strong>' . $row['stock'] . '</strong></td>';
                                $stock_status = $row['stock_status'] ?? 'Good Stock';
                                $status_color = ($stock_status === 'Out of Stock') ? 'cancelled' : (($stock_status === 'Low Stock') ? 'pending' : 'shipped');
                                echo '<td><span class="status-badge ' . $status_color . '">' . $stock_status . '</span></td>';
                                echo '<td>LKR ' . number_format($row['inventory_value'], 2) . '</td>';
                            elseif ($report_type === 'category'):
                                echo '<td>' . htmlspecialchars($row['name']) . '</td>';
                                echo '<td>' . (int)($row['orders'] ?? 0) . '</td>';
                                echo '<td>' . (int)($row['total_quantity'] ?? 0) . '</td>';
                                echo '<td>LKR ' . number_format($row['total_revenue'] ?? 0, 2) . '</td>';
                                echo '<td>LKR ' . number_format($row['avg_price'] ?? 0, 2) . '</td>';
                                echo '<td>' . (int)($row['product_count'] ?? 0) . '</td>';
                            endif;
                            ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-inbox" style="font-size: 3rem; color: #ddd; margin-bottom: 10px;"></i>
                    <p>No data available for this report.</p>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <footer>
        <div class="container">
            <p>&copy; 2026 I Mobile.lk. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
