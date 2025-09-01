// public/js/inventory_control.js

document.addEventListener('DOMContentLoaded', function() {
  // Modais
  const openControlModal    = document.getElementById('openControlModal');
  const closeControlModal   = document.getElementById('closeControlModal');
  const controlModal        = document.getElementById('inventoryControlModal');
  const cancelControlBtn    = document.getElementById('cancelControlBtn');

  const openHistoryModal    = document.getElementById('openHistoryModal');
  const closeHistoryModal   = document.getElementById('closeHistoryModal');
  const historyModal        = document.getElementById('inventoryHistoryModal');

  const quickModal          = document.getElementById('quickActionsModal');
  const closeQuickModal     = document.getElementById('closeQuickActionsModal');

  // Abrir/fechar modais de controle
  openControlModal.addEventListener('click', () => {
    updateDateTime();
    controlModal.classList.remove('hidden');
  });
  closeControlModal.addEventListener('click', () => controlModal.classList.add('hidden'));
  cancelControlBtn.addEventListener('click', () => controlModal.classList.add('hidden'));

  // Abrir/fechar modal de histórico
  openHistoryModal.addEventListener('click', () => historyModal.classList.remove('hidden'));
  closeHistoryModal.addEventListener('click', () => historyModal.classList.add('hidden'));

  // Fechar quickActions
  closeQuickModal.addEventListener('click', () => quickModal.classList.add('hidden'));

  // Fechar modais ao clicar fora
  [controlModal, historyModal, quickModal].forEach(modal => {
    modal.addEventListener('click', e => {
      if (e.target === modal) modal.classList.add('hidden');
    });
  });

  // Date/time
  const dtInput = document.getElementById('datetimeInput');
  function pad(n) { return n < 10 ? '0' + n : n; }
  function updateDateTime() {
    const now = new Date();
    dtInput.value = `${pad(now.getDate())}/${pad(now.getMonth()+1)}/${now.getFullYear()}, ` +
                    `${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;
  }
  updateDateTime();
  setInterval(updateDateTime, 1000);

  // Seções por motivo
  const reasonSelect   = document.getElementById('reasonSelect');
  const projectDiv     = document.getElementById('projectSelectDiv');
  const newItemDiv     = document.getElementById('newItemDiv');
  const stockDiv       = document.getElementById('stockItemsDiv');
  reasonSelect.addEventListener('change', () => {
    const r = reasonSelect.value;
    projectDiv.classList.toggle('hidden', r !== 'projeto');
    newItemDiv.classList.toggle('hidden', r !== 'criar');
    stockDiv.classList.toggle('hidden', r === 'criar');
    toggleBrandInput();
    updateItemsData();
  });

  // Toggle de campo Marca
  const newTypeSelect = document.getElementById('newItemType');
  const brandDiv      = document.getElementById('brandDiv');
  const newBrandInput = document.querySelector('input[name="new_item_brand"]');
  newTypeSelect.addEventListener('change', () => {
    toggleBrandInput();
    updateItemsData();
  });
  function toggleBrandInput() {
    if (reasonSelect.value === 'criar' && newTypeSelect.value === 'equipment') {
      brandDiv.classList.remove('hidden');
    } else {
      brandDiv.classList.add('hidden');
    }
  }

  // Checkboxes e qty inputs para itens existentes
  const itemCheckboxes = Array.from(document.querySelectorAll('.item-checkbox'));
  const qtyInputs      = Array.from(document.querySelectorAll('.qty-input'));
  const itemsDataInput = document.getElementById('itemsData');

  itemCheckboxes.forEach((cb, i) => {
    const inp = qtyInputs[i];
    cb.addEventListener('change', () => {
      inp.disabled = !cb.checked;
      updateItemsData();
    });
    inp.addEventListener('input', updateItemsData);
  });

  // Campos de novo item
  const newNameInput    = document.querySelector('input[name="new_item_name"]');
  const newQtyInput     = document.querySelector('input[name="new_item_qty"]');
  const newDescTextarea = document.querySelector('textarea[name="new_item_description"]');
  [newNameInput, newQtyInput, newDescTextarea, newBrandInput].forEach(el => {
    if (el) el.addEventListener('input', updateItemsData);
  });

  // Monta o payload JSON
  function updateItemsData() {
    const data = {};

    // itens existentes
    itemCheckboxes.forEach((cb, i) => {
      const q = parseInt(qtyInputs[i].value, 10) || 0;
      if (cb.checked && q > 0) data[cb.value] = q;
    });

    // novo item
    if (reasonSelect.value === 'criar') {
      data.new_item = {
        name:        newNameInput    ? newNameInput.value.trim() : '',
        type:        newTypeSelect   ? newTypeSelect.value : '',
        quantity:    newQtyInput     ? parseInt(newQtyInput.value,10) || 0 : 0,
        brand:       newBrandInput   ? newBrandInput.value.trim() : '',
        description: newDescTextarea ? newDescTextarea.value.trim() : ''
      };
    }

    itemsDataInput.value = JSON.stringify(data);
  }

  // Garante JSON atualizado antes de enviar
  document.getElementById('controlForm')
          .addEventListener('submit', updateItemsData);

  // Toggle histórico (detalhes)
  document.querySelectorAll('.toggleHistory').forEach(btn => {
    btn.addEventListener('click', () => {
      const item = btn.closest('.history-item');
      const div  = item.querySelector('.history-details');
      const id   = item.dataset.id;
      if (div.classList.contains('hidden')) {
        fetch(`${baseUrl}/inventory/history/details?id=${id}`)
          .then(r => r.json())
          .then(json => {
            let html = `<p><strong>Operador:</strong> ${json.movement.user_name}</p>`;
            html    += `<p><strong>Data:</strong> ${json.movement.datetime}</p>`;
            html    += `<p><strong>Motivo:</strong> ${json.movement.reason}</p>`;
            if (json.items.length) {
              html += '<ul>';
              json.items.forEach(it => html += `<li>${it.name}: ${it.qty}</li>`);
              html += '</ul>';
            }
            div.innerHTML = html;
            div.classList.remove('hidden');
            btn.textContent = '▾';
          });
      } else {
        div.classList.add('hidden');
        btn.textContent = '▸';
      }
    });
  });

  // Ações rápidas + QR Code
  document.querySelectorAll('.inventory-card').forEach(card => {
    card.addEventListener('click', () => {
      const id   = card.dataset.id;
      const name = card.querySelector('h3').textContent.trim();
      document.getElementById('quickActionsTitle').textContent = name;
      document.getElementById('quickActionsId').value         = id;

      let base = window.baseUrl || '';
      if (base.endsWith('/')) base = base.slice(0,-1);
      const detailUrl = /^https?:\/\//.test(`${base}/inventory?detailId=${id}`)
                        ? `${base}/inventory?detailId=${id}`
                        : `${window.location.origin}${base}/inventory?detailId=${id}`;

      const apiUrl = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(detailUrl)}`;
      const qrImg      = document.getElementById('qrImg');
      const downloadQr = document.getElementById('downloadQr');
      qrImg.src           = apiUrl;
      downloadQr.href     = apiUrl;
      downloadQr.download = `qr-item-${id}.png`;
      document.getElementById('qrContainer').classList.remove('hidden');
      quickModal.classList.remove('hidden');
    });
  });

  // Excluir item (AJAX + fallback)
  document.getElementById('quickDelete').addEventListener('click', () => {
    const id = document.getElementById('quickActionsId').value;
    if (!confirm('Deseja realmente excluir este item?')) return;
    fetch(`${baseUrl}/inventory/delete-ajax`, {
      method: 'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ id })
    })
    .then(r=>r.json())
    .then(res=> res.success ? location.reload() : location.href=`${baseUrl}/inventory/delete?id=${id}`)
    .catch(()=> location.href=`${baseUrl}/inventory/delete?id=${id}`);
  });

  // Adicionar quantidade (quickAdd)
  document.getElementById('quickAdd').addEventListener('click', () => {
    const id  = document.getElementById('quickActionsId').value;
    const qty = parseInt(document.getElementById('quickAddQty').value,10);
    if (qty < 1) return;
    fetch(`${baseUrl}/inventory/add-quantity-ajax`, {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ id, qty })
    }).then(r=>r.json()).then(res=>{ if(res.success) location.reload(); });
  });

  // Salvar descrição (quickDescSave)
  document.getElementById('quickDescSave').addEventListener('click', ()=>{
    const id   = document.getElementById('quickActionsId').value;
    const desc = document.getElementById('quickDescTxt').value.trim();
    fetch(`${baseUrl}/inventory/update-description-ajax`, {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ id, description: desc })
    }).then(r=>r.json()).then(res=>{ if(res.success) location.reload(); });
  });
});
