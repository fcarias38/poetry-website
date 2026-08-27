<?php
include 'config.php'; // this file has your DB login

$data = json_decode(file_get_contents('php://input'), true);
$title = $data['title'];

if ($title) {
    $stmt = $conn->prepare("UPDATE poems SET likes = likes + 1 WHERE title = ?");
    $stmt->bind_param("s", $title);
    $stmt->execute();

    $stmt2 = $conn->prepare("SELECT likes FROM poems WHERE title = ?");
    $stmt2->bind_param("s", $title);
    $stmt2->execute();
    $stmt2->bind_result($likes);
    $stmt2->fetch();
    
    echo json_encode(['likes' => $likes]);
} else {
    echo json_encode(['error' => 'Missing title']);
}
?>
