// public/js/employee-projects.js - VERSÃO CORRIGIDA COMPLETA
(function() {
  'use strict';

  console.log('🔧 employee-projects.js carregado e inicializado');

  // Variáveis globais
  const cards = document.querySelectorAll('.employee-card');
  const modal = document.getElementById('employeeModal');
  const closeModalBtn = document.getElementById('closeModal');
  const employeeForm = document.getElementById('employeeForm');
  
  // Elementos específicos para aba de horas
  const timeTrackingForm = document.getElementById('timeTrackingForm');
  const timeTrackingEmployeeId = document.getElementById('timeTrackingEmployeeId');
  const timeTrackingProject = document.getElementById('timeTrackingProject');
  const timeTrackingDate = document.getElementById('timeTrackingDate');
  const timeTrackingTime = document.getElementById('timeTrackingTime');
  const timeTrackingType = document.getElementById('timeTrackingType');
  const employeeHoursList = document.getElementById('employeeHoursList');
  const employeeModalTotalHours = document.getElementById('employeeModalTotalHours');
  
  let currentEmployeeId = null;

  // Utility function
  function ucfirst(str){
    return str.charAt(0).toUpperCase() + str.slice(1);
  }

  // ========== MODAL DE FUNCIONÁRIO ==========
  
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
          if (!emp) throw new Error('Dados do funcionário não encontrados');
          
          // Preenche dados gerais
          document.getElementById('modalEmployeeName').textContent = emp.name || 'N/A';
          document.getElementById('modalEmployeeEmail').textContent = emp.email || 'N/A';
          document.getElementById('modalEmployeeFunction').textContent = emp.function || 'N/A';
          document.getElementById('modalEmployeePhone').textContent = emp.phone || 'N/A';
          document.getElementById('modalEmployeeAddress').textContent = emp.address || 'N/A';
          
          // Preenche campos do formulário para edição
          const form = document.getElementById('employeeForm');
          if (form) {
            form.querySelector('[name="id"]').value = emp.id || '';
            form.querySelector('[name="name"]').value = emp.name || '';
            form.querySelector('[name="last_name"]').value = emp.last_name || '';
            form.querySelector('[name="function"]').value = emp.function || '';
            form.querySelector('[name="email"]').value = emp.email || '';
            form.querySelector('[name="phone"]').value = emp.phone || '';
            form.querySelector('[name="address"]').value = emp.address || '';
            form.querySelector('[name="zip_code"]').value = emp.zip_code || '';
            form.querySelector('[name="city"]').value = emp.city || '';
            form.querySelector('[name="sex"]').value = emp.sex || 'male';
            form.querySelector('[name="birth_date"]').value = emp.birth_date || '';
            form.querySelector('[name="nationality"]').value = emp.nationality || '';
            form.querySelector('[name="marital_status"]').value = emp.marital_status || 'single';
            form.querySelector('[name="role"]').value = emp.role || 'employee';
          }
          
          // Configura formulário de ponto se estivermos na aba de horas
          if (timeTrackingEmployeeId) {
            timeTrackingEmployeeId.value = emp.id;
          }
          
          // Mostra modal
          modal.classList.remove('hidden');
          modal.classList.add('flex');
        })
        .catch(error => {
          console.error('Erro ao carregar dados do funcionário:', error);
          alert('Erro ao carregar dados do funcionário');
        });
    });
  });

  // Fechar modal
  if (closeModalBtn) {
    closeModalBtn.addEventListener('click', () => {
      modal.classList.add('hidden');
      modal.classList.remove('flex');
      currentEmployeeId = null;
    });
  }

  // Fechar modal clicando fora
  if (modal) {
    modal.addEventListener('click', (e) => {
      if (e.target === modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        currentEmployeeId = null;
      }
    });
  }

  // ========== SISTEMA DE TABS ==========
  
  document.addEventListener('click', (e)=>{
    if (e.target.matches('[data-tab]')) {
      const targetTab = e.target.dataset.tab;
      
      // Remover classes ativas de todas as tabs
      document.querySelectorAll('[data-tab]').forEach(tab => {
        tab.classList.remove('text-blue-600', 'border-b-2', 'border-blue-600');
        tab.classList.add('text-gray-600');
      });
      
      // Adicionar classes ativas à tab clicada
      e.target.classList.add('text-blue-600', 'border-b-2', 'border-blue-600');
      e.target.classList.remove('text-gray-600');
      
      // Esconder todos os painéis
      document.querySelectorAll('.tab-panel').forEach(panel => {
        panel.classList.add('hidden');
      });
      
      // Mostrar o painel ativo
      const targetPanel = document.getElementById(`panel-${targetTab}`);
      if (targetPanel) {
        targetPanel.classList.remove('hidden');
      }

      // CORREÇÃO: Se for a aba de horas, carrega dados específicos
      if (targetTab === 'hours-details' && currentEmployeeId) {
        loadProjectsForAdmin(); // Carrega projetos para o admin
        loadEmployeeHours(currentEmployeeId); // Carrega horas do funcionário
        
        // Configura o ID do funcionário no formulário
        if (timeTrackingEmployeeId) {
          timeTrackingEmployeeId.value = currentEmployeeId;
        }
        
        // Define data atual
        if (timeTrackingDate) {
          const today = new Date().toISOString().split('T')[0];
          timeTrackingDate.value = today;
        }
        
        // Define horário atual
        if (timeTrackingTime) {
          const now = new Date();
          const currentTime = now.toTimeString().substring(0, 5);
          timeTrackingTime.value = currentTime;
        }
      }
    }
  });

  // ========== SISTEMA DE PONTO - ADMIN ==========

  // CORREÇÃO: Função específica para carregar projetos no contexto admin
  async function loadProjectsForAdmin() {
    if (!timeTrackingProject) return;

    try {
      console.log('🔄 Carregando projetos para admin...');
      
      const response = await fetch(`${window.baseUrl}/api/projects/active`);
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }
      
      const projects = await response.json();
      console.log('📋 Projetos carregados:', projects);

      // Limpa e repopula o select
      timeTrackingProject.innerHTML = '<option value="">Selecione um projeto...</option>';
      
      if (projects && projects.length > 0) {
        projects.forEach(project => {
          const option = document.createElement('option');
          option.value = project.id;
          option.textContent = project.name;
          timeTrackingProject.appendChild(option);
        });
        console.log('✅ Projetos carregados com sucesso no select');
      } else {
        timeTrackingProject.innerHTML = '<option value="">Nenhum projeto disponível</option>';
        console.log('⚠️ Nenhum projeto encontrado');
      }
    } catch (error) {
      console.error('❌ Erro ao carregar projetos:', error);
      timeTrackingProject.innerHTML = '<option value="">Erro ao carregar projetos</option>';
    }
  }

  // CORREÇÃO: Função específica para registro de ponto pelo admin
  async function handleAdminTimeEntrySubmit(e) {
    e.preventDefault();
    
    console.log('📝 Iniciando registro de ponto pelo admin...');
    
    // Coleta dados do formulário
    const employeeId = timeTrackingEmployeeId?.value;
    const projectId = timeTrackingProject?.value;
    const date = timeTrackingDate?.value;
    const time = timeTrackingTime?.value;
    const entryType = timeTrackingType?.value;

    // Validações
    if (!employeeId) {
      showNotification("ID do funcionário não encontrado", "error");
      return;
    }
    
    if (!projectId) {
      showNotification("Por favor, selecione um projeto", "error");
      return;
    }
    
    if (!date) {
      showNotification("Por favor, informe a data", "error");
      return;
    }
    
    if (!time) {
      showNotification("Por favor, informe o horário", "error");
      return;
    }
    
    if (!entryType) {
      showNotification("Por favor, selecione o tipo (entrada/saída)", "error");
      return;
    }

    try {
      console.log('📤 Enviando dados:', { employeeId, projectId, date, time, entryType });
      
      // CORREÇÃO: Usa endpoint correto para admin
      const response = await fetch(`${window.baseUrl}/api/work_logs/admin_time_entry`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
          employee_id: employeeId,
          project_id: projectId,
          date: date,
          time: time,
          entry_type: entryType
        })
      });

      const result = await response.json();
      console.log('📥 Resposta do servidor:', result);
      
      if (result.success) {
        showNotification("Ponto registrado com sucesso!", "success");
        
        // Limpa apenas o horário (mantém outros campos)
        if (timeTrackingTime) {
          const now = new Date();
          timeTrackingTime.value = now.toTimeString().substring(0, 5);
        }
        
        // Recarrega os dados de horas
        if (currentEmployeeId) {
          loadEmployeeHours(currentEmployeeId);
          loadEmployeeData(); // Atualiza totais na tabela principal
        }
      } else {
        showNotification(result.message || "Erro ao registrar ponto", "error");
      }
    } catch (error) {
      console.error("❌ Erro ao registrar ponto:", error);
      showNotification("Erro interno do servidor", "error");
    }
  }

  // CORREÇÃO: Event listener específico para o formulário de ponto admin
  if (timeTrackingForm) {
    // Remove event listeners anteriores para evitar duplicação
    timeTrackingForm.removeEventListener('submit', handleAdminTimeEntrySubmit);
    
    // Adiciona o event listener correto
    timeTrackingForm.addEventListener('submit', handleAdminTimeEntrySubmit);
    console.log('🎯 Event listener do formulário de ponto configurado');
  }

  // ========== CARREGAR HORAS DO FUNCIONÁRIO ==========
  
  // Função para atualizar filtros ativos
  function updateActiveFilter(activeId) {
    ['adminFilterall', 'adminFilterweek', 'adminFiltermonth', 'adminFilterperiod'].forEach(filterId => {
      const btn = document.getElementById(filterId);
      if (btn) {
        if (filterId === activeId) {
          btn.classList.remove('bg-gray-100', 'text-gray-700');
          btn.classList.add('bg-blue-100', 'text-blue-700');
        } else {
          btn.classList.remove('bg-blue-100', 'text-blue-700');
          btn.classList.add('bg-gray-100', 'text-gray-700');
        }
      }
    });
  }

  // Função para carregar horas por funcionário
  async function loadEmployeeHours(employeeId, filter = 'all') {
    if (!employeeHoursList) return;

    try {
      console.log(`🔄 Carregando horas do funcionário ${employeeId} com filtro ${filter}...`);
      
      // Mostra loading
      employeeHoursList.innerHTML = `
        <div class="p-8 text-center">
          <div class="text-sm text-gray-500">
            ${window.langText?.loading_hours || 'Carregando registros de horas...'}
          </div>
        </div>
      `;

      const response = await fetch(`${window.baseUrl}/api/employees/${employeeId}/hours?filter=${filter}`);
      
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }

      const result = await response.json();
      console.log('📊 Dados de horas recebidos:', result);

      if (result.error) {
        throw new Error(result.error);
      }

      // Atualiza total no modal
      if (employeeModalTotalHours) {
        employeeModalTotalHours.textContent = `${parseFloat(result.total_hours || 0).toFixed(1)}h`;
      }

      if (result.entries && result.entries.length > 0) {
        employeeHoursList.innerHTML = result.entries.map(entry => `
          <div class="flex justify-between items-start p-4 border-b border-gray-100">
            <div class="flex-1">
              <div class="flex items-center space-x-2">
                <div class="text-sm font-medium text-gray-900">
                  ${formatDate(entry.date)}
                </div>
                <div class="text-sm text-gray-500">
                  ${entry.project_name || 'Projeto não definido'}
                </div>
              </div>
              <div class="text-sm text-gray-600 mt-1">
                ${entry.formatted_display || 'Sem registros'}
              </div>
            </div>
            <div class="text-sm font-medium text-gray-900">
              ${parseFloat(entry.total_hours || 0).toFixed(1)}h
            </div>
          </div>
        `).join('');
      } else {
        employeeHoursList.innerHTML = `
          <div class="p-8 text-center">
            <div class="text-sm text-gray-500">
              ${window.langText?.no_hours_registered || 'Nenhum registro de horas encontrado'}
            </div>
          </div>
        `;
      }
    } catch (error) {
      console.error('❌ Erro ao carregar horas:', error);
      employeeHoursList.innerHTML = `
        <div class="p-8 text-center">
          <div class="text-sm text-red-500">
            ${window.langText?.error_loading_hours || 'Erro ao carregar registros de horas'}
          </div>
        </div>
      `;
    }
  }

  // Event listeners para os filtros de horas
  ['adminFilterall', 'adminFilterweek', 'adminFiltermonth', 'adminFilterperiod'].forEach(filterId => {
    const btn = document.getElementById(filterId);
    if (btn) {
      btn.addEventListener('click', () => {
        const filterMap = {
          'adminFilterall': 'today',
          'adminFilterweek': 'week', 
          'adminFiltermonth': 'month',
          'adminFilterperiod': 'all'
        };
        
        const filter = filterMap[filterId] || 'all';
        updateActiveFilter(filterId);
        
        if (currentEmployeeId) {
          loadEmployeeHours(currentEmployeeId, filter);
        }
      });
    }
  });

  // ========== UTILITÁRIOS ==========
  
  // Função para formatar data
  function formatDate(dateString) {
    if (!dateString) return 'Data inválida';
    
    try {
      const date = new Date(dateString);
      return date.toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit', 
        year: 'numeric'
      });
    } catch (error) {
      return dateString;
    }
  }

  // Função para mostrar notificações
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

  // ========== CRUD DE FUNCIONÁRIOS ==========
  
  // Submissão do formulário de funcionário
  if (employeeForm) {
    employeeForm.addEventListener('submit', async function(e) {
      e.preventDefault();

      const formData = new FormData(this);
      const isUpdate = formData.get('id') && formData.get('id') !== '';
      
      const url = isUpdate 
        ? `${window.baseUrl}/employees/update`
        : `${window.baseUrl}/employees/store`;

      try {
        const response = await fetch(url, {
          method: 'POST',
          body: formData
        });

        const result = await response.json();
        
        if (response.ok && result.success) {
          showNotification(
            isUpdate ? 'Funcionário atualizado com sucesso!' : 'Funcionário criado com sucesso!',
            'success'
          );
          setTimeout(() => {
            location.reload();
          }, 1500);
        } else {
          showNotification(result.message || 'Erro ao salvar funcionário', 'error');
        }
      } catch (error) {
        console.error('Erro:', error);
        showNotification('Erro de conexão', 'error');
      }
    });
  }

  // ========== FUNÇÕES PARA COMPATIBILIDADE ==========
  
  // Função para carregar dados dos funcionários (total de horas e tempo de serviço)
  async function loadEmployeeData() {
    console.log("📊 Carregando dados dos funcionários...");
    
    // Carrega total de horas para cada funcionário
    const employeeRows = document.querySelectorAll(".employee-total-hours");
    
    for (const element of employeeRows) {
      const employeeId = element.getAttribute("data-employee-id");
      if (employeeId) {
        try {
          const response = await fetch(`${window.baseUrl}/api/employees/${employeeId}/hours-summary`);
          const data = await response.json();
          
          element.textContent = `${parseFloat(data.total || 0).toFixed(1)}h`;
        } catch (error) {
          console.error(`Erro ao carregar horas do funcionário ${employeeId}:`, error);
          element.textContent = "0.0h";
        }
      }
    }
  }

  // Funções window para compatibilidade externa
  window.createEmployee = async function(formData) {
    try {
      const response = await fetch(`${window.baseUrl}/api/employees/create`, {
        method: 'POST',
        body: formData
      });

      const result = await response.json();
      
      if (result.success) {
        showNotification("Funcionário criado com sucesso!", "success");
        return true;
      } else {
        showNotification(result.message || "Erro ao criar funcionário", "error");
        return false;
      }
    } catch (error) {
      console.error("Erro ao criar funcionário:", error);
      showNotification("Erro interno do servidor", "error");
      return false;
    }
  };

  window.updateEmployee = async function(employeeId, formData) {
    try {
      const response = await fetch(`${window.baseUrl}/api/employees/${employeeId}/update`, {
        method: 'POST',
        body: formData
      });

      const result = await response.json();
      
      if (result.success) {
        showNotification("Funcionário atualizado com sucesso!", "success");
        return true;
      } else {
        showNotification(result.message || "Erro ao atualizar funcionário", "error");
        return false;
      }
    } catch (error) {
      console.error("Erro ao atualizar funcionário:", error);
      showNotification("Erro interno do servidor", "error");
      return false;
    }
  };

  window.deleteEmployee = async function(employeeId) {
    if (!confirm("Tem certeza que deseja excluir este funcionário?")) {
      return false;
    }

    try {
      const response = await fetch(`${window.baseUrl}/api/employees/${employeeId}/delete`, {
        method: 'POST'
      });

      const result = await response.json();
      
      if (result.success) {
        showNotification("Funcionário excluído com sucesso!", "success");
        
        // Remove a linha da tabela
        const row = document.getElementById(`emp-${employeeId}`);
        if (row) {
          row.remove();
        }
        
        return true;
      } else {
        showNotification(result.message || "Erro ao excluir funcionário", "error");
        return false;
      }
    } catch (error) {
      console.error("Erro ao excluir funcionário:", error);
      showNotification("Erro interno do servidor", "error");
      return false;
    }
  };

  // ========== INICIALIZAÇÃO ==========
  
  // Inicializa dados dos funcionários quando a página carrega
  document.addEventListener('DOMContentLoaded', () => {
    loadEmployeeData();
    console.log('✅ employee-projects.js inicializado completamente');
  });

  // Expõe funções para uso externo
  window.loadEmployeeData = loadEmployeeData;
  window.loadEmployeeHours = loadEmployeeHours;
  window.loadProjectsForAdmin = loadProjectsForAdmin;

})();