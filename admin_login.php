<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/admin_auth.php';

if (admin_is_logged_in()) {
    header('Location: admin_dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if (!defined('ADMIN_LOGIN_EMAIL') || !defined('ADMIN_LOGIN_PASSWORD_HASH')) {
        $error = 'Admin credentials are not configured.';
    } else {
        if ($email === ADMIN_LOGIN_EMAIL && password_verify($password, ADMIN_LOGIN_PASSWORD_HASH)) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_email'] = $email;
            header('Location: admin_dashboard.php');
            exit;
        }
        $error = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - PCN</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php include 'app_style.php'; ?>
</head>
<body>
    <div class="app-container" style="max-width: 460px;">
        <div class="app-card" style="text-align: center;">
            <div style="width: 70px; height: 70px; margin: 0 auto 15px; border-radius: 18px; background: rgba(0,242,255,0.08); display:flex; align-items:center; justify-content:center;">
                <i class="fas fa-user-shield" style="color: var(--primary); font-size: 1.8rem;"></i>
            </div>
            <h1 style="font-size: 1.5rem; margin-bottom: 5px;">Admin Login</h1>
            <p style="color: var(--text-dim); margin-bottom: 20px;">Sign in to manage tasks, ads, and users</p>

            <?php if ($error): ?>
                <div class="app-card" style="background: rgba(255,71,87,0.08); border: 1px solid rgba(255,71,87,0.25); text-align:left;">
                    <div style="color: var(--danger); font-weight: 600;"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" style="display: grid; gap: 12px; text-align:left;">
                <div>
                    <label style="display:block; font-size: 0.85rem; color: var(--text-dim); margin-bottom: 6px;">Email</label>
                    <input name="email" type="email" required placeholder="admin@example.com" style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.2); color: white;">
                </div>
                <div>
                    <label style="display:block; font-size: 0.85rem; color: var(--text-dim); margin-bottom: 6px;">Password</label>
                    <input name="password" type="password" required placeholder="••••••••" style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.2); color: white;">
                </div>
                <button class="app-btn" type="submit">Sign In</button>
            </form>
        </div>
    </div>
</body>
</html>
