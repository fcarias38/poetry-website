/* Category management uses textContent for all category names. */
let managedCategories = [];
let deletingCategory = null;
let categoryLoadVersion = 0;

async function loadCategories() {
  const version = ++categoryLoadVersion;
  const status = document.getElementById('categoryStatus');
  status.textContent = 'Loading categories…';
  try {
    const response = await fetch('manage_categories.php', { cache: 'no-store' });
    const data = await response.json();
    if (version !== categoryLoadVersion) return;
    if (!response.ok || !data.success) throw new Error(data.error || 'Could not load categories.');
    managedCategories = data.categories;
    document.getElementById('categoryOptions').replaceChildren(...managedCategories.map(category => {
      const option = document.createElement('option');
      option.value = category.name;
      return option;
    }));
    renderManagedCategories();
    status.textContent = managedCategories.length ? `${managedCategories.length} categories` : 'No categories yet. Add your first topic above.';
  } catch (error) {
    if (version === categoryLoadVersion) status.textContent = error.message;
  }
}

function renderManagedCategories() {
  const list = document.getElementById('categoryList');
  list.replaceChildren(...managedCategories.map(category => {
    const row = document.createElement('tr');
    const nameCell = document.createElement('td');
    const form = document.createElement('form');
    form.id = `category-form-${category.id}`;
    form.className = 'category-name-form';
    const field = document.createElement('div');
    field.className = 'form-field';
    const input = document.createElement('input');
    input.id = `category-${category.id}`;
    input.value = category.name;
    input.required = true;
    input.maxLength = 100;
    input.setAttribute('aria-label', `Category name: ${category.name}`);
    field.appendChild(input);
    form.appendChild(field);
    nameCell.appendChild(form);
    const countCell = document.createElement('td');
    countCell.className = 'category-count';
    countCell.textContent = Number(category.poem_count).toLocaleString();
    const actionsCell = document.createElement('td');
    const actions = document.createElement('div');
    actions.className = 'category-table-actions';
    const save = document.createElement('button');
    save.className = 'icon-btn';
    save.type = 'submit';
    save.setAttribute('form', form.id);
    save.title = 'Save category name';
    save.setAttribute('aria-label', `Save category name: ${category.name}`);
    const saveIcon = document.createElement('i');
    saveIcon.className = 'bi bi-check2';
    saveIcon.setAttribute('aria-hidden', 'true');
    save.appendChild(saveIcon);
    const remove = document.createElement('button');
    remove.className = 'icon-btn danger';
    remove.type = 'button';
    remove.title = 'Remove category';
    remove.setAttribute('aria-label', `Remove category: ${category.name}`);
    const removeIcon = document.createElement('i');
    removeIcon.className = 'bi bi-trash3';
    removeIcon.setAttribute('aria-hidden', 'true');
    remove.appendChild(removeIcon);
    remove.addEventListener('click', () => openCategoryDelete(category));
    actions.append(save, remove);
    actionsCell.appendChild(actions);
    row.append(nameCell, countCell, actionsCell);
    form.addEventListener('submit', async event => {
      event.preventDefault();
      if (save.disabled) return;
      save.disabled = remove.disabled = true;
      try {
        const name = input.value.trim();
        await categoryMutation({ action: 'rename', id: category.id, name });
        if (document.getElementById('poemCategory').value === category.name) document.getElementById('poemCategory').value = name;
        showToast('Category renamed. All poems updated.');
        await loadCategories();
      } catch (error) { showToast(error.message); }
      finally { save.disabled = remove.disabled = false; }
    });
    return row;
  }));
}

async function categoryMutation(data) {
  const response = await fetch('manage_categories.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN },
    body: JSON.stringify(data)
  });
  const result = await response.json();
  if (!response.ok || !result.success) throw new Error(result.error || 'Could not save categories.');
}

document.getElementById('categoryCreateForm').addEventListener('submit', async event => {
  event.preventDefault();
  const button = event.currentTarget.querySelector('button');
  if (button.disabled) return;
  button.disabled = true;
  try {
    await categoryMutation({ action: 'create', name: document.getElementById('categoryName').value.trim() });
    document.getElementById('categoryName').value = '';
    showToast('Category added. You can now use it on your poems.');
    await loadCategories();
  } catch (error) { showToast(error.message); }
  finally { button.disabled = false; }
});

function openCategoryDelete(category) {
  deletingCategory = category;
  const count = Number(category.poem_count);
  document.getElementById('categoryDeleteDescription').textContent = count
    ? `“${category.name}” contains ${count} ${count === 1 ? 'poem' : 'poems'}. Choose another category to keep them organized. No poems will be deleted.`
    : `Remove the empty category “${category.name}”? No poems will be deleted.`;
  const select = document.getElementById('categoryReplacement');
  select.replaceChildren(new Option('Choose a category', ''), ...managedCategories
    .filter(item => item.id !== category.id).map(item => new Option(item.name, item.id)));
  select.required = count > 0;
  select.disabled = count === 0;
  document.getElementById('categoryReplacementField').hidden = count === 0;
  document.getElementById('categoryDeleteError').textContent = count && managedCategories.length < 2
    ? 'Add another category first, then move these poems into it.' : '';
  document.getElementById('categoryDeleteForm').querySelector('[type="submit"]').disabled = count > 0 && managedCategories.length < 2;
  document.getElementById('categoryDeleteDialog').showModal();
}

document.getElementById('categoryDeleteForm').addEventListener('submit', async event => {
  event.preventDefault();
  const button = event.currentTarget.querySelector('[type="submit"]');
  if (button.disabled) return;
  button.disabled = true;
  const select = document.getElementById('categoryReplacement');
  try {
    await categoryMutation({ action: 'delete', id: deletingCategory.id, replacement_id: select.disabled ? 0 : Number(select.value) });
    const poemCategory = document.getElementById('poemCategory');
    if (poemCategory.value === deletingCategory.name) poemCategory.value = select.disabled ? '' : select.selectedOptions[0].textContent;
    document.getElementById('categoryDeleteDialog').close();
    showToast('Category removed. Your poems are safe.');
    await loadCategories();
  } catch (error) { document.getElementById('categoryDeleteError').textContent = error.message; }
  finally { button.disabled = false; }
});
