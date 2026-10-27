// Read-only live browser check. Writes diagnostic HTML/screenshots only to TEMP.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const os = require('node:os');
const { spawn } = require('node:child_process');
const temp = fs.mkdtempSync(path.join(os.tmpdir(), 'think-room-live-'));
const chrome = process.env.TEST_CHROME || 'C:/Program Files/Google/Chrome/Application/chrome.exe';
(async () => {
  for (const [label, url] of [['home', 'https://freddycarias.com/'], ['gallery', 'https://freddycarias.com/gallery']]) {
    const child = spawn(chrome, ['--headless', '--disable-gpu', '--no-first-run', '--no-default-browser-check', `--user-data-dir=${path.join(temp, label)}`, '--window-size=1440,1000', '--virtual-time-budget=10000', '--dump-dom', `--screenshot=${path.join(temp, label + '.png')}`, url], { windowsHide: true });
    let html = '', stderr = '';
    child.stdout.on('data', data => { html += data; });
    child.stderr.on('data', data => { stderr += data; });
    const status = await new Promise((resolve, reject) => { child.once('error', reject); child.once('exit', resolve); });
    fs.writeFileSync(path.join(temp, label + '.html'), html);
    assert.equal(status, 0, stderr);
    const markup = html.replace(/<script\b[^>]*>[\s\S]*?<\/script>/gi, '');
    const cards = [...markup.matchAll(/<article\b[^>]*class="poem-card[^>]*>/g)].length;
    const title = html.match(/<title>(.*?)<\/title>/s)?.[1];
    console.log(`${url}: ${title}; rendered cards=${cards}; screenshot=${path.join(temp, label + '.png')}`);
    assert.ok(cards > 0, `Live ${label} did not render poems. Inspect ${temp}`);
    for (const action of ['like', 'comment', 'share']) {
      assert.equal([...markup.matchAll(new RegExp('class="card-action card-' + action + '(?: |")', 'g'))].length, cards, `Every live card has a ${action} button`);
    }
    assert.ok(!html.includes('<title>Internal Server Error</title>'));
  }
})().catch(error => { console.error(error); process.exitCode = 1; });
