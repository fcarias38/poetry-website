<?php
/**
 * reset_password.php
 * Validates the reset token and allows the admin to set a new password.
 */

include 'admin_config.php';

$tokenDir = __DIR__ . '/reset_tokens';
$token    = preg_replace('/[^a-f0-9]/', '', $_GET['token'] ?? '');
$tokenFile = $tokenDir . '/' . $token . '.json';

$valid      = false;
$tokenData  = null;
$pageError  = '';
$pageSuccess = '';

// Validate token on page load
if ($token && file_exists($tokenFile)) {
    $tokenData = json_decode(file_get_contents($tokenFile), true);
    if ($tokenData && $tokenData['expires'] >= time()) {
        $valid = true;
    } else {
        $pageError = 'This reset link has expired. Please request a new one.';
    }
} else {
    $pageError = 'Invalid or missing reset token.';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid) {
    $newPassword = $_POST['new_password']    ?? '';
    $confirmPw   = $_POST['confirm_password'] ?? '';

    if (strlen($newPassword) < 10) {
        $pageError = 'Password must be at least 10 characters.';
    } elseif ($newPassword !== $confirmPw) {
        $pageError = 'Passwords do not match.';
    } else {
        // Hash the new password and write it back to admin_config.php.
        $newHash       = password_hash($newPassword, PASSWORD_DEFAULT);
        $configContent = file_get_contents(__DIR__ . '/admin_config.php');

        // Build the new line. NOTE: a bcrypt hash contains "$" characters
        // (e.g. $2y$10$...). We must NOT pass this as the replacement string to
        // preg_replace(), because "$1", "$2" etc. are interpreted as
        // backreferences and corrupt the hash. Use preg_replace_callback so the
        // replacement is treated as a literal string.
        $newLine = "\$ADMIN_PASS_HASH = '" . $newHash . "';";

        // Match the ADMIN_PASS_HASH assignment in any form:
        //   $ADMIN_PASS_HASH = password_hash('...', PASSWORD_DEFAULT);
        //   $ADMIN_PASS_HASH = '...';
        $pattern = '/\$ADMIN_PASS_HASH\s*=\s*(?:password_hash\([^;]*\)|\'[^\']*\'|"[^"]*")\s*;/';

        $written = preg_replace_callback(
            $pattern,
            function () use ($newLine) { return $newLine; },
            $configContent,
            1,
            $count
        );

        // Only write if we actually matched and replaced the line, so we never
        // blank out the config on an unexpected format.
        if ($count > 0 && $written !== null) {
            file_put_contents(__DIR__ . '/admin_config.php', $written);

            // Clear the cached hash so the new $ADMIN_PASS_HASH takes effect.
            $cacheFile = __DIR__ . '/admin_pass.hash';
            if (is_file($cacheFile)) {
                @unlink($cacheFile);
            }

            unlink($tokenFile); // Invalidate the token
            $pageSuccess = 'Password updated successfully. You can now log in.';
            $valid = false; // Hide the form
        } else {
            $pageError = 'Could not update the password. Please contact the administrator.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme-scope="admin" data-theme="light">
<head>
  <script src="/theme.js?v=20261027-2"></script>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Think Room | Reset Password</title>
  <meta name="robots" content="noindex, nofollow">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;1,300;1,400&family=Merriweather:wght@300;400&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      background: #0d0d0d url('images/jessica-christian-wuqYCTAYPH4-unsplash.jpg') no-repeat center center / cover;
      font-family: "Merriweather", serif;
      color: #f0ebe0;
      overflow: hidden;
    }
    .overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.68); z-index: 0; }
    .card {
      position: relative; z-index: 1;
      width: 100%; max-width: 380px;
      background: rgba(10,10,10,0.88);
      border: 1px solid rgba(255,255,255,0.08);
      border-radius: 6px;
      padding: 2.8rem 2.2rem 2.2rem;
      backdrop-filter: blur(8px);
      margin: 1rem;
    }
    .brand {
      font-family: "Cormorant Garamond", serif;
      font-size: 1.4rem; font-style: italic; font-weight: 400;
      color: #ede8de; text-align: center;
      margin-bottom: 0.3rem; display: block;
      text-decoration: none;
    }
    .brand-sub {
      font-size: 0.56rem; letter-spacing: 0.2em; text-transform: uppercase;
      color: #555; text-align: center; margin-bottom: 2.2rem;
    }
    .error-msg {
      background: rgba(192,57,43,0.12); border: 1px solid rgba(192,57,43,0.25);
      color: #e88; font-size: 0.78rem; padding: 0.65rem 0.9rem;
      border-radius: 3px; margin-bottom: 1.2rem;
    }
    .success-msg {
      background: rgba(46,160,67,0.12); border: 1px solid rgba(46,160,67,0.25);
      color: #8c8; font-size: 0.78rem; padding: 0.65rem 0.9rem;
      border-radius: 3px; margin-bottom: 1.2rem;
    }
    .field { margin-bottom: 1.2rem; }
    .field label {
      font-size: 0.54rem; letter-spacing: 0.18em; text-transform: uppercase;
      color: #888; display: block; margin-bottom: 0.4rem;
    }
    .field input {
      width: 100%; background: rgba(255,255,255,0.04);
      border: none; border-bottom: 1px solid rgba(255,255,255,0.12);
      padding: 0.65rem 0; color: #e0dbd0; font-size: 0.9rem;
      font-family: "Merriweather", serif; transition: border-color 0.2s;
    }
    .field input:focus { outline: none; border-bottom-color: rgba(200,169,110,0.5); }
    .hint { font-size: 0.6rem; color: #555; margin-top: 0.3rem; }
    .btn-submit {
      width: 100%; background: none;
      border: 1px solid rgba(200,169,110,0.35); color: #c8a96e;
      padding: 0.7rem; font-family: "Merriweather", serif;
      font-size: 0.68rem; letter-spacing: 0.16em; text-transform: uppercase;
      cursor: pointer; border-radius: 3px; transition: all 0.2s;
      margin-bottom: 1.2rem;
    }
    .btn-submit:hover { background: rgba(200,169,110,0.1); }
    .back-link {
      display: block; text-align: center;
      font-size: 0.56rem; letter-spacing: 0.12em; text-transform: uppercase;
      color: #666; text-decoration: none; transition: color 0.2s;
    }
    .back-link:hover { color: #c8a96e; }
    footer {
      position: relative; z-index: 1;
      font-size: 0.58rem; color: rgba(255,255,255,0.15);
      letter-spacing: 0.1em; text-transform: uppercase;
      margin-top: 1.5rem; text-align: center;
    }
  </style>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="admin.css">
  <link rel="stylesheet" href="/theme.css?v=20261027-2">
</head>
<body class="reset-page">
<div class="overlay"></div>

<div class="card">
  <a href="/" class="brand">Think Room</a>
  <p class="brand-sub">Reset Password</p>

  <?php if ($pageError): ?>
    <div class="error-msg"><?= htmlspecialchars($pageError) ?></div>
  <?php endif; ?>

  <?php if ($pageSuccess): ?>
    <div class="success-msg"><?= htmlspecialchars($pageSuccess) ?></div>
    <a href="login.php" class="back-link">Back to login &rarr;</a>
  <?php elseif ($valid): ?>
    <form method="POST" action="">
      <div class="field">
        <label for="new_password">New Password</label>
        <input type="password" id="new_password" name="new_password" required autocomplete="new-password" />
        <p class="hint">Minimum 10 characters.</p>
      </div>
      <div class="field">
        <label for="confirm_password">Confirm Password</label>
        <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password" />
      </div>
      <button type="submit" class="btn-submit">Update Password</button>
    </form>
    <a href="login.php" class="back-link">&larr; Back to login</a>
  <?php else: ?>
    <a href="login.php" class="back-link">&larr; Back to login</a>
  <?php endif; ?>
</div>

<footer>&copy; 2025 Freddy Carias</footer>
</body>
</html>
