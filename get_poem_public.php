<?php
include 'config.php';

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing id']);
    exit;
}

$id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT id, title, category, body, likes FROM poems WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$poem = $result->fetch_assoc();

if (!$poem) {
    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
    exit;
}

// Fetch APPROVED comments only, matching the moderation behavior of the
// other public endpoints.
$stmt2 = $conn->prepare(
    "SELECT name, comment, created_at FROM comments WHERE poem_id = ? AND approved = 1 ORDER BY created_at ASC"
);
$stmt2->bind_param("i", $id);
$stmt2->execute();
$cResult = $stmt2->get_result();

$comments = [];
while ($row = $cResult->fetch_assoc()) {
    $comments[] = $row;
}

$poem['comments'] = $comments;

header('Content-Type: application/json');
echo json_encode($poem);

$conn->close();
?>
