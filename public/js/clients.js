// public/js/clients.js

document.addEventListener('DOMContentLoaded', () => {
  const baseUrl        = window.baseUrl || '';

  // — CREATE MODAL —
  const createModal     = document.getElementById('createModal');
  const openCreateBtn   = document.getElementById('addClientBtn');
  const closeCreateBtn  = document.getElementById('closeCreateModal');
  const cancelCreateBtn = document.getElementById('cancelCreate');

  function showCreateModal() { createModal.classList.remove('hidden'); }
  function hideCreateModal() { createModal.classList.add('hidden'); }

  openCreateBtn  .addEventListener('click', showCreateModal);
  closeCreateBtn .addEventListener('click', hideCreateModal);
  cancelCreateBtn.addEventListener('click', hideCreateModal);
  createModal.addEventListener('click', e => {
    if (e.target === createModal) hideCreateModal();
  });

  // — DETAILS / TRANSACTIONS MODAL —
  const detailsModal = document.getElementById('detailsModal');
  const closeDetails = document.getElementById('closeDetailsModal');
  const cancelDetails = document.getElementById('cancelDetails');
  const tabInfoBtn   = document.getElementById('tabInfoBtn');
  const tabTransBtn  = document.getElementById('tabTransBtn');
  const infoPane     = document.getElementById('infoPane');
  const transPane    = document.getElementById('transPane');
  const transBody    = document.getElementById('transTableBody');

  function hideDetailsModal() { detailsModal.classList.add('hidden'); }
  closeDetails .addEventListener('click', hideDetailsModal);
  cancelDetails.addEventListener('click', hideDetailsModal);
  detailsModal.addEventListener('click', e => {
    if (e.target === detailsModal) hideDetailsModal();
  });

  function showPane(which) {
    const isInfo = which === 'info';
    infoPane.classList.toggle('hidden', !isInfo);
    transPane.classList.toggle('hidden', isInfo);
    tabInfoBtn.classList.toggle('border-blue-600', isInfo);
    tabTransBtn.classList.toggle('border-blue-600', !isInfo);
  }
  tabInfoBtn .addEventListener('click', () => showPane('info'));
  tabTransBtn.addEventListener('click', () => showPane('trans'));

  function formatCurrency(val) {
    return 'R$ ' + parseFloat(val).toFixed(2).replace('.', ',');
  }

  function fillTransactions(transactions, clientId) {
    const onlyThis = transactions.filter(tx => String(tx.client_id) === String(clientId));
    transBody.innerHTML = '';
    if (!onlyThis.length) {
      transBody.innerHTML = `
        <tr>
          <td colspan="3" class="p-4 text-center text-gray-500">
            Sem transações
          </td>
        </tr>`;
      return;
    }
    onlyThis.forEach(tx => {
      const tr = document.createElement('tr');
      tr.className = 'border-t';
      tr.innerHTML = `
        <td class="p-2">${new Date(tx.date).toLocaleDateString()}</td>
        <td class="p-2">${tx.type.charAt(0).toUpperCase() + tx.type.slice(1)}</td>
        <td class="p-2 text-right">${formatCurrency(tx.amount)}</td>
      `;
      transBody.appendChild(tr);
    });
  }

  document.querySelectorAll('.client-item').forEach(item => {
    item.addEventListener('click', () => {
      const id = item.dataset.id;
      fetch(`${baseUrl}/clients/show?id=${encodeURIComponent(id)}`, {
        credentials: 'same-origin'
      })
        .then(res => res.json())
        .then(data => {
          if (data.error) {
            return alert(data.error);
          }

          // preencher detalhes
          document.getElementById('detailId').value       = data.id;
          document.getElementById('detailName').value     = data.name;
          document.getElementById('detailAddress').value  = data.address  || '';
          document.getElementById('detailPhone').value    = data.phone    || '';
          document.getElementById('detailLoyalty').textContent  = data.loyalty_points;
          document.getElementById('detailProjects').textContent = data.project_count;

          // preencher somente as transações deste client
          fillTransactions(data.transactions, data.id);

          // mostrar aba de detalhes por padrão
          showPane('info');
          detailsModal.classList.remove('hidden');
        })
        .catch(() => {
          alert('Falha ao carregar detalhes do cliente.');
        });
    });
  });
});
