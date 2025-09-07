// public/js/employees.js - ARQUIVO COMPLETO CORRIGIDO

(() => {
  'use strict';

  const modal = document.getElementById('employeeModal');
  const cards = document.querySelectorAll('.employee-card');
  const addBtn = document.getElementById('addEmployeeBtn');
  const form = document.getElementById('employeeDetailsForm');
  const closeBtn = document.querySelector('.close-modal');
  const transBody = document.getElementById('empTransBody');
  
  // Elementos do formulário
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
                  ${langText['no_transactions'] ?? 'No transactions'}
                </td>
              </tr>
            `;
          }

          // — Login data —
          loginEmailInput.value = emp.email || '';

          modal.classList.remove('hidden');
          modal.classList.add('flex');
          document.body.style.overflow = 'hidden';
        })
        .catch(err=>{
          console.error('Erro ao carregar funcionário:', err);
          alert('Erro ao carregar dados do funcionário');
        });
    });
  });

  // Adicionar funcionário (limpa o form)
  addBtn?.addEventListener('click',()=>{
    currentEmployeeId = null;
    form.reset();
    
    // Limpar documentos (imagens e links)
    [
      'passport','permission_photo_front','permission_photo_back',
      'health_card_front','health_card_back','bank_card_front',
      'bank_card_back','marriage_certificate'
    ].forEach(field=>{
      const view = document.getElementById(`view${ucfirst(field)}`);
      const link = document.getElementById(`link${ucfirst(field)}`);
      if (view) view.style.display = 'none';
      if (link) link.style.display = 'none';
    });

    transBody.innerHTML = `
      <tr>
        <td colspan="3" class="p-4 text-center text-gray-500">
          ${langText['no_transactions'] ?? 'No transactions'}
        </td>
      </tr>
    `;
    
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
  });

  // Fechar modal
  closeBtn?.addEventListener('click', closeModal);
  modal?.addEventListener('click', (e)=>{
    if (e.target === modal) closeModal();
  });

  function closeModal(){
    modal?.classList.add('hidden');
    modal?.classList.remove('flex');
    document.body.style.overflow = '';
  }

  // Submit do formulário
  form?.addEventListener('submit', async (e)=>{
    e.preventDefault();
    
    const isUpdate = currentEmployeeId !== null;
    const formData = new FormData(form);
    
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

  // Tabs
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

      // Se for a aba de horas, carrega dados específicos
      if (targetTab === 'hours-details' && currentEmployeeId) {
        loadProjects();
        loadEmployeeHours(currentEmployeeId);
        
        // Configura o ID do funcionário no formulário
        if (timeTrackingEmployeeId) {
          timeTrackingEmployeeId.value = currentEmployeeId;
        }
        
        // Define data atual
        if (timeTrackingDate) {
          const today = new Date().toISOString().split('T')[0];
          timeTrackingDate.value = today;
        }
      }
    }
  });

  // Carregar horas por funcionário (versão original corrigida)
  async function loadEmployeeHours(employeeId, filter = 'all') {
    if (!employeeHoursList) return;

    try {
      // Mostra loading
      employeeHoursList.innerHTML = `
        <div class="p-8 text-center">
          <div class="text-sm text-gray-500">
            ${langText.loading_hours || 'Carregando registros de horas...'}
          </div>
        </div>
      `;

      const response = await fetch(`${window.baseUrl}/api/employees/${employeeId}/hours?filter=${filter}`);
      
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }

      const result = await response.json();

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
            <p class="text-sm text-gray-500">
              ${langText.no_hours_registered || 'Nenhum registro de horas encontrado'}
            </p>
          </div>
        `;
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

  // ========== CORREÇÕES PARA REGISTRO DE HORAS E TEMPO DE SERVIÇO ==========

  // Função para carregar dados dos funcionários (total de horas e tempo de serviço)
  async function loadEmployeeData() {
    console.log("📊 Carregando dados dos funcionários...");
    
    // Carrega total de horas para cada funcionário
    const employeeRows = document.querySelectorAll(".employee-total-hours");
    const serviceTimeElements = document.querySelectorAll(".employee-service-time");
    
    for (const element of employeeRows) {
      const employeeId = element.getAttribute("data-employee-id");
      if (employeeId) {
        try {
          const response = await fetch(`${window.baseUrl}/api/employees/${employeeId}/hours-summary`);
          const data = await response.json();
          
          element.textContent = `${parseFloat(data.total || 0).toFixed(1)}h`;
          
          // Calcula tempo de serviço
          const serviceElement = document.querySelector(`.employee-service-time[data-employee-id="${employeeId}"]`);
          if (serviceElement) {
            const serviceTime = await calculateServiceTime(employeeId);
            serviceElement.textContent = serviceTime;
          }
        } catch (error) {
          console.error(`Erro ao carregar horas do funcionário ${employeeId}:`, error);
          element.textContent = "0.0h";
        }
      }
    }
  }

  // Função para calcular tempo de serviço
  async function calculateServiceTime(employeeId) {
    try {
      const response = await fetch(`${window.baseUrl}/api/employees/${employeeId}/details`);
      const data = await response.json();
      
      if (data.start_date) {
        const startDate = new Date(data.start_date);
        const today = new Date();
        
        const diffTime = Math.abs(today - startDate);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        const years = Math.floor(diffDays / 365);
        const months = Math.floor((diffDays % 365) / 30);
        
        if (years > 0) {
          return `${years}a ${months}m`;
        } else if (months > 0) {
          return `${months} meses`;
        } else {
          return `${diffDays} dias`;
        }
      }
      
      return "N/A";
    } catch (error) {
      console.error("Erro ao calcular tempo de serviço:", error);
      return "N/A";
    }
  }

  // Função para carregar projetos no formulário de registro de ponto
  async function loadProjects() {
    const select = document.getElementById("timeTrackingProject");
    if (!select) return;

    try {
      const response = await fetch(`${window.baseUrl}/api/projects/active`);
      const projects = await response.json();

      select.innerHTML = '<option value="">Selecione um projeto...</option>';
      
      projects.forEach(project => {
        const option = document.createElement('option');
        option.value = project.id;
        option.textContent = project.name;
        select.appendChild(option);
      });
    } catch (error) {
      console.error("Erro ao carregar projetos:", error);
    }
  }

  // Função corrigida para lidar com registro de ponto
  async function handleTimeEntrySubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = {
      employee_id: formData.get('employee_id'),
      project_id: formData.get('project_id'),
      date: formData.get('date'),
      time: formData.get('time'),
      type: formData.get('type')
    };

    // Validações
    if (!data.employee_id || !data.project_id || !data.date || !data.time || !data.type) {
      alert("Por favor, preencha todos os campos");
      return;
    }

    try {
      const response = await fetch(`${window.baseUrl}/api/worklog/add-time-entry`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
      });

      const result = await response.json();
      
      if (result.success) {
        showNotification("Ponto registrado com sucesso!", "success");
        e.target.reset();
        
        // Define data atual novamente
        const today = new Date().toISOString().split('T')[0];
        const dateField = document.getElementById("timeTrackingDate");
        if (dateField) dateField.value = today;
        
        // Recarrega os dados de horas
        if (currentEmployeeId) {
          loadEmployeeHours(currentEmployeeId);
          loadEmployeeData(); // Atualiza totais na tabela principal
        }
      } else {
        showNotification(result.message || "Erro ao registrar ponto", "error");
      }
    } catch (error) {
      console.error("Erro ao registrar ponto:", error);
      showNotification("Erro interno do servidor", "error");
    }
  }

  // Event listeners para o formulário de registro de ponto
  if (timeTrackingForm) {
    timeTrackingForm.addEventListener('submit', handleTimeEntrySubmit);
  }

  // Event listeners para os filtros de horas
  ['adminFilterall', 'adminFilterweek', 'adminFiltermonth', 'adminFilterperiod'].forEach(filterId => {
    const btn = document.getElementById(filterId);
    if (btn) {
      btn.addEventListener('click', () => {
        const filter = filterId.replace('adminFilter', '').toLowerCase();
        updateActiveFilter(filterId);
        
        if (currentEmployeeId) {
          loadEmployeeHours(currentEmployeeId, filter);
        }
      });
    }
  });

  // Funções para criar/atualizar/excluir funcionários
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

  // Inicializa dados dos funcionários quando a página carrega
  document.addEventListener('DOMContentLoaded', () => {
    loadEmployeeData();
  });

  // Para compatibilidade, expõe as funções que podem ser chamadas externamente
  window.loadEmployeeData = loadEmployeeData;
  window.calculateServiceTime = calculateServiceTime;
  window.loadProjects = loadProjects;
  window.loadEmployeeHours = loadEmployeeHours;

})();