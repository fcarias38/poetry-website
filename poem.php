<?php include 'maintenance_check.php'; ?>
<!DOCTYPE html>
<html lang="en" data-theme-scope="site" data-theme="dark">
  <head>
  <script src="/theme.js?v=20261027-4"></script>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <?php
    include 'config.php';

    // Support both /poems/slug and ?id=N for backwards compat
    $poem = null;

    if (!empty($_GET['slug'])) {
        $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($_GET['slug']));
        $stmt = $conn->prepare("SELECT id, title, category, body, likes, slug, meta_description, meta_keywords, archived FROM poems WHERE slug = ?");
        $stmt->bind_param("s", $slug);
        $stmt->execute();
        $poem = $stmt->get_result()->fetch_assoc();
    } elseif (!empty($_GET['id'])) {
        $id = intval($_GET['id']);
        $stmt = $conn->prepare("SELECT id, title, category, body, likes, slug, meta_description, meta_keywords, archived FROM poems WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $poem = $stmt->get_result()->fetch_assoc();
    }

    if ($poem) {
        // Auto-generate slug if missing
        if (empty($poem['slug'] ?? '')) {
            $autoSlug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $poem['title']), '-'));
            $checkStmt = $conn->prepare("SHOW COLUMNS FROM poems LIKE 'slug'");
            $checkStmt->execute();
            if ($checkStmt->get_result()->num_rows > 0) {
                $upStmt = $conn->prepare("UPDATE poems SET slug = ? WHERE id = ? AND (slug IS NULL OR slug = '')");
                $upStmt->bind_param("si", $autoSlug, $poem['id']);
                $upStmt->execute();
                $poem['slug'] = $autoSlug;
            }
        }

        $canonicalSlug = $poem['slug'] ?? $poem['id'];
    }

    $title    = $poem ? htmlspecialchars($poem['title'])    : 'Think Room';
    $category = $poem ? htmlspecialchars($poem['category']) : '';
    $body     = $poem ? htmlspecialchars($poem['body'])     : '';
    $likes    = $poem ? intval($poem['likes'])              : 0;
    // Prefer the custom SEO meta description; fall back to an auto excerpt.
    $autoExcerpt = $poem ? htmlspecialchars(mb_substr(strip_tags($poem['body']), 0, 160)) . '…' : '';
    $customDesc  = $poem && !empty($poem['meta_description']) ? htmlspecialchars($poem['meta_description']) : '';
    $excerpt     = $customDesc !== '' ? $customDesc : $autoExcerpt;
    $metaKeywords = $poem && !empty($poem['meta_keywords']) ? htmlspecialchars($poem['meta_keywords']) : '';
    $isArchived   = $poem ? intval($poem['archived'] ?? 0) : 0;
    $canonicalUrl = isset($canonicalSlug) ? "https://freddycarias.com/poems/{$canonicalSlug}" : "https://freddycarias.com/gallery";
    $ogImage  = "https://freddycarias.com/images/jessica-christian-wuqYCTAYPH4-unsplash.jpg";
    ?>

    <title><?= $title ?> — Think Room by Freddy Carias</title>
    <meta name="description" content="<?= $excerpt ?>" />
    <meta name="author" content="Freddy Carias" />
    <?php if ($metaKeywords !== ''): ?>
    <meta name="keywords" content="<?= $metaKeywords ?>" />
    <?php endif; ?>
    <meta name="robots" content="<?= $isArchived ? 'noindex, follow' : 'index, follow' ?>" />
    <link rel="canonical" href="<?= $canonicalUrl ?>" />

    <meta property="og:title"       content="<?= $title ?> — Think Room" />
    <meta property="og:description" content="<?= $excerpt ?>" />
    <meta property="og:image"       content="<?= $ogImage ?>" />
    <meta property="og:url"         content="<?= $canonicalUrl ?>" />
    <meta property="og:type"        content="article" />
    <meta property="og:site_name"   content="Think Room" />

    <meta name="twitter:card"        content="summary_large_image" />
    <meta name="twitter:title"       content="<?= $title ?> — Think Room" />
    <meta name="twitter:description" content="<?= $excerpt ?>" />
    <meta name="twitter:image"       content="<?= $ogImage ?>" />

    <?php if ($poem): ?>
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Poem",
      "name": "<?= addslashes($title) ?>",
      "author": { "@type": "Person", "name": "Freddy Carias", "url": "https://freddycarias.com" },
      "url": "<?= $canonicalUrl ?>",
      "genre": "<?= addslashes($category) ?>"
    }
    </script>
    <?php endif; ?>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400;1,600&display=swap" rel="stylesheet" />

    <style>
      :root {
        --bg:        #0e0e10;
        --panel:     #161618;
        --surface:   #1b1b1e;
        --surface-2: #202024;
        --border:    rgba(255,255,255,0.09);
        --border-2:  rgba(255,255,255,0.14);
        --text:      #ededed;
        --text-dim:  #9a978f;
        --text-mute: #6b6862;
        --accent:    #c8a96e;
        --accent-d:  #b2965e;
      }
      *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
      html { scroll-behavior: smooth; }

      body {
        font-family: "Inter", system-ui, sans-serif;
        background: var(--bg);
        color: var(--text-dim);
        min-height: 100vh;
        -webkit-font-smoothing: antialiased;
      }

      /* ── Nav ── */
      .topnav {
        position: sticky; top: 0; z-index: 200;
        background: rgba(22,22,24,0.92);
        backdrop-filter: blur(10px);
        border-bottom: 1px solid var(--border);
        display: flex; align-items: center; justify-content: space-between;
        padding: 0 2.5rem; height: 62px;
      }
      .topnav-brand {
        font-family: "Cormorant Garamond", serif;
        font-size: 1.3rem; font-weight: 600; font-style: italic;
        color: var(--text); text-decoration: none;
        display: flex; align-items: center; gap: 0.55rem;
      }
      .topnav-brand:hover { color: #fff; }
      .brand-mark {
        width: 30px; height: 30px; border-radius: 7px;
        background: linear-gradient(145deg, var(--accent), var(--accent-d));
        display: inline-flex; align-items: center; justify-content: center;
        font-family: "Cormorant Garamond", serif; font-weight: 600;
        font-size: 1.1rem; color: #1a1408; font-style: italic; flex-shrink: 0;
      }
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

      /* ── Layout ── */
      .page {
        max-width: 660px;
        margin: 0 auto;
        padding: 4rem 2rem 6rem;
      }

      /* ── Poem header ── */
      .poem-eyebrow {
        font-size: 0.6rem; font-weight: 600; letter-spacing: 0.22em; text-transform: uppercase;
        color: var(--accent); margin-bottom: 0.9rem;
      }
      .poem-title {
        font-family: "Cormorant Garamond", serif;
        font-size: clamp(2rem, 6vw, 3rem);
        font-weight: 400; font-style: italic;
        color: var(--text); line-height: 1.15; margin-bottom: 0.5rem;
      }
      .poem-byline {
        font-size: 0.64rem; font-weight: 500; letter-spacing: 0.16em; text-transform: uppercase;
        color: var(--text-mute); margin-bottom: 3rem;
      }
      .poem-rule {
        width: 40px; height: 2px; background: var(--accent);
        opacity: 0.6; margin-bottom: 3rem;
      }

      /* ── Poem text ── */
      .poem-body {
        font-family: "Cormorant Garamond", serif;
        font-size: clamp(1.15rem, 2.4vw, 1.3rem);
        font-weight: 400; font-style: italic;
        line-height: 1.75; color: #e4ddd0;
        white-space: pre-wrap; word-wrap: break-word;
        margin-bottom: 3.5rem;
        letter-spacing: 0.005em;
      }

      /* ── Actions ── */
      .poem-actions {
        display: flex; align-items: center;
        gap: 0.5rem; flex-wrap: wrap;
        padding-bottom: 0;
        margin-bottom: 0;
      }
      .act-btn {
        background: var(--surface);
        border: 1px solid var(--border);
        color: var(--text-dim); font-size: 0.64rem; font-weight: 500;
        letter-spacing: 0.1em; text-transform: uppercase;
        cursor: pointer; display: flex; align-items: center; gap: 0.35rem;
        padding: 0.5rem 1rem; border-radius: 8px;
        transition: color 0.2s, background 0.2s, border-color 0.2s;
        font-family: "Inter", sans-serif; white-space: nowrap;
      }
      .act-btn:hover { color: var(--accent); background: var(--surface-2); border-color: var(--border-2); }
      .act-btn.liked { color: var(--accent); border-color: var(--accent); }
      .act-btn i { font-size: 0.85rem; }

      .act-divider { width: 1px; height: 22px; background: var(--border); margin: 0 0.3rem; flex-shrink: 0; }
      .act-icon {
        width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;
        background: var(--surface); border: 1px solid var(--border); border-radius: 8px;
        color: var(--text-dim); cursor: pointer; transition: all 0.18s; flex-shrink: 0;
      }
      .act-icon i { font-size: 1rem; }
      .act-icon:hover { color: var(--accent); background: var(--surface-2); border-color: var(--border-2); transform: translateY(-1px); }
      .act-icon.copied { color: var(--accent); border-color: var(--border-2); background: var(--surface-2); }

      .copy-confirm {
        font-size: 0.64rem; font-weight: 500; letter-spacing: 0.1em;
        text-transform: uppercase; color: var(--accent);
        opacity: 0; transform: translateX(-4px);
        transition: opacity 0.2s, transform 0.2s;
        pointer-events: none; white-space: nowrap;
      }
      .copy-confirm.show { opacity: 1; transform: translateX(0); }

      /* ── Comments ── */
      /* Count badge sits inside the inline comment button in the actions row. */
      .comment-count {
        background: rgba(200,169,110,0.16); color: var(--accent);
        font-size: 0.6rem; font-weight: 600; letter-spacing: 0;
        min-width: 1.15rem; height: 1.15rem; padding: 0 0.35rem;
        display: inline-flex; align-items: center; justify-content: center;
        border-radius: 999px; margin-left: 0.45rem;
      }
      /* When the comment button is active (panel open), highlight it. */
      .act-btn[aria-expanded="true"] { color: var(--accent); border-color: var(--border-2); background: var(--surface-2); }

      .comments-panel {
        max-height: 0; overflow: hidden; opacity: 0;
        transition: max-height 0.35s ease, opacity 0.3s ease, margin-top 0.3s ease;
        margin-top: 0;
      }
      .comments-section { margin-top: 2rem; }
      .comments-section[hidden] { display: none; }
      .comment-form:first-child { margin-top: 0; }
      .comments-panel.open {
        max-height: 4000px; opacity: 1; margin-top: 0.5rem;
      }
      .comment-item {
        padding: 1rem 1.2rem;
        background: var(--surface);
        border: 1px solid var(--border);
        border-left: 2px solid rgba(200,169,110,0.4);
        border-radius: 8px;
        margin-bottom: 0.8rem;
        font-size: 0.88rem; color: var(--text-dim); line-height: 1.75;
      }
      .comment-item strong { color: var(--text); }
      .comment-time { font-size: 0.66rem; color: var(--text-mute); margin-top: 0.35rem; }
      .comment-empty { font-size: 0.84rem; color: var(--text-mute); font-style: italic; margin-bottom: 1.5rem; }

      .comment-form { margin-top: 2rem; }
      .comment-form label {
        font-size: 0.62rem; font-weight: 500; letter-spacing: 0.12em; text-transform: uppercase;
        color: var(--text-dim); display: block; margin-bottom: 0.45rem;
      }
      .comment-form input,
      .comment-form textarea {
        width: 100%; background: var(--surface);
        border: 1px solid var(--border); border-radius: 9px;
        padding: 0.7rem 0.85rem; color: var(--text); font-size: 0.9rem;
        font-family: "Inter", sans-serif;
        margin-bottom: 1.2rem; resize: none; transition: border-color 0.2s, box-shadow 0.2s;
      }
      .comment-form input::placeholder,
      .comment-form textarea::placeholder { color: var(--text-mute); }
      .comment-form input:focus,
      .comment-form textarea:focus {
        outline: none; border-color: var(--accent);
        box-shadow: 0 0 0 3px rgba(200,169,110,0.14);
      }
      .comment-submit {
        background: linear-gradient(145deg, var(--accent), var(--accent-d));
        border: none; color: #1a1408;
        padding: 0.65rem 1.7rem; font-family: "Inter", sans-serif;
        font-size: 0.7rem; font-weight: 600; letter-spacing: 0.04em;
        cursor: pointer; border-radius: 9px; transition: filter 0.2s;
      }
      .comment-submit:hover { filter: brightness(1.08); }

      /* ── Read this next ── */
      .read-next {
        margin-top: 3.5rem;
        padding-top: 2.5rem;
        border-top: 1px solid var(--border);
      }
      .read-next-heading {
        font-family: "Cormorant Garamond", serif;
        font-size: 1.4rem; font-style: italic; font-weight: 500;
        color: var(--text); margin-bottom: 1.5rem;
      }
      .read-next-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
      }
      .read-next-card {
        display: flex; flex-direction: column;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 1.3rem 1.3rem 1.1rem;
        text-decoration: none;
        transition: border-color 0.2s, background 0.2s, transform 0.2s;
      }
      .read-next-card:hover {
        border-color: var(--border-2);
        background: var(--surface-2);
        transform: translateY(-3px);
      }
      .read-next-eyebrow {
        font-size: 0.58rem; font-weight: 500; letter-spacing: 0.16em;
        text-transform: uppercase; color: var(--accent);
        margin-bottom: 0.55rem;
      }
      .read-next-title {
        font-family: "Cormorant Garamond", serif;
        font-size: 1.3rem; font-style: italic; font-weight: 500;
        color: var(--text); line-height: 1.25; margin-bottom: 0.55rem;
      }
      .read-next-excerpt {
        font-size: 0.82rem; color: var(--text-mute); line-height: 1.65;
        margin-bottom: 1rem; flex-grow: 1;
      }
      .read-next-cue {
        font-size: 0.6rem; font-weight: 500; letter-spacing: 0.12em;
        text-transform: uppercase; color: var(--text-dim);
        display: inline-flex; align-items: center; gap: 0.35rem;
        transition: color 0.2s, gap 0.2s;
      }
      .read-next-card:hover .read-next-cue { color: var(--accent); gap: 0.55rem; }

      .read-next-all {
        display: inline-flex; align-items: center; gap: 0.4rem;
        margin-top: 1.6rem;
        font-size: 0.64rem; font-weight: 500; letter-spacing: 0.12em;
        text-transform: uppercase; color: var(--text-dim);
        text-decoration: none; transition: color 0.2s, gap 0.2s;
      }
      .read-next-all:hover { color: var(--accent); gap: 0.6rem; }

      /* ── Footer ── */
      .page-footer {
        text-align: center; padding: 2rem;
        font-size: 0.62rem; color: var(--text-mute);
        letter-spacing: 0.08em; text-transform: uppercase;
        border-top: 1px solid var(--border);
      }
      .page-footer a { color: var(--text-dim); text-decoration: none; transition: color 0.2s; }
      .page-footer a:hover { color: var(--accent); }

      /* ── 404 ── */
      .not-found {
        max-width: 480px; margin: 8rem auto; text-align: center; padding: 2rem;
      }
      .not-found h2 {
        font-family: "Cormorant Garamond", serif;
        font-size: 2rem; font-style: italic; color: var(--text); margin-bottom: 1rem;
      }
      .not-found p { color: var(--text-dim); font-size: 0.88rem; margin-bottom: 2rem; line-height: 1.7; }
      .not-found a {
        color: var(--accent); text-decoration: none; font-size: 0.66rem; font-weight: 500;
        letter-spacing: 0.14em; text-transform: uppercase;
      }

      @media (max-width: 640px) {
        .topnav { padding: 0 1.2rem; }
        .topnav-links { display:none; }
        .topnav-hamburger { display:block; }
        .page { padding: 2.5rem 1.2rem 4rem; }
        .read-next-grid { grid-template-columns: 1fr; }
      }
    </style>
    <link rel="stylesheet" href="/theme.css?v=20261027-4">
</head>
  <body>

  <nav class="topnav" aria-label="Main navigation">
    <a href="/" class="topnav-brand"><span class="brand-mark">T</span>Think Room</a>
    <ul class="topnav-links">
      <li class="topics-dd" id="topicsDd">
        <button class="topics-trigger" id="topicsTrigger" aria-expanded="false" aria-controls="topicsMenu">Topics <i class="bi bi-chevron-down chev"></i></button>
        <div class="topics-menu" id="topicsMenu"></div>
      </li>
      <li><a href="/author">Author</a></li>
      <li><a href="/contact">Contact</a></li>
    </ul>
    <button class="topnav-hamburger" id="hamburger" aria-label="Menu" aria-expanded="false" aria-controls="mobileMenu"><i class="bi bi-list"></i></button>
  </nav>
  <div class="mobile-menu" id="mobileMenu">
    <details class="mobile-topics">
      <summary>Topics</summary>
      <div id="mobileTopics"></div>
    </details>
    <a href="/author">Author</a>
    <a href="/contact">Contact</a>
  </div>

    <script>
      (() => {
        const hamburger = document.getElementById('hamburger');
        const mobileMenu = document.getElementById('mobileMenu');
        const dropdown = document.getElementById('topicsDd');
        const trigger = document.getElementById('topicsTrigger');
        const topics = document.getElementById('topicsMenu');
        const mobileTopics = document.getElementById('mobileTopics');
        const setMobileOpen = open => {
          mobileMenu.classList.toggle('open', open);
          hamburger.setAttribute('aria-expanded', String(open));
        };
        const setTopicsOpen = open => {
          dropdown.classList.toggle('open', open);
          trigger.setAttribute('aria-expanded', String(open));
        };
        hamburger.addEventListener('click', () => setMobileOpen(!mobileMenu.classList.contains('open')));
        trigger.addEventListener('click', () => setTopicsOpen(!dropdown.classList.contains('open')));
        document.addEventListener('click', event => {
          if (!dropdown.contains(event.target)) setTopicsOpen(false);
        });
        document.addEventListener('keydown', event => {
          if (event.key !== 'Escape') return;
          if (mobileMenu.classList.contains('open')) { setMobileOpen(false); hamburger.focus(); }
          if (dropdown.classList.contains('open')) { setTopicsOpen(false); trigger.focus(); }
        });
        function renderTopics(poems) {
          const categories = [...new Set(poems.map(poem => poem.category).filter(category => typeof category === 'string' && category.trim()))];
          topics.replaceChildren();
          mobileTopics.replaceChildren();
          for (const [label, href] of [['All poems', '/gallery'], ...categories.map(category => [category.charAt(0).toUpperCase() + category.slice(1), '/gallery?topic=' + encodeURIComponent(category)])]) {
            for (const parent of [topics, mobileTopics]) {
              const link = document.createElement('a');
              link.textContent = label; link.href = href;
              parent.appendChild(link);
            }
          }
        }
        renderTopics([]);
        fetch('/get_poems.php').then(response => {
          if (!response.ok) throw Error('Topics unavailable');
          return response.json();
        }).then(poems => { if (Array.isArray(poems)) renderTopics(poems); }).catch(() => {});
      })();
    </script>

    <?php if (!$poem): ?>
    <div class="not-found">
      <h2>Poem not found</h2>
      <p>This poem may have been moved or removed.</p>
      <a href="/">&larr; Back to the site</a>
    </div>
    <?php else: ?>

    <main class="page">
      <p class="poem-eyebrow"><?= $category ?></p>
      <h1 class="poem-title"><?= $title ?></h1>
      <p class="poem-byline">Freddy Carias</p>
      <div class="poem-rule"></div>
      <pre class="poem-body"><?= $body ?></pre>

      <?php
      // Pre-fetch approved comments so we know the count before rendering the
      // inline comment button in the actions row.
      $comments = [];
      $cStmt = $conn->prepare(
          "SELECT name, comment, created_at FROM comments WHERE poem_id = ? AND approved = 1 ORDER BY created_at ASC"
      );
      $cStmt->bind_param("i", $poem['id']);
      $cStmt->execute();
      $cResult = $cStmt->get_result();
      while ($c = $cResult->fetch_assoc()) { $comments[] = $c; }
      $cCount = count($comments);
      ?>

      <div class="poem-actions">
        <button class="act-btn like" id="likeBtn" onclick="likePoem()" title="Like this poem">
          <i class="bi bi-hand-thumbs-up" aria-hidden="true"></i>&nbsp;<span class="like-label">Like</span>&nbsp;<span id="likeCount"><?= $likes ?></span>
        </button>
        <button class="act-btn" id="commentToggle" onclick="toggleComments()"
                aria-expanded="<?= $cCount > 0 ? 'true' : 'false' ?>" aria-controls="commentsPanel" title="Comments">
          <i class="bi bi-chat-dots"></i>&nbsp;<?= $cCount > 0 ? 'Comments' : 'Leave a thought' ?>
          <?php if ($cCount > 0): ?><span class="comment-count"><?= $cCount ?></span><?php endif; ?>
        </button>
        <span class="act-divider"></span>
        <button class="act-btn" id="shareBtn" onclick="sharePoem()" title="Share this poem">
          <i class="bi bi-box-arrow-up"></i>&nbsp;Share
        </button>
        <span id="copyConfirm" class="copy-confirm" aria-live="polite">Link copied!</span>
      </div>

      <?php
      // ── "Read this next" suggestions ──
      // Prefer poems in the same category, then top up with the most recent
      // others. Archived poems and the current poem are excluded.
      $suggestions = [];
      $seen = [$poem['id'] => true];

      $sStmt = $conn->prepare(
          "SELECT id, title, category, body, slug FROM poems
           WHERE id != ? AND category = ? AND COALESCE(archived, 0) = 0
           ORDER BY id DESC LIMIT 3"
      );
      $sStmt->bind_param("is", $poem['id'], $poem['category']);
      $sStmt->execute();
      $sRes = $sStmt->get_result();
      while ($row = $sRes->fetch_assoc()) {
          $suggestions[] = $row;
          $seen[$row['id']] = true;
      }

      // Top up with recent poems from any category if we have fewer than 3.
      if (count($suggestions) < 3) {
          $fStmt = $conn->prepare(
              "SELECT id, title, category, body, slug FROM poems
               WHERE id != ? AND COALESCE(archived, 0) = 0
               ORDER BY id DESC LIMIT 10"
          );
          $fStmt->bind_param("i", $poem['id']);
          $fStmt->execute();
          $fRes = $fStmt->get_result();
          while (($row = $fRes->fetch_assoc()) && count($suggestions) < 3) {
              if (isset($seen[$row['id']])) continue;
              $suggestions[] = $row;
              $seen[$row['id']] = true;
          }
      }
      ?>

      <div id="commentsSection" class="comments-section"<?= $cCount === 0 ? ' hidden' : '' ?>>
        <!-- Toggle lives inline in the actions row above. Thread + form show here.
             Expanded by default when comments exist so the discussion is visible. -->
        <div id="commentsPanel" class="comments-panel<?= $cCount > 0 ? ' open' : '' ?>">
          <?php if ($cCount > 0): ?>
          <div id="commentsList">
            <?php foreach ($comments as $c): ?>
            <div class="comment-item">
              <?php if ($c['name']): ?><strong><?= htmlspecialchars($c['name']) ?></strong>: <?php endif; ?>
              <?= htmlspecialchars($c['comment']) ?>
              <div class="comment-time"><?= htmlspecialchars($c['created_at']) ?></div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

          <div class="comment-form">
            <label for="commentName">Your name (optional)</label>
            <input type="text" id="commentName" placeholder="Anonymous" />
            <label for="commentText">Leave a thought</label>
            <textarea id="commentText" rows="4" placeholder="What did this make you feel?"></textarea>
            <button class="comment-submit" onclick="postComment()">Post comment</button>
            <span id="commentMsg" style="display:none; font-size:0.72rem; color:#c8a96e; margin-left:1rem;"></span>
          </div>
        </div>
      </div>

      <?php if (!empty($suggestions)): ?>
      <section class="read-next">
        <h3 class="read-next-heading">Read this next</h3>
        <div class="read-next-grid">
          <?php foreach ($suggestions as $s):
            $sUrl     = '/poems/' . htmlspecialchars($s['slug'] ?: $s['id']);
            $sExcerpt = htmlspecialchars(mb_substr(trim(strip_tags($s['body'])), 0, 90));
          ?>
          <a class="read-next-card" href="<?= $sUrl ?>">
            <?php if (!empty($s['category'])): ?>
            <span class="read-next-eyebrow"><?= htmlspecialchars($s['category']) ?></span>
            <?php endif; ?>
            <span class="read-next-title"><?= htmlspecialchars($s['title']) ?></span>
            <span class="read-next-excerpt"><?= $sExcerpt ?>…</span>
            <span class="read-next-cue">Read <i class="bi bi-arrow-right"></i></span>
          </a>
          <?php endforeach; ?>
        </div>
        <a class="read-next-all" href="/">Browse all poems <i class="bi bi-arrow-right"></i></a>
      </section>
      <?php endif; ?>
    </main>



    <script>
      const POEM_TITLE = <?= json_encode($poem['title']) ?>;
      const POEM_ID    = <?= json_encode(intval($poem['id'])) ?>;
      const POEM_URL   = "<?= $canonicalUrl ?>";

      // ── Native view tracking (fire-and-forget) ──
      fetch("/track_view.php", {
        method: "POST", headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ id: POEM_ID })
      }).catch(() => {});

      function trackShare() {
        fetch("/track_share.php", {
          method: "POST", headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ id: POEM_ID })
        }).catch(() => {});
      }

      function likePoem() {
        fetch("/update_likes.php", {
          method: "POST", headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ id: POEM_ID })
        }).then(r => r.json()).then(data => {
          document.getElementById("likeCount").innerText = data.likes;
          document.getElementById("likeBtn").classList.add("liked");
          document.querySelector("#likeBtn .like-label").textContent = "Liked";
          document.querySelector("#likeBtn i").className = "bi bi-hand-thumbs-up-fill";
        });
      }
      // ── Share ──
      // Uses the native share sheet on devices that support it (mostly mobile),
      // and falls back to copying the link on desktop so the button always works.
      function sharePoem() {
        if (navigator.share) {
          navigator.share({
            title: POEM_TITLE,
            text: `"${POEM_TITLE}" — Think Room by Freddy Carias`,
            url: POEM_URL
          }).then(trackShare).catch(() => {});
        } else {
          copyLink();
        }
      }
      function copyLink() {
        const done = () => {
          trackShare();
          const el = document.getElementById("copyConfirm");
          el.classList.add("show");
          setTimeout(() => el.classList.remove("show"), 2000);
        };
        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText(POEM_URL).then(done).catch(fallbackCopy);
        } else {
          fallbackCopy();
        }
        function fallbackCopy() {
          const ta = document.createElement("textarea");
          ta.value = POEM_URL;
          ta.style.position = "fixed";
          ta.style.opacity = "0";
          document.body.appendChild(ta);
          ta.select();
          let copied = false;
          try { copied = document.execCommand("copy"); } catch (e) {}
          document.body.removeChild(ta);
          if (copied) done();
        }
      }

      // ── Comments toggle (Option A: thread open by default when comments exist) ──
      function toggleComments() {
        const panel  = document.getElementById("commentsPanel");
        const toggle = document.getElementById("commentToggle");
        const open   = panel.classList.toggle("open");
        document.getElementById("commentsSection").hidden = !open;
        toggle.setAttribute("aria-expanded", open ? "true" : "false");
        if (open) document.getElementById("commentText").focus();
      }

      function postComment() {
        const name    = document.getElementById("commentName").value.trim();
        const comment = document.getElementById("commentText").value.trim();
        if (!comment) return;
        fetch("/save_comment.php", {
          method: "POST", headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ id: POEM_ID, comment, name })
        }).then(r => r.json()).then(data => {
          if (data.success) {
            document.getElementById("commentText").value = "";
            document.getElementById("commentName").value = "";
            const msg = document.getElementById("commentMsg");
            msg.textContent = "Thank you — your comment is awaiting approval.";
            msg.style.display = "inline";
            setTimeout(() => msg.style.display = "none", 5000);
          } else {
            const msg = document.getElementById("commentMsg");
            msg.textContent = "Could not post. Please try again.";
            msg.style.display = "inline";
            setTimeout(() => msg.style.display = "none", 4000);
          }
        });
      }
    </script>

    <?php endif; ?>
  <footer class="page-footer">
    <span>&copy; 2025 Freddy Carias &mdash; All Rights Reserved</span>
  </footer>
  </body>
</html>
<?php $conn->close(); ?>
