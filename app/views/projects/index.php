<?php
// app/views/projects/index.php

require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../../../config/Database.php';

$pdo = Database::connect();

// 1) Carrega projetos com filtro (JOIN em client)
$filter = $_GET['filter'] ?? '';
$query = "
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

// 2) Carrega funcionários ativos
$activeEmployees = $pdo
    ->query("SELECT id, name, last_name FROM employees WHERE active = 1")
    ->fetchAll(PDO::FETCH_ASSOC);

// 3) Carrega clients (só para criação)
$clients = $pdo
    ->query("SELECT id, name FROM client ORDER BY name ASC")
    ->fetchAll(PDO::FETCH_ASSOC);

$baseUrl = '/ams-malergeschaft/public';
?>
<div class="ml-56 pt-20 p-8 relative">
  <h1 class="text-2xl font-bold mb-4"><?= htmlspecialchars($langText['projects'] ?? 'Projects', ENT_QUOTES, 'UTF-8') ?></h1>

  <!-- filtros -->
  <div class="mb-6">
    <span class="mr-4 font-semibold"><?= htmlspecialchars($langText['filter_by_status'] ?? 'Filter by status:', ENT_QUOTES, 'UTF-8') ?></span>
    <a href="<?= $baseUrl ?>/projects" class="mr-2 px-3 py-1 rounded-full border <?= $filter=='' ? 'bg-gray-300' : 'bg-white' ?>">
      <?= htmlspecialchars($langText['all'] ?? 'All', ENT_QUOTES, 'UTF-8') ?>
    </a>
    <a href="<?= $baseUrl ?>/projects?filter=active" class="mr-2 px-3 py-1 rounded-full border <?= $filter=='active' ? 'bg-blue-200 text-blue-800' : 'bg-white' ?>">
      <?= htmlspecialchars($langText['active'] ?? 'Active', ENT_QUOTES, 'UTF-8') ?>
    </a>
    <a href="<?= $baseUrl ?>/projects?filter=pending" class="mr-2 px-3 py-1 rounded-full border <?= $filter=='pending' ? 'bg-yellow-200 text-yellow-800' : 'bg-white' ?>">
      <?= htmlspecialchars($langText['pending'] ?? 'Pending', ENT_QUOTES, 'UTF-8') ?>
    </a>
    <a href="<?= $baseUrl ?>/projects?filter=completed" class="mr-2 px-3 py-1 rounded-full border <?= $filter=='completed' ? 'bg-green-200 text-green-800' : 'bg-white' ?>">
      <?= htmlspecialchars($langText['completed'] ?? 'Completed', ENT_QUOTES, 'UTF-8') ?>
    </a>
  </div>

  <!-- Grid de cards -->
  <div id="projectsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
    <?php if (empty($projects)): ?>
      <p><?= htmlspecialchars($langText['no_projects_available'] ?? 'No projects available.', ENT_QUOTES, 'UTF-8') ?></p>
    <?php else: ?>
      <?php foreach ($projects as $project): ?>
        <?php
          $status     = $project['status']      ?? 'pending';
          $name       = $project['name']        ?? '';
          $clientName = $project['client_name'] ?? '—';
          $location   = $project['location']    ?? '';
          $budget     = number_format((float)($project['budget'] ?? 0), 2, ',', '.');
          $empCount   = (int)($project['employee_count'] ?? 0);

          switch ($status) {
            case 'in_progress': $tagClass='bg-blue-500';   $tagText=$langText['active']    ?? 'Active';    break;
            case 'pending':     $tagClass='bg-yellow-500'; $tagText=$langText['pending']   ?? 'Pending';   break;
            default:            $tagClass='bg-green-500';  $tagText=$langText['completed'] ?? 'Completed'; break;
          }

          $tStmt = $pdo->prepare("SELECT completed FROM tasks WHERE project_id = ?");
          $tStmt->execute([(int)$project['id']]);
          $tasksData = $tStmt->fetchAll(PDO::FETCH_ASSOC);
          $done      = array_reduce($tasksData, fn($c,$t)=>$c+(int)$t['completed'],0);
          $progress  = count($tasksData) ? round($done/count($tasksData)*100) : 0;
        ?>
        <div class="project-item cursor-pointer bg-white p-6 rounded-xl shadow hover:shadow-md transition-all"
             data-project-id="<?= htmlspecialchars((string)$project['id'], ENT_QUOTES, 'UTF-8') ?>">
          <div class="flex items-center justify-between mb-2">
            <h4 class="text-xl font-bold flex-1"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></h4>
            <span class="<?= $tagClass ?> text-white px-3 py-1 rounded-full text-[12px] font-semibold">
              <?= htmlspecialchars($tagText, ENT_QUOTES, 'UTF-8') ?>
            </span>
          </div>
          <p class="text-sm text-gray-600 mb-1">
            <span class="font-semibold"><?= htmlspecialchars($langText['client'] ?? 'Client', ENT_QUOTES, 'UTF-8') ?>:</span>
            <?= htmlspecialchars($clientName, ENT_QUOTES, 'UTF-8') ?>
          </p>
          <p class="text-sm text-gray-600 mb-1">
            <span class="font-semibold"><?= htmlspecialchars($langText['location'] ?? 'Location', ENT_QUOTES, 'UTF-8') ?>:</span>
            <?= htmlspecialchars($location, ENT_QUOTES, 'UTF-8') ?>
          </p>
          <p class="text-sm text-gray-600 mb-2">
            <span class="font-semibold"><?= htmlspecialchars($langText['budget'] ?? 'Budget', ENT_QUOTES, 'UTF-8') ?>:</span>
            <?= $budget ?>
          </p>
          <div class="w-full bg-gray-200 rounded-full h-2 mb-1">
            <div class="bg-blue-500 h-2 rounded-full" style="width:<?= $progress ?>%;"></div>
          </div>
          <p class="text-sm text-gray-600">
            <?= htmlspecialchars($langText['employee_count'] ?? 'Employees', ENT_QUOTES, 'UTF-8') ?>: <?= $empCount ?> |
            <?= htmlspecialchars($langText['progress'] ?? 'Progress', ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars($progress.'%', ENT_QUOTES, 'UTF-8') ?>
          </p>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Botão Criar -->
  <button id="addProjectBtn" class="fixed bottom-8 right-8 bg-green-500 text-white rounded-full p-4 shadow-lg hover:bg-green-600">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
    </svg>
  </button>

  <!-- Modal de Criação -->
  <div id="projectModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white rounded-md p-8 w-90 max-h-[90vh] overflow-y-auto mt-10 relative">
      <button id="closeModal" class="absolute top-4 right-4 text-gray-700 text-2xl">&times;</button>
      <h3 class="text-xl font-bold mb-4"><?= htmlspecialchars($langText['add_project'] ?? 'Add Project', ENT_QUOTES, 'UTF-8') ?></h3>
      <form id="projectForm" action="<?= $baseUrl ?>/projects/store" method="POST">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
          <!-- nome -->
          <div>
            <label class="block text-gray-700"><?= htmlspecialchars($langText['name'] ?? 'Project Name', ENT_QUOTES, 'UTF-8') ?></label>
            <input name="name" type="text" required class="w-full p-2 border rounded">
          </div>
          <!-- cliente -->
          <div>
            <label class="block text-gray-700"><?= htmlspecialchars($langText['select_client'] ?? 'Select Client', ENT_QUOTES, 'UTF-8') ?></label>
            <select name="client_id" class="w-full p-2 border rounded">
              <option value=""><?= htmlspecialchars($langText['select_client'] ?? 'Select a client', ENT_QUOTES, 'UTF-8') ?></option>
              <?php foreach($clients as $c): ?>
                <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <!-- location -->
          <div>
            <label class="block text-gray-700"><?= htmlspecialchars($langText['location'] ?? 'Location', ENT_QUOTES, 'UTF-8') ?></label>
            <input name="location" type="text" class="w-full p-2 border rounded">
          </div>
          <!-- description -->
          <div>
            <label class="block text-gray-700"><?= htmlspecialchars($langText['description'] ?? 'Description', ENT_QUOTES, 'UTF-8') ?></label>
            <textarea name="description" class="w-full p-2 border rounded"></textarea>
          </div>
          <!-- start_date -->
          <div>
            <label class="block text-gray-700"><?= htmlspecialchars($langText['start_date'] ?? 'Start Date', ENT_QUOTES, 'UTF-8') ?></label>
            <input name="start_date" type="date" required class="w-full p-2 border rounded">
          </div>
          <!-- end_date -->
          <div>
            <label class="block text-gray-700"><?= htmlspecialchars($langText['end_date'] ?? 'End Date', ENT_QUOTES, 'UTF-8') ?></label>
            <input name="end_date" type="date" required class="w-full p-2 border rounded">
          </div>
          <!-- status -->
          <div>
            <label class="block text-gray-700"><?= htmlspecialchars($langText['status'] ?? 'Status', ENT_QUOTES, 'UTF-8') ?></label>
            <select name="status" class="w-full p-2 border rounded">
              <option value="in_progress"><?= htmlspecialchars($langText['in_progress'] ?? 'In Progress', ENT_QUOTES, 'UTF-8') ?></option>
              <option value="pending"><?= htmlspecialchars($langText['pending'] ?? 'Pending', ENT_QUOTES, 'UTF-8') ?></option>
              <option value="completed"><?= htmlspecialchars($langText['completed'] ?? 'Completed', ENT_QUOTES, 'UTF-8') ?></option>
            </select>
          </div>
        </div>

        <!-- Tasks -->
        <div class="mb-4">
          <label class="block text-gray-700"><?= htmlspecialchars($langText['tasks'] ?? 'Tasks', ENT_QUOTES, 'UTF-8') ?></label>
          <div id="createTasksContainer"></div>
          <div class="flex mt-2">
            <input id="createNewTaskInput" type="text" class="w-full p-2 border rounded" placeholder="<?= htmlspecialchars($langText['task_placeholder'] ?? 'Task description', ENT_QUOTES, 'UTF-8') ?>">
            <button id="createAddTaskBtn" type="button" class="ml-2 bg-blue-500 text-white px-3 py-2 rounded"><?= htmlspecialchars($langText['add'] ?? 'Add', ENT_QUOTES, 'UTF-8') ?></button>
          </div>
        </div>

        <!-- Employees -->
        <div class="mb-4">
          <label class="block text-gray-700"><?= htmlspecialchars($langText['employees'] ?? 'Employees', ENT_QUOTES, 'UTF-8') ?></label>
          <div id="createEmployeesContainer"></div>
          <div class="flex mt-2">
            <select id="createEmployeeSelect" class="w-full p-2 border rounded">
              <option value=""><?= htmlspecialchars($langText['select_employee'] ?? 'Select an employee', ENT_QUOTES, 'UTF-8') ?></option>
              <?php foreach($activeEmployees as $emp): ?>
                <option value="<?= (int)$emp['id'] ?>"><?= htmlspecialchars($emp['name'].' '.$emp['last_name'], ENT_QUOTES, 'UTF-8') ?></option>
              <?php endforeach; ?>
            </select>
            <button id="createAddEmployeeBtn" type="button" class="ml-2 bg-blue-500 text-white px-3 py-2 rounded"><?= htmlspecialchars($langText['add'] ?? 'Add', ENT_QUOTES, 'UTF-8') ?></button>
          </div>
        </div>

        <input type="hidden" name="tasks" id="createTasksData" value="[]">
        <input type="hidden" name="employees" id="createEmployeesData" value="[]">
        <input type="hidden" name="employee_count" id="createEmployeeCount" value="0">

        <div class="flex justify-end">
          <button id="closeCreateModal" type="button" class="mr-2 px-4 py-2 border rounded"><?= htmlspecialchars($langText['cancel'] ?? 'Cancel', ENT_QUOTES, 'UTF-8') ?></button>
          <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded"><?= htmlspecialchars($langText['submit'] ?? 'Submit', ENT_QUOTES, 'UTF-8') ?></button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal de Detalhes / Edição -->
  <div id="projectDetailsModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white rounded-md p-8 w-90 max-h-[90vh] overflow-y-auto mt-10 relative">
      <button id="closeProjectDetailsModal" class="absolute top-2 right-2 text-gray-700 text-2xl">&times;</button>
      <h3 class="text-xl font-bold mb-4"><?= htmlspecialchars($langText['project_details'] ?? 'Project Details', ENT_QUOTES, 'UTF-8') ?></h3>

      <form id="projectDetailsForm" action="<?= $baseUrl ?>/projects/update" method="POST">
        <input name="id" type="hidden" id="detailsProjectId">

        <!-- Client (readonly) -->
        <div class="mb-4">
          <span class="block text-gray-700 font-semibold"><?= htmlspecialchars($langText['client'] ?? 'Client', ENT_QUOTES, 'UTF-8') ?>:</span>
          <p id="detailsProjectClientName" class="mb-2 text-gray-800">—</p>
        </div>

        <!-- Status (editable) -->
        <div class="mb-4">
          <label class="block text-gray-700"><?= htmlspecialchars($langText['status'] ?? 'Status', ENT_QUOTES, 'UTF-8') ?></label>
          <select name="status" id="detailsProjectStatusSelect" class="w-full p-2 border rounded">
            <option value="in_progress"><?= htmlspecialchars($langText['in_progress'] ?? 'In Progress', ENT_QUOTES, 'UTF-8') ?></option>
            <option value="pending"><?= htmlspecialchars($langText['pending'] ?? 'Pending', ENT_QUOTES, 'UTF-8') ?></option>
            <option value="completed"><?= htmlspecialchars($langText['completed'] ?? 'Completed', ENT_QUOTES, 'UTF-8') ?></option>
          </select>
        </div>

        <!-- Datas -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
          <div>
            <label class="block text-gray-700"><?= htmlspecialchars($langText['start_date'] ?? 'Start Date', ENT_QUOTES, 'UTF-8') ?></label>
            <input name="start_date" type="date" id="detailsProjectStartDate" required class="w-full p-2 border rounded">
          </div>
          <div>
            <label class="block text-gray-700"><?= htmlspecialchars($langText['end_date'] ?? 'End Date', ENT_QUOTES, 'UTF-8') ?></label>
            <input name="end_date" type="date" id="detailsProjectEndDate" required class="w-full p-2 border rounded">
          </div>
        </div>

        <!-- Progress -->
        <div class="mb-4">
          <label class="block text-gray-700"><?= htmlspecialchars($langText['progress'] ?? 'Progress', ENT_QUOTES, 'UTF-8') ?></label>
          <div class="w-full bg-gray-200 rounded-full h-2 mb-1">
            <div id="detailsProgressBar" class="bg-blue-500 h-2 rounded-full" style="width:0%;"></div>
          </div>
          <span id="detailsProgressText" class="text-sm text-gray-600">0%</span>
        </div>

        <!-- Name -->
        <div class="mb-4">
          <label class="block text-gray-700"><?= htmlspecialchars($langText['name'] ?? 'Name', ENT_QUOTES, 'UTF-8') ?></label>
          <input name="name" type="text" id="detailsProjectName" class="w-full p-2 border rounded" required>
        </div>

        <!-- Location (readonly) -->
        <div class="mb-4">
          <span class="block text-gray-700 font-semibold"><?= htmlspecialchars($langText['location'] ?? 'Location', ENT_QUOTES, 'UTF-8') ?>:</span>
          <p id="detailsProjectLocation" class="text-gray-800 mb-2">—</p>
        </div>

        <!-- Description -->
        <div class="mb-4">
          <label class="block text-gray-700"><?= htmlspecialchars($langText['description'] ?? 'Description', ENT_QUOTES, 'UTF-8') ?></label>
          <textarea name="description" id="detailsProjectDescription" class="w-full p-2 border rounded"></textarea>
        </div>

        <!-- Tasks -->
        <div class="mb-4">
          <label class="block text-gray-700"><?= htmlspecialchars($langText['tasks'] ?? 'Tasks', ENT_QUOTES, 'UTF-8') ?></label>
          <div id="detailsTasksContainer"></div>
          <div class="flex mt-2">
            <input id="detailsNewTaskInput" type="text" class="w-full p-2 border rounded" placeholder="<?= htmlspecialchars($langText['task_placeholder'] ?? 'Task description', ENT_QUOTES, 'UTF-8') ?>">
            <button id="detailsAddTaskBtn" type="button" class="ml-2 bg-blue-500 text-white px-3 py-2 rounded"><?= htmlspecialchars($langText['add'] ?? 'Add', ENT_QUOTES, 'UTF-8') ?></button>
          </div>
        </div>

        <!-- Employees -->
        <div class="mb-4">
          <label class="block text-gray-700"><?= htmlspecialchars($langText['employees'] ?? 'Employees', ENT_QUOTES, 'UTF-8') ?></label>
          <div id="detailsEmployeesContainer"></div>
          <div class="flex mt-2">
            <select id="detailsEmployeeSelect" class="w-full p-2 border rounded">
              <option value=""><?= htmlspecialchars($langText['select_employee'] ?? 'Select an employee', ENT_QUOTES, 'UTF-8') ?></option>
              <?php foreach($activeEmployees as $emp): ?>
                <option value="<?= (int)$emp['id'] ?>"><?= htmlspecialchars($emp['name'].' '.$emp['last_name'], ENT_QUOTES, 'UTF-8') ?></option>
              <?php endforeach; ?>
            </select>
            <button id="detailsAddEmployeeBtn" type="button" class="ml-2 bg-blue-500 text-white px-3 py-2 rounded"><?= htmlspecialchars($langText['add'] ?? 'Add', ENT_QUOTES, 'UTF-8') ?></button>
          </div>
        </div>

        <!-- Inventory -->
        <div class="mb-4">
          <label class="block text-gray-700"><?= htmlspecialchars($langText['inventory'] ?? 'Inventory', ENT_QUOTES, 'UTF-8') ?></label>
          <div id="detailsInventoryContainer" class="text-gray-800 text-sm"></div>
        </div>

        <input name="tasks" type="hidden" id="detailsTasksData">
        <input name="employees" type="hidden" id="detailsEmployeesData">
        <input name="employee_count" type="hidden" id="detailsEmployeeCountData">

        <div class="flex justify-end space-x-2">
          <button id="cancelDetailsBtn" type="button" class="px-4 py-2 border rounded"><?= htmlspecialchars($langText['cancel'] ?? 'Cancel', ENT_QUOTES, 'UTF-8') ?></button>
          <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded"><?= htmlspecialchars($langText['save_changes'] ?? 'Save Changes', ENT_QUOTES, 'UTF-8') ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<script defer src="<?= $baseUrl ?>/js/projects.js"></script>
