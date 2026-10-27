/* Actions shared by gallery cards and the reader; poem IDs avoid title collisions. */
window.GalleryActions = (() => {
  let activeCard = null;
  let readerVersion = 0;
  const liked = new Set();
  const pending = new Set();
  const count = value => Math.max(0, Number.parseInt(value, 10) || 0);

  async function request(url, payload) {
    const response = await fetch(url, payload === undefined ? undefined : {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const data = await response.json();
    if (!response.ok || data.error) throw Error(data.error || 'Please try again.');
    return data;
  }

  function sync(card, likes, comments) {
    if (likes !== undefined) card.dataset.likes = Math.max(count(card.dataset.likes), count(likes));
    if (comments !== undefined) card.dataset.comments = count(comments);
    const id = card.dataset.poemId;
    const hasLiked = liked.has(id);
    const button = card.querySelector('.card-like');
    button.disabled = hasLiked || pending.has(id);
    button.classList.toggle('liked', hasLiked);
    button.querySelector('i').className = 'bi bi-hand-thumbs-up' + (hasLiked ? '-fill' : '');
    button.querySelector('.card-like-label').textContent = hasLiked ? 'Liked' : 'Like';
    button.setAttribute('aria-label', `${hasLiked ? 'Liked' : 'Like'} ${card._poem.title}: ${card.dataset.likes} likes`);
    card.querySelector('.card-likes').textContent = card.dataset.likes;
    card.querySelector('.card-comments').textContent = card.dataset.comments;
    card.querySelector('.card-comment').setAttribute('aria-label', `Comment on ${card._poem.title}: ${card.dataset.comments} comments`);
    if (card === activeCard) {
      const readerLike = document.getElementById('likeBtn');
      readerLike.disabled = button.disabled;
      readerLike.classList.toggle('liked', hasLiked);
      readerLike.querySelector('i').className = button.querySelector('i').className;
      readerLike.querySelector('.like-label').textContent = hasLiked ? 'Liked' : 'Like';
      document.getElementById('likeCount').textContent = card.dataset.likes;
    }
  }

  async function like(card) {
    if (!card || liked.has(card.dataset.poemId) || pending.has(card.dataset.poemId)) return;
    const status = card.querySelector('.card-status');
    status.textContent = '';
    pending.add(card.dataset.poemId);
    sync(card);
    try {
      const data = await request('/update_likes.php', { id: Number(card.dataset.poemId) });
      if (!Number.isFinite(Number(data.likes))) throw Error('Unable to like this poem. Please try again.');
      liked.add(card.dataset.poemId);
      sync(card, data.likes);
    } catch (error) {
      status.textContent = error.message;
      if (card === activeCard) document.getElementById('readerFeedback').textContent = error.message;
    } finally {
      pending.delete(card.dataset.poemId);
      sync(card);
    }
  }

  async function share(card, button) {
    const status = card.querySelector('.card-status');
    const url = new URL(card.dataset.url, location.origin).href;
    status.textContent = '';
    card.querySelector('.share-copy-field')?.remove();
    const copyLink = async () => {
      if (navigator.clipboard && window.isSecureContext) {
        try {
          await navigator.clipboard.writeText(url);
          status.textContent = 'Link copied.';
          return;
        } catch {}
      }
      const input = document.createElement('input');
      input.value = url;
      input.className = 'share-copy-field';
      card.querySelector('.card-inner').appendChild(input);
      input.focus(); input.select();
      let copied = false;
      try { copied = document.execCommand('copy'); } catch {}
      if (copied) {
        input.remove(); button.focus(); status.textContent = 'Link copied.';
      } else {
        input.readOnly = true;
        input.setAttribute('aria-label', 'Poem link to copy');
        status.textContent = 'Copy this link to share the poem.';
      }
    };
    try {
      if (navigator.share) {
        try {
          await navigator.share({ title: card._poem.title, text: `${card._poem.title} — Think Room by Freddy Carias`, url });
        } catch (error) {
          if (error.name === 'AbortError') return;
          await copyLink();
        }
      } else {
        await copyLink();
      }
      request('/track_share.php', { id: Number(card.dataset.poemId) }).catch(() => {});
    } catch (error) {
      if (error.name !== 'AbortError') status.textContent = 'Unable to share. Please try again.';
    }
  }

  function setup(card, poem) {
    card._poem = poem;
    card.dataset.poemId = poem.id;
    card.dataset.likes = count(poem.likes);
    card.dataset.comments = count(poem.comment_count);
    card.dataset.full = poem.body;
    card.dataset.url = poem.slug ? `/poems/${poem.slug}` : `/poem?id=${poem.id}`;
    card.querySelectorAll('.card-content, .card-read').forEach(link => { link.href = card.dataset.url; });
    card.querySelector('.card-like').addEventListener('click', () => like(card));
    card.querySelector('.card-comment').addEventListener('click', event => openPoem(card, true, event.currentTarget));
    const shareButton = card.querySelector('.card-share');
    shareButton.setAttribute('aria-label', `Share ${poem.title}`);
    shareButton.addEventListener('click', () => share(card, shareButton));
    sync(card);
  }

  async function loadComments(card) {
    const version = readerVersion;
    document.getElementById('readerFeedback').textContent = 'Loading comments...';
    try {
      const data = await request('/get_likes_comments.php?id=' + encodeURIComponent(card.dataset.poemId));
      if (activeCard !== card || readerVersion !== version) return;
      sync(card, data.likes, data.comments.length);
      displayComments(data.comments);
      document.getElementById('readerFeedback').textContent = '';
    } catch {
      if (activeCard === card && readerVersion === version) document.getElementById('readerFeedback').textContent = 'Unable to load comments. Please close and try again.';
    }
  }

  async function postComment() {
    const card = activeCard;
    const version = readerVersion;
    if (!card) return;
    const input = document.getElementById('commentInput');
    const name = document.getElementById('commentName');
    const button = document.querySelector('#commentForm button');
    const feedback = document.getElementById('readerFeedback');
    if (!input.value.trim() || button.disabled) { input.focus(); return; }
    button.disabled = true;
    feedback.textContent = 'Sending comment...';
    try {
      const data = await request('/save_comment.php', { id: Number(card.dataset.poemId), name: name.value.trim(), comment: input.value.trim() });
      if (!data.success) throw Error('Unable to post your comment. Please try again.');
      if (activeCard !== card || readerVersion !== version) return;
      input.value = ''; name.value = '';
      displayComments(data.comments || []);
      sync(card, undefined, (data.comments || []).length);
      feedback.textContent = 'Thank you — your comment is awaiting approval.';
    } catch (error) {
      if (activeCard === card && readerVersion === version) feedback.textContent = error.message;
    } finally {
      if (activeCard === card && readerVersion === version) button.disabled = false;
    }
  }

  return {
    setup, like, sync, loadComments, postComment,
    current: () => activeCard,
    select: card => { readerVersion++; activeCard = card; if (card) sync(card); }
  };
})();
