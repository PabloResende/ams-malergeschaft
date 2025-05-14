<?php
require_once __DIR__ . '/../layout/header.php';

$pdo = Database::connect();

// filtros
$filter = $_GET['filter'] ?? '';
$query  = "
    SELECT p.*, c.name AS client_name
    FROM projects p
    LEFT JOIN client c ON p.client_id = c.id
";
if ($filter === 'active') {
    $query .= " WHERE p.status = 'in_progress'";
} elseif ($filter === 'pending') {
    $query .= " WHERE p.status = 'pending'";
} elseif ($filter === 'completed') {
    $query .= " WHERE p.status = 'completed'";
}
$query .= $filter === 'active'
    ? " ORDER BY p.end_date ASC"
    : " ORDER BY p.created_at DESC";

$stmt     = $pdo->prepare($query);
$stmt->execute();
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// funcionários ativos para seleção
$activeEmployees = $pdo
    ->query("SELECT id, name, last_name FROM employees WHERE active = 1")
    ->fetchAll(PDO::FETCH_ASSOC);

// lista de clientes para criação
$clients = $pdo
    ->query("SELECT id, name FROM client ORDER BY name ASC")
    ->fetchAll(PDO::FETCH_ASSOC);

$baseUrl = '/ams-malergeschaft/public';
?>
<div class="ml-56 pt-20 p-8 relative">
  <h1 class="text-2xl font-bold mb-4">
    <?= htmlspecialchars($langText['projects'] ?? 'Projects', ENT_QUOTES) ?>
  </h1>

  <!-- filtros -->
  <div class="mb-6">
    <span class="mr-4 font-semibold">
      <?= htmlspecialchars($langText['filter_by_status'] ?? 'Filter by status:', ENT_QUOTES) ?>
    </span>
    <a href="<?= $baseUrl ?>/projects"
       class="mr-2 px-3 py-1 rounded-full border <?= $filter=='' ? 'bg-gray-300' : 'bg-white' ?>">
      <?= htmlspecialchars($langText['all'] ?? 'All', ENT_QUOTES) ?>
    </a>
    <a href="<?= $baseUrl ?>/projects?filter=active"
       class="mr-2 px-3 py-1 rounded-full border <?= $filter=='active' ? 'bg-blue-200 text-blue-800' : 'bg-white' ?>">
      <?= htmlspecialchars($langText['active'] ?? 'Active', ENT_QUOTES) ?>
    </a>
    <a href="<?= $baseUrl ?>/projects?filter=pending"
       class="mr-2 px-3 py-1 rounded-full border <?= $filter=='pending' ? 'bg-yellow-200 text-yellow-800' : 'bg-white' ?>">
      <?= htmlspecialchars($langText['pending'] ?? 'Pending', ENT_QUOTES) ?>
    </a>
    <a href="<?= $baseUrl ?>/projects?filter=completed"
       class="mr-2 px-3 py-1 rounded-full border <?= $filter=='completed' ? 'bg-green-200 text-green-800' : 'bg-white' ?>">
      <?= htmlspecialchars($langText['completed'] ?? 'Completed', ENT_QUOTES) ?>
    </a>
  </div>

  <!-- grid de cards -->
  <div id="projectsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
    <?php if (empty($projects)): ?>
      <p><?= htmlspecialchars($langText['no_projects_available'] ?? 'No projects available.', ENT_QUOTES) ?></p>
    <?php else: foreach ($projects as $project):
        // badge de status
        switch ($project['status']) {
          case 'in_progress':
            $tagClass = 'bg-blue-500'; $tagText = $langText['active'] ?? 'Active';
            break;
          case 'pending':
            $tagClass = 'bg-yellow-500'; $tagText = $langText['pending'] ?? 'Pending';
            break;
          default:
            $tagClass = 'bg-green-500'; $tagText = $langText['completed'] ?? 'Completed';
        }
        // progresso
        $tstmt = $pdo->prepare("SELECT completed FROM tasks WHERE project_id = ?");
        $tstmt->execute([$project['id']]);
        $tdata = $tstmt->fetchAll(PDO::FETCH_ASSOC);
        $done  = array_reduce($tdata, fn($c,$t)=>$c+(int)$t['completed'], 0);
        $pct   = count($tdata) ? round($done/count($tdata)*100) : 0;
        // dias restantes
        $today   = new DateTime();
        $endDate = new DateTime($project['end_date']);
        $days    = (int)$today->diff($endDate)->format('%r%a');
        if ($days < 0)         $daysClass = 'text-red-600';
        elseif ($days > 10)    $daysClass = 'text-green-600';
        elseif ($days > 5)     $daysClass = 'text-gray-600';
        elseif ($days > 2)     $daysClass = 'text-orange-500';
        else                   $daysClass = 'text-red-600';
    ?>
      <div class="project-item bg-white p-6 rounded-xl shadow hover:shadow-md cursor-pointer"
           data-project-id="<?= htmlspecialchars($project['id'], ENT_QUOTES) ?>">
        <div class="flex justify-between items-center mb-2">
          <h4 class="text-xl font-bold flex-1"><?= htmlspecialchars($project['name'], ENT_QUOTES) ?></h4>
          <span class="<?= $tagClass ?> text-white px-3 py-1 rounded-full text-sm font-semibold">
            <?= htmlspecialchars($tagText, ENT_QUOTES) ?>
          </span>
        </div>
        <p class="text-sm text-gray-600 mb-1">
          <span class="font-semibold"><?= htmlspecialchars($langText['client'] ?? 'Client', ENT_QUOTES) ?>:</span>
          <?= htmlspecialchars($project['client_name'] ?? '—', ENT_QUOTES) ?>
        </p>
        <p class="text-sm text-gray-600 mb-1">
          <span class="font-semibold"><?= htmlspecialchars($langText['location'] ?? 'Location', ENT_QUOTES) ?>:</span>
          <?= htmlspecialchars($project['location'], ENT_QUOTES) ?>
        </p>
        <p class="text-sm font-semibold <?= $daysClass ?> mb-2">
          <?php if ($days >= 0): ?>
            <?= htmlspecialchars($langText['days_remaining'] ?? 'Dias restantes', ENT_QUOTES) ?>: <?= $days ?>
          <?php else: ?>
            <?= abs($days) ?> <?= htmlspecialchars($langText['days_overdue'] ?? 'dias atrasado', ENT_QUOTES) ?>
          <?php endif; ?>
        </p>
        <div class="w-full bg-gray-200 rounded-full h-2 mb-1">
          <div class="bg-blue-500 h-2 rounded-full" style="width:<?= $pct ?>%"></div>
        </div>
        <p class="text-sm text-gray-600">
          <?= htmlspecialchars($langText['progress'] ?? 'Progress', ENT_QUOTES) ?>: <?= $pct ?>%
        </p>
      </div>
    <?php endforeach; endif; ?>
  </div>

  <!-- botão criar -->
  <button id="addProjectBtn"
          class="fixed bottom-8 right-8 bg-green-500 text-white p-4 rounded-full shadow-lg hover:bg-green-600"
          aria-label="<?= htmlspecialchars($langText['add_project'] ?? 'Add Project', ENT_QUOTES) ?>">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
    </svg>
  </button>

  <!-- Modal Criar Projeto -->
  <div id="projectModal"
       class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white rounded-md p-8 w-90 max-h-[90vh] overflow-y-auto relative">
      <button id="closeModal" class="absolute top-4 right-4 text-gray-700 text-2xl"
              aria-label="<?= htmlspecialchars($langText['cancel'] ?? 'Cancel', ENT_QUOTES) ?>">
        &times;
      </button>
      <h3 class="text-xl font-bold mb-4">
        <?= htmlspecialchars($langText['add_project'] ?? 'Add Project', ENT_QUOTES) ?>
      </h3>
      <form id="projectForm" action="<?= $baseUrl ?>/projects/store" method="POST">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
          <div>
            <label class="block text-gray-700"><?= htmlspecialchars($langText['name'] ?? 'Project Name', ENT_QUOTES) ?></label>
            <input name="name" type="text" required class="w-full p-2 border rounded">
          </div>
          <div>
            <label class="block text-gray-700"><?= htmlspecialchars($langText['select_client'] ?? 'Client', ENT_QUOTES) ?></label>
            <select name="client_id" class="w-full p-2 border rounded">
              <option value=""><?= htmlspecialchars($langText['select_client'] ?? 'Select a client', ENT_QUOTES) ?></option>
              <?php foreach($clients as $c): ?>
                <option value="<?= htmlspecialchars($c['id'], ENT_QUOTES) ?>"><?= htmlspecialchars($c['name'], ENT_QUOTES) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="block text-gray-700"><?= htmlspecialchars($langText['location'] ?? 'Location', ENT_QUOTES) ?></label>
            <input name="location" type="text" class="w-full p-2 border rounded">
          </div>
          <div>
            <label class="block text-gray-700"><?= htmlspecialchars($langText['description'] ?? 'Description', ENT_QUOTES) ?></label>
            <textarea name="description" class="w-full p-2 border rounded"></textarea>
          </div>
          <div>
            <label class="block text-gray-700"><?= htmlspecialchars($langText['start_date'] ?? 'Start Date', ENT_QUOTES) ?></label>
            <input name="start_date" type="date" required class="w-full p-2 border rounded">
          </div>
          <div>
            <label class="block text-gray-700"><?= htmlspecialchars($langText['end_date'] ?? 'End Date', ENT_QUOTES) ?></label>
            <input name="end_date" type="date" required class="w-full p-2 border rounded">
          </div>
        </div>

        <!-- Tasks -->
        <div class="mb-4">
          <label class="block text-gray-700"><?= htmlspecialchars($langText['tasks'] ?? 'Tasks', ENT_QUOTES) ?></label>
          <div id="createTasksContainer"></div>
          <div class="flex mt-2">
            <input id="createNewTaskInput" type="text" class="w-full p-2 border rounded"
                   placeholder="<?= htmlspecialchars($langText['task_placeholder'] ?? 'Task description', ENT_QUOTES) ?>">
            <button id="createAddTaskBtn" type="button" class="ml-2 bg-blue-500 text-white px-3 py-2 rounded">
              <?= htmlspecialchars($langText['add'] ?? 'Add', ENT_QUOTES) ?>
            </button>
          </div>
        </div>

        <!-- Employees -->
        <div class="mb-4">
          <label class="block text-gray-700"><?= htmlspecialchars($langText['employees'] ?? 'Employees', ENT_QUOTES) ?></label>
          <div id="createEmployeesContainer"></div>
          <div class="flex mt-2">
            <select id="createEmployeeSelect" class="w-full p-2 border rounded">
              <option value=""><?= htmlspecialchars($langText['select_employee'] ?? 'Select an employee', ENT_QUOTES) ?></option>
              <?php foreach($activeEmployees as $emp): ?>
                <option value="<?= htmlspecialchars($emp['id'], ENT_QUOTES) ?>">
                  <?= htmlspecialchars($emp['name'].' '.$emp['last_name'], ENT_QUOTES) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <button id="createAddEmployeeBtn" type="button" class="ml-2 bg-blue-500 text-white px-3 py-2 rounded">
              <?= htmlspecialchars($langText['add'] ?? 'Add', ENT_QUOTES) ?>
            </button>
          </div>
        </div>

        <input type="hidden" name="tasks" id="createTasksData" value="[]">
        <input type="hidden" name="employees" id="createEmployeesData" value="[]">
        <input type="hidden" name="employee_count" id="createEmployeeCount" value="0">
        <input type="hidden" name="status" id="createProjectStatus" value="pending">

        <div class="flex justify-end">
          <button id="closeCreateModal" type="button" class="mr-2 px-4 py-2 border rounded">
            <?= htmlspecialchars($langText['cancel'] ?? 'Cancel', ENT_QUOTES) ?>
          </button>
          <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded">
            <?= htmlspecialchars($langText['submit'] ?? 'Submit', ENT_QUOTES) ?>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal Detalhes / Edição com Abas -->
  <div id="projectDetailsModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white rounded-md p-8 w-90 max-h-[90vh] overflow-y-auto relative">
      <button id="closeProjectDetailsModal" class="absolute top-2 right-2 text-gray-700 text-2xl"
              aria-label="<?= htmlspecialchars($langText['cancel'] ?? 'Cancel', ENT_QUOTES) ?>">
        &times;
      </button>
      <h3 class="text-xl font-bold mb-4"><?= htmlspecialchars($langText['project_details'] ?? 'Project Details', ENT_QUOTES) ?></h3>

      <form id="projectDetailsForm" action="<?= $baseUrl ?>/projects/update" method="POST">
        <input name="id" type="hidden" id="detailsProjectId">
        <input name="status" type="hidden" id="detailsProjectStatus" value="pending">

        <nav class="mb-6">
          <ul class="flex space-x-6 border-b">
            <li>
              <button type="button" class="tab-btn pb-3 font-medium text-blue-600 border-b-2 border-blue-600" data-tab="geral">
                <?= htmlspecialchars($langText['general'] ?? 'Geral', ENT_QUOTES) ?>
              </button>
            </li>
            <li>
              <button type="button" class="tab-btn pb-3 font-medium text-gray-600 hover:text-gray-800" data-tab="tarefas">
                <?= htmlspecialchars($langText['tasks'] ?? 'Tarefas', ENT_QUOTES) ?>
              </button>
            </li>
            <li>
              <button type="button" class="tab-btn pb-3 font-medium text-gray-600 hover:text-gray-800" data-tab="funcionarios">
                <?= htmlspecialchars($langText['employees'] ?? 'Funcionários', ENT_QUOTES) ?>
              </button>
            </li>
            <li>
              <button type="button" class="tab-btn pb-3 font-medium text-gray-600 hover:text-gray-800" data-tab="inventario">
                <?= htmlspecialchars($langText['inventory'] ?? 'Inventário', ENT_QUOTES) ?>
              </button>
            </li>
            <li>
              <button type="button" class="tab-btn pb-3 font-medium text-gray-600 hover:text-gray-800" data-tab="transacoes">
                <?= htmlspecialchars($langText['transactions'] ?? 'Transações', ENT_QUOTES) ?>
              </button>
            </li>
          </ul>
        </nav>

        <div class="tab-content space-y-6">
          <!-- Geral (sem dias restantes/atrasado) -->
          <div id="tab-geral" class="tab-panel">
            <div class="mb-4">
              <span class="block text-gray-700 font-semibold"><?= htmlspecialchars($langText['client'] ?? 'Client', ENT_QUOTES) ?>:</span>
              <p id="detailsProjectClientName" class="text-gray-800">—</p>
            </div>
            <div class="mb-4">
              <label class="block text-gray-700 font-semibold"><?= htmlspecialchars($langText['name'] ?? 'Name', ENT_QUOTES) ?></label>
              <input name="name" type="text" id="detailsProjectName" class="w-full p-2 border rounded" required>
            </div>
            <div class="mb-4">
              <label class="block text-gray-700 font-semibold"><?= htmlspecialchars($langText['description'] ?? 'Description', ENT_QUOTES) ?></label>
              <textarea name="description" id="detailsProjectDescription" class="w-full p-2 border rounded"></textarea>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
              <div>
                <label class="block text-gray-700 font-semibold"><?= htmlspecialchars($langText['start_date'] ?? 'Start Date', ENT_QUOTES) ?></label>
                <input name="start_date" type="date" id="detailsProjectStartDate" class="w-full p-2 border rounded" required>
              </div>
              <div>
                <label class="block text-gray-700 font-semibold"><?= htmlspecialchars($langText['end_date'] ?? 'End Date', ENT_QUOTES) ?></label>
                <input name="end_date" type="date" id="detailsProjectEndDate" class="w-full p-2 border rounded" required>
              </div>
            </div>
          </div>

          <!-- Tarefas -->
          <div id="tab-tarefas" class="tab-panel hidden">
            <div class="mb-4">
              <label class="block text-gray-700 font-semibold"><?= htmlspecialchars($langText['progress'] ?? 'Progress', ENT_QUOTES) ?></label>
              <div class="w-full bg-gray-200 rounded-full h-2 mb-1">
                <div id="detailsProgressBar" class="h-2 rounded-full bg-blue-500" style="width:0%"></div>
              </div>
              <span id="detailsProgressText" class="text-sm text-gray-600">0%</span>
            </div>
            <label class="block text-gray-700 font-semibold"><?= htmlspecialchars($langText['tasks'] ?? 'Tasks', ENT_QUOTES) ?></label>
            <div id="detailsTasksContainer" class="mb-2"></div>
            <div class="flex m-4">
              <input id="detailsNewTaskInput" type="text" class="w-full p-2 border rounded"
                     placeholder="<?= htmlspecialchars($langText['task_placeholder'] ?? 'Task description', ENT_QUOTES) ?>">
              <button id="detailsAddTaskBtn" type="button" class="ml-2 bg-blue-500 text-white px-3 py-2 rounded">
                <?= htmlspecialchars($langText['add'] ?? 'Add', ENT_QUOTES) ?>
              </button>
            </div>
          </div>

          <!-- Funcionários -->
          <div id="tab-funcionarios" class="tab-panel hidden">
            <label class="block text-gray-700 font-semibold"><?= htmlspecialchars($langText['employees'] ?? 'Employees', ENT_QUOTES) ?></label>
            <div id="detailsEmployeesContainer" class="mb-2"></div>
            <div class="flex m-4">
              <select id="detailsEmployeeSelect" class="w-full p-2 border rounded">
                <option value=""><?= htmlspecialchars($langText['select_employee'] ?? 'Select an employee', ENT_QUOTES) ?></option>
                <?php foreach($activeEmployees as $emp): ?>
                  <option value="<?= htmlspecialchars($emp['id'], ENT_QUOTES) ?>"><?= htmlspecialchars($emp['name'].' '.$emp['last_name'], ENT_QUOTES) ?></option>
                <?php endforeach; ?>
              </select>
              <button id="detailsAddEmployeeBtn" type="button" class="ml-2 bg-blue-500 text-white px-3 py-2 rounded">
                <?= htmlspecialchars($langText['add'] ?? 'Add', ENT_QUOTES) ?>
              </button>
            </div>
          </div>

          <!-- Inventário -->
          <div id="tab-inventario" class="tab-panel hidden mb-4">
            <label class="block text-gray-700 font-semibold"><?= htmlspecialchars($langText['inventory'] ?? 'Inventory', ENT_QUOTES) ?></label>
            <div id="detailsInventoryContainer" class="text-gray-800 text-sm">
              <?= htmlspecialchars($langText['no_inventory_allocated'] ?? '— Nenhum item alocado', ENT_QUOTES) ?>
            </div>
          </div>

          <!-- Transações -->
          <div id="tab-transacoes" class="tab-panel hidden">
            <h3 class="text-lg font-semibold mb-2"><?= htmlspecialchars($langText['project_transactions'] ?? 'Transações do Projeto', ENT_QUOTES) ?></h3>
            <div class="overflow-x-auto">
              <table class="w-full bg-white rounded shadow">
                <thead class="bg-gray-100">
                  <tr>
                    <th class="p-2 text-left"><?= htmlspecialchars($langText['date'] ?? 'Data', ENT_QUOTES) ?></th>
                    <th class="p-2 text-left"><?= htmlspecialchars($langText['type'] ?? 'Tipo', ENT_QUOTES) ?></th>
                    <th class="p-2 text-right"><?= htmlspecialchars($langText['amount'] ?? 'Valor', ENT_QUOTES) ?></th>
                  </tr>
                </thead>
                <tbody id="detailsProjTransBody">
                  <tr>
                    <td colspan="3" class="p-4 text-center text-gray-500"><?= htmlspecialchars($langText['no_transactions'] ?? 'Sem transações', ENT_QUOTES) ?></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <input name="tasks" type="hidden" id="detailsTasksData">
        <input name="employees" type="hidden" id="detailsEmployeesData">
        <input name="employee_count" type="hidden" id="detailsEmployeeCountData">

        <div class="flex justify-end space-x-2">
          <button id="cancelDetailsBtn" type="button" class="px-4 py-2 border rounded"><?= htmlspecialchars($langText['cancel'] ?? 'Cancel', ENT_QUOTES) ?></button>
          <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded"><?= htmlspecialchars($langText['save_changes'] ?? 'Save Changes', ENT_QUOTES) ?></button>
          <button id="deleteDetailsBtn" type="button" class="bg-red-500 text-white px-4 py-2 rounded"><?= htmlspecialchars($langText['delete'] ?? 'Delete', ENT_QUOTES) ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  window.langText = <?= json_encode($langText, JSON_UNESCAPED_UNICODE) ?>;
  window.baseUrl  = '<?= $baseUrl ?>';
</script>
<script defer src="<?= $baseUrl ?>/js/projects.js"></script>

