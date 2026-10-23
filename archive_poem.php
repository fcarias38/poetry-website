<?php
/**
 * archive_poem.php — admin-only. Archive or unarchive a poem.
 * POST JSON: { "id": N, "archived": true|false }
 */
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['logged_in'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

include 'config.php';

$data     = json_decode(file_get_contents('php://input'), true);
$id       = isset($data['id']) ? intval($data['id']) : 0;
$archived = !empty($data['archived']) ? 1 : 0;

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'Missing id']);
    exit;
}

$stmt = $conn->prepare("UPDATE poems SET archived = ? WHERE id = ?");
$stmt->bind_param("ii", $archived, $id);
$ok = $stmt->execute();
$stmt->close();

echo json_encode(['success' => (bool)$ok, 'archived' => (bool)$archived, 'id' => $id]);
$conn->close();
?>
