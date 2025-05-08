document.addEventListener('DOMContentLoaded', () => {
  const baseUrl = window.baseUrl || '';

  // ——— Modal de Criação ———
  const addClientBtn     = document.getElementById('addClientBtn');
  const clientModal      = document.getElementById('clientModal');
  const closeClientModal = document.getElementById('closeClientModal');

  addClientBtn.addEventListener('click', () => {
    clientModal.classList.remove('hidden');
  });

  closeClientModal.addEventListener('click', () => {
    clientModal.classList.add('hidden');
  });

  window.addEventListener('click', e => {
    if (e.target === clientModal) {
      clientModal.classList.add('hidden');
    }
  });

  // ——— Modal de Detalhes ———
  const detailsModal    = document.getElementById('clientDetailsModal');
  const closeDetailsX   = document.getElementById('closeClientDetailsModal');
  const closeDetailsBtn = document.getElementById('closeClientDetailsBtn');

  function closeDetails() {
    detailsModal.classList.add('hidden');
  }

  closeDetailsX.addEventListener('click', closeDetails);
  closeDetailsBtn.addEventListener('click', closeDetails);

  window.addEventListener('click', e => {
    if (e.target === detailsModal) {
      closeDetails();
    }
  });

  // ——— Abre detalhes ao clicar em cada card ———
  document.querySelectorAll('.client-item').forEach(item => {
    item.addEventListener('click', () => {
      const id = item.getAttribute('data-id');
      if (!id) return;

      fetch(`${baseUrl}/clients/show?id=${encodeURIComponent(id)}`, {
        credentials: 'same-origin'
      })
        .then(res => {
          if (!res.ok) throw new Error(`HTTP ${res.status}`);
          return res.json();
        })
        .then(data => {
          if (data.error) {
            alert(data.error);
            return;
          }
          document.getElementById('detailsClientName').textContent    = data.name;
          document.getElementById('detailsClientAddress').textContent = data.address   || '—';
          document.getElementById('detailsClientAbout').textContent   = data.about     || '—';
          document.getElementById('detailsClientPhone').textContent   = data.phone     || '—';
          document.getElementById('detailsClientLoyalty').textContent = data.loyalty_points;
          document.getElementById('detailsClientProjects').textContent= data.project_count;
          detailsModal.classList.remove('hidden');
        })
        .catch(err => {
          console.error('Erro ao carregar detalhes:', err);
          alert('Não foi possível carregar detalhes do cliente.');
        });
    });
  });
});
