<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    echo json_encode([]);
    exit;
}

include 'config.php';

if (!isset($_GET['id'])) {
    echo json_encode([]);
    exit;
}

$id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT title, category, body, meta_description, meta_keywords, archived FROM poems WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$poem = $result->fetch_assoc();

echo json_encode($poem ?: []);
?>
