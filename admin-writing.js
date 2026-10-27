const poemWritingField = document.getElementById('poemBody');
const grammarStatus = document.getElementById('grammarStatus');
const grammarResults = document.getElementById('grammarResults');
let grammarRequest = 0;
let grammarInFlight = false;

document.getElementById('previewPoemButton').addEventListener('click', () => {
  if (!poemWritingField.value.trim()) {
    showToast('Write a few words first, then preview your poem.');
    poemWritingField.focus();
    return;
  }
  // POST into a new tab: drafts never enter a public URL or the poems database.
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = 'preview_poem.php';
  form.target = '_blank';
  form.rel = 'noopener';
  form.hidden = true;
  for (const [name, value] of Object.entries({
    csrf_token: CSRF_TOKEN,
    title: document.getElementById('poemTitle').value,
    category: document.getElementById('poemCategory').value,
    body: poemWritingField.value
  })) {
    const input = document.createElement('input');
    input.type = 'hidden'; input.name = name; input.value = value;
    form.appendChild(input);
  }
  document.body.appendChild(form);
  form.submit();
  form.remove();
});

function invalidateGrammar() {
  grammarRequest++;
  if (grammarResults.childElementCount || grammarInFlight) grammarStatus.textContent = 'Your poem changed. Check again for fresh suggestions.';
  grammarResults.replaceChildren();
}
poemWritingField.addEventListener('input', invalidateGrammar);
document.getElementById('composeForm').addEventListener('reset', () => {
  invalidateGrammar();
  grammarStatus.textContent = '';
});
document.getElementById('grammarLanguage').addEventListener('change', event => {
  poemWritingField.lang = event.target.value;
  document.getElementById('poemTitle').lang = event.target.value;
  invalidateGrammar();
});
document.getElementById('spellcheckToggle').addEventListener('change', event => {
  for (const field of [poemWritingField, document.getElementById('poemTitle')]) field.spellcheck = event.target.checked;
});

document.getElementById('checkGrammarButton').addEventListener('click', async event => {
  const text = poemWritingField.value;
  if (!text.trim()) { grammarStatus.textContent = 'Write a few words first.'; return; }
  if (new TextEncoder().encode(text).length > 19000) {
    grammarStatus.textContent = 'This poem is too long for the free grammar check (19 KB maximum). Browser spellcheck is still available.';
    return;
  }
  const button = event.currentTarget;
  if (button.disabled) return;
  const version = ++grammarRequest;
  grammarInFlight = true;
  button.disabled = true;
  grammarResults.replaceChildren();
  grammarStatus.textContent = 'Checking with LanguageTool…';
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), 20000);
  try {
    // Explicit clicks only: no text is sent while the user is typing.
    const response = await fetch('https://api.languagetool.org/v2/check', {
      method: 'POST', signal: controller.signal,
      body: new URLSearchParams({ text, language: document.getElementById('grammarLanguage').value })
    });
    if (!response.ok) throw new Error(response.status === 429
      ? 'LanguageTool is busy. Please wait a minute before checking again.'
      : 'LanguageTool is unavailable. Please try again later.');
    const data = await response.json();
    if (version !== grammarRequest) return;
    if (poemWritingField.value !== text) {
      grammarStatus.textContent = 'Your poem changed during the check. Check again for fresh suggestions.';
      return;
    }
    if (!Array.isArray(data.matches)) throw new Error('Could not read the grammar suggestions.');
    grammarStatus.textContent = data.matches.length
      ? `${data.matches.length} suggestions. Poetry can bend the rules — use only what fits your voice.`
      : 'No issues found by LanguageTool. You can still review for rhythm and meaning.';
    for (const match of data.matches) {
      if (!Number.isInteger(match.offset) || !Number.isInteger(match.length) || match.offset < 0 || match.length < 0 || match.offset + match.length > text.length) continue;
      const card = document.createElement('div');
      card.className = 'grammar-suggestion';
      const message = document.createElement('p');
      message.textContent = match.message;
      const context = document.createElement('p');
      context.className = 'grammar-context';
      context.textContent = `…${text.slice(Math.max(0, match.offset - 30), match.offset + match.length + 30)}…`;
      const actions = document.createElement('div');
      actions.className = 'writing-toolbar';
      const locate = document.createElement('button');
      locate.type = 'button'; locate.className = 'btn-ghost'; locate.textContent = 'Show in poem';
      locate.addEventListener('click', () => {
        poemWritingField.focus();
        poemWritingField.setSelectionRange(match.offset, match.offset + match.length);
      });
      actions.appendChild(locate);
      for (const replacement of (match.replacements || []).slice(0, 4)) {
        if (typeof replacement.value !== 'string') continue;
        const apply = document.createElement('button');
        apply.type = 'button'; apply.className = 'btn-ghost';
        apply.textContent = replacement.value ? `Use “${replacement.value}”` : 'Remove';
        apply.addEventListener('click', () => {
          if (poemWritingField.value !== text) { invalidateGrammar(); return; }
          poemWritingField.focus();
          poemWritingField.setRangeText(replacement.value, match.offset, match.offset + match.length, 'select');
          poemWritingField.dispatchEvent(new Event('input', { bubbles: true }));
        });
        actions.appendChild(apply);
      }
      card.append(message, context, actions);
      grammarResults.appendChild(card);
    }
  } catch (error) {
    if (version === grammarRequest) grammarStatus.textContent = error.name === 'AbortError'
      ? 'The grammar check timed out. Please try again.'
      : (error instanceof TypeError ? 'Could not reach LanguageTool. Check your connection or use browser spellcheck.' : error.message);
  } finally {
    clearTimeout(timeout);
    grammarInFlight = false;
    button.disabled = false;
  }
});
