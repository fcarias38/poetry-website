<?php
// all_poems.php
header('Content-Type: text/html; charset=UTF-8');
// Grab your poems JSON
$json = file_get_contents('https://freddycarias.com/get_poems.php');

$poems = json_decode($json, true);
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
  <?php foreach ($poems as $poem): ?>
    <article>
      <h2><?= htmlspecialchars($poem['title']) ?></h2>
      <pre><?= htmlspecialchars($poem['body']) ?></pre>
    </article>
  <?php endforeach; ?>
</body>
</html>

$json = file_get_contents('https://freddycarias.com/get_poems.php');