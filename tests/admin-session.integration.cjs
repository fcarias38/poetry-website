// Authentication tests use disposable credentials and files, never the live site.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const net = require('node:net');
const { spawn } = require('node:child_process');
const { setTimeout: delay } = require('node:timers/promises');

(async () => {
  const root = path.resolve(__dirname, '..');
  const temp = fs.mkdtempSync(path.join(os.tmpdir(), 'think-room-session-'));
  const config = path.join(temp, 'admin_config.php');
  fs.writeFileSync(config, "<?php $ADMIN_EMAIL='test@example.test'; $ADMIN_PASSWORD='test-only-password';");
  for (const file of ['admin_session.php', 'admin_auth.php', 'admin_login.php', 'logout.php', 'csrf.php', 'support_details.php']) {
    fs.copyFileSync(path.join(root, file), path.join(temp, file));
  }
  fs.writeFileSync(path.join(temp, 'fixture.php'), `<?php
    require 'csrf.php';
    if (!empty($_SESSION['logged_in']) && isset($_GET['age'])) {
      $_SESSION['last_activity'] = time() - 1801;
    }
    header('Content-Type: application/json');
    echo json_encode(['logged_in' => !empty($_SESSION['logged_in']), 'csrf' => csrf_token()]);`);
  const portProbe = net.createServer();
  await new Promise(resolve => portProbe.listen(0, '127.0.0.1', resolve));
  const port = portProbe.address().port;
  await new Promise(resolve => portProbe.close(resolve));
  const server = spawn(process.env.TEST_PHP || 'C:/xampp/php/php.exe', ['-S', `127.0.0.1:${port}`, '-t', temp], { windowsHide: true, stdio: 'ignore' });
  const base = `http://127.0.0.1:${port}/`;
  const cookie = (response, name) => [...(response.headers.get('set-cookie') || '').matchAll(new RegExp(`${name}=([^;,\\s]*)`, 'g'))].at(-1)?.[1];
  const read = async (cookies = '', query = '') => {
    const response = await fetch(base + 'fixture.php' + query, { headers: { Cookie: cookies } });
    return { response, data: await response.json() };
  };
  const login = async remember => {
    const response = await fetch(base + 'admin_login.php', { method: 'POST', redirect: 'manual', body: new URLSearchParams({ email: 'test@example.test', password: 'test-only-password', ...(remember ? { remember: 'on' } : {}) }) });
    assert.equal(response.headers.get('location'), '/dashboard');
    return { response, session: `PHPSESSID=${cookie(response, 'PHPSESSID')}`, token: cookie(response, 'tr_remember') };
  };
  try {
    let ready = false;
    for (let attempt = 0; attempt < 60; attempt++) {
      try { await read(); ready = true; break; } catch { await delay(100); }
    }
    assert.ok(ready, 'Isolated PHP server did not start');
    assert.equal((await read()).data.logged_in, false);
    const remembered = await login(true);
    assert.match(remembered.token, /^[a-f0-9]{64}$/);
    assert.match(remembered.response.headers.get('set-cookie'), /HttpOnly/i);
    assert.match(remembered.response.headers.get('set-cookie'), /SameSite=Lax/i);
    const records = () => fs.readdirSync(path.join(temp, 'admin_tokens'));
    assert.ok(!records()[0].includes(remembered.token), 'Raw token must not be stored on disk');
    const recordPath = path.join(temp, 'admin_tokens', records()[0]);
    const record = JSON.parse(fs.readFileSync(recordPath, 'utf8').split('\n')[1]);
    assert.ok(Math.abs(record.expires - Date.now() / 1000 - 604800) < 10, 'Seven-day server expiry');
    const first = await read(remembered.session);
    const restored = await read(`tr_remember=${remembered.token}`);
    assert.equal(restored.data.logged_in, true, 'Restore after PHP session is lost');
    assert.equal(restored.data.csrf, first.data.csrf, 'Open editor CSRF survives session restoration');
    const restoredSession = `PHPSESSID=${cookie(restored.response, 'PHPSESSID')}; tr_remember=${remembered.token}`;
    await read(restoredSession, '?age=1');
    assert.equal((await read(restoredSession)).data.logged_in, true, 'Remembered login renews an idle session');
    await fetch(base + 'logout.php', { redirect: 'manual', headers: { Cookie: restoredSession } });
    assert.equal((await read(`tr_remember=${remembered.token}`)).data.logged_in, false, 'Logout revokes saved token');
    const temporary = await login(false);
    assert.ok(!temporary.token || temporary.token === 'deleted', 'Unchecked option creates no remembered token');
    await read(temporary.session, '?age=1');
    assert.equal((await read(temporary.session)).data.logged_in, false, 'Normal login has an idle timeout');
    const expired = await login(true);
    const expiryPath = path.join(temp, 'admin_tokens', records()[0]);
    const expiredRecord = JSON.parse(fs.readFileSync(expiryPath, 'utf8').split('\n')[1]);
    expiredRecord.expires = Math.floor(Date.now() / 1000) - 1;
    fs.writeFileSync(expiryPath, '<?php exit; ?>\n' + JSON.stringify(expiredRecord));
    assert.equal((await read(`tr_remember=${expired.token}`)).data.logged_in, false, 'Expired token rejected');
    const changed = await login(true);
    fs.appendFileSync(config, '\n// Simulate password reset updating credentials.');
    assert.equal((await read(changed.session + `; tr_remember=${changed.token}`)).data.logged_in, false, 'Password change revokes active and remembered login');
    assert.equal((await read('tr_remember=../../admin_config.php')).data.logged_in, false, 'Malformed tokens rejected');
    console.log('PASS: seven-day expiry, session recovery, CSRF continuity, idle timeout, logout revocation, credential changes and malformed tokens.');
    for (const page of ['author.php', 'contact.php']) {
      assert.ok(!fs.readFileSync(path.join(root, page), 'utf8').includes('@outlook.com'), page + ' must not expose the email in source');
    }
    const publicSession = await read();
    const supportHeaders = { Cookie: `PHPSESSID=${cookie(publicSession.response, 'PHPSESSID')}`, 'X-CSRF-Token': publicSession.data.csrf, 'Content-Type': 'application/json' };
    const reveal = (headers = supportHeaders, website = '') => fetch(base + 'support_details.php', { method: 'POST', headers, body: JSON.stringify({ website }) });
    assert.equal((await fetch(base + 'support_details.php')).status, 405, 'Email cannot be scraped through GET');
    assert.equal((await reveal({})).status, 403, 'Anonymous POST without CSRF rejected');
    assert.equal((await reveal({ ...supportHeaders, Origin: 'https://other.example' })).status, 403, 'Foreign origin rejected');
    assert.equal((await reveal(supportHeaders, 'spam.example')).status, 400, 'Honeypot rejected');
    for (let attempt = 0; attempt < 10; attempt++) {
      const response = await reveal();
      assert.equal(response.status, 200, 'Human reveal request succeeds');
      assert.match(response.headers.get('cache-control'), /no-store/);
      assert.ok((await response.json()).email.includes('@'), 'Reveal contains Zelle address');
    }
    assert.equal((await reveal()).status, 429, 'Repeated requests rate limited');
    console.log('PASS: email absent from page sources; reveal requires POST, session CSRF and same origin; honeypot, no-cache and rate limit enforced.');
  } finally {
    server.kill();
  }
})().catch(error => { console.error(error); process.exitCode = 1; });
