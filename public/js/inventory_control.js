// public/js/inventory_control.js
document.addEventListener('DOMContentLoaded', function() {
    function toggle(el, show) { el.classList.toggle('hidden', !show); }
  
    // —— Controle de Estoque ——  
    const openCtrl = document.getElementById('openControlModal');
    const ctrlModal = document.getElementById('inventoryControlModal');
    const closeCtrl = document.getElementById('closeControlModal');
    const cancelCtrl = document.getElementById('cancelControlBtn');
    const form = document.getElementById('controlForm');
    const reasonSel = document.getElementById('reasonSelect');
    const customDiv = document.getElementById('customReasonDiv');
    const projDiv = document.getElementById('projectSelectDiv');
    const newItemDiv = document.getElementById('newItemDiv');
    const stockDiv = document.getElementById('stockItemsDiv');
    const itemsData = document.getElementById('itemsData');
    const nameInput = document.getElementById('userNameInput');
  
    if (openCtrl && ctrlModal) {
      openCtrl.addEventListener('click', () => toggle(ctrlModal, true));
      closeCtrl.addEventListener('click', () => { form.reset(); toggle(ctrlModal, false); });
      cancelCtrl.addEventListener('click', () => { form.reset(); toggle(ctrlModal, false); });
      window.addEventListener('click', e => {
        if (e.target === ctrlModal) { form.reset(); toggle(ctrlModal, false); }
      });
    }
  
    // campos dinâmicos
    reasonSel && reasonSel.addEventListener('change', function() {
      const v = this.value;
      toggle(projDiv, v === 'projeto');
      toggle(customDiv, v === 'outros');
      toggle(newItemDiv, v === 'criar');
      toggle(stockDiv, v !== 'criar');
    });
  
    // habilita qty
    document.querySelectorAll('.item-checkbox').forEach(cb => {
      const qty = cb.closest('div').querySelector('.qty-input');
      cb.addEventListener('change', () => {
        qty.disabled = !cb.checked;
        if (!cb.checked) qty.value = 1;
      });
    });
  
    // submit controle
    form && form.addEventListener('submit', function(e) {
      const reason = reasonSel.value;
      const user = nameInput.value.trim();
      if (!user) {
        e.preventDefault(); alert('Preencha seu nome.'); return;
      }
      if (reason === 'criar') {
        const name = document.getElementById('newItemName').value.trim();
        const type = document.getElementById('newItemType').value;
        const qty  = parseInt(document.getElementById('newItemQty').value, 10);
        if (!name || !type || qty < 1) {
          e.preventDefault(); alert('Preencha nome, tipo e quantidade do novo item.'); return;
        }
        itemsData.value = JSON.stringify({ new_item: { name, type, quantity: qty } });
        return;
      }
      const sel = {};
      document.querySelectorAll('.item-checkbox:checked').forEach(cb => {
        const id = cb.value;
        const q = parseInt(cb.closest('div').querySelector('.qty-input').value, 10) || 1;
        sel[id] = q;
      });
      if (!Object.keys(sel).length) {
        e.preventDefault(); alert('Selecione ao menos um item.'); return;
      }
      itemsData.value = JSON.stringify(sel);
    });
  
    // —— Histórico de Estoque ——
    const openHist = document.getElementById('openHistoryModal');
    const histModal = document.getElementById('inventoryHistoryModal');
    const closeHist = document.getElementById('closeHistoryModal');
  
    if (openHist && histModal) {
      openHist.addEventListener('click', () => toggle(histModal, true));
      closeHist.addEventListener('click', () => toggle(histModal, false));
      window.addEventListener('click', e => {
        if (e.target === histModal) toggle(histModal, false);
      });
    }
  
    // expandir/recolher detalhes
    document.querySelectorAll('.history-item').forEach(item => {
      const arrow = item.querySelector('.arrow');
      const details = item.querySelector('.history-details');
      item.addEventListener('click', function() {
        const open = !details.classList.contains('hidden');
        // fecha todos
        document.querySelectorAll('.history-details').forEach(d => d.classList.add('hidden'));
        document.querySelectorAll('.history-item .arrow').forEach(a => a.textContent = '▶');
        if (!open) {
          const id = this.dataset.id;
          fetch(window.location.origin + window.baseUrl + '/inventory/history/details?id=' + id)
            .then(r => r.json())
            .then(data => {
              let html = '<ul class="list-disc pl-5">';
              data.forEach(d => html += `<li>${d.item_name}: ${d.quantity}</li>`);
              html += '</ul>';
              details.innerHTML = html;
              details.classList.remove('hidden');
              arrow.textContent = '▼';
            });
        }
      });
    });
  });
  