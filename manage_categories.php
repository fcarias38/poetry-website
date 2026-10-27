<?php
require 'csrf.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
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
require 'categories.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$transaction = false;
try {
    require 'config.php';
    categories_init($conn);
    if ($method === 'GET') {
        $result = $conn->query("SELECT c.id, c.name, COUNT(p.id) AS poem_count
            FROM poem_categories c LEFT JOIN poems p
                ON CONVERT(TRIM(p.category) USING utf8mb4) COLLATE utf8mb4_unicode_ci = c.name
            GROUP BY c.id, c.name ORDER BY c.name");
        echo json_encode(['success' => true, 'categories' => $result->fetch_all(MYSQLI_ASSOC)]);
        exit;
    }
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) throw new InvalidArgumentException('Invalid request data.');
    $action = $data['action'] ?? '';
    if (!in_array($action, ['create', 'rename', 'delete'], true)) {
        throw new InvalidArgumentException('Unknown category action.');
    }
    $conn->begin_transaction();
    $transaction = true;
    if ($action === 'create') {
        $name = category_name(is_string($data['name'] ?? null) ? $data['name'] : '');
        $stmt = $conn->prepare('INSERT INTO poem_categories (name) VALUES (?)');
        $stmt->bind_param('s', $name);
        $stmt->execute();
    } else {
        $id = (int)($data['id'] ?? 0);
        $stmt = $conn->prepare('SELECT name FROM poem_categories WHERE id = ? FOR UPDATE');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $category = $stmt->get_result()->fetch_assoc();
        if (!$category) throw new InvalidArgumentException('This category no longer exists. Refresh and try again.');
        $oldName = $category['name'];
        if ($action === 'rename') {
            $name = category_name(is_string($data['name'] ?? null) ? $data['name'] : '');
            $stmt = $conn->prepare('UPDATE poem_categories SET name = ? WHERE id = ?');
            $stmt->bind_param('si', $name, $id);
            $stmt->execute();
            $stmt = $conn->prepare('UPDATE poems SET category = ? WHERE TRIM(category) = ?');
            $stmt->bind_param('ss', $name, $oldName);
            $stmt->execute();
        } else {
            $replacementId = (int)($data['replacement_id'] ?? 0);
            if ($replacementId === $id) throw new InvalidArgumentException('Choose a different category for these poems.');
            if ($replacementId > 0) {
                $stmt = $conn->prepare('SELECT name FROM poem_categories WHERE id = ? FOR UPDATE');
                $stmt->bind_param('i', $replacementId);
                $stmt->execute();
                $replacement = $stmt->get_result()->fetch_assoc();
                if (!$replacement) throw new InvalidArgumentException('The replacement category no longer exists.');
                $stmt = $conn->prepare('UPDATE poems SET category = ? WHERE TRIM(category) = ?');
                $stmt->bind_param('ss', $replacement['name'], $oldName);
                $stmt->execute();
            } else {
                $stmt = $conn->prepare('SELECT id FROM poems WHERE TRIM(category) = ? LIMIT 1 FOR UPDATE');
                $stmt->bind_param('s', $oldName);
                $stmt->execute();
                if ($stmt->get_result()->fetch_assoc()) {
                    throw new InvalidArgumentException('Choose where to move the poems before removing this category.');
                }
            }
            $stmt = $conn->prepare('DELETE FROM poem_categories WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
        }
    }
    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Throwable $error) {
    if ($transaction) $conn->rollback();
    $duplicate = $error instanceof mysqli_sql_exception && $error->getCode() === 1062;
    http_response_code($duplicate ? 409 : ($error instanceof InvalidArgumentException ? 422 : 500));
    if (!$duplicate && !($error instanceof InvalidArgumentException)) error_log('Category management: ' . $error->getMessage());
    echo json_encode(['success' => false, 'error' => $duplicate
        ? 'That category already exists. Choose another name, or move poems into the existing category.'
        : ($error instanceof InvalidArgumentException ? $error->getMessage() : 'Could not save categories. Please try again.')]);
}
