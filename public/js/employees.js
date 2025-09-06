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

  // Elementos para aba de horas
  const timeTrackingForm = document.getElementById('timeTrackingForm');
  const timeTrackingEmployeeId = document.getElementById('timeTrackingEmployeeId');
  const timeTrackingDate = document.getElementById('timeTrackingDate');
  const employeeHoursList = document.getElementById('employeeHoursList');
  const employeeModalTotalHours = document.getElementById('employeeModalTotalHours');
  
  let currentEmployeeId = null;

  function ucfirst(str){
    return str.charAt(0).toUpperCase() + str.slice(1);
  }

  // Abre modal de detalhes ao clicar no card
  cards.forEach(card=>{
    card.addEventListener('click', ()=>{
      const id = card.dataset.id;
      currentEmployeeId = id;
      
      fetch(`${window.baseUrl}/employees/get?id=${id}`)
        .then(res=>{
          if (!res.ok) throw new Error('Erro na resposta da rede');
          return res.json();
        })
        .then(data=>{
          // Verifica se a resposta tem success e data
          const emp = data.success ? data.data : data;
          
          // — Popula campos ocultos e gerais —
          userIdInput.value     = emp.user_id || '';
          idInput.value         = emp.id || '';
          nameInput.value       = emp.name || '';
          lastNameInput.value   = emp.last_name || '';
          funcInput.value       = emp.function || emp.position || '';
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
          loginEmailInput.value = emp.login_email || emp.email || '';
          loginPassInput.value  = '';

          // — Configurar aba de horas —
          if (timeTrackingEmployeeId) {
            timeTrackingEmployeeId.value = emp.id;
          }
          if (timeTrackingDate) {
            timeTrackingDate.value = new Date().toISOString().split('T')[0];
          }

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

  // Tabs dentro do modal de detalhes - ATUALIZADO PARA INCLUIR HORAS
  const detailTabButtons = detailsModal.querySelectorAll('.tab-btn[data-tab$="-details"]');
  const detailTabPanels  = detailsModal.querySelectorAll(
    '#panel-general-details, #panel-documents-details, #panel-login-details, #panel-transactions-details, #panel-hours-details'
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

    // Carregar dados específicos da aba de horas
    if (tabName === 'hours-details' && currentEmployeeId) {
      loadEmployeeHours(currentEmployeeId, 'today');
    }
  }

  detailTabButtons.forEach(btn=>
    btn.addEventListener('click', ()=> activateDetailTab(btn.dataset.tab))
  );

  // ——— Funcionalidades da Aba de Horas ———

  // Filtros de período
  document.getElementById('adminFilterall')?.addEventListener('click', () => {
    if (currentEmployeeId) {
      loadEmployeeHours(currentEmployeeId, 'today');
      updateActiveFilter('adminFilterall');
    }
  });

  document.getElementById('adminFilterweek')?.addEventListener('click', () => {
    if (currentEmployeeId) {
      loadEmployeeHours(currentEmployeeId, 'week');
      updateActiveFilter('adminFilterweek');
    }
  });

  document.getElementById('adminFiltermonth')?.addEventListener('click', () => {
    if (currentEmployeeId) {
      loadEmployeeHours(currentEmployeeId, 'month');
      updateActiveFilter('adminFiltermonth');
    }
  });

  document.getElementById('adminFilterperiod')?.addEventListener('click', () => {
    if (currentEmployeeId) {
      loadEmployeeHours(currentEmployeeId, 'all');
      updateActiveFilter('adminFilterperiod');
    }
  });

  // Submit do formulário de registro de horas - USANDO CLICK EVENT NO BOTÃO
  if (submitTimeTracking) {
    submitTimeTracking.addEventListener('click', (e) => {
      e.preventDefault();
      
      console.log('Botão de registrar ponto clicado'); // Debug
      
      if (!currentEmployeeId) {
        alert('Erro: ID do funcionário não encontrado');
        return;
      }
      
      // Validar campos obrigatórios
      if (!timeTrackingDate.value || !timeTrackingTime.value || !timeTrackingType.value) {
        alert('Por favor, preencha todos os campos obrigatórios');
        return;
      }
      
      // Preparar dados para envio
      const formData = {
        employee_id: timeTrackingEmployeeId.value,
        date: timeTrackingDate.value,
        time: timeTrackingTime.value,
        entry_type: timeTrackingType.value
      };
      
      console.log('Dados do formulário:', formData); // Debug
      
      // Por enquanto apenas mostra um alerta para testar
      alert(`Registrando ${formData.entry_type === 'entry' ? 'entrada' : 'saída'} para funcionário ${currentEmployeeId} em ${formData.date} às ${formData.time}`);
      
      // Limpar formulário após teste
      timeTrackingDate.value = new Date().toISOString().split('T')[0];
      timeTrackingTime.value = '';
      timeTrackingType.value = 'entry';
      
      // Aqui você pode implementar a chamada real da API quando estiver pronta
      /*
      fetch(`${window.baseUrl}/api/time-tracking`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(formData)
      })
      .then(response => response.json())
      .then(result => {
        if (result.success) {
          timeTrackingDate.value = new Date().toISOString().split('T')[0];
          timeTrackingTime.value = '';
          timeTrackingType.value = 'entry';
          loadEmployeeHours(currentEmployeeId, 'today');
          showNotification('Ponto registrado com sucesso!', 'success');
        } else {
          showNotification(result.message || 'Erro ao registrar ponto', 'error');
        }
      })
      .catch(error => {
        console.error('Erro:', error);
        showNotification('Erro ao registrar ponto', 'error');
      });
      */
    });
  }

  // Função para carregar horas do funcionário
  async function loadEmployeeHours(employeeId, filter = 'today') {
    if (!employeeHoursList) return;
    
    // Loading state
    employeeHoursList.innerHTML = `
      <div class="p-4 text-center text-gray-500">
        ${langText.loading_hours || 'Carregando registros de horas...'}
      </div>
    `;
    
    try {
      const response = await fetch(`${window.baseUrl}/api/employee-hours?employee_id=${employeeId}&filter=${filter}`);
      const result = await response.json();
      
      if (result.success) {
        const { hours, total } = result.data || { hours: [], total: 0 };
        
        // Atualizar total de horas
        if (employeeModalTotalHours) {
          employeeModalTotalHours.textContent = `${total}h`;
        }
        
        if (hours && hours.length > 0) {
          employeeHoursList.innerHTML = hours.map(entry => `
            <div class="p-4 flex justify-between items-center hover:bg-gray-50">
              <div>
                <div class="font-medium text-gray-900">
                  ${formatDate(entry.date)} - ${entry.time}
                </div>
                <div class="text-sm text-gray-500">
                  ${entry.entry_type === 'entry' ? 'Entrada' : 'Saída'}
                  ${entry.project_name ? ` • ${entry.project_name}` : ''}
                </div>
              </div>
              <div class="text-sm font-medium text-gray-600">
                ${entry.hours ? `${entry.hours}h` : '-'}
              </div>
            </div>
          `).join('');
        } else {
          employeeHoursList.innerHTML = `
            <div class="p-8 text-center">
              <p class="text-sm text-gray-500">
                ${langText.no_hours_registered || 'Nenhum registro de horas encontrado'}
              </p>
            </div>
          `;
        }
      } else {
        throw new Error(result.message || 'Erro ao carregar dados');
      }
    } catch (error) {
      console.error('Erro ao carregar horas:', error);
      employeeHoursList.innerHTML = `
        <div class="p-4 text-center text-red-500">
          ${langText.error_loading_hours || 'Erro ao carregar registros de horas'}
        </div>
      `;
    }
  }

  // Função para atualizar filtro ativo
  function updateActiveFilter(activeId) {
    // Remover estado ativo de todos os filtros
    ['adminFilterall', 'adminFilterweek', 'adminFiltermonth', 'adminFilterperiod'].forEach(id => {
      const btn = document.getElementById(id);
      if (btn) {
        btn.classList.remove('bg-blue-100', 'text-blue-700');
        btn.classList.add('bg-gray-100', 'text-gray-700');
      }
    });
    
    // Ativar o filtro clicado
    const activeBtn = document.getElementById(activeId);
    if (activeBtn) {
      activeBtn.classList.remove('bg-gray-100', 'text-gray-700');
      activeBtn.classList.add('bg-blue-100', 'text-blue-700');
    }
  }

  // Funções utilitárias
  function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR');
  }

  function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    const bgColor = type === 'success' ? 'bg-green-500' : 'bg-red-500';
    notification.className = `fixed top-4 right-4 z-50 px-6 py-4 rounded-lg shadow-lg text-white ${bgColor}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
      if (notification.parentNode) {
        notification.parentNode.removeChild(notification);
      }
    }, 3000);
  }

})();