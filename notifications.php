<?php
/** Notification failures must never reject a reader's like, comment, or share. */
function notifications_init(mysqli $conn): void {
    $conn->query("CREATE TABLE IF NOT EXISTS admin_notifications (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        kind VARCHAR(10) NOT NULL, poem_id INT UNSIGNED NOT NULL,
        poem_title VARCHAR(255) NOT NULL, actor VARCHAR(100) NOT NULL DEFAULT '',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        read_at TIMESTAMP NULL DEFAULT NULL, KEY unread (read_at, id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function notification_record(mysqli $conn, string $kind, int $poemId, string $actor = ''): void {
    try {
        notifications_init($conn);
        $stmt = $conn->prepare('INSERT INTO admin_notifications (kind, poem_id, poem_title, actor) SELECT ?, id, title, ? FROM poems WHERE id = ?');
        $stmt->bind_param('ssi', $kind, $actor, $poemId);
        $stmt->execute();
        $inserted = $stmt->affected_rows;
        $stmt->close();
        if ($inserted === 1) notification_email($conn, $kind, $poemId, $actor);
    } catch (Throwable $error) {
        error_log('Think Room notification failed: ' . $error->getMessage());
    }
}

function notification_email(mysqli $conn, string $kind, int $poemId, string $actor): void {
    if (!is_file(__DIR__ . '/admin_config.php')) return;
    require __DIR__ . '/admin_config.php';
    if (isset($NOTIFICATION_EMAIL_ENABLED) && !$NOTIFICATION_EMAIL_ENABLED) return;
    if ($kind === 'like' && isset($NOTIFICATION_EMAIL_LIKES) && !$NOTIFICATION_EMAIL_LIKES) return;
    if ($kind === 'comment' && isset($NOTIFICATION_EMAIL_COMMENTS) && !$NOTIFICATION_EMAIL_COMMENTS) return;
    if ($kind === 'share' && isset($NOTIFICATION_EMAIL_SHARES) && !$NOTIFICATION_EMAIL_SHARES) return;
    $to = $NOTIFICATION_EMAIL ?? $ADMIN_EMAIL ?? '';
    $from = $MAIL_FROM ?? '';
    foreach ([$to, $from] as $address) {
        if (!is_string($address) || preg_match('/[\r\n]/', $address) || !filter_var($address, FILTER_VALIDATE_EMAIL)) {
            error_log('Think Room notification email requires valid recipient and sender addresses.');
            return;
        }
    }
    // Limit like email bursts; every like still appears in the dashboard.
    if ($kind === 'like') {
        $conn->query("CREATE TABLE IF NOT EXISTS notification_email_limits (
            poem_id INT UNSIGNED NOT NULL PRIMARY KEY, last_sent DATETIME NULL
        ) ENGINE=InnoDB");
        $stmt = $conn->prepare('INSERT IGNORE INTO notification_email_limits (poem_id) VALUES (?)');
        $stmt->bind_param('i', $poemId);
        $stmt->execute();
        $stmt->close();
        $stmt = $conn->prepare('UPDATE notification_email_limits SET last_sent = UTC_TIMESTAMP() WHERE poem_id = ? AND (last_sent IS NULL OR last_sent <= UTC_TIMESTAMP() - INTERVAL 15 MINUTE)');
        $stmt->bind_param('i', $poemId);
        $stmt->execute();
        $claimed = $stmt->affected_rows;
        $stmt->close();
        if ($claimed !== 1) return;
    }
    $stmt = $conn->prepare('SELECT title FROM poems WHERE id = ?');
    $stmt->bind_param('i', $poemId);
    $stmt->execute();
    $title = $stmt->get_result()->fetch_assoc()['title'] ?? 'your poem';
    $stmt->close();
    $subject = $kind === 'comment' ? 'Think Room: new comment awaiting review' : 'Think Room: a poem received a like';
    $body = $kind === 'comment'
        ? ($actor !== '' ? $actor : 'Anonymous') . " left a comment on \"$title\".\n\nThe comment is awaiting your approval."
        : "A reader liked \"$title\".\n\nAdditional likes appear in your dashboard. Like emails are limited to one per poem every 15 minutes.";
    $body .= "\n\nOpen your dashboard: https://freddycarias.com/dashboard\n";
    if ($kind === 'share') {
        $subject = 'Think Room: a reader shared a poem';
        $body = "A reader used the share options for \"$title\".\n\nThis records sharing or copying a poem link on your site; it cannot confirm a post on another service.\n\nOpen your dashboard: https://freddycarias.com/dashboard\n";
    }
    if (!mail($to, $subject, $body, "From: Think Room <$from>\r\nContent-Type: text/plain; charset=UTF-8")) {
        error_log('Think Room notification email was not accepted by the mail server.');
        if ($kind === 'like') {
            $stmt = $conn->prepare('UPDATE notification_email_limits SET last_sent = NULL WHERE poem_id = ?');
            $stmt->bind_param('i', $poemId);
            $stmt->execute();
            $stmt->close();
        }
    }
}
