<?php
/**
 * send_contact.php — handles the public contact form.
 * Sends a branded HTML email to the admin. Uses an on-domain From: address
 * (Bluehost requirement) and sets Reply-To to the visitor so you can reply
 * straight from your inbox.
 */
include 'admin_config.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header('Location: /gallery');
    exit;
}

$to      = $ADMIN_EMAIL;                 // where the message is delivered
$name    = trim(strip_tags($_POST["name"] ?? ''));
$email   = trim(strip_tags($_POST["email"] ?? ''));
$message = trim(strip_tags($_POST["message"] ?? ''));

// Basic validation
if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $message === '') {
    header('Location: /contact?error=invalid');
    exit;
}

$subject = 'New message from Think Room';
$safeName    = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$safeEmail   = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
$safeMessage = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
$sentTime    = date('F j, Y \a\t g:i A');

// Plain-text fallback
$textBody = "New message from Think Room\r\n\r\n"
          . "From: {$name}\r\n"
          . "Email: {$email}\r\n"
          . "Sent: {$sentTime}\r\n\r\n"
          . "Message:\r\n{$message}\r\n";

// HTML body — table layout for email-client compatibility
$htmlBody = '<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="margin:0;padding:0;background:#0e0e10;font-family:Arial,Helvetica,sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#0e0e10;padding:32px 16px;">
    <tr><td align="center">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;background:#161618;border:1px solid rgba(255,255,255,0.09);border-radius:14px;overflow:hidden;">
        <tr><td style="padding:32px 36px 0;">
          <table role="presentation" cellpadding="0" cellspacing="0"><tr>
            <td style="width:38px;height:38px;background:#c8a96e;border-radius:9px;text-align:center;vertical-align:middle;font-family:Georgia,serif;font-style:italic;font-weight:bold;font-size:20px;color:#1a1408;">T</td>
            <td style="padding-left:12px;font-family:Georgia,serif;font-style:italic;font-weight:bold;font-size:20px;color:#ededed;">Think Room</td>
          </tr></table>
        </td></tr>
        <tr><td style="padding:26px 36px 8px;">
          <p style="margin:0 0 6px;font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#c8a96e;">Contact Form</p>
          <h1 style="margin:0 0 20px;font-size:21px;font-weight:600;color:#ededed;">New message received</h1>
        </td></tr>
        <tr><td style="padding:0 36px 8px;">
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;color:#9a978f;">
            <tr><td style="padding:6px 0;width:70px;color:#6b6862;">From</td><td style="padding:6px 0;color:#ededed;">' . $safeName . '</td></tr>
            <tr><td style="padding:6px 0;color:#6b6862;">Email</td><td style="padding:6px 0;"><a href="mailto:' . $safeEmail . '" style="color:#c8a96e;text-decoration:none;">' . $safeEmail . '</a></td></tr>
            <tr><td style="padding:6px 0;color:#6b6862;">Sent</td><td style="padding:6px 0;color:#9a978f;">' . htmlspecialchars($sentTime) . '</td></tr>
          </table>
        </td></tr>
        <tr><td style="padding:12px 36px;"><div style="border-top:1px solid rgba(255,255,255,0.09);"></div></td></tr>
        <tr><td style="padding:4px 36px 10px;">
          <p style="margin:0 0 8px;font-size:11px;letter-spacing:1px;text-transform:uppercase;color:#6b6862;">Message</p>
          <div style="font-size:15px;line-height:1.7;color:#d9d4ca;">' . $safeMessage . '</div>
        </td></tr>
        <tr><td style="padding:22px 36px 30px;">
          <table role="presentation" cellpadding="0" cellspacing="0"><tr>
            <td style="border-radius:9px;background:#c8a96e;">
              <a href="mailto:' . $safeEmail . '" style="display:inline-block;padding:11px 26px;font-size:14px;font-weight:bold;color:#1a1408;text-decoration:none;border-radius:9px;">Reply to ' . $safeName . '</a>
            </td>
          </tr></table>
        </td></tr>
      </table>
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;">
        <tr><td style="padding:18px 8px;text-align:center;">
          <p style="margin:0;font-size:11px;color:#55524c;">Sent from the contact form at freddycarias.com</p>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body></html>';

// Multipart (text + HTML)
$boundary = 'tr_' . bin2hex(random_bytes(12));
$headers  = "From: Think Room <{$MAIL_FROM}>\r\n";
$headers .= "Reply-To: {$name} <{$email}>\r\n"; // reply goes to the visitor
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

$body  = "--{$boundary}\r\n";
$body .= "Content-Type: text/plain; charset=UTF-8\r\n";
$body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
$body .= $textBody . "\r\n\r\n";
$body .= "--{$boundary}\r\n";
$body .= "Content-Type: text/html; charset=UTF-8\r\n";
$body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
$body .= $htmlBody . "\r\n\r\n";
$body .= "--{$boundary}--";

// Envelope sender aligned to the domain for Bluehost deliverability.
$sent = mail($to, $subject, $body, $headers, "-f {$MAIL_FROM}");

if ($sent) {
    header('Location: /contact?sent=1');
} else {
    error_log('send_contact.php: mail() returned false');
    header('Location: /contact?error=failed');
}
exit;
?>
