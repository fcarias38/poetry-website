<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    echo json_encode(['success' => false]);
    exit;
}

include 'config.php';

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$id = intval($_GET['id']);

$stmt = $conn->prepare("DELETE FROM poems WHERE id = ?");
$stmt->bind_param("i", $id);
$success = $stmt->execute();

echo json_encode(['success' => $success]);
?>
