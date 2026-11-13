<?php
require_once '../inc/session.php';
require_once '../inc/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Validation
    if (empty($full_name) || empty($email) || empty($phone) || empty($password) || empty($password_confirm)) {
        $_SESSION['signup_error'] = 'Please fill in all fields.';
        header('Location: signup.php');
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['signup_error'] = 'Please enter a valid email address.';
        header('Location: signup.php');
        exit;
    }

    if (strlen($password) < 6) {
        $_SESSION['signup_error'] = 'Password must be at least 6 characters long.';
        header('Location: signup.php');
        exit;
    }

    if ($password !== $password_confirm) {
        $_SESSION['signup_error'] = 'Passwords do not match.';
        header('Location: signup.php');
        exit;
    }

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $_SESSION['signup_error'] = 'Email address already registered.';
        header('Location: signup.php');
        exit;
    }

    // Hash password and insert user
    try {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, is_admin) VALUES (?, ?, ?, 0)");
        $stmt->execute([$full_name, $email, $hashed_password]);

        $_SESSION['signup_success'] = 'Account created successfully! You can now log in.';
        header('Location: ../signin.php');
        exit;
    } catch (Exception $e) {
        $_SESSION['signup_error'] = 'Error creating account: ' . $e->getMessage();
        header('Location: signup.php');
        exit;
    }
} else {
    header('Location: signup.php');
    exit;
}
?>
