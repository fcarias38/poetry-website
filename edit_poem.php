<?php
require 'csrf.php';   // starts the session
if (!isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit;
}

include 'config.php';

/**
 * Convert a title to a URL-safe slug.
 */
function makeSlug(string $title): string {
    $slug = strtolower(trim($title));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    return trim($slug, '-');
}

/**
 * Return a slug that doesn't already exist in the poems table,
 * excluding the poem currently being edited.
 */
function uniqueSlug(mysqli $conn, string $base, int $excludeId = 0): string {
    $candidate = $base;
    $n = 2;
    while (true) {
        $stmt = $conn->prepare("SELECT id FROM poems WHERE slug = ? AND id != ?");
        $stmt->bind_param("si", $candidate, $excludeId);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 0) {
            return $candidate;
        }
        $candidate = $base . '-' . $n++;
    }
}

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
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        echo "Invalid CSRF token.";
        exit;
    }
    $title = trim($_POST['title']);
    $category = trim($_POST['category']);
    $body = trim($_POST['body']);

    $slug = uniqueSlug($conn, makeSlug($title), $id);

    $stmt = $conn->prepare("UPDATE poems SET title = ?, category = ?, body = ?, slug = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $title, $category, $body, $slug, $id);

    if ($stmt->execute()) {
        header("Location: edit_poem.php?id=" . $id . "&saved=1", true, 303);
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
    <?php if (isset($_GET['saved'])): ?>
        <p class="alert alert-success" role="status">Poem updated.</p>
    <?php endif; ?>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">
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
