<?php
require 'csrf.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');
if (empty($_SESSION['logged_in'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}
$method = $_SERVER['REQUEST_METHOD'];
if (!in_array($method, ['GET', 'POST'], true)) {
    http_response_code(405);
    header('Allow: GET, POST');
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}
if ($method === 'POST') csrf_require();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require 'poem_drafts.php';
try {
    require 'config.php';
    poem_drafts_init($conn);
    if ($method === 'GET') {
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $stmt = $conn->prepare('SELECT * FROM poem_drafts WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $draft = $stmt->get_result()->fetch_assoc();
            if (!$draft) throw new OutOfBoundsException('Draft no longer exists.');
            echo json_encode(['success' => true, 'draft' => $draft]);
        } else {
            $result = $conn->query('SELECT id, title, category, updated_at FROM poem_drafts ORDER BY updated_at DESC, id DESC');
            echo json_encode(['success' => true, 'drafts' => $result->fetch_all(MYSQLI_ASSOC)]);
        }
        exit;
    }
    $action = $_POST['action'] ?? 'save';
    $id = intval($_POST['draft_id'] ?? 0);
    if (!in_array($action, ['save', 'delete'], true)) throw new InvalidArgumentException('Invalid draft action.');
    if ($id > 0) {
        $stmt = $conn->prepare('SELECT id FROM poem_drafts WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) throw new OutOfBoundsException('Draft no longer exists.');
    }
    if ($action === 'delete') {
        if ($id < 1) throw new InvalidArgumentException('Choose a draft to delete.');
        $stmt = $conn->prepare('DELETE FROM poem_drafts WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        echo json_encode(['success' => true]);
        exit;
    }
    // Preserve unfinished writing exactly, including leading/trailing line breaks.
    $title = $_POST['title'] ?? '';
    $category = $_POST['category'] ?? '';
    $body = $_POST['body'] ?? '';
    $description = $_POST['meta_description'] ?? '';
    $keywords = $_POST['meta_keywords'] ?? '';
    if (!is_string($title) || !is_string($category) || !is_string($body)
        || !is_string($description) || !is_string($keywords)) throw new InvalidArgumentException('Invalid draft text.');
    if (trim($title) === '' && trim($body) === '') throw new InvalidArgumentException('Write a title or a few words before saving a draft.');
    if (!preg_match('/^.{0,255}$/us', $title) || !preg_match('/^.{0,100}$/us', $category)) throw new InvalidArgumentException('Use a shorter title or category.');
    if ($id > 0) {
        $stmt = $conn->prepare('UPDATE poem_drafts SET title = ?, category = ?, body = ?, meta_description = ?, meta_keywords = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
        $stmt->bind_param('sssssi', $title, $category, $body, $description, $keywords, $id);
    } else {
        $source = intval($_POST['id'] ?? 0) ?: null;
        if ($source !== null) {
            $stmt = $conn->prepare('SELECT id FROM poems WHERE id = ?');
            $stmt->bind_param('i', $source);
            $stmt->execute();
            if (!$stmt->get_result()->fetch_assoc()) throw new OutOfBoundsException('The original poem no longer exists.');
        }
        $stmt = $conn->prepare('INSERT INTO poem_drafts (source_poem_id, title, category, body, meta_description, meta_keywords) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('isssss', $source, $title, $category, $body, $description, $keywords);
    }
    $stmt->execute();
    if (!$id) $id = $conn->insert_id;
    echo json_encode(['success' => true, 'id' => $id]);
} catch (Throwable $error) {
    http_response_code($error instanceof InvalidArgumentException ? 422 : ($error instanceof OutOfBoundsException ? 404 : 500));
    if (!($error instanceof InvalidArgumentException) && !($error instanceof OutOfBoundsException)) error_log('Draft operation failed: ' . $error->getMessage());
    echo json_encode(['success' => false, 'error' => ($error instanceof InvalidArgumentException || $error instanceof OutOfBoundsException)
        ? $error->getMessage() : 'Could not save or load drafts. Please try again.']);
}
