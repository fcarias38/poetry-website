<?php
include 'config.php'; // this file has your DB login

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$id    = isset($data['id'])    ? intval($data['id'])  : 0;
$title = isset($data['title']) ? trim($data['title']) : '';

// Resolve to a poem id. Prefer the id (unique); fall back to title for
// backward compatibility with older clients. Titles are NOT unique, so the
// id path is the correct one.
if (!$id && $title !== '') {
    $stmt = $conn->prepare("SELECT id FROM poems WHERE title = ? LIMIT 1");
    $stmt->bind_param("s", $title);
    $stmt->execute();
    $stmt->bind_result($resolvedId);
    if ($stmt->fetch()) { $id = intval($resolvedId); }
    $stmt->close();
}

if (!$id) {
    echo json_encode(['error' => 'Missing id or title']);
    $conn->close();
    exit;
}

$stmt = $conn->prepare("UPDATE poems SET likes = likes + 1 WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

$stmt2 = $conn->prepare("SELECT likes FROM poems WHERE id = ?");
$stmt2->bind_param("i", $id);
$stmt2->execute();
$stmt2->bind_result($likes);
$stmt2->fetch();
$stmt2->close();

echo json_encode(['likes' => intval($likes)]);
$conn->close();
?>
