<?php
/**
 * track_share.php — increments the share counter for a poem.
 * Accepts POST JSON { "id": N } or { "title": "..." }.
 * Fire-and-forget: always returns JSON.
 */
include 'config.php';
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

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

// Resolve legacy title requests to one poem, as likes and comments do.
if (!$id && $title) {
    $stmt = $conn->prepare('SELECT id FROM poems WHERE title = ? ORDER BY id LIMIT 1');
    $stmt->bind_param('s', $title);
    $stmt->execute();
    $stmt->bind_result($resolvedId);
    if ($stmt->fetch()) $id = (int) $resolvedId;
    $stmt->close();
}
if ($id) {
    $stmt = $conn->prepare("UPDATE poems SET shares = shares + 1 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $saved = $stmt->affected_rows === 1;
    $stmt->close();
    if (!$saved) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Poem not found']);
        $conn->close();
        exit;
    }
    require_once __DIR__ . '/notifications.php';
    notification_record($conn, 'share', $id);
} else {
    echo json_encode(['success' => false, 'error' => 'Missing id or title']);
    $conn->close();
    exit;
}

echo json_encode(['success' => true]);
$conn->close();
?>
