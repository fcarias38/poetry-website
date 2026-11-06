const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const root = path.resolve(__dirname, '..');

class Element {
  constructor() { this.value = ''; this.checked = true; this.children = []; this.listeners = {}; }
  addEventListener(type, listener) { this.listeners[type] = listener; }
  dispatchEvent(event) { this.listeners[event.type]?.(event); }
  replaceChildren() { this.children = []; }
  append(...children) { this.children.push(...children); }
  appendChild(child) { this.append(child); }
  focus() {}
  setSelectionRange(start, end) { this.selection = [start, end]; }
  setRangeText(value, start, end) { this.value = this.value.slice(0, start) + value + this.value.slice(end); }
}

(async () => {
  // Exercise the real worker and dictionaries without making network requests.
  const replies = [];
  const workerContext = {
    importScripts: () => {}, Typo: require('../vendor/spelling/typo.js'),
    self: { postMessage: data => replies.push(data) },
    fetch: async file => ({ ok: true, text: async () => fs.readFileSync(path.join(root, file), 'utf8') })
  };
  vm.runInNewContext(fs.readFileSync(path.join(root, 'spelling-worker.js'), 'utf8'), workerContext);
  for (const language of ['en-US', 'en-GB']) {
    await workerContext.self.onmessage({ data: { version: 1, language, text: 'The beautifull moon\nshines tonight.' } });
    const result = replies.pop();
    assert.equal(result.error, undefined);
    assert.equal(result.matches.length, 1);
    assert.equal(result.matches[0].word, 'beautifull');
    assert.ok(result.matches[0].suggestions.includes('beautiful'));
    await workerContext.self.onmessage({ data: { version: 2, language, text: 'The colour of the night' } });
    const regional = replies.pop();
    assert.equal(regional.matches.some(match => match.word === 'colour'), language === 'en-US');
  }

  await workerContext.self.onmessage({ data: { version: 3, language: 'en-US', text: 'Hello wrld.\nAnother wrld.' } });
  const repeated = replies.pop();
  assert.equal(repeated.matches[0].suggestions[0], 'world');
  assert.equal(repeated.matches.length, 1, 'Repeated words share a correction card');
  assert.deepEqual(Array.from(repeated.issues, issue => issue.offset), [6, 20], 'Every occurrence gets an underline');
  await workerContext.self.onmessage({ data: { type: 'suggest', version: 4, language: 'en-US', text: 'Hello wrld.', word: 'wrld', offset: 6 } });
  const onDemand = replies.pop();
  assert.equal(onDemand.type, 'suggest');
  assert.equal(onDemand.suggestions[0], 'world', 'Right-click requests can retrieve corrections beyond the first eight cards');

  const fields = Object.fromEntries(['poemBody', 'autoSpellcheckToggle', 'grammarLanguage', 'spellingStatus', 'spellingResults', 'composeForm'].map(id => [id, new Element()]));
  const timers = new Map();
  const requests = [];
  let timerId = 0;
  let worker;
  const context = {
    document: { getElementById: id => fields[id], createElement: () => new Element() },
    Event: class { constructor(type) { this.type = type; } },
    setTimeout: callback => { timers.set(++timerId, callback); return timerId; },
    clearTimeout: id => timers.delete(id),
    Worker: class { constructor() { worker = this; } postMessage(data) { requests.push(data); } terminate() {} }
  };
  const flush = () => { const pending = [...timers.values()]; timers.clear(); pending.forEach(callback => callback()); };
  const input = () => fields.poemBody.dispatchEvent({ type: 'input' });
  vm.runInNewContext(fs.readFileSync(path.join(root, 'admin-spelling.js'), 'utf8'), context);
  fields.grammarLanguage.value = 'en-US';
  fields.poemBody.value = 'The beautifull moon\nshines tonight.';
  input(); input();
  assert.equal(requests.length, 0);
  assert.equal(timers.size, 1, 'Typing is debounced');
  flush();
  assert.equal(requests.length, 1, 'A pause starts an automatic check');
  const response = { ...requests[0], matches: [{ word: 'beautifull', offset: 4, suggestions: ['beautiful'] }] };
  worker.onmessage({ data: response });
  fields.spellingResults.children[0].children[1].children[1].dispatchEvent({ type: 'click' });
  assert.equal(fields.poemBody.value, 'The beautiful moon\nshines tonight.', 'Only selected correction changes text and preserves line breaks');
  worker.onmessage({ data: response });
  assert.equal(fields.spellingResults.children.length, 0, 'Stale results are discarded');
  fields.grammarLanguage.value = 'en-GB';
  fields.grammarLanguage.dispatchEvent({ type: 'change' });
  flush();
  assert.equal(requests.at(-1).language, 'en-GB');
  fields.autoSpellcheckToggle.checked = false;
  fields.autoSpellcheckToggle.dispatchEvent({ type: 'change' });
  input(); flush();
  assert.equal(requests.length, 2, 'Disabled checks do not run');
  fields.autoSpellcheckToggle.checked = true;
  fields.poemBody.dispatchEvent({ type: 'compositionstart' });
  input(); flush();
  assert.equal(requests.length, 2, 'Composition is not interrupted');
  fields.poemBody.dispatchEvent({ type: 'compositionend' });
  flush();
  assert.equal(requests.length, 3);
  fields.poemBody.value = '';
  fields.composeForm.dispatchEvent({ type: 'reset' });
  flush(); flush();
  assert.equal(requests.length, 3, 'Reset clears pending checks');
  console.log('PASS: real local US/UK dictionaries, automatic checks, debounce, optional corrections, stale response safety, language switching, toggle, composition and reset.');
})().catch(error => { console.error(error); process.exitCode = 1; });
