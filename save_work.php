<?php
include 'config.php'; // Connect to your database

header('Content-Type: application/json'); // important for JS fetch

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $category = trim($_POST['category']);
    $body = trim($_POST['body']);

    if (!empty($title) && !empty($category) && !empty($body)) {
        $stmt = $conn->prepare("INSERT INTO poems (title, body, category, likes) VALUES (?, ?, ?, 0)");
        $stmt->bind_param("sss", $title, $body, $category);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database insert failed']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
exit;
