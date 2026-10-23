<?php
/**
 * moderate_comment.php — admin-only. Approve or delete a pending comment.
 * POST JSON: { "id": N, "action": "approve" | "delete" }
 */
require 'csrf.php';   // starts the session
header('Content-Type: application/json');

if (empty($_SESSION['logged_in'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}
csrf_require();

include 'config.php';

$data   = json_decode(file_get_contents('php://input'), true);
$id     = isset($data['id']) ? intval($data['id']) : 0;
$action = $data['action'] ?? '';

if (!$id || !in_array($action, ['approve', 'delete'], true)) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

if ($action === 'approve') {
    $stmt = $conn->prepare("UPDATE comments SET approved = 1 WHERE id = ?");
} else {
    $stmt = $conn->prepare("DELETE FROM comments WHERE id = ?");
}
$stmt->bind_param("i", $id);
$ok = $stmt->execute();
$stmt->close();

echo json_encode(['success' => (bool)$ok, 'action' => $action, 'id' => $id]);
$conn->close();
?>
