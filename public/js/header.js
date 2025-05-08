// public/js/header.js

document.addEventListener('DOMContentLoaded', function () {
  // ===== Mobile Menu =====
  const sidebar          = document.getElementById('sidebar');
  const mobileMenuButton = document.getElementById('mobileMenuButton');
  const contentOverlay   = document.getElementById('contentOverlay');
  if (sidebar && mobileMenuButton && contentOverlay) {
    const toggleMenu = () => {
      sidebar.classList.toggle('sidebar-open');
      contentOverlay.classList.toggle('hidden');
      document.body.classList.toggle('overflow-hidden');
    };
    mobileMenuButton.addEventListener('click', function (e) {
      e.stopPropagation();
      toggleMenu();
    });
    contentOverlay.addEventListener('click', toggleMenu);
    sidebar.querySelectorAll('a').forEach(link => {
      link.addEventListener('click', toggleMenu);
    });
  }

  // ===== Language Dropdown =====
  const langButton = document.getElementById('language-button');
  const langMenu   = document.getElementById('language-menu');
  if (langButton && langMenu) {
    langButton.addEventListener('click', function (e) {
      e.stopPropagation();
      langMenu.classList.toggle('hidden');
    });
  }

  // ===== Notifications Dropdown =====
  // suporta tanto IDs quanto classes
  const notificationButtons = [
    document.getElementById('notificationBtn'),
    ...document.querySelectorAll('.notification-btn')
  ].filter(Boolean);

  notificationButtons.forEach(btn => {
    const list = btn.parentElement.querySelector('.notification-list') 
               || document.getElementById('notificationList');
    const dot  = btn.querySelector('.notification-dot') 
               || document.getElementById('notificationDot');

    if (!list) {
      console.warn('Notification list not found for', btn);
      return;
    }

    // abre/fecha ao clicar no botão
    btn.addEventListener('click', function (e) {
      e.stopPropagation();

      // fecha outras listas abertas
      document.querySelectorAll('.notification-list, #notificationList').forEach(l => {
        if (l !== list) l.classList.add('hidden');
      });

      // toggle desta lista
      list.classList.toggle('hidden');

      // esconde o dot
      if (dot) dot.classList.add('hidden');
    });
  });

  // evita fechar quando clicar dentro da lista
  document.querySelectorAll('.notification-list, #notificationList').forEach(list => {
    list.addEventListener('click', function (e) {
      e.stopPropagation();
    });
  });

  // ===== Close dropdowns on outside click =====
  document.addEventListener('click', function () {
    // fecha language menu
    if (langMenu) langMenu.classList.add('hidden');
    // fecha notifications
    document.querySelectorAll('.notification-list, #notificationList').forEach(l => {
      l.classList.add('hidden');
    });
  });

  // ===== Initialize notification dot visibility =====
  const firstList = document.querySelector('.notification-list') 
                 || document.getElementById('notificationList');
  const firstDot  = document.querySelector('.notification-dot') 
                 || document.getElementById('notificationDot');
  if (firstList && firstDot) {
    const items = firstList.querySelectorAll('li');
    const hasNotifications = items.length > 0
      && !items[0].classList.contains('text-gray-500');
    firstDot.classList.toggle('hidden', !hasNotifications);
  }
});
