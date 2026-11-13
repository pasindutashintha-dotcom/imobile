<?php
/**
 * Global utility functions for I Mobile.lk
 */

/**
 * Get all categories
 */
function getCategories($pdo) {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get products by category
 */
function getProductsByCategory($pdo, $category_id) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE category_id = ? AND status = 'active' ORDER BY name ASC");
    $stmt->execute([$category_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get single product by ID
 */
function getProduct($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get trending products
 */
function getTrendingProducts($pdo, $limit = 5) {
    $stmt = $pdo->query("SELECT * FROM products WHERE status = 'active' ORDER BY id DESC LIMIT " . (int)$limit);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get all products with pagination
 */
function getProducts($pdo, $page = 1, $limit = PRODUCTS_PER_PAGE) {
    $offset = ($page - 1) * $limit;
    $stmt = $pdo->query("
        SELECT * FROM products 
        WHERE status = 'active' 
        ORDER BY name ASC 
        LIMIT " . (int)$limit . " OFFSET " . (int)$offset
    );
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Count total products
 */
function countProducts($pdo) {
    return (int)$pdo->query("SELECT COUNT(*) FROM products WHERE status = 'active'")->fetchColumn();
}

/**
 * Get order by ID
 */
function getOrder($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get order items
 */
function getOrderItems($pdo, $order_id) {
    $stmt = $pdo->prepare("
        SELECT oi.*, p.name, p.image_url
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get user by ID
 */
function getUser($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get user by email
 */
function getUserByEmail($pdo, $email) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Check if product is in stock
 */
function isInStock($pdo, $product_id, $quantity = 1) {
    $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    return $product && $product['stock'] >= $quantity;
}

/**
 * Update product stock
 */
function updateProductStock($pdo, $product_id, $quantity) {
    $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
    return $stmt->execute([$quantity, $product_id]);
}

/**
 * Get dashboard statistics
 */
function getDashboardStats($pdo) {
    return [
        'total_products' => (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(),
        'total_orders' => (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
        'total_revenue' => (float)($pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status != 'cancelled'")->fetchColumn() ?? 0),
        'total_customers' => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 0")->fetchColumn(),
        'pending_orders' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn(),
        'low_stock_products' => (int)$pdo->query("SELECT COUNT(*) FROM products WHERE stock < 10 AND status = 'active'")->fetchColumn(),
    ];
}

/**
 * ============================================================================
 * REPORT FUNCTIONS - Sales, Customer, Inventory, Revenue Reports
 * ============================================================================
 */

/**
 * Get Sales Report - Orders and revenue summary
 */
function getSalesReport($pdo, $days = 30) {
    $start_date = date('Y-m-d', strtotime("-$days days"));
    
    $stmt = $pdo->prepare("
        SELECT 
            DATE(created_at) as report_date,
            COUNT(*) as order_count,
            SUM(total_amount) as daily_revenue,
            AVG(total_amount) as avg_order_value
        FROM orders
        WHERE created_at >= ? AND status != 'cancelled'
        GROUP BY DATE(created_at)
        ORDER BY report_date DESC
    ");
    $stmt->execute([$start_date . ' 00:00:00']);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get Sales by Status - Break down of orders by status
 */
function getSalesByStatus($pdo) {
    $stmt = $pdo->query("
        SELECT 
            status,
            COUNT(*) as count,
            SUM(total_amount) as total_amount,
            ROUND(COUNT(*) * 100 / (SELECT COUNT(*) FROM orders), 2) as percentage
        FROM orders
        GROUP BY status
        ORDER BY count DESC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get Top Products by Sales
 */
function getTopProductsBySales($pdo, $limit = 10) {
    $stmt = $pdo->query("
        SELECT 
            p.id,
            p.name,
            COUNT(oi.id) as times_ordered,
            SUM(oi.quantity) as total_quantity,
            SUM(oi.quantity * oi.price) as total_revenue,
            ROUND(SUM(oi.quantity * oi.price) * 100 / (SELECT SUM(total_amount) FROM orders), 2) as revenue_percentage
        FROM products p
        LEFT JOIN order_items oi ON p.id = oi.product_id
        GROUP BY p.id, p.name
        HAVING times_ordered > 0
        ORDER BY total_revenue DESC
        LIMIT " . (int)$limit
    );
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get Inventory Report - Stock levels and product status
 */
function getInventoryReport($pdo) {
    $stmt = $pdo->query("
        SELECT 
            p.id,
            p.name,
            p.category_id,
            p.price,
            COALESCE(p.stock, 0) as stock,
            CASE 
                WHEN COALESCE(p.stock, 0) = 0 THEN 'Out of Stock'
                WHEN COALESCE(p.stock, 0) < 5 THEN 'Low Stock'
                WHEN COALESCE(p.stock, 0) < 20 THEN 'Medium Stock'
                ELSE 'Good Stock'
            END as stock_status,
            COALESCE(p.stock, 0) * COALESCE(p.price, 0) as inventory_value,
            p.created_at
        FROM products p
        ORDER BY COALESCE(p.stock, 0) ASC, p.name ASC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get Low Stock Products - Products with stock below threshold
 */
function getLowStockProducts($pdo, $threshold = 10) {
    $stmt = $pdo->prepare("
        SELECT 
            id,
            name,
            COALESCE(stock, 0) as stock,
            price
        FROM products
        WHERE COALESCE(stock, 0) < ?
        ORDER BY COALESCE(stock, 0) ASC, name ASC
    ");
    $stmt->execute([$threshold]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get Revenue Report - Detailed revenue analysis
 */
function getRevenueReport($pdo, $days = 30) {
    $start_date = date('Y-m-d', strtotime("-$days days"));
    
    $stmt = $pdo->prepare("
        SELECT 
            DATE(created_at) as report_date,
            COUNT(*) as order_count,
            SUM(total_amount) as daily_revenue,
            MIN(total_amount) as min_order,
            MAX(total_amount) as max_order,
            ROUND(AVG(total_amount), 2) as avg_order,
            COUNT(DISTINCT user_id) as unique_customers
        FROM orders
        WHERE created_at >= ? AND status != 'cancelled'
        GROUP BY DATE(created_at)
        ORDER BY report_date DESC
    ");
    $stmt->execute([$start_date . ' 00:00:00']);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get Revenue by Status - Revenue breakdown by order status
 */
function getRevenueByStatus($pdo) {
    $stmt = $pdo->query("
        SELECT 
            status,
            COUNT(*) as order_count,
            SUM(total_amount) as total_revenue,
            ROUND(SUM(total_amount) * 100 / (SELECT SUM(total_amount) FROM orders), 2) as revenue_percentage,
            ROUND(AVG(total_amount), 2) as avg_revenue
        FROM orders
        GROUP BY status
        ORDER BY total_revenue DESC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get Category Performance - Sales by category
 */
function getCategoryPerformance($pdo) {
    $stmt = $pdo->query("
        SELECT 
            c.id,
            c.name,
            COUNT(DISTINCT oi.order_id) as orders,
            SUM(oi.quantity) as total_quantity,
            SUM(oi.quantity * oi.price) as total_revenue,
            ROUND(AVG(oi.price), 2) as avg_price,
            COUNT(p.id) as product_count
        FROM categories c
        LEFT JOIN products p ON c.id = p.category_id
        LEFT JOIN order_items oi ON p.id = oi.product_id
        GROUP BY c.id, c.name
        ORDER BY total_revenue DESC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get Overall Summary - Key business metrics
 */
function getOverallSummary($pdo, $days = 30) {
    $start_date = date('Y-m-d', strtotime("-$days days"));
    
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT id) as total_orders,
            COUNT(DISTINCT user_id) as unique_customers,
            SUM(total_amount) as total_revenue,
            ROUND(AVG(total_amount), 2) as avg_order_value,
            MAX(total_amount) as highest_order,
            MIN(total_amount) as lowest_order
        FROM orders
        WHERE created_at >= ? AND status != 'cancelled'
    ");
    $stmt->execute([$start_date . ' 00:00:00']);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Generate monthly performance data
 */
function getMonthlyPerformance($pdo, $months = 12) {
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as order_count,
            SUM(total_amount) as revenue,
            COUNT(DISTINCT user_id) as unique_customers
        FROM orders
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL $months MONTH)
            AND status != 'cancelled'
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month DESC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>

