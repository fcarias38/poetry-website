// Headless visual smoke check with mock data; no live account or database access.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const os = require('node:os');
const http = require('node:http');
const { spawn } = require('node:child_process');
const root = path.resolve(__dirname, '..');
const chrome = process.env.TEST_CHROME || 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const temp = fs.mkdtempSync(path.join(os.tmpdir(), 'think-room-browser-'));
const categories = ['God', 'Love', 'Heartbreak'].map((name, i) => ({ id: String(i + 1), name, poem_count: i + 1 }));
const poems = categories.map((category, i) => ({ id: i + 1, title: ['A quiet prayer', 'Where the light falls', 'What remains'][i], category: category.name, body: 'A line of poetry,\nand a moment to breathe.', slug: 'sample-' + i, archived: 0, views: 40, likes: 8, comments: 2, shares: 3, engagement: 86 }));
const server = http.createServer((request, response) => {
  const pathname = new URL(request.url, 'http://localhost').pathname;
  const fixtures = {
    '/manage_categories.php': { success: true, categories },
    '/get_analytics.php': {
      success: true, totals: { poems: 3, views: 120, likes: 24, comments: 6, shares: 9 }, poems,
      top: { engagement: [{ title: poems[0].title, value: 86 }], views: [{ title: poems[0].title, value: 40 }] },
      categories: categories.map(c => ({ category: c.name, views: 40, poems: 1 })),
      comment_trend: [{ day: '2026-10-25', count: 2 }, { day: '2026-10-26', count: 4 }],
      recent_comments: [{ name: 'Reader', comment: 'Beautiful words', poem_title: poems[0].title, created_at: '2026-10-26' }]
    },
    '/get_pending_comments.php': { success: true, count: 0, comments: [] },
    '/get_poems.php': poems
  };
  if (fixtures[pathname]) { response.setHeader('Content-Type', 'application/json'); response.end(JSON.stringify(fixtures[pathname])); return; }
  const file = path.join(root, pathname === '/' ? 'home.shtml' : pathname.slice(1));
  if (!file.startsWith(root + path.sep) || !fs.existsSync(file)) { response.writeHead(404); response.end(); return; }
  let content = fs.readFileSync(file);
  if (pathname === '/dashboard.php') {
    let markup = content.toString().replace(/<\?php if \(isset\(\$_GET\['success'\]\)\): \?>[\s\S]*?<\?php endif; \?>/g, '').replace(/<\?(?:php|=)[\s\S]*?\?>/g, '');
    const checks = `<script>document.addEventListener('DOMContentLoaded', async () => {
      try {
        if(document.documentElement.dataset.theme !== 'light') throw Error('Admin default');
        const toggle = document.querySelector('.theme-toggle'); toggle.click();
        if(document.documentElement.dataset.theme !== 'dark' || localStorage.getItem('tr_theme_admin') !== 'dark') throw Error('Admin dark toggle');
        toggle.click(); showPanel('categories'); await loadCategories();
        if(document.querySelectorAll('.category-table tbody tr').length !== 3) throw Error('Category rows');
        if(!document.querySelector('.category-table .bi-check2') || !document.querySelector('.category-table .bi-trash3')) throw Error('Action icons');
        if(!document.querySelector('#nav-compose .bi-pen')) throw Error('New poem icon');
        showPanel('stats'); await loadAnalytics();
        if(document.querySelectorAll('#catBreakdown .bar-row').length !== 3) throw Error('Analytics category chart');
        if(document.querySelectorAll('#poemMetricBody tr').length !== 3) throw Error('Analytics poem metrics');
        if(document.querySelectorAll('#trendChart .trend-bar').length !== 2) throw Error('Analytics comment trend');
        if(!document.querySelector('#recentComments .mini-comment')) throw Error('Analytics recent comments');
        if(document.querySelectorAll('.category-table tbody tr').length !== 3) throw Error('Analytics overwrote category management');
        showPanel('categories'); await loadCategories();
        if(document.documentElement.scrollWidth > innerWidth) throw Error('Page overflow');
        document.documentElement.dataset.browserCheck = 'PASS';
      } catch(e) { document.documentElement.dataset.browserCheck = e.message; }
    });</script>`;
    content = markup.replace('</body>', checks + '</body>');
  } else if (pathname === '/') {
    content = content.toString().replace('</body>', `<script>document.addEventListener('DOMContentLoaded', async () => {
      try {
        if(document.documentElement.dataset.theme !== 'dark') throw Error('Public default');
        document.querySelector('.theme-toggle').click();
        if(document.documentElement.dataset.theme !== 'light' || localStorage.getItem('tr_theme_site') !== 'light') throw Error('Public light toggle');
        await loadPoems();
        if(document.querySelectorAll('.poem-card').length !== 3) throw Error('Public gallery cards');
        if(document.querySelectorAll('#filterBar button').length !== 4) throw Error('Public category filters');
        document.documentElement.dataset.browserCheck = 'PASS';
      } catch(e) { document.documentElement.dataset.browserCheck = e.message; }
    });</script></body>`);
  }
  response.setHeader('Content-Type', /\.(?:shtml|php)$/.test(pathname) || pathname === '/' ? 'text/html; charset=utf-8' : pathname.endsWith('.css') ? 'text/css' : 'text/javascript');
  response.end(content);
});
(async () => {
  await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
  const port = server.address().port;
  try {
    for (const [label, url, width] of [['admin-desktop', '/dashboard.php', 1440], ['admin-mobile', '/dashboard.php', 520], ['public-light', '/', 1440]]) {
      const args = ['--headless', '--disable-gpu', '--no-first-run', '--no-default-browser-check', `--user-data-dir=${path.join(temp, label)}`, `--window-size=${width},1000`, '--virtual-time-budget=5000', '--dump-dom', `--screenshot=${path.join(temp, label + '.png')}`, `http://127.0.0.1:${port}${url}`];
      const child = spawn(chrome, args, { windowsHide: true });
      let stdout = '', stderr = '';
      child.stdout.on('data', data => { stdout += data; });
      child.stderr.on('data', data => { stderr += data; });
      const status = await new Promise((resolve, reject) => { child.once('error', reject); child.once('exit', resolve); });
      assert.equal(status, 0, stderr);
      assert.ok(stdout.includes('data-browser-check="PASS"'), label + ': ' + (stdout.match(/data-browser-check="([^"]*)"/)?.[1] || stderr));
      console.log(`PASS: ${label} (${path.join(temp, label + '.png')})`);
    }
  } finally {
    await new Promise(resolve => server.close(resolve));
  }
})().catch(error => { console.error(error); process.exitCode = 1; });
