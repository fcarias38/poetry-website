<?php include 'maintenance_check.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>About the Author | Think Room</title>
  <meta name="description" content="About Freddy Carias — the voice behind Think Room, a gallery of poetry and reflection." />
  <meta name="robots" content="index, follow" />
  <link rel="canonical" href="https://freddycarias.com/author" />
  <meta property="og:title" content="About the Author | Think Room" />
  <meta property="og:description" content="About Freddy Carias — the voice behind Think Room." />
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
    body { font-family:"Inter",system-ui,sans-serif; background:var(--bg); color:var(--text-dim); min-height:100vh; -webkit-font-smoothing:antialiased; }

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

    .wrap { max-width:720px; margin:0 auto; padding:4rem 2rem 5rem; }
    .eyebrow { font-size:0.64rem; font-weight:600; letter-spacing:0.24em; text-transform:uppercase; color:var(--accent); margin-bottom:0.9rem; }
    h1 { font-family:"Cormorant Garamond",serif; font-size:clamp(2.2rem,6vw,3.4rem); font-weight:400; font-style:italic; color:var(--text); line-height:1.1; margin-bottom:1rem; }
    .rule { width:44px; height:2px; background:var(--accent); opacity:0.6; margin:1.8rem 0; }
    .prose p { font-family:"Cormorant Garamond",serif; font-size:1.25rem; font-weight:300; line-height:1.9; color:#d4cec2; margin-bottom:1.5rem; }
    .support-card { background:var(--surface); border:1px solid var(--border); border-radius:12px; padding:1.6rem 1.8rem; margin-top:2.5rem; }
    .support-card .label { font-size:0.6rem; font-weight:600; letter-spacing:0.2em; text-transform:uppercase; color:var(--accent); margin-bottom:0.8rem; }
    .support-card p { font-size:0.95rem; color:var(--text-dim); line-height:1.7; }
    .support-card strong { color:var(--text); }

    .page-footer { padding:1.6rem 2.5rem; border-top:1px solid var(--border); font-size:0.64rem; color:var(--text-mute); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.5rem; letter-spacing:0.08em; text-transform:uppercase; }
    .page-footer a { color:var(--text-dim); text-decoration:none; transition:color 0.2s; }
    .page-footer a:hover { color:var(--accent); }

    @media (max-width:640px) {
      .topnav { padding:0 1.2rem; }
      .topnav-links { display:none; }
      .topnav-hamburger { display:block; }
      .wrap { padding:2.5rem 1.2rem 3.5rem; }
      .page-footer { padding:1.2rem; flex-direction:column; align-items:flex-start; }
    }
  </style>
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
    <a href="/author">Author</a>
    <a href="/contact">Contact</a>
    <div id="mobileTopics"></div>
  </div>

  <main class="wrap">
    <p class="eyebrow">About the author</p>
    <h1>Freddy Carias</h1>
    <div class="rule"></div>
    <div class="prose">
      <p>Freddy Carias is the voice behind <strong style="color:var(--text);font-style:normal;">Think Room</strong> — a quiet gallery of thoughts, poetry, and reflection.</p>
      <p>Every word here is written from lived experience, raw honesty, and a desire to make others feel less alone. These poems move through faith, love, loss, and the search for meaning — a record of a soul thinking out loud.</p>
    </div>

    <div class="support-card">
      <p class="label">Support the work</p>
      <p>If these words have meant something to you, you can support their continued creation.</p>
      <p style="margin-top:0.8rem;">Zelle: <strong>fcarias38@outlook.com</strong></p>
    </div>
  </main>

  <footer class="page-footer">
    <span>&copy; 2025 Freddy Carias &mdash; All Rights Reserved</span>
    <a href="/">&larr; Back to the site</a>
  </footer>

  <script>
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
        let mhtml = '';
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
