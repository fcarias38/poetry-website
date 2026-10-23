<?php
include 'config.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$id      = isset($data['id']) ? intval($data['id']) : 0;
$title   = trim($data['title'] ?? '');
$name    = trim($data['name'] ?? '');
$comment = trim($data['comment'] ?? '');
$ip      = $_SERVER['REMOTE_ADDR'] ?? '';

if (!$comment) {
    echo json_encode(['error' => 'Missing comment']);
    exit;
}

// Resolve the poem id. Prefer the explicit id (unique); fall back to title
// for older clients. Titles are not unique, so id is the correct key.
$poem_id = 0;
if ($id) {
    $stmt = $conn->prepare("SELECT id FROM poems WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($resolvedId);
    if ($stmt->fetch()) { $poem_id = intval($resolvedId); }
    $stmt->close();
} elseif ($title !== '') {
    $stmt = $conn->prepare("SELECT id FROM poems WHERE title = ? LIMIT 1");
    $stmt->bind_param("s", $title);
    $stmt->execute();
    $stmt->bind_result($resolvedId);
    if ($stmt->fetch()) { $poem_id = intval($resolvedId); }
    $stmt->close();
}

if (!$poem_id) {
    echo json_encode(['error' => 'Poem not found']);
    exit;
}

// Insert as PENDING (approved = 0). An admin approves it in the dashboard
// before it appears publicly — guards against spam / hate mail.
$stmt = $conn->prepare("INSERT INTO comments (poem_id, name, comment, ip_address, approved) VALUES (?, ?, ?, ?, 0)");
$stmt->bind_param("isss", $poem_id, $name, $comment, $ip);
$stmt->execute();
$stmt->close();

// Do NOT return the new comment — it's pending review. Return only the
// currently APPROVED comments plus a flag so the UI can say "pending".
$stmt = $conn->prepare("SELECT name, comment, created_at FROM comments WHERE poem_id = ? AND approved = 1 ORDER BY created_at DESC");
$stmt->bind_param("i", $poem_id);
$stmt->execute();
$result = $stmt->get_result();
$comments = [];
while ($row = $result->fetch_assoc()) {
    $comments[] = $row;
}

echo json_encode([
    'success'  => true,
    'pending'  => true,
    'message'  => 'Thank you — your comment is awaiting approval.',
    'comments' => $comments
]);
$conn->close();
?>
