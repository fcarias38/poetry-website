<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit;
}

include 'config.php';

if (!isset($_GET['id'])) {
    echo "No poem ID provided.";
    exit;
}

$id = intval($_GET['id']);

// Fetch existing poem
$stmt = $conn->prepare("SELECT title, category, body FROM poems WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$poem = $result->fetch_assoc();

if (!$poem) {
    echo "Poem not found.";
    exit;
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $category = trim($_POST['category']);
    $body = trim($_POST['body']);

    $stmt = $conn->prepare("UPDATE poems SET title = ?, category = ?, body = ? WHERE id = ?");
    $stmt->bind_param("sssi", $title, $category, $body, $id);

    if ($stmt->execute()) {
        header("Location: dashboard.php?success=1");
        exit;
    } else {
        echo "Error updating poem.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Poem</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-light">
<div class="container py-5">
    <h1>Edit Poem</h1>
    <form method="POST">
        <div class="mb-3">
            <label>Title</label>
            <input class="form-control bg-dark text-light" name="title" value="<?= htmlspecialchars($poem['title']) ?>" required>
        </div>
        <div class="mb-3">
            <label>Category</label>
            <input class="form-control bg-dark text-light" name="category" value="<?= htmlspecialchars($poem['category']) ?>" required>
        </div>
        <div class="mb-3">
            <label>Body</label>
            <textarea class="form-control bg-dark text-light" name="body" rows="10" required><?= htmlspecialchars($poem['body']) ?></textarea>
        </div>
        <button class="btn btn-outline-light">Update</button>
        <a href="dashboard.php" class="btn btn-outline-secondary">Cancel</a>
    </form>
</div>
</body>
</html>
