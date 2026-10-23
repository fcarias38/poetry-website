<?php
// Copy this file to admin_config.php and set your admin credentials.
// admin_config.php is git-ignored so your real values never get committed.

$ADMIN_EMAIL = 'you@example.com';

// Your admin password in plaintext. It is hashed once and cached in
// admin_pass.hash (also git-ignored); it is never hashed on every request.
$ADMIN_PASSWORD = 'change-me';

// Populated automatically by the "Forgot password" flow. Leave empty.
$ADMIN_PASS_HASH = '';

// Outgoing mail sender — must be a real mailbox on your domain.
$MAIL_FROM = 'noreply@example.com';
?>
