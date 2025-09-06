// public/js/employees.js - VERSÃO SIMPLIFICADA E FUNCIONAL

document.addEventListener('DOMContentLoaded', function() {
  
  const baseUrl = window.baseUrl;
  const langText = window.langText || {};
  
  // Elementos principais
  const employeeCards = document.querySelectorAll('.employee-card');
  const detailsModal = document.getElementById('employeeDetailsModal');
  const closeDetailBtns = document.querySelectorAll('.closeEmployeeDetailsModal');
  const form = document.getElementById('employeeDetailsForm');
  
  // Elementos da aba de horas
  const timeTrackingEmployeeId = document.getElementById('timeTrackingEmployeeId');
  const timeTrackingProject = document.getElementById('timeTrackingProject');
  const timeTrackingDate = document.getElementById('timeTrackingDate');
  const timeTrackingTime = document.getElementById('timeTrackingTime');
  const timeTrackingType = document.getElementById('timeTrackingType');
  const submitTimeTracking = document.getElementById('submitTimeTracking');
  const employeeHoursList = document.getElementById('employeeHoursList');
  const employeeModalTotalHours = document.getElementById('employeeModalTotalHours');
  
  let currentEmployeeId = null;

  console.log('🚀 Sistema de funcionários carregado');

  // ========== FUNÇÕES UTILITÁRIAS ==========
  
  function showNotification(message, type = 'info') {
    document.querySelectorAll('.notification-toast').forEach(n => n.remove());
    
    const notification = document.createElement('div');
    notification.className = `notification-toast fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm ${
      type === 'success' ? 'bg-green-500 text-white' :
      type === 'error' ? 'bg-red-500 text-white' :
      'bg-blue-500 text-white'
    }`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
      notification.style.opacity = '0';
      setTimeout(() => notification.remove(), 300);
    }, 3000);
  }

  function formatDate(dateStr) {
    if (!dateStr) return '';
    try {
      return new Date(dateStr).toLocaleDateString('pt-BR');
    } catch (e) {
      return dateStr;
    }
  }

  // ========== CARREGAMENTO DE PROJETOS ==========
  
  async function loadEmployeeProjects(employeeId) {
    if (!timeTrackingProject) return;
    
    console.log('📋 Carregando projetos para funcionário:', employeeId);
    
    timeTrackingProject.innerHTML = '<option value="">Carregando projetos...</option>';
    timeTrackingProject.disabled = true;
    
    try {
      const response = await fetch(`${baseUrl}/api/employee-projects?employee_id=${employeeId}`);
      
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }
      
      const projects = await response.json();
      console.log('📊 Projetos recebidos:', projects);
      
      timeTrackingProject.innerHTML = '';
      
      if (projects && projects.length > 0) {
        timeTrackingProject.innerHTML += '<option value="">Selecione um projeto...</option>';
        
        projects.forEach(project => {
          timeTrackingProject.innerHTML += `<option value="${project.id}">${project.name}</option>`;
        });
        
        if (projects.length === 1) {
          timeTrackingProject.value = projects[0].id;
        }
        
        timeTrackingProject.disabled = false;
      } else {
        timeTrackingProject.innerHTML = '<option value="">Nenhum projeto encontrado</option>';
        showNotification('Nenhum projeto ativo encontrado', 'error');
      }
      
    } catch (error) {
      console.error('❌ Erro ao carregar projetos:', error);
      timeTrackingProject.innerHTML = '<option value="">Erro ao carregar projetos</option>';
      showNotification('Erro ao carregar projetos', 'error');
    }
  }

  // ========== GERENCIAMENTO DE ABAS ==========
  
  const detailTabButtons = document.querySelectorAll('.tab-btn[data-tab$="-details"]');
  const detailTabPanels = document.querySelectorAll(
    '#panel-general-details, #panel-documents-details, #panel-login-details, #panel-transactions-details, #panel-hours-details'
  );

  function activateDetailTab(tabName) {
    console.log('🔄 Ativando aba:', tabName);
    
    detailTabButtons.forEach(btn => {
      btn.classList.remove('border-blue-600', 'text-blue-600');
      btn.classList.add('text-gray-600');
    });
    
    detailTabPanels.forEach(panel => panel.classList.add('hidden'));

    const activeBtn = document.querySelector(`.tab-btn[data-tab="${tabName}"]`);
    if (activeBtn) {
      activeBtn.classList.remove('text-gray-600');
      activeBtn.classList.add('border-blue-600', 'text-blue-600');
    }
    
    const activePanel = document.getElementById(`panel-${tabName}`);
    if (activePanel) {
      activePanel.classList.remove('hidden');
    }

    if (tabName === 'hours-details' && currentEmployeeId) {
      loadEmployeeProjects(currentEmployeeId);
      loadEmployeeHours(currentEmployeeId, 'today');
    }
  }

  detailTabButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      activateDetailTab(btn.dataset.tab);
    });
  });

  // ========== CARREGAMENTO DE FUNCIONÁRIO ==========
  
  employeeCards.forEach(card => {
    card.addEventListener('click', async () => {
      const employeeId = card.dataset.id;
      currentEmployeeId = employeeId;
      
      console.log('👤 Carregando funcionário ID:', employeeId);
      
      try {
        const response = await fetch(`${baseUrl}/employees/get?id=${employeeId}`);
        const data = await response.json();
        const emp = data.success ? data.data : data;
        
        if (!emp) throw new Error('Dados não encontrados');

        // Preenche apenas campos essenciais
        const fieldsMap = {
          'detailsEmployeeId': emp.id,
          'detailsEmployeeName': emp.name,
          'detailsEmployeeLastName': emp.last_name,
          'detailsEmployeeFunction': emp.function,
          'detailsEmployeePhone': emp.phone,
          'detailsEmployeeEmail': emp.email,
          'detailsEmployeeAddress': emp.address,
          'detailsEmployeeZipCode': emp.zip_code,
          'detailsEmployeeCity': emp.city,
          'detailsEmployeeSex': emp.sex,
          'detailsEmployeeBirthDate': emp.birth_date,
          'detailsEmployeeNationality': emp.nationality,
          'detailsEmployeeAhvNumber': emp.ahv_number,
          'detailsEmployeeReligion': emp.religion,
          'detailsEmployeeMaritalStatus': emp.marital_status,
          'detailsEmployeeStartDate': emp.start_date,
          'detailsEmployeeAbout': emp.about,
          'detailsEmployeeRoleId': emp.role_id,
          'detailsLoginEmail': emp.login_email || emp.email,
          'detailsLoginPassword': '' // Sempre vazio
        };

        // Preenche campos
        Object.entries(fieldsMap).forEach(([fieldId, value]) => {
          const element = document.getElementById(fieldId);
          if (element) {
            element.value = value || '';
          }
        });
        
        // Configura aba de horas
        if (timeTrackingEmployeeId) {
          timeTrackingEmployeeId.value = emp.id;
        }
        
        activateDetailTab('general-details');
        detailsModal.classList.remove('hidden');
        
      } catch (error) {
        console.error('❌ Erro ao carregar funcionário:', error);
        showNotification('Erro ao carregar dados do funcionário', 'error');
      }
    });
  });

  // ========== CARREGAMENTO DE HORAS ==========
  
  async function loadEmployeeHours(employeeId, filter = 'today') {
    if (!employeeHoursList) return;
    
    console.log('⏰ Carregando horas:', employeeId, filter);
    
    employeeHoursList.innerHTML = `
      <div class="p-4 text-center text-gray-500">
        <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600 mx-auto mb-2"></div>
        Carregando...
      </div>
    `;
    
    try {
      const response = await fetch(`${baseUrl}/api/worklog/time-entries?employee_id=${employeeId}&filter=${filter}`);
      const result = await response.json();
      
      if (result.entries && result.entries.length > 0) {
        if (employeeModalTotalHours) {
          employeeModalTotalHours.textContent = `${result.total_hours || '0.00'}h`;
        }
        
        employeeHoursList.innerHTML = result.entries.map(entry => `
          <div class="p-4 flex justify-between items-center hover:bg-gray-50">
            <div>
              <div class="font-medium text-gray-900">
                ${formatDate(entry.date)} - ${entry.time || 'N/A'}
              </div>
              <div class="text-sm text-gray-500">
                ${entry.entry_type === 'entry' ? 'Entrada' : 'Saída'}
                ${entry.project_name ? ` • ${entry.project_name}` : ''}
              </div>
            </div>
            <div class="text-sm font-medium text-gray-600">
              ${entry.calculated_hours ? `${parseFloat(entry.calculated_hours).toFixed(2)}h` : '-'}
            </div>
          </div>
        `).join('');
      } else {
        if (employeeModalTotalHours) {
          employeeModalTotalHours.textContent = '0.00h';
        }
        
        employeeHoursList.innerHTML = `
          <div class="p-8 text-center text-gray-500">
            <svg class="mx-auto h-12 w-12 text-gray-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p>Nenhum registro encontrado</p>
          </div>
        `;
      }
      
    } catch (error) {
      console.error('❌ Erro ao carregar horas:', error);
      employeeHoursList.innerHTML = `
        <div class="p-8 text-center text-red-500">
          <p>Erro ao carregar registros</p>
        </div>
      `;
    }
  }

  // ========== FILTROS DE HORAS ==========
  
  function updateActiveFilter(activeButtonId) {
    document.querySelectorAll('[id^="adminFilter"]').forEach(btn => {
      btn.classList.remove('bg-blue-100', 'text-blue-700');
      btn.classList.add('bg-gray-100', 'text-gray-700');
    });
    
    const activeBtn = document.getElementById(activeButtonId);
    if (activeBtn) {
      activeBtn.classList.remove('bg-gray-100', 'text-gray-700');
      activeBtn.classList.add('bg-blue-100', 'text-blue-700');
    }
  }

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

  // ========== REGISTRO DE PONTO ==========
  
  if (submitTimeTracking) {
    submitTimeTracking.addEventListener('click', async (e) => {
      e.preventDefault();
      
      console.log('🔄 Registrando ponto...');
      
      // Validações
      if (!timeTrackingEmployeeId?.value) {
        showNotification('ID do funcionário não encontrado', 'error');
        return;
      }
      
      if (!timeTrackingProject?.value) {
        showNotification('Selecione um projeto', 'error');
        return;
      }
      
      if (!timeTrackingDate?.value) {
        showNotification('Data é obrigatória', 'error');
        return;
      }
      
      if (!timeTrackingTime?.value) {
        showNotification('Horário é obrigatório', 'error');
        return;
      }
      
      if (!timeTrackingType?.value) {
        showNotification('Selecione o tipo', 'error');
        return;
      }
      
      // Prepara dados
      const formData = new FormData();
      formData.append('employee_id', timeTrackingEmployeeId.value);
      formData.append('project_id', timeTrackingProject.value);
      formData.append('date', timeTrackingDate.value);
      formData.append('time', timeTrackingTime.value);
      formData.append('type', timeTrackingType.value);
      
      console.log('📤 Enviando:', {
        employee_id: timeTrackingEmployeeId.value,
        project_id: timeTrackingProject.value,
        date: timeTrackingDate.value,
        time: timeTrackingTime.value,
        type: timeTrackingType.value
      });
      
      // Desabilita botão
      submitTimeTracking.disabled = true;
      submitTimeTracking.textContent = 'Registrando...';
      
      try {
        const response = await fetch(`${baseUrl}/api/worklog/add-time-entry`, {
          method: 'POST',
          body: formData
        });
        
        if (!response.ok) {
          throw new Error(`HTTP ${response.status}`);
        }
        
        const result = await response.json();
        console.log('📊 Resultado:', result);
        
        if (result.success) {
          // Reset campos mantendo funcionário e projeto
          timeTrackingDate.value = new Date().toISOString().split('T')[0];
          timeTrackingTime.value = new Date().toTimeString().slice(0, 5);
          timeTrackingType.value = 'entry';
          
          // Recarrega horas
          loadEmployeeHours(currentEmployeeId, 'today');
          
          showNotification('Ponto registrado com sucesso!', 'success');
        } else {
          showNotification(result.message || 'Erro ao registrar ponto', 'error');
        }
      } catch (error) {
        console.error('❌ Erro:', error);
        showNotification('Erro ao registrar ponto', 'error');
      } finally {
        // Reabilita botão
        submitTimeTracking.disabled = false;
        submitTimeTracking.textContent = 'Registrar';
      }
    });
  }

  // ========== EVENTOS DO MODAL ==========
  
  // Fechar modal
  closeDetailBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      detailsModal.classList.add('hidden');
      currentEmployeeId = null;
    });
  });

  // Fechar clicando fora
  detailsModal?.addEventListener('click', (e) => {
    if (e.target === detailsModal) {
      detailsModal.classList.add('hidden');
      currentEmployeeId = null;
    }
  });

  // Excluir funcionário
  document.getElementById('deleteEmployeeBtn')?.addEventListener('click', () => {
    if (confirm('Tem certeza que deseja excluir este funcionário?')) {
      if (currentEmployeeId) {
        window.location.href = `${baseUrl}/employees/delete?id=${currentEmployeeId}`;
      }
    }
  });

  // ========== SUBMISSÃO DO FORMULÁRIO PRINCIPAL ==========
  
  form?.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    console.log('💾 Salvando funcionário...');
    
    // Valida se há funcionário selecionado
    const employeeIdField = document.getElementById('detailsEmployeeId');
    if (!employeeIdField?.value) {
      showNotification('Nenhum funcionário selecionado', 'error');
      return;
    }
    
    const formData = new FormData(form);
    formData.set('id', employeeIdField.value); // Força ID correto
    
    try {
      const response = await fetch(`${baseUrl}/employees/update`, {
        method: 'POST',
        body: formData
      });
      
      if (response.ok) {
        showNotification('Funcionário atualizado com sucesso!', 'success');
        setTimeout(() => window.location.reload(), 1500);
      } else {
        throw new Error('Erro na resposta do servidor');
      }
      
    } catch (error) {
      console.error('❌ Erro ao salvar:', error);
      showNotification('Erro ao salvar alterações', 'error');
    }
  });

  console.log('✅ Sistema inicializado com sucesso!');
  
});