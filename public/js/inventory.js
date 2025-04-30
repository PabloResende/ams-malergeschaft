document.addEventListener('DOMContentLoaded', () => {
  // helper
  const toggle = (el, show) => el.classList.toggle('hidden', !show);

  // — Criação de Inventário —
  (()=>{
    const addInvBtn   = document.getElementById('addInventoryBtn');
    const invModal    = document.getElementById('inventoryModal');
    const closeInvBtn = document.getElementById('closeInventoryModal');
    if(!addInvBtn||!invModal) return;
    addInvBtn.addEventListener('click',  ()=> toggle(invModal,true));
    closeInvBtn.addEventListener('click', ()=> toggle(invModal,false));
    window.addEventListener('click', e=> { if(e.target===invModal) toggle(invModal,false) });
  })();

  // — Edição de Inventário —
  (()=>{
    const editBtns        = document.querySelectorAll('.editInventoryBtn');
    const invEditModal    = document.getElementById('inventoryEditModal');
    const closeInvEditBtn = document.getElementById('closeInventoryEditModal');
    if(!invEditModal) return;
    editBtns.forEach(btn=>{
      btn.addEventListener('click', ()=>{
        document.getElementById('editInventoryId').value       = btn.dataset.id;
        document.getElementById('editInventoryType').value     = btn.dataset.type;
        document.getElementById('editInventoryName').value     = btn.dataset.name;
        document.getElementById('editInventoryQuantity').value = btn.dataset.quantity;
        toggle(invEditModal,true);
      });
    });
    closeInvEditBtn?.addEventListener('click', ()=> toggle(invEditModal,false));
    window.addEventListener('click', e=> { if(e.target===invEditModal) toggle(invEditModal,false) });
  })();

  // — Controle de Estoque —
  (()=>{
    const
      openCtrlBtn   = document.getElementById('openControlModal'),
      ctrlModal     = document.getElementById('inventoryControlModal'),
      closeCtrlBtn  = document.getElementById('closeControlModal'),
      cancelCtrlBtn = document.getElementById('cancelControlBtn'),
      reasonSel     = document.getElementById('reasonSelect'),
      customDiv     = document.getElementById('customReasonDiv'),
      projDiv       = document.getElementById('projectSelectDiv'),
      controlForm   = document.getElementById('controlForm'),
      itemsData     = document.getElementById('itemsData'),
      nameInput     = document.getElementById('userNameInput');

    if(openCtrlBtn && ctrlModal){
      openCtrlBtn.addEventListener('click',  ()=> toggle(ctrlModal,true));
      closeCtrlBtn.addEventListener('click', ()=> toggle(ctrlModal,false));
      cancelCtrlBtn.addEventListener('click',()=> toggle(ctrlModal,false));
      window.addEventListener('click', e=> { if(e.target===ctrlModal) toggle(ctrlModal,false) });
    }

    // toggle campos de motivo
    reasonSel?.addEventListener('change', ()=>{
      toggle(customDiv, reasonSel.value==='outros');
      toggle(projDiv,   reasonSel.value==='projeto');
    });

    // habilita qty-input
    document.querySelectorAll('.item-checkbox').forEach(cb=>{
      const qty = cb.closest('div').querySelector('.qty-input');
      cb.addEventListener('change', ()=> { qty.disabled = !cb.checked; });
    });

    // valida e monta JSON antes de enviar
    controlForm?.addEventListener('submit', e=>{
      const data = {};
      document.querySelectorAll('.item-checkbox:checked').forEach(cb=>{
        const id  = cb.value;
        const q   = parseInt(cb.closest('div').querySelector('.qty-input').value,10)||1;
        data[id] = q;
      });
      if(nameInput.value.trim()==='' || Object.keys(data).length===0){
        e.preventDefault();
        alert('Preencha seu nome e selecione ao menos um item');
        return;
      }
      itemsData.value = JSON.stringify(data);
    });
  })();

  // — Histórico de Estoque —
  (()=>{
    const
      openHistBtn  = document.getElementById('openHistoryModal'),
      histModal    = document.getElementById('inventoryHistoryModal'),
      closeHistBtn = document.getElementById('closeHistoryModal');

    if(openHistBtn && histModal){
      openHistBtn.addEventListener('click',  ()=> toggle(histModal,true));
      closeHistBtn.addEventListener('click', ()=> toggle(histModal,false));
      window.addEventListener('click', e=> { if(e.target===histModal) toggle(histModal,false) });

      document.querySelectorAll('.history-item').forEach(li=>{
        li.addEventListener('click', ()=>{
          fetch(`${window.location.origin}/ams-malergeschaft/public/inventory/history/details?id=${li.dataset.id}`)
            .then(r=>r.json())
            .then(data=>{
              let html = '<h4 class="font-semibold mb-2">Detalhes</h4><ul class="list-disc pl-5">';
              data.forEach(d=> html+=`<li>${d.item_name}: ${d.quantity}</li>`);
              html += '</ul>';
              const det = document.getElementById('historyDetails');
              det.innerHTML = html;
              det.classList.remove('hidden');
            });
        });
      });
    }
  })();

});
