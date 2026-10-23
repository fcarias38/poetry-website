<?php
/**
 * track_view.php — increments the view counter for a poem, deduplicated.
 * A view only counts once per visitor per poem per day. The visitor is
 * identified by a hash of IP + User-Agent (no cookies). Repeat opens by the
 * same person on the same day do NOT re-count.
 *
 * Accepts POST JSON { "id": N } or { "title": "..." }, or GET ?id=N.
 */
include 'config.php';
header('Content-Type: application/json');

$id    = null;
$title = null;

$raw = file_get_contents('php://input');
if ($raw) {
    $data = json_decode($raw, true);
    if (is_array($data)) {
        $id    = isset($data['id'])    ? intval($data['id'])  : null;
        $title = isset($data['title']) ? trim($data['title']) : null;
    }
}
if ($id === null && isset($_REQUEST['id']))       $id    = intval($_REQUEST['id']);
if ($title === null && isset($_REQUEST['title'])) $title = trim($_REQUEST['title']);

// Resolve to a poem id.
if (!$id && $title) {
    $stmt = $conn->prepare("SELECT id FROM poems WHERE title = ?");
    $stmt->bind_param("s", $title);
    $stmt->execute();
    $stmt->bind_result($resolvedId);
    if ($stmt->fetch()) { $id = intval($resolvedId); }
    $stmt->close();
}

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'Missing id or title']);
    $conn->close();
    exit;
}

// Build a cookieless visitor fingerprint for the day.
$ip   = $_SERVER['REMOTE_ADDR'] ?? '';
$ua   = $_SERVER['HTTP_USER_AGENT'] ?? '';
$hash = hash('sha256', $ip . '|' . $ua);
$today = date('Y-m-d');

// Try to record a unique view. The UNIQUE key (poem_id, visitor_hash, view_date)
// means a duplicate insert silently fails — that's how we dedup.
$counted = false;
$stmt = $conn->prepare("INSERT IGNORE INTO poem_views (poem_id, visitor_hash, view_date) VALUES (?, ?, ?)");
$stmt->bind_param("iss", $id, $hash, $today);
$stmt->execute();
if ($stmt->affected_rows > 0) {
    $counted = true; // first time this visitor saw this poem today
}
$stmt->close();

// Only bump the headline counter for a genuinely unique view.
if ($counted) {
    $up = $conn->prepare("UPDATE poems SET views = views + 1 WHERE id = ?");
    $up->bind_param("i", $id);
    $up->execute();
    $up->close();
}

echo json_encode(['success' => true, 'counted' => $counted]);
$conn->close();
?>
