<?php
/**
 * maintenance_check.php
 * Include at the top of every public-facing page.
 * Admins who are already logged in always bypass maintenance mode.
 */

// Start session only if one hasn't been started yet
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$maintenanceFlagFile = __DIR__ . '/maintenance.flag';

if (file_exists($maintenanceFlagFile) && empty($_SESSION['logged_in'])) {
    // Render the maintenance page in place with a proper 503 status.
    // (A 503 paired with a Location header is non-standard and many clients
    // won't follow it, so we serve the page directly instead of redirecting.)
    http_response_code(503);
    header('Retry-After: 3600');
    $maintenancePage = __DIR__ . '/maintenance.php';
    if (is_file($maintenancePage)) {
        include $maintenancePage;
    } else {
        echo 'The site is temporarily down for maintenance. Please check back soon.';
    }
    exit;
}
?>
