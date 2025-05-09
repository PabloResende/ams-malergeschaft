// public/js/inventory_control.js

// DEBUG TEMP: confirma que é a versão certa
console.log('🚀 inventory_control.js carregado em:', new Date());

// Ao carregar o DOM
document.addEventListener('DOMContentLoaded', () => {
  console.log('DOM pronto para manipulação');

  // Elementos de Controle de Estoque
  const openCtrlBtn   = document.getElementById('openControlModal');
  const closeCtrlBtn  = document.getElementById('closeControlModal');
  const cancelCtrlBtn = document.getElementById('cancelControlBtn');
  const ctrlModal     = document.getElementById('inventoryControlModal');
  const datetimeInput = document.getElementById('datetimeInput');

  // Elementos de Histórico de Estoque
  const openHistBtn   = document.getElementById('openHistoryModal');
  const closeHistBtn  = document.getElementById('closeHistoryModal');
  const histModal     = document.getElementById('inventoryHistoryModal');

  // --- Modal de Controle ---
  if (openCtrlBtn && closeCtrlBtn && cancelCtrlBtn && ctrlModal) {
    openCtrlBtn.addEventListener('click', () => {
      // Popula data/hora no formato pt-BR
      const now = new Date();
      datetimeInput.value = now.toLocaleString('pt-BR', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit', second: '2-digit'
      });
      ctrlModal.classList.remove('hidden');
    });
    [closeCtrlBtn, cancelCtrlBtn].forEach(btn =>
      btn.addEventListener('click', () => ctrlModal.classList.add('hidden'))
    );
    ctrlModal.addEventListener('click', e => {
      if (e.target === ctrlModal) ctrlModal.classList.add('hidden');
    });
  }

  // --- Modal de Histórico ---
  if (openHistBtn && closeHistBtn && histModal) {
    openHistBtn.addEventListener('click', () => histModal.classList.remove('hidden'));
    closeHistBtn.addEventListener('click', () => histModal.classList.add('hidden'));
    histModal.addEventListener('click', e => {
      if (e.target === histModal) histModal.classList.add('hidden');
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
      projectSelectDiv.classList.toggle('hidden', v !== 'projeto');
      customReasonDiv .classList.toggle('hidden', v !== 'outros');
      newItemDiv      .classList.toggle('hidden', v !== 'criar');
    });
  }

  // Itens e quantidades de controle
  const checkboxes     = document.querySelectorAll('.item-checkbox');
  const itemsDataInput = document.getElementById('itemsData');
  const controlForm    = document.getElementById('controlForm');

  checkboxes.forEach(box => {
    const qtyInput = box.parentElement.querySelector('.qty-input');
    box.addEventListener('change', () => qtyInput.disabled = !box.checked);
    qtyInput.addEventListener('input', () => {
      const max = parseInt(box.dataset.max, 10);
      if (qtyInput.value < 1) qtyInput.value = 1;
      if (qtyInput.value > max) qtyInput.value = max;
    });
  });

  if (controlForm) {
    controlForm.addEventListener('submit', () => {
      const data = {};
      if (reasonSelect.value === 'criar') {
        data.new_item = {
          name:     document.getElementById('newItemName').value,
          type:     document.getElementById('newItemType').value,
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
  }

  // --- Histórico: expandir/recolher ---
  const historyItems = document.querySelectorAll('.history-item');
  historyItems.forEach(item => {
    const arrow   = item.querySelector('.arrow');
    const details = item.querySelector('.history-details');

    const toggleDetails = () => {
      const id = item.dataset.id;
      console.log('⟳ toggleDetails para movimento', id, 'hidden=', details.classList.contains('hidden'));

      if (details.classList.contains('hidden')) {
        fetch(`${window.baseUrl}/inventory/history/details?id=${id}`)
          .then(res => {
            console.log('Fetch status:', res.status);
            if (!res.ok) throw new Error(`Status ${res.status}`);
            return res.json();
          })
          .then(json => {
            // se vier { items: [...] } usa json.items, senão usa json direto
            const arr = Array.isArray(json) ? json : (json.items || []);
            console.log('✔ dados recebidos:', arr);
            details.innerHTML = arr.map(i =>
              `<div>${i.name}: ${i.qty}</div>`
            ).join('') || '<div class="text-gray-500">Sem detalhes.</div>';
            details.classList.remove('hidden');
            arrow.textContent = '▾';
          })
          .catch(err => {
            console.error('❌ Erro ao carregar detalhes de histórico:', err);
            details.innerHTML = `<div class="text-red-500">Erro ao carregar detalhes.</div>`;
            details.classList.remove('hidden');
            arrow.textContent = '▾';
          });
      } else {
        details.classList.add('hidden');
        arrow.textContent = '▸';
      }
    };

    arrow.addEventListener('click', e => {
      e.stopPropagation();
      toggleDetails();
    });
    item.addEventListener('click', e => {
      if (!e.target.closest('.history-details')) toggleDetails();
    });
  });

});
