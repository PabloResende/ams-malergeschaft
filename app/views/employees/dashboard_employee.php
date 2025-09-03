<?php
// app/views/employees/dashboard_employee.php - ARQUIVO COMPLETO ATUALIZADO

require __DIR__.'/../layout/header.php';

// Get employee info
$userId = (int) ($_SESSION['user']['id'] ?? 0);
$empModel = new Employee();
$emp = $empModel->findByUserId($userId);
$empId = $emp['id'] ?? 0;

// Get total hours from new system
require_once __DIR__.'/../../models/TimeEntryModel.php';
$timeEntryModel = new TimeEntryModel();
$totalHours = $timeEntryModel->getTotalHoursByEmployee($empId);
?>

<div class="min-h-screen bg-gray-100 py-6">
  <div class="max-w-7xl mx-auto px-4">
    
    <!-- Header com estatísticas -->
    <div class="mb-6">
      <h1 class="text-3xl font-bold text-gray-900 mb-4">
        <?= htmlspecialchars($langText['employee_dashboard'] ?? 'Dashboard do Funcionário', ENT_QUOTES); ?>
      </h1>
      
      <!-- Cards de estatísticas -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold text-gray-700 mb-2">
            <?= htmlspecialchars($langText['total_projects'] ?? 'Projetos Ativos', ENT_QUOTES); ?>
          </h3>
          <p class="text-3xl font-bold text-blue-600"><?= count($projects); ?></p>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold text-gray-700 mb-2">
            <?= htmlspecialchars($langText['total_hours_worked'] ?? 'Horas Trabalhadas', ENT_QUOTES); ?>
          </h3>
          <p class="text-3xl font-bold text-green-600"><?= number_format($totalHours, 2, ',', '.'); ?>h</p>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold text-gray-700 mb-2">
            <?= htmlspecialchars($langText['this_month'] ?? 'Este Mês', ENT_QUOTES); ?>
          </h3>
          <p class="text-3xl font-bold text-purple-600">0h</p>
        </div>
      </div>
    </div>

    <!-- Lista de Projetos -->
    <div class="mb-8">
      <h2 class="text-2xl font-bold text-gray-900 mb-4">
        <?= htmlspecialchars($langText['my_projects'] ?? 'Meus Projetos', ENT_QUOTES); ?>
      </h2>
      
      <?php if (empty($projects)): ?>
        <div class="bg-white rounded-lg shadow p-6 text-center">
          <p class="text-gray-500">
            <?= htmlspecialchars($langText['no_projects_assigned'] ?? 'Nenhum projeto atribuído', ENT_QUOTES); ?>
          </p>
        </div>
      <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <?php foreach ($projects as $project): ?>
            <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow cursor-pointer project-item"
                 data-project-id="<?= htmlspecialchars($project['id'], ENT_QUOTES); ?>">
              <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">
                  <?= htmlspecialchars($project['name'], ENT_QUOTES); ?>
                </h3>
                <p class="text-sm text-gray-600 mb-4">
                  <?= htmlspecialchars($project['client_name'] ?? 'Cliente não definido', ENT_QUOTES); ?>
                </p>
                
                <!-- Progress bar -->
                <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
                  <div class="bg-blue-600 h-2 rounded-full" 
                       style="width: <?= min(100, max(0, (int) ($project['progress'] ?? 0))); ?>%"></div>
                </div>
                
                <div class="flex justify-between text-sm text-gray-500">
                  <span><?= htmlspecialchars($project['location'] ?? 'Local não definido', ENT_QUOTES); ?></span>
                  <span><?= (int) ($project['progress'] ?? 0); ?>%</span>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

  </div>
</div>

<!-- Modal de Detalhes do Projeto -->
<div id="projectDetailsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
  <div class="bg-white rounded-lg shadow-lg max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
    <div class="p-6">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Detalhes do Projeto</h2>
        <button id="closeProjectDetailsModal" class="text-gray-400 hover:text-gray-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>

      <!-- Abas -->
      <div class="border-b border-gray-200 mb-6">
        <nav class="-mb-px flex space-x-8">
          <button class="tab-btn border-b-2 border-blue-600 text-blue-600 py-2 px-1 text-sm font-medium" data-tab="geral">
            Geral
          </button>
          <button class="tab-btn border-b-2 border-transparent text-gray-500 hover:text-gray-700 py-2 px-1 text-sm font-medium" data-tab="tarefas">
            Tarefas
          </button>
          <button class="tab-btn border-b-2 border-transparent text-gray-500 hover:text-gray-700 py-2 px-1 text-sm font-medium" data-tab="funcionarios">
            Funcionários
          </button>
          <button class="tab-btn border-b-2 border-transparent text-gray-500 hover:text-gray-700 py-2 px-1 text-sm font-medium" data-tab="inventario">
            Inventário
          </button>
          <button class="tab-btn border-b-2 border-transparent text-gray-500 hover:text-gray-700 py-2 px-1 text-sm font-medium" data-tab="ponto">
            Registrar Ponto
          </button>
        </nav>
      </div>

      <!-- Conteúdo das Abas -->
      
      <!-- Aba Geral -->
      <div id="tab-geral" class="tab-panel">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-gray-700 font-semibold">Nome do Projeto</label>
            <p id="roName" class="text-gray-900"></p>
          </div>
          <div>
            <label class="block text-gray-700 font-semibold">Cliente</label>
            <p id="roClient" class="text-gray-900"></p>
          </div>
          <div>
            <label class="block text-gray-700 font-semibold">Localização</label>
            <p id="roLocation" class="text-gray-900"></p>
          </div>
          <div>
            <label class="block text-gray-700 font-semibold">Data de Início</label>
            <p id="roStart" class="text-gray-900"></p>
          </div>
          <div>
            <label class="block text-gray-700 font-semibold">Data de Fim</label>
            <p id="roEnd" class="text-gray-900"></p>
          </div>
        </div>
      </div>

      <!-- Aba Tarefas -->
      <div id="tab-tarefas" class="tab-panel hidden">
        <h4 class="text-lg font-semibold mb-4">Tarefas do Projeto</h4>
        <ul id="roTasks" class="list-disc pl-5 space-y-2"></ul>
      </div>

      <!-- Aba Funcionários -->
      <div id="tab-funcionarios" class="tab-panel hidden">
        <h4 class="text-lg font-semibold mb-4">Funcionários Alocados</h4>
        <ul id="roEmployees" class="list-disc pl-5 space-y-2"></ul>
      </div>

      <!-- Aba Inventário -->
      <div id="tab-inventario" class="tab-panel hidden">
        <h4 class="text-lg font-semibold mb-4">Inventário Alocado</h4>
        <ul id="roInventory" class="list-disc pl-5 space-y-2"></ul>
      </div>

      <!-- Aba Registrar Ponto - NOVA -->
      <div id="tab-ponto" class="tab-panel hidden">
        <h4 class="text-lg font-semibold mb-4">
          <?= htmlspecialchars($langText['total_hours'] ?? 'Total Horas', ENT_QUOTES); ?>:
          <span id="workLogTotal">0</span>h
        </h4>
        
        <!-- Formulário de Registro de Ponto -->
        <form id="timeTrackingForm"
              method="POST"
              action="<?= BASE_URL; ?>/work_logs/store_time_entry"
              class="mb-6 bg-gray-50 p-4 rounded-lg"
        >
          <input type="hidden" name="project_id" id="timeTrackingProjectId">
          
          <h5 class="font-medium mb-3"><?= htmlspecialchars($langText['register_time_entry'] ?? 'Registrar Ponto', ENT_QUOTES); ?></h5>
          
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
            <div>
              <label class="block text-gray-700 mb-1">
                <?= htmlspecialchars($langText['input_date_label'] ?? 'Data', ENT_QUOTES); ?>
              </label>
              <input type="date" 
                     name="date" 
                     id="entryDate"
                     value="<?= date('Y-m-d'); ?>"
                     class="w-full p-2 border rounded" 
                     required>
            </div>
            
            <div>
              <label class="block text-gray-700 mb-1">
                <?= htmlspecialchars($langText['entry_type'] ?? 'Tipo', ENT_QUOTES); ?>
              </label>
              <select name="entry_type" class="w-full p-2 border rounded" required>
                <option value="entry"><?= htmlspecialchars($langText['entry'] ?? 'Entrada', ENT_QUOTES); ?></option>
                <option value="exit"><?= htmlspecialchars($langText['exit'] ?? 'Saída', ENT_QUOTES); ?></option>
              </select>
            </div>
            
            <div>
              <label class="block text-gray-700 mb-1">
                <?= htmlspecialchars($langText['input_time_label'] ?? 'Horário', ENT_QUOTES); ?>
              </label>
              <input type="time" 
                     name="time" 
                     value="<?= date('H:i'); ?>"
                     class="w-full p-2 border rounded" 
                     required>
            </div>
            
            <div class="flex items-end">
              <button type="submit"
                      class="w-full bg-blue-600 text-white p-2 rounded hover:bg-blue-700">
                <?= htmlspecialchars($langText['button_register_entry'] ?? 'Registrar', ENT_QUOTES); ?>
              </button>
            </div>
          </div>
        </form>

        <!-- Lista de Registros Agrupados por Data -->
        <div id="timeEntriesList" class="space-y-4">
          <div class="text-gray-500 text-center py-4">
            <?= htmlspecialchars($langText['no_time_entries'] ?? 'Nenhum registro de ponto', ENT_QUOTES); ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  window.baseUrl = '<?= BASE_URL; ?>';
  
  // abre modal ao clicar num card
  document.querySelectorAll('.project-item').forEach(card => {
    card.addEventListener('click', () => {
      const id = card.getAttribute('data-project-id');
      const modal = document.getElementById('projectDetailsModal');
      modal.classList.remove('hidden');
      modal.classList.add('flex');
      
      // Define o project_id no formulário de ponto
      document.getElementById('timeTrackingProjectId').value = id;
      
      // carregar detalhes via fetch
      fetch(`${window.baseUrl}/api/projects/${id}`)
        .then(res => res.json())
        .then(data => {
          // Preenche abas gerais
          document.getElementById('roName').textContent = data.name || '';
          document.getElementById('roClient').textContent = data.client_name || '—';
          document.getElementById('roLocation').textContent = data.location || '—';
          document.getElementById('roStart').textContent = data.start_date || '';
          document.getElementById('roEnd').textContent = data.end_date || '';
          
          // Preenche tarefas
          const tasksList = document.getElementById('roTasks');
          tasksList.innerHTML = (data.tasks || [])
            .map(t => `<li>${t.description}</li>`)
            .join('') || '<li class="text-gray-500">Nenhuma tarefa</li>';
          
          // Preenche funcionários
          const empList = document.getElementById('roEmployees');
          empList.innerHTML = (data.employees || [])
            .map(e => `<li>${e.name}</li>`)
            .join('') || '<li class="text-gray-500">Nenhum funcionário</li>';
          
          // Preenche inventário
          const invList = document.getElementById('roInventory');
          invList.innerHTML = (data.inventory || [])
            .map(i => `<li>${i.name} - Qtd: ${i.quantity}</li>`)
            .join('') || '<li class="text-gray-500">Nenhum item</li>';
          
          // Carrega registros de ponto
          loadTimeEntries(id);
        })
        .catch(() => {
          console.error('Falha ao buscar detalhes do projeto');
        });
    });
  });

  // fecha modal
  document.getElementById('closeProjectDetailsModal')
    .addEventListener('click', () => {
      const modal = document.getElementById('projectDetailsModal');
      modal.classList.add('hidden');
      modal.classList.remove('flex');
    });
  
  // Gerenciamento de abas
  document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const tab = btn.getAttribute('data-tab');
      
      // Remove active de todos os botões
      document.querySelectorAll('.tab-btn').forEach(b => {
        b.classList.remove('border-blue-600', 'text-blue-600');
        b.classList.add('text-gray-600');
      });
      
      // Adiciona active no botão clicado
      btn.classList.add('border-blue-600', 'text-blue-600');
      btn.classList.remove('text-gray-600');
      
      // Esconde todos os painéis
      document.querySelectorAll('.tab-panel').forEach(panel => {
        panel.classList.add('hidden');
      });
      
      // Mostra o painel selecionado
      document.getElementById(`tab-${tab}`).classList.remove('hidden');
    });
  });

  // Função para carregar registros de ponto - NOVA IMPLEMENTAÇÃO
  function loadTimeEntries(projectId) {
    fetch(`${window.baseUrl}/api/work_logs/time_entries/${projectId}`)
      .then(res => res.json())
      .then(data => {
        const container = document.getElementById('timeEntriesList');
        const totalHours = document.getElementById('workLogTotal');
        
        if (!data.entries || data.entries.length === 0) {
          container.innerHTML = '<div class="text-gray-500 text-center py-4">Nenhum registro de ponto</div>';
          totalHours.textContent = '0.00';
          return;
        }
        
        // Monta HTML com novo formato
        let html = '';
        data.entries.forEach(entry => {
          html += `
            <div class="bg-white p-4 rounded-lg border">
              <div class="font-medium text-gray-900 mb-2">
                ${entry.formatted_display}
              </div>
              <div class="text-sm text-gray-600">
                Total do dia: ${parseFloat(entry.total_hours).toFixed(2)}h
              </div>
            </div>
          `;
        });
        
        container.innerHTML = html;
        totalHours.textContent = data.total_hours || '0.00';
      })
      .catch(() => {
        document.getElementById('timeEntriesList').innerHTML = 
          '<div class="text-red-500 text-center py-4">Erro ao carregar registros</div>';
      });
  }

  // Submissão do formulário de ponto - NOVO ENDPOINT
  document.getElementById('timeTrackingForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch(this.action, {
      method: 'POST',
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        // Recarrega a lista
        const projectId = document.getElementById('timeTrackingProjectId').value;
        loadTimeEntries(projectId);
        
        // Limpa o horário (mantém data e project_id)
        this.querySelector('[name="time"]').value = new Date().toTimeString().substring(0, 5);
        
        // Feedback visual
        const button = this.querySelector('button[type="submit"]');
        const originalText = button.textContent;
        button.textContent = 'Registrado!';
        button.classList.add('bg-green-600');
        button.classList.remove('bg-blue-600');
        
        setTimeout(() => {
          button.textContent = originalText;
          button.classList.remove('bg-green-600');
          button.classList.add('bg-blue-600');
        }, 2000);
        
      } else {
        alert(data.message || 'Erro ao registrar ponto');
      }
    })
    .catch(() => {
      alert('Erro ao registrar ponto');
    });
  });
</script>

<script src="<?= BASE_URL; ?>/public/js/employee-projects.js?v=<?= time(); ?>" defer></script>
<script defer src="<?= asset('js/header.js'); ?>"></script>

<?php require __DIR__.'/../layout/footer.php'; ?>

   