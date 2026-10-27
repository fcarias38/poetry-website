<?php $loginError = isset($_GET['error']); ?>
<!DOCTYPE html>
<html lang="en" data-theme-scope="admin" data-theme="light">
<head>
  <script src="/theme.js?v=20261027-2"></script>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Think Room | Sign in</title>
  <link rel="manifest" href="/manifest.json">
  <meta name="theme-color" content="#f7f4ee">
  <meta name="robots" content="noindex, nofollow">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    html, body { height: 100%; }
    body {
      font-family: "Inter", system-ui, sans-serif;
      background: var(--bg);
      color: var(--text);
      -webkit-font-smoothing: antialiased;
      display: grid;
      grid-template-columns: 1.05fr 1fr;
      min-height: 100vh;
    }

    /* ── Left brand panel ───────────────────────── */
    .brand-panel {
      position: relative;
      background:
        radial-gradient(1200px 600px at 20% 10%, rgba(200,169,110,0.10), transparent 55%),
        radial-gradient(900px 500px at 90% 90%, rgba(200,169,110,0.06), transparent 60%),
        linear-gradient(160deg, #141416 0%, #0d0d0e 100%);
      border-right: 1px solid var(--border);
      padding: 3.5rem;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      overflow: hidden;
    }
    .brand-panel::before {
      content: "";
      position: absolute;
      inset: 0;
      background-image:
        linear-gradient(rgba(255,255,255,0.025) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.025) 1px, transparent 1px);
      background-size: 46px 46px;
      mask-image: radial-gradient(circle at 30% 40%, #000 0%, transparent 70%);
      pointer-events: none;
    }
    .brand-top { position: relative; z-index: 1; display: flex; align-items: center; gap: 0.7rem; }
    .brand-mark {
      width: 34px; height: 34px; border-radius: 8px;
      background: linear-gradient(145deg, var(--accent), var(--accent-d));
      display: flex; align-items: center; justify-content: center;
      font-family: "Cormorant Garamond", serif; font-weight: 600;
      font-size: 1.25rem; color: #1a1408; font-style: italic;
    }
    .brand-name {
      font-family: "Cormorant Garamond", serif;
      font-size: 1.3rem; font-style: italic; font-weight: 600;
      letter-spacing: 0.02em; color: var(--text);
    }
    .brand-center { position: relative; z-index: 1; max-width: 420px; }
    .brand-center .eyebrow {
      font-size: 0.68rem; letter-spacing: 0.26em; text-transform: uppercase;
      color: var(--accent); margin-bottom: 1.4rem; opacity: 0.85;
    }
    .brand-center blockquote {
      font-family: "Cormorant Garamond", serif;
      font-size: clamp(1.4rem, 2.6vw, 2rem);
      font-style: italic; font-weight: 400;
      line-height: 1.5; color: #d9d4ca;
    }
    .brand-center cite {
      display: block; margin-top: 1.3rem;
      font-size: 0.72rem; letter-spacing: 0.16em; text-transform: uppercase;
      color: var(--text-mute); font-style: normal;
    }
    .brand-foot {
      position: relative; z-index: 1;
      font-size: 0.72rem; color: var(--text-mute); letter-spacing: 0.04em;
    }

    /* ── Right form panel ───────────────────────── */
    .form-panel {
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2.5rem;
      background: var(--panel);
    }
    .form-wrap { width: 100%; max-width: 380px; }

    .form-head { margin-bottom: 2rem; }
    .form-head h1 {
      font-size: 1.5rem; font-weight: 600; letter-spacing: -0.01em;
      color: var(--text); margin-bottom: 0.4rem;
    }
    .form-head p { font-size: 0.86rem; color: var(--text-dim); }

    .alert {
      font-size: 0.8rem; padding: 0.7rem 0.9rem;
      border-radius: 8px; margin-bottom: 1.3rem;
      display: none; line-height: 1.5;
    }
    .alert.error   { background: rgba(229,128,122,0.10); border: 1px solid rgba(229,128,122,0.28); color: var(--danger); }
    .alert.success { background: rgba(124,201,140,0.10); border: 1px solid rgba(124,201,140,0.28); color: var(--success); }

    .field { margin-bottom: 1.1rem; }
    .field label {
      display: block; font-size: 0.78rem; font-weight: 500;
      color: #c7c3bb; margin-bottom: 0.45rem;
    }
    .input-wrap { position: relative; }
    .field input {
      width: 100%;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 9px;
      padding: 0.72rem 0.9rem;
      color: var(--text);
      font-size: 0.92rem;
      font-family: inherit;
      transition: border-color 0.18s, box-shadow 0.18s, background 0.18s;
    }
    .field input::placeholder { color: var(--text-mute); }
    .field input:hover { border-color: var(--border-2); }
    .field input:focus {
      outline: none;
      border-color: var(--accent);
      background: var(--surface-2);
      box-shadow: 0 0 0 3px rgba(200,169,110,0.14);
    }
    .pw-toggle {
      position: absolute; top: 50%; right: 0.7rem; transform: translateY(-50%);
      background: none; border: none; cursor: pointer;
      font-size: 0.72rem; font-weight: 500; letter-spacing: 0.04em;
      color: var(--text-mute); padding: 0.2rem 0.3rem; font-family: inherit;
      transition: color 0.18s;
    }
    .pw-toggle:hover { color: var(--accent); }

    .row-between {
      display: flex; align-items: center; justify-content: space-between;
        flex-wrap: wrap; gap: 0.75rem;
      margin: 0.2rem 0 1.5rem;
    }
    .remember { display: flex; align-items: center; gap: 0.5rem; cursor: pointer; }
    .remember input {
        width: 16px; height: 16px; flex-shrink: 0; accent-color: var(--accent); cursor: pointer;
    }
    .remember label { font-size: 0.8rem; color: var(--text-dim); cursor: pointer; }
    .link-accent {
      font-size: 0.8rem; color: var(--accent); text-decoration: none; font-weight: 500;
      transition: color 0.18s;
    }
    .link-accent:hover { color: #dcc088; }

    .btn-primary {
      width: 100%;
      background: linear-gradient(145deg, var(--accent), var(--accent-d));
      border: none; border-radius: 9px;
      padding: 0.8rem; color: #1a1408;
      font-size: 0.9rem; font-weight: 600; letter-spacing: 0.01em;
      font-family: inherit; cursor: pointer;
      transition: filter 0.18s, transform 0.05s;
    }
    .btn-primary:hover { filter: brightness(1.07); }
    .btn-primary:active { transform: translateY(1px); }
    .btn-primary:disabled { opacity: 0.6; cursor: default; filter: none; }

    .back-row { margin-top: 1.6rem; text-align: center; }
    .back-row a {
      font-size: 0.78rem; color: var(--text-dim); text-decoration: none;
      transition: color 0.18s;
    }
    .back-row a:hover { color: var(--text); }

    /* ── Responsive ─────────────────────────────── */
    @media (max-width: 900px) {
      body { grid-template-columns: 1fr; }
      .brand-panel { display: none; }
      .form-panel { background: var(--bg); min-height: 100vh; }
      .form-wrap {
        background: var(--panel);
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 2.2rem 1.8rem;
      }
    }
  </style>
  <link rel="stylesheet" href="admin.css">
  <link rel="stylesheet" href="/theme.css?v=20261027-2">
</head>
<body>

  <!-- Left: brand -->
  <aside class="brand-panel">
    <div class="brand-top">
      <span class="brand-mark">T</span>
      <span class="brand-name">Think Room</span>
    </div>

    <div class="brand-center">
      <p class="eyebrow">Admin Console</p>
      <blockquote>
        "If hell's fury awaits my soul, then let it be — for God's fate for me
        must be to burn in its flames."
      </blockquote>
      <cite>— Freddy Carias</cite>
    </div>

    <div class="brand-foot">&copy; 2025 Freddy Carias. All rights reserved.</div>
  </aside>

  <!-- Right: form -->
  <main class="form-panel">
    <div class="form-wrap">
      <div class="form-head">
        <h1>Sign in</h1>
        <p>Enter your credentials to access the admin console.</p>
      </div>

      <div class="alert error"   id="errorMsg" style="<?= $loginError ? 'display:block;' : '' ?>">Incorrect email or password.</div>
      <div class="alert success" id="successMsg"></div>

      <form id="loginForm" action="admin_login.php" method="POST">
        <div class="field">
          <label for="email">Email</label>
          <div class="input-wrap">
            <input type="email" id="email" name="email" placeholder="you@example.com" autocomplete="username" required />
          </div>
        </div>

        <div class="field">
          <label for="password">Password</label>
          <div class="input-wrap">
            <input type="password" id="password" name="password" placeholder="••••••••" autocomplete="current-password" required />
            <button type="button" class="pw-toggle" id="pwToggle" aria-label="Show password">Show</button>
          </div>
        </div>

        <div class="row-between">
          <span class="remember">
            <input type="checkbox" id="rememberMe" name="remember" />
            <label for="rememberMe">Keep me signed in for 7 days</label>
          </span>
          <a href="forgot_password.php" class="link-accent">Forgot password?</a>
        </div>

        <button type="submit" class="btn-primary" id="submitBtn">Sign in</button>
      </form>

      <div class="back-row">
        <a href="/">&larr; Back to site</a>
      </div>
    </div>
  </main>

<script>
  // ── Remember me ──
  const savedEmail = localStorage.getItem("tr_remember_email");
  if (savedEmail) {
    document.getElementById("email").value = savedEmail;
    document.getElementById("rememberMe").checked = true;
  }

  // ── Password show/hide ──
  document.getElementById("pwToggle").addEventListener("click", function () {
    const pw = document.getElementById("password");
    const show = pw.type === "password";
    pw.type = show ? "text" : "password";
    this.textContent = show ? "Hide" : "Show";
  });

  // ── Login submit ──
  // Native form POST (so Chrome offers to save the password). We only remember
  // the email convenience value and let the form submit normally.
  document.getElementById("loginForm").addEventListener("submit", function () {
    const email    = document.getElementById("email").value.trim();
    const remember = document.getElementById("rememberMe").checked;
    if (remember) localStorage.setItem("tr_remember_email", email);
    else localStorage.removeItem("tr_remember_email");

    // Defer disabling the submit button. Disabling it synchronously during the
    // submit event can suppress Chrome's "Save password?" prompt, so we wait a
    // tick — by then the form navigation (and the password-manager capture) has
    // already started.
    const btn = document.getElementById("submitBtn");
    setTimeout(function () {
      btn.disabled = true;
      btn.textContent = "Signing in…";
    }, 0);
    // allow the form to submit normally
  });
</script>
</body>
</html>
