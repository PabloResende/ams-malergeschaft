// public/js/inventory_control.js

console.log('inventory_control.js carregado!', 'document.readyState=', document.readyState);

document.addEventListener('DOMContentLoaded', () => {
  console.log('DOM pronto para manipulação');

  // Botões e modais
  const openCtrlBtn   = document.getElementById('openControlModal');
  const closeCtrlBtn  = document.getElementById('closeControlModal');
  const cancelCtrlBtn = document.getElementById('cancelControlBtn');
  const ctrlModal     = document.getElementById('inventoryControlModal');

  const openHistBtn   = document.getElementById('openHistoryModal');
  const closeHistBtn  = document.getElementById('closeHistoryModal');
  const histModal     = document.getElementById('inventoryHistoryModal');

  console.log('openCtrlBtn=', openCtrlBtn, 'openHistBtn=', openHistBtn);

  if (!openCtrlBtn || !closeCtrlBtn || !cancelCtrlBtn || !ctrlModal) {
    console.error('Falha ao localizar elementos do modal de controle!');
  } else {
    // Abre modal de Controle
    openCtrlBtn.addEventListener('click', () => {
      console.log('clicou em abrir controle');
      ctrlModal.classList.remove('hidden');
    });
    // Fecha modal de Controle
    [closeCtrlBtn, cancelCtrlBtn].forEach(btn => {
      btn.addEventListener('click', () => {
        console.log('clicou em fechar controle');
        ctrlModal.classList.add('hidden');
      });
    });
  }

  if (!openHistBtn || !closeHistBtn || !histModal) {
    console.error('Falha ao localizar elementos do modal de histórico!');
  } else {
    // Abre modal de Histórico
    openHistBtn.addEventListener('click', () => {
      console.log('clicou em abrir histórico');
      histModal.classList.remove('hidden');
    });
    // Fecha modal de Histórico
    closeHistBtn.addEventListener('click', () => {
      console.log('clicou em fechar histórico');
      histModal.classList.add('hidden');
    });
  }

  // Campos condicionais no formulário de controle
  const reasonSelect     = document.getElementById('reasonSelect');
  const projectSelectDiv = document.getElementById('projectSelectDiv');
  const customReasonDiv  = document.getElementById('customReasonDiv');
  const newItemDiv       = document.getElementById('newItemDiv');

  if (reasonSelect) {
    reasonSelect.addEventListener('change', () => {
      const v = reasonSelect.value;
      console.log('motivo alterado para', v);
      projectSelectDiv.classList.toggle('hidden', v !== 'projeto');
      customReasonDiv .classList.toggle('hidden', v !== 'outros');
      newItemDiv      .classList.toggle('hidden', v !== 'criar');
    });
  }

  // Itens e quantidades
  const checkboxes     = document.querySelectorAll('.item-checkbox');
  const itemsDataInput = document.getElementById('itemsData');
  const controlForm    = document.getElementById('controlForm');

  checkboxes.forEach(box => {
    const qtyInput = box.parentElement.querySelector('.qty-input');
    box.addEventListener('change', () => {
      console.log(`checkbox ${box.value} ${box.checked ? 'checked' : 'unchecked'}`);
      qtyInput.disabled = !box.checked;
    });
    qtyInput.addEventListener('input', () => {
      const max = parseInt(box.dataset.max, 10);
      if (qtyInput.value < 1) qtyInput.value = 1;
      if (qtyInput.value > max) qtyInput.value = max;
    });
  });

  if (controlForm) {
    controlForm.addEventListener('submit', () => {
      console.log('submetendo form de controle');
      const data = {};
      if (reasonSelect.value === 'criar') {
        data.new_item = {
          name:     document.getElementById('newItemName').value,
          type:     document.getElementById('newItemType').value,
          quantity: parseInt(document.getElementById('newItemQty').value, 10) || 0
        };
        console.log('novo item:', data.new_item);
      } else {
        checkboxes.forEach(box => {
          if (box.checked) {
            const qty = parseInt(box.parentElement.querySelector('.qty-input').value, 10);
            data[box.value] = qty;
            console.log(`item ${box.value}: quantidade=${qty}`);
          }
        });
      }
      itemsDataInput.value = JSON.stringify(data);
      console.log('JSON enviado:', itemsDataInput.value);
    });
  }

  // Histórico: carrega detalhes via fetch
  const historyItems = document.querySelectorAll('.history-item');
  historyItems.forEach(item => {
    const arrow   = item.querySelector('.arrow');
    const details = item.querySelector('.history-details');
    arrow.addEventListener('click', () => {
      const id = item.dataset.id;
      console.log('buscando detalhes de movimento', id);
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
