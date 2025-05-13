// public/js/finance.js
(function(){
  const BASE         = window.BASE_URL;
  const API          = BASE + '/finance';
  const STR          = window.FINANCE_STR;

  document.addEventListener('DOMContentLoaded', () => {
    // Buttons and modal
    const openBtn      = document.getElementById('openTxModalBtn');
    const modal        = document.getElementById('transactionModal');
    const closeBtn     = document.getElementById('closeTxModalBtn');
    const cancelBtn    = document.getElementById('txCancelBtn');
    const deleteLink   = document.getElementById('txDeleteLink');
    const titleEl      = document.getElementById('txModalTitle');
    const form         = document.getElementById('transactionForm');

    // Tabs
    const tabGeneralBtn  = document.getElementById('tabGeneralBtn');
    const tabDebtBtn     = document.getElementById('tabDebtBtn');
    const panelGen       = document.getElementById('tabGeneral');
    const panelDebt      = document.getElementById('tabDebt');

    // Form fields
    const selType        = document.getElementById('txTypeSelect');
    const selCategory    = document.getElementById('txCategorySelect');
    const clientContainer   = document.getElementById('clientContainer');
    const projectContainer  = document.getElementById('projectContainer');
    const employeeContainer = document.getElementById('employeeContainer');
    const dateContainer     = document.getElementById('dateContainer');
    const dueDateInput      = document.getElementById('txDueDateInput');
    const initialChk        = document.getElementById('initialPaymentChk');
    const initialContainer  = document.getElementById('initialPaymentContainer');
    const initialAmt        = document.getElementById('initialPaymentAmt');
    const installmentsSel   = document.getElementById('installmentsSelect');
    const installmentInfo   = document.getElementById('installmentInfo');
    const amountInput       = document.getElementById('txAmountInput');
    const attachmentsList   = document.getElementById('txAttachments');
    const idField           = document.getElementById('txId');

    function show(el, cond) {
      el.classList.toggle('hidden', !cond);
    }

    function activateTab(tab) {
      const general = (tab === 'general');
      show(panelGen, general);
      show(panelDebt, !general);
      tabGeneralBtn.classList.toggle('border-b-2', general);
      tabGeneralBtn.classList.toggle('text-blue-600', general);
      tabDebtBtn .classList.toggle('border-b-2', !general);
      tabDebtBtn .classList.toggle('text-blue-600', !general);
    }

    function calculateInstallments() {
      const total = parseFloat(amountInput.value) || 0;
      const parts = parseInt(installmentsSel.value) || 0;
      const init  = initialChk.checked ? (parseFloat(initialAmt.value) || 0) : 0;
      const base  = total - init;
      installmentInfo.textContent = (parts > 0 && base > 0)
        ? `${parts}× R$ ${(base / parts).toFixed(2)}`
        : '';
    }

    function handleCategoryChange() {
      const cat = selCategory.value;
      // association fields
      show(clientContainer,   cat === 'clientes');
      show(projectContainer,  cat === 'projetos');
      show(employeeContainer, cat === 'funcionarios');
      // parcelamento tab
      if (cat === 'parcelamento') {
        show(tabDebtBtn, true);
        activateTab('debt');
      } else {
        show(tabDebtBtn, false);
        activateTab('general');
      }
    }

    function resetForm() {
      form.reset();
      idField.value = '';
      attachmentsList.innerHTML = '';
      deleteLink.classList.add('hidden');
      show(tabDebtBtn, false);
      activateTab('general');
      show(clientContainer, false);
      show(projectContainer, false);
      show(employeeContainer, false);
      show(initialContainer, false);
    }

    function openNew() {
      resetForm();
      titleEl.textContent = STR.newTransaction;
      form.action = API + '/store';
      form.method = 'POST';
      show(modal, true);
    }

    function openEdit(id) {
      fetch(`${API}/edit?id=${id}`)
        .then(r => r.json())
        .then(tx => {
          resetForm();
          titleEl.textContent = STR.editTransaction;
          idField.value = tx.id;
          form.action = API + '/update';
          form.method = 'POST';

          // tipo e categoria
          selType.value     = tx.type;
          selCategory.value = tx.category;
          handleCategoryChange();

          // associação
          document.getElementById('txClientSelect').value    = tx.client_id    || '';
          document.getElementById('txProjectSelect').value   = tx.project_id   || '';
          document.getElementById('txEmployeeSelect').value  = tx.employee_id  || '';

          // geral
          document.getElementById('txDateInput').value       = tx.date;
          dueDateInput.value   = tx.due_date       || '';
          amountInput.value    = tx.amount;
          document.getElementById('txDescInput').value       = tx.description || '';

          // parcelamento
          initialChk.checked = !!tx.initial_payment;
          initialContainer.classList.toggle('hidden', !initialChk.checked);
          initialAmt.value   = tx.initial_payment_amount || '';
          installmentsSel.value = tx.installments_count || '';
          calculateInstallments();

          // anexos
          attachmentsList.innerHTML = '';
          (tx.attachments || []).forEach(a => {
            const li  = document.createElement('li');
            const ael = document.createElement('a');
            ael.href       = `${BASE}/${a.file_path}`;
            ael.textContent= a.file_path.split('/').pop();
            ael.target     = '_blank';
            li.appendChild(ael);
            attachmentsList.appendChild(li);
          });

          deleteLink.href = `${API}/delete?id=${tx.id}`;
          deleteLink.classList.remove('hidden');
          show(modal, true);
        })
        .catch(e => alert('Erro: ' + e.message));
    }

    // table delegation
    document.querySelector('table.w-full tbody')
      .addEventListener('click', e => {
        const tr = e.target.closest('tr.tx-row');
        if (tr) openEdit(tr.dataset.txId);
      });

    // event listeners
    openBtn .addEventListener('click', openNew);
    closeBtn.addEventListener('click', () => show(modal, false));
    cancelBtn.addEventListener('click', () => show(modal, false));
    modal  .addEventListener('click', e => e.target === modal && show(modal, false));

    selCategory   .addEventListener('change', handleCategoryChange);
    tabGeneralBtn .addEventListener('click', () => activateTab('general'));
    tabDebtBtn    .addEventListener('click', () => activateTab('debt'));

    initialChk     .addEventListener('change', () => {
      show(initialContainer, initialChk.checked);
      calculateInstallments();
    });
    installmentsSel.addEventListener('change', calculateInstallments);
    amountInput    .addEventListener('input', calculateInstallments);
  });
})();
