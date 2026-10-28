// Read-only authenticated checks using local admin config. Credentials stay in memory.
const assert = require('node:assert/strict');
const path = require('node:path');
const { spawnSync } = require('node:child_process');
const php = process.env.TEST_PHP || 'C:/xampp/php/php.exe';
(async () => {
  const config = path.resolve(__dirname, '..', 'admin_config.php');
  const read = spawnSync(php, ['-r', 'require $argv[1]; echo json_encode(["email" => $ADMIN_EMAIL ?? "", "password" => $ADMIN_PASSWORD ?? ""]);', config], { windowsHide: true, encoding: 'utf8' });
  assert.equal(read.status, 0, 'Could not load local admin configuration');
  const credentials = JSON.parse(read.stdout);
  assert.ok(credentials.email && credentials.password, 'No usable local admin login is configured');
  const base = 'https://freddycarias.com/';
  const login = await fetch(base + 'admin_login.php', { method: 'POST', redirect: 'manual', body: new URLSearchParams(credentials) });
  assert.ok([302, 303].includes(login.status), 'Login did not redirect');
  assert.ok((login.headers.get('location') || '').includes('dashboard'), 'Configured admin login was not accepted');
  const sessions = [...(login.headers.get('set-cookie') || '').matchAll(/PHPSESSID=([^;,\s]+)/g)];
  assert.ok(sessions.length, 'Login did not issue a session');
  const headers = { Cookie: `PHPSESSID=${sessions.at(-1)[1]}; tr_admin=1` };
  try {
    const response = await fetch(base + 'get_analytics.php', { headers });
    assert.equal(response.status, 200, 'Live analytics HTTP error');
    const data = await response.json();
    assert.equal(data.success, true, 'Live analytics returned an error');
    assert.ok(Array.isArray(data.poems) && Array.isArray(data.categories) && Array.isArray(data.comment_trend));
    assert.ok(data.top && data.totals);
    console.log(`PASS: authenticated live analytics returns ${data.poems.length} poems, ${data.categories.length} category groups, ${data.comment_trend.length} trend days.`);
    const dashboard = await (await fetch(base + 'dashboard', { headers })).text();
    assert.ok(dashboard.includes('renderCategoryBreakdown(data.categories)'), 'Live dashboard still has the old analytics renderer');
    const categoryScript = await (await fetch(base + 'admin-categories.js?v=20261026-2', { headers })).text();
    assert.ok(categoryScript.includes('function renderManagedCategories()'));
    assert.ok(!categoryScript.includes('function renderCategories('));
    console.log('PASS: live dashboard and category script use separate renderers.');
    assert.ok(dashboard.includes('id="notificationToggle"'), 'Live dashboard is missing the notification bell');
    assert.ok(dashboard.includes('id="nav-notifications"') && dashboard.includes('data-panel="notifications"'), 'Desktop and mobile menus must link to notifications');
    assert.ok(dashboard.includes('id="panel-notifications"') && dashboard.includes('id="notificationPageList"'), 'Live notifications page is missing');
    const feedResponse = await fetch(base + 'admin_notifications.php', { headers, redirect: 'manual' });
    assert.equal(feedResponse.status, 200, 'Live notification API must not redirect');
    assert.ok(feedResponse.headers.get('cache-control').includes('no-store'));
    const feed = await feedResponse.json();
    assert.equal(feed.success, true, 'Live notification storage failed to initialize');
    assert.ok(Number.isInteger(feed.unread_count) && Array.isArray(feed.notifications));
    assert.equal((await fetch(base + 'admin_notifications.php')).status, 403, 'Notifications must remain private');
    const notifications = await (await fetch(base + 'admin-notifications.js?v=20261027-3', { headers })).text();
    assert.ok(notifications.includes("item.kind === 'share'"), 'Live notification widget must recognize shares');
    console.log('PASS: live notification bell, share rendering, authenticated feed, private/no-cache API and database initialization.');
  } finally {
    await fetch(base + 'logout.php', { headers, redirect: 'manual' });
  }
})().catch(error => { console.error(error.message); process.exitCode = 1; });
