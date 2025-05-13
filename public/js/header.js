// public/js/header.js

document.addEventListener('DOMContentLoaded', () => {
  const STORAGE_KEY = 'readNotifications';
  let readSet = new Set();

  // Tenta carregar do localStorage as notificações já lidas
  try {
    const saved = JSON.parse(localStorage.getItem(STORAGE_KEY));
    if (Array.isArray(saved)) readSet = new Set(saved);
  } catch {
    console.warn('Não foi possível ler readNotifications do localStorage');
  }

  /**
   * Inicia o badge de notificações.
   */
  function initBadge(btnId, listId, countId) {
    const btn   = document.getElementById(btnId);
    const list  = document.getElementById(listId);
    const badge = document.getElementById(countId);
    if (!btn || !list || !badge) return;

    const items   = Array.from(list.querySelectorAll('.notification-item'));
    const allKeys = items.map(i => i.dataset.key).filter(k => k);
    const unread  = allKeys.filter(k => !readSet.has(k));

    if (unread.length > 0) {
      badge.textContent = unread.length;
      badge.classList.remove('hidden');
    } else {
      badge.classList.add('hidden');
    }

    btn.addEventListener('click', e => {
      e.stopPropagation();
      list.classList.toggle('hidden');

      allKeys.forEach(k => readSet.add(k));
      localStorage.setItem(STORAGE_KEY, JSON.stringify(Array.from(readSet)));

      badge.textContent = '0';
      badge.classList.add('hidden');
    });
  }

  // Inicializa notificações desktop e mobile
  initBadge('notificationBtn',       'notificationList',       'notificationCount');
  initBadge('notificationBtnMobile', 'notificationListMobile', 'notificationCountMobile');

  /**
   * Toggle do menu de idiomas (desktop e mobile)
   */
  function initLanguageToggle(btnId, menuId) {
    const btn  = document.getElementById(btnId);
    const menu = document.getElementById(menuId);
    if (!btn || !menu) return;

    btn.addEventListener('click', e => {
      e.stopPropagation();
      menu.classList.toggle('hidden');
    });
  }

  initLanguageToggle('language-button',        'language-menu');
  initLanguageToggle('language-button-mobile', 'language-menu-mobile');

  // Fecha dropdowns ao clicar fora
  document.addEventListener('click', () => {
    // notificações
    const notifList        = document.getElementById('notificationList');
    const notifListMobile  = document.getElementById('notificationListMobile');
    if (notifList)       notifList.classList.add('hidden');
    if (notifListMobile) notifListMobile.classList.add('hidden');

    // idiomas
    const langMenu        = document.getElementById('language-menu');
    const langMenuMobile  = document.getElementById('language-menu-mobile');
    if (langMenu)        langMenu.classList.add('hidden');
    if (langMenuMobile)  langMenuMobile.classList.add('hidden');
  });
});
