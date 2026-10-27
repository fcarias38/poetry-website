<?php
include 'maintenance_check.php';
require_once 'csrf.php';
header('Cache-Control: private, no-store');
$supportToken = csrf_token();
?>
<!DOCTYPE html>
<html lang="en" data-theme-scope="site" data-theme="dark">
<head>
  <script src="/theme.js?v=20261027-4"></script>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>About the Author | Think Room</title>
  <meta name="description" content="Freddy Carias is a poet and the creator of Think Room, where he shares his original poetry." />
  <meta name="robots" content="index, follow" />
  <link rel="canonical" href="https://freddycarias.com/author" />
  <meta property="og:title" content="About the Author | Think Room" />
  <meta property="og:description" content="Meet Freddy Carias, the poet behind Think Room." />
  <meta property="og:url" content="https://freddycarias.com/author" />
  <meta property="og:type" content="profile" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400;1,600&display=swap" rel="stylesheet" />
  <style>
    :root {
      --bg:#0e0e10; --panel:#161618; --surface:#1b1b1e; --surface-2:#202024;
      --border:rgba(255,255,255,0.09); --border-2:rgba(255,255,255,0.14);
      --text:#ededed; --text-dim:#9a978f; --text-mute:#6b6862;
      --accent:#c8a96e; --accent-d:#b2965e;
    }
    *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
    html { scroll-behavior:smooth; }
    body { font-family:"Inter",system-ui,sans-serif; background:var(--bg); color:var(--text-dim); min-height:100vh; display:flex; flex-direction:column; -webkit-font-smoothing:antialiased; }

    .topnav { position:sticky; top:0; z-index:200; background:rgba(22,22,24,0.92); backdrop-filter:blur(10px); border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; padding:0 2.5rem; height:62px; }
    .topnav-brand { font-family:"Cormorant Garamond",serif; font-size:1.3rem; font-weight:600; font-style:italic; color:var(--text); text-decoration:none; display:flex; align-items:center; gap:0.55rem; }
    .brand-mark { width:30px; height:30px; border-radius:7px; background:linear-gradient(145deg,var(--accent),var(--accent-d)); display:inline-flex; align-items:center; justify-content:center; font-family:"Cormorant Garamond",serif; font-weight:600; font-size:1.1rem; color:#1a1408; font-style:italic; flex-shrink:0; }
    .topnav-links { display:flex; align-items:center; gap:2rem; list-style:none; }
    .topnav-links a { text-decoration:none; color:var(--text-dim); font-size:0.72rem; font-weight:500; letter-spacing:0.14em; text-transform:uppercase; transition:color 0.2s; }
    .topnav-links a:hover { color:var(--accent); }
    /* ── Topics dropdown ── */
    .topics-dd { position:relative; }
    .topics-trigger { display:inline-flex; align-items:center; gap:0.35rem; background:none; border:none; color:var(--text-dim); font-family:"Inter",sans-serif; font-size:0.72rem; font-weight:500; letter-spacing:0.14em; text-transform:uppercase; cursor:pointer; transition:color 0.2s; }
    .topics-trigger:hover, .topics-dd.open .topics-trigger { color:var(--accent); }
    .topics-trigger .chev { font-size:0.7rem; transition:transform 0.2s; }
    .topics-dd.open .topics-trigger .chev { transform:rotate(180deg); }
    .topics-menu { position:absolute; top:calc(100% + 0.8rem); right:0; min-width:180px; background:var(--panel); border:1px solid var(--border); border-radius:10px; padding:0.4rem; box-shadow:0 12px 30px rgba(0,0,0,0.45); opacity:0; visibility:hidden; transform:translateY(-6px); transition:opacity 0.18s, transform 0.18s, visibility 0.18s; z-index:300; max-height:60vh; overflow-y:auto; }
    .topics-dd.open .topics-menu { opacity:1; visibility:visible; transform:translateY(0); }
    .topics-menu a { display:block; padding:0.55rem 0.8rem; border-radius:7px; color:var(--text-dim); font-size:0.74rem; letter-spacing:0.08em; text-transform:none; text-decoration:none; transition:background 0.15s, color 0.15s; }
    .topics-menu a:hover { background:var(--surface-2); color:var(--text); }
    .topnav-links a:hover, .topnav-links a.active { color:var(--accent); }
    .topnav-hamburger { display:none; background:none; border:none; color:var(--text-dim); font-size:1.4rem; cursor:pointer; }
    .mobile-menu { display:none; background:var(--panel); border-bottom:1px solid var(--border); flex-direction:column; padding:0.5rem 1.4rem 1rem; position:sticky; top:62px; z-index:199; }
    .mobile-menu.open { display:flex; }
    .mobile-menu a { color:var(--text-dim); text-decoration:none; font-size:0.72rem; font-weight:500; letter-spacing:0.12em; text-transform:uppercase; padding:0.8rem 0; border-bottom:1px solid var(--border); }
    .mobile-menu a:last-child { border-bottom:none; }

    .wrap { width:100%; max-width:720px; flex:1; margin:0 auto; padding:4rem 2rem 5rem; }
    .eyebrow { font-size:0.64rem; font-weight:600; letter-spacing:0.24em; text-transform:uppercase; color:var(--accent); margin-bottom:0.9rem; }
    h1 { font-family:"Cormorant Garamond",serif; font-size:clamp(2.2rem,6vw,3.4rem); font-weight:400; font-style:italic; color:var(--text); line-height:1.1; margin-bottom:1rem; }
    .rule { width:44px; height:2px; background:var(--accent); opacity:0.6; margin:1.8rem 0; }
    .prose p { font-family:"Cormorant Garamond",serif; font-size:1.25rem; font-weight:300; line-height:1.9; color:#d4cec2; margin-bottom:1.5rem; }
    .support-card { position:relative; display:grid; grid-template-columns:minmax(0,1fr) auto; align-items:center; gap:1.4rem; margin-top:3rem; padding-top:2rem; border-top:1px solid var(--border); }
    .support-card h2 { font-family:"Cormorant Garamond",serif; font-size:1.65rem; font-weight:400; color:var(--text); line-height:1.2; margin-bottom:0.55rem; }
    .support-note { font-size:0.8rem; color:var(--text-dim); line-height:1.7; }
    .support-card .support-reveal { display:inline-flex; align-items:center; justify-content:center; gap:0.75rem; min-height:46px; margin:0; padding:0.65rem 1.1rem; border:1px solid var(--accent-d); border-radius:999px; background:transparent; color:var(--accent); font:500 0.76rem/1.5 "Inter",system-ui,sans-serif; transition:background 0.2s,border-color 0.2s; }
    .support-card .support-reveal:hover { background:var(--surface); border-color:var(--accent); }
    .support-card .support-reveal:disabled { cursor:wait; opacity:0.65; }
    .support-card .support-reveal[hidden] { display:none; }
    .support-reveal svg { width:16px; height:16px; flex-shrink:0; }
    .support-card #supportDetails { grid-column:1/-1; margin:0; padding:0.2rem 0 0.2rem 1rem; border-left:2px solid var(--accent-d); color:var(--text); font-size:0.9rem; line-height:1.8; overflow-wrap:anywhere; }

    .page-footer { padding:1.6rem 2.5rem; border-top:1px solid var(--border); font-size:0.64rem; color:var(--text-mute); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.5rem; letter-spacing:0.08em; text-transform:uppercase; }
    .page-footer a { color:var(--text-dim); text-decoration:none; transition:color 0.2s; }
    .page-footer a:hover { color:var(--accent); }

    @media (max-width:640px) {
      .topnav { padding:0 1.2rem; }
      .topnav-links { display:none; }
      .topnav-hamburger { display:block; }
      .wrap { padding:2.5rem 1.2rem 3.5rem; }
      .support-card { grid-template-columns:minmax(0,1fr); gap:1.1rem; margin-top:2.5rem; padding-top:1.6rem; }
      .support-card .support-reveal { justify-self:start; }
      .page-footer { padding:1.8rem 1.2rem 2.2rem; flex-direction:column; align-items:center; justify-content:center; text-align:center; gap:1rem; }
      .page-footer span { display:block; }
    }
  </style>
  <link rel="stylesheet" href="/theme.css?v=20261027-4">
</head>
<body>
  <nav class="topnav">
    <a href="/" class="topnav-brand"><span class="brand-mark">T</span>Think Room</a>
    <ul class="topnav-links">
      <li class="topics-dd" id="topicsDd">
        <button class="topics-trigger" id="topicsTrigger" aria-haspopup="true" aria-expanded="false">Topics <i class="bi bi-chevron-down chev"></i></button>
        <div class="topics-menu" id="topicsMenu" role="menu"></div>
      </li>
      <li><a href="/author" class="active">Author</a></li>
      <li><a href="/contact">Contact</a></li>
    </ul>
    <button class="topnav-hamburger" id="hamburger" aria-label="Menu"><i class="bi bi-list"></i></button>
  </nav>
  <div class="mobile-menu" id="mobileMenu">
    <details class="mobile-topics">
      <summary>Topics</summary>
      <div id="mobileTopics"></div>
    </details>
    <a href="/author">Author</a>
    <a href="/contact">Contact</a>
  </div>

  <main class="wrap">
    <p class="eyebrow">About the author</p>
    <h1>Freddy Carias</h1>
    <div class="rule"></div>
    <div class="prose">
      <p>Freddy Carias is a poet and the creator of <strong style="color:var(--text);font-style:normal;">Think Room</strong>, where he shares his original poetry.</p>
    </div>

    <section class="support-card" aria-labelledby="supportTitle">
      <div class="support-copy">
        <h2 id="supportTitle">Support the poetry</h2>
        <p class="support-note">Contributions via Zelle are welcome.</p>
      </div>
      <button class="support-reveal" id="showZelle" type="button" aria-expanded="false" aria-controls="supportDetails"><span>Support via Zelle</span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M5 12h14m-5-5 5 5-5 5"/></svg></button>
      <div class="support-trap" aria-hidden="true"><label for="supportWebsite">Website</label><input id="supportWebsite" type="text" tabindex="-1" autocomplete="off"></div>
      <p id="supportDetails" role="status" aria-live="polite" hidden></p>
    </section>
  </main>

  <footer class="page-footer">
    <span>&copy; 2025 Freddy Carias &mdash; All Rights Reserved</span>
  </footer>

  <script>
    document.getElementById("showZelle").addEventListener("click", async function () {
      const details = document.getElementById("supportDetails");
      this.disabled = true;
      details.hidden = false;
      details.textContent = "Loading Zelle details...";
      try {
        const response = await fetch("/support_details.php", {
          method: "POST",
          headers: { "Content-Type": "application/json", "X-CSRF-Token": <?= json_encode($supportToken) ?> },
          body: JSON.stringify({ website: document.getElementById("supportWebsite").value })
        });
        const data = await response.json();
        if (!response.ok || !data.email) throw new Error(data.error || "Please try again later.");
        details.textContent = "Zelle: " + data.email;
        this.setAttribute("aria-expanded", "true");
        this.hidden = true;
      } catch (error) {
        details.textContent = error.message;
        this.disabled = false;
      }
    });

    document.getElementById("hamburger").addEventListener("click", () => {
      document.getElementById("mobileMenu").classList.toggle("open");
    });

    // ── Topics dropdown: build from poem categories, link to gallery ──
    function esc(s){return (s==null?"":String(s)).replace(/[&<>"']/g,c=>({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;"}[c]));}
    (function initTopics(){
      const dd      = document.getElementById("topicsDd");
      const trigger = document.getElementById("topicsTrigger");
      const menu    = document.getElementById("topicsMenu");
      const mobile  = document.getElementById("mobileTopics");

      trigger.addEventListener("click", e => {
        e.stopPropagation();
        const open = dd.classList.toggle("open");
        trigger.setAttribute("aria-expanded", open ? "true" : "false");
      });
      document.addEventListener("click", () => {
        dd.classList.remove("open");
        trigger.setAttribute("aria-expanded", "false");
      });

      fetch("get_poems.php").then(r => r.json()).then(data => {
        const cats = [];
        (data || []).forEach(p => { if (p.category && !cats.includes(p.category)) cats.push(p.category); });
        if (!cats.length) { dd.style.display = "none"; return; }
        let html = '<a href="/">All poems</a>';
        let mhtml = '<a href="/gallery">All poems</a>';
        cats.forEach(c => {
          const label = c.charAt(0).toUpperCase() + c.slice(1);
          const href  = '/gallery?topic=' + encodeURIComponent(c);
          html  += '<a href="' + href + '">' + esc(label) + '</a>';
          mhtml += '<a href="' + href + '">' + esc(label) + '</a>';
        });
        menu.innerHTML = html;
        if (mobile) mobile.innerHTML = mhtml;
      }).catch(() => { dd.style.display = "none"; });
    })();
  </script>
</body>
</html>
