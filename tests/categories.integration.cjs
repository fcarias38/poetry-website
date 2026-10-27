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
    for (const file of ['categories.php', 'manage_categories.php', 'save_work.php', 'get_poems.php', 'csrf.php', 'admin_session.php', 'maintenance_check.php', 'poem.php', 'preview_poem.php', 'home.shtml', 'theme.css', 'theme.js']) fs.copyFileSync(path.join(root, file), path.join(siteDir, file));
    fs.writeFileSync(path.join(siteDir, 'admin_config.php'), '<?php // Isolated test credentials.');
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
      $conn->query("CREATE TABLE IF NOT EXISTS comments (id INT AUTO_INCREMENT PRIMARY KEY, poem_id INT, approved TINYINT DEFAULT 0, name VARCHAR(255) DEFAULT '', comment VARCHAR(1000) DEFAULT '', created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
      if (isset($_GET['seed'])) {
        $conn->query("INSERT INTO poems (title, category, body, archived) VALUES
          ('First', 'God', 'First poem', 0), ('Second', 'God', 'Second poem', 1), ('Third', 'Love', 'Third poem', 0)");
        $conn->query('UPDATE poems SET likes = 8 WHERE id = 1');
        $conn->query('INSERT INTO comments (poem_id, approved) VALUES (1, 1), (1, 1), (1, 0), (2, 1)');
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
    const galleryPoems = await (await fetch(base + 'get_poems.php')).json();
    assert.equal(Number(galleryPoems.find(p => p.title === 'First').likes), 8, 'Gallery API includes likes');
    assert.equal(Number(galleryPoems.find(p => p.title === 'First').comment_count), 2, 'Gallery counts approved comments only');
    assert.equal(Number(galleryPoems.find(p => p.title === 'Third').comment_count), 0, 'Poems without comments retain zero counts');
    fs.mkdirSync(path.join(siteDir, 'poems'));
    for (const [id, hasComments] of [[1, true], [3, false]]) {
      const response = await fetch(base + 'poem.php?id=' + id);
      assert.equal(response.status, 200, 'Poem page renders');
      const html = await response.text();
      assert.ok(!html.includes('Fatal error'), 'Poem page has no PHP error');
      const section = html.match(/<div id="commentsSection"[^>]*>/)[0];
      assert.equal(section.includes(' hidden'), !hasComments, 'Only existing comments show their section initially');
      assert.ok(html.includes('id="commentToggle"'), 'First-comment action remains available');
      const navigationChecks = `<script>document.addEventListener('DOMContentLoaded', async () => {
        try {
          for(let i=0; i<30 && document.querySelectorAll('#topicsMenu a').length<3; i++) await new Promise(r=>setTimeout(r,100));
          const nav=document.querySelector('.topnav'), trigger=document.getElementById('topicsTrigger'), hamburger=document.getElementById('hamburger'), mobile=document.getElementById('mobileMenu');
          if(!nav.querySelector('a[href="/author"]') || !nav.querySelector('a[href="/contact"]')) throw Error('Full navigation missing');
          if(document.querySelectorAll('#topicsMenu a').length!==3) throw Error('Topics must load from root endpoint on a nested poem URL');
          if(getComputedStyle(nav).position!=='sticky' || nav.getBoundingClientRect().height<62) throw Error('Sticky header dimensions');
          if(innerWidth<=640){
            const menuRect=hamburger.getBoundingClientRect(),themeRect=document.querySelector('.theme-toggle').getBoundingClientRect();
            if(menuRect.width<48 || menuRect.height<44 || menuRect.left-themeRect.right<16) throw Error('Mobile menu touch area');
            if(getComputedStyle(nav.querySelector('.topnav-links')).display!=='none') throw Error('Desktop links must collapse on mobile');
            hamburger.click(); if(!mobile.classList.contains('open') || hamburger.getAttribute('aria-expanded')!=='true') throw Error('Mobile menu does not open');
            document.querySelector('.mobile-topics summary').click(); if(document.querySelectorAll('#mobileTopics a').length!==3) throw Error('Mobile topic links missing');
            document.dispatchEvent(new KeyboardEvent('keydown',{key:'Escape',bubbles:true})); if(mobile.classList.contains('open')) throw Error('Escape does not close mobile menu');
          }else{
            if(getComputedStyle(nav.querySelector('.topnav-links')).display==='none') throw Error('Desktop menu hidden');
            trigger.click(); if(trigger.getAttribute('aria-expanded')!=='true') throw Error('Desktop Topics does not open');
            document.dispatchEvent(new KeyboardEvent('keydown',{key:'Escape',bubbles:true})); if(trigger.getAttribute('aria-expanded')!=='false') throw Error('Desktop Topics does not close');
          }
          const section=document.getElementById('commentsSection'),initiallyHidden=section.hidden;
          document.getElementById('commentToggle').click(); if(section.hidden===initiallyHidden) throw Error('Comment section does not toggle');
          document.getElementById('commentToggle').click();
          let called=''; window.fetch=async url=>{called=url;return {json:async()=>({likes:9,success:true})};};
          likePoem(); if(called!=='/update_likes.php') throw Error('Like URL must be rooted');
          document.getElementById('commentText').value='Test'; postComment(); if(called!=='/save_comment.php') throw Error('Comment URL must be rooted');
          if(document.documentElement.scrollWidth>innerWidth) throw Error('Poem page overflow');
          document.documentElement.dataset.navigationCheck='PASS';
        }catch(error){document.documentElement.dataset.navigationCheck=error.message;}
      });</script>`;
      fs.writeFileSync(path.join(siteDir, 'poems', 'navigation-' + id + '.html'), html.replace('</body>', navigationChecks + '</body>'));
      for (const width of [1440, 520]) {
        const browser = spawn(process.env.TEST_CHROME || 'C:/Program Files/Google/Chrome/Application/chrome.exe', ['--headless', '--disable-gpu', '--no-first-run', '--no-default-browser-check', '--user-data-dir=' + path.join(temp, 'poem-browser-' + id + '-' + width), '--window-size=' + width + ',1000', '--virtual-time-budget=5000', '--dump-dom', base + 'poems/navigation-' + id + '.html'], { windowsHide: true });
        let output=''; browser.stdout.on('data', data=>{output+=data;}); browser.stderr.resume();
        const status=await new Promise((resolve,reject)=>{browser.once('error',reject);browser.once('exit',resolve);});
        assert.equal(status,0,'Poem navigation browser exits cleanly');
        assert.ok(output.includes('data-navigation-check="PASS"'), 'Poem '+id+' at '+width+'px: '+(output.match(/data-navigation-check="([^"]*)"/)?.[1]||'No browser check result'));
      }
    }
    console.log('PASS: rendered poem pages retain full desktop/mobile navigation, nested topic and action URLs, larger menu targets and empty-comment behavior.');
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
