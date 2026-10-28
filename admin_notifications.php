<?php
// Passive polling must not extend an idle administrator's session.
define('ADMIN_SESSION_BACKGROUND', ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET');
require_once __DIR__ . '/admin_session.php';
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
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}
require_once __DIR__ . '/csrf.php';
if ($method === 'POST') csrf_require();
session_write_close();
try {
    require __DIR__ . '/config.php';
    require_once __DIR__ . '/notifications.php';
    $conn->query("SET time_zone = '+00:00'");
    notifications_init($conn);
    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $through = filter_var($data['through_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($through === false) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'A valid through_id is required']);
            exit;
        }
        $stmt = $conn->prepare('UPDATE admin_notifications SET read_at = CURRENT_TIMESTAMP WHERE id <= ? AND read_at IS NULL');
        $stmt->bind_param('i', $through);
        $stmt->execute();
        $stmt->close();
    }
    // Consistent badge/feed snapshot, even when new activity arrives during this request.
    $conn->begin_transaction(MYSQLI_TRANS_START_READ_ONLY);
    $summary = $conn->query('SELECT COUNT(*) AS unread_count, COALESCE(MAX(id), 0) AS latest_id FROM admin_notifications WHERE read_at IS NULL')->fetch_assoc();
    $rows = $conn->query("SELECT id, kind, poem_id, poem_title, actor,
        DATE_FORMAT(created_at, '%Y-%m-%dT%H:%i:%sZ') AS created_at, read_at
        FROM admin_notifications ORDER BY (read_at IS NULL) DESC, id DESC LIMIT 40")->fetch_all(MYSQLI_ASSOC);
    $conn->commit();
    echo json_encode(['success' => true, 'unread_count' => (int) $summary['unread_count'], 'latest_id' => (int) $summary['latest_id'], 'notifications' => $rows]);
} catch (Throwable $error) {
    error_log('Think Room notification feed failed: ' . $error->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Could not load notifications. Please try again.']);
}
