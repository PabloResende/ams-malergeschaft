// public/js/clients.js

document.addEventListener('DOMContentLoaded', () => {
  const baseUrl    = window.baseUrl || '';
  const confirmMsg = window.confirmDeleteMsg || 'Are you sure?';

  function closeModal(modal) {
    modal.classList.add('hidden');
  }

  // — Modal de Criação
  const createModal     = document.getElementById('createModal');
  const openCreateBtn   = document.getElementById('addClientBtn');
  const closeCreateBtn  = document.getElementById('closeCreateModal');
  const cancelCreateBtn = document.getElementById('cancelCreate');

  openCreateBtn.addEventListener('click', () => createModal.classList.remove('hidden'));
  [closeCreateBtn, cancelCreateBtn].forEach(btn =>
    btn.addEventListener('click', () => closeModal(createModal))
  );
  createModal.addEventListener('click', e => {
    if (e.target === createModal) closeModal(createModal);
  });

  // — Modal de Detalhes / Edição
  const detailsModal     = document.getElementById('detailsModal');
  const closeDetailsBtn  = document.getElementById('closeDetailsModal');
  const cancelDetailsBtn = document.getElementById('cancelDetails');
  const detailForm       = document.getElementById('detailsForm');
  const deleteLink       = document.getElementById('deleteClientLink');
  const deleteForm       = document.getElementById('deleteForm');
  const deleteIdField    = document.getElementById('deleteIdField');

  [closeDetailsBtn, cancelDetailsBtn].forEach(btn =>
    btn.addEventListener('click', () => closeModal(detailsModal))
  );
  detailsModal.addEventListener('click', e => {
    if (e.target === detailsModal) closeModal(detailsModal);
  });

  // Abre detalhes ao clicar em cada card
  document.querySelectorAll('.client-item').forEach(item => {
    item.addEventListener('click', () => {
      const id = item.dataset.id;
      if (!id) return;

      fetch(`${baseUrl}/clients/show?id=${encodeURIComponent(id)}`, {
        credentials: 'same-origin'
      })
      .then(res => res.ok ? res.json() : Promise.reject(res.status))
      .then(data => {
        if (data.error) return alert(data.error);

        // Preenche formulário de edição
        detailForm.id.value      = data.id;
        detailForm.name.value    = data.name;
        detailForm.address.value = data.address || '';
        detailForm.about.value   = data.about   || '';
        detailForm.phone.value   = data.phone   || '';
        document.getElementById('detailLoyalty').textContent  = data.loyalty_points;
        document.getElementById('detailProjects').textContent = data.project_count;

        // Configura exclusão via form POST
        deleteIdField.value = data.id;
        deleteLink.onclick = e => {
          e.preventDefault();
          if (confirm(confirmMsg)) deleteForm.submit();
        };

        detailsModal.classList.remove('hidden');
      })
      .catch(err => {
        console.error('Erro ao carregar detalhes:', err);
        alert('Não foi possível carregar detalhes do cliente.');
      });
    });
  });
});
