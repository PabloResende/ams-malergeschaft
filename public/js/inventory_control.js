// public/js/inventory_control.js

// Formata números com zero à esquerda
function pad(n) {
  return n < 10 ? '0' + n : n;
}

// Retorna timestamp local em "YYYY-MM-DD HH:mm:ss"
function getLocalTimestamp() {
  const d = new Date();
  return d.getFullYear() + '-' +
    pad(d.getMonth() + 1) + '-' +
    pad(d.getDate()) + ' ' +
    pad(d.getHours()) + ':' +
    pad(d.getMinutes()) + ':' +
    pad(d.getSeconds());
}

document.addEventListener('DOMContentLoaded', () => {
  const openCtrlBtn   = document.getElementById('openControlModal');
  const closeCtrlBtn  = document.getElementById('closeControlModal');
  const cancelCtrlBtn = document.getElementById('cancelControlBtn');
  const ctrlModal     = document.getElementById('inventoryControlModal');
  const dtInput       = document.getElementById('datetimeInput');

  const reasonSelect      = document.getElementById('reasonSelect');
  const projectSelectDiv  = document.getElementById('projectSelectDiv');
  const customReasonDiv   = document.getElementById('customReasonDiv');
  const newItemDiv        = document.getElementById('newItemDiv');
  const stockItemsDiv     = document.getElementById('stockItemsDiv');
  const checkboxes        = document.querySelectorAll('.item-checkbox');
  const itemsDataInput    = document.getElementById('itemsData');

  const openHistBtn   = document.getElementById('openHistoryModal');
  const closeHistBtn  = document.getElementById('closeHistoryModal');
  const histModal     = document.getElementById('inventoryHistoryModal');
  const historyItems  = document.querySelectorAll('.history-item');

  // Abre modal de controle
  openCtrlBtn.addEventListener('click', () => {
    dtInput.value = getLocalTimestamp();
    ctrlModal.classList.remove('hidden');
  });

  // Fecha modal de controle
  [closeCtrlBtn, cancelCtrlBtn].forEach(btn =>
    btn.addEventListener('click', () => ctrlModal.classList.add('hidden'))
  );

  // Mostra campos conforme motivo
  reasonSelect.addEventListener('change', () => {
    const v = reasonSelect.value;
    projectSelectDiv.classList.toggle('hidden', v !== 'projeto');
    customReasonDiv .classList.toggle('hidden', v !== 'outros');
    newItemDiv      .classList.toggle('hidden', v !== 'criar');
  });

  // Habilita quantidade quando marcar checkbox
  checkboxes.forEach(box => {
    const qtyInput = box.parentElement.querySelector('.qty-input');
    box.addEventListener('change', () => {
      qtyInput.disabled = !box.checked;
    });
    qtyInput.addEventListener('input', () => {
      const max = parseInt(box.dataset.max, 10);
      if (qtyInput.value < 1) qtyInput.value = 1;
      if (qtyInput.value > max) qtyInput.value = max;
    });
  });

  // Monta JSON de items antes de submeter
  document.getElementById('controlForm').addEventListener('submit', e => {
    const data = {};
    if (reasonSelect.value === 'criar') {
      data.new_item = {
        name: document.getElementById('newItemName').value,
        type: document.getElementById('newItemType').value,
        quantity: parseInt(document.getElementById('newItemQty').value, 10) || 0
      };
    } else {
      checkboxes.forEach(box => {
        if (box.checked) {
          const qty = parseInt(box.parentElement.querySelector('.qty-input').value, 10);
          data[box.value] = qty;
        }
      });
    }
    itemsDataInput.value = JSON.stringify(data);
  });

  // Modal de histórico
  openHistBtn.addEventListener('click', () => histModal.classList.remove('hidden'));
  closeHistBtn.addEventListener('click', () => histModal.classList.add('hidden'));

  // Toggle detalhes nas movimentações
  historyItems.forEach(item => {
    const arrow = item.querySelector('.arrow');
    const details = item.querySelector('.history-details');
    arrow.addEventListener('click', () => {
      const id = item.dataset.id;
      if (details.classList.contains('hidden')) {
        fetch(`${window.baseUrl}/inventory/history/details?id=${id}`)
          .then(r => r.json())
          .then(json => {
            details.innerHTML = json.items.map(i =>
              `<div>${i.name}: ${i.qty}</div>`
            ).join('');
            details.classList.remove('hidden');
            arrow.textContent = '▾';
          });
      } else {
        details.classList.add('hidden');
        arrow.textContent = '▸';
      }
    });
  });
});
