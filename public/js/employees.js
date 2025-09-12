// public/js/employees.js - ARQUIVO COMPLETO CORRIGIDO

console.log('employees.js carregado');

// ========== VARIÁVEIS GLOBAIS ==========
const baseUrl = window.baseUrl || '';
let currentEmployeeId = null;

// ========== ELEMENTOS DO DOM ==========
const employeeModal = document.getElementById('employeeModal');
const employeeDetailsModal = document.getElementById('employeeDetailsModal');
const addEmployeeBtn = document.getElementById('addEmployeeBtn');
const closeEmployeeModal = document.getElementById('closeEmployeeModal');
const cancelEmployeeModal = document.getElementById('cancelEmployeeModal');
const closeEmployeeDetailsButtons = document.querySelectorAll('.closeEmployeeDetailsModal');
const employeeCards = document.querySelectorAll('.employee-card[data-id]');
const employeeCreateForm = document.querySelector('#employeeModal form');
const employeeDetailsForm = document.getElementById('employeeDetailsForm');
const deleteEmployeeBtn = document.getElementById('deleteEmployeeBtn');
const saveEmployeeBtn = document.getElementById('saveEmployee');

// ========== INICIALIZAÇÃO ==========
document.addEventListener('DOMContentLoaded', () => {
  console.log('Inicializando sistema de funcionários');
  console.log(`Modal criação: ${!!employeeModal}`);
  console.log(`Modal detalhes: ${!!employeeDetailsModal}`);
  console.log(`Cards funcionários: ${employeeCards.length}`);
  
  setupEventListeners();
});

// ========== EVENT LISTENERS ==========
function setupEventListeners() {
  console.log('Configurando event listeners...');

  // Botão adicionar funcionário
  if (addEmployeeBtn) {
    addEmployeeBtn.addEventListener('click', (e) => {
      e.preventDefault();
      openCreateModal();
    });
    console.log('Botão adicionar configurado');
  }

  // Cards de funcionários
  employeeCards.forEach((card, index) => {
    card.addEventListener('click', (e) => {
      e.preventDefault();
      const empId = card.getAttribute('data-id');
      console.log(`Card ${index + 1} clicado, ID: ${empId}`);
      
      if (empId) {
        currentEmployeeId = empId;
        openDetailsModal(empId);
      }
    });
  });
  console.log(`${employeeCards.length} cards configurados`);

  // Fechar modais
  if (closeEmployeeModal) {
    closeEmployeeModal.addEventListener('click', closeCreateModal);
  }
  if (cancelEmployeeModal) {
    cancelEmployeeModal.addEventListener('click', closeCreateModal);
  }
  
  closeEmployeeDetailsButtons.forEach(btn => {
    btn.addEventListener('click', closeDetailsModal);
  });

  // Clique fora do modal
  if (employeeModal) {
    employeeModal.addEventListener('click', (e) => {
      if (e.target === employeeModal) closeCreateModal();
    });
  }
  
  if (employeeDetailsModal) {
    employeeDetailsModal.addEventListener('click', (e) => {
      if (e.target === employeeDetailsModal) closeDetailsModal();
    });
  }

  // Formulários
  if (employeeCreateForm) {
    employeeCreateForm.addEventListener('submit', handleCreateEmployee);
  }
  
  if (employeeDetailsForm) {
    employeeDetailsForm.addEventListener('submit', handleUpdateEmployee);
  }

  // Botões de ação
  if (deleteEmployeeBtn) {
    deleteEmployeeBtn.addEventListener('click', handleDeleteEmployee);
  }
  
  // CORREÇÃO: Botão salvar
  if (saveEmployeeBtn) {
    saveEmployeeBtn.addEventListener('click', handleUpdateEmployee);
    console.log('Botão salvar configurado');
  }

  // Tabs
  setupTabs();
  
  // Formulário de ponto
  const timeTrackingForm = document.getElementById('timeTrackingForm');
  if (timeTrackingForm) {
    timeTrackingForm.addEventListener('submit', handleTimeTrackingSubmit);
    console.log('Form de ponto configurado');
  }

  // ESC para fechar modais
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      closeCreateModal();
      closeDetailsModal();
    }
  });
}

// ========== MODAL DE CRIAÇÃO ==========
function openCreateModal() {
  console.log('Abrindo modal de criação...');
  
  if (!employeeModal) return;

  if (employeeCreateForm) {
    employeeCreateForm.reset();
  }

  employeeModal.classList.remove('hidden');
  employeeModal.classList.add('flex');
  document.body.style.overflow = 'hidden';
  
  console.log('Modal de criação aberto');
}

function closeCreateModal() {
  if (employeeModal) {
    employeeModal.classList.add('hidden');
    employeeModal.classList.remove('flex');
    document.body.style.overflow = '';
    console.log('Modal de criação fechado');
  }
}

// ========== MODAL DE DETALHES ==========
async function openDetailsModal(employeeId) {
  console.log(`Abrindo modal de detalhes para funcionário ID: ${employeeId}`);
  
  if (!employeeDetailsModal) return;

  employeeDetailsModal.classList.remove('hidden');
  employeeDetailsModal.classList.add('flex');
  document.body.style.overflow = 'hidden';

  await loadEmployeeDetails(employeeId);
  switchToTab('general-details');
  
  console.log('Modal de detalhes aberto');
}

function closeDetailsModal() {
  if (employeeDetailsModal) {
    employeeDetailsModal.classList.add('hidden');
    employeeDetailsModal.classList.remove('flex');
    document.body.style.overflow = '';
    currentEmployeeId = null;
    console.log('Modal de detalhes fechado');
  }
}

// ========== CARREGAR DADOS DO FUNCIONÁRIO ==========
async function loadEmployeeDetails(employeeId) {
  try {
    console.log(`Carregando detalhes do funcionário ID: ${employeeId}`);
    
    const response = await fetch(`${baseUrl}/employees/get?id=${employeeId}`);
    
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`);
    }
    
    const result = await response.json();
    console.log('Dados recebidos:', result);
    
    // CORREÇÃO: Aceita diferentes estruturas de resposta
    let employee = null;
    
    if (result.success && result.employee) {
      employee = result.employee;
    } else if (result.success && result.data) {
      employee = result.data;
    } else if (result.id) {
      employee = result;
    }
    
    if (employee && employee.id) {
      populateEmployeeForm(employee);
      console.log('Formulário preenchido com sucesso');
    } else {
      throw new Error('Dados do funcionário não encontrados');
    }
    
  } catch (error) {
    console.error('Erro ao carregar detalhes:', error);
    showNotification(`Erro ao carregar funcionário: ${error.message}`, 'error');
  }
}

// ========== PREENCHER FORMULÁRIO ==========
function populateEmployeeForm(employee) {
  console.log('Preenchendo formulário com dados:', employee);
  
  // CORREÇÃO: Usar os IDs corretos que estão no HTML
  setFieldValue('detailsEmployeeId', employee.id);
  setFieldValue('detailsLoginUserId', employee.user_id);
  
  // Dados pessoais - IDs corretos do HTML corrigido
  setFieldValue('detailsEmployeeName', employee.name);
  setFieldValue('detailsEmployeeLastName', employee.last_name);
  setFieldValue('detailsEmployeeFunction', employee.function);
  setFieldValue('detailsEmployeeAddress', employee.address);
  setFieldValue('detailsEmployeeZipCode', employee.zip_code);
  setFieldValue('detailsEmployeeCity', employee.city);
  setFieldValue('detailsEmployeeSex', employee.sex || 'male');
  setFieldValue('detailsEmployeeBirthDate', employee.birth_date);
  setFieldValue('detailsEmployeeNationality', employee.nationality);
  setFieldValue('detailsEmployeePermissionType', employee.permission_type);
  setFieldValue('detailsEmployeeAhvNumber', employee.ahv_number);
  setFieldValue('detailsEmployeePhone', employee.phone);
  setFieldValue('detailsEmployeeReligion', employee.religion);
  setFieldValue('detailsEmployeeMaritalStatus', employee.marital_status || 'single');
  setFieldValue('detailsEmployeeStartDate', employee.start_date);
  setFieldValue('detailsEmployeeAbout', employee.about);
  setFieldValue('detailsLoginEmail', employee.email);
  setFieldValue('detailsEmployeeRoleId', employee.role || 'employee');
  
  // Campo para registro de ponto
  setFieldValue('timeTrackingEmployeeId', employee.id);
  
  console.log('Todos os campos preenchidos');
}

function setFieldValue(fieldId, value) {
  const field = document.getElementById(fieldId);
  if (field) {
    field.value = value || '';
    console.log(`Campo ${fieldId} = "${value}"`);
  } else {
    console.warn(`Campo ${fieldId} não encontrado`);
  }
}

// ========== SISTEMA DE TABS ==========
function setupTabs() {
  document.querySelectorAll('[data-tab]').forEach(tab => {
    tab.addEventListener('click', (e) => {
      e.preventDefault();
      const tabName = tab.getAttribute('data-tab');
      switchToTab(tabName);
    });
  });
  console.log('Tabs configuradas');
}

function switchToTab(tabName) {
  console.log(`Mudando para aba: ${tabName}`);
  
  // Remove active de todas as tabs
  document.querySelectorAll('[data-tab]').forEach(tab => {
    tab.classList.remove('border-blue-600', 'text-blue-600');
    tab.classList.add('border-transparent', 'text-gray-500');
  });
  
  // Esconde todos os painéis
  document.querySelectorAll('.tab-panel').forEach(panel => {
    panel.classList.add('hidden');
  });
  
  // Ativa tab selecionada
  const activeTab = document.querySelector(`[data-tab="${tabName}"]`);
  const activePanel = document.getElementById(`tab-${tabName}`);
  
  if (activeTab) {
    activeTab.classList.add('border-blue-600', 'text-blue-600');
    activeTab.classList.remove('border-transparent', 'text-gray-500');
  }
  
  if (activePanel) {
    activePanel.classList.remove('hidden');
  }
  
  // Se for aba de horas, carrega projetos e dados
  if (tabName === 'work-hours' && currentEmployeeId) {
    loadProjectsForTimeTracking();
    loadEmployeeTimeEntries(currentEmployeeId);
  }
}

// ========== CARREGAR PROJETOS PARA PONTO ==========
async function loadProjectsForTimeTracking() {
  console.log('Carregando projetos para registro de ponto...');
  
  const projectSelect = document.getElementById('timeTrackingProject');
  if (!projectSelect) {
    console.warn('Select de projetos não encontrado');
    return;
  }
  
  try {
    const response = await fetch(`${baseUrl}/api/projects/active`);
    
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`);
    }
    
    const projects = await response.json();
    console.log('Projetos recebidos:', projects);
    
    // Limpa e popula o select
    projectSelect.innerHTML = '<option value="">Selecione um projeto...</option>';
    
    projects.forEach(project => {
      const option = document.createElement('option');
      option.value = project.id;
      option.textContent = project.name;
      projectSelect.appendChild(option);
    });
    
    console.log('Projetos carregados no select');
    
  } catch (error) {
    console.error('Erro ao carregar projetos:', error);
    projectSelect.innerHTML = '<option value="">Erro ao carregar projetos</option>';
  }
}

// ========== CARREGAR REGISTROS DE HORAS ==========
async function loadEmployeeTimeEntries(employeeId) {
  console.log(`Carregando registros de horas do funcionário ${employeeId}`);
  
  const hoursList = document.getElementById('employeeHoursList');
  const totalHoursElement = document.getElementById('employeeModalTotalHours');
  
  if (!hoursList) return;
  
  try {
    hoursList.innerHTML = '<div class="p-4 text-center text-gray-500">Carregando registros...</div>';
    
    const response = await fetch(`${baseUrl}/api/employees/${employeeId}/hours`);
    const result = await response.json();
    
    console.log('Dados de horas recebidos:', result);
    
    if (result.entries && result.entries.length > 0) {
      // Agrupa por data E projeto
      const groupedByDateAndProject = {};
      
      result.entries.forEach(entry => {
        const key = `${entry.date}_${entry.project_name}`;
        
        if (!groupedByDateAndProject[key]) {
          groupedByDateAndProject[key] = {
            date: entry.date,
            project_name: entry.project_name,
            formatted_display: entry.formatted_display,
            total_hours: 0
          };
        }
        
        groupedByDateAndProject[key].total_hours += parseFloat(entry.total_hours || 0);
      });
      
      // Converte para array e ordena por data
      const sortedEntries = Object.values(groupedByDateAndProject)
        .sort((a, b) => b.date.localeCompare(a.date));
      
      hoursList.innerHTML = sortedEntries.map(entry => `
        <div class="p-4 border-b border-gray-200">
          <div class="flex justify-between items-start">
            <div class="flex-1">
              <div class="text-sm font-medium text-gray-900 mb-1">
                ${entry.formatted_display} - ${formatDate(entry.date)}
              </div>
              <div class="text-xs text-gray-500">
                ${entry.project_name || 'Projeto não definido'}
              </div>
            </div>
            <div class="text-sm font-medium ${entry.total_hours > 0 ? 'text-gray-900' : 'text-orange-500'}">
              ${entry.total_hours.toFixed(1)}h
            </div>
          </div>
        </div>
      `).join('');
      
      // Atualiza o total geral
      if (totalHoursElement) {
        const grandTotal = parseFloat(result.total_hours || 0);
        totalHoursElement.textContent = `${grandTotal.toFixed(1)}h`;
      }
      
    } else {
      hoursList.innerHTML = '<div class="p-4 text-center text-gray-500">Nenhum registro encontrado</div>';
      
      if (totalHoursElement) {
        totalHoursElement.textContent = '0.0h';
      }
    }
  } catch (error) {
    console.error('Erro ao carregar horas:', error);
    hoursList.innerHTML = '<div class="p-4 text-center text-red-500">Erro ao carregar registros</div>';
  }
}

// ========== FORMULÁRIO DE PONTO ==========
async function handleTimeTrackingSubmit(e) {
  e.preventDefault();
  console.log('Submetendo registro de ponto...');
  
  const formData = new FormData(e.target);
  const data = {
    employee_id: formData.get('employee_id'),
    project_id: formData.get('project_id'),
    date: formData.get('date'),
    time: formData.get('time'),
    entry_type: formData.get('entry_type')
  };
  
  console.log('Dados do ponto:', data);
  
  // Validações
  if (!data.employee_id) {
    showNotification('ID do funcionário não encontrado', 'error');
    return;
  }
  
  if (!data.project_id) {
    showNotification('Por favor, selecione um projeto', 'error');
    return;
  }
  
  try {
    const response = await fetch(`${baseUrl}/api/work_logs/admin_time_entry`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: new URLSearchParams(data)
    });
    
    const result = await response.json();
    console.log('Resposta do servidor:', result);
    
    if (result.success) {
      showNotification('Ponto registrado com sucesso!', 'success');
      
      // Limpa apenas o horário
      const timeField = document.getElementById('timeTrackingTime');
      if (timeField) {
        const now = new Date();
        timeField.value = now.toTimeString().substring(0, 5);
      }
      
      // Recarrega registros
      if (currentEmployeeId) {
        loadEmployeeTimeEntries(currentEmployeeId);
      }
    } else {
      showNotification(result.message || 'Erro ao registrar ponto', 'error');
    }
  } catch (error) {
    console.error('Erro ao registrar ponto:', error);
    showNotification('Erro interno do servidor', 'error');
  }
}

// ========== CRUD FUNCIONÁRIOS ==========
async function handleCreateEmployee(e) {
  e.preventDefault();
  console.log('Criando funcionário...');
  
  const formData = new FormData(e.target);
  
  try {
    const response = await fetch(`${baseUrl}/employees/store`, {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
      showNotification('Funcionário criado com sucesso!', 'success');
      closeCreateModal();
      setTimeout(() => window.location.reload(), 1000);
    } else {
      showNotification(result.message || 'Erro ao criar funcionário', 'error');
    }
  } catch (error) {
    console.error('Erro ao criar:', error);
    showNotification('Erro de conexão', 'error');
  }
}

async function handleUpdateEmployee(e) {
  e.preventDefault();
  console.log('Atualizando funcionário...');
  
  // CORREÇÃO: Usar o formulário correto
  const form = document.getElementById('employeeDetailsForm');
  if (!form) {
    showNotification('Formulário não encontrado', 'error');
    return;
  }
  
  const formData = new FormData(form);
  
  try {
    const response = await fetch(`${baseUrl}/employees/update`, {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
      showNotification('Funcionário atualizado com sucesso!', 'success');
      closeDetailsModal();
      setTimeout(() => window.location.reload(), 1000);
    } else {
      showNotification(result.message || 'Erro ao atualizar funcionário', 'error');
    }
  } catch (error) {
    console.error('Erro ao atualizar:', error);
    showNotification('Erro ao salvar alterações', 'error');
  }
}

async function handleDeleteEmployee(e) {
  e.preventDefault();
  console.log(`Tentativa de deletar funcionário ID: ${currentEmployeeId}`);
  
  if (!currentEmployeeId) {
    showNotification('ID do funcionário não encontrado', 'error');
    return;
  }
  
  if (!confirm('Tem certeza que deseja excluir este funcionário? Esta ação não pode ser desfeita.')) {
    return;
  }
  
  try {
    const response = await fetch(`${baseUrl}/employees/delete`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: new URLSearchParams({ employee_id: currentEmployeeId })
    });
    
    const result = await response.json();
    
    if (result.success) {
      showNotification('Funcionário excluído com sucesso!', 'success');
      closeDetailsModal();
      setTimeout(() => window.location.reload(), 1000);
    } else {
      showNotification(result.message || 'Erro ao excluir funcionário', 'error');
    }
  } catch (error) {
    console.error('Erro ao excluir:', error);
    showNotification('Erro ao excluir funcionário', 'error');
  }
}

function formatDate(dateString) {
  if (!dateString) return 'Data inválida';
  
  try {
    // CORREÇÃO: Força interpretação como data local
    const dateParts = dateString.split('-');
    const date = new Date(dateParts[0], dateParts[1] - 1, dateParts[2]);
    
    return date.toLocaleDateString('pt-BR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric'
    });
  } catch {
    return dateString;
  }
}

function showNotification(message, type = 'info') {
  console.log(`Notificação (${type}): ${message}`);
  
  // Remove notificações existentes
  document.querySelectorAll('.notification').forEach(n => n.remove());
  
  // Cria nova notificação
  const notification = document.createElement('div');
  notification.className = `notification fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm transition-all duration-300 ${
    type === 'success' ? 'bg-green-500 text-white' :
    type === 'error' ? 'bg-red-500 text-white' :
    'bg-blue-500 text-white'
  }`;
  notification.textContent = message;
  
  document.body.appendChild(notification);
  
  // Remove após 4 segundos
  setTimeout(() => {
    notification.style.opacity = '0';
    setTimeout(() => notification.remove(), 300);
  }, 4000);
}

// ========== LOG FINAL ==========
console.log('employees.js carregado e configurado completamente');
console.log('Elementos encontrados:', {
  employeeModal: !!employeeModal,
  employeeDetailsModal: !!employeeDetailsModal,
  addEmployeeBtn: !!addEmployeeBtn,
  employeeCards: employeeCards.length,
  employeeCreateForm: !!employeeCreateForm,
  employeeDetailsForm: !!employeeDetailsForm,
  deleteEmployeeBtn: !!deleteEmployeeBtn,
  saveEmployeeBtn: !!saveEmployeeBtn
});