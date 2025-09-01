// public/js/header.js

(() => {
  const STORAGE_KEY = 'readNotifications';
  let readSet = new Set();

  // Carrega notificações lidas do localStorage
  try {
    const saved = JSON.parse(localStorage.getItem(STORAGE_KEY));
    if (Array.isArray(saved)) readSet = new Set(saved);
  } catch (err) {
    console.warn('Não foi possível ler readNotifications do localStorage', err);
  }

  /**
   * Atualiza o contador (badge) com base nas notificações existentes e lidas.
   */
  function updateBadge(listEl, badgeEl) {
    const items   = Array.from(listEl.querySelectorAll('.notification-item'));
    const allKeys = items.map(i => i.dataset.key).filter(k => k);
    const unread  = allKeys.filter(k => !readSet.has(k));
    if (unread.length > 0) {
      badgeEl.textContent = unread.length;
      badgeEl.classList.remove('hidden');
    } else {
      badgeEl.classList.add('hidden');
    }
  }



  /**
   * Inicializa badge e eventos de clique tanto no botão quanto em cada item.
   */
  function initNotifications(btnId, listId, countId) {
    const btn   = document.getElementById(btnId);
    const list  = document.getElementById(listId);
    const badge = document.getElementById(countId);
    if (!btn || !list || !badge) return;

    updateBadge(list, badge);

    btn.addEventListener('click', e => {
      e.stopPropagation();
      list.classList.toggle('hidden');
    });

    list.querySelectorAll('.notification-item').forEach(item => {
      item.addEventListener('click', () => {
        const key = item.dataset.key;
        if (!key || readSet.has(key)) return;

        readSet.add(key);
        localStorage.setItem(STORAGE_KEY, JSON.stringify(Array.from(readSet)));
        updateBadge(list, badge);

        const link = item.querySelector('a');
        if (link) window.location.href = link.href;
      });
    });
  }

  /**
   * Inicializa toggle de idioma.
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

  // notificações desktop e mobile
  initNotifications('notificationBtn',       'notificationList',       'notificationCount');
  initNotifications('notificationBtnMobile', 'notificationListMobile', 'notificationCountMobile');

  // dropdown de idioma desktop e mobile
  initLanguageToggle('language-button',        'language-menu');
  initLanguageToggle('language-button-mobile', 'language-menu-mobile');

  // Fecha qualquer dropdown ao clicar fora
  document.addEventListener('click', () => {
    ['notificationList', 'notificationListMobile', 'language-menu', 'language-menu-mobile']
      .forEach(id => {
        const el = document.getElementById(id);
        if (el) el.classList.add('hidden');
      });
  });
})();
