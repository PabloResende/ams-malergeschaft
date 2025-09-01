// public/js/cars.js

document.addEventListener('DOMContentLoaded', () => {
  // Base URL e traduções
  const baseUrl = window.baseUrl;
  const langText = window.langText || {};

  // Modais
  const createModal   = document.getElementById('carCreateModal');
  const detailModal   = document.getElementById('carDetailModal');
  const histModal     = document.getElementById('usageHistoryModal');

  // Botões de abrir/fechar
  const openCreateBtn = document.getElementById('openCarCreateBtn');
  const closeCreate   = document.getElementById('closeCarCreateModal');
  const closeDetail   = document.getElementById('closeCarDetailModal');
  const openHistBtn   = document.getElementById('openUsageHistoryBtn');
  const closeHist     = document.getElementById('closeUsageHistoryModal');

  // Elementos de interação
  const carCards       = document.querySelectorAll('.car-card');
  const deleteBtn      = document.getElementById('deleteCarBtn');
  const detailCarId    = document.getElementById('detailCarId');
  const usageForm      = document.getElementById('usageForm');
  const stopsContainer = document.getElementById('stopsContainer');
  const addStopBtn     = document.getElementById('addStopBtn');
  let stopCount        = stopsContainer ? stopsContainer.querySelectorAll('input[name^="stops"]').length : 0;

  // Abre/fecha modais
  openCreateBtn.addEventListener('click',  () => createModal.classList.remove('hidden'));
  closeCreate .addEventListener('click',  () => createModal.classList.add('hidden'));
  openHistBtn .addEventListener('click',  () => histModal.classList.remove('hidden'));
  closeHist   .addEventListener('click',  () => histModal.classList.add('hidden'));
  closeDetail .addEventListener('click',  () => detailModal.classList.add('hidden'));

  // Fecha modal ao clicar fora do conteúdo
  [createModal, detailModal, histModal].forEach(modal => {
    modal.addEventListener('click', e => {
      if (e.target === modal) modal.classList.add('hidden');
    });
  });

  // Carrega detalhes do carro e abre modal
  carCards.forEach(card => {
    card.addEventListener('click', async () => {
      const id = card.dataset.id;
      detailModal.classList.remove('hidden');
      detailModal.querySelector('#carDetailTitle').textContent = langText['loading'] || 'Carregando...';
      detailCarId.value = id;

      try {
        const res = await fetch(`${baseUrl}/cars/get?id=${id}`);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const car = await res.json();

        // Preenche campos
        detailModal.querySelector('#carDetailTitle').textContent =
          `${car.manufacturer} ${car.model} (${car.year})`;
        ['manufacturer','model','year','plate','mileage','color']
          .forEach(f => {
            const inp = detailModal.querySelector(`#detail_${f}`);
            if (inp) inp.value = car[f] ?? '';
          });

        // Atualiza hidden do form de uso
        const hidden = usageForm.querySelector('input[name="car_id"]');
        if (hidden) hidden.value = id;

      } catch (err) {
        console.error('Erro ao buscar detalhes:', err);
        detailModal.querySelector('#carDetailTitle').textContent =
          langText['load_error'] || 'Erro ao carregar detalhes.';
      }
    });
  });

  // Excluir veículo
  deleteBtn.addEventListener('click', () => {
    const id = detailCarId.value;
    if (!id || !confirm(langText['confirm_delete'] || 'Excluir veículo?')) return;
    fetch(`${baseUrl}/cars/delete?id=${id}`)
      .then(() => {
        detailModal.classList.add('hidden');
        window.location.reload();
      })
      .catch(err => console.error('Erro ao excluir:', err));
  });

  // Abas no modal de detalhes
  document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const tab = btn.dataset.tab;
      document.querySelectorAll('.tab-pane').forEach(p => p.classList.add('hidden'));
      document.getElementById(`pane_${tab}`).classList.remove('hidden');
      document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('border-blue-500','font-bold'));
      btn.classList.add('border-blue-500','font-bold');
    });
  });
  // Inicializa na aba "general"
  const firstTab = document.querySelector('.tab-btn[data-tab="general"]');
  if (firstTab) firstTab.click();

  // Adicionar paradas dinamicamente
  addStopBtn.addEventListener('click', () => {
    stopCount++;
    const row = document.createElement('div');
    row.className = 'grid grid-cols-3 gap-2 items-end';
    row.innerHTML = `
      <div>
        <label>${langText['stops'] || 'Litros'}</label>
        <input name="stops[${stopCount}]" type="number" step="0.1" min="0" required class="w-full border rounded p-2">
      </div>
      <div>
        <label>${langText['cost'] || 'Custo'}</label>
        <input name="costs[${stopCount}]" type="number" step="0.01" min="0" required class="w-full border rounded p-2">
      </div>
      <div>
        <label>${langText['receipt'] || 'Comprovante'}</label>
        <input name="receipts[${stopCount}]" type="file" accept="image/png,application/pdf" class="w-full">
      </div>
    `;
    stopsContainer.appendChild(row);
  });

  // Registrar uso e recarregar página
  usageForm.addEventListener('submit', e => {
    e.preventDefault();
    const fd = new FormData(usageForm);
    fetch(usageForm.action, { method: 'POST', body: fd })
      .then(res => {
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return res.json();
      })
      .then(() => window.location.reload())
      .catch(err => {
        console.error('Erro ao salvar uso:', err);
        alert(langText['save_error'] || 'Erro ao registrar uso.');
      });
  });

  // Toggle detalhes no histórico
  histModal.querySelectorAll('.toggleHistory').forEach(btn => {
    btn.addEventListener('click', () => {
      const details = btn.closest('.history-item').querySelector('.history-details');
      if (!details) return;
      details.classList.toggle('hidden');
      btn.textContent = details.classList.contains('hidden') ? '▸' : '▾';
    });
  });
});
