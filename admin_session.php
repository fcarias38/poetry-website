<?php
/** Shared session startup and optional, revocable seven-day remembered login. */
const ADMIN_REMEMBER_SECONDS = 7 * 24 * 60 * 60;
const ADMIN_SESSION_IDLE_SECONDS = 30 * 60;
const ADMIN_SESSION_MAX_SECONDS = 8 * 60 * 60;

function admin_cookie(string $name, string $value, int $expires): void {
    setcookie($name, $value, [
        'expires' => $expires,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function admin_credential_fingerprint(): string {
    return (string) hash_file('sha256', __DIR__ . '/admin_config.php');
}

function admin_remember_path(string $token): ?string {
    if (!preg_match('/\A[a-f0-9]{64}\z/D', $token)) {
        return null;
    }
    return __DIR__ . '/admin_tokens/' . hash('sha256', $token) . '.php';
}

function admin_remember_forget(): void {
    $path = admin_remember_path((string) ($_COOKIE['tr_remember'] ?? ''));
    if ($path !== null && is_file($path)) {
        unlink($path);
    }
    admin_cookie('tr_remember', '', time() - 3600);
    admin_cookie('tr_admin', '', time() - 3600);
    unset($_COOKIE['tr_remember'], $_SESSION['remember_expires']);
}

function admin_remember_issue(): bool {
    admin_remember_forget();
    $directory = __DIR__ . '/admin_tokens';
    if (!is_dir($directory) && !@mkdir($directory, 0700, true) && !is_dir($directory)) {
        error_log('Unable to create admin remembered-login storage.');
        return false;
    }
    // Remove expired records whenever a new remembered login is created.
    foreach (glob($directory . '/*.php') ?: [] as $old) {
        if (filemtime($old) < time() - ADMIN_REMEMBER_SECONDS) {
            @unlink($old);
        }
    }
    $token = bin2hex(random_bytes(32));
    $expires = time() + ADMIN_REMEMBER_SECONDS;
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    $record = [
        'expires' => $expires,
        'credential' => admin_credential_fingerprint(),
        'csrf_token' => $_SESSION['csrf_token'],
    ];
    // PHP guard also prevents disclosure if server directory rules are absent.
    $contents = "<?php exit; ?>\n" . json_encode($record);
    if (@file_put_contents(admin_remember_path($token), $contents, LOCK_EX) === false) {
        error_log('Unable to save admin remembered login.');
        return false;
    }
    @chmod(admin_remember_path($token), 0600);
    admin_cookie('tr_remember', $token, $expires);
    admin_cookie('tr_admin', '1', $expires);
    $_COOKIE['tr_remember'] = $token;
    $_SESSION['remember_expires'] = $expires;
    return true;
}

function admin_session_start(): void {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.use_strict_mode', '1');
        session_set_cookie_params([
            'lifetime' => 0, 'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true, 'samesite' => 'Lax',
        ]);
        session_start();
    }
    $now = time();
    if (!empty($_SESSION['logged_in'])) {
        $expired = $now - ($_SESSION['last_activity'] ?? $now) >= ADMIN_SESSION_IDLE_SECONDS
            || $now - ($_SESSION['authenticated_at'] ?? $now) >= ADMIN_SESSION_MAX_SECONDS
            || (!empty($_SESSION['remember_expires']) && $_SESSION['remember_expires'] <= $now)
            || (isset($_SESSION['auth_fingerprint']) && !hash_equals($_SESSION['auth_fingerprint'], admin_credential_fingerprint()));
        if ($expired) {
            $_SESSION = [];
            session_regenerate_id(true);
        }
    }
    if (empty($_SESSION['logged_in']) && !empty($_COOKIE['tr_remember'])) {
        $path = admin_remember_path((string) $_COOKIE['tr_remember']);
        $contents = $path !== null && is_file($path) ? file_get_contents($path) : '';
        $record = json_decode(substr((string) $contents, strlen("<?php exit; ?>\n")), true);
        if (is_array($record) && ($record['expires'] ?? 0) > $now
            && is_string($record['credential'] ?? null)
            && hash_equals(admin_credential_fingerprint(), $record['credential'])
            && is_string($record['csrf_token'] ?? null)) {
            session_regenerate_id(true);
            $_SESSION['logged_in'] = true;
            $_SESSION['authenticated_at'] = $now;
            $_SESSION['remember_expires'] = $record['expires'];
            $_SESSION['auth_fingerprint'] = $record['credential'];
            $_SESSION['csrf_token'] = $record['csrf_token'];
            admin_cookie('tr_admin', '1', $record['expires']);
        } else {
            admin_remember_forget();
        }
    }
    if (!empty($_SESSION['logged_in'])) {
        $_SESSION['authenticated_at'] = $_SESSION['authenticated_at'] ?? $now;
        $_SESSION['auth_fingerprint'] = $_SESSION['auth_fingerprint'] ?? admin_credential_fingerprint();
        if (!defined('ADMIN_SESSION_BACKGROUND') || !ADMIN_SESSION_BACKGROUND) {
            $_SESSION['last_activity'] = $now;
        }
    }
}

admin_session_start();
