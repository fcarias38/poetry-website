// Headless visual smoke check with mock data; no live account or database access.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const os = require('node:os');
const http = require('node:http');
const { spawn } = require('node:child_process');
const { setTimeout: delay } = require('node:timers/promises');
const root = path.resolve(__dirname, '..');
const chrome = process.env.TEST_CHROME || 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const temp = fs.mkdtempSync(path.join(os.tmpdir(), 'think-room-browser-'));
const categories = ['God', 'Love', 'Heartbreak'].map((name, i) => ({ id: String(i + 1), name, poem_count: i + 1 }));
const poems = categories.map((category, i) => ({ id: i + 1, title: ['A quiet prayer', 'Where the light falls', 'What remains'][i], category: category.name, body: 'A line of poetry,\nand a moment to breathe.', slug: 'sample-' + i, archived: 0, views: 40, likes: 8, comments: 2, comment_count: 2, shares: 3, engagement: 86 }));
const footerMarkup = name => fs.readFileSync(path.join(root, name), 'utf8').match(/<footer\b[^>]*>[\s\S]*?<\/footer>/)[0].replace(/\s+/g, ' ').trim();
for (const name of ['author.php', 'contact.php', 'poem.php', 'index.shtml', 'maintenance.php']) {
  assert.equal(footerMarkup(name), footerMarkup('home.shtml'), name + ' must share the gallery footer');
  assert.ok(!footerMarkup(name).includes('Back to'), 'Footer must not include a back link');
}
const server = http.createServer((request, response) => {
  const pathname = new URL(request.url, 'http://localhost').pathname;
  const approvedComments = [{ name: '<img src=x onerror=alert(1)>', comment: '<script>unsafe</script>', created_at: '2026-10-27 10:00:00' }, { name: 'Reader', comment: 'Beautiful poem.', created_at: '2026-10-27 09:00:00' }];
  if (pathname === '/get_likes_comments.php') {
    const empty = new URL(request.url, 'http://localhost').searchParams.get('id') === '3';
    response.setHeader('Content-Type', 'application/json'); response.end(JSON.stringify({ likes: 8, comments: empty ? [] : approvedComments })); return;
  }
  if (['/update_likes.php', '/save_comment.php', '/track_view.php', '/track_share.php'].includes(pathname)) {
    let raw = ''; request.on('data', data => { raw += data; }); request.on('end', () => {
      const body = JSON.parse(raw || '{}');
      response.setHeader('Content-Type', 'application/json');
      if (!Number.isInteger(body.id)) { response.writeHead(400); response.end(JSON.stringify({ error: 'Use the poem ID' })); return; }
      if ((pathname === '/update_likes.php' && body.id === 2) || (pathname === '/save_comment.php' && body.comment === 'fail')) {
        response.writeHead(503); response.end(JSON.stringify({ error: 'Please try again later.' })); return;
      }
      response.end(JSON.stringify(pathname === '/update_likes.php' ? { likes: 9 } : { success: true, pending: true, comments: body.id === 3 ? [] : approvedComments }));
    }); return;
  }
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
        if(toggle.textContent.trim()) throw Error('Theme button must be icon-only');
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
  } else if (pathname === '/' || pathname === '/author.php' || pathname === '/contact.php') {
    content = content.toString().replace('<?= json_encode($supportToken) ?>', '"test-support-token"').replace(/<\?php if \(\$notice\): \?>[\s\S]*?<\?php endif; \?>/g, '').replace(/<\?(?:php|=)[\s\S]*?\?>/g, '').replace('</body>', `<script>document.addEventListener('DOMContentLoaded', async () => {
      try {
        if(document.documentElement.dataset.theme !== 'dark') throw Error('Public default');
        document.querySelector('.theme-toggle').click();
        if(document.documentElement.dataset.theme !== 'light' || localStorage.getItem('tr_theme_site') !== 'light') throw Error('Public light toggle');
        if (typeof loadPoems === 'function') {
          await loadPoems();
          if(document.querySelectorAll('.poem-card').length !== 3) throw Error('Public gallery cards');
          if(document.querySelectorAll('#filterBar button').length !== 4) throw Error('Public category filters');
          if(document.querySelector('.card-likes').textContent !== '8') throw Error('Gallery likes missing');
          if(document.querySelector('.card-comments').textContent !== '2') throw Error('Gallery comments missing');
          const cards = [...document.querySelectorAll('.poem-card')];
          const card = cards[0], initialUrl = location.href;
          if(card.tagName !== 'ARTICLE' || card.querySelector('a button')) throw Error('Card links must not contain buttons');
          if(!card.querySelector('.card-read').href.endsWith('/poems/sample-0')) throw Error('Read must open full poem');
          for(const button of card.querySelectorAll('.card-action')) if(button.getBoundingClientRect().height < 44) throw Error('Card action touch target');
          const readRect = card.querySelector('.card-read').getBoundingClientRect();
          for(const button of card.querySelectorAll('.card-action')) {
            const rect = button.getBoundingClientRect();
            if(rect.left < readRect.right || Math.abs(rect.top - readRect.top) > 2) throw Error('Card actions must stay beside Read');
          }
          await GalleryActions.like(card);
          if(card.querySelector('.card-likes').textContent !== '9' || !card.querySelector('.card-like').disabled) throw Error('Card like state');
          if(!card.querySelector('.bi-hand-thumbs-up-fill')) throw Error('Filled like icon');
          if(location.href !== initialUrl) throw Error('Like navigated away');
          await GalleryActions.like(cards[1]);
          if(cards[1].querySelector('.card-like').disabled || !cards[1].querySelector('.card-status').textContent) throw Error('Like failure recovery');
          card.querySelector('.card-comment').click();
          await new Promise(resolve => setTimeout(resolve, 150));
          if(!document.getElementById('readerOverlay').classList.contains('open')) throw Error('Comment panel missing');
          if(document.getElementById('readerBody').textContent !== card._poem.body) throw Error('Comment panel poem missing: ' + JSON.stringify(document.getElementById('readerBody').textContent));
          if(document.getElementById('likeCount').textContent !== '9') throw Error('Reader like count not synchronized');
          if(document.activeElement.id !== 'commentInput') throw Error('Comment input focus');
          if(document.querySelector('#commentsList img, #commentsList script')) throw Error('Comment text must not execute HTML');
          if(document.getElementById('readerComments').hidden) throw Error('Existing comments must remain visible');
          const comment = document.getElementById('commentInput');
          comment.value = 'fail'; await GalleryActions.postComment();
          if(comment.value !== 'fail' || !document.getElementById('readerFeedback').textContent.includes('try again')) throw Error('Failed comment lost draft');
          comment.value = 'A thoughtful poem.'; await GalleryActions.postComment();
          if(comment.value || !document.getElementById('readerFeedback').textContent.includes('awaiting approval')) throw Error('Comment submission');
          if(card.querySelector('.card-comments').textContent !== '2') throw Error('Pending comments must not increase approved count');
          document.dispatchEvent(new KeyboardEvent('keydown', { key:'Escape', bubbles:true }));
          if(document.getElementById('readerOverlay').classList.contains('open') || location.href !== initialUrl) throw Error('Close must return to gallery');
          if(document.activeElement !== card.querySelector('.card-comment')) throw Error('Return focus after comment');
          openPoem(cards[2]); await new Promise(resolve => setTimeout(resolve, 150));
          const emptyThread = document.getElementById('readerComments');
          if(!emptyThread.hidden || emptyThread.getBoundingClientRect().height !== 0) throw Error('Empty comments must occupy no space');
          if(document.getElementById('commentsList').childElementCount) throw Error('No empty comment placeholder');
          if(getComputedStyle(document.getElementById('commentForm')).display !== 'none') throw Error('Empty comment form opens only on request');
          openCommentForm();
          if(getComputedStyle(document.getElementById('commentForm')).display === 'none') throw Error('First comment form unavailable');
          document.getElementById('commentInput').value = 'First comment'; await GalleryActions.postComment();
          if(!emptyThread.hidden) throw Error('Pending first comment must not show empty thread');
          closeReader();
          Object.defineProperty(navigator, 'share', { configurable:true, value:undefined });
          Object.defineProperty(navigator, 'clipboard', { configurable:true, value:{ writeText:async value => { window.testCopiedLink = value; } } });
          card.querySelector('.card-share').click(); await new Promise(resolve => setTimeout(resolve, 100));
          if(!window.testCopiedLink.endsWith('/poems/sample-0') || !card.querySelector('.card-status').textContent.includes('copied')) throw Error('Copy share fallback');
          Object.defineProperty(navigator, 'share', { configurable:true, value:async data => { window.testNativeShare = data; } });
          card.querySelector('.card-share').click(); await new Promise(resolve => setTimeout(resolve, 100));
          if(!window.testNativeShare.url.endsWith('/poems/sample-0')) throw Error('Native share URL');
          Object.defineProperty(navigator, 'share', { configurable:true, value:async () => { throw new DOMException('Cancelled', 'AbortError'); } });
          card.querySelector('.card-share').click(); await new Promise(resolve => setTimeout(resolve, 100));
          if(card.querySelector('.card-status').textContent) throw Error('Share cancellation must be silent');
          Object.defineProperty(navigator, 'share', { configurable:true, value:undefined });
          Object.defineProperty(navigator, 'clipboard', { configurable:true, value:{ writeText:async () => { throw Error('Permission denied'); } } });
          document.execCommand = () => false;
          card.querySelector('.card-share').click(); await new Promise(resolve => setTimeout(resolve, 100));
          if(!card.querySelector('.share-copy-field')?.value.endsWith('/poems/sample-0')) throw Error('Manual link fallback');
          card.querySelector('.share-copy-field').remove();
          if(location.href !== initialUrl) throw Error('Share navigated away');
        }
        await new Promise(resolve => setTimeout(resolve, 200));
        const icon = document.querySelector('.theme-toggle-icon svg');
        if(!icon || Math.abs(icon.getBoundingClientRect().width - 19.2) > 1) throw Error('Theme icon sizing');
        if(document.querySelector('.theme-toggle').textContent.trim()) throw Error('Theme button must be icon-only');
        const footerStyle = getComputedStyle(document.querySelector('.page-footer'));
        if(footerStyle.textAlign !== 'center' || footerStyle.justifyContent !== 'center') throw Error('Footer alignment');
        if(innerWidth <= 640) {
          const menuRect = document.getElementById('hamburger').getBoundingClientRect();
          const themeRect = document.querySelector('.theme-toggle').getBoundingClientRect();
          if(menuRect.width < 48 || menuRect.height < 44) throw Error('Menu touch target');
          if(themeRect.width < 44 || themeRect.height < 44) throw Error('Theme touch target');
          if(menuRect.left - themeRect.right < 16) throw Error('Menu and theme need separate touch areas');
          document.getElementById('hamburger').click();
          const topics = document.querySelector('.mobile-topics');
          if(topics.open) throw Error('Topics must start collapsed');
          topics.querySelector('summary').click();
          if(!topics.open) throw Error('Topics do not expand');
          const links = [...document.querySelectorAll('#mobileTopics a')];
          if(links.length !== 4) throw Error('Mobile topic links');
          for(let i=0; i<links.length; i++) {
            const rect = links[i].getBoundingClientRect();
            if(rect.height < 44) throw Error('Mobile link touch target');
            if(i && rect.top < links[i-1].getBoundingClientRect().bottom) throw Error('Overlapping topics');
          }
        }
        if(document.documentElement.scrollWidth > innerWidth) throw Error('Public page overflow');
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
    for (const [label, url, width] of [['admin-desktop', '/dashboard.php', 1440], ['admin-mobile', '/dashboard.php', 520], ['public-light', '/', 1440], ['gallery-mobile', '/', 390], ['gallery-small-phone', '/', 320], ['author-desktop', '/author.php', 1440], ['author-mobile', '/author.php', 390], ['contact-desktop', '/contact.php', 1440], ['contact-mobile', '/contact.php', 390]]) {
      // CDP emulation avoids Chrome's minimum desktop window width on Windows.
      const child = spawn(chrome, ['--headless', '--disable-gpu', '--no-first-run', '--no-default-browser-check', '--remote-debugging-pipe', `--user-data-dir=${path.join(temp, label)}`], { windowsHide: true, stdio: ['ignore', 'ignore', 'ignore', 'pipe', 'pipe'] });
      let sequence = 0, buffer = '';
      const pending = new Map();
      child.stdio[4].on('data', data => {
        buffer += data.toString();
        let end;
        while ((end = buffer.indexOf('\0')) >= 0) {
          const message = JSON.parse(buffer.slice(0, end)); buffer = buffer.slice(end + 1);
          if (pending.has(message.id)) {
            const request = pending.get(message.id); pending.delete(message.id);
            clearTimeout(request.timer);
            if (message.error) request.reject(Error(message.error.message)); else request.resolve(message.result);
          }
        }
      });
      const command = (method, params = {}, sessionId) => new Promise((resolve, reject) => {
        const id = ++sequence;
        const timer = setTimeout(() => { pending.delete(id); reject(Error('CDP timeout: ' + method)); }, 15000);
        pending.set(id, { resolve, reject, timer });
        child.stdio[3].write(JSON.stringify({ id, method, params, ...(sessionId ? { sessionId } : {}) }) + '\0');
      });
      try {
        const { targetId } = await command('Target.createTarget', { url: 'about:blank' });
        const { sessionId } = await command('Target.attachToTarget', { targetId, flatten: true });
        await command('Emulation.setDeviceMetricsOverride', { width, height: 1000, deviceScaleFactor: 1, mobile: width <= 640 }, sessionId);
        await command('Page.navigate', { url: `http://127.0.0.1:${port}${url}` }, sessionId);
        let result;
        for (let attempt = 0; attempt < 100; attempt++) {
          result = await command('Runtime.evaluate', { expression: 'document.documentElement.dataset.browserCheck', returnByValue: true }, sessionId);
          if (result.result.value) break;
          await delay(100);
        }
        assert.equal(result.result.value, 'PASS', label + ': browser checks');
        const viewport = await command('Runtime.evaluate', { expression: 'innerWidth', returnByValue: true }, sessionId);
        assert.equal(viewport.result.value, width, 'Actual viewport must match requested width');
        const screenshot = await command('Page.captureScreenshot', { format: 'png' }, sessionId);
        fs.writeFileSync(path.join(temp, label + '.png'), Buffer.from(screenshot.data, 'base64'));
        console.log(`PASS: ${label} (${path.join(temp, label + '.png')})`);
      } finally {
        for (const request of pending.values()) clearTimeout(request.timer);
        child.kill();
      }
    }
  } finally {
    await new Promise(resolve => server.close(resolve));
  }
})().catch(error => { console.error(error); process.exitCode = 1; });
