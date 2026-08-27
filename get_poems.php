<?php
include 'config.php';

// Fetch all poems
$sql = "SELECT id, title, body, category FROM poems ORDER BY id DESC";
$result = $conn->query($sql);

$poems = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $poems[] = $row;
    }
}

echo json_encode($poems);

$conn->close();
?>
