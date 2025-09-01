// public/js/employees.js
(function(){
  // ——— Modal de Criação ———
  const employeeModal      = document.getElementById('employeeModal');
  const openEmployeeBtn    = document.getElementById('addEmployeeBtn');
  const closeEmployeeBtn   = document.getElementById('closeEmployeeModal');
  const cancelEmployeeBtn  = document.getElementById('cancelEmployeeModal');

  // Tabs criação
  const createTabButtons = employeeModal.querySelectorAll('.tab-btn[data-tab$="-create"]');
  const createTabPanels  = employeeModal.querySelectorAll(
    '#panel-general-create, #panel-documents-create, #panel-login-create'
  );

  function activateCreateTab(tabName) {
    createTabButtons.forEach(btn=>{
      btn.classList.remove('border-blue-600','text-blue-600');
      btn.classList.add('text-gray-600');
    });
    createTabPanels.forEach(p=>p.classList.add('hidden'));

    const btn   = employeeModal.querySelector(`.tab-btn[data-tab="${tabName}"]`);
    const panel = document.getElementById(`panel-${tabName}`);
    if (btn) {
      btn.classList.remove('text-gray-600');
      btn.classList.add('border-blue-600','text-blue-600');
    }
    if (panel) panel.classList.remove('hidden');
  }

  openEmployeeBtn.addEventListener('click', ()=>{
    activateCreateTab('general-create');
    employeeModal.classList.remove('hidden');
  });
  [closeEmployeeBtn, cancelEmployeeBtn].forEach(btn=>
    btn.addEventListener('click', ()=> employeeModal.classList.add('hidden'))
  );
  employeeModal.addEventListener('click', e=>{
    if (e.target === employeeModal) employeeModal.classList.add('hidden');
  });
  createTabButtons.forEach(btn=>
    btn.addEventListener('click', ()=> activateCreateTab(btn.dataset.tab))
  );


  // ——— Modal de Detalhes / Edição ———
  const cards           = document.querySelectorAll('.employee-card');
  const detailsModal    = document.getElementById('employeeDetailsModal');
  const closeDetailBtns = detailsModal.querySelectorAll('.closeEmployeeDetailsModal');
  const deleteBtn       = document.getElementById('deleteEmployeeBtn');
  const form            = document.getElementById('employeeDetailsForm');
  const transBody       = document.getElementById('empTransBody');
  const langText        = window.langText || {};

  // Campos de formulário de detalhes
  const userIdInput     = form.querySelector('#detailsLoginUserId');
  const idInput         = form.querySelector('#detailsEmployeeId');
  const nameInput       = form.querySelector('#detailsEmployeeName');
  const lastNameInput   = form.querySelector('#detailsEmployeeLastName');
  const funcInput       = form.querySelector('#detailsEmployeeFunction');
  const addressInput    = form.querySelector('#detailsEmployeeAddress');
  const zipInput        = form.querySelector('#detailsEmployeeZipCode');
  const cityInput       = form.querySelector('#detailsEmployeeCity');
  const birthInput      = form.querySelector('#detailsEmployeeBirthDate');
  const natInput        = form.querySelector('#detailsEmployeeNationality');
  const permTypeInput   = form.querySelector('#detailsEmployeePermissionType');
  const ahvInput        = form.querySelector('#detailsEmployeeAhvNumber');
  const phoneInput      = form.querySelector('#detailsEmployeePhone');
  const religionInput   = form.querySelector('#detailsEmployeeReligion');
  const startInput      = form.querySelector('#detailsEmployeeStartDate');
  const sexInput        = form.querySelector('#detailsEmployeeSex');
  const maritalInput    = form.querySelector('#detailsEmployeeMaritalStatus');
  const aboutInput      = form.querySelector('#detailsEmployeeAbout');
  const roleSelect      = form.querySelector('#detailsEmployeeRoleId');
  const loginEmailInput = form.querySelector('#detailsLoginEmail');
  const loginPassInput  = form.querySelector('#detailsLoginPassword');

  function ucfirst(str){
    return str.charAt(0).toUpperCase() + str.slice(1);
  }

  // Abre modal de detalhes ao clicar no card
  cards.forEach(card=>{
    card.addEventListener('click', ()=>{
      const id = card.dataset.id;
      fetch(`${window.baseUrl}/employees/get?id=${id}`)
        .then(res=>{
          if (!res.ok) throw new Error('Erro na resposta da rede');
          return res.json();
        })
        .then(emp=>{
          // — Popula campos ocultos e gerais —
          userIdInput.value     = emp.user_id || '';
          idInput.value         = emp.id || '';
          nameInput.value       = emp.name || '';
          lastNameInput.value   = emp.last_name || '';
          funcInput.value       = emp.function || '';
          addressInput.value    = emp.address || '';
          zipInput.value        = emp.zip_code || '';
          cityInput.value       = emp.city || '';
          birthInput.value      = emp.birth_date || '';
          natInput.value        = emp.nationality || '';
          permTypeInput.value   = emp.permission_type || '';
          ahvInput.value        = emp.ahv_number || '';
          phoneInput.value      = emp.phone || '';
          religionInput.value   = emp.religion || '';
          startInput.value      = emp.start_date || '';
          sexInput.value        = emp.sex || '';
          maritalInput.value    = emp.marital_status || '';
          aboutInput.value      = emp.about || '';
          roleSelect.value      = emp.role_id || '';

          // — Documentos —
          [
            'passport','permission_photo_front','permission_photo_back',
            'health_card_front','health_card_back','bank_card_front',
            'bank_card_back','marriage_certificate'
          ].forEach(field=>{
            const view = document.getElementById(`view${ucfirst(field)}`);
            const link = document.getElementById(`link${ucfirst(field)}`);
            if (!view || !link) return;
            if (emp[field]) {
              const url = `${window.baseUrl}/${emp[field]}`;
              view.src           = url;
              view.style.display = 'block';
              link.href          = url;
              link.textContent   = emp[field].split('/').pop();
              link.style.display = 'block';
            } else {
              view.style.display = 'none';
              link.style.display = 'none';
            }
          });

          // — Transações —
          transBody.innerHTML = '';
          if (emp.transactions && emp.transactions.length) {
            emp.transactions.forEach(tx=>{
              const tr = document.createElement('tr');
              tr.innerHTML = `
                <td class="p-2">${tx.date}</td>
                <td class="p-2">${tx.type}</td>
                <td class="p-2 text-right">${tx.amount}</td>
              `;
              transBody.appendChild(tr);
            });
          } else {
            transBody.innerHTML = `
              <tr>
                <td colspan="3" class="p-4 text-center text-gray-500">
                  ${langText['no_transactions'] ?? 'Sem transações'}
                </td>
              </tr>`;
          }

          // — Acesso (senha sempre em branco) —
          loginEmailInput.value = emp.login_email || '';
          loginPassInput.value  = '';

          // ativa tab Geral e abre modal
          activateDetailTab('general-details');
          detailsModal.classList.remove('hidden');
        })
        .catch(err=>{
          console.error(err);
          alert('Não foi possível carregar os dados do funcionário.');
        });
    });
  });

  // Fecha modal de detalhes
  closeDetailBtns.forEach(btn=>
    btn.addEventListener('click', ()=> detailsModal.classList.add('hidden'))
  );
  detailsModal.addEventListener('click', e=>{
    if (e.target === detailsModal) detailsModal.classList.add('hidden');
  });

  // Excluir funcionário
  deleteBtn.addEventListener('click', ()=>{
    if (confirm(window.confirmDeleteMsg)) {
      window.location.href = `${window.baseUrl}/employees/delete?id=${idInput.value}`;
    }
  });

  // Tabs dentro do modal de detalhes
  const detailTabButtons = detailsModal.querySelectorAll('.tab-btn[data-tab$="-details"]');
  const detailTabPanels  = detailsModal.querySelectorAll(
    '#panel-general-details, #panel-documents-details, #panel-login-details, #panel-transactions-details'
  );

  function activateDetailTab(tabName) {
    detailTabButtons.forEach(b=>{
      b.classList.remove('border-blue-600','text-blue-600');
      b.classList.add('text-gray-600');
    });
    detailTabPanels.forEach(p=>p.classList.add('hidden'));

    const btn   = detailsModal.querySelector(`.tab-btn[data-tab="${tabName}"]`);
    const panel = document.getElementById(`panel-${tabName}`);
    if (btn) {
      btn.classList.remove('text-gray-600');
      btn.classList.add('border-blue-600','text-blue-600');
    }
    if (panel) panel.classList.remove('hidden');
  }

  detailTabButtons.forEach(btn=>
    btn.addEventListener('click', ()=> activateDetailTab(btn.dataset.tab))
  );

})();
