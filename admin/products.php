<?php
// admin/products.php
require_once '../inc/session.php';
if (!isAdmin()) redirect('../pages/index.php');
require_once '../inc/db_connect.php';

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: products.php');
    exit;
}

// Fetch all products
$products = $pdo->query("SELECT p.*, c.name AS category FROM products p JOIN categories c ON p.category_id = c.id")->fetchAll();
?>
<!-- Display table with edit/delete buttons -->