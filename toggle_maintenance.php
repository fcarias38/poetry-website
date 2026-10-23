<?php
require 'csrf.php';   // starts the session
header('Content-Type: application/json');

// Must be a logged-in admin
if (empty($_SESSION['logged_in'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$flagFile = __DIR__ . '/maintenance.flag';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require(); // only the state-changing POST needs a token
    $body   = json_decode(file_get_contents('php://input'), true);
    $enable = isset($body['enable']) ? (bool)$body['enable'] : null;

    if ($enable === null) {
        echo json_encode(['success' => false, 'error' => 'Missing "enable" parameter']);
        exit;
    }

    if ($enable) {
        // Turn maintenance ON — create the flag file.
        $written = @file_put_contents($flagFile, date('Y-m-d H:i:s'));
        if ($written === false) {
            // Most likely a directory-permissions problem on the server.
            echo json_encode([
                'success' => false,
                'error'   => 'Could not write maintenance flag. Check that the web directory is writable by PHP.',
                'active'  => file_exists($flagFile),
            ]);
            exit;
        }
    } else {
        // Turn maintenance OFF — remove the flag file.
        if (file_exists($flagFile) && !@unlink($flagFile)) {
            echo json_encode([
                'success' => false,
                'error'   => 'Could not remove maintenance flag. Check file permissions.',
                'active'  => file_exists($flagFile),
            ]);
            exit;
        }
    }

    echo json_encode(['success' => true, 'active' => file_exists($flagFile)]);
    exit;
}

// GET — return current state
echo json_encode(['success' => true, 'active' => file_exists($flagFile)]);
