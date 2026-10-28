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

// Like/comment alerts use the admin email unless a different recipient is set.
$NOTIFICATION_EMAIL_ENABLED = true;
$NOTIFICATION_EMAIL = $ADMIN_EMAIL;
$NOTIFICATION_EMAIL_LIKES = true; // At most one per poem every 15 minutes.
$NOTIFICATION_EMAIL_COMMENTS = true; // Sent when a comment is submitted for review.
$NOTIFICATION_EMAIL_SHARES = true; // Sent when a reader uses the site's share options.
?>
