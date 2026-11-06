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

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
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
require 'categories.php';
try {
    $category = category_register($conn, $category);
} catch (Throwable $error) {
    http_response_code($error instanceof InvalidArgumentException ? 422 : 500);
    echo json_encode(['success' => false, 'error' => $error instanceof InvalidArgumentException
        ? $error->getMessage() : 'Could not save the category. Please try again.']);
    exit;
}

$metaDescParam = $metaDesc !== '' ? $metaDesc : null;
$metaKwParam   = $metaKeywords !== '' ? $metaKeywords : null;

$draftId = intval($_POST['draft_id'] ?? 0);
$transaction = false;
try {
    if ($draftId > 0) {
        require 'poem_drafts.php';
        poem_drafts_init($conn);
    }
    $conn->begin_transaction();
    $transaction = true;
    if ($draftId > 0) {
        $stmt = $conn->prepare('SELECT source_poem_id FROM poem_drafts WHERE id = ? FOR UPDATE');
        $stmt->bind_param('i', $draftId);
        $stmt->execute();
        $draft = $stmt->get_result()->fetch_assoc();
        if (!$draft) throw new OutOfBoundsException('Draft no longer exists. Check your poems before publishing again.');
        $id = intval($draft['source_poem_id'] ?? 0);
    }
    $updated = $id > 0;
    if ($updated) {
        $stmt = $conn->prepare('SELECT id FROM poems WHERE id = ? FOR UPDATE');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) throw new OutOfBoundsException('The original poem no longer exists. Your draft has been kept.');
        $slug = uniqueSlug($conn, makeSlug($title), $id);
        $stmt = $conn->prepare('UPDATE poems SET title = ?, body = ?, category = ?, slug = ?, meta_description = ?, meta_keywords = ? WHERE id = ?');
        $stmt->bind_param('ssssssi', $title, $body, $category, $slug, $metaDescParam, $metaKwParam, $id);
    } else {
        $slug = uniqueSlug($conn, makeSlug($title));
        $stmt = $conn->prepare('INSERT INTO poems (title, body, category, likes, slug, meta_description, meta_keywords) VALUES (?, ?, ?, 0, ?, ?, ?)');
        $stmt->bind_param('ssssss', $title, $body, $category, $slug, $metaDescParam, $metaKwParam);
    }
    $stmt->execute();
    if (!$updated) $id = $conn->insert_id;
    $stmt->close();
    if ($draftId > 0) {
        $stmt = $conn->prepare('DELETE FROM poem_drafts WHERE id = ?');
        $stmt->bind_param('i', $draftId);
        $stmt->execute();
        $stmt->close();
    }
    $conn->commit();
    $transaction = false;
    echo json_encode(['success' => true, 'id' => $id, 'slug' => $slug, 'updated' => $updated]);
} catch (Throwable $error) {
    if ($transaction) $conn->rollback();
    http_response_code($error instanceof OutOfBoundsException ? 404 : 500);
    error_log('Poem publish failed: ' . $error->getMessage());
    echo json_encode(['success' => false, 'error' => $error instanceof OutOfBoundsException
        ? $error->getMessage() : 'Could not publish the poem. Your draft has been kept. Please try again.']);
}
$conn->close();
