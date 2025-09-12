<?php
// app/views/employees/dashboard_employee.php - VERSÃO CORRIGIDA

require __DIR__.'/../layout/header_employee.php';

require_once __DIR__.'/../../../app/models/WorkLogModel.php';
require_once __DIR__.'/../../../app/models/Employees.php';
require_once __DIR__.'/../../../app/models/Project.php';

$wlModel = new WorkLogModel();
$userId = (int) ($_SESSION['user']['id'] ?? 0);
$empModel = new Employee();
$emp = $empModel->findByUserId($userId);
$empId = $emp['id'] ?? 0;
$totalH = $wlModel->getTotalHoursByEmployee($empId);

// CORREÇÃO: Usar a variável $projects que vem do controller
// Se não houver, buscar aqui como fallback
if (!isset($projects) || empty($projects)) {
    $projModel = new ProjectModel();
    $projects = $projModel->getByEmployee($empId);
}
?>
<script>
  window.baseUrl  = <?= json_encode(BASE_URL, JSON_UNESCAPED_SLASHES); ?>;
  window.langText = <?= json_encode($langText, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_APOS); ?>;
</script>

<div class="pt-20 px-4 sm:px-8 sm:ml-56 pb-8">
  <!-- Título -->
  <h1 class="text-3xl font-bold mb-4">
    <?= htmlspecialchars($langText['employee_dashboard'] ?? 'Seu Painel', ENT_QUOTES); ?>
  </h1>

  <!-- Boas-vindas -->
  <p class="mb-6 text-gray-700">
    <?= sprintf(
         htmlspecialchars($langText['welcome_employee'] ?? 'Bem-vindo, %s!', ENT_QUOTES),
         htmlspecialchars($_SESSION['user']['name'], ENT_QUOTES)
       ); ?>
  </p>

  <!-- Cards de Estatísticas -->
  <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <!-- Total de Horas -->
    <div class="bg-white rounded-lg shadow p-6">
      <div class="flex items-center">
        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
        </div>
        <div class="ml-4">
          <p class="text-sm font-medium text-gray-600">
            <?= htmlspecialchars($langText['total_hours'] ?? 'Total de Horas', ENT_QUOTES); ?>
          </p>
          <p class="text-2xl font-semibold text-gray-900" id="totalHoursCard">
            <?= number_format($totalH, 2); ?>h
          </p>
        </div>
      </div>
    </div>

    <!-- Horas Hoje -->
    <div class="bg-white rounded-lg shadow p-6">
      <div class="flex items-center">
        <div class="p-3 rounded-full bg-green-100 text-green-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
          </svg>
        </div>
        <div class="ml-4">
          <p class="text-sm font-medium text-gray-600">Hoje</p>
          <p class="text-2xl font-semibold text-gray-900" id="todayHoursCard">0.00h</p>
        </div>
      </div>
    </div>

    <!-- Horas Semana -->
    <div class="bg-white rounded-lg shadow p-6">
      <div class="flex items-center">
        <div class="p-3 rounded-full bg-purple-100 text-purple-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
          </svg>
        </div>
        <div class="ml-4">
          <p class="text-sm font-medium text-gray-600">Esta Semana</p>
          <p class="text-2xl font-semibold text-gray-900" id="weekHoursCard">0.00h</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Grid de Projetos -->
  <div class="bg-white rounded-lg shadow mb-8">
    <div class="px-6 py-4 border-b border-gray-200">
      <h2 class="text-xl font-semibold text-gray-900">
        <?= htmlspecialchars($langText['my_projects'] ?? 'Meus Projetos', ENT_QUOTES); ?>
      </h2>
    </div>
    
    <div class="p-6">
      <div id="projectsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (empty($projects)): ?>
          <div class="col-span-full text-center py-8">
            <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <p class="text-gray-500 text-lg">
              <?= htmlspecialchars($langText['no_projects_allocated'] ?? 'Nenhum projeto alocado.', ENT_QUOTES); ?>
            </p>
          </div>
        <?php else:
          foreach ($projects as $p):
            switch ($p['status']) {
              case 'in_progress':
                $tagClass = 'bg-blue-500'; $tagText = $langText['active'] ?? 'Em Andamento'; break;
              case 'pending':
                $tagClass = 'bg-yellow-500'; $tagText = $langText['pending'] ?? 'Pendente'; break;
              default:
                $tagClass = 'bg-green-500'; $tagText = $langText['completed'] ?? 'Concluído'; break;
            }
        ?>
          <div
            class="project-item bg-white p-4 sm:p-6 rounded-xl border hover:shadow-md transition cursor-pointer flex flex-col justify-between"
            data-project-id="<?= (int) $p['id']; ?>"
          >
            <div>
              <div class="flex justify-between items-start mb-3">
                <h4 class="text-xl font-bold flex-1"><?= htmlspecialchars($p['name'], ENT_QUOTES); ?></h4>
                <span class="<?= $tagClass; ?> inline-block text-white text-xs sm:text-sm px-2 sm:px-3 py-1 rounded-full font-semibold ml-4">
                  <?= htmlspecialchars($tagText, ENT_QUOTES); ?>
                </span>
              </div>
              <p class="text-sm text-gray-600 mb-1">
                <strong><?= htmlspecialchars($langText['client'] ?? 'Cliente', ENT_QUOTES); ?>:</strong>
                <?= htmlspecialchars($p['client_name'] ?? '—', ENT_QUOTES); ?>
              </p>
              <?php if (!empty($p['location'])): ?>
              <p class="text-sm text-gray-600 mb-1">
                <strong><?= htmlspecialchars($langText['location'] ?? 'Local', ENT_QUOTES); ?>:</strong>
                <?= htmlspecialchars($p['location'], ENT_QUOTES); ?>
              </p>
              <?php endif; ?>
              <p class="text-sm text-gray-600 mb-3">
                <strong><?= htmlspecialchars($langText['budget'] ?? 'Orçamento', ENT_QUOTES); ?>:</strong>
                <?= number_format($p['budget'], 2, ',', '.'); ?>
              </p>
            </div>
          </div>
        <?php endforeach;
        endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- SUBSTITUIR o modal de projeto no dashboard_employee.php -->

<!-- Modal de Detalhes do Projeto -->
<div id="projectDetailsModal" class="fixed inset-0 hidden items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm z-50">
  <div class="bg-white rounded-2xl p-6 sm:p-8 w-full max-w-4xl mx-4 sm:mx-auto max-h-[90vh] overflow-y-auto relative">
    <button id="closeProjectDetailsModal" class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 text-2xl" aria-label="Close">&times;</button>

    <h3 class="text-xl font-bold mb-6" id="projectModalTitle">Detalhes do Projeto</h3>

    <!-- Tabs Navigation -->
    <div class="border-b border-gray-200 mb-6">
      <nav class="flex space-x-8">
        <button data-tab="general" class="py-2 px-1 border-b-2 border-blue-600 text-blue-600 text-sm font-medium">
          Geral
        </button>
        <button data-tab="tasks" class="py-2 px-1 border-b-2 border-transparent text-gray-600 hover:text-gray-800 text-sm font-medium">
          Tarefas
        </button>
        <button data-tab="employees" class="py-2 px-1 border-b-2 border-transparent text-gray-600 hover:text-gray-800 text-sm font-medium">
          Funcionários
        </button>
        <button data-tab="inventory" class="py-2 px-1 border-b-2 border-transparent text-gray-600 hover:text-gray-800 text-sm font-medium">
          Estoque
        </button>
        <?php if (!empty($projects)): ?>
        <button data-tab="timesheet" class="py-2 px-1 border-b-2 border-transparent text-gray-600 hover:text-gray-800 text-sm font-medium">
          Ponto
        </button>
        <?php endif; ?>
      </nav>
    </div>

    <!-- Tab Content -->
    <div class="tab-content">
      
      <!-- ABA GERAL -->
      <div id="tab-general" class="tab-panel">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <h4 class="font-semibold text-gray-900 mb-3">Informações do Projeto</h4>
            <div class="space-y-2">
              <p><strong>Nome:</strong> <span id="projectName">-</span></p>
              <p><strong>Cliente:</strong> <span id="projectClient">-</span></p>
              <p><strong>Status:</strong> <span id="projectStatus">-</span></p>
              <p><strong>Local:</strong> <span id="projectLocation">-</span></p>
              <p><strong>Orçamento:</strong> <span id="projectBudget">-</span></p>
            </div>
          </div>
          <div>
            <h4 class="font-semibold text-gray-900 mb-3">Período</h4>
            <div class="space-y-2">
              <p><strong>Início:</strong> <span id="projectStartDate">-</span></p>
              <p><strong>Término:</strong> <span id="projectEndDate">-</span></p>
              <p><strong>Total de Horas:</strong> <span id="projectTotalHours">0.0h</span></p>
            </div>
          </div>
        </div>
        
        <div class="mt-6">
          <h4 class="font-semibold text-gray-900 mb-3">Descrição</h4>
          <p id="projectDescription" class="text-gray-600">-</p>
        </div>
      </div>

      <!-- ABA TAREFAS -->
      <div id="tab-tasks" class="tab-panel hidden">
        <h4 class="font-semibold text-gray-900 mb-4">Tarefas do Projeto</h4>
        <div id="projectTasksList" class="space-y-3">
          <div class="text-gray-500 text-center py-4">Carregando tarefas...</div>
        </div>
      </div>

      <!-- ABA FUNCIONÁRIOS -->
      <div id="tab-employees" class="tab-panel hidden">
        <h4 class="font-semibold text-gray-900 mb-4">Funcionários no Projeto</h4>
        <div id="projectEmployeesList" class="space-y-3">
          <div class="text-gray-500 text-center py-4">Carregando funcionários...</div>
        </div>
      </div>

      <!-- ABA ESTOQUE -->
      <div id="tab-inventory" class="tab-panel hidden">
        <h4 class="font-semibold text-gray-900 mb-4">Itens de Estoque</h4>
        <div id="projectInventoryList" class="space-y-3">
          <div class="text-gray-500 text-center py-4">Carregando itens...</div>
        </div>
      </div>

      <!-- ABA PONTO -->
      <?php if (!empty($projects)): ?>
      <div id="tab-timesheet" class="tab-panel hidden">
        <div class="mb-6">
          <h4 class="font-semibold text-gray-900 mb-3">
            Total de Horas: <span id="workLogTotal">0</span>h
          </h4>
        </div>
        
        <!-- Formulário de Registro de Ponto -->
        <form id="timeTrackingForm" method="POST" action="<?= BASE_URL ?>/work_logs/store_time_entry" class="mb-6 bg-gray-50 p-4 rounded-lg">
          <input type="hidden" name="project_id" id="timeTrackingProjectId">
          
          <h5 class="font-medium mb-3">Registrar Ponto</h5>
          
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
            <div>
              <label class="block text-gray-700 mb-1">Data</label>
              <input type="date" name="date" id="entryDate" value="<?= date('Y-m-d') ?>" class="w-full p-2 border rounded" required>
            </div>
            
            <div>
              <label class="block text-gray-700 mb-1">Tipo</label>
              <select name="entry_type" class="w-full p-2 border rounded" required>
                <option value="entry">Entrada</option>
                <option value="exit">Saída</option>
              </select>
            </div>
            
            <div>
              <label class="block text-gray-700 mb-1">Horário</label>
              <input type="time" name="time" value="<?= date('H:i'); ?>" class="w-full p-2 border rounded" required>
            </div>
            
            <div class="flex items-end">
              <button type="submit" class="w-full bg-blue-600 text-white p-2 rounded hover:bg-blue-700">
                Registrar
              </button>
            </div>
          </div>
        </form>

        <!-- Lista de Registros -->
        <div id="timeEntriesList" class="space-y-4">
          <div class="text-gray-500 text-center py-4">Nenhum registro de ponto</div>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
// JavaScript para o modal de projeto
document.addEventListener('DOMContentLoaded', function() {
  // Event listeners para abrir modal
  document.querySelectorAll('.project-item').forEach(card => {
    card.addEventListener('click', () => {
      const projectId = card.getAttribute('data-project-id');
      openProjectModal(projectId);
    });
  });

  // Event listener para fechar modal
  document.getElementById('closeProjectDetailsModal').addEventListener('click', closeProjectModal);

  // Event listeners para tabs
  document.querySelectorAll('[data-tab]').forEach(tab => {
    tab.addEventListener('click', (e) => {
      e.preventDefault();
      const tabName = tab.getAttribute('data-tab');
      switchProjectTab(tabName);
    });
  });

  // Event listener para formulário de ponto
  const timeForm = document.getElementById('timeTrackingForm');
  if (timeForm) {
    timeForm.addEventListener('submit', handleTimeEntrySubmit);
  }
});

// Abrir modal do projeto
async function openProjectModal(projectId) {
  const modal = document.getElementById('projectDetailsModal');
  modal.classList.remove('hidden');
  modal.classList.add('flex');
  
  // Define o project_id no formulário de ponto
  const projectIdInput = document.getElementById('timeTrackingProjectId');
  if (projectIdInput) {
    projectIdInput.value = projectId;
  }
  
  // Carrega dados do projeto
  await loadProjectDetails(projectId);
  
  // Ativa primeira aba
  switchProjectTab('general');
}

// Fechar modal
function closeProjectModal() {
  const modal = document.getElementById('projectDetailsModal');
  modal.classList.add('hidden');
  modal.classList.remove('flex');
}

// Trocar aba
function switchProjectTab(tabName) {
  // Remove ativo de todas as tabs
  document.querySelectorAll('[data-tab]').forEach(tab => {
    tab.classList.remove('border-blue-600', 'text-blue-600');
    tab.classList.add('border-transparent', 'text-gray-600');
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
    activeTab.classList.remove('border-transparent', 'text-gray-600');
  }
  
  if (activePanel) {
    activePanel.classList.remove('hidden');
  }
  
  // Carrega dados específicos da aba
  const projectId = document.getElementById('timeTrackingProjectId')?.value;
  if (projectId) {
    switch(tabName) {
      case 'tasks':
        loadProjectTasks(projectId);
        break;
      case 'employees':
        loadProjectEmployees(projectId);
        break;
      case 'inventory':
        loadProjectInventory(projectId);
        break;
      case 'timesheet':
        loadTimeEntries(projectId);
        break;
    }
  }
}

// Carregar detalhes do projeto
async function loadProjectDetails(projectId) {
  try {
    const response = await fetch(`${window.baseUrl}/api/projects/${projectId}`);
    const result = await response.json();
    
    if (result.success && result.project) {
      const project = result.project;
      
      // Preenche dados gerais
      document.getElementById('projectModalTitle').textContent = project.name || 'Projeto';
      document.getElementById('projectName').textContent = project.name || '-';
      document.getElementById('projectClient').textContent = project.client_name || '-';
      document.getElementById('projectStatus').textContent = getStatusText(project.status);
      document.getElementById('projectLocation').textContent = project.location || '-';
      document.getElementById('projectBudget').textContent = formatCurrency(project.budget);
      document.getElementById('projectStartDate').textContent = formatDate(project.start_date);
      document.getElementById('projectEndDate').textContent = formatDate(project.end_date);
      document.getElementById('projectDescription').textContent = project.description || 'Sem descrição';
      document.getElementById('projectTotalHours').textContent = `${parseFloat(project.total_hours_calculated || 0).toFixed(1)}h`;
    }
  } catch (error) {
    console.error('Erro ao carregar projeto:', error);
  }
}

// Carregar tarefas
async function loadProjectTasks(projectId) {
  const container = document.getElementById('projectTasksList');
  container.innerHTML = '<div class="text-gray-500 text-center py-4">Carregando tarefas...</div>';
  
  try {
    const response = await fetch(`${window.baseUrl}/api/projects/${projectId}/tasks`);
    const tasks = await response.json();
    
    if (tasks && tasks.length > 0) {
      container.innerHTML = tasks.map(task => `
        <div class="p-3 border rounded-lg">
          <div class="flex justify-between items-start">
            <div>
              <h5 class="font-medium">${task.title || 'Tarefa'}</h5>
              <p class="text-sm text-gray-600">${task.description || ''}</p>
            </div>
            <span class="px-2 py-1 text-xs rounded ${task.completed ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}">
              ${task.completed ? 'Concluída' : 'Pendente'}
            </span>
          </div>
        </div>
      `).join('');
    } else {
      container.innerHTML = '<div class="text-gray-500 text-center py-4">Nenhuma tarefa encontrada</div>';
    }
  } catch (error) {
    container.innerHTML = '<div class="text-red-500 text-center py-4">Erro ao carregar tarefas</div>';
  }
}

// Carregar funcionários
async function loadProjectEmployees(projectId) {
  const container = document.getElementById('projectEmployeesList');
  container.innerHTML = '<div class="text-gray-500 text-center py-4">Carregando funcionários...</div>';
  
  try {
    const response = await fetch(`${window.baseUrl}/api/projects/${projectId}/employees`);
    const employees = await response.json();
    
    if (employees && employees.length > 0) {
      container.innerHTML = employees.map(emp => `
        <div class="p-3 border rounded-lg">
          <div class="flex justify-between items-center">
            <div>
              <h5 class="font-medium">${emp.name || 'Funcionário'}</h5>
              <p class="text-sm text-gray-600">${emp.function || 'Função não definida'}</p>
            </div>
            <span class="text-sm text-gray-500">${parseFloat(emp.hours_worked || 0).toFixed(1)}h</span>
          </div>
        </div>
      `).join('');
    } else {
      container.innerHTML = '<div class="text-gray-500 text-center py-4">Nenhum funcionário alocado</div>';
    }
  } catch (error) {
    container.innerHTML = '<div class="text-red-500 text-center py-4">Erro ao carregar funcionários</div>';
  }
}

// Carregar inventário
async function loadProjectInventory(projectId) {
  const container = document.getElementById('projectInventoryList');
  container.innerHTML = '<div class="text-gray-500 text-center py-4">Carregando itens...</div>';
  
  try {
    const response = await fetch(`${window.baseUrl}/api/projects/${projectId}/inventory`);
    const items = await response.json();
    
    if (items && items.length > 0) {
      container.innerHTML = items.map(item => `
        <div class="p-3 border rounded-lg">
          <div class="flex justify-between items-center">
            <div>
              <h5 class="font-medium">${item.name || 'Item'}</h5>
              <p class="text-sm text-gray-600">${item.description || ''}</p>
            </div>
            <span class="text-sm text-gray-500">Qtd: ${item.quantity || 0}</span>
          </div>
        </div>
      `).join('');
    } else {
      container.innerHTML = '<div class="text-gray-500 text-center py-4">Nenhum item alocado</div>';
    }
  } catch (error) {
    container.innerHTML = '<div class="text-red-500 text-center py-4">Erro ao carregar inventário</div>';
  }
}

// Carregar registros de ponto
async function loadTimeEntries(projectId) {
  const container = document.getElementById('timeEntriesList');
  const totalElement = document.getElementById('workLogTotal');
  
  try {
    const response = await fetch(`${window.baseUrl}/api/work_logs/time_entries/${projectId}`);
    const result = await response.json();
    
    if (result.entries && result.entries.length > 0) {
      container.innerHTML = result.entries.map(entry => `
        <div class="p-3 border rounded-lg">
          <div class="flex justify-between items-center">
            <div>
              <div class="font-medium">${entry.formatted_display} - ${formatDate(entry.date)}</div>
              <div class="text-sm text-gray-600">${entry.project_name || 'Projeto'}</div>
            </div>
            <span class="font-medium">${parseFloat(entry.total_hours || 0).toFixed(1)}h</span>
          </div>
        </div>
      `).join('');
      
      totalElement.textContent = parseFloat(result.total_hours || 0).toFixed(1);
    } else {
      container.innerHTML = '<div class="text-gray-500 text-center py-4">Nenhum registro de ponto</div>';
      totalElement.textContent = '0';
    }
  } catch (error) {
    container.innerHTML = '<div class="text-red-500 text-center py-4">Erro ao carregar registros</div>';
  }
}

// Submeter formulário de ponto
async function handleTimeEntrySubmit(e) {
  e.preventDefault();
  
  const formData = new FormData(e.target);
  
  try {
    const response = await fetch(e.target.action, {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
      alert('Ponto registrado com sucesso!');
      
      // Recarrega registros
      const projectId = document.getElementById('timeTrackingProjectId').value;
      if (projectId) {
        loadTimeEntries(projectId);
      }
      
      // Limpa horário
      e.target.querySelector('[name="time"]').value = new Date().toTimeString().substring(0, 5);
    } else {
      alert(result.message || 'Erro ao registrar ponto');
    }
  } catch (error) {
    alert('Erro ao registrar ponto');
  }
}

// Funções utilitárias
function getStatusText(status) {
  const statusMap = {
    'in_progress': 'Em Andamento',
    'pending': 'Pendente',
    'completed': 'Concluído',
    'active': 'Ativo'
  };
  return statusMap[status] || status || '-';
}

function formatCurrency(value) {
  if (!value) return '-';
  return new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'EUR'
  }).format(value);
}

function formatDate(dateString) {
  if (!dateString) return '-';
  try {
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR');
  } catch {
    return dateString;
  }
}
</script>