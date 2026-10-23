<?php
// gallery.php — the homepage/gallery, routed through PHP so it can enforce
// maintenance mode (home.shtml is static and cannot run the PHP check).
// maintenance_check.php serves the maintenance page (503) to non-admins when
// the maintenance.flag file exists; logged-in admins pass through.
include 'maintenance_check.php';

// Not in maintenance (or an admin): output the gallery markup verbatim.
// home.shtml is plain HTML/JS (no SSI includes), so readfile is safe.
readfile(__DIR__ . '/home.shtml');
