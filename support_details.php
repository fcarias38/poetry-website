<?php
require_once __DIR__ . '/csrf.php';
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: private, no-store');
header('X-Robots-Tag: noindex, nofollow');

function support_error(int $status, string $message): void {
    http_response_code($status);
    echo json_encode(['error' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    support_error(405, 'Use the Show Zelle details button on the author page.');
}
if (isset($_SERVER['HTTP_SEC_FETCH_SITE']) && !in_array($_SERVER['HTTP_SEC_FETCH_SITE'], ['same-origin', 'none'], true)) {
    support_error(403, 'Please open the author page and try again.');
}
if (!empty($_SERVER['HTTP_ORIGIN'])) {
    $origin = parse_url($_SERVER['HTTP_ORIGIN']);
    $host = parse_url('https://' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST);
    if (!is_array($origin) || strtolower($origin['host'] ?? '') !== strtolower((string) $host)) {
        support_error(403, 'Please open the author page and try again.');
    }
}
csrf_require();
$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data) || !isset($data['website']) || !is_string($data['website']) || $data['website'] !== '') {
    support_error(400, 'Unable to show Zelle details.');
}

// Keep the address out of HTML and script files; limit repeated reveal requests.
$directory = sys_get_temp_dir() . '/think-room-support-' . hash('sha256', __DIR__);
if (!is_dir($directory) && !@mkdir($directory, 0700, true) && !is_dir($directory)) {
    support_error(503, 'Please try again later.');
}
$key = hash('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
$handle = @fopen($directory . '/' . $key, 'c+');
if (!$handle || !flock($handle, LOCK_EX)) {
    if ($handle) fclose($handle);
    support_error(503, 'Please try again later.');
}
$rate = json_decode(stream_get_contents($handle), true);
if (!is_array($rate) || ($rate['expires'] ?? 0) <= time()) {
    $rate = ['expires' => time() + 15 * 60, 'count' => 0];
}
if ($rate['count'] >= 10) {
    flock($handle, LOCK_UN);
    fclose($handle);
    header('Retry-After: ' . max(1, $rate['expires'] - time()));
    support_error(429, 'Please wait a few minutes before trying again.');
}
$rate['count']++;
rewind($handle);
ftruncate($handle, 0);
fwrite($handle, json_encode($rate));
flock($handle, LOCK_UN);
fclose($handle);

echo json_encode(['email' => 'fcarias38@outlook.com']);
