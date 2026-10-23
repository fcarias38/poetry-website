<?php
/**
 * track_share.php — increments the share counter for a poem.
 * Accepts POST JSON { "id": N } or { "title": "..." }.
 * Fire-and-forget: always returns JSON.
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

if ($id) {
    $stmt = $conn->prepare("UPDATE poems SET shares = shares + 1 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
} elseif ($title) {
    $stmt = $conn->prepare("UPDATE poems SET shares = shares + 1 WHERE title = ?");
    $stmt->bind_param("s", $title);
    $stmt->execute();
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Missing id or title']);
    $conn->close();
    exit;
}

echo json_encode(['success' => true]);
$conn->close();
?>
