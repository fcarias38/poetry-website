// Dictionary checks stay in a worker so typing remains responsive.
(() => {
  const field = document.getElementById('poemBody');
  const toggle = document.getElementById('autoSpellcheckToggle');
  const language = document.getElementById('grammarLanguage');
  const status = document.getElementById('spellingStatus');
  const results = document.getElementById('spellingResults');
  const highlights = document.getElementById('spellingHighlights');
  const menu = document.getElementById('spellingMenu');
  let latestCheck;
  let menuTarget;
  let worker;
  let timer;
  let version = 0;
  let composing = false;
  status.textContent = 'Automatic spelling is on. Suggestions appear here after you pause typing.';

  function closeMenu() {
    if (menu) { menu.hidden = true; menu.replaceChildren(); }
    menuTarget = null;
  }

  function fillMenu(suggestions) {
    if (!menuTarget || menuTarget.version !== version || menuTarget.text !== field.value) { closeMenu(); return; }
    const target = menuTarget;
    menu.replaceChildren();
    const label = document.createElement('p');
    label.textContent = suggestions === null ? `Looking up “${target.word}”…` : `Corrections for “${target.word}”`;
    menu.appendChild(label);
    for (const replacement of suggestions || []) {
      const button = document.createElement('button');
      button.type = 'button'; button.setAttribute('role', 'menuitem'); button.textContent = replacement;
      button.addEventListener('click', () => {
        if (target.version !== version || target.text !== field.value) { closeMenu(); return; }
        closeMenu();
        field.focus();
        field.setRangeText(replacement, target.offset, target.offset + target.word.length, 'end');
        field.dispatchEvent(new Event('input', { bubbles: true }));
      });
      menu.appendChild(button);
    }
    if (suggestions && !suggestions.length) {
      const empty = document.createElement('p');
      empty.textContent = 'No suggested replacements.';
      menu.appendChild(empty);
    }
    const rect = menu.getBoundingClientRect();
    menu.style.left = `${Math.max(8, Math.min(target.x, innerWidth - rect.width - 8))}px`;
    menu.style.top = `${Math.max(8, Math.min(target.y, innerHeight - rect.height - 8))}px`;
    menu.querySelector('button')?.focus();
  }

  function openMenu(event, keyboard = false) {
    if (!menu || !highlights || !latestCheck || latestCheck.text !== field.value || !toggle.checked) return;
    syncHighlights();
    const marks = [...highlights.querySelectorAll('.spelling-error')];
    const mark = marks.find(element => keyboard
      ? field.selectionStart >= Number(element.dataset.offset) && field.selectionStart < Number(element.dataset.offset) + element.textContent.length
      : [...element.getClientRects()].some(rect => event.clientX >= rect.left && event.clientX <= rect.right && event.clientY >= rect.top && event.clientY <= rect.bottom));
    if (!mark) { closeMenu(); return; }
    event.preventDefault();
    const rect = mark.getBoundingClientRect();
    menuTarget = { version, text: field.value, word: mark.textContent, offset: Number(mark.dataset.offset), x: keyboard ? rect.left : event.clientX, y: keyboard ? rect.bottom : event.clientY };
    field.setSelectionRange(menuTarget.offset, menuTarget.offset + menuTarget.word.length);
    menu.hidden = false;
    const cached = latestCheck.matches.find(match => match.word === menuTarget.word);
    fillMenu(cached ? cached.suggestions : null);
    if (!cached) worker.postMessage({ type: 'suggest', version, text: field.value, language: language.value, offset: menuTarget.offset, word: menuTarget.word });
  }

  function syncHighlights() {
    if (!highlights) return;
    const style = getComputedStyle(field);
    for (const property of ['fontFamily', 'fontSize', 'fontWeight', 'fontStyle', 'fontVariant', 'lineHeight', 'letterSpacing', 'wordSpacing', 'textAlign', 'textIndent', 'textTransform', 'direction', 'tabSize', 'paddingTop', 'paddingRight', 'paddingBottom', 'paddingLeft', 'borderTopWidth', 'borderRightWidth', 'borderBottomWidth', 'borderLeftWidth']) {
      highlights.style[property] = style[property];
    }
    highlights.style.boxSizing = 'border-box';
    // clientWidth excludes the scrollbar, keeping wrapped lines aligned.
    highlights.style.width = `${field.clientWidth + parseFloat(style.borderLeftWidth) + parseFloat(style.borderRightWidth)}px`;
    highlights.style.height = `${field.offsetHeight}px`;
    highlights.scrollTop = field.scrollTop;
    highlights.scrollLeft = field.scrollLeft;
  }

  function underline(text, issues = []) {
    if (!highlights) return;
    highlights.replaceChildren();
    let offset = 0;
    for (const issue of issues) {
      if (issue.offset < offset || issue.offset + issue.word.length > text.length) continue;
      highlights.appendChild(document.createTextNode(text.slice(offset, issue.offset)));
      const mark = document.createElement('span');
      mark.className = 'spelling-error';
      mark.dataset.offset = issue.offset;
      mark.textContent = text.slice(issue.offset, issue.offset + issue.word.length);
      highlights.appendChild(mark);
      offset = issue.offset + issue.word.length;
    }
    highlights.appendChild(document.createTextNode(text.slice(offset) + '\n'));
    syncHighlights();
  }

  function invalidate() {
    clearTimeout(timer);
    closeMenu();
    latestCheck = null;
    version++;
    results.replaceChildren();
    underline(field.value);
    status.textContent = '';
  }

  function schedule() {
    invalidate();
    if (!toggle.checked || composing || !field.value.trim()) return;
    status.textContent = 'Waiting to check spelling…';
    timer = setTimeout(() => {
      try {
        if (!worker) {
          worker = new Worker('/spelling-worker.js?v=20261106-3');
          worker.onmessage = render;
          worker.onerror = () => {
            invalidate();
            status.textContent = 'Automatic spelling is unavailable. Try browser spellcheck or LanguageTool.';
            worker.terminate();
            worker = null;
          };
        }
        status.textContent = 'Checking spelling…';
        worker.postMessage({ version, text: field.value, language: language.value });
      } catch (error) {
        status.textContent = 'Automatic spelling is unavailable. Try browser spellcheck or LanguageTool.';
      }
    }, 800);
  }

  function render({ data }) {
    if (data.version !== version || data.text !== field.value || !toggle.checked) return;
    if (data.type === 'suggest') {
      if (menuTarget && menuTarget.offset === data.offset && menuTarget.word === data.word) fillMenu(data.suggestions || []);
      return;
    }
    results.replaceChildren();
    if (data.error) {
      status.textContent = 'Could not load the spelling dictionary. Try again or use LanguageTool.';
      return;
    }
    underline(data.text, data.issues || data.matches);
    latestCheck = data;
    status.textContent = data.matches.length
      ? 'Possible spelling issues (up to 8 shown). Choose only what fits your poem.'
      : 'No spelling issues found.';
    for (const match of data.matches) {
      const card = document.createElement('div');
      card.className = 'grammar-suggestion';
      const label = document.createElement('p');
      label.textContent = `Check “${match.word}”`;
      const actions = document.createElement('div');
      actions.className = 'writing-toolbar';
      const locate = document.createElement('button');
      locate.type = 'button'; locate.className = 'btn-ghost'; locate.textContent = 'Show in poem';
      locate.addEventListener('click', () => {
        if (data.text !== field.value) { schedule(); return; }
        field.focus();
        field.setSelectionRange(match.offset, match.offset + match.word.length);
      });
      actions.appendChild(locate);
      for (const replacement of match.suggestions) {
        const button = document.createElement('button');
        button.type = 'button'; button.className = 'btn-ghost'; button.textContent = `Use “${replacement}”`;
        button.addEventListener('click', () => {
          if (data.text !== field.value) { schedule(); return; }
          field.focus();
          field.setRangeText(replacement, match.offset, match.offset + match.word.length, 'select');
          field.dispatchEvent(new Event('input', { bubbles: true }));
        });
        actions.appendChild(button);
      }
      card.append(label, actions);
      results.appendChild(card);
    }
  }

  field.addEventListener('input', schedule);
  field.addEventListener('scroll', () => { closeMenu(); syncHighlights(); });
  if (menu) {
    field.addEventListener('contextmenu', openMenu);
    field.addEventListener('keydown', event => {
      if ((event.shiftKey && event.key === 'F10') || event.key === 'ContextMenu') openMenu(event, true);
    });
    document.addEventListener('pointerdown', event => { if (!menu.contains(event.target)) closeMenu(); });
    document.addEventListener('scroll', event => { if (!menu.contains(event.target)) closeMenu(); }, true);
    window.addEventListener('resize', closeMenu);
    menu.addEventListener('keydown', event => {
      if (event.key === 'Escape') { event.preventDefault(); closeMenu(); field.focus(); return; }
      if (event.key === 'Tab') { closeMenu(); return; }
      const buttons = [...menu.querySelectorAll('button')];
      if (!buttons.length || !['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) return;
      event.preventDefault();
      const current = buttons.indexOf(document.activeElement);
      const next = event.key === 'Home' ? 0 : event.key === 'End' ? buttons.length - 1 : (current + (event.key === 'ArrowUp' ? -1 : 1) + buttons.length) % buttons.length;
      buttons[next].focus();
    });
  }
  if (highlights && typeof ResizeObserver !== 'undefined') new ResizeObserver(syncHighlights).observe(field);
  field.addEventListener('compositionstart', () => { composing = true; invalidate(); });
  field.addEventListener('compositionend', () => { composing = false; schedule(); });
  toggle.addEventListener('change', schedule);
  language.addEventListener('change', schedule);
  document.getElementById('composeForm').addEventListener('reset', () => {
    invalidate();
    // Wait until the browser has restored the form defaults.
    timer = setTimeout(schedule, 0);
  });
})();
