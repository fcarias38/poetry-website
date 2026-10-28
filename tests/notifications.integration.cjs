// Disposable PHP/MariaDB and local SMTP only; never reads production credentials.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const net = require('node:net');
const { spawn, spawnSync } = require('node:child_process');
const { setTimeout: delay } = require('node:timers/promises');

async function freePort() {
  const server = net.createServer();
  await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
  const port = server.address().port;
  await new Promise(resolve => server.close(resolve));
  return port;
}

(async () => {
  const root = path.resolve(__dirname, '..');
  const temp = fs.mkdtempSync(path.join(os.tmpdir(), 'think-room-notifications-'));
  const site = path.join(temp, 'site');
  const db = path.join(temp, 'db');
  const php = process.env.TEST_PHP || 'C:/xampp/php/php.exe';
  const mysql = process.env.TEST_MYSQL_BIN || 'C:/xampp/mysql/bin';
  const dbPort = await freePort();
  const webPort = await freePort();
  const messages = [];
  const children = [];
  // Capture mail accepted by PHP, including recipient and subject, without delivery.
  const smtp = net.createServer(socket => {
    socket.setEncoding('utf8');
    socket.write('220 localhost test SMTP\r\n');
    let buffer = '', message = '', receiving = false;
    socket.on('data', chunk => {
      buffer += chunk;
      let index;
      while ((index = buffer.indexOf('\r\n')) >= 0) {
        const line = buffer.slice(0, index);
        buffer = buffer.slice(index + 2);
        if (receiving) {
          if (line === '.') { messages.push(message); receiving = false; socket.write('250 accepted\r\n'); }
          else message += line + '\n';
        } else if (/^DATA/i.test(line)) { receiving = true; message = ''; socket.write('354 send data\r\n'); }
        else if (/^QUIT/i.test(line)) socket.end('221 bye\r\n');
        else socket.write('250 OK\r\n');
      }
    });
  });
  await new Promise(resolve => smtp.listen(0, '127.0.0.1', resolve));
  try {
    const init = spawnSync(path.join(mysql, 'mysql_install_db.exe'), [`--datadir=${db}`, `--port=${dbPort}`, '--silent'], { windowsHide: true, encoding: 'utf8' });
    assert.equal(init.status, 0, init.stderr || init.stdout || String(init.error));
    children.push(spawn(path.join(mysql, 'mysqld.exe'), [`--defaults-file=${path.join(db, 'my.ini')}`, '--console', '--bind-address=127.0.0.1'], { windowsHide: true, stdio: 'ignore' }));
    fs.mkdirSync(site);
    for (const file of ['admin_session.php', 'csrf.php', 'notifications.php', 'admin_notifications.php', 'save_comment.php', 'update_likes.php', 'track_share.php', 'admin-notifications.js', 'admin.css', 'theme.css']) fs.copyFileSync(path.join(root, file), path.join(site, file));
    fs.writeFileSync(path.join(site, 'admin_config.php'), `<?php $ADMIN_EMAIL='admin@example.test'; $MAIL_FROM='noreply@example.test';`);
    fs.writeFileSync(path.join(site, 'config.php'), `<?php mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); $conn=new mysqli('127.0.0.1','root','','notifications_test',${dbPort}); $conn->set_charset('utf8mb4');`);
    fs.writeFileSync(path.join(site, 'fixture.php'), `<?php
      require 'csrf.php';
      $conn=new mysqli('127.0.0.1','root','','',${dbPort});
      $conn->query('CREATE DATABASE IF NOT EXISTS notifications_test'); $conn->select_db('notifications_test');
      $conn->query("CREATE TABLE IF NOT EXISTS poems (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, title VARCHAR(255), likes INT DEFAULT 0, shares INT DEFAULT 0)");
      $conn->query("CREATE TABLE IF NOT EXISTS comments (id INT AUTO_INCREMENT PRIMARY KEY, poem_id INT, name VARCHAR(100), comment TEXT, ip_address VARCHAR(45), approved TINYINT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
      if (isset($_GET['seed'])) $conn->query("INSERT INTO poems (title) VALUES ('A poem <img src=x onerror=alert(1)>')");
      if (isset($_GET['duplicate'])) $conn->query("INSERT INTO poems (title) SELECT title FROM poems WHERE id=1");
      if (isset($_GET['many'])) $conn->query("INSERT INTO admin_notifications (kind,poem_id,poem_title) SELECT 'share',1,'A poem <img src=x onerror=alert(1)>' FROM information_schema.COLUMNS LIMIT 45");
      if (isset($_GET['break'])) $conn->query('DROP TABLE admin_notifications');
      if (isset($_GET['break'])) $conn->query('CREATE TABLE admin_notifications (broken INT)');
      $_SESSION['logged_in']=true;
      if (isset($_GET['idle'])) $_SESSION['last_activity']=time()-60;
      echo json_encode(['token'=>csrf_token(),'last_activity'=>$_SESSION['last_activity'] ?? null,'shares'=>$conn->query('SELECT shares FROM poems ORDER BY id')->fetch_all(MYSQLI_ASSOC),'pending'=>(int)$conn->query('SELECT COUNT(*) FROM comments WHERE approved=0')->fetch_row()[0]]);`);
    fs.writeFileSync(path.join(site, 'inspect-session.php'), '<?php session_start(); echo json_encode(["last_activity"=>$_SESSION["last_activity"]]);');
    children.push(spawn(php, ['-d', 'SMTP=127.0.0.1', '-d', `smtp_port=${smtp.address().port}`, '-S', `127.0.0.1:${webPort}`, '-t', site], { windowsHide: true, stdio: 'ignore' }));
    const base = `http://127.0.0.1:${webPort}/`;
    let fixture;
    for (let i = 0; i < 100; i++) {
      try { fixture = await fetch(base + 'fixture.php'); if (fixture.ok && (await fixture.clone().text()).startsWith('{')) break; } catch {}
      await delay(100);
    }
    assert.ok(fixture?.ok, 'Test server starts');
    const cookie = fixture.headers.get('set-cookie').split(';')[0];
    const { token } = await (await fetch(base + 'fixture.php?seed=1', { headers: { Cookie: cookie } })).json();
    const headers = { Cookie: cookie, 'Content-Type': 'application/json', 'X-CSRF-Token': token };
    const api = async (file, body, custom = headers) => fetch(base + file, { headers: custom, ...(body === undefined ? {} : { method: 'POST', body: JSON.stringify(body) }) });
    assert.equal((await api('admin_notifications.php', undefined, {})).status, 403);
    assert.equal((await api('admin_notifications.php', { through_id: 1 }, { Cookie: cookie })).status, 403);
    assert.equal((await fetch(base + 'admin_notifications.php', { method: 'DELETE', headers })).status, 405);
    assert.equal((await (await api('admin_notifications.php')).json()).unread_count, 0);
    assert.equal((await (await api('update_likes.php', { id: 1 }, {})).json()).likes, 1);
    assert.equal((await (await api('update_likes.php', { id: 1 }, {})).json()).likes, 2);
    assert.equal(messages.length, 1, 'Like email burst is throttled');
    const comment = await (await api('save_comment.php', { id: 1, name: '<script>Reader</script>', comment: 'Great poem' }, {})).json();
    assert.equal(comment.pending, true);
    assert.deepEqual(comment.comments, [], 'Pending comment stays private');
    assert.equal(messages.length, 2, 'Comment triggers email');
    assert.ok(messages[1].includes('awaiting review'));
    assert.ok(messages[1].includes('admin@example.test'));
    assert.ok(messages[1].includes('https://freddycarias.com/dashboard'));
    let feed = await (await api('admin_notifications.php')).json();
    assert.equal(feed.unread_count, 3);
    assert.equal(feed.notifications[0].kind, 'comment');
    assert.ok(feed.notifications[0].created_at.endsWith('Z'));
    const seen = feed.latest_id;
    await api('update_likes.php', { id: 1 }, {});
    feed = await (await api('admin_notifications.php', { through_id: seen })).json();
    assert.equal(feed.unread_count, 1, 'Acknowledgement preserves newer activity');
    feed = await (await api('admin_notifications.php', { through_id: feed.latest_id })).json();
    assert.equal(feed.unread_count, 0);
    assert.equal(feed.notifications.length, 4, 'Read notifications remain available');
    assert.equal((await api('admin_notifications.php', {})).status, 400);
    assert.equal((await api('update_likes.php', { id: 999 }, {})).status, 404);
    await api('save_comment.php', { id: 999, comment: 'Missing' }, {});
    assert.equal((await (await api('admin_notifications.php')).json()).notifications.length, 4, 'Invalid activity creates no notification');
    assert.equal((await fetch(base + 'track_share.php?id=1')).status, 405, 'GET cannot record a share');
    assert.equal((await api('track_share.php', { id: 999 }, {})).status, 404);
    await fetch(base + 'fixture.php?duplicate=1', { headers });
    assert.equal((await (await api('track_share.php', { id: 1 }, {})).json()).success, true);
    assert.equal((await (await api('track_share.php', { title: 'A poem <img src=x onerror=alert(1)>' }, {})).json()).success, true);
    const formShare = await fetch(base + 'track_share.php', { method: 'POST', body: new URLSearchParams({ id: '1' }) });
    assert.equal((await formShare.json()).success, true, 'Legacy form shares remain supported');
    const shared = await (await fetch(base + 'fixture.php', { headers })).json();
    assert.deepEqual(shared.shares.map(row => Number(row.shares)), [3, 0], 'Title fallback updates only one matching poem');
    assert.equal(messages.length, 5, 'Each share sends email independently of like throttling');
    assert.ok(messages[4].includes('a reader shared a poem'));
    feed = await (await api('admin_notifications.php')).json();
    assert.equal(feed.unread_count, 3);
    assert.equal(feed.notifications[0].kind, 'share');
    assert.equal((await (await api('admin_notifications.php', { through_id: feed.latest_id })).json()).unread_count, 0);
    const idle = await (await fetch(base + 'fixture.php?idle=1', { headers })).json();
    await api('admin_notifications.php');
    const polled = await (await fetch(base + 'inspect-session.php', { headers })).json();
    assert.equal(polled.last_activity, idle.last_activity, 'Passive polling preserves idle timeout');
    await fetch(base + 'fixture.php?many=1', { headers });
    feed = await (await api('admin_notifications.php')).json();
    assert.equal(feed.unread_count, 45);
    assert.equal(feed.notifications.length, 40, 'Feed is bounded independently of unread count');

    const widget = fs.readFileSync(path.join(root, 'dashboard.php'), 'utf8').match(/<div class="notification-center"[\s\S]*?<\/section>[\s\S]*?<\/div>/)[0];
    const checks = `(async () => {
      const wait=async predicate=>{for(let i=0;i<100;i++){if(predicate())return;await new Promise(r=>setTimeout(r,30));}throw Error('Timed out');};
      try {
        const session=await (await fetch('/fixture.php')).json();document.querySelector('meta[name="csrf-token"]').content=session.token;
        const script=document.createElement('script');script.src='/admin-notifications.js';document.body.append(script);
        await wait(()=>document.getElementById('notificationCount').textContent==='45');
        const toggle=document.getElementById('notificationToggle'), panel=document.getElementById('notificationPopover');
        if(!panel.hidden)throw Error('Initially collapsed');toggle.click();await wait(()=>!document.getElementById('notificationRead').disabled);
        if(toggle.getAttribute('aria-expanded')!=='true')throw Error('Accessible expansion');
        if(document.querySelector('#notificationList img'))throw Error('Unsafe notification HTML');
        if(!document.getElementById('notificationList').textContent.includes('share options'))throw Error('Shares mislabeled');
        if(document.documentElement.scrollWidth>innerWidth)throw Error('Mobile overflow');
        document.dispatchEvent(new KeyboardEvent('keydown',{key:'Escape',bubbles:true}));if(!panel.hidden)throw Error('Escape');
        toggle.click();await wait(()=>!document.getElementById('notificationRead').disabled);
        document.getElementById('notificationRead').click();await wait(()=>document.getElementById('notificationCount').hidden);
        document.documentElement.dataset.notificationTest='PASS';
      }catch(error){document.documentElement.dataset.notificationTest=error.message;}})();`;
    fs.writeFileSync(path.join(site, 'browser.html'), `<!doctype html><html data-theme-scope="admin" data-theme="light"><head><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="${token}"><link rel="stylesheet" href="/theme.css"><link rel="stylesheet" href="/admin.css"></head><body>${widget}<script>${checks}</script></body></html>`);
    const browser = spawn(process.env.TEST_CHROME || 'C:/Program Files/Google/Chrome/Application/chrome.exe', ['--headless', '--disable-gpu', '--no-first-run', '--no-default-browser-check', '--user-data-dir=' + path.join(temp, 'chrome'), '--window-size=390,844', '--virtual-time-budget=10000', '--dump-dom', base + 'browser.html'], { windowsHide: true });
    children.push(browser);
    let output = ''; browser.stdout.on('data', chunk => { output += chunk; }); browser.stderr.resume();
    assert.equal(await new Promise((resolve, reject) => { browser.once('exit', resolve); browser.once('error', reject); }), 0);
    assert.ok(output.includes('data-notification-test="PASS"'), output.match(/data-notification-test="([^"]*)"/)?.[1] || output);
    // A broken notification table cannot lose the reader's submitted activity.
    await fetch(base + 'fixture.php?break=1', { headers });
    assert.equal((await (await api('update_likes.php', { id: 1 }, {})).json()).likes, 4);
    assert.equal((await (await api('save_comment.php', { id: 1, comment: 'Still saved' }, {})).json()).success, true);
    assert.equal((await (await api('track_share.php', { id: 1 }, {})).json()).success, true, 'Notification failure cannot reject a share');
    assert.equal((await (await fetch(base + 'fixture.php', { headers })).json()).pending, 2);
    console.log('PASS: activity capture, email delivery to local SMTP, like throttling, pending moderation, access/CSRF, durable unread state, concurrent arrival, bounded feed, mobile widget, safe text rendering, acknowledgement, failure isolation.');
  } finally {
    for (const child of children.reverse()) {
      if (child.exitCode === null) { const exited = new Promise(resolve => child.once('exit', resolve)); child.kill(); await exited; }
    }
    await new Promise(resolve => smtp.close(resolve));
    fs.rmSync(temp, { recursive: true, force: true, maxRetries: 5, retryDelay: 100 });
  }
})().catch(error => { console.error(error); process.exitCode = 1; });
