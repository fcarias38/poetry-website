const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

class Element {
  constructor(tag = 'div') { this.tag = tag; this.value = ''; this.textContent = ''; this.children = []; this.listeners = {}; this.disabled = false; }
  addEventListener(name, listener) { this.listeners[name] = listener; }
  dispatchEvent(event) { return this.listeners[event.type]?.({ ...event, target: this, currentTarget: this }); }
  append(...children) { this.children.push(...children); }
  appendChild(child) { this.append(child); }
  replaceChildren(...children) { this.children = children; }
  get childElementCount() { return this.children.length; }
  focus() { this.focused = true; }
  setRangeText(value, start, end) { this.value = this.value.slice(0, start) + value + this.value.slice(end); }
  setSelectionRange(start, end) { this.selection = [start, end]; }
  remove() {}
  submit() { submitted.push(this); }
}
const fields = Object.fromEntries(['poemBody', 'poemTitle', 'poemCategory', 'grammarStatus', 'grammarResults', 'previewPoemButton', 'composeForm', 'grammarLanguage', 'spellcheckToggle', 'checkGrammarButton'].map(id => [id, new Element()]));
const requests = [], submitted = [], toasts = [];
let mockResponse = async () => ({ ok: true, json: async () => ({ matches: [{ offset: 2, length: 3, message: 'Consider subject–verb agreement.', replacements: [{ value: 'have' }] }] }) });
const context = {
  document: { getElementById: id => fields[id], createElement: tag => new Element(tag), body: new Element('body') },
  CSRF_TOKEN: 'test-token', showToast: message => toasts.push(message),
  Event: class { constructor(type) { this.type = type; } },
  URLSearchParams, TextEncoder, AbortController, setTimeout, clearTimeout,
  fetch: async (url, options) => { requests.push({ url, options }); return mockResponse(); }
};
vm.runInNewContext(fs.readFileSync(path.join(__dirname, '..', 'admin-writing.js'), 'utf8'), context);
const click = field => fields[field].dispatchEvent({ type: 'click' });
const input = () => fields.poemBody.dispatchEvent({ type: 'input' });

(async () => {
  fields.grammarLanguage.value = 'en-US';
  fields.poemTitle.value = 'New poem';
  fields.poemCategory.value = 'Love';
  fields.poemBody.value = 'I has a dream.\nAnd it goes on.';
  input();
  assert.equal(requests.length, 0, 'Typing never sends text to a service');
  click('previewPoemButton');
  assert.equal(requests.length, 0, 'Preview never calls the publishing API');
  assert.equal(submitted.length, 1);
  const form = submitted[0];
  assert.equal(form.method, 'POST');
  assert.equal(form.action, 'preview_poem.php');
  assert.equal(form.target, '_blank');
  assert.equal(Object.fromEntries(form.children.map(field => [field.name, field.value])).body, fields.poemBody.value);
  await click('checkGrammarButton');
  assert.equal(requests.length, 1);
  assert.equal(requests[0].url, 'https://api.languagetool.org/v2/check');
  assert.equal(requests[0].options.body.get('text'), fields.poemBody.value);
  assert.equal(fields.poemBody.value, 'I has a dream.\nAnd it goes on.', 'Suggestions do not change the poem');
  const suggestion = fields.grammarResults.children[0];
  const actions = suggestion.children[2];
  actions.children[0].dispatchEvent({ type: 'click' });
  assert.deepEqual(fields.poemBody.selection, [2, 5]);
  actions.children[1].dispatchEvent({ type: 'click' });
  assert.equal(fields.poemBody.value, 'I have a dream.\nAnd it goes on.');
  assert.equal(fields.grammarResults.childElementCount, 0, 'Changed offsets invalidate old suggestions');
  let resolve;
  mockResponse = () => new Promise(done => { resolve = done; });
  const checking = click('checkGrammarButton');
  fields.poemBody.value = 'A different poem';
  input();
  resolve({ ok: true, json: async () => ({ matches: [{ offset: 2, length: 3, message: 'Stale', replacements: [{ value: 'have' }] }] }) });
  await checking;
  assert.equal(fields.grammarResults.childElementCount, 0, 'Responses for old text are discarded');
  const before = requests.length;
  fields.poemBody.value = 'x'.repeat(19001);
  await click('checkGrammarButton');
  assert.equal(requests.length, before, 'Over-limit text is never sent');
  fields.poemBody.value = '   ';
  click('previewPoemButton');
  assert.equal(submitted.length, 1, 'Empty drafts are not submitted');
  fields.spellcheckToggle.checked = false;
  fields.spellcheckToggle.dispatchEvent({ type: 'change' });
  assert.equal(fields.poemBody.spellcheck, false);
  console.log('PASS: private POST preview, spelling toggle, grammar only on click, optional replacements, preserved line breaks, stale-response safety and size limits.');
})().catch(error => { console.error(error); process.exitCode = 1; });
