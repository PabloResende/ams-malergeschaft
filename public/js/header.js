// public/js/header.js

document.addEventListener('DOMContentLoaded', () => {
  // Sidebar mobile toggle
  const mobileBtn = document.getElementById('mobileMenuButton');
  const sidebar   = document.getElementById('sidebar');
  const overlay   = document.getElementById('contentOverlay');
  if (mobileBtn && sidebar && overlay) {
    mobileBtn.addEventListener('click', () => {
      sidebar.classList.toggle('sidebar-open');
      overlay.classList.toggle('hidden');
      document.body.classList.toggle('overflow-hidden');
    });
    overlay.addEventListener('click', () => {
      sidebar.classList.add('hidden');
      overlay.classList.add('hidden');
      document.body.classList.remove('overflow-hidden');
    });
  }

  // Language dropdown
  const langBtn = document.getElementById('language-button');
  const langMenu = document.getElementById('language-menu');
  if (langBtn && langMenu) {
    langBtn.addEventListener('click', e => {
      e.stopPropagation();
      langMenu.classList.toggle('hidden');
    });
    document.addEventListener('click', e => {
      if (!langBtn.contains(e.target) && !langMenu.contains(e.target)) {
        langMenu.classList.add('hidden');
      }
    });
  }

  // Notifications desktop
  const notifBtn = document.getElementById('notificationBtn');
  const notifList = document.getElementById('notificationList');
  if (notifBtn && notifList) {
    notifBtn.addEventListener('click', e => {
      e.stopPropagation();
      notifList.classList.toggle('hidden');
    });
    document.addEventListener('click', e => {
      if (!notifBtn.contains(e.target) && !notifList.contains(e.target)) {
        notifList.classList.add('hidden');
      }
    });
  }

  // Notifications mobile
  const notifBtnM = document.getElementById('notificationBtnMobile');
  const notifListM = document.getElementById('notificationListMobile');
  if (notifBtnM && notifListM) {
    notifBtnM.addEventListener('click', e => {
      e.stopPropagation();
      notifListM.classList.toggle('hidden');
    });
    document.addEventListener('click', e => {
      if (!notifBtnM.contains(e.target) && !notifListM.contains(e.target)) {
        notifListM.classList.add('hidden');
      }
    });
  }
});
