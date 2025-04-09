document.addEventListener("DOMContentLoaded", function () {
  // Mobile Menu
  const sidebar = document.getElementById('sidebar');
  const mobileMenuButton = document.getElementById('mobileMenuButton');
  const contentOverlay = document.getElementById('contentOverlay');

  if (mobileMenuButton && sidebar && contentOverlay) {
    const toggleMenu = () => {
      sidebar.classList.toggle('sidebar-open');
      contentOverlay.classList.toggle('hidden');
      document.body.classList.toggle('overflow-hidden');
    };

    mobileMenuButton.addEventListener('click', (e) => {
      e.stopPropagation();
      toggleMenu();
    });

    contentOverlay.addEventListener('click', toggleMenu);
    
    // Fechar menu ao clicar nos links
    document.querySelectorAll('#sidebar a').forEach(link => {
      link.addEventListener('click', toggleMenu);
    });
  }

  // Idioma
  const langButton = document.getElementById('language-button');
  const langMenu = document.getElementById('language-menu');

  if (langButton && langMenu) {
    langButton.addEventListener('click', (e) => {
      e.stopPropagation();
      langMenu.classList.toggle('hidden');
    });

    document.addEventListener('click', (e) => {
      if (!langButton.contains(e.target) && !langMenu.contains(e.target)) {
        langMenu.classList.add('hidden');
      }
    });
  }

  // Notificações
  const notificationBtn = document.getElementById('notificationBtn');
  const notificationList = document.getElementById('notificationList');
  const notificationDot = document.getElementById('notificationDot');

  if (notificationBtn && notificationList && notificationDot) {
    notificationBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      notificationList.classList.toggle('hidden');
      notificationDot.classList.add('hidden');
    });

    // Verificar notificações
    const notificationItems = document.querySelectorAll('#notificationList ul li');
    const hasNotifications = notificationItems.length > 0 && 
      !notificationItems[0].classList.contains('text-gray-500');

    notificationDot.classList.toggle('hidden', !hasNotifications);
  }

  // Fechar menus ao clicar fora
  document.addEventListener('click', function(e) {
    if (notificationList && !notificationBtn.contains(e.target) && !notificationList.contains(e.target)) {
      notificationList.classList.add('hidden');
    }
  });
});

// Adicione esta verificação para prevenir erros
if (notificationItems.length > 0) {
  const hasNotifications = !notificationItems[0].classList.contains('text-gray-500');
  notificationDot.classList.toggle('hidden', !hasNotifications);
}