<?php
require_once 'inc/session.php';
require_once 'inc/db_connect.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// Check if logged in and admin
if (!isset($_SESSION['user_id']) || empty($_SESSION['is_admin']) || (int)$_SESSION['is_admin'] !== 1) {
    header('Location: signin.php');
    exit;
}

$currentUserName = isset($_SESSION['full_name']) && $_SESSION['full_name'] !== '' ? $_SESSION['full_name'] : 'Admin';

// Handle status change via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'change_status') {
        $order_id = isset($_POST['order_id']) ? (int)trim($_POST['order_id']) : 0;
        $new_status = isset($_POST['status']) ? strtolower(trim($_POST['status'])) : '';
        
        
        if (!$order_id || !$new_status) {
            echo json_encode(['success' => false, 'message' => 'Invalid order ID or status. ID: ' . $order_id . ', Status: ' . $new_status]);
            exit;
        }
        
        $allowed_statuses = ['pending', 'paid', 'shipped', 'delivered', 'cancelled'];
        if (!in_array($new_status, $allowed_statuses)) {
            echo json_encode(['success' => false, 'message' => 'Invalid status: ' . $new_status]);
            exit;
        }
        
        try {
            // First check if order exists
            $checkStmt = $pdo->prepare("SELECT id, status FROM orders WHERE id = ?");
            $checkStmt->execute([$order_id]);
            $existingOrder = $checkStmt->fetch();
            
            if (!$existingOrder) {
                echo json_encode(['success' => false, 'message' => 'Order not found']);
                exit;
            }
            
            // Check if status is already this value
            if (strtolower($existingOrder['status']) === $new_status) {
                echo json_encode(['success' => true, 'message' => 'Already set to ' . $new_status]);
                exit;
            }
            
            // Only update if status is different
            $stmt = $pdo->prepare("UPDATE orders SET status = :status WHERE id = :id");
            $stmt->execute([':status' => $new_status, ':id' => $order_id]);
            
            echo json_encode(['success' => true, 'message' => 'Updated to ' . $new_status]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($_POST['action'] === 'delete_order') {
        $order_id = isset($_POST['order_id']) ? (int)trim($_POST['order_id']) : 0;

        if (!$order_id) {
            echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
            exit;
        }

        try {
            $pdo->beginTransaction();

            $deleteItemsStmt = $pdo->prepare("DELETE FROM order_items WHERE order_id = ?");
            $deleteItemsStmt->execute([$order_id]);

            $deleteOrderStmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
            $deleteOrderStmt->execute([$order_id]);

            $pdo->commit();

            echo json_encode(['success' => true, 'message' => 'Order deleted successfully']);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit;
    }

    if ($_POST['action'] === 'bulk_update_status') {
        $from_status = isset($_POST['from_status']) ? strtolower(trim($_POST['from_status'])) : '';
        $to_status = isset($_POST['to_status']) ? strtolower(trim($_POST['to_status'])) : '';
        
        if (!$from_status || !$to_status) {
            echo json_encode(['success' => false, 'message' => 'Invalid status values']);
            exit;
        }
        
        $allowed_statuses = ['pending', 'shipped', 'delivered'];
        if (!in_array($from_status, $allowed_statuses) || !in_array($to_status, $allowed_statuses)) {
            echo json_encode(['success' => false, 'message' => 'Invalid status']);
            exit;
        }
        
        try {
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE status = ?");
            $stmt->execute([$to_status, $from_status]);
            $rowCount = $stmt->rowCount();
            
            echo json_encode(['success' => true, 'message' => "Updated $rowCount order(s) from $from_status to $to_status"]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit;
    }
    
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

// Stats
$totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalOrders   = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalRevenue  = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE status != 'cancelled'")->fetchColumn();
$totalCustomers = $pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 0")->fetchColumn();

// Recent orders (latest 20) with product names aggregated safely per order
$recentOrders = $pdo->query(" 
    SELECT 
        o.id,
        o.order_number,
        o.customer_name,
        o.customer_address,
        o.customer_phone,
        o.total_amount,
        o.created_at,
        o.status,
        COALESCE(item_summary.product_names, 'No items') AS product_names
    FROM orders o
    LEFT JOIN (
        SELECT 
            oi.order_id,
            GROUP_CONCAT(DISTINCT CONCAT(p.name, ' (x', oi.quantity, ')') SEPARATOR ', ') AS product_names
        FROM order_items oi
        LEFT JOIN products p ON p.id = oi.product_id
        GROUP BY oi.order_id
    ) item_summary ON item_summary.order_id = o.id
    ORDER BY o.created_at DESC, o.id DESC
    LIMIT 20
")->fetchAll();

$recentOrders = array_values($recentOrders);

// Process orders to ensure lowercase status
foreach ($recentOrders as &$order) {
    $order['status'] = strtolower(trim($order['status']));
}

// Products for mini grid with all products from the database
$products = $pdo->query("SELECT id, name, price, stock, image_url FROM products ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Dashboard - I Mobile.lk</title>
    <link rel="stylesheet" href="assets/css/styles.css" />
    <link rel="stylesheet" href="db-style.css" />
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
                    <li><a href="items.php">Items</a></li>
                    <li><a href="about.php">About</a></li>
                    <li><a href="auth/logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="dashboard-wrapper">
        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                <h2><i class="fas fa-user-shield"></i> Admin</h2>
            </div>
            <ul class="sidebar-nav">
                <li><a href="#" class="active" data-section="dashboard"><i class="fas fa-th-large"></i> Dashboard</a></li>
                <li><a href="#" data-section="products"><i class="fas fa-box"></i> Product <span class="badge"><?= $totalProducts ?></span></a></li>
                <li><a href="#" data-section="orders"><i class="fas fa-shopping-cart"></i> Order <span class="badge"><?= $totalOrders ?></span></a></li>
                <li><a href="#" data-section="reports"><i class="fas fa-chart-bar"></i> Report</a></li>
                <li><a href="auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
            <div class="sidebar-footer">
                <i class="fas fa-copyright"></i> 2026 I Mobile.lk
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="dashboard-main">
            <!-- HEADER -->
            <div class="dashboard-header">
                <h1>
                    <i class="fas fa-tachometer-alt" style="color:#e94560; margin-right:10px;"></i>
                    Dashboard
                    <small>Welcome back, <?= htmlspecialchars($currentUserName) ?>!</small>
                </h1>
                <div class="admin-profile">
                    <img src="https://ui-avatars.com/api/?name=Admin&background=e94560&color=fff&size=40" alt="Admin" />
                    <div>
                        <div class="admin-name">Admin</div>
                        <div class="admin-role">Super Admin</div>
                    </div>
                </div>
            </div>

            <!-- STATS CARDS -->
            <section class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-box"></i></div>
                    <div class="stat-number"><?= $totalProducts ?></div>
                    <div class="stat-label">Total Products</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-shopping-bag"></i></div>
                    <div class="stat-number"><?= $totalOrders ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
                    <div class="stat-number">LKR <?= number_format($totalRevenue ?? 0, 2) ?></div>
                    <div class="stat-label">Revenue</div>
                </div>
               
            </section>

            <!-- PRODUCT SECTION -->
            <section id="section-products" class="section-card">
                <div class="section-title">
                    <i class="fas fa-box"></i> Product Management
                    <a href="add_product.php" class="action-link"><i class="fas fa-plus"></i> Add New</a>
                </div>
                <div class="mini-product-grid">
                    <?php foreach ($products as $p): ?>
                        <div class="mini-product-card">
                            <img src="assets/images/<?= htmlspecialchars($p['image_url']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" />
                            <h4><?= htmlspecialchars($p['name']) ?></h4>
                            <div class="mini-price">LKR <?= number_format((float)($p['price'] ?? 0), 0) ?></div>
                            <div class="mini-stock" style="font-weight:600; color:#333; margin-top:4px;">Stock: <?= (int)($p['stock'] ?? 0) ?></div>
                            <a href="product_update.php?id=<?= (int)$p['id'] ?>" class="action-link" style="display:inline-block; margin-top:8px;"><i class="fas fa-edit"></i> Edit</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- ORDER SECTION -->
            <section id="section-orders" class="section-card">
                <div class="section-title">
                    <i class="fas fa-shopping-cart"></i> Recent Orders
                    <a href="#" class="action-link">View All <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Address</th>
                                <th>Contact No</th>
                                <th>Products</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recentOrders)): ?>
                                <?php foreach ($recentOrders as $index => $order): ?>
                                    <tr data-order-id="<?= (int)$order['id'] ?>" data-row-index="<?= $index ?>">
                                        <td><strong><?= htmlspecialchars($order['order_number']) ?></strong></td>
                                        <td><?= htmlspecialchars($order['customer_name']) ?></td>
                                        <td><?= htmlspecialchars($order['customer_address']) ?></td>
                                        <td><?= htmlspecialchars($order['customer_phone']) ?></td>
                                        <td><?= htmlspecialchars($order['product_names']) ?></td>
                                        <td>LKR <?= number_format((float)$order['total_amount'], 2) ?></td>
                                        <td>
                                            <span class="status-badge <?= htmlspecialchars($order['status']) ?>" title="<?= htmlspecialchars($order['status']) ?>">
                                                <?= ucfirst(htmlspecialchars($order['status'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="table-actions">
                                                <button class="edit-btn" type="button" title="Edit Status"><i class="fas fa-edit"></i></button>
                                                <button class="delete-btn" type="button" title="Delete"><i class="fas fa-trash"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="8" style="text-align:center;">No orders placed yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- REPORT SECTION -->
            <section id="section-reports" class="section-card">
                <div class="section-title">
                    <i class="fas fa-chart-bar"></i> Reports
                    <a href="admin/reports.php" class="action-link"><i class="fas fa-arrow-right"></i> View All Reports</a>
                </div>
                <div class="report-grid">
                    <a href="admin/reports.php?type=sales" class="report-item" style="text-decoration: none; color: inherit; cursor: pointer;">
                        <i class="fas fa-file-invoice"></i>
                        <h4>Sales Report</h4>
                        <p>View daily sales summary & trends</p>
                    </a>
                    <a href="admin/reports.php?type=inventory" class="report-item" style="text-decoration: none; color: inherit; cursor: pointer;">
                        <i class="fas fa-boxes"></i>
                        <h4>Inventory Report</h4>
                        <p>Stock levels & reorder alerts</p>
                    </a>
                    <a href="admin/reports.php?type=revenue" class="report-item" style="text-decoration: none; color: inherit; cursor: pointer;">
                        <i class="fas fa-credit-card"></i>
                        <h4>Revenue Report</h4>
                        <p>Income breakdown & analysis</p>
                    </a>
                </div>
            </section>
        </main>
    </div>

    <footer>
        <div class="container">
            <p>&copy; 2026 I Mobile.lk. All rights reserved.</p>
        </div>
    </footer>

    <!-- Status Change Modal -->
    <div id="statusModal" class="modal" style="display:none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-edit"></i> Change Order Status</h2>
                <span class="close-modal" onclick="closeStatusModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="statusSelect">Select New Status:</label>
                    <select id="statusSelect">
                        <option value="">-- Select Status --</option>
                        <option value="pending">Pending</option>
                        <option value="paid">Paid</option>
                        <option value="shipped">Shipped</option>
                        <option value="delivered">Delivered</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="form-group">
                    <small id="currentStatus" style="color:#666;"></small>
                </div>
            </div>
            <div class="modal-footer">
                <button onclick="closeStatusModal()" class="btn-cancel">Cancel</button>
                <button onclick="submitStatusChange()" class="btn-submit">Update Status</button>
            </div>
        </div>
    </div>

    <style>
        .modal {
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            width: 90%;
            max-width: 400px;
            animation: slideIn 0.3s ease-out;
        }
        
        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .modal-header h2 {
            margin: 0;
            font-size: 1.2rem;
            color: #333;
        }
        
        .close-modal {
            font-size: 28px;
            font-weight: bold;
            color: #aaa;
            cursor: pointer;
            transition: color 0.3s;
        }
        
        .close-modal:hover {
            color: #000;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .modal-body .form-group {
            margin-bottom: 15px;
        }
        
        .modal-body label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }
        
        .modal-body select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
        }
        
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding: 20px;
            border-top: 1px solid #eee;
        }
        
        .btn-cancel {
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn-cancel:hover {
            background: #5a6268;
        }
        
        .btn-submit {
            background: #e94560;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn-submit:hover {
            background: #c72a48;
        }

        .bulk-status-controls {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-left: auto;
            margin-right: 12px;
            flex-wrap: wrap;
        }

        .bulk-status-controls select {
            padding: 8px 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.9rem;
            min-width: 100px;
        }

        .bulk-btn {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            background: #e94560;
            color: #fff;
            cursor: pointer;
        }

        .bulk-btn:hover {
            background: #c72a48;
        }
    </style>

    

    <!-- JavaScript for sidebar navigation, report clicks, edit/delete (demos) -->
    <script>
        // Sidebar navigation
        const navLinks = document.querySelectorAll('.sidebar-nav a');
        const sections = {
            dashboard: document.querySelector('.dashboard-header'),
            products: document.getElementById('section-products'),
            orders: document.getElementById('section-orders'),
            reports: document.getElementById('section-reports')
        };

        function showSection(sectionId) {
            if (sectionId === 'dashboard') {
                window.scrollTo({ top: 0, behavior: 'smooth' });
                return;
            }
            const target = sections[sectionId];
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        navLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                navLinks.forEach(l => l.classList.remove('active'));
                this.classList.add('active');
                const section = this.getAttribute('data-section');
                showSection(section);
            });
        });

        // Report item clicks
        document.querySelectorAll('.report-item').forEach(item => {
            item.addEventListener('click', function() {
                const title = this.querySelector('h4')?.textContent || 'Report';
                alert(`📊 Generating "${title}" ...\n(This is a demo action.)`);
            });
        });

        // Edit / Delete buttons with status change functionality
        let currentOrderId = null;
        let currentOrderStatus = null;

        function bindOrderActions() {
            document.addEventListener('click', function(event) {
                const editBtn = event.target.closest('.edit-btn');
                if (editBtn) {
                    event.stopPropagation();
                    const row = editBtn.closest('tr');
                    if (!row) return;

                    const statusCell = row.querySelector('.status-badge');
                    const modal = document.getElementById('statusModal');
                    const statusSelect = document.getElementById('statusSelect');
                    const statusLabel = document.getElementById('currentStatus');

                    currentOrderStatus = statusCell ? statusCell.textContent.trim().toLowerCase() : '';
                    currentOrderId = row.getAttribute('data-order-id');

                    if (statusSelect) {
                        statusSelect.value = currentOrderStatus || '';
                    }
                    if (statusLabel) {
                        statusLabel.textContent = 'Current Status: ' + (currentOrderStatus || 'unknown');
                    }
                    if (modal) {
                        modal.style.display = 'flex';
                    }
                    return;
                }

                const deleteBtn = event.target.closest('.delete-btn');
                if (deleteBtn) {
                    event.stopPropagation();
                    if (!confirm('⚠️ Are you sure you want to delete this order?')) {
                        return;
                    }

                    const row = deleteBtn.closest('tr');
                    const orderId = row?.getAttribute('data-order-id');

                    if (!orderId) {
                        alert('Error: Order ID not found');
                        return;
                    }

                    const formData = new FormData();
                    formData.append('action', 'delete_order');
                    formData.append('order_id', orderId);

                    fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('🗑️ ' + data.message);
                            location.reload();
                        } else {
                            alert('❌ Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Delete error:', error);
                        alert('Error deleting order');
                    });
                }
            });
        }

        bindOrderActions();

        // Modal functions
        function closeStatusModal() {
            document.getElementById('statusModal').style.display = 'none';
            currentOrderId = null;
            currentOrderStatus = null;
        }

        function submitStatusChange() {
            const newStatus = document.getElementById('statusSelect').value;
            
            if (!newStatus) {
                alert('Please select a status');
                return;
            }
            
            if (!currentOrderId) {
                alert('Error: Order ID not found');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'change_status');
            formData.append('order_id', currentOrderId);
            formData.append('status', newStatus);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Status updated to: ' + newStatus);
                    setTimeout(() => {
                        location.reload();
                    }, 500);
                } else {
                    alert('❌ Error: ' + data.message);
                    console.error('Server response:', data);
                }
            })
            .catch(error => {
                alert('Error: ' + error.message);
                console.error('Fetch error:', error);
            });
        }

        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('statusModal');
            if (event.target === modal) {
                closeStatusModal();
            }
        });

        // Bulk Status Update
        function submitBulkStatusUpdate() {
            const fromStatus = document.getElementById('bulkFromStatus').value;
            const toStatus = document.getElementById('bulkToStatus').value;
            
            if (!fromStatus || !toStatus) {
                alert('⚠️ Please select both "From" and "To" statuses');
                return;
            }
            
            if (fromStatus === toStatus) {
                alert('⚠️ "From" and "To" statuses must be different');
                return;
            }
            
            if (!confirm(`Are you sure you want to update all "${fromStatus}" orders to "${toStatus}"?`)) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'bulk_update_status');
            formData.append('from_status', fromStatus);
            formData.append('to_status', toStatus);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ ' + data.message);
                    location.reload();
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating statuses');
            });
        }

        // Add product
        document.querySelector('.action-link')?.addEventListener('click', function(e) {
            // Already links to add_product.php, so no need to prevent default.
            // This demo alert is optional – remove if you want.
        });

        console.log('✅ Admin Dashboard loaded.');
    </script>
</body>
</html>