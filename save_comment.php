<?php
include 'config.php';

$data = json_decode(file_get_contents('php://input'), true);

$title = trim($data['title']);
$name = trim($data['name'] ?? '');
$comment = trim($data['comment']);
$ip = $_SERVER['REMOTE_ADDR'];

if ($title && $comment) {
    // Get poem ID
    $stmt = $conn->prepare("SELECT id FROM poems WHERE title = ?");
    $stmt->bind_param("s", $title);
    $stmt->execute();
    $stmt->bind_result($poem_id);
    $stmt->fetch();
    $stmt->close();

    if ($poem_id) {
        // Insert comment into the new table
        $stmt = $conn->prepare("INSERT INTO comments (poem_id, name, comment, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $poem_id, $name, $comment, $ip);
        $stmt->execute();
        $stmt->close();

        // Fetch updated comments
     $stmt = $conn->prepare("SELECT name, comment, created_at FROM comments WHERE poem_id = ? ORDER BY created_at DESC");

        $stmt->bind_param("i", $poem_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $comments = [];
        while ($row = $result->fetch_assoc()) {
            $comments[] = $row;
        }

        echo json_encode(['comments' => $comments]);
    } else {
        echo json_encode(['error' => 'Poem not found']);
    }
} else {
    echo json_encode(['error' => 'Missing title or comment']);
}
?>
