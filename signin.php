<?php require_once 'inc/session.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In</title>
    <link rel="stylesheet" href="assets/css/styles.css">
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
                    <li><a href="signin.php" class="active">LogIn</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <section class="signin-section">
            <div class="container">
                <div class="signin-container">
                    <div class="signin-card">
                        <div class="signin-header">
                            <i class="fas fa-user-circle"></i>
                            <h2>Welcome Back</h2>
                            <p>Sign in to your account</p>
                        </div>

                        <!-- Show error if login fails -->
                        <?php if (isset($_SESSION['login_error'])): ?>
                            <div class="error" style="color:red; text-align:center; margin-bottom:15px; padding:10px; background:#ffe5e5; border-radius:5px;">
                                <?= htmlspecialchars($_SESSION['login_error']); unset($_SESSION['login_error']); ?>
                            </div>
                        <?php endif; ?>

                        <!-- Show success message from signup -->
                        <?php if (isset($_SESSION['signup_success'])): ?>
                            <div class="success" style="color:green; text-align:center; margin-bottom:15px; padding:10px; background:#e5ffe5; border-radius:5px;">
                                <?= htmlspecialchars($_SESSION['signup_success']); unset($_SESSION['signup_success']); ?>
                            </div>
                        <?php endif; ?>

                        <!-- SUBMIT TO PHP BACKEND -->
                        <form action="auth/login.php" method="POST">
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" placeholder="example@email.com" required>
                            </div>
                            <div class="form-group">
                                <label for="password">Password</label>
                                <input type="password" id="password" name="password" placeholder="Enter your password" required>
                            </div>
                            <button type="submit" class="btn btn-signin">Log In</button>
                        </form>
                        <div class="signup-link">
                            <p>Don't have an account? <a href="auth/signup.php">Create one</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2026 I Mobile.lk. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>