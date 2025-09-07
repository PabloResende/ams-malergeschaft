// public/js/employees.js - VERSÃO CORRIGIDA COMPLETA

console.log('🔧 employees.js carregado');

const baseUrl = window.baseUrl || '';
const translations = window.langText || {};
let currentEmployeeId = null;
let currentFilter = 'today';

// ========== ELEMENTOS DOM ==========
const employeeModal = document.getElementById('employeeModal'); // Modal de criação
const detailsModal = document.getElementById('employeeDetailsModal'); // Modal de detalhes
const addEmployeeBtn = document.getElementById('addEmployeeBtn');

// Botões de fechar
const closeEmployeeModal = document.getElementById('closeEmployeeModal');
const cancelEmployeeModal = document.getElementById('cancelEmployeeModal');
const closeDetailsBtns = document.querySelectorAll('.closeEmployeeDetailsModal');

// Cards dos funcionários - usando data-id
const employeeCards = document.querySelectorAll('.employee-card[data-id]');

// Elementos do modal de horas
const timeTrackingEmployeeId = document.getElementById('timeTrackingEmployeeId');
const timeTrackingProject = document.getElementById('timeTrackingProject');
const timeTrackingDate = document.getElementById('timeTrackingDate');
const timeTrackingTime = document.getElementById('timeTrackingTime');
const timeTrackingType = document.getElementById('timeTrackingType');
const submitTimeTracking = document.getElementById('submitTimeTracking');

// ========== INICIALIZAÇÃO ==========
document.addEventListener('DOMContentLoaded', () => {
  console.log('🚀 Inicializando sistema de funcionários');
  console.log(`📊 Encontrados ${employeeCards.length} cards de funcionários`);
  
  setupEventListeners();
});

function setupEventListeners() {
  // ========== BOTÃO ADICIONAR FUNCIONÁRIO ==========
  if (addEmployeeBtn) {
    addEmployeeBtn.addEventListener('click', () => {
      console.log('➕ Abrindo modal de criação');
      openCreateModal();
    });
  } else {
    console.warn('⚠️ Botão addEmployeeBtn não encontrado');
  }

  // ========== CLIQUES NOS CARDS DE FUNCIONÁRIOS ==========
  employeeCards.forEach(card => {
    card.addEventListener('click', async () => {
      const empId = card.getAttribute('data-id');
      console.log('👤 Card clicado, ID do funcionário:', empId);
      
      if (empId) {
        currentEmployeeId = empId;
        await openEmployeeModal(empId);
      } else {
        console.error('❌ ID do funcionário não encontrado no card');
      }
    });
  });

  // ========== FECHAR MODAIS ==========
  // Modal de criação
  if (closeEmployeeModal) {
    closeEmployeeModal.addEventListener('click', closeCreateModal);
  }
  if (cancelEmployeeModal) {
    cancelEmployeeModal.addEventListener('click', closeCreateModal);
  }
  if (employeeModal) {
    employeeModal.addEventListener('click', (e) => {
      if (e.target === employeeModal) closeCreateModal();
    });
  }

  // Modal de detalhes
  closeDetailsBtns.forEach(btn => {
    btn.addEventListener('click', closeDetailsModal);
  });
  if (detailsModal) {
    detailsModal.addEventListener('click', (e) => {
      if (e.target === detailsModal) closeDetailsModal();
    });
  }

  // ESC para fechar
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      if (!employeeModal.classList.contains('hidden')) {
        closeCreateModal();
      }
      if (!detailsModal.classList.contains('hidden')) {
        closeDetailsModal();
      }
    }
  });

  // ========== TABS DO MODAL DE DETALHES ==========
  setupTabs();
  
  // ========== FILTROS DE PERÍODO ==========
  setupFilters();
  
  // ========== FORMULÁRIOS ==========
  setupForms();
}

// ========== MODAL DE CRIAÇÃO ==========
function openCreateModal() {
  if (employeeModal) {
    employeeModal.classList.remove('hidden');
    
    // Reset do formulário
    const form = employeeModal.querySelector('form');
    if (form) {
      form.reset();
    }
    
    console.log('✅ Modal de criação aberto');
  }
}

function closeCreateModal() {
  if (employeeModal) {
    employeeModal.classList.add('hidden');
    console.log('✅ Modal de criação fechado');
  }
}

// ========== MODAL DE DETALHES ==========
async function openEmployeeModal(employeeId) {
  if (!detailsModal) {
    console.error('❌ Modal de detalhes não encontrado');
    return;
  }
  
  console.log('📂 Abrindo modal de detalhes para funcionário:', employeeId);
  
  detailsModal.classList.remove('hidden');
  detailsModal.classList.add('flex');
  
  // Definir ID ativo
  currentEmployeeId = employeeId;
  if (timeTrackingEmployeeId) {
    timeTrackingEmployeeId.value = employeeId;
  }
  
  try {
    // Carregar dados do funcionário
    await loadEmployeeDetails(employeeId);
    
    // Carregar projetos alocados
    await loadEmployeeProjects(employeeId);
    
    // Ativar primeira tab
    switchToTab('general-details');
    
    console.log('✅ Modal de detalhes carregado com sucesso');
    
  } catch (error) {
    console.error('❌ Erro ao abrir modal:', error);
    showNotification('Erro ao carregar dados do funcionário', 'error');
  }
}

function closeDetailsModal() {
  if (detailsModal) {
    detailsModal.classList.add('hidden');
    detailsModal.classList.remove('flex');
    currentEmployeeId = null;
    console.log('✅ Modal de detalhes fechado');
  }
}

// ========== SISTEMA DE TABS ==========
function setupTabs() {
  const tabButtons = document.querySelectorAll('.tab-btn[data-tab]');
  
  console.log(`📑 Configurando ${tabButtons.length} tabs`);
  
  tabButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      const targetTab = btn.getAttribute('data-tab');
      console.log('🔄 Mudando para tab:', targetTab);
      switchToTab(targetTab, btn);
    });
  });
}

function switchToTab(tabId, clickedBtn = null) {
  // Encontrar o botão correto se não foi passado
  if (!clickedBtn) {
    clickedBtn = document.querySelector(`[data-tab="${tabId}"]`);
  }
  
  if (!clickedBtn) {
    console.error('❌ Botão da tab não encontrado:', tabId);
    return;
  }

  // Remove active de todos os botões
  const allTabBtns = document.querySelectorAll('.tab-btn');
  allTabBtns.forEach(btn => {
    btn.classList.remove('border-blue-600', 'text-blue-600');
    btn.classList.add('text-gray-600');
  });

  // Ativa o botão clicado
  clickedBtn.classList.remove('text-gray-600');
  clickedBtn.classList.add('border-blue-600', 'text-blue-600');

  // Esconde todos os painéis
  const allPanels = document.querySelectorAll('.tab-panel');
  allPanels.forEach(panel => panel.classList.add('hidden'));

  // Mostra o painel ativo
  const activePanel = document.getElementById(`panel-${tabId}`);
  if (activePanel) {
    activePanel.classList.remove('hidden');

    // Se for aba de horas, carrega os dados
    if (tabId === 'hours-details' && currentEmployeeId) {
      loadEmployeeHours(currentEmployeeId, currentFilter);
    }
  } else {
    console.error('❌ Painel não encontrado:', `panel-${tabId}`);
  }
}

// ========== CARREGAR DADOS DO FUNCIONÁRIO - CORRIGIDO ==========
async function loadEmployeeDetails(employeeId) {
  console.log('📥 Carregando detalhes do funcionário:', employeeId);
  
  try {
    const response = await fetch(`${baseUrl}/employees/get?id=${employeeId}`);
    
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`);
    }
    
    const result = await response.json();
    console.log('📊 Dados recebidos:', result);
    
    // CORREÇÃO: A API retorna { success: true, data: {...} }
    const employee = result.success ? result.data : result;
    
    // Atualizar título do modal
    const modalTitle = document.querySelector('#employeeDetailsModal .text-xl');
    if (modalTitle && employee.name) {
      modalTitle.textContent = `Detalhes - ${employee.name} ${employee.last_name || ''}`.trim();
    }
    
    // Preencher campos básicos
    const fields = {
      'detailsEmployeeName': employee.name || '',
      'detailsEmployeeLastName': employee.last_name || '',
      'detailsEmployeeFunction': employee.function || '',
      'detailsEmployeeEmail': employee.email || '',
      'detailsEmployeeAddress': employee.address || '',
      'detailsEmployeePhone': employee.phone || '',
      'detailsEmployeeCity': employee.city || '',
      'detailsEmployeeZipCode': employee.zip_code || '',
      'detailsEmployeeBirthDate': employee.birth_date || '',
      'detailsEmployeeNationality': employee.nationality || '',
      'detailsEmployeeStartDate': employee.start_date || ''
    };
    
    Object.entries(fields).forEach(([fieldId, value]) => {
      const field = document.getElementById(fieldId);
      if (field) {
        field.value = value;
      }
    });
    
  } catch (error) {
    console.error('❌ Erro ao carregar detalhes:', error);
    showNotification('Erro ao carregar detalhes do funcionário', 'error');
  }
}

// ========== CARREGAR PROJETOS DO FUNCIONÁRIO ==========
async function loadEmployeeProjects(employeeId) {
  const projectSelect = document.getElementById('timeTrackingProject');
  if (!projectSelect || !employeeId) {
    console.log('⏭️ Pulando carregamento de projetos (elementos não encontrados)');
    return;
  }
  
  console.log('📥 Carregando projetos para funcionário:', employeeId);
  
  try {
    projectSelect.innerHTML = '<option value="">Carregando projetos...</option>';
    
    const response = await fetch(`${baseUrl}/api/worklog/employee-projects?employee_id=${employeeId}`);
    
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`);
    }
    
    const projects = await response.json();
    console.log(`📊 ${projects.length} projetos carregados`);
    
    projectSelect.innerHTML = '<option value="">Selecione um projeto...</option>';
    
    if (projects.length === 0) {
      projectSelect.innerHTML = '<option value="">Nenhum projeto alocado</option>';
      return;
    }
    
    projects.forEach(project => {
      const option = document.createElement('option');
      option.value = project.id;
      option.textContent = project.name;
      projectSelect.appendChild(option);
    });
    
  } catch (error) {
    console.error('❌ Erro ao carregar projetos:', error);
    projectSelect.innerHTML = '<option value="">Erro ao carregar projetos</option>';
  }
}

// ========== SISTEMA DE FILTROS ==========
function setupFilters() {
  const filterButtons = document.querySelectorAll('[id^="adminFilter"]');
  
  console.log(`🔍 Configurando ${filterButtons.length} filtros`);
  
  filterButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      const filter = btn.id.replace('adminFilter', '').toLowerCase();
      applyFilter(filter, btn);
    });
  });
}

function applyFilter(filter, clickedBtn) {
  currentFilter = filter;
  console.log('🔍 Aplicando filtro:', filter);
  
  // Atualiza visual dos botões
  const allFilterBtns = document.querySelectorAll('[id^="adminFilter"]');
  allFilterBtns.forEach(btn => {
    btn.classList.remove('bg-blue-100', 'text-blue-700');
    btn.classList.add('bg-gray-100', 'text-gray-700');
  });
  
  clickedBtn.classList.remove('bg-gray-100', 'text-gray-700');
  clickedBtn.classList.add('bg-blue-100', 'text-blue-700');
  
  // Recarrega dados se modal estiver aberto
  if (currentEmployeeId) {
    loadEmployeeHours(currentEmployeeId, filter);
  }
}

// ========== CARREGAR REGISTROS DE HORAS - CORRIGIDO ==========
async function loadEmployeeHours(employeeId, filter = 'today') {
  const hoursList = document.getElementById('employeeHoursList');
  const totalHoursDisplay = document.getElementById('employeeModalTotalHours');
  
  if (!hoursList || !employeeId) {
    console.log('⏭️ Elementos necessários não encontrados');
    return;
  }
  
  console.log(`📥 Carregando horas - Funcionário: ${employeeId}, Filtro: ${filter}`);
  
  try {
    hoursList.innerHTML = '<div class="p-4 text-center text-gray-500">Carregando...</div>';
    
    // CORREÇÃO: Usar a API correta time-entries-by-day
    const response = await fetch(`${baseUrl}/api/worklog/time-entries-by-day?employee_id=${employeeId}&filter=${filter}`);
    
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`);
    }
    
    const dayEntries = await response.json();
    console.log('📊 Dados recebidos:', dayEntries);
    
    // Verificar se há dados
    if (!dayEntries || dayEntries.length === 0) {
      hoursList.innerHTML = '<div class="p-4 text-center text-gray-500">Nenhum registro encontrado</div>';
      if (totalHoursDisplay) totalHoursDisplay.textContent = '0.00h';
      return;
    }
    
    // Calcular total geral
    const grandTotal = dayEntries.reduce((sum, day) => sum + (day.total_hours || 0), 0);
    if (totalHoursDisplay) {
      totalHoursDisplay.textContent = `${grandTotal.toFixed(2)}h`;
    }
    
    // Gerar HTML dos registros
    let html = '';
    dayEntries.forEach(day => {
      const formattedDate = new Date(day.date + 'T00:00:00').toLocaleDateString('pt-BR');
      
      // Separar entradas e saídas e organizar em pares
      const entradas = day.entries.filter(e => e.entry_type === 'entry').map(e => e.time).sort();
      const saidas = day.entries.filter(e => e.entry_type === 'exit').map(e => e.time).sort();
      
      // Montar string de exibição no formato desejado
      let periods = [];
      const maxPairs = Math.min(entradas.length, saidas.length);
      
      for (let i = 0; i < maxPairs; i++) {
        periods.push(`entrada ${entradas[i]} saída ${saidas[i]}`);
      }
      
      // Adicionar entrada sem saída, se houver
      if (entradas.length > saidas.length) {
        periods.push(`entrada ${entradas[entradas.length - 1]} saída ?`);
      }
      
      const displayString = periods.length > 0 
        ? `${periods.join(' - ')} - ${formattedDate}`
        : `Registro incompleto - ${formattedDate}`;
      
      html += `
        <div class="border-b border-gray-100 pb-3 mb-3">
          <div class="flex justify-between items-start">
            <span class="font-medium text-gray-900 flex-1">${displayString}</span>
            <span class="text-sm font-medium text-blue-600 ml-2">${(day.total_hours || 0).toFixed(2)}h</span>
          </div>
        </div>
      `;
    });
    
    hoursList.innerHTML = html;
    
  } catch (error) {
    console.error('❌ Erro ao carregar horas:', error);
    hoursList.innerHTML = '<div class="p-4 text-center text-red-500">Erro ao carregar registros</div>';
    if (totalHoursDisplay) totalHoursDisplay.textContent = '0.00h';
  }
}

// ========== REGISTRO DE PONTO ==========
async function handleTimeEntry() {
  console.log('🕐 Iniciando registro de ponto');
  
  // Validações
  if (!currentEmployeeId) {
    showNotification('Funcionário não identificado', 'error');
    return;
  }
  
  if (!timeTrackingProject?.value) {
    showNotification('Selecione um projeto', 'error');
    return;
  }
  
  // Prepara dados
  const formData = new FormData();
  formData.append('employee_id', currentEmployeeId);
  formData.append('project_id', timeTrackingProject.value);
  formData.append('date', timeTrackingDate.value);
  formData.append('time', timeTrackingTime.value);
  formData.append('type', timeTrackingType.value);
  
  const originalText = submitTimeTracking.textContent;
  
  try {
    submitTimeTracking.disabled = true;
    submitTimeTracking.textContent = 'Registrando...';
    
    const response = await fetch(`${baseUrl}/api/worklog/add-time-entry`, {
      method: 'POST',
      body: formData
    });
    
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`);
    }
    
    const result = await response.json();
    
    if (result.success) {
      // Reset campos mantendo funcionário e projeto
      timeTrackingDate.value = new Date().toISOString().split('T')[0];
      timeTrackingTime.value = new Date().toTimeString().slice(0, 5);
      timeTrackingType.value = 'entry';
      
      // Recarrega horas se estiver na aba de horas
      const hoursPanel = document.getElementById('panel-hours-details');
      if (hoursPanel && !hoursPanel.classList.contains('hidden')) {
        loadEmployeeHours(currentEmployeeId, currentFilter);
      }
      
      showNotification('Ponto registrado com sucesso!', 'success');
      console.log('✅ Ponto registrado com sucesso');
    } else {
      showNotification(result.message || 'Erro ao registrar ponto', 'error');
    }
  } catch (error) {
    console.error('❌ Erro ao registrar ponto:', error);
    showNotification('Erro ao registrar ponto', 'error');
  } finally {
    submitTimeTracking.disabled = false;
    submitTimeTracking.textContent = originalText;
  }
}

// ========== SALVAR ALTERAÇÕES ==========
async function saveEmployeeChanges() {
  if (!currentEmployeeId) return;
  
  console.log('💾 Salvando alterações do funcionário:', currentEmployeeId);
  
  const form = document.getElementById('employeeDetailsForm');
  if (!form) {
    console.error('❌ Formulário não encontrado');
    return;
  }
  
  const formData = new FormData(form);
  
  try {
    const response = await fetch(`${baseUrl}/employees/update`, {
      method: 'POST',
      body: formData
    });
    
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`);
    }
    
    const result = await response.json();
    
    if (result.success) {
      showNotification('Funcionário atualizado com sucesso!', 'success');
    } else {
      showNotification(result.message || 'Erro ao atualizar funcionário', 'error');
    }
  } catch (error) {
    console.error('❌ Erro ao salvar alterações:', error);
    showNotification('Erro ao salvar alterações', 'error');
  }
}

// ========== EXCLUIR FUNCIONÁRIO ==========
async function deleteEmployee(employeeId) {
  if (!employeeId) return;
  
  console.log('🗑️ Excluindo funcionário:', employeeId);
  
  try {
    const response = await fetch(`${baseUrl}/employees/delete`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ employee_id: employeeId })
    });
    
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`);
    }
    
    const result = await response.json();
    
    if (result.success) {
      showNotification('Funcionário excluído com sucesso!', 'success');
      closeDetailsModal();
      // Recarregar página após exclusão
      setTimeout(() => {
        window.location.reload();
      }, 1000);
    } else {
      showNotification(result.message || 'Erro ao excluir funcionário', 'error');
    }
  } catch (error) {
    console.error('❌ Erro ao excluir funcionário:', error);
    showNotification('Erro ao excluir funcionário', 'error');
  }
}

// ========== CONFIGURAR FORMULÁRIOS ==========
function setupForms() {
  // Formulário de registro de ponto
  if (submitTimeTracking) {
    submitTimeTracking.addEventListener('click', async (e) => {
      e.preventDefault();
      await handleTimeEntry();
    });
  }

  // Formulário de salvar alterações
  const saveBtn = document.getElementById('saveEmployeeChanges');
  if (saveBtn) {
    saveBtn.addEventListener('click', async (e) => {
      e.preventDefault();
      await saveEmployeeChanges();
    });
  }

  // Formulário de excluir
  const deleteBtn = document.getElementById('deleteEmployeeBtn');
  if (deleteBtn) {
    deleteBtn.addEventListener('click', async () => {
      if (confirm('Tem certeza que deseja excluir este funcionário?')) {
        await deleteEmployee(currentEmployeeId);
      }
    });
  }
}

// ========== NOTIFICAÇÕES ==========
function showNotification(message, type = 'info') {
  // Criar elemento de notificação
  const notification = document.createElement('div');
  notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm ${
    type === 'success' ? 'bg-green-500 text-white' :
    type === 'error' ? 'bg-red-500 text-white' :
    'bg-blue-500 text-white'
  }`;
  notification.textContent = message;
  
  document.body.appendChild(notification);
  
  // Remover após 3 segundos
  setTimeout(() => {
    notification.remove();
  }, 3000);
}