// Only the authenticated POST preview sets this payload; public galleries do nothing.
if (window.THINK_ROOM_PREVIEW) {
  const banner = document.createElement('div');
  banner.className = 'draft-preview-banner';
  const label = document.createElement('span');
  label.textContent = 'Private preview · Your poem has not been published. Close this tab to keep editing.';
  const read = document.createElement('button');
  read.type = 'button'; read.textContent = 'Read preview';
  read.addEventListener('click', openPreviewPoem);
  banner.append(label, read);
  document.querySelector('.masthead').before(banner);
  document.title = 'Private poem preview | Think Room';
}

function openPreviewPoem() {
  const poem = window.THINK_ROOM_PREVIEW;
  if (!poem) return;
  resetReader();
  document.getElementById('readerCategory').textContent = poem.category;
  document.getElementById('readerTitle').textContent = poem.title;
  document.getElementById('readerBody').textContent = poem.body;
  const overlay = document.getElementById('readerOverlay');
  overlay.classList.add('draft-preview-reader', 'open');
  document.body.style.overflow = 'hidden';
  document.getElementById('readerCloseBtn').focus();
  // Deliberately skip likes, comments, sharing, URLs, and view tracking for drafts.
}
