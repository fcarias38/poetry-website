<?php
include 'config.php';

header('Content-Type: application/json');

// Fetch all poems
$sql    = "SELECT id, title, body, category, slug FROM poems ORDER BY id DESC";
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
