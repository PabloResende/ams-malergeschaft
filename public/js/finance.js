// public/js/finance.js
(function(){
  const BASE    = window.BASE_URL;      // '/ams-malergeschaft/public'
  const API     = BASE + '/finance';    // endpoint raiz para finance
  const STR     = window.FINANCE_STR;

  document.addEventListener('DOMContentLoaded', () => {
    // DOM elements
    const openBtn    = document.getElementById('openTxModalBtn');
    const modal      = document.getElementById('transactionModal');
    const modalBox   = modal.querySelector('div.bg-white'); // a caixa interna
    const closeBtn   = document.getElementById('closeTxModalBtn');
    const cancelBtn  = document.getElementById('txCancelBtn');
    const deleteLink = document.getElementById('txDeleteLink');
    const titleEl    = document.getElementById('txModalTitle');

    const tabGenBtn  = document.getElementById('tabGeneralBtn');
    const tabDebtBtn = document.getElementById('tabDebtBtn');
    const panelGen   = document.getElementById('tabGeneral');
    const panelDebt  = document.getElementById('tabDebt');

    const form       = document.getElementById('transactionForm');
    const fldId      = document.getElementById('txId');
    const selType    = document.getElementById('txTypeSelect');
    const dateCont   = document.getElementById('dateContainer');
    const dueCont    = document.getElementById('dueDateContainer');
    const inpDate    = document.getElementById('txDateInput');
    const inpDue     = document.getElementById('txDueDateInput');
    const inpAmt     = document.getElementById('txAmountInput');
    const selCat     = document.getElementById('txCategorySelect');
    const projCont   = document.getElementById('projectContainer');
    const selProj    = document.getElementById('txProjectSelect');
    const descInput  = document.getElementById('txDescInput');
    const attachList = document.getElementById('txAttachments');

    const chkInit    = document.getElementById('initialPaymentChk');
    const initCont   = document.getElementById('initialPaymentContainer');
    const initAmt    = document.getElementById('initialPaymentAmt');
    const selInst    = document.getElementById('installmentsSelect');
    const infoInst   = document.getElementById('installmentInfo');

    const show = (el, cond) => el.classList.toggle('hidden', !cond);

    // alterna entre abas
    function activateTab(tab) {
      if (tab === 'general') {
        show(panelGen, true);
        show(panelDebt, false);
        tabGenBtn.classList.add('border-blue-600','font-medium','text-blue-600');
        tabDebtBtn.classList.remove('border-blue-600','font-medium','text-blue-600');
      } else {
        show(panelGen, false);
        show(panelDebt, true);
        tabDebtBtn.classList.add('border-blue-600','font-medium','text-blue-600');
        tabGenBtn.classList.remove('border-blue-600','font-medium','text-blue-600');
      }
    }

    // limpa tudo
    function resetForm() {
      form.reset();
      fldId.value = '';
      attachList.innerHTML = '';
      deleteLink.classList.add('hidden');
      selType.value = '';
      const firstCat = [...selCat.options].find(o => o.value);
      if (firstCat) selCat.value = firstCat.value;
      activateTab('general');
    }

    // mostra/oculta campos conforme tipo
    function updateVisibility() {
      const type = selType.value;
      show(dateCont, type !== 'debt');
      show(dueCont, type === 'debt');
      show(tabDebtBtn, type === 'debt');
      activateTab(type === 'debt' ? 'debt' : 'general');
      const opt = selCat.selectedOptions[0];
      const needProj = opt?.dataset.project === '1' && type !== 'income';
      show(projCont, needProj);
      show(initCont, chkInit.checked);
      calculateInstallment();
    }

    // cálculo das parcelas
    function calculateInstallment() {
      const total = parseFloat(inpAmt.value) || 0;
      const parts = parseInt(selInst.value) || 0;
      const init  = chkInit.checked ? (parseFloat(initAmt.value) || 0) : 0;
      const base  = total - init;
      infoInst.textContent = (parts > 0 && base > 0)
        ? `${parts}× R$ ${(base/parts).toFixed(2)}`
        : '';
    }

    // abre modal "novo"
    function openNew() {
      resetForm();
      titleEl.textContent = STR.newTransaction;
      form.method = 'POST';
      form.action = API + '/store';
      updateVisibility();
      show(modal, true);
    }

    // abre modal "editar"
    function openEdit(id) {
      if (!id) return alert('ID não encontrado!');
      fetch(`${API}/edit?id=${encodeURIComponent(id)}`)
        .then(res => {
          if (!res.ok) throw new Error('HTTP ' + res.status);
          return res.json();
        })
        .then(tx => {
          resetForm();
          titleEl.textContent = STR.editTransaction;
          form.method = 'POST';
          form.action = API + '/update';
          fldId.value     = tx.id;
          selType.value   = tx.type;
          inpDate.value   = tx.date;
          inpDue.value    = tx.due_date   || '';
          inpAmt.value    = tx.amount;
          descInput.value = tx.description || '';
          selCat.value    = tx.category_id;
          selProj.value   = tx.project_id || '';
          chkInit.checked = !!tx.initial_payment;
          initAmt.value   = tx.initial_payment_amount || '';
          selInst.value   = tx.installments_count || '';
          attachList.innerHTML = '';
          (tx.attachments||[]).forEach(a => {
            const li = document.createElement('li');
            const ael = document.createElement('a');
            ael.href        = `${BASE}/${a.file_path}`;
            ael.textContent = a.file_path.split('/').pop();
            ael.target      = '_blank';
            li.appendChild(ael);
            attachList.appendChild(li);
          });
          deleteLink.href = API + '/delete?id=' + tx.id;
          show(deleteLink, true);
          updateVisibility();
          show(modal, true);
        })
        .catch(err => {
          console.error('Erro ao carregar transação:', err);
          alert('Não foi possível carregar detalhes: ' + err.message);
        });
    }

    // fecha modal
    function closeModal() {
      show(modal, false);
    }

    // fecha modal ao clicar fora da caixa branca
    modal.addEventListener('click', e => {
      if (e.target === modal) closeModal();
    });

    // event listeners
    openBtn.addEventListener('click', openNew);
    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);
    tabGenBtn.addEventListener('click', () => activateTab('general'));
    tabDebtBtn.addEventListener('click', () => activateTab('debt'));
    selType.addEventListener('change', updateVisibility);
    selCat.addEventListener('change', updateVisibility);
    chkInit.addEventListener('change', updateVisibility);
    selInst.addEventListener('change', calculateInstallment);
    initAmt.addEventListener('input', calculateInstallment);
    inpAmt.addEventListener('input', calculateInstallment);
    document.querySelectorAll('.tx-row').forEach(r =>
      r.addEventListener('click', () => openEdit(r.getAttribute('data-tx-id')))
    );
  });
})();
