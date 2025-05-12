// public/js/finance.js
(function(){
  const PREFIX       = window.FINANCE_PREFIX;
  const PROJECT_CAT  = window.PROJECT_CAT;
  const STR          = window.FINANCE_STR;

  document.addEventListener('DOMContentLoaded', ()=>{
    // Modal controls
    const openBtn    = document.getElementById('openTxModalBtn');
    const modal      = document.getElementById('transactionModal');
    const closeBtn   = document.getElementById('closeTxModalBtn');
    const cancelBtn  = document.getElementById('txCancelBtn');
    const deleteLink = document.getElementById('txDeleteLink');
    const titleEl    = document.getElementById('txModalTitle');

    // Tabs
    const tabGenBtn  = document.getElementById('modalTabGenBtn');
    const tabDebtBtn = document.getElementById('modalTabDebtBtn');
    const panelGen   = document.getElementById('tabGeneral');
    const panelDebt  = document.getElementById('tabDebt');

    // Form fields
    const form        = document.getElementById('transactionForm');
    const fldId       = document.getElementById('txId');
    const selType     = document.getElementById('txTypeSelect');
    const inpDate     = document.getElementById('txDateInput');
    const selCat      = document.getElementById('txCategorySelect');
    const projCont    = document.getElementById('projectContainer');
    const selProj     = document.getElementById('txProjectSelect');
    const chkInit     = document.getElementById('initialPaymentChk');
    const selInst     = document.getElementById('installmentsSelect');
    const infoInst    = document.getElementById('installmentInfo');
    const inpDue      = document.getElementById('txDueDateInput');
    const inpAmt      = document.getElementById('txAmountInput');
    const attachList  = document.getElementById('txAttachments');

    // Preserve original options
    const origCats  = Array.from(selCat.options).map(o=>({
      value:o.value,
      type:o.getAttribute('data-type'),
      proj:o.getAttribute('data-project')==='1'
    }));
    const origProjs = Array.from(selProj.options);

    // Helpers
    function show(el, cond){ el.classList.toggle('hidden', !cond); }
    function showTabGen(){
      panelGen.classList.remove('hidden');
      panelDebt.classList.add('hidden');
      tabGenBtn.classList.add('border-blue-600','text-blue-600');
      tabDebtBtn.classList.remove('border-blue-600','text-blue-600');
    }
    function showTabDebt(){
      panelDebt.classList.remove('hidden');
      panelGen.classList.add('hidden');
      tabDebtBtn.classList.add('border-blue-600','text-blue-600');
      tabGenBtn.classList.remove('border-blue-600','text-blue-600');
    }
    function filterCats(){
      const t = selType.value;
      selCat.innerHTML = '';
      origCats.forEach(o=>{
        if (!t || o.type===t) {
          const opt=document.createElement('option');
          opt.value=o.value; opt.textContent=o.value; // text overwritten by server-render
          opt.dataset.type=o.type;
          opt.dataset.project=o.proj?'1':'0';
          selCat.appendChild(opt);
        }
      });
    }
    function updateFields(){
      const isDebt = selType.value==='debt';
      const catOpt = selCat.selectedOptions[0];
      const needProj = catOpt && catOpt.dataset.project==='1' && selType.value!=='income';
      show(projCont, needProj);
      show(panelDebt, isDebt && tabDebtBtn.classList.contains('border-blue-600'));
      show(tabDebtBtn, isDebt);
      showTabGen();
      calcInstall();
    }
    function calcInstall(){
      const v= parseFloat(inpAmt.value)||0;
      const n= parseInt(selInst.value)||0;
      if (!chkInit.checked && v>0 && n>0) {
        infoInst.textContent = `${n}× R$ ${(v/n).toFixed(2)}`;
      } else {
        infoInst.textContent = '';
      }
    }

    // Open / Close Modal
    function openNew(){
      titleEl.textContent = STR.newTransaction;
      form.action         = PREFIX + '/store';
      fldId.value         = '';
      selType.value       = '';
      inpDate.value       = new Date().toISOString().slice(0,10);
      inpAmt.value        = '';
      document.getElementById('txDescInput').value = '';
      inpDue.value        = '';
      selInst.value       = '';
      chkInit.checked     = false;
      attachList.innerHTML= '';

      filterCats(); selCat.value='';
      selProj.innerHTML=''; origProjs.forEach(o=>selProj.appendChild(o.cloneNode(true)));

      deleteLink.classList.add('hidden');
      updateFields();
      show(modal,true);
    }
    function openEdit(tx){
      titleEl.textContent  = STR.editTransaction;
      form.action          = PREFIX + '/update';
      fldId.value          = tx.id;
      selType.value        = tx.type;
      inpDate.value        = tx.date;
      inpAmt.value         = tx.amount;
      document.getElementById('txDescInput').value = tx.description||'';
      inpDue.value         = tx.due_date||'';

      attachList.innerHTML='';
      (tx.attachments||[]).forEach(a=>{
        const li=document.createElement('li'),
              ael=document.createElement('a');
        ael.href=window.BASE_URL+'/'+a.file_path;
        ael.textContent=a.file_path.split('/').pop();
        ael.target='_blank';
        li.appendChild(ael);
        attachList.appendChild(li);
      });

      filterCats(); selCat.value=tx.category_id;
      selProj.innerHTML=''; origProjs.forEach(o=>selProj.appendChild(o.cloneNode(true)));
      selProj.value=tx.project_id||'';

      selInst.value      = tx.installments_count||'';
      chkInit.checked    = tx.initial_payment==1;

      deleteLink.href    = PREFIX+'/delete?id='+tx.id;
      deleteLink.classList.remove('hidden');

      updateFields();
      show(modal,true);
    }

    // Event bindings
    openBtn.addEventListener('click', openNew);
    closeBtn.addEventListener('click', ()=>show(modal,false));
    cancelBtn.addEventListener('click', ()=>show(modal,false));
    tabGenBtn.addEventListener('click', showTabGen);
    tabDebtBtn.addEventListener('click', showTabDebt);

    selType.addEventListener('change', updateFields);
    selCat .addEventListener('change', updateFields);
    selInst.addEventListener('change', calcInstall);
    inpAmt .addEventListener('input', calcInstall);
    chkInit.addEventListener('change', calcInstall);

    document.querySelectorAll('.editBtn').forEach(btn=>{
      btn.addEventListener('click', ()=>{
        fetch(PREFIX+'/edit?id='+btn.dataset.id)
          .then(r=>r.ok?r.json():Promise.reject())
          .then(openEdit)
          .catch(()=>alert('Não foi possível carregar detalhes.'));
      });
    });

    // Init
    filterCats();
    updateFields();
    calcInstall();
  });
})();
