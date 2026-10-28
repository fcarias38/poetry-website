"""One deployment email test; removes the temporary, token-protected probe."""
import io
import json
from pathlib import Path
import runpy
import secrets
import time
import urllib.request

ROOT = Path(__file__).resolve().parent.parent


def main():
    connect = runpy.run_path(str(ROOT / '.kiro' / 'deploy-notifications.py'))['connect']
    token = secrets.token_hex(32)
    name = 'notification-check.' + secrets.token_hex(16) + '.php'
    source = r'''<?php
header('Content-Type: application/json');
header('Cache-Control: no-store');
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || time() > EXPIRES
    || !hash_equals('TOKEN', $_SERVER['HTTP_X_DEPLOY_CHECK'] ?? '')) {
    http_response_code(403); echo json_encode(['success'=>false]); exit;
}
try {
    require __DIR__ . '/admin_config.php';
    require __DIR__ . '/config.php';
    require __DIR__ . '/notifications.php';
    notifications_init($conn);
    $to = $NOTIFICATION_EMAIL ?? $ADMIN_EMAIL ?? '';
    $from = $MAIL_FROM ?? '';
    $enabled = (!isset($NOTIFICATION_EMAIL_ENABLED) || $NOTIFICATION_EMAIL_ENABLED)
        && (!isset($NOTIFICATION_EMAIL_LIKES) || $NOTIFICATION_EMAIL_LIKES)
        && (!isset($NOTIFICATION_EMAIL_COMMENTS) || $NOTIFICATION_EMAIL_COMMENTS)
        && (!isset($NOTIFICATION_EMAIL_SHARES) || $NOTIFICATION_EMAIL_SHARES);
    if (!$enabled || !filter_var($to, FILTER_VALIDATE_EMAIL) || !filter_var($from, FILTER_VALIDATE_EMAIL)
        || preg_match('/[\r\n]/', $to . $from)) {
        echo json_encode(['success'=>false, 'error'=>'Notification email configuration needs attention']); exit;
    }
    $shares = $conn->query("SHOW COLUMNS FROM poems LIKE 'shares'")->num_rows === 1;
    $accepted = mail($to, 'Think Room: notification setup test',
        "Your dashboard and email alerts for likes, comments, and shares have been deployed.\n\nThis is a setup test; no poem activity was added.\n\nOpen your dashboard: https://freddycarias.com/dashboard\n",
        "From: Think Room <$from>\r\nContent-Type: text/plain; charset=UTF-8");
    echo json_encode(['success'=>$accepted && $shares, 'mail_accepted'=>$accepted, 'shares_column_ready'=>$shares]);
} catch (Throwable $error) {
    error_log('Notification deployment check failed: ' . $error->getMessage());
    http_response_code(500); echo json_encode(['success'=>false]);
}
'''.replace('EXPIRES', str(int(time.time()) + 600)).replace('TOKEN', token)
    ftp = connect()
    uploaded = False
    try:
        # Track the upload before starting so a partial file is removed as well.
        uploaded = True
        ftp.storbinary('STOR ' + name, io.BytesIO(source.encode()))
        request = urllib.request.Request(
            'https://freddycarias.com/' + name, data=b'', method='POST',
            headers={'X-Deploy-Check': token, 'User-Agent': 'ThinkRoom-Deployment-Check/1.0'})
        with urllib.request.urlopen(request, timeout=45) as response:
            result = json.loads(response.read())
        print(json.dumps(result), flush=True)
        if not result.get('success'):
            raise RuntimeError('The live email readiness check failed')
    finally:
        if uploaded:
            try:
                ftp.delete(name)
            except Exception:
                ftp.close()
                ftp = connect()
                ftp.delete(name)
            print('Temporary deployment check removed.', flush=True)
        ftp.quit()


if __name__ == '__main__':
    main()
