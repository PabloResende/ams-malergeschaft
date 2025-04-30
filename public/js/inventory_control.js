// public/js/inventory_control.js
document.addEventListener('DOMContentLoaded', () => {
    const toggle = (el, show) => el.classList.toggle('hidden', !show);
  
    // Modal Elements
    const openCtrlBtn   = document.getElementById('openControlModal');
    const ctrlModal     = document.getElementById('inventoryControlModal');
    const closeCtrlBtn  = document.getElementById('closeControlModal');
    const cancelCtrlBtn = document.getElementById('cancelControlBtn');
  
    const openHistBtn   = document.getElementById('openHistoryModal');
    const histModal     = document.getElementById('inventoryHistoryModal');
    const closeHistBtn  = document.getElementById('closeHistoryModal');
  
    // Form & Fields
    const form          = document.getElementById('controlForm');
    const reasonSel     = document.getElementById('reasonSelect');
    const customDiv     = document.getElementById('customReasonDiv');
    const projDiv       = document.getElementById('projectSelectDiv');
    const newItemDiv    = document.getElementById('newItemDiv');
    const stockDiv      = document.getElementById('stockItemsDiv');
    const itemsData     = document.getElementById('itemsData');
    const nameInput     = document.getElementById('userNameInput');
  
    // Open / Close Control Modal
    if (openCtrlBtn && ctrlModal) {
      openCtrlBtn.addEventListener('click', () => toggle(ctrlModal, true));
      closeCtrlBtn.addEventListener('click', () => toggle(ctrlModal, false));
      cancelCtrlBtn.addEventListener('click', () => {
        form.reset();
        toggle(ctrlModal, false);
      });
      window.addEventListener('click', e => {
        if (e.target === ctrlModal) {
          form.reset();
          toggle(ctrlModal, false);
        }
      });
    }
  
    // Open / Close History Modal
    if (openHistBtn && histModal) {
      openHistBtn.addEventListener('click', () => toggle(histModal, true));
      closeHistBtn.addEventListener('click', () => toggle(histModal, false));
      window.addEventListener('click', e => {
        if (e.target === histModal) toggle(histModal, false);
      });
    }
  
    // Toggle dynamic fields based on motivo
    reasonSel?.addEventListener('change', () => {
      const v = reasonSel.value;
      toggle(projDiv,    v === 'projeto');
      toggle(customDiv,  v === 'outros');
      toggle(newItemDiv, v === 'criar');
      toggle(stockDiv,   v !== 'criar');
    });
  
    // Enable qty inputs when checkbox checked
    document.querySelectorAll('.item-checkbox').forEach(cb => {
      const qty = cb.closest('div').querySelector('.qty-input');
      cb.addEventListener('change', () => {
        qty.disabled = !cb.checked;
        if (!cb.checked) qty.value = 1;
      });
    });
  
    // Form submission
    form?.addEventListener('submit', e => {
      const user   = nameInput.value.trim();
      const reason = reasonSel.value;
  
      // Nome obrigatório
      if (!user) {
        e.preventDefault();
        return alert('Preencha seu nome.');
      }
  
      // Criar novo item
      if (reason === 'criar') {
        const name = document.getElementById('newItemName').value.trim();
        const type = document.getElementById('newItemType').value;
        const qty  = parseInt(document.getElementById('newItemQty').value, 10);
        if (!name || !type || qty < 1) {
          e.preventDefault();
          return alert('Preencha nome, tipo e quantidade do novo item.');
        }
        // JSON with new_item
        itemsData.value = JSON.stringify({
          new_item: { name, type, quantity: qty }
        });
        return; // submit
      }
  
      // Para demais motivos, ao menos 1 item
      const sel = {};
      document.querySelectorAll('.item-checkbox:checked').forEach(cb => {
        const id = cb.value;
        const q  = parseInt(cb.closest('div').querySelector('.qty-input').value, 10) || 1;
        sel[id] = q;
      });
      if (!Object.keys(sel).length) {
        e.preventDefault();
        return alert('Selecione ao menos um item.');
      }
      itemsData.value = JSON.stringify(sel);
    });
  
    // Histórico: expandir detalhes
    document.querySelectorAll('.history-item').forEach(div => {
      div.addEventListener('click', () => {
        const details = div.querySelector('.history-details');
        if (!details.classList.contains('hidden')) {
          // já aberto, fecha
          return details.classList.add('hidden');
        }
        // fecha qualquer outro
        document.querySelectorAll('.history-details').forEach(d => d.classList.add('hidden'));
        // busca e exibe
        const id = div.dataset.id;
        fetch(`${window.location.origin}${'<?= $baseUrl ?>'}/inventory/history/details?id=${id}`)
          .then(r => r.json())
          .then(data => {
            let html = '<ul class="list-disc pl-5">';
            data.forEach(d => html += `<li>${d.item_name}: ${d.quantity}</li>`);
            html += '</ul>';
            details.innerHTML = html;
            details.classList.remove('hidden');
          });
      });
    });
  });
  