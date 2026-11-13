<?php
require_once '../inc/session.php';
require_once '../inc/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $_SESSION['login_error'] = 'Please fill in all fields.';
        header('Location: ../signin.php');
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['is_admin'] = $user['is_admin'];

        if ($user['is_admin']) {
            header('Location: ../db.php');
        } else {
            header('Location: ../index.php');
        }
        exit;
    } else {
        $_SESSION['login_error'] = 'Invalid email or password.';
        header('Location: ../signin.php');
        exit;
    }
} else {
    header('Location: ../signin.php');
    exit;
}
?>