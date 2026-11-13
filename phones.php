<?php
require_once '../inc/db_connect.php';
$stmt = $pdo->prepare("SELECT * FROM products WHERE category_id = (SELECT id FROM categories WHERE slug = 'phones')");
$stmt->execute();
$phones = $stmt->fetchAll();
?>
<!-- Display $phones in product cards -->