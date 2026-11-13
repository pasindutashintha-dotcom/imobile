<?php
require_once 'inc/session.php';
require_once 'inc/db_connect.php';

// Admin check
if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    header('Location: signin.php');
    exit;
}

// Fetch categories for dropdown
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $category_id = (int)$_POST['category_id'];
    $specs = trim($_POST['specs']);
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock'];
    $description = trim($_POST['description']);
    $image_url = '';

    // Basic validation
    if (empty($name) || $category_id == 0 || empty($specs) || $price <= 0 || $stock < 0) {
        $error = 'Please fill all required fields correctly.';
    } else {
        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'assets/images/';
            // Ensure directory exists
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $file_name = basename($_FILES['image']['name']);
            $target_path = $upload_dir . $file_name;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                $image_url = $file_name;
            } else {
                $error = 'Failed to upload image.';
            }
        } else {
            $error = 'Please select an image.';
        }

        // Insert if no error
        if (empty($error)) {
            $stmt = $pdo->prepare("
                INSERT INTO products (category_id, name, description, specs, price, stock, image_url)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$category_id, $name, $description, $specs, $price, $stock, $image_url]);
            $success = 'Product added successfully!';
            // Redirect after 2 seconds
            header('Refresh: 2; url=db.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .form-container { max-width: 600px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        .form-container h2 { margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 5px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .form-group textarea { height: 80px; }
        .btn-submit { background: #e94560; color: #fff; padding: 12px 30px; border: none; border-radius: 30px; cursor: pointer; font-weight: bold; }
        .btn-submit:hover { background: #c72a48; }
        .error { color: red; margin-bottom: 10px; }
        .success { color: green; margin-bottom: 10px; }
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
                    <li><a href="auth/logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="form-container">
            <h2><i class="fas fa-plus-circle"></i> Add New Product</h2>

            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form action="add_product.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name">Product Name *</label>
                    <input type="text" id="name" name="name" required>
                </div>

                <div class="form-group">
                    <label for="category_id">Category *</label>
                    <select id="category_id" name="category_id" required>
                        <option value="">-- Select --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="specs">Specifications *</label>
                    <input type="text" id="specs" name="specs" required>
                </div>

                <div class="form-group">
                    <label for="price">Price (LKR) *</label>
                    <input type="number" step="0.01" id="price" name="price" required>
                </div>

                <div class="form-group">
                    <label for="stock">Stock Quantity *</label>
                    <input type="number" id="stock" name="stock" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description"></textarea>
                </div>

                <div class="form-group">
                    <label for="image">Product Image *</label>
                    <input type="file" id="image" name="image" accept="image/*" required>
                </div>

                <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Save Product</button>
                <a href="db.php" class="btn" style="background:#6c757d; margin-left:10px;">Cancel</a>
            </form>
        </div>
    </div>

    <footer>
        <div class="container"><p>&copy; 2026 I Mobile.lk. All rights reserved.</p></div>
    </footer>
</body>
</html>