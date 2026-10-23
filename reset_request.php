<?php
/**
 * reset_request.php
 * Generates a secure one-time password reset token, stores it in a flat file,
 * and emails the reset link to the admin. Token expires in 1 hour.
 */

include 'admin_config.php';

// Only respond to POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$email = strtolower(trim($_POST['email'] ?? ''));

// Always return 200 so we don't reveal whether the email exists (anti-enumeration)
http_response_code(200);

if (strtolower($email) !== strtolower($ADMIN_EMAIL)) {
    // Email not recognised — silently exit (same response as success)
    echo 'ok';
    exit;
}

// Generate a cryptographically secure token
$token   = bin2hex(random_bytes(32)); // 64-char hex string
$expires = time() + 3600;            // 1 hour from now

// Store token in a flat file outside the web root if possible,
// or in a dedicated tokens directory with restricted access.
$tokenDir  = __DIR__ . '/reset_tokens';
$tokenFile = $tokenDir . '/' . $token . '.json';

// Create directory if it doesn't exist
if (!is_dir($tokenDir)) {
    mkdir($tokenDir, 0700, true);
}

// Clean up expired tokens before writing new one
foreach (glob($tokenDir . '/*.json') as $file) {
    $data = json_decode(file_get_contents($file), true);
    if (isset($data['expires']) && $data['expires'] < time()) {
        unlink($file);
    }
}

// Write the new token
file_put_contents($tokenFile, json_encode([
    'email'   => $ADMIN_EMAIL,
    'expires' => $expires,
]));

// Build reset URL — adjust domain if needed
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'] ?? 'freddycarias.com';
$resetUrl = $protocol . '://' . $host . '/reset_password.php?token=' . $token;

// ── Build the email ──
// IMPORTANT (Bluehost): the From: address MUST be a real mailbox that exists
// in cPanel on this domain, otherwise Bluehost rewrites/blocks the message.
// $MAIL_FROM is defined in admin_config.php.
$to      = $ADMIN_EMAIL;
$subject = 'Reset your Think Room password';
$safeUrl = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');

// Plain-text fallback part
$textBody = "THINK ROOM — Password Reset\r\n\r\n"
          . "You requested a password reset for your Think Room admin account.\r\n\r\n"
          . "Open the link below to choose a new password (this link expires in 1 hour):\r\n\r\n"
          . $resetUrl . "\r\n\r\n"
          . "If you didn't request this, you can safely ignore this email — your password won't change.\r\n\r\n"
          . "— Think Room\r\n"
          . "https://freddycarias.com";

// HTML part — table-based layout for email-client compatibility
$htmlBody = '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset your password</title>
</head>
<body style="margin:0;padding:0;background:#0e0e10;font-family:Arial,Helvetica,sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#0e0e10;padding:32px 16px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:480px;background:#161618;border:1px solid rgba(255,255,255,0.09);border-radius:14px;overflow:hidden;">
          <!-- Header -->
          <tr>
            <td style="padding:32px 36px 0;">
              <table role="presentation" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="width:38px;height:38px;background:#c8a96e;border-radius:9px;text-align:center;vertical-align:middle;font-family:Georgia,\'Times New Roman\',serif;font-style:italic;font-weight:bold;font-size:20px;color:#1a1408;">T</td>
                  <td style="padding-left:12px;font-family:Georgia,\'Times New Roman\',serif;font-style:italic;font-weight:bold;font-size:20px;color:#ededed;">Think Room</td>
                </tr>
              </table>
            </td>
          </tr>
          <!-- Body -->
          <tr>
            <td style="padding:28px 36px 8px;">
              <p style="margin:0 0 6px;font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#c8a96e;">Admin Console</p>
              <h1 style="margin:0 0 18px;font-size:22px;font-weight:600;color:#ededed;">Reset your password</h1>
              <p style="margin:0 0 20px;font-size:15px;line-height:1.65;color:#9a978f;">
                We received a request to reset the password for your Think Room admin account. Click the button below to choose a new password. This link will expire in <strong style="color:#c7c3bb;">1 hour</strong>.
              </p>
            </td>
          </tr>
          <!-- Button -->
          <tr>
            <td style="padding:4px 36px 24px;">
              <table role="presentation" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="border-radius:9px;background:#c8a96e;">
                    <a href="' . $safeUrl . '" target="_blank" style="display:inline-block;padding:13px 30px;font-size:15px;font-weight:bold;color:#1a1408;text-decoration:none;border-radius:9px;">Reset password</a>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <!-- Fallback link -->
          <tr>
            <td style="padding:0 36px 24px;">
              <p style="margin:0 0 6px;font-size:12px;color:#6b6862;">Or copy and paste this link into your browser:</p>
              <p style="margin:0;font-size:12px;word-break:break-all;"><a href="' . $safeUrl . '" style="color:#c8a96e;text-decoration:none;">' . $safeUrl . '</a></p>
            </td>
          </tr>
          <!-- Divider -->
          <tr><td style="padding:0 36px;"><div style="border-top:1px solid rgba(255,255,255,0.09);"></div></td></tr>
          <!-- Footer note -->
          <tr>
            <td style="padding:20px 36px 32px;">
              <p style="margin:0;font-size:13px;line-height:1.6;color:#6b6862;">
                If you didn\'t request this, you can safely ignore this email — your password won\'t be changed.
              </p>
            </td>
          </tr>
        </table>
        <!-- Outer footer -->
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:480px;">
          <tr>
            <td style="padding:20px 8px;text-align:center;">
              <p style="margin:0;font-size:11px;color:#55524c;">&copy; 2025 Freddy Carias &middot; Think Room</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>';

// ── Assemble a multipart/alternative message (text + HTML) ──
$boundary = 'tr_' . bin2hex(random_bytes(12));

$headers  = "From: Think Room <{$MAIL_FROM}>\r\n";
$headers .= "Reply-To: {$MAIL_FROM}\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

$message  = "--{$boundary}\r\n";
$message .= "Content-Type: text/plain; charset=UTF-8\r\n";
$message .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
$message .= $textBody . "\r\n\r\n";
$message .= "--{$boundary}\r\n";
$message .= "Content-Type: text/html; charset=UTF-8\r\n";
$message .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
$message .= $htmlBody . "\r\n\r\n";
$message .= "--{$boundary}--";

// The 5th parameter sets the envelope sender (Return-Path) so it aligns with
// the domain's SPF record — critical for Bluehost deliverability.
$sent = mail($to, $subject, $message, $headers, "-f {$MAIL_FROM}");

if (!$sent) {
    error_log('reset_request.php: mail() returned false for ' . $to);
}

echo 'ok';
exit;
?>
