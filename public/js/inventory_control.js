// public/js/inventory_control.js

document.addEventListener('DOMContentLoaded', function() {
    // Helper para mostrar/ocultar
    const toggle = (el, show) => el.classList.toggle('hidden', !show);
  
    // ----- CONTROLE DE ESTOQUE -----
    const openCtrlBtn   = document.getElementById('openControlModal');
    const ctrlModal     = document.getElementById('inventoryControlModal');
    const closeCtrlBtn  = document.getElementById('closeControlModal');
    const cancelCtrlBtn = document.getElementById('cancelControlBtn');
    const form          = document.getElementById('controlForm');
    const reasonSel     = document.getElementById('reasonSelect');
    const projDiv       = document.getElementById('projectSelectDiv');
    const customDiv     = document.getElementById('customReasonDiv');
    const newItemDiv    = document.getElementById('newItemDiv');
    const stockDiv      = document.getElementById('stockItemsDiv');
    const itemsData     = document.getElementById('itemsData');
    const nameInput     = document.getElementById('userNameInput');
  
    if (openCtrlBtn && ctrlModal) {
      openCtrlBtn.addEventListener('click', () => toggle(ctrlModal, true));
      closeCtrlBtn.addEventListener('click', () => { form.reset(); toggle(ctrlModal, false); });
      cancelCtrlBtn.addEventListener('click', () => { form.reset(); toggle(ctrlModal, false); });
      window.addEventListener('click', function(e) {
        if (e.target === ctrlModal) {
          form.reset();
          toggle(ctrlModal, false);
        }
      });
    }
  
    // Ajusta campos conforme motivo
    if (reasonSel) {
      reasonSel.addEventListener('change', function() {
        const v = this.value;
        toggle(projDiv,    v === 'projeto');
        toggle(customDiv,  v === 'outros');
        toggle(newItemDiv, v === 'criar');
        toggle(stockDiv,   v !== 'criar');
      });
    }
  
    // Habilita/desabilita quantidade nos checkboxes
    document.querySelectorAll('.item-checkbox').forEach(function(cb) {
      const qty = cb.closest('div').querySelector('.qty-input');
      cb.addEventListener('change', function() {
        qty.disabled = !this.checked;
        if (!this.checked) qty.value = 1;
      });
    });
  
    // Valida e monta JSON antes de enviar
    if (form) {
      form.addEventListener('submit', function(e) {
        const reason = reasonSel.value;
        const user   = nameInput.value.trim();
        // valida nome
        if (!user) {
          e.preventDefault();
          alert('Preencha seu nome.');
          return;
        }
        // se criar novo
        if (reason === 'criar') {
          const niName = document.getElementById('newItemName').value.trim();
          const niType = document.getElementById('newItemType').value;
          const niQty  = parseInt(document.getElementById('newItemQty').value, 10);
          if (!niName || !niType || niQty < 1) {
            e.preventDefault();
            alert('Preencha nome, tipo e quantidade do novo item.');
            return;
          }
          itemsData.value = JSON.stringify({
            new_item: { id: 0, name: niName, type: niType, quantity: niQty }
          });
          return;
        }
        // caso movimente itens existentes
        const sel = {};
        document.querySelectorAll('.item-checkbox:checked').forEach(function(cb) {
          const id = cb.value;
          const q  = parseInt(cb.closest('div').querySelector('.qty-input').value, 10) || 1;
          sel[id] = q;
        });
        if (Object.keys(sel).length === 0) {
          e.preventDefault();
          alert('Selecione ao menos um item.');
          return;
        }
        itemsData.value = JSON.stringify(sel);
      });
    }
  
    // ----- HISTÓRICO DE ESTOQUE -----
    const openHistBtn  = document.getElementById('openHistoryModal');
    const histModal    = document.getElementById('inventoryHistoryModal');
    const closeHistBtn = document.getElementById('closeHistoryModal');
  
    if (openHistBtn && histModal) {
      openHistBtn.addEventListener('click', () => toggle(histModal, true));
      closeHistBtn.addEventListener('click', () => toggle(histModal, false));
      window.addEventListener('click', function(e) {
        if (e.target === histModal) {
          toggle(histModal, false);
        }
      });
    }
  
    // Expande / recolhe detalhes e alterna seta
    document.querySelectorAll('.history-item').forEach(function(item) {
      const arrow   = item.querySelector('.arrow');
      const details = item.querySelector('.history-details');
  
      item.addEventListener('click', function() {
        const isOpen = !details.classList.contains('hidden');
        // fecha todos antes
        document.querySelectorAll('.history-details').forEach(d => d.classList.add('hidden'));
        document.querySelectorAll('.arrow').forEach(a => a.textContent = '▸');
  
        if (!isOpen) {
          const id = item.dataset.id;
          fetch(`${window.baseUrl}/inventory/history/details?id=${id}`)
            .then(r => r.json())
            .then(data => {
              let html = '<ul class="list-disc pl-5">';
              data.forEach(d => {
                html += `<li>${d.item_name}: ${d.quantity}</li>`;
              });
              html += '</ul>';
              details.innerHTML = html;
              details.classList.remove('hidden');
              arrow.textContent = '▾';
            });
        }
      });
    });
  });
  