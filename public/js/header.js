// public/js/header.js

document.addEventListener('DOMContentLoaded', () => {
  const STORAGE_KEY = 'readNotifications';

  // Lê do localStorage as keys já marcadas como lidas
  let readSet = new Set();
  try {
    const saved = JSON.parse(localStorage.getItem(STORAGE_KEY));
    if (Array.isArray(saved)) readSet = new Set(saved);
  } catch (e) {
    console.warn('Não foi possível ler readNotifications do localStorage');
  }

  /**
   * Inicia o badge de notificações.
   * @param {string} btnId       id do botão de sino
   * @param {string} listId      id da lista <ul>
   * @param {string} countId     id do badge <span>
   */
  function initBadge(btnId, listId, countId) {
    const btn   = document.getElementById(btnId);
    const list  = document.getElementById(listId);
    const badge = document.getElementById(countId);
    if (!btn || !list || !badge) return;

    // Coleta todas as keys atuais das notificações
    const items   = Array.from(list.querySelectorAll('.notification-item'));
    const allKeys = items
      .map(i => i.dataset.key)
      .filter(k => k);

    // Calcula quantas NÃO estão em readSet
    const unreadKeys = allKeys.filter(k => !readSet.has(k));

    if (unreadKeys.length > 0) {
      badge.textContent = unreadKeys.length;
      badge.classList.remove('hidden');
    } else {
      badge.classList.add('hidden');
    }

    // Ao clicar no sino, marca todas como lidas
    btn.addEventListener('click', e => {
      e.stopPropagation();
      list.classList.toggle('hidden');

      allKeys.forEach(k => readSet.add(k));
      localStorage.setItem(STORAGE_KEY, JSON.stringify(Array.from(readSet)));

      badge.textContent = '0';
      badge.classList.add('hidden');
    });

    // Fecha dropdown ao clicar fora
    document.addEventListener('click', e => {
      if (!btn.contains(e.target) && !list.contains(e.target)) {
        list.classList.add('hidden');
      }
    });
  }

  // Inicializa desktop e mobile
  initBadge('notificationBtn',       'notificationList',       'notificationCount');
  initBadge('notificationBtnMobile', 'notificationListMobile', 'notificationCountMobile');
});
