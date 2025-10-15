<?php
// app/views/employees/dashboard_employee.php

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

$projModel = new ProjectModel();
$projects  = $projModel->getByEmployee($empId);
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

  <!-- Card de Total de Horas -->
  <div class="mb-8">
    <div class="bg-white p-6 rounded-lg shadow flex flex-col items-center">
      <h3 class="font-semibold text-gray-600 mb-2">
        <?= htmlspecialchars($langText['total_hours'] ?? 'Total de Horas', ENT_QUOTES) ?>
      </h3>
      <p id="employeeTotalHoursValue" class="text-4xl font-bold">
        <?= number_format($totalH, 2) ?>h
      </p>
    </div>
  </div>

  <!-- Grid de Projetos -->
  <div id="projectsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if (empty($projects)): ?>
      <div class="col-span-full text-center text-gray-500">
        <?= htmlspecialchars($langText['no_projects_allocated'] ?? 'Nenhum projeto alocado.', ENT_QUOTES) ?>
      </div>
    <?php else: foreach ($projects as $p):
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
        class="project-item bg-white p-4 sm:p-6 rounded-xl shadow hover:shadow-md transition cursor-pointer flex flex-col justify-between"
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
    <?php endforeach; endif; ?>
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
        <li>
          <button data-tab="pontos" type="button"
                  class="tab-btn pb-2 font-medium text-gray-600 hover:text-gray-800 border-b-2 border-transparent">
            <?= htmlspecialchars($langText['time_tracking'] ?? 'Ponto', ENT_QUOTES) ?>
          </button>
        </li>
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
  </div>
</div>

<script>
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

  // Função para carregar registros de ponto
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

  // Submissão do formulário de ponto
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
</script>

<script src="<?= BASE_URL ?>/public/js/employee-projects.js?v=<?= time() ?>" defer></script>
<script defer src="<?= asset('js/header.js') ?>"></script>