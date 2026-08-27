<?php
session_start();
include 'admin_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if ($username === $ADMIN_USER && $password === $ADMIN_PASS) {
        $_SESSION['logged_in'] = true;
        echo 'success'; // send plain "success"
        exit;
    } else {
        echo 'error'; // send plain "error"
        exit;
    }
} else {
    echo 'error';
    exit;
}
?>
