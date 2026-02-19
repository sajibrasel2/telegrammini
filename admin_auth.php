<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/config.php';

function admin_is_logged_in(): bool {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function admin_require_login(): void {
    if (!admin_is_logged_in()) {
        header('Location: admin_login.php');
        exit;
    }
}
?>
