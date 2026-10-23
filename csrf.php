<?php
/**
 * csrf.php — minimal CSRF protection for admin-authenticated endpoints.
 *
 * Usage:
 *   require 'csrf.php';          // starts the session if needed
 *   $token = csrf_token();       // for embedding in a page / meta tag
 *   csrf_require();              // call at the top of a state-changing endpoint
 *
 * csrf_require() accepts the token from either the X-CSRF-Token request header
 * (used by fetch() JSON calls) or a `csrf_token` field in POST / JSON body.
 * On failure it sends 403 and exits.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_validate')) {
    function csrf_validate(?string $token): bool {
        if (empty($_SESSION['csrf_token']) || !is_string($token) || $token === '') {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}

if (!function_exists('csrf_require')) {
    function csrf_require(): void {
        // Header set by fetch() calls.
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

        // Fall back to form field or JSON body.
        if ($token === null) {
            if (isset($_POST['csrf_token'])) {
                $token = $_POST['csrf_token'];
            } else {
                $raw = file_get_contents('php://input');
                if ($raw) {
                    $data = json_decode($raw, true);
                    if (is_array($data) && isset($data['csrf_token'])) {
                        $token = $data['csrf_token'];
                    }
                }
            }
        }

        if (!csrf_validate(is_string($token) ? $token : null)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
            exit;
        }
    }
}
?>
