<?php
// app/views/employees/dashboard_employee.php - VERSÃO CORRIGIDA

require __DIR__ . '/../layout/header_employee.php';

require_once __DIR__ . '/../../../app/models/WorkLogModel.php';
require_once __DIR__ . '/../../../app/models/Employees.php';
require_once __DIR__ . '/../../../app/models/Project.php';

$wlModel   = new WorkLogModel();
$userId    = (int)($_SESSION['user']['id'] ?? 0);
$empModel  = new Employee();
$emp       = $empModel->findByUserId($userId);
$empId     = $emp['id'] ?? 0;
$totalH    = $wlModel->getTotalHoursByEmployee($empId);

// CORREÇÃO: Usar a variável $projects que vem do controller
// Se não houver, buscar aqui como fallback
if (!isset($projects) || empty($projects)) {
    $projModel = new ProjectModel();
    $projects  = $projModel->getByEmployee($empId);
}
?>
<script>
  window.baseUrl  = <?= json_encode(BASE_URL, JSON_UNESCAPED_SLASHES) ?>;
  window.langText = <?= json_encode($langText, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_QUOT|JSON_HEX_APOS) ?>;
</script>

<div class="pt-20 px-4 sm:px-8 sm:ml-56 pb-8">
  <!-- Título -->
  <h1 class="text-3xl font-bold mb-4">
    <?= htmlspecialchars($langText['employee_dashboard'] ?? 'Seu Painel', ENT_QUOTES) ?>
  </h1>

  <!-- Boas-vindas -->
  <p class="mb-6 text-gray-700">
    <?= sprintf(
         htmlspecialchars($langText['welcome_employee'] ?? 'Bem-vindo, %s!', ENT_QUOTES),
         htmlspecialchars($_SESSION['user']['name'], ENT_QUOTES)
       ) ?>
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
            <?= htmlspecialchars($langText['total_hours'] ?? 'Total de Horas', ENT_QUOTES) ?>
          </p>
          <p class="text-2xl font-semibold text-gray-900" id="totalHoursCard">
            <?= number_format($totalH, 2) ?>h
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
        <?= htmlspecialchars($langText['my_projects'] ?? 'Meus Projetos', ENT_QUOTES) ?>
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
              <?= htmlspecialchars($langText['no_projects_allocated'] ?? 'Nenhum projeto alocado.', ENT_QUOTES) ?>
            </p>
          </div>
        <?php else: 
          foreach ($projects as $p):
            switch ($p['status']) {
              case 'in_progress':
                $tagClass = 'bg-blue-500';   $tagText = $langText['active']    ?? 'Em Andamento'; break;
              case 'pending':
                $tagClass = 'bg-yellow-500'; $tagText = $langText['pending']   ?? 'Pendente';     break;
              default:
                $tagClass = 'bg-green-500';  $tagText = $langText['completed'] ?? 'Concluído';    break;
            }
        ?>
          <div
            class="project-item bg-white p-4 sm:p-6 rounded-xl border hover:shadow-md transition cursor-pointer flex flex-col justify-between"
            data-project-id="<?= (int)$p['id'] ?>"
          >
            <div>
              <div class="flex justify-between items-start mb-3">
                <h4 class="text-xl font-bold flex-1"><?= htmlspecialchars($p['name'], ENT_QUOTES) ?></h4>
                <span class="<?= $tagClass ?> inline-block text-white text-xs sm:text-sm px-2 sm:px-3 py-1 rounded-full font-semibold ml-4">
                  <?= htmlspecialchars($tagText, ENT_QUOTES) ?>
                </span>
              </div>
              <p class="text-sm text-gray-600 mb-1">
                <strong><?= htmlspecialchars($langText['client'] ?? 'Cliente', ENT_QUOTES) ?>:</strong>
                <?= htmlspecialchars($p['client_name'] ?? '—', ENT_QUOTES) ?>
              </p>
              <?php if (!empty($p['location'])): ?>
              <p class="text-sm text-gray-600 mb-1">
                <strong><?= htmlspecialchars($langText['location'] ?? 'Local', ENT_QUOTES) ?>:</strong>
                <?= htmlspecialchars($p['location'], ENT_QUOTES) ?>
              </p>
              <?php endif; ?>
              <p class="text-sm text-gray-600 mb-3">
                <strong><?= htmlspecialchars($langText['budget'] ?? 'Orçamento', ENT_QUOTES) ?>:</strong>
                <?= number_format($p['budget'], 2, ',', '.') ?>
              </p>
            </div>
          </div>
        <?php endforeach; 
        endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Modal de Detalhes do Projeto -->
<div
  id="projectDetailsModal"
  class="fixed inset-0 hidden items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm z-50"
>
  <div class="bg-white rounded-2xl p-6 sm:p-8 w-full max-w-3xl mx-4 sm:mx-auto max-h-[90vh] overflow-y-auto relative">
    <button
      id="closeProjectDetailsModal"
      class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 text-2xl"
      aria-label="Close"
    >&times;</button>

    <h3 class="text-xl font-bold mb-4">
      <?= htmlspecialchars($langText['project_details'] ?? 'Detalhes do Projeto', ENT_QUOTES) ?>
    </h3>

    <!-- Abas de Visualização -->
    <nav class="mb-6">
      <ul class="flex flex-wrap gap-4 border-b">
        <li>
          <button data-tab="geral"
                  class="tab-btn pb-2 font-medium text-gray-600 hover:text-gray-800 border-b-2 border-transparent">
            <?= htmlspecialchars($langText['general'] ?? 'Geral', ENT_QUOTES) ?>
          </button>
        </li>
        <li>
          <button data-tab="tarefas"
                  class="tab-btn pb-2 font-medium text-gray-600 hover:text-gray-800 border-b-2 border-transparent">
            <?= htmlspecialchars($langText['tasks'] ?? 'Tarefas', ENT_QUOTES) ?>
          </button>
        </li>
        <li>
          <button data-tab="funcionarios"
                  class="tab-btn pb-2 font-medium text-gray-600 hover:text-gray-800 border-b-2 border-transparent">
            <?= htmlspecialchars($langText['employees'] ?? 'Funcionários', ENT_QUOTES) ?>
          </button>
        </li>
        <li>
          <button data-tab="inventario"
                  class="tab-btn pb-2 font-medium text-gray-600 hover:text-gray-800 border-b-2 border-transparent">
            <?= htmlspecialchars($langText['inventory'] ?? 'Inventário', ENT_QUOTES) ?>
          </button>
        </li>
        <!-- CORREÇÃO: Sistema de ponto só aparece se tiver projetos -->
        <?php if (!empty($projects)): ?>
        <li>
          <button data-tab="pontos" type="button"
                  class="tab-btn pb-2 font-medium text-gray-600 hover:text-gray-800 border-b-2 border-transparent">
            <?= htmlspecialchars($langText['time_tracking'] ?? 'Ponto', ENT_QUOTES) ?>
          </button>
        </li>
        <?php endif; ?>
      </ul>
    </nav>

    <!-- Conteúdo das Abas -->
    <div id="tab-geral" class="tab-panel">
      <p><strong><?= htmlspecialchars($langText['name'] ?? 'Nome', ENT_QUOTES) ?>:</strong> <span id="roName"></span></p>
      <p><strong><?= htmlspecialchars($langText['client'] ?? 'Cliente', ENT_QUOTES) ?>:</strong> <span id="roClient"></span></p>
      <p><strong><?= htmlspecialchars($langText['location'] ?? 'Local', ENT_QUOTES) ?>:</strong> <span id="roLocation"></span></p>
      <p><strong><?= htmlspecialchars($langText['start_date'] ?? 'Início', ENT_QUOTES) ?>:</strong> <span id="roStart"></span></p>
      <p><strong><?= htmlspecialchars($langText['end_date'] ?? 'Término', ENT_QUOTES) ?>:</strong> <span id="roEnd"></span></p>
    </div>

    <div id="tab-tarefas" class="tab-panel hidden">
      <ul id="roTasks" class="list-disc list-inside text-gray-700"></ul>
    </div>

    <div id="tab-funcionarios" class="tab-panel hidden">
      <ul id="roEmployees" class="list-disc list-inside text-gray-700"></ul>
    </div>

    <div id="tab-inventario" class="tab-panel hidden">
      <ul id="roInventory" class="list-disc list-inside text-gray-700"></ul>
    </div>

    <!-- CORREÇÃO: Aba de ponto só aparece se tiver projetos -->
    <?php if (!empty($projects)): ?>
    <div id="tab-pontos" class="tab-panel hidden">
      <div class="mb-4">
        <h4 class="font-semibold text-gray-600">
          <?= htmlspecialchars($langText['total_hours_registered'] ?? 'Total Horas', ENT_QUOTES) ?>:
          <span id="workLogTotal">0</span>h
        </h4>
      </div>
      
      <!-- Formulário de Registro de Ponto -->
      <form id="timeTrackingForm"
            method="POST"
            action="<?= BASE_URL ?>/work_logs/store_time_entry"
            class="mb-6 bg-gray-50 p-4 rounded-lg"
      >
        <input type="hidden" name="project_id" id="timeTrackingProjectId">
        
        <h5 class="font-medium mb-3"><?= htmlspecialchars($langText['register_time_entry'] ?? 'Registrar Ponto', ENT_QUOTES) ?></h5>
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
          <div>
            <label class="block text-gray-700 mb-1">
              <?= htmlspecialchars($langText['input_date_label'] ?? 'Data', ENT_QUOTES) ?>
            </label>
            <input type="date" 
                   name="date" 
                   id="entryDate"
                   value="<?= date('Y-m-d') ?>"
                   class="w-full p-2 border rounded" 
                   required>
          </div>
          
          <div>
            <label class="block text-gray-700 mb-1">
              <?= htmlspecialchars($langText['entry_type'] ?? 'Tipo', ENT_QUOTES) ?>
            </label>
            <select name="entry_type" class="w-full p-2 border rounded" required>
              <option value="entry"><?= htmlspecialchars($langText['entry'] ?? 'Entrada', ENT_QUOTES) ?></option>
              <option value="exit"><?= htmlspecialchars($langText['exit'] ?? 'Saída', ENT_QUOTES) ?></option>
            </select>
          </div>
          
          <div>
            <label class="block text-gray-700 mb-1">
              <?= htmlspecialchars($langText['input_time_label'] ?? 'Horário', ENT_QUOTES) ?>
            </label>
            <input type="time" 
                   name="time" 
                   value="<?= date('H:i') ?>"
                   class="w-full p-2 border rounded" 
                   required>
          </div>
          
          <div class="flex items-end">
            <button type="submit"
                    class="w-full bg-blue-600 text-white p-2 rounded hover:bg-blue-700">
              <?= htmlspecialchars($langText['button_register_entry'] ?? 'Registrar', ENT_QUOTES) ?>
            </button>
          </div>
        </div>
      </form>

      <!-- Lista de Registros Agrupados por Data -->
      <div id="timeEntriesList" class="space-y-4">
        <div class="text-gray-500 text-center py-4">
          <?= htmlspecialchars($langText['no_time_entries'] ?? 'Nenhum registro de ponto', ENT_QUOTES) ?>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<script>
  // Carrega estatísticas iniciais
  document.addEventListener('DOMContentLoaded', function() {
    loadDashboardStats();
  });

  async function loadDashboardStats() {
    try {
      const response = await fetch(`${window.baseUrl}/api/employees/hours-summary`);
      const data = await response.json();
      
      document.getElementById('todayHoursCard').textContent = `${data.today || '0.00'}h`;
      document.getElementById('weekHoursCard').textContent = `${data.week || '0.00'}h`;
      
    } catch (error) {
      console.error('Erro ao carregar estatísticas:', error);
    }
  }

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
          
          // Carrega registros de ponto (só se tiver projetos)
          <?php if (!empty($projects)): ?>
          loadTimeEntries(id);
          <?php endif; ?>
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

  <?php if (!empty($projects)): ?>
  // Função para carregar registros de ponto (só se tiver projetos)
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
        
        // Agrupa por data
        const groupedByDate = {};
        data.entries.forEach(entry => {
          if (!groupedByDate[entry.date]) {
            groupedByDate[entry.date] = [];
          }
          groupedByDate[entry.date].push(entry);
        });
        
        // Monta HTML
        let html = '';
        Object.keys(groupedByDate).sort().reverse().forEach(date => {
          const entries = groupedByDate[date];
          const dateFormatted = new Date(date + 'T00:00:00').toLocaleDateString('pt-BR');
          
          // Separa entradas e saídas
          const timeEntries = entries.sort((a, b) => a.time.localeCompare(b.time));
          const pairs = [];
          let currentEntry = null;
          
          timeEntries.forEach(entry => {
            if (entry.entry_type === 'entry') {
              if (currentEntry) {
                // Entrada sem saída correspondente
                pairs.push({ entry: currentEntry, exit: null });
              }
              currentEntry = entry;
            } else if (entry.entry_type === 'exit') {
              if (currentEntry) {
                pairs.push({ entry: currentEntry, exit: entry });
                currentEntry = null;
              } else {
                // Saída sem entrada correspondente
                pairs.push({ entry: null, exit: entry });
              }
            }
          });
          
          // Adiciona entrada pendente se houver
          if (currentEntry) {
            pairs.push({ entry: currentEntry, exit: null });
          }
          
          // Monta string de horários
          const timeRanges = pairs.map(pair => {
            const entryTime = pair.entry ? pair.entry.time.substring(0, 5) : '?';
            const exitTime = pair.exit ? pair.exit.time.substring(0, 5) : '?';
            return `entrada ${entryTime} saída ${exitTime}`;
          }).join(' - ');
          
          html += `
            <div class="bg-white p-4 rounded-lg border">
              <div class="font-medium text-gray-900 mb-2">
                ${timeRanges} ${dateFormatted}
              </div>
              <div class="text-sm text-gray-600">
                Total de registros: ${entries.length}
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

  // Submissão do formulário de ponto (só se tiver projetos)
  const timeTrackingForm = document.getElementById('timeTrackingForm');
  if (timeTrackingForm) {
    timeTrackingForm.addEventListener('submit', function(e) {
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
          
          // Limpa o formulário (mantém data e project_id)
          this.querySelector('[name="time"]').value = new Date().toTimeString().substring(0, 5);
        } else {
          alert(data.message || 'Erro ao registrar ponto');
        }
      })
      .catch(() => {
        alert('Erro ao registrar ponto');
      });
    });
  }
  <?php endif; ?>
</script>

<script src="<?= BASE_URL ?>/public/js/employee-projects.js?v=<?= time() ?>" defer></script>
<script defer src="<?= asset('js/header.js') ?>"></script>