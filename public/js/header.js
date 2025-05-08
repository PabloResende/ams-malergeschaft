// public/js/header.js

document.addEventListener('DOMContentLoaded', () => {
  // Notificações
  document.querySelectorAll('.notification-btn').forEach(btn => {
    const wrap = btn.parentElement;
    const list = wrap.querySelector('.notification-list');
    const dot  = btn.querySelector('.notification-dot');

    if (!list) return;

    // inicializa badge
    if (dot) {
      const has = list.querySelectorAll('li.notification-item').length > 0;
      dot.classList.toggle('hidden', !has);
    }

    // toggle dropdown
    btn.addEventListener('click', e => {
      e.stopPropagation();
      document.querySelectorAll('.notification-list').forEach(o => { if (o !== list) o.classList.add('hidden'); });
      list.classList.toggle('hidden');
      if (dot) dot.classList.add('hidden');
    });

    // fecha ao clicar fora
    document.addEventListener('click', e => {
      if (!wrap.contains(e.target)) list.classList.add('hidden');
    });

    // hover marca lida & decrementa badge
    if (dot) {
      let count = parseInt(dot.innerText, 10) || 0;
      list.querySelectorAll('li.notification-item').forEach(item => {
        item.dataset.read = 'false';
        item.addEventListener('mouseenter', () => {
          if (item.dataset.read === 'false') {
            count = Math.max(count - 1, 0);
            dot.innerText = count;
            if (count === 0) dot.classList.add('hidden');
            item.dataset.read = 'true';
          }
          item.classList.add('bg-gray-100');
        });
        item.addEventListener('mouseleave', () => {
          item.classList.remove('bg-gray-100');
        });
      });
    }
  });

  // Idioma
  const langBtn = document.getElementById('language-button'),
        langMenu = document.getElementById('language-menu');
  if (langBtn && langMenu) {
    langBtn.addEventListener('click', e => { e.stopPropagation(); langMenu.classList.toggle('hidden'); });
    document.addEventListener('click', e => {
      if (!langBtn.contains(e.target) && !langMenu.contains(e.target)) langMenu.classList.add('hidden');
    });
  }
});
