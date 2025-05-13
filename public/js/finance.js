(function(){
  const BASE = window.BASE_URL;
  const API  = BASE + '/finance';
  const STR  = window.FINANCE_STR;

  document.addEventListener('DOMContentLoaded', ()=>{
    const openBtn    = document.getElementById('openTxModalBtn');
    const modal      = document.getElementById('transactionModal');
    const closeBtn   = document.getElementById('closeTxModalBtn');
    const cancelBtn  = document.getElementById('txCancelBtn');
    const deleteLink = document.getElementById('txDeleteLink');
    const titleEl    = document.getElementById('txModalTitle');
    const form       = document.getElementById('transactionForm');

    const selCat     = document.getElementById('txCategorySelect');
    const cliCont    = document.getElementById('clientContainer');
    const projCont   = document.getElementById('projectContainer');
    const empCont    = document.getElementById('employeeContainer');

    const selType    = document.getElementById('txTypeSelect');
    const dateCont   = document.getElementById('dateContainer');
    const dueCont    = document.getElementById('dueDateContainer');
    const inpDate    = document.getElementById('txDateInput');
    const inpDue     = document.getElementById('txDueDateInput');
    const inpAmt     = document.getElementById('txAmountInput');
    const descInput  = document.getElementById('txDescInput');
    const attachList = document.getElementById('txAttachments');

    const tabGenBtn  = document.getElementById('tabGeneralBtn');
    const tabDebtBtn = document.getElementById('tabDebtBtn');
    const panelGen   = document.getElementById('tabGeneral');
    const panelDebt  = document.getElementById('tabDebt');

    const chkInit    = document.getElementById('initialPaymentChk');
    const initCont   = document.getElementById('initialPaymentContainer');
    const initAmt    = document.getElementById('initialPaymentAmt');
    const selInst    = document.getElementById('installmentsSelect');
    const infoInst   = document.getElementById('installmentInfo');

    const fldId      = document.getElementById('txId');

    const show = (el,cond) => el.classList.toggle('hidden',!cond);

    function updateAssociation(){
      const assoc = selCat.selectedOptions[0].dataset.assoc;
      show(cliCont,    assoc==='client');
      show(projCont,   assoc==='project');
      show(empCont,    assoc==='employee');
    }

    function activateTab(tab){
      const isGen = tab==='general';
      show(panelGen, isGen);
      show(panelDebt,!isGen);
      tabGenBtn.classList.toggle('border-blue-600', isGen);
      tabDebtBtn.classList.toggle('border-blue-600', !isGen);
    }

    function calculateInstallment(){
      const total = parseFloat(inpAmt.value)||0;
      const parts = parseInt(selInst.value)||0;
      const init  = chkInit.checked?parseFloat(initAmt.value)||0:0;
      const base  = total - init;
      infoInst.textContent = (parts>0&&base>0)?`${parts}× R$ ${(base/parts).toFixed(2)}`:'';
    }

    function updateVisibility(){
      const isDebt = selType.value==='debt';
      show(dueCont, isDebt);
      show(tabDebtBtn, isDebt);
      activateTab(isDebt?'debt':'general');
    }

    function resetForm(){
      form.reset();
      fldId.value    = '';
      attachList.innerHTML = '';
      deleteLink.classList.add('hidden');
      show(cliCont,false);
      show(projCont,false);
      show(empCont,false);
      activateTab('general');
    }

    function openNew(){
      resetForm();
      titleEl.textContent = STR.newTransaction;
      form.method = 'POST';
      form.action = API + '/store';
      show(modal,true);
    }

    function openEdit(id){
      fetch(`${API}/edit?id=${id}`)
        .then(r=>r.json())
        .then(tx=>{
          resetForm();
          titleEl.textContent = STR.editTransaction;
          form.method = 'POST';
          form.action = API + '/update';
          fldId.value = tx.id;

          selCat.value = tx.category; updateAssociation();
          document.getElementById('txClientSelect').value   = tx.client_id    || '';
          document.getElementById('txProjectSelect').value  = tx.project_id   || '';
          document.getElementById('txEmployeeSelect').value = tx.employee_id  || '';

          selType.value  = tx.type; updateVisibility();
          inpDate.value  = tx.date;
          inpDue.value   = tx.due_date   || '';
          inpAmt.value   = tx.amount;
          descInput.value= tx.description||'';
          chkInit.checked= !!tx.initial_payment;
          initAmt.value  = tx.initial_payment_amount||'';
          selInst.value  = tx.installments_count||'';
          calculateInstallment();

          attachList.innerHTML='';
          (tx.attachments||[]).forEach(a=>{
            const li=document.createElement('li');
            const ael=document.createElement('a');
            ael.href=`${BASE}/${a.file_path}`;
            ael.textContent=a.file_path.split('/').pop();
            ael.target   ='_blank';
            li.appendChild(ael);
            attachList.appendChild(li);
          });

          deleteLink.href = `${API}/delete?id=${tx.id}`;
          deleteLink.classList.remove('hidden');
          show(modal,true);
        })
        .catch(err=>alert('Erro: '+err.message));
    }

    document.querySelector('table.w-full tbody')
      .addEventListener('click', e=>{
        const tr = e.target.closest('tr.tx-row');
        if (!tr) return;
        openEdit(tr.dataset.txId);
      });

    openBtn.addEventListener('click',openNew);
    closeBtn.addEventListener('click',()=>show(modal,false));
    cancelBtn.addEventListener('click',()=>show(modal,false));
    modal.addEventListener('click',e=>{ if(e.target===modal) show(modal,false); });

    selCat.addEventListener('change',updateAssociation);
    selType.addEventListener('change',updateVisibility);
    chkInit.addEventListener('change',updateVisibility);
    selInst.addEventListener('change',calculateInstallment);
    initAmt.addEventListener('input',calculateInstallment);
    inpAmt.addEventListener('input',calculateInstallment);
    tabGenBtn.addEventListener('click',()=>activateTab('general'));
    tabDebtBtn.addEventListener('click',()=>activateTab('debt'));
  });
})();
