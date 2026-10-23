<?php
include 'config.php';

header('Content-Type: application/json');

$id    = isset($_GET['id']) ? intval($_GET['id']) : 0;
$title = $_GET['title'] ?? '';

// Resolve poem id + likes. Prefer id (unique); fall back to title.
$poem_id = 0;
$likes   = 0;
if ($id) {
    $stmt = $conn->prepare("SELECT id, likes FROM poems WHERE id = ?");
    $stmt->bind_param("i", $id);
} elseif ($title !== '') {
    $stmt = $conn->prepare("SELECT id, likes FROM poems WHERE title = ? LIMIT 1");
    $stmt->bind_param("s", $title);
} else {
    echo json_encode(['error' => 'Missing id or title']);
    $conn->close();
    exit;
}

$stmt->execute();
$stmt->bind_result($resolvedId, $resolvedLikes);
if ($stmt->fetch()) {
    $poem_id = intval($resolvedId);
    $likes   = intval($resolvedLikes);
}
$stmt->close();

if (!$poem_id) {
    echo json_encode(['error' => 'Poem not found']);
    $conn->close();
    exit;
}

// Fetch APPROVED comments only (moderation)
$stmt = $conn->prepare("SELECT name, comment, created_at FROM comments WHERE poem_id = ? AND approved = 1 ORDER BY created_at DESC");
$stmt->bind_param("i", $poem_id);
$stmt->execute();
$result = $stmt->get_result();

$comments = [];
while ($row = $result->fetch_assoc()) {
    $comments[] = $row;
}

echo json_encode(['likes' => $likes, 'comments' => $comments]);
$conn->close();
?>
