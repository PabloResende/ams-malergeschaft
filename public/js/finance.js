// public/js/finance.js
(function() {
  // detecta base da rota a partir da URL deste script
  const me = document.currentScript;
  const base = me.src.replace(/\/js\/finance\.js$/, '');
  // FINANCE_PREFIX e STR devem ter sido atribuídos em index.php
  const FINANCE_PREFIX = window.FINANCE_PREFIX || (base + '/finance');
  const STR            = window.FINANCE_STR || {};

  document.addEventListener('DOMContentLoaded', () => {
    // botões e elementos do modal
    const openNewBtn   = document.getElementById('openTxModalBtn');
    const modal        = document.getElementById('transactionModal');
    const closeBtn     = document.getElementById('closeTxModalBtn');
    const cancelBtn    = document.getElementById('txCancelBtn');
    const form         = document.getElementById('transactionForm');
    const titleEl      = document.getElementById('txModalTitle');
    const saveBtn      = document.getElementById('txSaveBtn');
    const deleteLink   = document.getElementById('txDeleteLink');

    // campos do formulário
    const idInput      = document.getElementById('txId');
    const typeSel      = document.getElementById('txTypeSelect');
    const dateInput    = document.getElementById('txDateInput');
    const catSel       = document.getElementById('txCategorySelect');
    const amtInput     = document.getElementById('txAmountInput');
    const descInput    = document.getElementById('txDescInput');
    const dueCont      = document.getElementById('dueDateContainer');
    const dueInput     = document.getElementById('txDueDateInput');
    const attachList   = document.getElementById('txAttachments');

    // armazena opções originais de categoria
    const origOpts = Array.from(catSel.options).map(o => ({
      value: o.value,
      text:  o.textContent,
      type:  o.getAttribute('data-type')
    }));

    // abre/fecha modal
    function toggleModal(show) {
      modal.classList.toggle('hidden', !show);
    }

    // mostra/esconde campo de due_date
    function toggleDue() {
      dueCont.classList.toggle('hidden', typeSel.value !== 'debt');
    }

    // filtra categorias conforme tipo
    function filterCategories() {
      const t = typeSel.value;
      catSel.innerHTML = '';
      origOpts.forEach(o => {
        if (!t || o.type === t) {
          const opt = document.createElement('option');
          opt.value       = o.value;
          opt.textContent = o.text;
          catSel.appendChild(opt);
        }
      });
    }

    // configura modal em modo "novo"
    function openModalNew() {
      titleEl.textContent = STR.newTransaction || 'Nova Transação';
      form.action         = FINANCE_PREFIX + '/store';
      saveBtn.textContent = STR.save           || 'Salvar';
      idInput.value       = '';
      typeSel.value       = 'income';
      dateInput.value     = new Date().toISOString().slice(0,10);
      amtInput.value      = '';
      descInput.value     = '';
      dueInput.value      = '';
      attachList.innerHTML= '';
      deleteLink.classList.add('hidden');
      toggleDue();
      filterCategories();
      toggleModal(true);
    }

    // configura modal em modo "editar"
    function openModalEdit(tx) {
      titleEl.textContent = STR.editTransaction || 'Editar Transação';
      form.action         = FINANCE_PREFIX + '/update';
      saveBtn.textContent = STR.saveChanges    || 'Salvar';
      idInput.value       = tx.id;
      typeSel.value       = tx.type;
      dateInput.value     = tx.date;
      amtInput.value      = tx.amount;
      descInput.value     = tx.description;
      dueInput.value      = tx.due_date || '';
      attachList.innerHTML= '';

      // popula lista de anexos
      if (Array.isArray(tx.attachments)) {
        tx.attachments.forEach(a => {
          const li  = document.createElement('li');
          const ael = document.createElement('a');
          ael.href        = FINANCE_PREFIX.replace('/finance','') + '/' + a.file_path;
          ael.textContent = a.file_path.split('/').pop();
          ael.target      = '_blank';
          li.appendChild(ael);
          attachList.appendChild(li);
        });
      }

      // configura link de excluir
      deleteLink.href = FINANCE_PREFIX + '/delete?id=' + tx.id;
      deleteLink.classList.remove('hidden');
      deleteLink.onclick = e => {
        const msg = STR.confirmDelete || 'Excluir esta transação?';
        if (!confirm(msg)) e.preventDefault();
      };

      toggleDue();
      filterCategories();
      catSel.value = tx.category_id;
      toggleModal(true);
    }

    // eventos de abertura/fechamento
    openNewBtn.addEventListener('click', openModalNew);
    closeBtn.addEventListener('click', () => toggleModal(false));
    cancelBtn.addEventListener('click', () => toggleModal(false));
    modal.addEventListener('click', e => { if (e.target === modal) toggleModal(false); });

    // ao mudar tipo, ajusta vencimento e categoria
    typeSel.addEventListener('change', () => {
      toggleDue();
      filterCategories();
    });

    // vincula o clique a cada linha da tabela
    document.querySelectorAll('.tx-row').forEach(row => {
      row.addEventListener('click', () => {
        const id = row.dataset.txId;
        fetch(FINANCE_PREFIX + '/edit?id=' + id)
          .then(r => {
            if (!r.ok) throw new Error('HTTP ' + r.status);
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
