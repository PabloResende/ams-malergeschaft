// public/js/inventory_control.js

document.addEventListener('DOMContentLoaded', () => {
  // Controle de Estoque
  const openCtrlBtn   = document.getElementById('openControlModal');
  const closeCtrlBtn  = document.getElementById('closeControlModal');
  const cancelCtrlBtn = document.getElementById('cancelControlBtn');
  const ctrlModal     = document.getElementById('inventoryControlModal');
  const datetimeInput = document.getElementById('datetimeInput');

  // Histórico de Estoque
  const openHistBtn   = document.getElementById('openHistoryModal');
  const closeHistBtn  = document.getElementById('closeHistoryModal');
  const histModal     = document.getElementById('inventoryHistoryModal');

  // Abre modal de Controle e popula data/hora em fuso Swiss
  openCtrlBtn?.addEventListener('click', () => {
    const now = new Date();
    datetimeInput.value = now.toLocaleString('pt-CH', {
      day: '2-digit', month: '2-digit', year: 'numeric',
      hour: '2-digit', minute: '2-digit', second: '2-digit',
      timeZone: 'Europe/Zurich'
    });
    ctrlModal.classList.remove('hidden');
  });
  [closeCtrlBtn, cancelCtrlBtn].forEach(b => b?.addEventListener('click', () => {
    ctrlModal.classList.add('hidden');
  }));
  ctrlModal?.addEventListener('click', e => {
    if (e.target === ctrlModal) ctrlModal.classList.add('hidden');
  });

  // Abre/fecha modal de Histórico
  openHistBtn?.addEventListener('click', () => histModal.classList.remove('hidden'));
  closeHistBtn?.addEventListener('click', () => histModal.classList.add('hidden'));
  histModal?.addEventListener('click', e => {
    if (e.target === histModal) histModal.classList.add('hidden');
  });

  // Campos condicionais
  const reasonSelect     = document.getElementById('reasonSelect');
  const projectSelectDiv = document.getElementById('projectSelectDiv');
  const customReasonDiv  = document.getElementById('customReasonDiv');
  const newItemDiv       = document.getElementById('newItemDiv');
  reasonSelect?.addEventListener('change', () => {
    const v = reasonSelect.value;
    projectSelectDiv .classList.toggle('hidden', v !== 'projeto');
    customReasonDiv  .classList.toggle('hidden', v !== 'outros');
    newItemDiv       .classList.toggle('hidden', v !== 'criar');
  });

  // Form submission
  document.getElementById('controlForm')?.addEventListener('submit', () => {
    const data = {};
    if (reasonSelect.value === 'criar') {
      data.new_item = {
        name:     document.querySelector('[name=new_item_name]').value,
        type:     document.querySelector('[name=new_item_type]').value,
        quantity: parseInt(document.querySelector('[name=new_item_qty]').value, 10) || 0
      };
    } else {
      document.querySelectorAll('.item-checkbox').forEach(box => {
        if (box.checked) {
          data[box.value] = parseInt(box.parentElement.querySelector('.qty-input').value, 10);
        }
      });
    }
    document.getElementById('itemsData').value = JSON.stringify(data);
  });

  // Histórico: expandir/recolher com detalhes
  document.querySelectorAll('.history-item').forEach(item => {
    const arrow   = item.querySelector('.arrow');
    const details = item.querySelector('.history-details');

    const toggleDetails = () => {
      const id = item.dataset.id;
      if (details.classList.contains('hidden')) {
        fetch(`${baseUrl}/inventory/history/details?id=${id}`)
          .then(r => r.json())
          .then(json => {
            const m = json.movement;
            let html = `
              <div><strong>Operador:</strong> ${m.user_name}</div>
              <div><strong>Data/Hora:</strong> ${m.datetime}</div>
              <div><strong>Motivo:</strong> ${m.reason}</div>
            `;
            if (m.reason === 'projeto' && m.project_name) {
              html += `<div><strong>Projeto:</strong> ${m.project_name}</div>`;
            }
            if (m.custom_reason) {
              html += `<div><strong>Detalhe:</strong> ${m.custom_reason}</div>`;
            }
            html += '<hr class="my-2">';
            if (json.items.length) {
              html += json.items.map(i => `<div>${i.name}: ${i.qty}</div>`).join('');
            } else {
              html += `<div class="text-gray-500">Sem itens registrados.</div>`;
            }
            details.innerHTML = html;
            details.classList.remove('hidden');
            arrow.textContent = '▾';
          })
          .catch(err => {
            details.innerHTML = `<div class="text-red-500">Erro ao carregar detalhes.</div>`;
            details.classList.remove('hidden');
            arrow.textContent = '▾';
            console.error('Erro detalhes histórico:', err);
          });
      } else {
        details.classList.add('hidden');
        arrow.textContent = '▸';
      }
    };

    arrow.addEventListener('click', e => { e.stopPropagation(); toggleDetails(); });
    item.addEventListener('click', e => {
      if (!e.target.closest('.history-details')) toggleDetails();
    });
  });
});
