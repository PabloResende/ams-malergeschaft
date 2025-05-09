document.addEventListener('DOMContentLoaded', () => {
    const openBtn    = document.getElementById('openTxModalBtn');
    const modal      = document.getElementById('transactionModal');
    const closeBtn   = document.getElementById('closeTxModalBtn');
    const cancelBtn  = document.getElementById('txCancelBtn');
    const form       = document.getElementById('transactionForm');
    const titleEl    = document.getElementById('txModalTitle');
    const saveBtn    = document.getElementById('txSaveBtn');
  
    const idInput    = document.getElementById('txId');
    const typeSel    = document.getElementById('txTypeSelect');
    const dateInput  = document.getElementById('txDateInput');
    const catSel     = document.getElementById('txCategorySelect');
    const amtInput   = document.getElementById('txAmountInput');
    const descInput  = document.getElementById('txDescInput');
    const dueCont    = document.getElementById('dueDateContainer');
    const dueInput   = document.getElementById('txDueDateInput');
  
    // mantém opções originais
    const origOpts = Array.from(catSel.options).map(o => ({
      value: o.value, text: o.textContent, type: o.getAttribute('data-type')
    }));
  
    function openModal(mode, tx) {
      modal.classList.remove('hidden');
      if (mode === 'new') {
        titleEl.textContent = '<?= $langText['new_transaction'] ?>';
        form.action = FINANCE_PREFIX + '/store';
        saveBtn.textContent = '<?= $langText['save'] ?>';
        idInput.value = '';
        typeSel.value = 'income';
        dateInput.value = new Date().toISOString().substr(0,10);
        amtInput.value = '';
        descInput.value = '';
        dueInput.value = '';
      } else {
        titleEl.textContent = '<?= $langText['edit_transaction'] ?? 'Editar Transação' ?>';
        form.action = FINANCE_PREFIX + '/update';
        saveBtn.textContent = '<?= $langText['save_changes'] ?? 'Salvar' ?>';
        idInput.value    = tx.id;
        typeSel.value    = tx.type;
        dateInput.value  = tx.date;
        amtInput.value   = tx.amount;
        descInput.value  = tx.description;
        if (tx.due_date) dueInput.value = tx.due_date;
      }
      toggleDue();
      filterCats();
      if (mode==='edit') catSel.value = tx.category_id;
    }
  
    function closeModal() {
      modal.classList.add('hidden');
    }
  
    function toggleDue() {
      dueCont.classList.toggle('hidden', typeSel.value !== 'debt');
    }
  
    function filterCats() {
      const t = typeSel.value;
      catSel.innerHTML = '';
      origOpts.forEach(o => {
        if (!t || o.type === t) {
          const opt = document.createElement('option');
          opt.value = o.value; opt.textContent = o.text;
          catSel.appendChild(opt);
        }
      });
    }
  
    // abre modal “novo”
    openBtn.addEventListener('click', () => openModal('new'));
  
    // fecha modal
    [closeBtn, cancelBtn].forEach(b => b.addEventListener('click', closeModal));
    modal.addEventListener('click', e => e.target === modal && closeModal());
  
    typeSel.addEventListener('change', () => {
      toggleDue();
      filterCats();
    });
  
    // click em linha para editar
    document.querySelectorAll('.tx-row').forEach(row => {
      row.addEventListener('click', () => {
        const id = row.dataset.txId;
        fetch(`${FINANCE_PREFIX}/edit?id=${id}`)
          .then(r => r.json())
          .then(tx => openModal('edit', tx))
          .catch(console.error);
      });
    });
  });
  