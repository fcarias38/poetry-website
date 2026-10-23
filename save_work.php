<?php
require 'csrf.php';   // starts the session
header('Content-Type: application/json');

// Admin only.
if (empty($_SESSION['logged_in'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}
csrf_require();

include 'config.php';

/** Convert a title to a URL-safe slug. */
function makeSlug(string $title): string {
    $slug = strtolower(trim($title));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    return trim($slug, '-');
}

/** Return a slug that doesn't already exist (ignoring the poem being edited). */
function uniqueSlug(mysqli $conn, string $base, int $excludeId = 0): string {
    $candidate = $base !== '' ? $base : 'poem';
    $n = 2;
    while (true) {
        $stmt = $conn->prepare("SELECT id FROM poems WHERE slug = ? AND id != ?");
        $stmt->bind_param("si", $candidate, $excludeId);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 0) { $stmt->close(); return $candidate; }
        $stmt->close();
        $candidate = $base . '-' . $n++;
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$id          = isset($_POST['id']) && $_POST['id'] !== '' ? intval($_POST['id']) : 0;
$title       = trim($_POST['title'] ?? '');
$category    = trim($_POST['category'] ?? '');
$body        = trim($_POST['body'] ?? '');
$metaDesc    = trim($_POST['meta_description'] ?? '');
$metaKeywords= trim($_POST['meta_keywords'] ?? '');

if ($title === '' || $category === '' || $body === '') {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

// Normalise empty SEO fields to NULL so poem.php falls back to auto-generation.
$metaDescParam = $metaDesc !== '' ? $metaDesc : null;
$metaKwParam   = $metaKeywords !== '' ? $metaKeywords : null;

if ($id > 0) {
    // ── UPDATE existing poem (fixes the old duplicate-on-edit bug) ──
    $slug = uniqueSlug($conn, makeSlug($title), $id);
    $stmt = $conn->prepare(
        "UPDATE poems SET title = ?, body = ?, category = ?, slug = ?, meta_description = ?, meta_keywords = ? WHERE id = ?"
    );
    $stmt->bind_param("ssssssi", $title, $body, $category, $slug, $metaDescParam, $metaKwParam, $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'id' => $id, 'slug' => $slug, 'updated' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database update failed']);
    }
    $stmt->close();
} else {
    // ── INSERT new poem ──
    $slug = uniqueSlug($conn, makeSlug($title));
    $stmt = $conn->prepare(
        "INSERT INTO poems (title, body, category, likes, slug, meta_description, meta_keywords) VALUES (?, ?, ?, 0, ?, ?, ?)"
    );
    $stmt->bind_param("ssssss", $title, $body, $category, $slug, $metaDescParam, $metaKwParam);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'id' => $conn->insert_id, 'slug' => $slug, 'updated' => false]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database insert failed']);
    }
    $stmt->close();
}

$conn->close();
exit;
?>
