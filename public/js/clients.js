// public/js/clients.js

document.addEventListener('DOMContentLoaded', () => {
  // Base URL
  const baseUrl = window.location.origin + '/ams-malergeschaft/public';

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
  const clientsContainer     = document.getElementById('clientsContainer');
  const detailsModal         = document.getElementById('clientDetailsModal');
  const closeDetailsX        = document.getElementById('closeClientDetailsModal');
  const closeDetailsBtn      = document.getElementById('closeClientDetailsBtn');

  // Delegação de evento para abrir detalhes
  clientsContainer.addEventListener('click', e => {
    const item = e.target.closest('.client-item');
    if (!item) return;

    const id = item.dataset.id;
    fetch(`${baseUrl}/clients/show?id=${id}`, {
      credentials: 'same-origin'
    })
      .then(res => {
        if (!res.ok) throw new Error(`Status ${res.status}`);
        return res.json();
      })
      .then(data => {
        if (data.error) {
          alert(data.error);
          return;
        }
        // Preencher campos do modal
        document.getElementById('detailsClientName').textContent     = data.name;
        document.getElementById('detailsClientAddress').textContent  = data.address || '—';
        document.getElementById('detailsClientAbout').textContent    = data.about   || '—';
        document.getElementById('detailsClientPhone').textContent    = data.phone   || '—';
        document.getElementById('detailsClientLoyalty').textContent  = data.loyalty_points;
        document.getElementById('detailsClientProjects').textContent = data.project_count;
        // Exibir modal
        detailsModal.classList.remove('hidden');
      })
      .catch(err => {
        console.error('Erro ao carregar detalhes:', err);
        alert('Não foi possível carregar detalhes do cliente.');
      });
  });

  // Função para fechar modal de detalhes
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
});
