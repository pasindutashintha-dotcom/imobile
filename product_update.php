<?php
require_once 'inc/session.php';
require_once 'inc/db_connect.php';

if (!isset($_SESSION['user_id']) || (int)$_SESSION['is_admin'] !== 1) {
    header('Location: signin.php');
    exit;
}

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';
$success = '';

if ($product_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        header('Location: db.php');
        exit;
    }
} else {
    header('Location: db.php');
    exit;
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $specs = trim($_POST['specs'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    if ($name === '' || $category_id <= 0 || $specs === '' || $price <= 0 || $stock < 0) {
        $error = 'Please fill all required fields correctly.';
    } else {
        $image_url = $product['image_url'] ?? '';

        if (!empty($_FILES['image']['name'])) {
            $upload_dir = 'assets/images/';
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
        }

        if ($error === '') {
            $stmt = $pdo->prepare("UPDATE products SET category_id = ?, name = ?, description = ?, specs = ?, price = ?, stock = ?, image_url = ? WHERE id = ?");
            $stmt->execute([$category_id, $name, $description, $specs, $price, $stock, $image_url, $product_id]);
            $success = 'Product updated successfully!';
            $product = array_merge($product, [
                'category_id' => $category_id,
                'name' => $name,
                'description' => $description,
                'specs' => $specs,
                'price' => $price,
                'stock' => $stock,
                'image_url' => $image_url,
            ]);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Product</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .form-container { max-width: 700px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 5px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .error { color: red; margin-bottom: 10px; }
        .success { color: green; margin-bottom: 10px; }
        .btn-submit { background: #e94560; color: #fff; padding: 12px 25px; border: none; border-radius: 30px; cursor: pointer; font-weight: bold; }
        .btn-submit:hover { background: #c72a48; }
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
            <h2><i class="fas fa-edit"></i> Update Product</h2>
            <?php if ($error !== ''): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if ($success !== ''): ?><div class="success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

            <form action="product_update.php?id=<?= (int)$product_id ?>" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name">Product Name *</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($product['name'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="category_id">Category *</label>
                    <select id="category_id" name="category_id" required>
                        <option value="">-- Select --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= (int)$cat['id'] ?>" <?= ((int)($product['category_id'] ?? 0) === (int)$cat['id']) ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="specs">Specifications *</label>
                    <input type="text" id="specs" name="specs" value="<?= htmlspecialchars($product['specs'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="price">Price (LKR) *</label>
                    <input type="number" step="0.01" id="price" name="price" value="<?= htmlspecialchars((string)($product['price'] ?? 0)) ?>" required>
                </div>

                <div class="form-group">
                    <label for="stock">Stock Quantity *</label>
                    <input type="number" id="stock" name="stock" value="<?= (int)($product['stock'] ?? 0) ?>" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label for="image">New Product Image</label>
                    <input type="file" id="image" name="image" accept="image/*">
                </div>

                <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Update Product</button>
                <a href="db.php" class="btn" style="background:#6c757d; margin-left:10px;">Cancel</a>
            </form>
        </div>
    </div>
</body>
</html>
