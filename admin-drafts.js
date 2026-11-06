(() => {
  const form = document.getElementById('composeForm');
  const draftId = document.getElementById('editDraftId');
  const save = document.getElementById('saveDraftButton');
  const publish = document.getElementById('publishPoemButton');
  const status = document.getElementById('draftSaveStatus');
  const list = document.getElementById('draftList');
  const search = document.getElementById('draftSearch');
  let drafts = [];
  let loadVersion = 0;
  let formGeneration = 0;
  let editVersion = 0;
  form.addEventListener('reset', () => { formGeneration++; });

  async function request(url, options = {}) {
    const response = await fetch(url, { cache: 'no-store', ...options });
    const data = await response.json();
    if (!response.ok || !data.success) throw new Error(data.error || 'Could not load or save the draft.');
    return data;
  }

  function render() {
    list.replaceChildren();
    const query = search.value.trim().toLowerCase();
    const matches = drafts.filter(draft => `${draft.title} ${draft.category}`.toLowerCase().includes(query));
    if (!matches.length) {
      const empty = document.createElement('p');
      empty.className = 'category-help';
      empty.textContent = drafts.length ? 'No matching drafts.' : 'No drafts yet. Choose Save draft while writing a poem.';
      list.appendChild(empty);
    }
    for (const draft of matches) {
      const row = document.createElement('div');
      row.className = 'poem-row';
      const title = document.createElement('span');
      title.className = 'poem-row-title'; title.textContent = draft.title.trim() || 'Untitled draft';
      const category = document.createElement('span');
      category.className = 'poem-row-cat'; category.textContent = draft.category;
      const actions = document.createElement('div');
      actions.className = 'poem-row-actions';
      const open = document.createElement('button');
      open.type = 'button'; open.className = 'btn-ghost'; open.textContent = 'Continue writing';
      open.addEventListener('click', async () => {
        open.disabled = true;
        try {
          const { draft: saved } = await request(`admin_drafts.php?id=${Number(draft.id)}`);
          clearCompose();
          draftId.value = saved.id;
          document.getElementById('editPoemId').value = saved.source_poem_id || '';
          for (const [id, key] of Object.entries({ poemTitle: 'title', poemCategory: 'category', poemBody: 'body', metaDescription: 'meta_description', metaKeywords: 'meta_keywords' })) {
            document.getElementById(id).value = saved[key] || '';
          }
          document.getElementById('poemBody').dispatchEvent(new Event('input', { bubbles: true }));
          document.getElementById('composeHeading').textContent = 'Edit draft';
          status.textContent = 'Saved privately. Publish poem when you are ready.';
          updateSeoPreview();
          showPanel('compose');
        } catch (error) { showToast(error.message); }
        finally { open.disabled = false; }
      });
      const remove = document.createElement('button');
      remove.type = 'button'; remove.className = 'btn-ghost'; remove.textContent = 'Delete draft';
      remove.addEventListener('click', async () => {
        if (!confirm('Delete this draft? This cannot be undone.')) return;
        remove.disabled = true;
        try {
          const body = new URLSearchParams({ action: 'delete', draft_id: draft.id });
          await request('admin_drafts.php', { method: 'POST', headers: { 'X-CSRF-Token': CSRF_TOKEN }, body });
          if (String(draftId.value) === String(draft.id)) clearCompose();
          drafts = drafts.filter(item => item.id !== draft.id);
          render();
          showToast('Draft deleted.');
        } catch (error) { showToast(error.message); remove.disabled = false; }
      });
      actions.append(open, remove);
      row.append(title, category, actions);
      list.appendChild(row);
    }
  }

  async function load() {
    const version = ++loadVersion;
    list.textContent = 'Loading drafts…';
    try {
      const data = await request('admin_drafts.php');
      if (version !== loadVersion) return;
      drafts = data.drafts;
      render();
    } catch (error) { if (version === loadVersion) list.textContent = error.message; }
  }

  save.addEventListener('click', async () => {
    if (save.disabled || publish.disabled) return;
    const body = new FormData(form);
    const generation = formGeneration;
    const editing = editVersion;
    save.disabled = true; publish.disabled = true;
    status.textContent = 'Saving draft…';
    try {
      const data = await request('admin_drafts.php', { method: 'POST', headers: { 'X-CSRF-Token': CSRF_TOKEN }, body });
      if (generation !== formGeneration || String(body.get('id') || '') !== document.getElementById('editPoemId').value) {
        showToast('Draft saved. Find it in Drafts.');
        return;
      }
      draftId.value = data.id;
      document.getElementById('composeHeading').textContent = 'Edit draft';
      document.getElementById('topbarTitle').textContent = 'Edit Draft';
      status.textContent = 'Draft saved privately. Find it in Drafts whenever you want to continue.';
      if (editing !== editVersion) status.textContent = 'Draft saved. You have newer unsaved changes; choose Save draft again to keep them.';
      showToast('Draft saved.');
    } catch (error) { status.textContent = error.message; }
    finally { save.disabled = false; publish.disabled = false; }
  });
  search.addEventListener('input', render);
  document.addEventListener('dashboard:panel', event => { if (event.detail.id === 'drafts') load(); });
  form.addEventListener('input', () => {
    editVersion++;
    if (draftId.value && !save.disabled) status.textContent = 'Unsaved changes. Choose Save draft to keep them.';
  });
})();
