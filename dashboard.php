<?php
require_once __DIR__ . '/admin_session.php';
if (!isset($_SESSION['logged_in'])) {
    header('Location: /login');
    exit;
}
require 'csrf.php';
$csrfToken = csrf_token();
?>
<!DOCTYPE html>
<html lang="en" data-theme-scope="admin" data-theme="light">
<head>
  <script src="/theme.js?v=20261027-4"></script>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">
  <title>Think Room | Dashboard</title>
  <meta name="robots" content="noindex, nofollow">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html, body { height: 100%; }
    body {
      font-family: "Inter", system-ui, sans-serif;
      background: var(--bg);
      color: var(--text-dim);
      display: flex;
      overflow: hidden;
      -webkit-font-smoothing: antialiased;
    }

    /* ── Sidebar ── */
    .sidebar {
      width: 236px; background: var(--panel);
      border-right: 1px solid var(--border);
      display: flex; flex-direction: column; height: 100vh;
      flex-shrink: 0; position: relative; z-index: 10;
    }
    .sidebar-top { padding: 1.6rem 1.4rem 1.3rem; border-bottom: 1px solid var(--border); display:flex; align-items:center; gap:0.6rem; }
    .sidebar-mark {
      width: 32px; height: 32px; border-radius: 8px;
      background: linear-gradient(145deg, var(--accent), var(--accent-d));
      display:flex; align-items:center; justify-content:center;
      font-family: "Cormorant Garamond", serif; font-style: italic; font-weight: 600;
      font-size: 1.15rem; color: #1a1408; flex-shrink:0;
    }
    .sidebar-brand { font-family: "Cormorant Garamond", serif; font-size: 1.15rem; font-style: italic; font-weight: 600; color: var(--text); text-decoration:none; display:block; line-height:1.1; }
    .sidebar-label { font-size: 0.56rem; letter-spacing: 0.16em; text-transform: uppercase; color: var(--text-mute); }
    .sidebar-nav { flex: 1; padding: 1.2rem 0.7rem; overflow-y: auto; }
    .nav-item {
      display: flex; align-items: center; gap: 0.7rem;
      padding: 0.68rem 0.85rem; border-radius: 8px;
      color: var(--text-dim); font-size: 0.78rem; font-weight: 500;
      letter-spacing: 0.02em; cursor: pointer; transition: all 0.18s;
      border: none; background: none; width: 100%; text-align: left;
      font-family: "Inter", sans-serif; margin-bottom: 0.15rem;
    }
    .nav-item i { font-size: 1rem; flex-shrink: 0; }
    .nav-item:hover { color: var(--text); background: var(--surface); }
    .nav-item.active { color: var(--accent); background: rgba(200,169,110,0.1); }
    .nav-section-label { font-size: 0.56rem; letter-spacing: 0.18em; text-transform: uppercase; color: var(--text-mute); padding: 1rem 0.85rem 0.5rem; }
    .sidebar-footer { padding: 0.9rem 0.7rem; border-top: 1px solid var(--border); display: flex; flex-direction: column; gap: 0.15rem; }
    .sidebar-footer a {
      display: flex; align-items: center; gap: 0.7rem;
      padding: 0.6rem 0.85rem; border-radius: 8px;
      color: var(--text-dim); font-size: 0.74rem; font-weight: 500;
      text-decoration: none; transition: all 0.18s;
    }
    .sidebar-footer a:hover { color: var(--text); background: var(--surface); }
    .sidebar-footer a i { font-size: 1rem; }

    /* ── Main ── */
    .main { flex: 1; height: 100vh; overflow-y: auto; display: flex; flex-direction: column; }
    .topbar {
      padding: 1.1rem 2rem; border-bottom: 1px solid var(--border);
      display: flex; align-items: center; justify-content: space-between;
      flex-shrink: 0; background: rgba(22,22,24,0.92); backdrop-filter: blur(10px);
      position: sticky; top: 0; z-index: 5;
    }
    .topbar-title { font-family: "Cormorant Garamond", serif; font-size: 1.5rem; font-style: italic; font-weight: 500; color: var(--text); }
    .topbar-actions { display: flex; align-items: center; gap: 0.6rem; }

    .btn-primary {
      background: linear-gradient(145deg, var(--accent), var(--accent-d)); border: none;
      color: #1a1408; padding: 0.55rem 1.1rem; font-family: "Inter", sans-serif;
      font-size: 0.76rem; font-weight: 600; cursor: pointer; border-radius: 8px;
      transition: filter 0.18s; display: flex; align-items: center; gap: 0.4rem;
    }
    .btn-primary:hover { filter: brightness(1.08); }
    .btn-ghost {
      background: var(--surface); border: 1px solid var(--border); color: var(--text-dim);
      padding: 0.55rem 1rem; font-family: "Inter", sans-serif; font-size: 0.76rem; font-weight: 500;
      cursor: pointer; border-radius: 8px; transition: all 0.18s;
    }
    .btn-ghost:hover { color: var(--text); border-color: var(--border-2); background: var(--surface-2); }

    .panel { display: none; padding: 2rem; flex: 1; }
    .panel.active { display: block; }

    /* ── Stat cards ── */
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 1rem; margin-bottom: 2.2rem; }
    .stat-card { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 1.3rem 1.3rem; position: relative; overflow:hidden; }
    .stat-card .ic { position:absolute; top:1.1rem; right:1.1rem; font-size:1.1rem; color: var(--accent); opacity:0.5; }
    .stat-label { font-size: 0.64rem; letter-spacing: 0.12em; text-transform: uppercase; color: var(--text-mute); margin-bottom: 0.7rem; font-weight: 500; }
    .stat-value { font-family: "Cormorant Garamond", serif; font-size: 2.3rem; font-weight: 500; color: var(--text); line-height: 1; }
    .stat-value.sm { font-size: 1.5rem; }

    .section-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.2rem; padding-bottom: 0.8rem; border-bottom: 1px solid var(--border); }
    .section-head h3 { font-family: "Cormorant Garamond", serif; font-size: 1.3rem; font-style: italic; font-weight: 500; color: var(--text); }

    /* ── Poem list ── */
    .poem-list { display: flex; flex-direction: column; gap: 0.5rem; }
    .poem-row { display: flex; align-items: center; background: var(--surface); border: 1px solid var(--border); border-radius: 10px; padding: 0.9rem 1.2rem; transition: border-color 0.18s; gap: 1rem; }
    .poem-row:hover { border-color: var(--border-2); }
    .poem-row-title { font-family: "Cormorant Garamond", serif; font-size: 1.1rem; font-style: italic; color: var(--text); flex: 1; }
    .poem-row-cat { font-size: 0.6rem; font-weight:500; letter-spacing: 0.1em; text-transform: uppercase; color: var(--accent); white-space: nowrap; }
    .poem-row-actions { display: flex; gap: 0.3rem; flex-shrink: 0; }
    .icon-btn { background: none; border: none; color: var(--text-mute); cursor: pointer; font-size: 1rem; padding: 0.4rem; border-radius: 6px; transition: color 0.18s, background 0.18s; }
    .icon-btn:hover { color: var(--accent); background: rgba(200,169,110,0.1); }
    .icon-btn.danger:hover { color: var(--danger); background: rgba(229,128,122,0.12); }

    /* ── Compose ── */
    .compose-form { max-width: 680px; }
    .form-field { margin-bottom: 1.4rem; }
    .form-field label { font-size: 0.72rem; font-weight: 500; color: #c7c3bb; display: block; margin-bottom: 0.45rem; }
    .form-field input, .form-field textarea, .form-field select {
      width: 100%; background: var(--surface); border: 1px solid var(--border); border-radius: 9px;
      padding: 0.72rem 0.9rem; color: var(--text); font-size: 0.9rem; font-family: "Inter", sans-serif;
      transition: border-color 0.18s, box-shadow 0.18s; resize: vertical;
    }
    .form-field textarea { min-height: 300px; line-height: 1.8; font-family: "Cormorant Garamond", serif; font-size: 1.05rem; }
    .form-field input::placeholder, .form-field textarea::placeholder { color: var(--text-mute); }
    .form-field input:focus, .form-field textarea:focus, .form-field select:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(200,169,110,0.14); }

    /* ── SEO section ── */
    .seo-section { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 1.3rem 1.4rem; margin-bottom: 1.5rem; }
    .seo-head { font-size: 0.8rem; font-weight: 600; color: var(--text); margin-bottom: 1.1rem; display: flex; align-items: center; gap: 0.5rem; }
    .seo-head i { color: var(--accent); }
    .seo-optional { font-size: 0.6rem; font-weight: 500; letter-spacing: 0.08em; text-transform: uppercase; color: var(--text-mute); background: var(--bg); border: 1px solid var(--border); border-radius: 20px; padding: 0.1rem 0.6rem; }
    .char-count { float: right; font-size: 0.66rem; font-weight: 400; color: var(--text-mute); letter-spacing: 0; text-transform: none; }
    .char-count.over { color: var(--danger); }
    .char-count.good { color: var(--success); }
    .serp-preview { background: var(--bg); border: 1px solid var(--border); border-radius: 9px; padding: 1rem 1.1rem; margin-top: 0.3rem; }
    .serp-label { font-size: 0.6rem; font-weight: 600; letter-spacing: 0.1em; text-transform: uppercase; color: var(--text-mute); margin-bottom: 0.7rem; }
    .serp-url { font-size: 0.78rem; color: var(--text-dim); margin-bottom: 0.2rem; }
    .serp-title { font-family: "Inter", sans-serif; font-size: 1.05rem; color: #8ab4f8; line-height: 1.3; margin-bottom: 0.25rem; }
    .serp-desc { font-size: 0.82rem; color: var(--text-dim); line-height: 1.5; }

    /* ── Archive badge on poem rows ── */
    .poem-row-archived { font-size: 0.56rem; font-weight: 600; letter-spacing: 0.1em; text-transform: uppercase; color: var(--text-mute); background: var(--bg); border: 1px solid var(--border); border-radius: 20px; padding: 0.1rem 0.5rem; }

    /* ── Toast ── */
    .toast { position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 999; background: var(--surface-2); border: 1px solid var(--accent); color: var(--accent); padding: 0.8rem 1.4rem; border-radius: 10px; font-size: 0.78rem; font-weight:500; display: none; animation: fadeUp 0.3s ease; }
    @keyframes fadeUp { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:translateY(0); } }

    /* ── Analytics ── */
    .analytics-grid { display: grid; grid-template-columns: 1.4fr 1fr; gap: 1.2rem; margin-top: 0.5rem; }
    @media (max-width: 1000px) { .analytics-grid { grid-template-columns: 1fr; } }
    .card-box { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 1.4rem 1.5rem; margin-bottom: 1.2rem; }
    .card-box h4 { font-family: "Inter", sans-serif; font-size: 0.72rem; font-weight: 600; letter-spacing: 0.1em; text-transform: uppercase; color: var(--text-mute); margin-bottom: 1.1rem; }

    .lead-row { display: flex; align-items: center; gap: 0.8rem; padding: 0.55rem 0; border-bottom: 1px solid var(--border); }
    .lead-row:last-child { border-bottom: none; }
    .lead-rank { font-size: 0.8rem; color: var(--text-mute); width: 18px; flex-shrink:0; font-variant-numeric: tabular-nums; }
    .lead-title { flex: 1; font-family: "Cormorant Garamond", serif; font-style: italic; font-size: 1rem; color: var(--text); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .lead-val { font-weight: 600; color: var(--accent); font-size: 0.85rem; font-variant-numeric: tabular-nums; flex-shrink:0; }

    .bar-row { margin-bottom: 0.9rem; }
    .bar-top { display:flex; justify-content:space-between; font-size:0.78rem; margin-bottom:0.35rem; }
    .bar-top .cat { color: var(--text); font-weight:500; }
    .bar-top .num { color: var(--text-mute); font-variant-numeric: tabular-nums; }
    .bar-track { height: 7px; background: var(--bg); border-radius: 4px; overflow: hidden; }
    .bar-fill { height: 100%; background: linear-gradient(90deg, var(--accent-d), var(--accent)); border-radius: 4px; transition: width 0.6s ease; }

    /* Trend chart (CSS bars) */
    .trend { display: flex; align-items: flex-end; gap: 3px; height: 120px; padding-top: 0.5rem; }
    .trend-bar { flex: 1; background: linear-gradient(180deg, var(--accent), var(--accent-d)); border-radius: 3px 3px 0 0; min-height: 2px; opacity: 0.85; transition: height 0.5s ease; position: relative; }
    .trend-bar:hover { opacity: 1; }
    .trend-bar:hover::after { content: attr(data-label); position:absolute; bottom:100%; left:50%; transform:translateX(-50%); background: var(--surface-2); border:1px solid var(--border); color: var(--text); font-size:0.62rem; padding:0.2rem 0.4rem; border-radius:4px; white-space:nowrap; margin-bottom:4px; z-index:5; }
    .trend-axis { display:flex; justify-content:space-between; font-size:0.6rem; color: var(--text-mute); margin-top:0.5rem; }

    .mini-comment { padding: 0.7rem 0; border-bottom: 1px solid var(--border); font-size: 0.82rem; color: var(--text-dim); line-height: 1.6; }
    .mini-comment:last-child { border-bottom: none; }
    .mini-comment strong { color: var(--text); }
    .mini-comment .meta { font-size: 0.64rem; color: var(--text-mute); margin-top: 0.25rem; }

    /* ── Metric table ── */
    .metric-table { width: 100%; border-collapse: collapse; font-size: 0.82rem; }
    .metric-table th { text-align: left; padding: 0.6rem 0.8rem; font-size: 0.64rem; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase; color: var(--text-mute); border-bottom: 1px solid var(--border); white-space: nowrap; }
    .metric-table th.num, .metric-table td.num { text-align: right; font-variant-numeric: tabular-nums; }
    .metric-table th.sortable { cursor: pointer; user-select: none; }
    .metric-table th.sortable:hover { color: var(--accent); }
    .metric-table td { padding: 0.65rem 0.8rem; border-bottom: 1px solid var(--border); color: var(--text-dim); }
    .metric-table tbody tr:hover { background: var(--surface-2); }
    .metric-table .t-title { font-family: "Cormorant Garamond", serif; font-style: italic; font-size: 0.98rem; color: var(--text); }
    .metric-table .t-score { color: var(--accent); font-weight: 600; }

    /* ── Settings ── */
    .setting-card { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 1.6rem 1.8rem; max-width: 520px; }

    /* ── Comment moderation ── */
    .nav-badge { background: var(--accent); color: #1a1408; font-size: 0.58rem; font-weight: 700; border-radius: 10px; padding: 0.05rem 0.4rem; margin-left: auto; }
    .mod-card { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 1.3rem 1.5rem; margin-bottom: 0.9rem; }
    .mod-head { display: flex; justify-content: space-between; align-items: baseline; gap: 1rem; margin-bottom: 0.7rem; flex-wrap: wrap; }
    .mod-author { font-weight: 600; color: var(--text); font-size: 0.9rem; }
    .mod-meta { font-size: 0.68rem; color: var(--text-mute); }
    .mod-body { font-family: "Cormorant Garamond", serif; font-style: italic; font-size: 1.1rem; color: var(--text-dim); line-height: 1.6; margin-bottom: 1.1rem; white-space: pre-wrap; word-wrap: break-word; }
    .mod-actions { display: flex; gap: 0.6rem; }
    .btn-approve { background: rgba(124,201,140,0.12); border: 1px solid rgba(124,201,140,0.35); color: var(--success); padding: 0.5rem 1.1rem; border-radius: 8px; font-family: "Inter", sans-serif; font-size: 0.74rem; font-weight: 600; cursor: pointer; transition: all 0.18s; display:flex; align-items:center; gap:0.4rem; }
    .btn-approve:hover { background: rgba(124,201,140,0.2); }
    .btn-reject { background: rgba(229,128,122,0.1); border: 1px solid rgba(229,128,122,0.32); color: var(--danger); padding: 0.5rem 1.1rem; border-radius: 8px; font-family: "Inter", sans-serif; font-size: 0.74rem; font-weight: 600; cursor: pointer; transition: all 0.18s; display:flex; align-items:center; gap:0.4rem; }
    .btn-reject:hover { background: rgba(229,128,122,0.2); }

    /* ── Mobile ── */
    .mobile-topbar { display: none; background: var(--panel); border-bottom: 1px solid var(--border); padding: 0.9rem 1.2rem; align-items: center; justify-content: space-between; }
    .mobile-topbar .brand { font-family: "Cormorant Garamond", serif; font-size: 1.1rem; font-style: italic; color: var(--text); text-decoration: none; display:flex; align-items:center; gap:0.5rem; }
    .mobile-topbar .brand .brand-mark { width:26px; height:26px; border-radius:6px; background:linear-gradient(145deg,var(--accent),var(--accent-d)); display:inline-flex; align-items:center; justify-content:center; font-weight:600; font-size:0.95rem; color:#1a1408; flex-shrink:0; }
    .mobile-topbar button { background: none; border: none; color: var(--text-dim); font-size: 1.3rem; cursor: pointer; }
    .mobile-sidebar-overlay { display: none; position: fixed; inset: 0; z-index: 50; background: rgba(0,0,0,0.6); }
    .mobile-sidebar-overlay.open { display: block; }
    .mobile-sidebar { position: fixed; left: 0; top: 0; bottom: 0; width: 250px; background: var(--panel); z-index: 51; transform: translateX(-100%); transition: transform 0.3s ease; display: flex; flex-direction: column; border-right: 1px solid var(--border); }
    .mobile-sidebar.open { transform: translateX(0); }

    @media (max-width: 768px) {
      .sidebar { display: none; }
      .mobile-topbar { display: flex; }
      body { flex-direction: column; }
      .main { height: calc(100vh - 54px); }
      .panel { padding: 1.2rem; }
      .topbar { padding: 1rem 1.2rem; }
      .analytics-grid { grid-template-columns: 1fr; }
    }
  </style>
  <link rel="stylesheet" href="admin.css?v=20261027-3">
  <link rel="stylesheet" href="/theme.css?v=20261027-4">
</head>
<body>

  <!-- Mobile top bar -->
  <div class="mobile-topbar">
    <a href="/" class="brand"><span class="brand-mark">T</span>Think Room</a>
    <button id="mobileMenuButton" onclick="toggleMobileSidebar()" aria-label="Open navigation" aria-controls="mobileSidebar" aria-expanded="false"><i class="bi bi-list"></i></button>
  </div>

  <div class="mobile-sidebar-overlay" id="mobileSidebarOverlay" onclick="toggleMobileSidebar()"></div>
  <div class="mobile-sidebar" id="mobileSidebar">
    <div style="padding:1.4rem 1.2rem; border-bottom:1px solid var(--border); display:flex; align-items:center; gap:0.6rem;">
      <span class="sidebar-mark">T</span>
      <span style="font-family:'Cormorant Garamond',serif; font-size:1.1rem; font-style:italic; color:var(--text);">Think Room</span>
    </div>
    <div style="flex:1; padding:1rem 0.7rem; overflow-y:auto;">
      <button class="nav-item active" data-panel="home" onclick="showPanel('home'); toggleMobileSidebar()"><i class="bi bi-grid-1x2"></i> Overview</button>
      <button class="nav-item" data-panel="compose" onclick="newPoem(); toggleMobileSidebar()"><i class="bi bi-pen"></i> New Poem</button>
      <button class="nav-item" data-panel="allpoems" onclick="showPanel('allpoems'); toggleMobileSidebar()"><i class="bi bi-collection"></i> All Poems</button>
      <button class="nav-item" data-panel="archived" onclick="showPanel('archived'); toggleMobileSidebar()"><i class="bi bi-archive"></i> Archived</button>
      <button class="nav-item" data-panel="categories" onclick="showPanel('categories'); toggleMobileSidebar()"><i class="bi bi-tags"></i> Categories</button>
      <button class="nav-item" data-panel="stats" onclick="showPanel('stats'); toggleMobileSidebar()"><i class="bi bi-bar-chart"></i> Analytics</button>
      <button class="nav-item" data-panel="comments" onclick="showPanel('comments'); toggleMobileSidebar()"><i class="bi bi-chat-square-text"></i> Comments</button>
      <button class="nav-item" data-panel="notifications" onclick="showPanel('notifications'); toggleMobileSidebar()"><i class="bi bi-bell"></i> Notifications <span class="nav-badge" data-notification-count hidden></span></button>
      <button class="nav-item" data-panel="settings" onclick="showPanel('settings'); toggleMobileSidebar()"><i class="bi bi-sliders"></i> Settings</button>
    </div>
    <div style="padding:1rem 0.7rem; border-top:1px solid var(--border);">
      <a href="/" style="display:flex;align-items:center;gap:.6rem;color:var(--text-dim);text-decoration:none;font-size:.74rem;padding:.6rem .85rem;"><i class="bi bi-arrow-left"></i> Back to site</a>
      <a href="logout.php" style="display:flex;align-items:center;gap:.6rem;color:var(--text-dim);text-decoration:none;font-size:.74rem;padding:.6rem .85rem;"><i class="bi bi-box-arrow-left"></i> Log out</a>
    </div>
  </div>

  <!-- Desktop sidebar -->
  <aside class="sidebar">
    <div class="sidebar-top">
      <span class="sidebar-mark">T</span>
      <div>
        <a href="/" class="sidebar-brand">Think Room</a>
        <span class="sidebar-label">Dashboard</span>
      </div>
    </div>
    <div class="sidebar-nav">
      <p class="nav-section-label">Workspace</p>
      <button class="nav-item active" id="nav-home"     onclick="showPanel('home')"><i class="bi bi-grid-1x2"></i> Overview</button>
      <button class="nav-item"        id="nav-compose"  onclick="newPoem()"><i class="bi bi-pen"></i> New Poem</button>
      <button class="nav-item"        id="nav-allpoems" onclick="showPanel('allpoems')"><i class="bi bi-collection"></i> All Poems</button>
      <button class="nav-item"        id="nav-archived" onclick="showPanel('archived')"><i class="bi bi-archive"></i> Archived</button>
      <button class="nav-item"        id="nav-categories" onclick="showPanel('categories')"><i class="bi bi-tags"></i> Categories</button>
      <p class="nav-section-label">Insights</p>
      <button class="nav-item"        id="nav-stats"   onclick="showPanel('stats')"><i class="bi bi-bar-chart"></i> Analytics</button>
      <button class="nav-item"        id="nav-comments" onclick="showPanel('comments')"><i class="bi bi-chat-square-text"></i> Comments <span class="nav-badge" id="pendingBadge" style="display:none;"></span></button>
      <button class="nav-item" id="nav-notifications" onclick="showPanel('notifications')"><i class="bi bi-bell"></i> Notifications <span class="nav-badge" data-notification-count hidden></span></button>
      <p class="nav-section-label">Site</p>
      <button class="nav-item"        id="nav-settings" onclick="showPanel('settings')"><i class="bi bi-sliders"></i> Settings</button>
    </div>
    <div class="sidebar-footer">
      <a href="/"><i class="bi bi-arrow-left"></i> Back to site</a>
      <a href="logout.php"><i class="bi bi-box-arrow-left"></i> Log out</a>
    </div>
  </aside>

  <!-- Main -->
  <div class="main">
    <div class="topbar">
      <span class="topbar-title" id="topbarTitle">Overview</span>
      <div class="topbar-actions">
        <div class="notification-center" id="notificationCenter">
          <button class="btn-ghost notification-toggle" id="notificationToggle" type="button" aria-label="Notifications" aria-expanded="false" aria-controls="notificationPopover">
            <i class="bi bi-bell" aria-hidden="true"></i><span id="notificationCount" hidden></span>
          </button>
          <section class="notification-popover" id="notificationPopover" aria-label="Notifications" hidden>
            <div class="notification-heading"><h2>Notifications</h2><button class="icon-btn" id="notificationClose" type="button" aria-label="Close notifications"><i class="bi bi-x-lg" aria-hidden="true"></i></button></div>
            <div class="notification-toolbar"><button class="btn-ghost" id="notificationRead" type="button" disabled>Mark all as read</button><button class="btn-ghost" id="notificationRefresh" type="button">Refresh</button></div>
            <p id="notificationStatus" role="status">Loading notifications...</p>
            <ul id="notificationList"></ul>
            <button class="category-link" type="button" onclick="showPanel('notifications')">View notifications page</button>
          </section>
          <span class="notification-sr" id="notificationAnnouncement" role="status" aria-live="polite"></span>
        </div>
        <button class="btn-primary" onclick="newPoem()"><i class="bi bi-pen"></i> New poem</button>
      </div>
    </div>

    <!-- Overview -->
    <div class="panel active" id="panel-home">
      <?php if (isset($_GET['success'])): ?>
      <div style="background:rgba(124,201,140,0.1);border:1px solid rgba(124,201,140,0.28);color:var(--success);font-size:.82rem;padding:.75rem 1rem;border-radius:9px;margin-bottom:1.5rem;">
        Poem saved successfully!
      </div>
      <?php endif; ?>

      <div class="workspace-intro">
        <p class="eyebrow">Your writing space</p>
        <h1>A little room for your words.</h1>
        <p>Create something new, revisit your poems, and see how readers connect with your work.</p>
      </div>

      <div class="stats-grid">
        <div class="stat-card"><i class="bi bi-journal-text ic"></i><p class="stat-label">Total Poems</p><p class="stat-value" id="statTotal">—</p></div>
        <div class="stat-card"><i class="bi bi-eye ic"></i><p class="stat-label">Total Views</p><p class="stat-value" id="statViews">—</p></div>
        <div class="stat-card"><i class="bi bi-heart ic"></i><p class="stat-label">Total Likes</p><p class="stat-value" id="statLikes">—</p></div>
        <div class="stat-card"><i class="bi bi-chat-dots ic"></i><p class="stat-label">Comments</p><p class="stat-value" id="statComments">—</p></div>
        <div class="stat-card"><i class="bi bi-share ic"></i><p class="stat-label">Shares</p><p class="stat-value" id="statShares">—</p></div>
      </div>

      <div class="section-head">
        <h3>Recent poems</h3>
        <button class="btn-ghost" onclick="showPanel('allpoems')">View all</button>
      </div>
      <div class="poem-list" id="recentPoemsList"></div>
    </div>

    <!-- Compose -->
    <div class="panel" id="panel-compose">
      <div class="compose-form">
        <div class="section-head"><h3 id="composeHeading">New poem</h3></div>
        <form id="composeForm">
          <input type="hidden" id="editPoemId" name="id" value="">
          <div class="form-field"><label for="poemTitle">Title</label><input type="text" id="poemTitle" name="title" placeholder="Give it a name" spellcheck="true" lang="en-US" required /></div>
          <div class="form-field">
            <label for="poemCategory">Category</label>
            <input type="text" id="poemCategory" name="category" list="categoryOptions" maxlength="100" placeholder="Choose a category or type a new one" required />
            <datalist id="categoryOptions"></datalist>
            <button type="button" class="category-link" onclick="showPanel('categories')">Manage categories</button>
          </div>
          <div class="form-field"><label for="poemBody">Poem</label><textarea id="poemBody" name="body" placeholder="Write here…" spellcheck="true" writingsuggestions="true" lang="en-US" aria-describedby="writingHelp" required></textarea></div>
          <div class="writing-tools">
            <div class="writing-toolbar">
              <label class="spellcheck-toggle"><input type="checkbox" id="spellcheckToggle" checked> Browser spellcheck</label>
              <label for="grammarLanguage">Language <select id="grammarLanguage"><option value="en-US">English (US)</option><option value="en-GB">English (UK)</option></select></label>
              <button type="button" class="btn-ghost" id="checkGrammarButton">Check with LanguageTool</button>
            </div>
            <p id="writingHelp" class="category-help">Browser spelling and grammar suggestions depend on your browser settings. Clicking “Check with LanguageTool” sends the poem text to <a href="https://languagetool.org" target="_blank" rel="noopener noreferrer">LanguageTool</a> for review (<a href="https://languagetool.org/legal/privacy" target="_blank" rel="noopener noreferrer">privacy policy</a>). Suggestions are optional; your words and line breaks stay yours.</p>
            <p id="grammarStatus" role="status" aria-live="polite"></p>
            <div id="grammarResults"></div>
          </div>

          <!-- SEO section -->
          <div class="seo-section">
            <div class="seo-head"><i class="bi bi-search"></i> Search engine optimization <span class="seo-optional">optional</span></div>
            <div class="form-field">
              <label for="metaDescription">Meta description
                <span class="char-count" id="metaCount">0 / 160</span>
              </label>
              <textarea id="metaDescription" name="meta_description" maxlength="300" placeholder="A short, compelling summary shown in Google results (aim for ~150–160 characters). Leave blank to auto-generate from the poem." style="min-height:72px;font-family:'Inter',sans-serif;font-size:0.88rem;line-height:1.5;"></textarea>
            </div>
            <div class="form-field">
              <label for="metaKeywords">Keywords <span style="color:var(--text-mute);font-weight:400;text-transform:none;letter-spacing:0;">comma-separated</span></label>
              <input type="text" id="metaKeywords" name="meta_keywords" placeholder="poetry, faith, love, reflection" />
            </div>
            <!-- Google-style preview -->
            <div class="serp-preview">
              <div class="serp-label">Search preview</div>
              <div class="serp-url">freddycarias.com › poems › <span id="serpSlug">your-poem</span></div>
              <div class="serp-title" id="serpTitle">Poem title — Think Room by Freddy Carias</div>
              <div class="serp-desc" id="serpDesc">Your meta description will appear here. If left blank, the first ~160 characters of the poem are used.</div>
            </div>
          </div>

          <div style="display:flex;gap:.8rem;flex-wrap:wrap;">
            <button type="submit" class="btn-primary"><i class="bi bi-check2"></i> Save poem</button>
            <button type="button" class="btn-ghost" id="previewPoemButton"><i class="bi bi-eye"></i> Preview on site</button>
            <button type="button" class="btn-ghost" onclick="clearCompose()">Clear</button>
            <button type="button" class="btn-ghost" onclick="cancelCompose()"><i class="bi bi-x-lg"></i> Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <!-- All Poems (active) -->
    <div class="panel" id="panel-allpoems">
      <div class="section-head">
        <h3>All poems</h3>
        <input type="text" id="allPoemsSearch" placeholder="Search…" aria-label="Search all poems"
          style="background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:.5rem .8rem;color:var(--text);font-size:.82rem;font-family:'Inter',sans-serif;width:200px;"
          oninput="renderAllPoems(this.value)" />
      </div>
      <div class="poem-list" id="allPoemsList"></div>
    </div>

    <!-- Archived (retired) -->
    <div class="panel" id="panel-archived">
      <div class="section-head">
        <h3>Archived poems</h3>
        <input type="text" id="archivedSearch" placeholder="Search…" aria-label="Search archived poems"
          style="background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:.5rem .8rem;color:var(--text);font-size:.82rem;font-family:'Inter',sans-serif;width:200px;"
          oninput="renderArchived(this.value)" />
      </div>
      <p style="font-size:0.82rem;color:var(--text-dim);margin-bottom:1.2rem;">
        Retired poems are hidden from the public gallery and marked no-index for search engines, but still reachable by direct link.
      </p>
      <div class="poem-list" id="archivedList"></div>
    </div>

    <div class="panel" id="panel-categories">
      <div class="section-head"><h3>Poem categories</h3><button class="btn-ghost" onclick="loadCategories()">Refresh</button></div>
      <p class="category-help">Organize your writing into topics like God, Love, Loss, and Heartbreak. Renaming a category updates every poem in it, including archived poems.</p>
      <form id="categoryCreateForm" class="category-create">
        <div class="form-field"><label for="categoryName">New category</label><input id="categoryName" name="name" maxlength="100" placeholder="e.g. Heartbreak" required /></div>
        <button class="btn-primary" type="submit">Add category</button>
      </form>
      <p id="categoryStatus" role="status" aria-live="polite"></p>
      <div class="category-table-wrap">
        <table class="category-table">
          <caption>Manage poem categories</caption>
          <thead><tr><th scope="col">Category name</th><th scope="col" class="category-count">Poems</th><th scope="col">Actions</th></tr></thead>
          <tbody id="categoryList"></tbody>
        </table>
      </div>
    </div>

    <dialog id="categoryDeleteDialog" class="category-dialog" aria-labelledby="categoryDeleteTitle">
      <form id="categoryDeleteForm">
        <h2 id="categoryDeleteTitle">Remove category</h2>
        <p id="categoryDeleteDescription" class="category-help"></p>
        <div class="form-field" id="categoryReplacementField"><label for="categoryReplacement">Move poems to</label><select id="categoryReplacement"></select></div>
        <p id="categoryDeleteError" role="alert"></p>
        <div class="mod-actions"><button type="submit" class="btn-reject">Remove category</button><button type="button" class="btn-ghost" onclick="document.getElementById('categoryDeleteDialog').close()">Cancel</button></div>
      </form>
    </dialog>

    <!-- Analytics -->
    <div class="panel" id="panel-stats">
      <div class="section-head">
        <h3>Analytics</h3>
        <button class="btn-ghost" onclick="loadAnalytics()"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
      </div>

      <div class="stats-grid" id="analyticsSummary">
        <div class="stat-card"><i class="bi bi-eye ic"></i><p class="stat-label">Views</p><p class="stat-value" id="aViews">—</p></div>
        <div class="stat-card"><i class="bi bi-heart ic"></i><p class="stat-label">Likes</p><p class="stat-value" id="aLikes">—</p></div>
        <div class="stat-card"><i class="bi bi-chat-dots ic"></i><p class="stat-label">Comments</p><p class="stat-value" id="aComments">—</p></div>
        <div class="stat-card"><i class="bi bi-share ic"></i><p class="stat-label">Shares</p><p class="stat-value" id="aShares">—</p></div>
      </div>

      <div class="card-box">
        <h4>Comments — last 30 days</h4>
        <div class="trend" id="trendChart"></div>
        <div class="trend-axis"><span id="trendStart">—</span><span id="trendEnd">—</span></div>
      </div>

      <div class="analytics-grid">
        <div>
          <div class="card-box">
            <h4>Top poems by engagement</h4>
            <div id="topEngagement"></div>
          </div>
          <div class="card-box">
            <h4>Most viewed</h4>
            <div id="topViews"></div>
          </div>
        </div>
        <div>
          <div class="card-box">
            <h4>Engagement by category</h4>
            <div id="catBreakdown"></div>
          </div>
          <div class="card-box">
            <h4>Recent comments</h4>
            <div id="recentComments"></div>
          </div>
        </div>
      </div>

      <!-- Full per-poem breakdown -->
      <div class="card-box" style="margin-top:1.2rem;">
        <h4>Every poem — full breakdown</h4>
        <div style="overflow-x:auto;">
          <table class="metric-table" id="poemMetricTable">
            <thead>
              <tr>
                <th class="sortable" onclick="sortMetricTable('title')">Poem</th>
                <th class="sortable num" onclick="sortMetricTable('views')">Views</th>
                <th class="sortable num" onclick="sortMetricTable('likes')">Likes</th>
                <th class="sortable num" onclick="sortMetricTable('comments')">Comments</th>
                <th class="sortable num" onclick="sortMetricTable('shares')">Shares</th>
                <th class="sortable num" onclick="sortMetricTable('engagement')">Score</th>
              </tr>
            </thead>
            <tbody id="poemMetricBody"></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Comments moderation -->
    <div class="panel" id="panel-comments">
      <div class="section-head">
        <h3>Pending comments</h3>
        <button class="btn-ghost" onclick="loadPendingComments()"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
      </div>
      <p style="font-size:0.82rem;color:var(--text-dim);margin-bottom:1.3rem;">
        New comments are held here until you approve them. Approve to publish, or delete to discard spam or hate mail.
      </p>
      <div id="pendingList"></div>
    </div>

    <!-- Notifications -->
    <div class="panel" id="panel-notifications">
      <div class="section-head"><h3>Notifications</h3></div>
      <p class="category-help">Likes, comments, and shares from your readers. New activity updates automatically. Reading notifications does not approve comments.</p>
      <div class="notification-page">
        <div class="notification-toolbar"><button class="btn-ghost" id="notificationPageRead" type="button" disabled>Mark all as read</button><button class="btn-ghost" id="notificationPageRefresh" type="button">Refresh</button></div>
        <p id="notificationPageStatus" role="status">Loading notifications...</p>
        <ul id="notificationPageList"></ul>
      </div>
    </div>

    <!-- Settings -->
    <div class="panel" id="panel-settings">
      <div class="section-head"><h3>Settings</h3></div>
      <div class="setting-card">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;">
          <div>
            <p style="font-family:'Cormorant Garamond',serif;font-size:1.2rem;font-style:italic;color:var(--text);margin-bottom:0.3rem;">Maintenance Mode</p>
            <p style="font-size:0.78rem;color:var(--text-dim);line-height:1.5;">When on, visitors see a maintenance page instead of the site. You stay logged in and can still browse.</p>
          </div>
          <button id="maintenanceToggle" onclick="toggleMaintenance()"
            style="position:relative; display:inline-flex; align-items:center; width:50px; height:28px; border-radius:14px; border:none; background:var(--border); cursor:pointer; transition:background 0.25s; flex-shrink:0; padding:0;"
            aria-label="Toggle maintenance mode" aria-checked="false" role="switch">
            <span id="maintenanceKnob" style="position:absolute; left:3px; width:22px; height:22px; border-radius:50%; background:var(--text-mute); transition:left 0.25s, background 0.25s;"></span>
          </button>
        </div>
        <p id="maintenanceStatus" style="font-size:0.72rem;color:var(--text-mute);margin-top:1rem;">Checking status…</p>
      </div>
    </div>
  </div>

  <div class="toast" id="toast" role="status" aria-live="polite"></div>

  <script>
    let allPoems = [];

    // CSRF token sent with every state-changing request to admin endpoints.
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute("content");

    function showPanel(id) {
      document.querySelectorAll(".panel").forEach(p => p.classList.remove("active"));
      document.querySelectorAll(".nav-item").forEach(n => n.classList.remove("active"));
      document.getElementById("panel-" + id).classList.add("active");
      const navEl = document.getElementById("nav-" + id);
      if (navEl) navEl.classList.add("active");
      document.querySelectorAll('.mobile-sidebar [data-panel]').forEach(button => {
        button.classList.toggle('active', button.dataset.panel === id);
      });
      const titles = { home: "Overview", compose: document.getElementById("editPoemId").value ? "Edit Poem" : "New Poem", allpoems: "All Poems", archived: "Archived", stats: "Analytics", comments: "Comments", settings: "Settings" };
      document.getElementById("topbarTitle").textContent = titles[id] || id;
      if (id === 'categories') document.getElementById('topbarTitle').textContent = 'Categories';
      if (id === 'notifications') document.getElementById('topbarTitle').textContent = 'Notifications';
      localStorage.setItem('tr_dashboard_panel', id);
      document.dispatchEvent(new CustomEvent('dashboard:panel', { detail: { id } }));
      if (id === 'categories' || id === 'compose') loadCategories();
      if (id === "allpoems") renderAllPoems();
      if (id === "archived") renderArchived();
      if (id === "home") loadStats();
      if (id === "stats") loadAnalytics();
      if (id === "comments") loadPendingComments();
      if (id === "settings") loadMaintenanceStatus();
    }

    function toggleMobileSidebar() {
      const open = document.getElementById("mobileSidebar").classList.toggle("open");
      document.getElementById("mobileSidebarOverlay").classList.toggle("open", open);
      const menuButton = document.getElementById("mobileMenuButton");
      menuButton.setAttribute("aria-expanded", String(open));
      menuButton.setAttribute("aria-label", open ? "Close navigation" : "Open navigation");
      if (open) document.querySelector('.mobile-sidebar .nav-item.active')?.focus();
      else menuButton.focus();
    }
    document.addEventListener('keydown', event => {
      if (event.key === 'Escape' && document.getElementById('mobileSidebar').classList.contains('open')) toggleMobileSidebar();
    });

    const fmt = n => (n ?? 0).toLocaleString();

    // ── Overview stats (now from analytics for accuracy) ──
    function loadStats() {
      fetch("get_analytics.php").then(r => r.json()).then(data => {
        if (!data.success) { return; }
        const t = data.totals;
        document.getElementById("statTotal").textContent    = fmt(t.poems);
        document.getElementById("statViews").textContent    = fmt(t.views);
        document.getElementById("statLikes").textContent    = fmt(t.likes);
        document.getElementById("statComments").textContent = fmt(t.comments);
        document.getElementById("statShares").textContent   = fmt(t.shares);

        allPoems = data.poems;
        const list = document.getElementById("recentPoemsList");
        list.innerHTML = "";
        data.poems.slice(0, 5).forEach(p => list.appendChild(makePoemRow(p)));
      }).catch(() => {});
    }

    // ── All Poems (active) & Archived ──
    function renderAllPoems(filter = "") {
      fetch("get_poems.php").then(r => r.json()).then(poems => {
        allPoems = poems;
        const list = document.getElementById("allPoemsList");
        list.innerHTML = "";
        let items = poems.filter(p => p.archived != 1); // active only
        if (filter) items = items.filter(p => p.title.toLowerCase().includes(filter.toLowerCase()) || (p.category||"").toLowerCase().includes(filter.toLowerCase()));
        if (!items.length) { list.innerHTML = '<p style="color:var(--text-mute);font-size:.86rem;font-style:italic;padding:.5rem 0;">No poems found.</p>'; return; }
        items.forEach(p => list.appendChild(makePoemRow(p, false)));
      });
    }

    function renderArchived(filter = "") {
      fetch("get_poems.php").then(r => r.json()).then(poems => {
        allPoems = poems;
        const list = document.getElementById("archivedList");
        list.innerHTML = "";
        let items = poems.filter(p => p.archived == 1); // archived only
        if (filter) items = items.filter(p => p.title.toLowerCase().includes(filter.toLowerCase()) || (p.category||"").toLowerCase().includes(filter.toLowerCase()));
        if (!items.length) { list.innerHTML = '<p style="color:var(--text-mute);font-size:.86rem;font-style:italic;padding:.5rem 0;">No archived poems. Use the archive button on any poem to retire it here.</p>'; return; }
        items.forEach(p => list.appendChild(makePoemRow(p, true)));
      });
    }

    function makePoemRow(p, isArchived) {
      const row = document.createElement("div");
      row.className = "poem-row";
      // Archive vs Unarchive button depending on the poem's current state.
      const archiveBtn = isArchived
        ? `<button class="icon-btn" title="Unarchive (restore to gallery)" onclick="archivePoem(${p.id}, false, this)"><i class="bi bi-arrow-counterclockwise"></i></button>`
        : `<button class="icon-btn" title="Archive (retire this poem)" onclick="archivePoem(${p.id}, true, this)"><i class="bi bi-archive"></i></button>`;
      const badge = isArchived ? '<span class="poem-row-archived">Archived</span>' : '';
      row.innerHTML = `
        <span class="poem-row-title">${escapeHtml(p.title)}</span>
        ${badge}
        <span class="poem-row-cat">${escapeHtml(p.category || "")}</span>
        <div class="poem-row-actions">
          <button class="icon-btn" title="View live" onclick="window.open('${p.slug ? '/poems/'+p.slug : '/poem?id='+p.id}','_blank')"><i class="bi bi-box-arrow-up-right"></i></button>
          <button class="icon-btn" title="Edit" onclick="editPoem(${p.id})"><i class="bi bi-pencil"></i></button>
          ${archiveBtn}
          <button class="icon-btn danger" title="Delete" onclick="deletePoem(${p.id}, this)"><i class="bi bi-trash3"></i></button>
        </div>`;
      return row;
    }

    // Archive / unarchive a poem.
    function archivePoem(id, archived, btn) {
      const verb = archived ? "archive" : "restore";
      if (!confirm(`${archived ? "Archive" : "Restore"} this poem?`)) return;
      fetch("archive_poem.php", {
        method: "POST", headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ id, archived })
      }).then(r => r.json()).then(data => {
        if (data.success) {
          btn.closest(".poem-row").remove();
          showToast(archived ? "Poem archived." : "Poem restored.");
          loadStats();
        } else { showToast("Action failed."); }
      }).catch(() => showToast("Action failed."));
    }

    function editPoem(id) {
      fetch("get_poem_by_id.php?id=" + id).then(r => r.json()).then(p => {
        if (!p || !p.title) { showToast("Could not load poem."); return; }
        document.getElementById("editPoemId").value = id;
        document.getElementById("poemTitle").value = p.title;
        document.getElementById("poemCategory").value = p.category;
        document.getElementById("poemBody").value = p.body;
        document.getElementById("poemBody").dispatchEvent(new Event('input', { bubbles: true }));
        document.getElementById("metaDescription").value = p.meta_description || "";
        document.getElementById("metaKeywords").value = p.meta_keywords || "";
        document.getElementById("composeHeading").textContent = "Edit poem";
        updateSeoPreview();
        showPanel("compose");
      });
    }
    function newPoem() {
      clearCompose();
      showPanel("compose");
    }
    function clearCompose() {
      document.getElementById("editPoemId").value = "";
      document.getElementById("composeForm").reset();
      document.getElementById("composeHeading").textContent = "New poem";
      document.getElementById("topbarTitle").textContent = "New Poem";
      updateSeoPreview();
    }
    // Cancel editing/composing and return to the Overview without saving.
    function cancelCompose() {
      clearCompose();
      showPanel("home");
    }
    function deletePoem(id, btn) {
      if (!confirm("Delete this poem? This cannot be undone.")) return;
      fetch("delete_poem.php?id=" + id, {
        headers: { "X-CSRF-Token": CSRF_TOKEN }
      }).then(r => r.json()).then(data => {
        if (data.success) { btn.closest(".poem-row").remove(); showToast("Poem deleted."); loadStats(); }
        else { showToast("Delete failed."); }
      });
    }
    document.getElementById("composeForm").addEventListener("submit", function(e) {
      e.preventDefault();
      const formData = new FormData(this);
      const isEditing = formData.get("id") !== "";
      const saveButton = this.querySelector('button[type="submit"]');
      if (saveButton.disabled) return;
      saveButton.disabled = true;
      fetch("save_work.php", {
        method: "POST",
        headers: { "X-CSRF-Token": CSRF_TOKEN },
        body: formData
      }).then(r => r.json()).then(data => {
        if (data.success) {
          showToast(data.updated ? "Poem updated!" : "Poem saved!");
          if (!isEditing) clearCompose();
          loadStats();
        }
        else { showToast(data.error || "Save failed."); }
      }).catch(() => showToast("Could not confirm the save. Please check your connection and try again."))
        .finally(() => { saveButton.disabled = false; });
    });

    // ── SEO live preview + character counter ──
    function slugifyPreview(t) {
      return (t||"").toLowerCase().trim().replace(/[^a-z0-9]+/g,"-").replace(/^-+|-+$/g,"") || "your-poem";
    }
    function updateSeoPreview() {
      const title = document.getElementById("poemTitle")?.value || "";
      const body  = document.getElementById("poemBody")?.value || "";
      const desc  = document.getElementById("metaDescription")?.value || "";
      const count = document.getElementById("metaCount");
      if (count) {
        const n = desc.length;
        count.textContent = `${n} / 160`;
        count.className = "char-count" + (n > 160 ? " over" : (n >= 120 && n <= 160 ? " good" : ""));
      }
      const serpSlug  = document.getElementById("serpSlug");
      const serpTitle = document.getElementById("serpTitle");
      const serpDesc  = document.getElementById("serpDesc");
      if (serpSlug)  serpSlug.textContent = slugifyPreview(title);
      if (serpTitle) serpTitle.textContent = (title || "Poem title") + " — Think Room by Freddy Carias";
      if (serpDesc)  serpDesc.textContent = desc.trim() !== ""
        ? desc
        : (body.trim() !== "" ? body.replace(/\s+/g," ").slice(0,160) + "…" : "If left blank, the first ~160 characters of the poem are used.");
    }
    ["poemTitle","poemBody","metaDescription"].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.addEventListener("input", updateSeoPreview);
    });

    // ── Native analytics ──
    function loadAnalytics() {
      return fetch("get_analytics.php").then(r => r.json()).then(data => {
        if (!data.success) { showToast("Could not load analytics."); return; }
        const t = data.totals;
        document.getElementById("aViews").textContent    = fmt(t.views);
        document.getElementById("aLikes").textContent    = fmt(t.likes);
        document.getElementById("aComments").textContent = fmt(t.comments);
        document.getElementById("aShares").textContent   = fmt(t.shares);

        renderLeaderboard("topEngagement", data.top.engagement, "pts");
        renderLeaderboard("topViews", data.top.views, "views");
        renderCategoryBreakdown(data.categories);
        renderTrend(data.comment_trend);
        renderRecentComments(data.recent_comments);

        _metricPoems = data.poems || [];
        sortMetricTable(_metricSortKey, true);
      }).catch(() => showToast("Could not load analytics."));
    }

    // ── Full per-poem metric table (sortable) ──
    let _metricPoems = [];
    let _metricSortKey = "engagement";
    let _metricSortDesc = true;

    function sortMetricTable(key, keepDir = false) {
      if (!keepDir) {
        if (_metricSortKey === key) { _metricSortDesc = !_metricSortDesc; }
        else { _metricSortKey = key; _metricSortDesc = true; }
      }
      const rows = [..._metricPoems].sort((a, b) => {
        let va = a[key], vb = b[key];
        if (key === "title") { va = (va||"").toLowerCase(); vb = (vb||"").toLowerCase(); return _metricSortDesc ? vb.localeCompare(va) : va.localeCompare(vb); }
        return _metricSortDesc ? (vb - va) : (va - vb);
      });
      const body = document.getElementById("poemMetricBody");
      body.innerHTML = "";
      if (!rows.length) { body.innerHTML = '<tr><td colspan="6" style="color:var(--text-mute);font-style:italic;">No poems yet.</td></tr>'; return; }
      rows.forEach(p => {
        const tr = document.createElement("tr");
        tr.innerHTML = `
          <td class="t-title">${escapeHtml(p.title)}</td>
          <td class="num">${fmt(p.views)}</td>
          <td class="num">${fmt(p.likes)}</td>
          <td class="num">${fmt(p.comments)}</td>
          <td class="num">${fmt(p.shares)}</td>
          <td class="num t-score">${fmt(p.engagement)}</td>`;
        body.appendChild(tr);
      });
    }

    function renderLeaderboard(elId, items, unit) {
      const el = document.getElementById(elId);
      el.innerHTML = "";
      if (!items || !items.length || items.every(i => i.value === 0)) {
        el.innerHTML = '<p style="color:var(--text-mute);font-size:.82rem;font-style:italic;">No data yet.</p>';
        return;
      }
      items.forEach((it, i) => {
        const row = document.createElement("div");
        row.className = "lead-row";
        row.innerHTML = `<span class="lead-rank">${i+1}</span>
          <span class="lead-title">${escapeHtml(it.title)}</span>
          <span class="lead-val">${fmt(it.value)}</span>`;
        el.appendChild(row);
      });
    }

    function renderCategoryBreakdown(cats) {
      const el = document.getElementById("catBreakdown");
      el.innerHTML = "";
      if (!cats || !cats.length) { el.innerHTML = '<p style="color:var(--text-mute);font-size:.82rem;font-style:italic;">No data yet.</p>'; return; }
      const max = Math.max(...cats.map(c => c.views), 1);
      cats.forEach(c => {
        const pct = Math.round((c.views / max) * 100);
        const row = document.createElement("div");
        row.className = "bar-row";
        row.innerHTML = `
          <div class="bar-top"><span class="cat">${escapeHtml(c.category)}</span><span class="num">${fmt(c.views)} views · ${fmt(c.poems)} poems</span></div>
          <div class="bar-track"><div class="bar-fill" style="width:${pct}%"></div></div>`;
        el.appendChild(row);
      });
    }

    function renderTrend(trend) {
      const el = document.getElementById("trendChart");
      el.innerHTML = "";
      if (!trend || !trend.length) return;
      const max = Math.max(...trend.map(d => d.count), 1);
      trend.forEach(d => {
        const bar = document.createElement("div");
        bar.className = "trend-bar";
        bar.style.height = Math.round((d.count / max) * 100) + "%";
        bar.setAttribute("data-label", `${d.day}: ${d.count}`);
        el.appendChild(bar);
      });
      document.getElementById("trendStart").textContent = trend[0].day.slice(5);
      document.getElementById("trendEnd").textContent = trend[trend.length-1].day.slice(5);
    }

    function renderRecentComments(comments) {
      const el = document.getElementById("recentComments");
      el.innerHTML = "";
      if (!comments || !comments.length) { el.innerHTML = '<p style="color:var(--text-mute);font-size:.82rem;font-style:italic;">No comments yet.</p>'; return; }
      comments.forEach(c => {
        const el2 = document.createElement("div");
        el2.className = "mini-comment";
        const name = c.name ? `<strong>${escapeHtml(c.name)}</strong>` : "<strong>Anonymous</strong>";
        el2.innerHTML = `${name}: ${escapeHtml(c.comment)}<div class="meta">on “${escapeHtml(c.poem_title || "—")}” · ${escapeHtml(c.created_at)}</div>`;
        el.appendChild(el2);
      });
    }

    function showToast(msg) {
      const t = document.getElementById("toast");
      t.textContent = msg; t.style.display = "block";
      setTimeout(() => t.style.display = "none", 3000);
    }

    // ── Comment moderation ──
    function escapeHtml(s) {
      return (s || "").replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    function updatePendingBadge(count) {
      const badge = document.getElementById("pendingBadge");
      if (!badge) return;
      if (count > 0) { badge.textContent = count; badge.style.display = "inline-block"; }
      else { badge.style.display = "none"; }
    }

    function loadPendingComments() {
      fetch("get_pending_comments.php").then(r => r.json()).then(data => {
        if (!data.success) { showToast("Could not load comments."); return; }
        updatePendingBadge(data.count);
        const el = document.getElementById("pendingList");
        el.innerHTML = "";
        if (!data.pending.length) {
          el.innerHTML = '<p style="color:var(--text-mute);font-size:.9rem;font-style:italic;padding:1rem 0;">Nothing to review — you\'re all caught up.</p>';
          return;
        }
        data.pending.forEach(c => {
          const card = document.createElement("div");
          card.className = "mod-card";
          const author = c.name ? escapeHtml(c.name) : "Anonymous";
          card.innerHTML = `
            <div class="mod-head">
              <span class="mod-author">${author}</span>
              <span class="mod-meta">on “${escapeHtml(c.poem_title || "—")}” · ${escapeHtml(c.created_at)}</span>
            </div>
            <div class="mod-body">${escapeHtml(c.comment)}</div>
            <div class="mod-actions">
              <button class="btn-approve" onclick="moderateComment(${c.id}, 'approve', this)"><i class="bi bi-check2"></i> Approve</button>
              <button class="btn-reject" onclick="moderateComment(${c.id}, 'delete', this)"><i class="bi bi-trash3"></i> Delete</button>
            </div>`;
          el.appendChild(card);
        });
      }).catch(() => showToast("Could not load comments."));
    }

    function moderateComment(id, action, btn) {
      if (action === "delete" && !confirm("Delete this comment permanently?")) return;
      fetch("moderate_comment.php", {
        method: "POST", headers: { "Content-Type": "application/json", "X-CSRF-Token": CSRF_TOKEN },
        body: JSON.stringify({ id, action })
      }).then(r => r.json()).then(data => {
        if (data.success) {
          btn.closest(".mod-card").remove();
          showToast(action === "approve" ? "Comment approved." : "Comment deleted.");
          // Refresh badge count
          refreshPendingBadge();
          const el = document.getElementById("pendingList");
          if (el && !el.querySelector(".mod-card")) {
            el.innerHTML = '<p style="color:var(--text-mute);font-size:.9rem;font-style:italic;padding:1rem 0;">Nothing to review — you\'re all caught up.</p>';
          }
        } else { showToast("Action failed."); }
      }).catch(() => showToast("Action failed."));
    }

    function refreshPendingBadge() {
      fetch("get_pending_comments.php").then(r => r.json()).then(data => {
        if (data.success) updatePendingBadge(data.count);
      }).catch(() => {});
    }

    // ── Maintenance ──
    let _maintenanceActive = false;
    function loadMaintenanceStatus() {
      fetch("toggle_maintenance.php").then(r => r.json()).then(data => setMaintenanceUI(data.active))
        .catch(() => { document.getElementById("maintenanceStatus").textContent = "Could not load status."; });
    }
    function setMaintenanceUI(active) {
      _maintenanceActive = active;
      const toggle = document.getElementById("maintenanceToggle");
      const knob   = document.getElementById("maintenanceKnob");
      const status = document.getElementById("maintenanceStatus");
      if (active) {
        toggle.style.background = "var(--accent)";
        toggle.setAttribute("aria-checked", "true");
        knob.style.left = "25px"; knob.style.background = "#fff";
        status.textContent = "On — visitors are seeing the maintenance page";
        status.style.color = "var(--accent)";
      } else {
        toggle.style.background = "var(--border)";
        toggle.setAttribute("aria-checked", "false");
        knob.style.left = "3px"; knob.style.background = "var(--text-mute)";
        status.textContent = "Off — site is live";
        status.style.color = "var(--text-mute)";
      }
    }
    function toggleMaintenance() {
      const newState = !_maintenanceActive;
      fetch("toggle_maintenance.php", {
        method: "POST", headers: { "Content-Type": "application/json", "X-CSRF-Token": CSRF_TOKEN },
        body: JSON.stringify({ enable: newState })
      }).then(r => r.json()).then(data => {
        if (data.success) { setMaintenanceUI(data.active); showToast(data.active ? "Maintenance mode ON" : "Maintenance mode OFF"); }
        else { setMaintenanceUI(data.active); showToast(data.error || "Toggle failed."); }
      }).catch(() => showToast("Toggle failed."));
    }

    // ── Init ──
    document.addEventListener("DOMContentLoaded", () => {
      const saved = localStorage.getItem("tr_dashboard_panel");
      showPanel(saved && ["home","compose","allpoems","archived","categories","stats","comments","notifications","settings"].includes(saved) ? saved : "home");
      refreshPendingBadge(); // show pending count on the Comments nav item
    });
    document.querySelectorAll(".nav-item").forEach(btn => {
      btn.addEventListener("click", () => {
        const match = btn.id?.replace("nav-", "");
        if (match) localStorage.setItem("tr_dashboard_panel", match);
      });
    });
  </script>
  <script src="admin-categories.js?v=20261026-2"></script>
  <script src="admin-writing.js"></script>
  <script src="admin-notifications.js?v=20261027-3"></script>
</body>
</html>
