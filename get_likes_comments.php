<?php
include 'config.php';

$title = $_GET['title'] ?? '';

if ($title) {
    // Get poem ID and likes
    $stmt = $conn->prepare("SELECT id, likes FROM poems WHERE title = ?");
    $stmt->bind_param("s", $title);
    $stmt->execute();
    $stmt->bind_result($poem_id, $likes);
    $stmt->fetch();
    $stmt->close();

    if ($poem_id) {
        // Fetch comments with timestamps
        $stmt = $conn->prepare("SELECT name, comment, created_at FROM comments WHERE poem_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $poem_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $comments = [];
        while ($row = $result->fetch_assoc()) {
            $comments[] = $row;
        }

        echo json_encode(['likes' => $likes, 'comments' => $comments]);
    } else {
        echo json_encode(['error' => 'Poem not found']);
    }
} else {
    echo json_encode(['error' => 'Missing title']);
}
?>
