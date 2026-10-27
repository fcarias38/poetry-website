<?php
http_response_code(503);
header('Retry-After: 3600');
?>
<!DOCTYPE html>
<html lang="en" data-theme-scope="site" data-theme="dark">
  <head>
  <script src="/theme.js"></script>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Think Room — Back Soon</title>
    <meta name="robots" content="noindex, nofollow" />
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:ital,wght@0,300;0,400;1,300;1,400&family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400;1,600&display=swap" rel="stylesheet" />
    <style>
      *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

      body {
        font-family: "Merriweather", serif;
        background: #111113;
        color: #c8c2b8;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 2rem 1.5rem;
        animation: fadeIn 1.4s ease-in;
      }

      @keyframes fadeIn {
        from { opacity: 0; transform: translateY(12px); }
        to   { opacity: 1; transform: translateY(0); }
      }

      .brand {
        font-family: "Cormorant Garamond", serif;
        font-size: clamp(2.4rem, 7vw, 3.8rem);
        font-weight: 300;
        font-style: italic;
        color: #ede8de;
        letter-spacing: 0.03em;
        margin-bottom: 1.4rem;
      }

      .divider {
        width: 40px;
        height: 1px;
        background: #c8a96e;
        opacity: 0.5;
        margin: 0 auto 1.8rem;
      }

      .message {
        font-family: "Cormorant Garamond", serif;
        font-size: clamp(1rem, 3vw, 1.25rem);
        font-weight: 300;
        font-style: italic;
        color: #a8a098;
        line-height: 1.9;
        max-width: 480px;
        margin-bottom: 0.8rem;
      }

      .sub {
        font-size: 0.66rem;
        letter-spacing: 0.18em;
        text-transform: uppercase;
        color: #3a3830;
        margin-top: 2rem;
      }

      .admin-link {
        margin-top: 2.6rem;
      }
      .admin-link a {
        display: inline-block;
        font-family: "Cormorant Garamond", serif;
        font-size: 0.78rem;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        color: #8a8178;
        text-decoration: none;
        padding: 0.7rem 1.5rem;
        border: 1px solid rgba(200,169,110,0.25);
        border-radius: 8px;
        transition: color 0.2s, border-color 0.2s, background 0.2s;
      }
      .admin-link a:hover {
        color: #ede8de;
        border-color: rgba(200,169,110,0.55);
        background: rgba(200,169,110,0.06);
      }

      footer {
        position: fixed;
        bottom: 0;
        width: 100%;
        text-align: center;
        font-size: 0.68rem;
        color: rgba(255,255,255,0.12);
        padding: 1rem;
      }
    </style>
    <link rel="stylesheet" href="/theme.css">
</head>
  <body class="maintenance-page">
    <p class="brand">Think Room</p>
    <div class="divider"></div>
    <p class="message">We're doing a little work behind the scenes.<br>We'll be back shortly.</p>
    <p class="sub">Down for maintenance</p>
    <p class="admin-link"><a href="/login">Admin login</a></p>
    <footer>&copy; <?php echo date('Y'); ?> Freddy Carias. All Rights Reserved.</footer>
  </body>
</html>
