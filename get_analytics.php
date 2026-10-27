<?php
/**
 * get_analytics.php — admin-only native analytics aggregated from our own DB.
 * Returns totals, per-poem engagement, category breakdown, recent comments,
 * and a 30-day comment trend. No external services.
 */
require_once __DIR__ . '/admin_session.php';
header('Content-Type: application/json');

if (empty($_SESSION['logged_in'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

include 'config.php';

$out = ['success' => true];

// ── Per-poem engagement + totals ───────────────────────────────────────
// Comment counts come from the comments table joined by poem_id.
$sql = "
    SELECT p.id, p.title, p.category, p.slug,
           p.likes  AS likes,
           p.views  AS views,
           p.shares AS shares,
           COALESCE(c.cnt, 0) AS comments
    FROM poems p
    LEFT JOIN (
        SELECT poem_id, COUNT(*) AS cnt
        FROM comments
        GROUP BY poem_id
    ) c ON c.poem_id = p.id
    ORDER BY p.id DESC
";
$res = $conn->query($sql);

$poems = [];
$totals = ['poems' => 0, 'likes' => 0, 'views' => 0, 'shares' => 0, 'comments' => 0];
$byCategory = [];

while ($res && $row = $res->fetch_assoc()) {
    $row['likes']    = intval($row['likes']);
    $row['views']    = intval($row['views']);
    $row['shares']   = intval($row['shares']);
    $row['comments'] = intval($row['comments']);
    // Engagement score: weighted blend of signals.
    $row['engagement'] = $row['views']
                       + $row['likes'] * 3
                       + $row['comments'] * 5
                       + $row['shares'] * 4;

    $totals['poems']    += 1;
    $totals['likes']    += $row['likes'];
    $totals['views']    += $row['views'];
    $totals['shares']   += $row['shares'];
    $totals['comments'] += $row['comments'];

    $cat = $row['category'] ?: 'Uncategorized';
    if (!isset($byCategory[$cat])) {
        $byCategory[$cat] = ['category' => $cat, 'poems' => 0, 'likes' => 0, 'views' => 0, 'comments' => 0, 'shares' => 0];
    }
    $byCategory[$cat]['poems']    += 1;
    $byCategory[$cat]['likes']    += $row['likes'];
    $byCategory[$cat]['views']    += $row['views'];
    $byCategory[$cat]['comments'] += $row['comments'];
    $byCategory[$cat]['shares']   += $row['shares'];

    $poems[] = $row;
}

$out['totals'] = $totals;
$out['poems']  = $poems;

// Category list sorted by views desc
$cats = array_values($byCategory);
usort($cats, fn($a, $b) => $b['views'] <=> $a['views']);
$out['categories'] = $cats;

// ── Top poems by each metric (top 5) ───────────────────────────────────
function topBy(array $poems, string $key, int $n = 5): array {
    $copy = $poems;
    usort($copy, fn($a, $b) => $b[$key] <=> $a[$key]);
    return array_slice(array_map(fn($p) => [
        'id' => $p['id'], 'title' => $p['title'], 'slug' => $p['slug'],
        'value' => $p[$key],
    ], $copy), 0, $n);
}
$out['top'] = [
    'engagement' => topBy($poems, 'engagement'),
    'views'      => topBy($poems, 'views'),
    'likes'      => topBy($poems, 'likes'),
    'comments'   => topBy($poems, 'comments'),
    'shares'     => topBy($poems, 'shares'),
];

// ── Recent comments (last 8) ───────────────────────────────────────────
$recent = [];
$rc = $conn->query("
    SELECT c.name, c.comment, c.created_at, p.title AS poem_title
    FROM comments c
    LEFT JOIN poems p ON p.id = c.poem_id
    ORDER BY c.created_at DESC
    LIMIT 8
");
while ($rc && $row = $rc->fetch_assoc()) {
    $recent[] = $row;
}
$out['recent_comments'] = $recent;

// ── 30-day comment trend (count per day) ───────────────────────────────
$trend = [];
$tr = $conn->query("
    SELECT DATE(created_at) AS day, COUNT(*) AS cnt
    FROM comments
    WHERE created_at >= (CURRENT_DATE - INTERVAL 29 DAY)
    GROUP BY DATE(created_at)
");
$trendMap = [];
while ($tr && $row = $tr->fetch_assoc()) {
    $trendMap[$row['day']] = intval($row['cnt']);
}
// Fill all 30 days so the chart is continuous.
for ($i = 29; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-{$i} day"));
    $trend[] = ['day' => $day, 'count' => $trendMap[$day] ?? 0];
}
$out['comment_trend'] = $trend;

echo json_encode($out);
$conn->close();
?>
