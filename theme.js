/* Separate preferences keep the writing workspace independent of the public site. */
(() => {
  const root = document.documentElement;
  const scope = root.dataset.themeScope === 'admin' ? 'admin' : 'site';
  const key = `tr_theme_${scope}`;
  const defaultTheme = scope === 'admin' ? 'light' : 'dark';
  const valid = value => value === 'light' || value === 'dark';
  let theme = defaultTheme;
  try { const saved = localStorage.getItem(key); if (valid(saved)) theme = saved; } catch {}
  let button;
  function apply(value) {
    theme = valid(value) ? value : defaultTheme;
    root.dataset.theme = theme;
    root.style.colorScheme = theme;
    const meta = document.querySelector('meta[name="theme-color"]');
    if (meta) meta.content = theme === 'light' ? '#f7f4ee' : '#0e0e10';
    if (button) {
      const next = theme === 'light' ? 'dark' : 'light';
      button.setAttribute('aria-label', `Switch to ${next} mode`);
      button.setAttribute('aria-pressed', String(theme === 'dark'));
      button.title = `Switch to ${next} mode`;
      button.querySelector('.theme-toggle-icon').innerHTML = next === 'light'
        ? '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M2 12h2m16 0h2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"/></svg>'
        : '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M20.9 13.3A9 9 0 0 1 10.7 3.1a9 9 0 1 0 10.2 10.2Z"/></svg>';
      button.querySelector('.theme-toggle-label').textContent = `${next === 'light' ? 'Light' : 'Dark'} mode`;
    }
  }
  apply(theme); // Runs in the head before the page paints.
  document.addEventListener('DOMContentLoaded', () => {
    button = document.createElement('button');
    button.className = 'theme-toggle';
    button.type = 'button';
    const icon = document.createElement('span'); icon.className = 'theme-toggle-icon'; icon.setAttribute('aria-hidden', 'true');
    const label = document.createElement('span'); label.className = 'theme-toggle-label';
    button.append(icon, label);
    button.addEventListener('click', () => {
      apply(theme === 'light' ? 'dark' : 'light');
      try { localStorage.setItem(key, theme); } catch {}
    });
    const adminActions = document.querySelector('.topbar-actions');
    const nav = document.querySelector('.topnav');
    if (adminActions) adminActions.prepend(button);
    else if (nav) {
      const actions = document.createElement('div'); actions.className = 'theme-actions';
      actions.appendChild(button);
      const menu = nav.querySelector('.topnav-hamburger');
      if (menu) actions.appendChild(menu);
      nav.appendChild(actions);
    } else {
      button.classList.add('theme-toggle-floating');
      document.body.appendChild(button);
    }
    apply(theme);
  });
  window.addEventListener('storage', event => {
    if (event.key === key) apply(event.newValue);
    else if (event.key === null) apply(defaultTheme);
  });
})();
