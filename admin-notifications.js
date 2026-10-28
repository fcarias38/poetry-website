(() => {
  'use strict';
  const center = document.getElementById('notificationCenter');
  if (!center) return;
  const toggle = document.getElementById('notificationToggle');
  const popover = document.getElementById('notificationPopover');
  const list = document.getElementById('notificationList');
  const status = document.getElementById('notificationStatus');
  const mark = document.getElementById('notificationRead');
  const refresh = document.getElementById('notificationRefresh');
  const views = [{ list, status, mark, refresh }];
  const pageList = document.getElementById('notificationPageList');
  if (pageList) views.push({
    list: pageList,
    status: document.getElementById('notificationPageStatus'),
    mark: document.getElementById('notificationPageRead'),
    refresh: document.getElementById('notificationPageRefresh')
  });
  const badges = document.querySelectorAll('#notificationCount, [data-notification-count]');
  const announcement = document.getElementById('notificationAnnouncement');
  let latestId = 0;
  let previousId = null;
  let busy = false;
  let stopped = false;

  function setOpen(open, restoreFocus = false) {
    popover.hidden = !open;
    toggle.setAttribute('aria-expanded', String(open));
    if (restoreFocus) toggle.focus();
  }

  function render(data) {
    const unread = Number(data.unread_count);
    latestId = Number(data.latest_id);
    badges.forEach(badge => {
      badge.hidden = unread === 0;
      badge.textContent = unread > 99 ? '99+' : String(unread);
    });
    toggle.setAttribute('aria-label', unread ? `Notifications, ${unread} unread` : 'Notifications');
    for (const view of views) {
      view.mark.disabled = unread === 0;
      view.list.replaceChildren();
      for (const item of data.notifications) {
        const row = document.createElement('li');
        row.className = item.read_at ? 'notification-row' : 'notification-row unread';
        const text = document.createElement('p');
        text.textContent = item.kind === 'comment'
          ? `${item.actor || 'Anonymous'} commented on “${item.poem_title}”.`
          : `A reader liked “${item.poem_title}”.`;
        const time = document.createElement('time');
        time.dateTime = item.created_at;
        time.textContent = new Date(item.created_at).toLocaleString();
        if (item.kind === 'share') text.textContent = `A reader used the share options for “${item.poem_title}”.`;
        row.append(text, time);
        if (item.kind === 'comment') {
          const action = document.createElement('button');
          action.type = 'button';
          action.className = 'category-link';
          action.textContent = 'Review comments';
          action.addEventListener('click', () => {
            setOpen(false, true);
            showPanel('comments');
          });
          row.append(action);
        }
        view.list.append(row);
      }
      view.status.textContent = data.notifications.length
        ? 'Recent activity, unread first. Showing up to 40 notifications.'
        : 'No notifications yet. New likes, comments, and shares will appear here.';
    }
    if (previousId !== null && latestId > previousId) {
      announcement.textContent = `New activity. You have ${unread} unread notifications.`;
      if (typeof refreshPendingBadge === 'function') refreshPendingBadge();
    }
    previousId = latestId;
  }

  async function load(acknowledge = false) {
    if (busy || stopped) return;
    busy = true;
    views.forEach(view => { view.refresh.disabled = true; view.mark.disabled = true; });
    const throughId = latestId;
    try {
      const response = await fetch('/admin_notifications.php', {
        method: acknowledge ? 'POST' : 'GET',
        credentials: 'same-origin', cache: 'no-store',
        headers: acknowledge ? {
          'Content-Type': 'application/json',
          'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
        } : {},
        ...(acknowledge ? { body: JSON.stringify({ through_id: throughId }) } : {}),
        signal: AbortSignal.timeout(15000)
      });
      if (response.status === 403) {
        stopped = true;
        views.forEach(view => view.list.replaceChildren());
        badges.forEach(badge => { badge.hidden = true; });
        toggle.setAttribute('aria-label', 'Notifications, sign in required');
        throw new Error('Your session has expired. Sign in again to see notifications.');
      }
      const data = await response.json();
      if (!response.ok || !data.success) throw new Error('Could not load notifications. Please try again.');
      render(data);
    } catch (error) {
      views.forEach(view => {
        view.status.textContent = stopped ? error.message : 'Could not update notifications. Please try Refresh.';
        view.mark.disabled = true;
      });
    } finally {
      busy = false;
      views.forEach(view => { view.refresh.disabled = stopped; });
    }
  }

  toggle.addEventListener('click', () => {
    const opening = popover.hidden;
    setOpen(opening);
    if (opening) load();
  });
  document.getElementById('notificationClose').addEventListener('click', () => setOpen(false, true));
  document.addEventListener('click', event => {
    if (!center.contains(event.target)) setOpen(false);
  });
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && !popover.hidden) setOpen(false, true);
  });
  views.forEach(view => {
    view.mark.addEventListener('click', () => { if (latestId > 0) load(true); });
    view.refresh.addEventListener('click', () => load());
  });
  document.addEventListener('dashboard:panel', event => {
    setOpen(false);
    if (event.detail.id === 'notifications') load();
  });
  document.addEventListener('visibilitychange', () => { if (!document.hidden) load(); });
  setInterval(() => { if (!document.hidden) load(); }, 30000);
  load();
})();
