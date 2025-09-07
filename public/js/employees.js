// public/js/employees.js - CORRIGIDO BASEADO NO HTML REAL

console.log('🔧 employees.js carregado');

// ========== VARIÁVEIS GLOBAIS ==========
const baseUrl = window.baseUrl || '';
let currentEmployeeId = null;

// ========== ELEMENTOS DO DOM (BASEADO NO HTML REAL) ==========
const employeeModal = document.getElementById('employeeModal'); // Modal de criação
const employeeDetailsModal = document.getElementById('employeeDetailsModal'); // Modal de detalhes
const addEmployeeBtn = document.getElementById('addEmployeeBtn');

// Botões de fechar (HTML real)
const closeEmployeeModal = document.getElementById('closeEmployeeModal');
const cancelEmployeeModal = document.getElementById('cancelEmployeeModal');
const closeEmployeeDetailsButtons = document.querySelectorAll('.closeEmployeeDetailsModal');

// Cards dos funcionários com data-id
const employeeCards = document.querySelectorAll('.employee-card[data-id]');

// Formulários
const employeeCreateForm = document.querySelector('#employeeModal form');
const employeeDetailsForm = document.getElementById('employeeDetailsForm');

// Botão de deletar
const deleteEmployeeBtn = document.getElementById('deleteEmployeeBtn');

// ========== INICIALIZAÇÃO ==========
document.addEventListener('DOMContentLoaded', () => {
  console.log('🚀 Inicializando sistema de funcionários');
  console.log(`📊 Modal criação encontrado: ${!!employeeModal}`);
  console.log(`📊 Modal detalhes encontrado: ${!!employeeDetailsModal}`);
  console.log(`📊 Botão adicionar encontrado: ${!!addEmployeeBtn}`);
  console.log(`📊 Encontrados ${employeeCards.length} cards de funcionários`);
  
  setupEventListeners();
});

// ========== CONFIGURAR EVENT LISTENERS ==========
function setupEventListeners() {
  console.log('🔗 Configurando event listeners...');

  // ========== BOTÃO ADICIONAR FUNCIONÁRIO ==========
  if (addEmployeeBtn) {
    console.log('✅ Configurando botão adicionar funcionário');
    addEmployeeBtn.addEventListener('click', (e) => {
      e.preventDefault();
      console.log('➕ Clique no botão adicionar - abrindo modal de criação');
      openCreateModal();
    });
  } else {
    console.error('❌ Botão addEmployeeBtn não encontrado no DOM');
  }

  // ========== CARDS DE FUNCIONÁRIOS ==========
  if (employeeCards.length > 0) {
    console.log(`✅ Configurando ${employeeCards.length} cards de funcionários`);
    employeeCards.forEach((card, index) => {
      card.addEventListener('click', (e) => {
        e.preventDefault();
        const empId = card.getAttribute('data-id');
        console.log(`👤 Card ${index + 1} clicado, ID: ${empId}`);
        
        if (empId) {
          currentEmployeeId = empId;
          openDetailsModal(empId);
        } else {
          console.error('❌ ID do funcionário não encontrado no card');
        }
      });
    });
  } else {
    console.warn('⚠️ Nenhum card de funcionário encontrado');
  }

  // ========== FECHAR MODAL DE CRIAÇÃO ==========
  if (closeEmployeeModal) {
    closeEmployeeModal.addEventListener('click', closeCreateModal);
    console.log('✅ Botão fechar modal criação configurado');
  }

  if (cancelEmployeeModal) {
    cancelEmployeeModal.addEventListener('click', closeCreateModal);
    console.log('✅ Botão cancelar modal criação configurado');
  }

  if (employeeModal) {
    employeeModal.addEventListener('click', (e) => {
      if (e.target === employeeModal) {
        closeCreateModal();
      }
    });
  }

  // ========== FECHAR MODAL DE DETALHES ==========
  if (closeEmployeeDetailsButtons.length > 0) {
    console.log(`✅ Configurando ${closeEmployeeDetailsButtons.length} botões fechar detalhes`);
    closeEmployeeDetailsButtons.forEach(btn => {
      btn.addEventListener('click', closeDetailsModal);
    });
  }

  if (employeeDetailsModal) {
    employeeDetailsModal.addEventListener('click', (e) => {
      if (e.target === employeeDetailsModal) {
        closeDetailsModal();
      }
    });
  }

  // ========== FORMULÁRIOS ==========
  if (employeeCreateForm) {
    console.log('✅ Configurando formulário de criação');
    employeeCreateForm.addEventListener('submit', handleCreateEmployee);
  }

  if (employeeDetailsForm) {
    console.log('✅ Configurando formulário de detalhes');
    employeeDetailsForm.addEventListener('submit', handleUpdateEmployee);
  }

  // ========== BOTÃO DELETAR ==========
  if (deleteEmployeeBtn) {
    console.log('✅ Configurando botão deletar');
    deleteEmployeeBtn.addEventListener('click', handleDeleteEmployee);
  }

  // ========== TABS ==========
  setupTabs();

  // ========== TECLA ESC ==========
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      closeCreateModal();
      closeDetailsModal();
    }
  });
}

// ========== MODAL DE CRIAÇÃO ==========
function openCreateModal() {
  console.log('🆕 Abrindo modal de criação...');
  
  if (!employeeModal) {
    console.error('❌ Modal de criação não encontrado');
    return;
  }

  // Limpar formulário
  if (employeeCreateForm) {
    employeeCreateForm.reset();
  }

  // Mostrar modal
  employeeModal.classList.remove('hidden');
  employeeModal.classList.add('flex');
  document.body.style.overflow = 'hidden';
  
  console.log('✅ Modal de criação aberto');
}

function closeCreateModal() {
  console.log('❌ Fechando modal de criação...');
  
  if (employeeModal) {
    employeeModal.classList.add('hidden');
    employeeModal.classList.remove('flex');
    document.body.style.overflow = '';
    console.log('✅ Modal de criação fechado');
  }
}

// ========== MODAL DE DETALHES ==========
async function openDetailsModal(employeeId) {
  console.log(`📝 Abrindo modal de detalhes para funcionário ID: ${employeeId}`);
  
  if (!employeeDetailsModal) {
    console.error('❌ Modal de detalhes não encontrado');
    return;
  }

  // Mostrar modal
  employeeDetailsModal.classList.remove('hidden');
  employeeDetailsModal.classList.add('flex');
  document.body.style.overflow = 'hidden';

  // Carregar dados do funcionário
  await loadEmployeeDetails(employeeId);
  
  // Ativar primeira aba
  switchToTab('general-details');
  
  console.log('✅ Modal de detalhes aberto');
}

function closeDetailsModal() {
  console.log('❌ Fechando modal de detalhes...');
  
  if (employeeDetailsModal) {
    employeeDetailsModal.classList.add('hidden');
    employeeDetailsModal.classList.remove('flex');
    document.body.style.overflow = '';
    currentEmployeeId = null;
    console.log('✅ Modal de detalhes fechado');
  }
}

// ========== CARREGAR DETALHES DO FUNCIONÁRIO ==========
async function loadEmployeeDetails(employeeId) {
  try {
    console.log(`🔍 Carregando detalhes do funcionário ID: ${employeeId}`);
    
    const response = await fetch(`${baseUrl}/api/employees/${employeeId}/details`);
    
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }
    
    const result = await response.json();
    console.log('📄 Dados recebidos:', result);
    
    if (result.success && result.employee) {
      populateEmployeeForm(result.employee);
      console.log('✅ Formulário preenchido com sucesso');
    } else {
      throw new Error(result.message || 'Dados do funcionário não encontrados');
    }
    
  } catch (error) {
    console.error('❌ Erro ao carregar detalhes:', error);
    showNotification(`Erro ao carregar funcionário: ${error.message}`, 'error');
  }
}

// ========== PREENCHER FORMULÁRIO ==========
function populateEmployeeForm(employee) {
  console.log('📝 Preenchendo formulário com dados:', employee);
  
  // Campos ocultos
  setFieldValue('detailsEmployeeId', employee.id);
  setFieldValue('detailsLoginUserId', employee.user_id);
  
  // Dados pessoais
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
  
  // Dados de login
  setFieldValue('detailsLoginEmail', employee.email);
  
  // Role
  setFieldValue('detailsEmployeeRoleId', employee.role_id);
}

function setFieldValue(fieldId, value) {
  const field = document.getElementById(fieldId);
  if (field) {
    field.value = value || '';
    console.log(`📝 Campo ${fieldId} = "${value}"`);
  } else {
    console.warn(`⚠️ Campo ${fieldId} não encontrado`);
  }
}

// ========== SISTEMA DE ABAS ==========
function setupTabs() {
  const tabButtons = document.querySelectorAll('.tab-btn[data-tab]');
  console.log(`📑 Configurando ${tabButtons.length} abas`);
  
  tabButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      const tabName = btn.getAttribute('data-tab');
      console.log(`🔄 Mudando para aba: ${tabName}`);
      switchToTab(tabName);
    });
  });
}

function switchToTab(tabName) {
  console.log(`🎯 Ativando aba: ${tabName}`);
  
  // Remover active de todas as abas
  document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.classList.remove('border-blue-600', 'text-blue-600', 'border-b-2');
    btn.classList.add('text-gray-600');
  });
  
  // Ativar aba selecionada
  const activeTab = document.querySelector(`[data-tab="${tabName}"]`);
  if (activeTab) {
    activeTab.classList.add('border-blue-600', 'text-blue-600', 'border-b-2');
    activeTab.classList.remove('text-gray-600');
  }
  
  // Esconder todos os painéis
  document.querySelectorAll('.tab-panel').forEach(panel => {
    panel.classList.add('hidden');
  });
  
  // Mostrar painel selecionado
  const activePanel = document.getElementById(`panel-${tabName}`);
  if (activePanel) {
    activePanel.classList.remove('hidden');
    console.log(`✅ Painel ${tabName} ativado`);
  } else {
    console.warn(`⚠️ Painel panel-${tabName} não encontrado`);
  }
}

// ========== CRIAR FUNCIONÁRIO ==========
async function handleCreateEmployee(e) {
  e.preventDefault();
  console.log('➕ Criando novo funcionário...');
  
  const formData = new FormData(employeeCreateForm);
  
  try {
    const response = await fetch(`${baseUrl}/employees/store`, {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    console.log('📤 Resposta do servidor:', result);
    
    if (result.success) {
      showNotification('Funcionário criado com sucesso!', 'success');
      closeCreateModal();
      setTimeout(() => window.location.reload(), 1000);
    } else {
      showNotification(result.message || 'Erro ao criar funcionário', 'error');
    }
    
  } catch (error) {
    console.error('❌ Erro ao criar funcionário:', error);
    showNotification('Erro ao criar funcionário', 'error');
  }
}

// ========== ATUALIZAR FUNCIONÁRIO ==========
async function handleUpdateEmployee(e) {
  e.preventDefault();
  console.log(`💾 Atualizando funcionário ID: ${currentEmployeeId}`);
  
  if (!currentEmployeeId) {
    showNotification('ID do funcionário não encontrado', 'error');
    return;
  }
  
  const formData = new FormData(employeeDetailsForm);
  formData.append('employee_id', currentEmployeeId);
  
  try {
    const response = await fetch(`${baseUrl}/employees/update`, {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    console.log('📤 Resposta do servidor:', result);
    
    if (result.success) {
      showNotification('Funcionário atualizado com sucesso!', 'success');
      closeDetailsModal();
      setTimeout(() => window.location.reload(), 1000);
    } else {
      showNotification(result.message || 'Erro ao atualizar funcionário', 'error');
    }
    
  } catch (error) {
    console.error('❌ Erro ao atualizar funcionário:', error);
    showNotification('Erro ao salvar alterações', 'error');
  }
}

// ========== DELETAR FUNCIONÁRIO ==========
async function handleDeleteEmployee(e) {
  e.preventDefault();
  console.log(`🗑️ Tentativa de deletar funcionário ID: ${currentEmployeeId}`);
  
  if (!currentEmployeeId) {
    showNotification('ID do funcionário não encontrado', 'error');
    return;
  }
  
  if (!confirm('Tem certeza que deseja excluir este funcionário? Esta ação não pode ser desfeita.')) {
    console.log('❌ Usuário cancelou a exclusão');
    return;
  }
  
  const formData = new FormData();
  formData.append('employee_id', currentEmployeeId);
  
  try {
    const response = await fetch(`${baseUrl}/employees/delete`, {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    console.log('📤 Resposta do servidor:', result);
    
    if (result.success) {
      showNotification('Funcionário excluído com sucesso!', 'success');
      closeDetailsModal();
      setTimeout(() => window.location.reload(), 1000);
    } else {
      showNotification(result.message || 'Erro ao excluir funcionário', 'error');
    }
    
  } catch (error) {
    console.error('❌ Erro ao excluir funcionário:', error);
    showNotification('Erro ao excluir funcionário', 'error');
  }
}

// ========== NOTIFICAÇÕES ==========
function showNotification(message, type = 'info') {
  console.log(`📢 Notificação (${type}): ${message}`);
  
  // Remover notificações existentes
  document.querySelectorAll('.notification').forEach(n => n.remove());
  
  // Criar nova notificação
  const notification = document.createElement('div');
  notification.className = `notification fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm transition-all duration-300 ${
    type === 'success' ? 'bg-green-500 text-white' :
    type === 'error' ? 'bg-red-500 text-white' :
    'bg-blue-500 text-white'
  }`;
  notification.textContent = message;
  
  document.body.appendChild(notification);
  
  // Auto-remover após 4 segundos
  setTimeout(() => {
    notification.style.opacity = '0';
    setTimeout(() => notification.remove(), 300);
  }, 4000);
}

// ========== LOGS DE DEBUG ==========
console.log('🎯 JavaScript employees.js carregado e configurado');
console.log('📋 Elementos disponíveis:', {
  employeeModal: !!employeeModal,
  employeeDetailsModal: !!employeeDetailsModal,
  addEmployeeBtn: !!addEmployeeBtn,
  closeEmployeeModal: !!closeEmployeeModal,
  cancelEmployeeModal: !!cancelEmployeeModal,
  closeEmployeeDetailsButtons: closeEmployeeDetailsButtons.length,
  employeeCards: employeeCards.length,
  employeeCreateForm: !!employeeCreateForm,
  employeeDetailsForm: !!employeeDetailsForm,
  deleteEmployeeBtn: !!deleteEmployeeBtn
});