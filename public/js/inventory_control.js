// public/js/inventory_control.js

document.addEventListener('DOMContentLoaded', () => {
    const toggle = (el, show) => el.classList.toggle('hidden', !show);
  
    // Modais
    const openCtrl  = document.getElementById('openControlModal');
    const closeCtrl = document.getElementById('closeControlModal');
    const cancel    = document.getElementById('cancelControlBtn');
    const ctrlModal = document.getElementById('inventoryControlModal');
    const openHist  = document.getElementById('openHistoryModal');
    const closeHist = document.getElementById('closeHistoryModal');
    const histModal = document.getElementById('inventoryHistoryModal');
  
    [openCtrl, closeCtrl, cancel].forEach(btn => {
      if (!btn) return;
      btn.addEventListener('click', () => {
        toggle(ctrlModal, btn === openCtrl);
        if (btn !== openCtrl) document.getElementById('controlForm').reset();
      });
    });
    window.addEventListener('click', e => {
      if (e.target === ctrlModal) {
        toggle(ctrlModal, false);
        document.getElementById('controlForm').reset();
      }
      if (e.target === histModal) {
        toggle(histModal, false);
      }
    });
    openHist?.addEventListener('click', () => toggle(histModal, true));
    closeHist?.addEventListener('click', () => toggle(histModal, false));
  
    // Controle de Estoque
    const reasonSel  = document.getElementById('reasonSelect');
    const projDiv    = document.getElementById('projectSelectDiv');
    const customDiv  = document.getElementById('customReasonDiv');
    const newItemDiv = document.getElementById('newItemDiv');
    const stockDiv   = document.getElementById('stockItemsDiv');
    const form       = document.getElementById('controlForm');
    const itemsData  = document.getElementById('itemsData');
  
    reasonSel?.addEventListener('change', e => {
      const v = e.target.value;
      toggle(projDiv,    v === 'projeto');
      toggle(customDiv,  v === 'outros');
      toggle(newItemDiv, v === 'criar');
      toggle(stockDiv,   v !== 'criar');
    });
  
    document.querySelectorAll('.item-checkbox').forEach(cb => {
      const qty = cb.closest('div').querySelector('.qty-input');
      cb.addEventListener('change', () => {
        qty.disabled = !cb.checked;
        if (!cb.checked) qty.value = 1;
      });
    });
  
    form?.addEventListener('submit', e => {
      const user = document.getElementById('userNameInput').value.trim();
      if (!user) {
        alert('Preencha seu nome.');
        e.preventDefault();
        return;
      }
  
      const reason = reasonSel.value;
      if (reason === 'criar') {
        const niName = document.getElementById('newItemName').value.trim();
        const niType = document.getElementById('newItemType').value;
        const niQty  = parseInt(document.getElementById('newItemQty').value, 10);
        if (!niName || !niType || niQty < 1) {
          alert('Preencha nome, tipo e quantidade do novo item.');
          e.preventDefault();
          return;
        }
        itemsData.value = JSON.stringify({
          new_item: {
            id: 0,
            name: niName,
            type: niType,
            quantity: niQty
          }
        });
        return;
      }
  
      const sel = {};
      document.querySelectorAll('.item-checkbox:checked').forEach(cb => {
        const id = cb.value;
        const q  = parseInt(cb.closest('div').querySelector('.qty-input').value, 10) || 1;
        sel[id] = q;
      });
      if (Object.keys(sel).length === 0) {
        alert('Selecione ao menos um item.');
        e.preventDefault();
        return;
      }
      itemsData.value = JSON.stringify(sel);
    });
  
    // Histórico: expande detalhes
    document.querySelectorAll('.history-item').forEach(item => {
      const arrow   = item.querySelector('.arrow');
      const details = item.querySelector('.history-details');
  
      item.addEventListener('click', () => {
        const isOpen = !details.classList.contains('hidden');
        // fecha todos
        document.querySelectorAll('.history-details').forEach(d => d.classList.add('hidden'));
        document.querySelectorAll('.arrow').forEach(a => a.textContent = '▸');
  
        if (!isOpen) {
          const id = item.dataset.id;
          fetch(`${window.baseUrl}/inventory/history/details?id=${id}`)
            .then(r => r.json())
            .then(data => {
              let html = `
                <p><strong>Motivo:</strong> ${data.master.reason}</p>
                ${data.master.custom_reason
                  ? `<p><strong>Detalhe:</strong> ${data.master.custom_reason}</p>`
                  : ''}
                ${data.master.project_name
                  ? `<p><strong>Projeto:</strong> ${data.master.project_name}</p>`
                  : ''}
                <p><strong>Itens:</strong></p>
                <ul class="list-disc pl-5">`;
              data.details.forEach(d => {
                html += `<li>${d.item_name}: ${d.quantity}</li>`;
              });
              html += `</ul>`;
              details.innerHTML = html;
              details.classList.remove('hidden');
              arrow.textContent = '▾';
            });
        }
      });
    });
  });
  