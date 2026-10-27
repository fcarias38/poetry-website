// Real Apache test with an isolated document root. No production server is used.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const os = require('node:os');
const net = require('node:net');
const { spawn, spawnSync } = require('node:child_process');
const { setTimeout: delay } = require('node:timers/promises');
(async () => {
  const apache = process.env.TEST_APACHE_ROOT || 'C:/xampp/apache';
  const executable = path.join(apache, 'bin/httpd.exe');
  const temp = fs.mkdtempSync(path.join(os.tmpdir(), 'think-room-routing-'));
  const server = net.createServer();
  await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
  const port = server.address().port;
  await new Promise(resolve => server.close(resolve));
  const root = path.resolve(__dirname, '..');
  const docroot = path.join(temp, 'site');
  fs.mkdirSync(docroot);
  let child;
  try {
    const normalize = value => value.replaceAll('\\', '/');
    // Local HTTP fixture skips only the production HTTPS/domain redirect.
    let rules = fs.readFileSync(path.join(root, '.htaccess'), 'utf8').replace(/RewriteCond %\{HTTPS\} off \[OR\]\r?\nRewriteCond %\{HTTP_HOST\}[^\n]+\r?\nRewriteRule \^ https:[^\n]+\r?\n/, '');
    fs.writeFileSync(path.join(docroot, '.htaccess'), rules);
    for (const file of ['error.html', 'theme.css', 'theme.js']) fs.copyFileSync(path.join(root, file), path.join(docroot, file));
    fs.writeFileSync(path.join(docroot, 'sample.shtml'), '<h1>Existing page</h1>');
    const config = `ServerRoot "${normalize(apache)}"
Listen 127.0.0.1:${port}
ServerName localhost
PidFile "${normalize(path.join(temp, 'httpd.pid'))}"
ErrorLog "${normalize(path.join(temp, 'error.log'))}"
LoadModule authz_core_module modules/mod_authz_core.so
LoadModule rewrite_module modules/mod_rewrite.so
LoadModule mime_module modules/mod_mime.so
LoadModule dir_module modules/mod_dir.so
TypesConfig conf/mime.types
DocumentRoot "${normalize(docroot)}"
<Directory "${normalize(docroot)}">
  AllowOverride All
  Options FollowSymLinks
  Require all granted
</Directory>`;
    const configPath = path.join(temp, 'httpd.conf');
    fs.writeFileSync(configPath, config);
    const check = spawnSync(executable, ['-t', '-f', configPath], { windowsHide: true, encoding: 'utf8' });
    assert.equal(check.status, 0, check.stderr || String(check.error));
    child = spawn(executable, ['-X', '-f', configPath], { windowsHide: true, stdio: 'ignore' });
    const base = `http://127.0.0.1:${port}`;
    let ready = false;
    for (let attempt = 0; attempt < 60; attempt++) {
      try { await fetch(base + '/error.html'); ready = true; break; } catch {}
      await delay(100);
    }
    assert.ok(ready, 'Apache did not start');
    for (const [url, status] of [['/502', 502], ['/500', 500], ['/404', 404], ['/missing-page', 404], ['/missing.shtml', 404], ['/error.html', 200]]) {
      const response = await fetch(base + url);
      assert.equal(response.status, status, url);
      assert.ok((await response.text()).includes('Let’s find our way back.'), url + ' uses branded fallback');
    }
    const existing = await fetch(base + '/sample');
    assert.equal(existing.status, 200);
    assert.ok((await existing.text()).includes('Existing page'));
    fs.writeFileSync(path.join(docroot, 'maintenance.flag'), '');
    const maintenanceError = await fetch(base + '/502');
    assert.equal(maintenanceError.status, 502);
    assert.ok((await maintenanceError.text()).includes('Let’s find our way back.'));
    const logs = fs.readFileSync(path.join(temp, 'error.log'), 'utf8');
    assert.ok(!logs.includes('internal redirects'), 'No rewrite recursion');
    console.log('PASS: Apache syntax, branded 500/502/404, missing URLs, existing clean URLs, maintenance bypass and no rewrite loops.');
  } finally {
    if (child && child.exitCode === null) { const exited = new Promise(resolve => child.once('exit', resolve)); child.kill(); await exited; }
    fs.rmSync(temp, { recursive: true, force: true, maxRetries: 5, retryDelay: 100 });
  }
})().catch(error => { console.error(error); process.exitCode = 1; });
