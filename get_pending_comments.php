<?php
/**
 * get_pending_comments.php — admin-only. Lists comments awaiting approval.
 */
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['logged_in'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

include 'config.php';

$sql = "
    SELECT c.id, c.name, c.comment, c.created_at, c.ip_address,
           p.title AS poem_title
    FROM comments c
    LEFT JOIN poems p ON p.id = c.poem_id
    WHERE c.approved = 0
    ORDER BY c.created_at DESC
";
$res = $conn->query($sql);

$pending = [];
while ($res && $row = $res->fetch_assoc()) {
    $pending[] = $row;
}

echo json_encode(['success' => true, 'pending' => $pending, 'count' => count($pending)]);
$conn->close();
?>
