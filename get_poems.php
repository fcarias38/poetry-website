<?php
include 'config.php';

header('Content-Type: application/json');

// Fetch all poems
$sql    = "SELECT p.id, p.title, p.body, p.category, p.slug, p.likes,
                  (SELECT COUNT(*) FROM comments c WHERE c.poem_id = p.id AND c.approved = 1) AS comment_count
           FROM poems p ORDER BY p.id DESC";
$result = $conn->query($sql);

$poems = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $poems[] = $row;
    }
}

echo json_encode($poems);

$conn->close();
?>
