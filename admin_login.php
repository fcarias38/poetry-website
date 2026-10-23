<?php
session_start();
include 'admin_config.php';
include 'admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (
        strtolower($email) === strtolower($ADMIN_EMAIL) &&
        password_verify($password, admin_password_hash())
    ) {
        session_regenerate_id(true); // Prevent session fixation
        $_SESSION['logged_in'] = true;
        // Set an admin cookie so the .htaccess maintenance rule lets you bypass
        // the maintenance page and keep browsing the live site.
        setcookie('tr_admin', '1', [
            'expires'  => time() + 60 * 60 * 24 * 30, // 30 days
            'path'     => '/',
            'secure'   => true,
            'httponly' => false,
            'samesite' => 'Lax',
        ]);
        // Normal form POST → redirect so Chrome's password manager triggers.
        header('Location: /dashboard');
        exit;
    } else {
        // Redirect back to login with an error flag.
        header('Location: /login?error=1');
        exit;
    }
} else {
    header('Location: /login');
    exit;
}
?>
