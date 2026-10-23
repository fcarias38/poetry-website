<?php
require 'csrf.php';   // starts the session
header('Content-Type: application/json');

if (!isset($_SESSION['logged_in'])) {
    echo json_encode(['success' => false]);
    exit;
}
csrf_require();

include 'config.php';

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$id = intval($_GET['id']);

// Remove dependent rows first so we don't leave orphaned comments/views.
// poem_views may not exist on every install, so tolerate its absence.
if ($stmt = $conn->prepare("DELETE FROM comments WHERE poem_id = ?")) {
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}
if ($stmt = $conn->prepare("DELETE FROM poem_views WHERE poem_id = ?")) {
    $stmt->bind_param("i", $id);
    @$stmt->execute();
    $stmt->close();
}

$stmt = $conn->prepare("DELETE FROM poems WHERE id = ?");
$stmt->bind_param("i", $id);
$success = $stmt->execute();
$stmt->close();

echo json_encode(['success' => (bool)$success]);
$conn->close();
?>
