// public/js/finance.js
(function() {
  const FINANCE_PREFIX = window.FINANCE_PREFIX;
  const STR = window.FINANCE_STR;

  document.addEventListener('DOMContentLoaded', () => {
    // Elementos
    const openBtn     = document.getElementById('openTxModalBtn');
    const modal       = document.getElementById('transactionModal');
    const closeBtn    = document.getElementById('closeTxModalBtn');
    const cancelBtn   = document.getElementById('txCancelBtn');
    const form        = document.getElementById('transactionForm');
    const titleEl     = document.getElementById('txModalTitle');
    const saveBtn     = document.getElementById('txSaveBtn');

    const idInput     = document.getElementById('txId');
    const typeSel     = document.getElementById('txTypeSelect');
    const dateInput   = document.getElementById('txDateInput');
    const catSel      = document.getElementById('txCategorySelect');
    const amtInput    = document.getElementById('txAmountInput');
    const descInput   = document.getElementById('txDescInput');
    const dueCont     = document.getElementById('dueDateContainer');
    const dueInput    = document.getElementById('txDueDateInput');
    const attachList  = document.getElementById('txAttachments');

    // guarda estado original das opções de categoria
    const origOpts = Array.from(catSel.options).map(o => ({
      value: o.value,
      text: o.textContent,
      type:  o.getAttribute('data-type')
    }));

    // mostrar/ocultar modal
    function toggleModal(show) {
      modal.classList.toggle('hidden', !show);
    }
    // mostrar/ocultar campo de vencimento
    function toggleDue() {
      dueCont.classList.toggle('hidden', typeSel.value !== 'debt');
    }
    // filtrar categorias conforme tipo
    function filterCategories() {
      const t = typeSel.value;
      catSel.innerHTML = '';
      origOpts.forEach(o => {
        if (!t || o.type === t) {
          const opt = document.createElement('option');
          opt.value = o.value;
          opt.textContent = o.text;
          catSel.appendChild(opt);
        }
      });
    }

    // abre modal em modo "novo"
    function openModalNew() {
      titleEl.textContent = STR.newTransaction;
      form.action         = FINANCE_PREFIX + '/store';
      saveBtn.textContent = STR.save;
      idInput.value       = '';
      typeSel.value       = 'income';
      dateInput.value     = new Date().toISOString().slice(0,10);
      amtInput.value      = '';
      descInput.value     = '';
      dueInput.value      = '';
      attachList.innerHTML= '';
      toggleDue();
      filterCategories();
      toggleModal(true);
    }

    // abre modal em modo "editar" populando todos os campos
    function openModalEdit(tx) {
      titleEl.textContent = STR.editTransaction;
      form.action         = FINANCE_PREFIX + '/update';
      saveBtn.textContent = STR.saveChanges;

      idInput.value       = tx.id;
      typeSel.value       = tx.type;
      dateInput.value     = tx.date;
      amtInput.value      = tx.amount;
      descInput.value     = tx.description;
      dueInput.value      = tx.due_date || '';
      attachList.innerHTML= '';

      // popula lista de comprovantes
      if (Array.isArray(tx.attachments)) {
        tx.attachments.forEach(a => {
          const li  = document.createElement('li');
          const ael = document.createElement('a');
          ael.href       = FINANCE_PREFIX.replace('/finance','') + '/' + a.file_path;
          ael.textContent= a.file_path.split('/').pop();
          ael.target     = '_blank';
          li.appendChild(ael);
          attachList.appendChild(li);
        });
      }

      toggleDue();
      filterCategories();
      catSel.value = tx.category_id;
      toggleModal(true);
    }

    // eventos de abertura/fechamento
    openBtn.addEventListener('click', openModalNew);
    closeBtn.addEventListener('click', () => toggleModal(false));
    cancelBtn.addEventListener('click', () => toggleModal(false));
    modal.addEventListener('click', e => {
      if (e.target === modal) toggleModal(false);
    });

    // ao mudar tipo, ajusta vencimento e categorias
    typeSel.addEventListener('change', () => {
      toggleDue();
      filterCategories();
    });

    // anexa o clique a cada linha
    document.querySelectorAll('.tx-row').forEach(row => {
      row.addEventListener('click', () => {
        const id = row.dataset.txId;
        fetch(`${FINANCE_PREFIX}/edit?id=${id}`)
          .then(r => {
            if (!r.ok) throw new Error('Erro ao buscar detalhes');
            return r.json();
          })
          .then(tx => openModalEdit(tx))
          .catch(err => {
            console.error(err);
            alert('Não foi possível carregar os detalhes.');
          });
      });
    });
  });
})();
