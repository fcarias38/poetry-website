const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const source = fs.readFileSync(path.join(__dirname, '..', 'theme.js'), 'utf8');
function setup(scope, saved = {}, blocked = false) {
  const store = new Map(Object.entries(saved));
  const listeners = {}, docListeners = {};
  const root = { dataset: { themeScope: scope }, style: {} };
  const element = () => ({ children: [], attributes: {}, classList: { add() {} }, append(...children) { this.children.push(...children); }, appendChild(child) { this.append(child); }, setAttribute(key, value) { this.attributes[key] = value; }, addEventListener(key, cb) { this[key] = cb; }, querySelector(selector) { return this.children.find(child => '.' + child.className === selector); } });
  const body = element();
  vm.runInNewContext(source, {
    document: { documentElement: root, body, createElement: element, querySelector: () => null, addEventListener: (key, cb) => { docListeners[key] = cb; } },
    localStorage: { getItem: key => { if (blocked) throw Error('Blocked'); return store.get(key); }, setItem: (key, value) => { if (blocked) throw Error('Blocked'); store.set(key, value); } },
    window: { addEventListener: (key, cb) => { listeners[key] = cb; } }
  });
  const initial = root.dataset.theme;
  docListeners.DOMContentLoaded();
  return { root, store, initial, button: body.children[0], storage: listeners.storage };
}
let admin = setup('admin');
assert.equal(admin.initial, 'light', 'Admin starts light before DOMContentLoaded');
admin.button.click();
assert.equal(admin.root.dataset.theme, 'dark');
assert.equal(admin.store.get('tr_theme_admin'), 'dark');
assert.equal(admin.store.has('tr_theme_site'), false);
assert.equal(admin.button.attributes['aria-label'], 'Switch to light mode');
assert.equal(setup('admin', Object.fromEntries(admin.store)).initial, 'dark');
let site = setup('site', Object.fromEntries(admin.store));
assert.equal(site.initial, 'dark', 'Public default is independent of admin');
site.button.click();
assert.equal(site.store.get('tr_theme_site'), 'light');
assert.equal(setup('site', Object.fromEntries(site.store)).initial, 'light');
admin.storage({ key: 'tr_theme_site', newValue: 'light' });
assert.equal(admin.root.dataset.theme, 'dark', 'Other scope storage events are ignored');
admin.storage({ key: 'tr_theme_admin', newValue: 'light' });
assert.equal(admin.root.dataset.theme, 'light');
site.storage({ key: null });
assert.equal(site.root.dataset.theme, 'dark', 'Cleared preferences restore the scope default');
assert.equal(setup('site', { tr_theme_site: 'invalid' }).initial, 'dark');
admin = setup('admin', {}, true);
admin.button.click();
assert.equal(admin.root.dataset.theme, 'dark', 'Toggle still works if storage is blocked');
console.log('PASS: separate defaults, persistence, early application, independent scopes, cross-tab sync, accessibility and blocked-storage fallback.');
