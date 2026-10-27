<?php
require 'csrf.php';
header('Cache-Control: no-store, private');
header('X-Robots-Tag: noindex, nofollow');
header('Referrer-Policy: no-referrer');
if (empty($_SESSION['logged_in'])) {
    http_response_code(403);
    exit('Please log in to preview your poem.');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Open a preview from the poem editor.');
}
csrf_require();
$preview = [];
foreach (['title' => 1000, 'category' => 400, 'body' => 200000] as $field => $limit) {
    $value = $_POST[$field] ?? '';
    if (!is_string($value) || strlen($value) > $limit || !preg_match('//u', $value)) {
        http_response_code(422);
        exit('The poem is too long or contains invalid text. Please return to the editor.');
    }
    $preview[$field] = $value;
}
$preview['title'] = trim($preview['title']) ?: 'Untitled poem';
$preview['category'] = trim($preview['category']) ?: 'Uncategorized';
$preview['preview'] = true;
$payload = json_encode($preview, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
$html = file_get_contents(__DIR__ . '/home.shtml');
$previewHead = '<meta name="robots" content="noindex, nofollow"><script>window.THINK_ROOM_PREVIEW = ' . $payload . ';</script>';
// Use the actual gallery markup so the preview stays in step with the website.
$html = preg_replace('/<meta name="robots"[^>]*>/', '', $html);
$html = preg_replace('/<link rel="canonical"[^>]*>/', '', $html);
echo str_replace('</head>', $previewHead . '</head>', $html);
