<?php
// all_poems.php — a plain, crawlable list of every poem.
header('Content-Type: text/html; charset=UTF-8');

include 'config.php';

// Query the database directly rather than fetching our own public endpoint
// over HTTP (which is slow and fails if the request can't resolve).
$poems  = [];
$result = $conn->query("SELECT title, body, slug, id FROM poems ORDER BY id DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $poems[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>All Poems</title>
  <meta name="robots" content="index,follow">
</head>
<body>
  <h1>All Poems</h1>
  <?php if (empty($poems)): ?>
    <p>No poems yet.</p>
  <?php else: ?>
    <?php foreach ($poems as $poem): ?>
      <article>
        <h2>
          <?php $href = !empty($poem['slug']) ? '/poems/' . rawurlencode($poem['slug']) : '/poem?id=' . intval($poem['id']); ?>
          <a href="<?= htmlspecialchars($href) ?>"><?= htmlspecialchars($poem['title']) ?></a>
        </h2>
        <pre><?= htmlspecialchars($poem['body']) ?></pre>
      </article>
    <?php endforeach; ?>
  <?php endif; ?>
</body>
</html>
<?php $conn->close(); ?>
