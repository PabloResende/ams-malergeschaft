<?php
require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../../../config/Database.php';

$pdo = Database::connect();

// 1) Carrega projetos com filtro
$filter = $_GET['filter'] ?? '';
$query = "SELECT * FROM projects";
if ($filter === 'active') {
    $query .= " WHERE status = 'in_progress'";
} elseif ($filter === 'pending') {
    $query .= " WHERE status = 'pending'";
} elseif ($filter === 'completed') {
    $query .= " WHERE status = 'completed'";
}
$query .= $filter === 'active'
    ? " ORDER BY end_date ASC"
    : " ORDER BY created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute();
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2) Carrega funcionários ativos
$activeEmployees = $pdo
    ->query("SELECT id, name, last_name FROM employees WHERE active = 1")
    ->fetchAll(PDO::FETCH_ASSOC);

// 3) Carrega inventário disponível
$inventoryItems = $pdo
    ->query("SELECT id, name, quantity FROM inventory WHERE quantity > 0")
    ->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="ml-56 pt-20 p-8 relative">
  <h1 class="text-2xl font-bold mb-4"><?= $langText['projects'] ?? 'Projects' ?></h1>

  <!-- filtros -->
  <div class="mb-6">
    <span class="mr-4 font-semibold"><?= $langText['filter_by_status'] ?? 'Filter by status:' ?></span>
    <a href="<?= $baseUrl ?>/projects" class="mr-2 px-3 py-1 rounded-full border <?= $filter=='' ? 'bg-gray-300' : 'bg-white' ?>">
      <?= $langText['all'] ?? 'All' ?>
    </a>
    <a href="<?= $baseUrl ?>/projects?filter=active" class="mr-2 px-3 py-1 rounded-full border <?= $filter=='active' ? 'bg-blue-200 text-blue-800' : 'bg-white' ?>">
      <?= $langText['active'] ?? 'Active' ?>
    </a>
    <a href="<?= $baseUrl ?>/projects?filter=pending" class="mr-2 px-3 py-1 rounded-full border <?= $filter=='pending' ? 'bg-yellow-200 text-yellow-800' : 'bg-white' ?>">
      <?= $langText['pending'] ?? 'Pending' ?>
    </a>
    <a href="<?= $baseUrl ?>/projects?filter=completed" class="mr-2 px-3 py-1 rounded-full border <?= $filter=='completed' ? 'bg-green-200 text-green-800' : 'bg-white' ?>">
      <?= $langText['completed'] ?? 'Completed' ?>
    </a>
  </div>

  <!-- Grid de cards -->
  <div id="projectsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
    <?php if (empty($projects)): ?>
      <p><?= $langText['no_projects_available'] ?? 'No projects available.' ?></p>
    <?php else: ?>
      <?php foreach ($projects as $project): ?>
        <?php
          // Tag de status
          switch ($project['status']) {
            case 'in_progress':
              $tag = '<span class="bg-blue-500 text-white px-3 py-1 rounded-full text-[12px] font-semibold">'
                   . ($langText['active'] ?? 'Active') . '</span>';
              break;
            case 'pending':
              $tag = '<span class="bg-yellow-500 text-white px-3 py-1 rounded-full text-[12px] font-semibold">'
                   . ($langText['pending'] ?? 'Pending') . '</span>';
              break;
            default:
              $tag = '<span class="bg-green-500 text-white px-3 py-1 rounded-full text-[12px] font-semibold">'
                   . ($langText['completed'] ?? 'Completed') . '</span>';
          }
          // Progresso real
          $tStmt = $pdo->prepare("SELECT completed FROM tasks WHERE project_id = ?");
          $tStmt->execute([$project['id']]);
          $tasks = $tStmt->fetchAll(PDO::FETCH_ASSOC);
          $done = array_reduce($tasks, fn($c,$t) => $c + (int)$t['completed'], 0);
          $progress = count($tasks) ? round($done / count($tasks) * 100) : 0;
        ?>
        <div
          class="project-item cursor-pointer bg-white p-6 rounded-xl shadow hover:shadow-md transition-all"
          data-project-id="<?= $project['id'] ?>"
        >
          <div class="flex items-center justify-between mb-2">
            <h4 class="text-xl font-bold flex-1"><?= htmlspecialchars($project['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></h4>
            <?= $tag ?>
          </div>

          <p class="text-sm text-gray-600 mb-1">
            <span class="font-semibold"><?= $langText['location'] ?? 'Location' ?>:</span>
            <?= htmlspecialchars($project['location'] ?? '', ENT_QUOTES, 'UTF-8') ?>
          </p>
          <p class="text-sm text-gray-600 mb-2">
            <span class="font-semibold"><?= $langText['budget'] ?? 'Budget' ?>:</span>
            <?= number_format($project['budget'] ?? 0, 2, ',', '.') ?>
          </p>

          <div class="w-full bg-gray-200 rounded-full h-2 mb-1">
            <div class="bg-blue-500 h-2 rounded-full" style="width:<?= $progress ?>%;"></div>
          </div>

          <p class="text-sm text-gray-600">
            <?= $langText['employee_count'] ?? 'Funcionários' ?>: <?= (int)($project['employee_count'] ?? 0) ?> |
            <?= $langText['progress'] ?? 'Progress' ?>: <?= $progress ?>%
          </p>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Botão flutuante de criar -->
  <button type="button" id="addProjectBtn" class="fixed bottom-8 right-8 bg-green-500 text-white rounded-full p-4 shadow-lg hover:bg-green-600">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
    </svg>
  </button>

  <!-- Modal de Criação -->
  <div id="projectModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white rounded-md p-8 w-90 max-h-[90vh] overflow-y-auto mt-10">
      <h3 class="text-xl font-bold mb-4"><?= $langText['add_project'] ?? 'Add Project' ?></h3>
      <form id="projectForm" action="<?= $baseUrl ?>/projects/store" method="POST">

        <!-- Campos básicos -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
          <div>
            <label class="block text-gray-700"><?= $langText['name'] ?? 'Nome do Projeto' ?></label>
            <input type="text" name="name" class="w-full p-2 border rounded" required>
          </div>
          <div>
            <label class="block text-gray-700"><?= $langText['location'] ?? 'Localização / Endereço' ?></label>
            <input type="text" name="location" class="w-full p-2 border rounded">
          </div>
          <div>
            <label class="block text-gray-700"><?= $langText['total_hours'] ?? 'Quantidade de Horas' ?></label>
            <input type="number" name="total_hours" class="w-full p-2 border rounded" required>
          </div>
          <div>
            <label class="block text-gray-700"><?= $langText['budget'] ?? 'Budget' ?></label>
            <input type="number" name="budget" step="0.01" class="w-full p-2 border rounded">
          </div>
          <div>
            <label class="block text-gray-700"><?= $langText['start_date'] ?? 'Data de Início' ?></label>
            <input type="date" name="start_date" class="w-full p-2 border rounded" required>
          </div>
          <div>
            <label class="block text-gray-700"><?= $langText['end_date'] ?? 'Data de Término' ?></label>
            <input type="date" name="end_date" class="w-full p-2 border rounded" required>
          </div>
          <div>
            <label class="block text-gray-700"><?= $langText['status'] ?? 'Status' ?></label>
            <select name="status" class="w-full p-2 border rounded">
              <option value="in_progress"><?= $langText['in_progress'] ?? 'In Progress' ?></option>
              <option value="pending"><?= $langText['pending'] ?? 'Pending' ?></option>
              <option value="completed"><?= $langText['completed'] ?? 'Completed' ?></option>
            </select>
          </div>
        </div>

        <!-- TAREFAS -->
        <div class="mb-4">
          <label class="block text-gray-700"><?= $langText['tasks'] ?? 'Tasks' ?></label>
          <div id="tasksContainer"></div>
          <div class="flex mt-2">
            <input type="text" id="newTaskInput" class="w-full p-2 border rounded" placeholder="<?= $langText['task_placeholder'] ?? 'Task description' ?>">
            <button type="button" id="addTaskBtn" class="ml-2 bg-blue-500 text-white px-3 py-2 rounded"><?= $langText['add'] ?? 'Add' ?></button>
          </div>
        </div>

        <!-- FUNCIONÁRIOS -->
        <div class="mb-4">
          <label class="block text-gray-700"><?= $langText['employees'] ?? 'Employees' ?></label>
          <div id="employeesContainer"></div>
          <div class="flex mt-2">
            <select id="employeeSelect" class="w-full p-2 border rounded">
              <option value=""><?= $langText['select_employee'] ?? 'Select an employee' ?></option>
              <?php foreach ($activeEmployees as $emp): ?>
                <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['name'].' '.$emp['last_name'], ENT_QUOTES, 'UTF-8') ?></option>
              <?php endforeach; ?>
            </select>
            <button type="button" id="addEmployeeBtn" class="ml-2 bg-blue-500 text-white px-3 py-2 rounded"><?= $langText['add'] ?? 'Add' ?></button>
          </div>
        </div>

        <!-- INVENTÁRIO -->
        <div class="mb-4">
          <label class="block text-gray-700"><?= $langText['inventory'] ?? 'Inventory' ?></label>
          <div id="inventoryContainer"></div>
          <div class="flex mt-2 space-x-2">
            <select id="inventorySelect" class="flex-1 p-2 border rounded">
              <option value=""><?= $langText['select_material'] ?? 'Select material' ?></option>
              <?php foreach ($inventoryItems as $item): ?>
                <option value="<?= $item['id'] ?>" data-stock="<?= $item['quantity'] ?>">
                  <?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?> (<?= $langText['available'] ?? 'Available' ?>: <?= $item['quantity'] ?>)
                </option>
              <?php endforeach; ?>
            </select>
            <input type="number" id="inventoryQuantity" class="p-2 border rounded" placeholder="<?= $langText['quantity'] ?? 'Quantity' ?>" min="1">
            <button type="button" id="addInventoryBtn" class="bg-blue-500 text-white px-3 py-2 rounded"><?= $langText['add'] ?? 'Add' ?></button>
          </div>
        </div>

        <!-- HIDDENS PARA JSON & COUNTS -->
        <input type="hidden" name="tasks" id="tasksData">
        <input type="hidden" name="employees" id="employeesData">
        <input type="hidden" name="inventoryResources" id="inventoryData">
        <input type="hidden" name="employee_count" id="employeeCountDataCreate">

        <div class="flex justify-end mt-4 space-x-2">
          <button type="button" id="closeModal" class="px-4 py-2 border rounded"><?= $langText['cancel'] ?? 'Cancel' ?></button>
          <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded"><?= $langText['submit'] ?? 'Submit' ?></button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal de Detalhes / Edição -->
  <div id="projectDetailsModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white rounded-md p-8 w-90 max-h-[90vh] overflow-y-auto mt-10 relative">
      <button type="button" id="closeProjectDetailsModal" class="absolute top-2 right-2 text-gray-700 text-2xl">&times;</button>
      <h3 class="text-xl font-bold mb-4"><?= $langText['project_details'] ?? 'Project Details' ?></h3>
      <form id="projectDetailsForm" action="<?= $baseUrl ?>/projects/update" method="POST">
        <input type="hidden" name="id" id="detailsProjectId">

        <!-- PROGRESSO DINÂMICO -->
        <div class="mb-4">
          <label class="block text-gray-700"><?= $langText['progress'] ?? 'Progress' ?></label>
          <div class="w-full bg-gray-200 rounded-full h-2 mb-1">
            <div id="detailsProgressBar" class="bg-blue-500 h-2 rounded-full" style="width:0%;"></div>
          </div>
          <span id="detailsProgressText" class="text-sm text-gray-600">0%</span>
        </div>

        <!-- Campos editáveis -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
          <div>
            <label class="block text-gray-700"><?= $langText['name'] ?? 'Nome do Projeto' ?></label>
            <input type="text" name="name" id="detailsProjectName" class="w-full p-2 border rounded">
          </div>
          <div>
            <label class="block text-gray-700"><?= $langText['location'] ?? 'Localização / Endereço' ?></label>
            <input type="text" name="location" id="detailsProjectLocation" class="w-full p-2 border rounded">
          </div>
          <div>
            <label class="block text-gray-700"><?= $langText['total_hours'] ?? 'Quantidade de Horas' ?></label>
            <input type="number" name="total_hours" id="detailsProjectTotalHours" class="w-full p-2 border rounded">
          </div>
          <div>
            <label class="block text-gray-700"><?= $langText['budget'] ?? 'Budget' ?></label>
            <input type="number" name="budget" step="0.01" id="detailsProjectBudget" class="w-full p-2 border rounded">
          </div>
          <div>
            <label class="block text-gray-700"><?= $langText['start_date'] ?? 'Data de Início' ?></label>
            <input type="date" name="start_date" id="detailsProjectStartDate" class="w-full p-2 border rounded">
          </div>
          <div>
            <label class="block text-gray-700"><?= $langText['end_date'] ?? 'Data de Término' ?></label>
            <input type="date" name="end_date" id="detailsProjectEndDate" class="w-full p-2 border rounded">
          </div>
          <div>
            <label class="block text-gray-700"><?= $langText['status'] ?? 'Status' ?></label>
            <select name="status" id="detailsProjectStatus" class="w-full p-2 border rounded">
              <option value="in_progress"><?= $langText['in_progress'] ?? 'In Progress' ?></option>
              <option value="pending"><?= $langText['pending'] ?? 'Pending' ?></option>
              <option value="completed"><?= $langText['completed'] ?? 'Completed' ?></option>
            </select>
          </div>
        </div>

        <!-- TAREFAS -->
        <div class="mb-4">
          <label class="block text-gray-700"><?= $langText['tasks'] ?? 'Tasks' ?></label>
          <div id="detailsTasksContainer"></div>
          <div class="flex mt-2">
            <input type="text" id="detailsNewTaskInput" class="w-full p-2 border rounded" placeholder="<?= $langText['task_placeholder'] ?? 'Task description' ?>">
            <button type="button" id="detailsAddTaskBtn" class="ml-2 bg-blue-500 text-white px-3 py-2 rounded"><?= $langText['add'] ?? 'Add' ?></button>
          </div>
        </div>

        <!-- FUNCIONÁRIOS -->
        <div class="mb-4">
          <label class="block text-gray-700"><?= $langText['employees'] ?? 'Employees' ?></label>
          <div id="detailsEmployeesContainer"></div>
          <div class="flex mt-2">
            <select id="detailsEmployeeSelect" class="w-full p-2 border rounded">
              <option value=""><?= $langText['select_employee'] ?? 'Select an employee' ?></option>
              <?php foreach ($activeEmployees as $emp): ?>
                <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['name'].' '.$emp['last_name'], ENT_QUOTES, 'UTF-8') ?></option>
              <?php endforeach; ?>
            </select>
            <button type="button" id="detailsAddEmployeeBtn" class="ml-2 bg-blue-500 text-white px-3 py-2 rounded"><?= $langText['add'] ?? 'Add' ?></button>
          </div>
        </div>

        <!-- INVENTÁRIO -->
        <div class="mb-4">
          <label class="block text-gray-700"><?= $langText['inventory'] ?? 'Inventory' ?></label>
          <div id="detailsInventoryContainer"></div>
          <div class="flex mt-2 space-x-2">
            <select id="detailsInventorySelect" class="flex-1 p-2 border rounded">
              <option value=""><?= $langText['select_material'] ?? 'Select material' ?></option>
              <?php foreach ($inventoryItems as $item): ?>
                <option value="<?= $item['id'] ?>" data-stock="<?= $item['quantity'] ?>">
                  <?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?> (<?= $langText['available'] ?? 'Available' ?>: <?= $item['quantity'] ?>)
                </option>
              <?php endforeach; ?>
            </select>
            <input type="number" id="detailsInventoryQuantity" class="p-2 border rounded" placeholder="<?= $langText['quantity'] ?? 'Quantity' ?>" min="1">
            <button type="button" id="detailsAddInventoryBtn" class="bg-blue-500 text-white px-3 py-2 rounded"><?= $langText['add'] ?? 'Add' ?></button>
          </div>
        </div>

        <!-- HIDDENS PARA JSON & COUNTS -->
        <input type="hidden" name="tasks" id="detailsTasksData">
        <input type="hidden" name="employees" id="detailsEmployeesData">
        <input type="hidden" name="inventoryResources" id="detailsInventoryData">
        <input type="hidden" name="employee_count" id="detailsEmployeeCountData">

        <div class="flex justify-end mt-4 space-x-2">
          <button type="button" id="cancelDetailsBtn" class="px-4 py-2 border rounded"><?= $langText['cancel'] ?? 'Cancel' ?></button>
          <button type="button" id="deleteProjectBtn" class="px-4 py-2 border rounded text-red-600"><?= $langText['delete'] ?? 'Delete' ?></button>
          <button type="submit" id="saveDetailsBtn" class="hidden bg-green-500 text-white px-4 py-2 rounded"><?= $langText['save_changes'] ?? 'Save Changes' ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<script defer src="<?= $baseUrl ?>/js/projects.js"></script>
