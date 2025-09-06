// public/js/employees.js - VERSÃO FINAL COM PROJETOS E BUG FIXES

document.addEventListener('DOMContentLoaded', function() {
  
  // ========== CONFIGURAÇÃO E VARIÁVEIS GLOBAIS ==========
  const baseUrl = window.baseUrl;
  const langText = window.langText || {};
  
  console.log('🚀 Sistema de funcionários inicializado');
  console.log('Base URL:', baseUrl);
  
  // Elementos do DOM
  const employeeCards = document.querySelectorAll('.employee-card');
  const detailsModal = document.getElementById('employeeDetailsModal');
  const closeDetailBtns = document.querySelectorAll('.closeEmployeeDetailsModal');
  const deleteBtn = document.getElementById('deleteEmployeeBtn');
  const form = document.getElementById('employeeDetailsForm');
  
  // Elementos para aba de horas - SEPARADOS DO FORMULÁRIO PRINCIPAL
  const timeTrackingEmployeeId = document.getElementById('timeTrackingEmployeeId');
  const timeTrackingProject = document.getElementById('timeTrackingProject');
  const timeTrackingDate = document.getElementById('timeTrackingDate');
  const timeTrackingTime = document.getElementById('timeTrackingTime');
  const timeTrackingType = document.getElementById('timeTrackingType');
  const submitTimeTracking = document.getElementById('submitTimeTracking');
  const employeeHoursList = document.getElementById('employeeHoursList');
  const employeeModalTotalHours = document.getElementById('employeeModalTotalHours');
  
  let currentEmployeeId = null;

  console.log('🔧 Elementos encontrados:', {
    timeTrackingEmployeeId: !!timeTrackingEmployeeId,
    timeTrackingProject: !!timeTrackingProject,
    timeTrackingDate: !!timeTrackingDate,
    timeTrackingTime: !!timeTrackingTime,
    timeTrackingType: !!timeTrackingType,
    submitTimeTracking: !!submitTimeTracking,
    employeeHoursList: !!employeeHoursList
  });

  // ========== FUNÇÕES UTILITÁRIAS ==========
  
  function showNotification(message, type = 'info') {
    console.log(`📢 Notification (${type}):`, message);
    
    // Remove notificações existentes
    document.querySelectorAll('.notification-toast').forEach(n => n.remove());
    
    // Cria elemento de notificação
    const notification = document.createElement('div');
    notification.className = `notification-toast fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm transition-all duration-300 ${
      type === 'success' ? 'bg-green-500 text-white' :
      type === 'error' ? 'bg-red-500 text-white' :
      'bg-blue-500 text-white'
    }`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // Remove após 3 segundos
    setTimeout(() => {
      notification.style.opacity = '0';
      setTimeout(() => {
        if (notification.parentNode) {
          notification.parentNode.removeChild(notification);
        }
      }, 300);
    }, 3000);
  }

  function formatDate(dateStr) {
    if (!dateStr) return '';
    try {
      const date = new Date(dateStr);
      return date.toLocaleDateString('pt-BR');
    } catch (e) {
      return dateStr;
    }
  }

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

  // ========== CARREGAMENTO DE PROJETOS ==========
  
  async function loadEmployeeProjects(employeeId) {
    if (!timeTrackingProject) {
      console.warn('⚠️ timeTrackingProject não encontrado');
      return;
    }
    
    console.log('📋 Carregando projetos do funcionário:', employeeId);
    
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
        // Adiciona opção padrão
        timeTrackingProject.innerHTML += '<option value="">Selecione um projeto...</option>';
        
        // Adiciona projetos
        projects.forEach(project => {
          timeTrackingProject.innerHTML += `<option value="${project.id}">${project.name}</option>`;
        });
        
        // Seleciona o primeiro projeto automaticamente se houver apenas um
        if (projects.length === 1) {
          timeTrackingProject.value = projects[0].id;
        }
        
        timeTrackingProject.disabled = false;
      } else {
        timeTrackingProject.innerHTML = '<option value="">Nenhum projeto encontrado</option>';
        showNotification('Este funcionário não está alocado em nenhum projeto', 'error');
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
    
    // Remove classes ativas de todos os botões
    detailTabButtons.forEach(btn => {
      btn.classList.remove('border-blue-600', 'text-blue-600');
      btn.classList.add('text-gray-600');
    });
    
    // Esconde todos os painéis
    detailTabPanels.forEach(panel => panel.classList.add('hidden'));

    // Ativa o botão selecionado
    const activeBtn = document.querySelector(`.tab-btn[data-tab="${tabName}"]`);
    if (activeBtn) {
      activeBtn.classList.remove('text-gray-600');
      activeBtn.classList.add('border-blue-600', 'text-blue-600');
    }
    
    // Mostra o painel correspondente
    const activePanel = document.getElementById(`panel-${tabName}`);
    if (activePanel) {
      activePanel.classList.remove('hidden');
    }

    // Se for a aba de horas, carrega os dados
    if (tabName === 'hours-details' && currentEmployeeId) {
      loadEmployeeProjects(currentEmployeeId);
      loadEmployeeHours(currentEmployeeId, 'today');
    }
  }

  // Event listeners para as abas
  detailTabButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      activateDetailTab(btn.dataset.tab);
    });
  });

  // ========== CARREGAMENTO DE DADOS DO FUNCIONÁRIO ==========
  
  employeeCards.forEach(card => {
    card.addEventListener('click', async () => {
      const employeeId = card.dataset.id;
      currentEmployeeId = employeeId;
      
      console.log('👤 Carregando funcionário ID:', employeeId);
      
      try {
        const response = await fetch(`${baseUrl}/employees/get?id=${employeeId}`);
        
        if (!response.ok) {
          throw new Error('Erro na resposta da rede');
        }
        
        const data = await response.json();
        const emp = data.success ? data.data : data;
        
        if (!emp) {
          throw new Error('Dados do funcionário não encontrados');
        }

        console.log('✅ Dados do funcionário carregados:', emp);

        // Preenche os campos do formulário - CORREÇÃO DO BUG
        populateEmployeeForm(emp);
        
        // Ativa a aba geral e abre o modal
        activateDetailTab('general-details');
        detailsModal.classList.remove('hidden');
        
      } catch (error) {
        console.error('❌ Erro ao carregar funcionário:', error);
        showNotification('Não foi possível carregar os dados do funcionário.', 'error');
      }
    });
  });

  function populateEmployeeForm(emp) {
    console.log('📝 Preenchendo formulário com dados:', emp);
    
    // CORREÇÃO DO BUG: Preencher TODOS os campos do formulário principal
    const formFields = [
      { id: 'detailsEmployeeId', value: emp.id },
      { id: 'detailsEmployeeName', value: emp.name },
      { id: 'detailsEmployeeLastName', value: emp.last_name },
      { id: 'detailsEmployeePhone', value: emp.phone },
      { id: 'detailsEmployeeEmail', value: emp.email },
      { id: 'detailsEmployeeAddress', value: emp.address },
      { id: 'detailsEmployeeCPF', value: emp.cpf },
      { id: 'detailsEmployeeRG', value: emp.rg },
      { id: 'detailsEmployeeBirthDate', value: emp.birth_date },
      { id: 'detailsEmployeeEmergencyContact', value: emp.emergency_contact },
      { id: 'detailsEmployeeEmergencyPhone', value: emp.emergency_phone },
      { id: 'detailsEmployeeReligion', value: emp.religion },
      { id: 'detailsEmployeeStartDate', value: emp.start_date },
      { id: 'detailsEmployeeSex', value: emp.sex },
      { id: 'detailsEmployeeMaritalStatus', value: emp.marital_status },
      { id: 'detailsEmployeeAbout', value: emp.about },
      { id: 'detailsEmployeeRoleId', value: emp.role_id },
      { id: 'detailsLoginEmail', value: emp.login_email || emp.email },
      { id: 'detailsLoginPassword', value: '' } // Senha sempre em branco
    ];
    
    formFields.forEach(field => {
      const element = document.getElementById(field.id);
      if (element) {
        element.value = field.value || '';
        console.log(`✅ Campo ${field.id} preenchido:`, field.value);
      } else {
        console.warn(`⚠️ Campo ${field.id} não encontrado`);
      }
    });
    
    // Configurar campos da aba de horas
    if (timeTrackingEmployeeId) {
      timeTrackingEmployeeId.value = emp.id;
      console.log('✅ Employee ID definido para tracking:', emp.id);
    }
    
    if (timeTrackingDate) {
      timeTrackingDate.value = new Date().toISOString().split('T')[0];
    }
    
    if (timeTrackingTime) {
      timeTrackingTime.value = new Date().toTimeString().slice(0, 5);
    }
  }

  // ========== CARREGAMENTO DE HORAS DO FUNCIONÁRIO ==========
  
  async function loadEmployeeHours(employeeId, filter = 'today') {
    if (!employeeHoursList) {
      console.warn('⚠️ employeeHoursList não encontrado');
      return;
    }
    
    console.log('⏰ Carregando horas do funcionário:', employeeId, 'filtro:', filter);
    
    // Estado de carregamento
    employeeHoursList.innerHTML = `
      <div class="p-4 text-center text-gray-500">
        <div class="flex items-center justify-center space-x-2">
          <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
          <span>${langText.loading_hours || 'Carregando registros de horas...'}</span>
        </div>
      </div>
    `;
    
    try {
      const response = await fetch(`${baseUrl}/api/worklog/time-entries?employee_id=${employeeId}&filter=${filter}`);
      
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }
      
      const result = await response.json();
      console.log('📊 Dados de horas recebidos:', result);
      
      if (result.entries && result.entries.length > 0) {
        // Atualizar total de horas
        if (employeeModalTotalHours) {
          employeeModalTotalHours.textContent = `${result.total_hours || '0.00'}h`;
        }
        
        employeeHoursList.innerHTML = result.entries.map(entry => `
          <div class="p-4 flex justify-between items-center hover:bg-gray-50 border-b border-gray-100">
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
            <div class="mb-2">
              <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <p>${langText.no_hours_registered || 'Nenhum registro de horas encontrado'}</p>
          </div>
        `;
      }
      
    } catch (error) {
      console.error('❌ Erro ao carregar horas:', error);
      employeeHoursList.innerHTML = `
        <div class="p-8 text-center text-red-500">
          <div class="mb-2">
            <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
          </div>
          <p>${langText.error_loading_hours || 'Erro ao carregar registros de horas'}</p>
        </div>
      `;
    }
  }

  // ========== FILTROS DE HORAS ==========
  
  document.getElementById('adminFilterall')?.addEventListener('click', (e) => {
    if (currentEmployeeId) {
      loadEmployeeHours(currentEmployeeId, 'today');
      updateActiveFilter('adminFilterall');
    }
  });

  document.getElementById('adminFilterweek')?.addEventListener('click', (e) => {
    if (currentEmployeeId) {
      loadEmployeeHours(currentEmployeeId, 'week');
      updateActiveFilter('adminFilterweek');
    }
  });

  document.getElementById('adminFiltermonth')?.addEventListener('click', (e) => {
    if (currentEmployeeId) {
      loadEmployeeHours(currentEmployeeId, 'month');
      updateActiveFilter('adminFiltermonth');
    }
  });

  document.getElementById('adminFilterperiod')?.addEventListener('click', (e) => {
    if (currentEmployeeId) {
      loadEmployeeHours(currentEmployeeId, 'all');
      updateActiveFilter('adminFilterperiod');
    }
  });

  // ========== REGISTRO DE PONTO ==========
  
  if (submitTimeTracking) {
    console.log('✅ Botão de registro de ponto encontrado');
    
    submitTimeTracking.addEventListener('click', async (e) => {
      e.preventDefault();
      
      console.log('🔄 Iniciando registro de ponto...');
      
      // Validação dos campos
      if (!timeTrackingEmployeeId?.value) {
        console.error('❌ Employee ID não encontrado');
        showNotification('ID do funcionário não encontrado', 'error');
        return;
      }
      
      if (!timeTrackingProject?.value) {
        console.error('❌ Projeto não selecionado');
        showNotification('Selecione um projeto', 'error');
        return;
      }
      
      if (!timeTrackingDate?.value) {
        console.error('❌ Data não informada');
        showNotification('Data é obrigatória', 'error');
        return;
      }
      
      if (!timeTrackingTime?.value) {
        console.error('❌ Horário não informado');
        showNotification('Horário é obrigatório', 'error');
        return;
      }
      
      if (!timeTrackingType?.value) {
        console.error('❌ Tipo não selecionado');
        showNotification('Tipo de entrada é obrigatório', 'error');
        return;
      }
      
      // Prepara os dados usando FormData
      const formData = new FormData();
      formData.append('employee_id', timeTrackingEmployeeId.value);
      formData.append('project_id', timeTrackingProject.value);
      formData.append('date', timeTrackingDate.value);
      formData.append('time', timeTrackingTime.value);
      formData.append('type', timeTrackingType.value);
      
      console.log('📤 Enviando dados:', {
        employee_id: timeTrackingEmployeeId.value,
        project_id: timeTrackingProject.value,
        date: timeTrackingDate.value,
        time: timeTrackingTime.value,
        type: timeTrackingType.value
      });
      
      // Desabilita botão durante o envio
      submitTimeTracking.disabled = true;
      submitTimeTracking.textContent = 'Registrando...';
      
      try {
        const response = await fetch(`${baseUrl}/api/worklog/add-time-entry`, {
          method: 'POST',
          body: formData
        });
        
        console.log('📡 Resposta do servidor:', {
          status: response.status,
          statusText: response.statusText,
          ok: response.ok
        });
        
        if (!response.ok) {
          throw new Error(`HTTP ${response.status}`);
        }
        
        const result = await response.json();
        console.log('📊 Resultado:', result);
        
        if (result.success) {
          // Reset apenas os campos de data/hora, mantém funcionário e projeto
          timeTrackingDate.value = new Date().toISOString().split('T')[0];
          timeTrackingTime.value = new Date().toTimeString().slice(0, 5);
          timeTrackingType.value = 'entry';
          
          // Recarregar lista de horas
          loadEmployeeHours(currentEmployeeId, 'today');
          
          showNotification('Ponto registrado com sucesso!', 'success');
          console.log('✅ Ponto registrado com sucesso!');
        } else {
          console.error('❌ Erro do servidor:', result.message);
          showNotification(result.message || 'Erro ao registrar ponto', 'error');
        }
      } catch (error) {
        console.error('❌ Erro na requisição:', error);
        showNotification('Erro ao registrar ponto. Verifique a conexão.', 'error');
      } finally {
        // Reabilita botão
        submitTimeTracking.disabled = false;
        submitTimeTracking.textContent = 'Registrar';
      }
    });
  } else {
    console.warn('⚠️ Botão de registro de ponto não encontrado');
  }

  // ========== EVENTOS DO MODAL ==========
  
  // Fechar modal
  closeDetailBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      detailsModal.classList.add('hidden');
      currentEmployeeId = null; // Reset
    });
  });

  // Fechar modal clicando fora
  detailsModal?.addEventListener('click', (e) => {
    if (e.target === detailsModal) {
      detailsModal.classList.add('hidden');
      currentEmployeeId = null; // Reset
    }
  });

  // Excluir funcionário
  deleteBtn?.addEventListener('click', () => {
    if (confirm(window.confirmDeleteMsg || 'Tem certeza que deseja excluir este funcionário?')) {
      const employeeId = currentEmployeeId;
      if (employeeId) {
        window.location.href = `${baseUrl}/employees/delete?id=${employeeId}`;
      }
    }
  });

  // ========== SUBMISSÃO DO FORMULÁRIO PRINCIPAL - CORREÇÃO DO BUG ==========
  
  form?.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    console.log('💾 Salvando alterações do funcionário...');
    
    // VALIDAÇÃO: Verifica se há um funcionário selecionado
    const employeeIdField = document.getElementById('detailsEmployeeId');
    if (!employeeIdField || !employeeIdField.value) {
      showNotification('Erro: Nenhum funcionário selecionado', 'error');
      return;
    }
    
    const formData = new FormData(form);
    
    // GARANTIA: Força o employee_id correto
    formData.set('id', employeeIdField.value);
    
    console.log('📤 Dados sendo enviados para funcionário ID:', employeeIdField.value);
    
    try {
      const response = await fetch(`${baseUrl}/employees/update`, {
        method: 'POST',
        body: formData
      });
      
      if (response.ok) {
        showNotification('Funcionário atualizado com sucesso!', 'success');
        console.log('✅ Funcionário atualizado com sucesso!');
        
        // Recarrega a página após um delay
        setTimeout(() => {
          window.location.reload();
        }, 1500);
      } else {
        throw new Error('Erro na resposta do servidor');
      }
      
    } catch (error) {
      console.error('❌ Erro ao salvar:', error);
      showNotification('Erro ao salvar alterações', 'error');
    }
  });

  console.log('🎉 Sistema de funcionários totalmente inicializado!');
  
});