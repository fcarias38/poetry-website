// Runs against a disposable MariaDB instance; never reads the site's config.php.
// Windows/XAMPP: node tests/categories.integration.cjs
const assert = require('node:assert/strict');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const { spawn, spawnSync } = require('node:child_process');
const { setTimeout: delay } = require('node:timers/promises');
const net = require('node:net');

async function freePort() {
  const server = net.createServer();
  await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
  const port = server.address().port;
  await new Promise(resolve => server.close(resolve));
  return port;
}

(async () => {
  const root = path.resolve(__dirname, '..');
  const temp = fs.mkdtempSync(path.join(os.tmpdir(), 'think-room-category-test-'));
  const dbDir = path.join(temp, 'db');
  const siteDir = path.join(temp, 'site');
  const php = process.env.TEST_PHP || 'C:/xampp/php/php.exe';
  const mysqlBin = process.env.TEST_MYSQL_BIN || 'C:/xampp/mysql/bin';
  const dbPort = await freePort();
  const webPort = await freePort();
  const children = [];
  try {
    const init = spawnSync(path.join(mysqlBin, 'mysql_install_db.exe'), [`--datadir=${dbDir}`, `--port=${dbPort}`, '--silent'], { windowsHide: true, encoding: 'utf8' });
    assert.equal(init.status, 0, init.stderr || init.stdout || String(init.error));
    const database = spawn(path.join(mysqlBin, 'mysqld.exe'), [`--defaults-file=${path.join(dbDir, 'my.ini')}`, '--console', '--bind-address=127.0.0.1'], { windowsHide: true, stdio: 'ignore' });
    children.push(database);
    fs.mkdirSync(siteDir);
    for (const file of ['categories.php', 'manage_categories.php', 'save_work.php', 'csrf.php', 'preview_poem.php', 'home.shtml']) fs.copyFileSync(path.join(root, file), path.join(siteDir, file));
    fs.writeFileSync(path.join(siteDir, 'config.php'), `<?php
      mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
      $conn = new mysqli('127.0.0.1', 'root', '', 'category_test', ${dbPort});
      $conn->set_charset('utf8mb4');`);
    // Fixtures exist only in the temporary server, outside the real website.
    fs.writeFileSync(path.join(siteDir, 'fixture.php'), `<?php
      require 'csrf.php';
      mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
      $conn = new mysqli('127.0.0.1', 'root', '', '', ${dbPort});
      $conn->query('CREATE DATABASE IF NOT EXISTS category_test');
      $conn->select_db('category_test');
      $conn->set_charset('utf8mb4');
      $conn->query("CREATE TABLE IF NOT EXISTS poems (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, title VARCHAR(255), category VARCHAR(100),
        body TEXT, archived TINYINT DEFAULT 0, slug VARCHAR(255), likes INT DEFAULT 0,
        meta_description TEXT, meta_keywords TEXT
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
      if (isset($_GET['seed'])) {
        $conn->query("INSERT INTO poems (title, category, body, archived) VALUES
          ('First', 'God', 'First poem', 0), ('Second', 'God', 'Second poem', 1), ('Third', 'Love', 'Third poem', 0)");
      }
      $_SESSION['logged_in'] = true;
      echo json_encode(['token' => csrf_token(), 'poems' => $conn->query('SELECT * FROM poems ORDER BY id')->fetch_all(MYSQLI_ASSOC)]);`);
    const web = spawn(php, ['-S', `127.0.0.1:${webPort}`, '-t', siteDir], { windowsHide: true, stdio: 'ignore' });
    children.push(web);
    const base = `http://127.0.0.1:${webPort}/`;
    let fixture;
    for (let attempt = 0; attempt < 80; attempt++) {
      try {
        const response = await fetch(base + 'fixture.php');
        const data = await response.json();
        if (data.token) { fixture = response; break; }
      } catch {}
      await delay(100);
    }
    assert.ok(fixture, 'Temporary PHP/MariaDB server did not start');
    const cookie = fixture.headers.get('set-cookie').split(';')[0];
    const seeded = await fetch(base + 'fixture.php?seed=1', { headers: { Cookie: cookie } });
    const { token } = await seeded.json();
    const headers = { Cookie: cookie, 'X-CSRF-Token': token, 'Content-Type': 'application/json' };
    const request = async (data, override = {}) => {
      const response = await fetch(base + 'manage_categories.php', data === undefined
        ? { headers: { Cookie: cookie } }
        : { method: 'POST', headers: { ...headers, ...override }, body: JSON.stringify(data) });
      return { status: response.status, ...await response.json() };
    };
    assert.equal((await fetch(base + 'manage_categories.php')).status, 403);
    assert.equal((await request({ action: 'create', name: 'Loss' }, { 'X-CSRF-Token': '' })).status, 403);
    let categories = (await request()).categories;
    assert.equal(categories.length, 2);
    const god = categories.find(c => c.name === 'God');
    const love = categories.find(c => c.name === 'Love');
    assert.equal(Number(god.poem_count), 2, 'Counts include archived poems');
    assert.equal((await request({ action: 'create', name: 'Heartbreak' })).success, true);
    assert.equal((await request({ action: 'create', name: 'heartbreak' })).status, 409);
    for (const name of ['', '  ', 'x'.repeat(101), 'Love\nLoss']) assert.equal((await request({ action: 'create', name })).status, 422);
    assert.equal((await request({ action: 'rename', id: god.id, name: 'Faith' })).success, true);
    assert.equal((await request({ action: 'rename', id: god.id, name: 'Love' })).status, 409);
    assert.equal((await request({ action: 'delete', id: god.id })).status, 422);
    assert.equal((await request({ action: 'delete', id: god.id, replacement_id: god.id })).status, 422);
    assert.equal((await request({ action: 'delete', id: god.id, replacement_id: 999999 })).status, 422);
    assert.equal((await request({ action: 'delete', id: god.id, replacement_id: love.id })).success, true);
    categories = (await request()).categories;
    assert.equal(Number(categories.find(c => c.name === 'Love').poem_count), 3);
    assert.ok(!categories.some(c => c.name === 'Faith' || c.name === 'God'));
    const heartbreak = categories.find(c => c.name === 'Heartbreak');
    assert.equal((await request({ action: 'delete', id: heartbreak.id })).success, true);
    const form = new URLSearchParams({ title: 'New poem', category: 'love', body: 'New words' });
    const saved = await fetch(base + 'save_work.php', { method: 'POST', headers: { Cookie: cookie, 'X-CSRF-Token': token }, body: form });
    assert.equal((await saved.json()).success, true);
    const state = await (await fetch(base + 'fixture.php', { headers: { Cookie: cookie } })).json();
    assert.equal(state.poems.length, 4);
    assert.ok(state.poems.every(poem => poem.category === 'Love'), 'Saved names use canonical capitalization');
    assert.deepEqual(state.poems.slice(0, 3).map(poem => [poem.body, Number(poem.archived)]), [['First poem', 0], ['Second poem', 1], ['Third poem', 0]]);
    const draft = { title: 'Private title', category: 'Private topic', body: '</script><script>alert(1)</script>\nLine two' };
    const previewForm = new URLSearchParams({ ...draft, csrf_token: token });
    assert.equal((await fetch(base + 'preview_poem.php', { method: 'POST', body: previewForm })).status, 403);
    assert.equal((await fetch(base + 'preview_poem.php', { headers: { Cookie: cookie } })).status, 405);
    assert.equal((await fetch(base + 'preview_poem.php', { method: 'POST', headers: { Cookie: cookie }, body: new URLSearchParams(draft) })).status, 403);
    const preview = await fetch(base + 'preview_poem.php', { method: 'POST', headers: { Cookie: cookie }, body: previewForm });
    assert.equal(preview.status, 200);
    assert.ok(preview.headers.get('cache-control').includes('no-store'));
    assert.equal(preview.headers.get('x-robots-tag'), 'noindex, nofollow');
    const html = await preview.text();
    const payload = JSON.parse(html.match(/window.THINK_ROOM_PREVIEW = (.*?);<\/script>/)[1]);
    assert.equal(payload.body, draft.body, 'Preview preserves line breaks and literal markup');
    assert.ok(!html.includes(draft.body), 'Preview payload cannot break out of its script');
    assert.ok(!html.includes('rel="canonical"'));
    assert.ok(html.includes('class="masonry"'));
    const afterPreview = await (await fetch(base + 'fixture.php', { headers: { Cookie: cookie } })).json();
    assert.equal(afterPreview.poems.length, 4, 'Preview never publishes a poem');
    console.log('PASS: import, counts, create, duplicates, validation, rename, rollback, reassignment, empty removal, canonical poem save, authentication and CSRF.');
    console.log('PASS: private gallery preview, no persistence, safe text encoding, line breaks, authentication, CSRF and no-cache/no-index headers.');
  } finally {
    for (const child of children.reverse()) {
      if (child.exitCode === null) { const exited = new Promise(resolve => child.once('exit', resolve)); child.kill(); await exited; }
    }
    // Only remove this test's freshly created directory after servers stop.
    fs.rmSync(temp, { recursive: true, force: true, maxRetries: 5, retryDelay: 100 });
  }
})().catch(error => { console.error(error); process.exitCode = 1; });
