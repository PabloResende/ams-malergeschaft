<?php
require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../../../config/Database.php';

$pdo = Database::connect();

// Carrega os projetos
$filter = $_GET['filter'] ?? '';
$query = "SELECT * FROM projects";
$params = [];
if ($filter === 'active') {
    $query .= " WHERE status = 'in_progress'";
} elseif ($filter === 'pending') {
    $query .= " WHERE status = 'pending'";
} elseif ($filter === 'completed') {
    $query .= " WHERE status = 'completed'";
}
$query .= ($filter === 'active') ? " ORDER BY end_date ASC" : " ORDER BY created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Carrega funcionários ativos para o select
$activeEmployees = $pdo->query("SELECT id, name, last_name FROM employees WHERE active = 1")->fetchAll(PDO::FETCH_ASSOC);

// Carrega itens do inventário com quantidade disponível
$inventoryItems = $pdo->query("SELECT id, name, quantity FROM inventory WHERE quantity > 0")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="ml-56 pt-20 p-8 relative">
  <h1 class="text-2xl font-bold mb-4"><?= $langText['projects'] ?? 'Projects' ?></h1>

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

  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
    <?php if (empty($projects)): ?>
      <p><?= $langText['no_projects_available'] ?? 'No projects available.' ?></p>
    <?php else: ?>
      <?php foreach ($projects as $project): ?>
        <?php
        // Define tag de status
        $status = $project['status'];
        if ($status === 'in_progress') {
            $tag = '<span class="bg-blue-500 text-white px-3 py-1 rounded-full text-[12px] font-semibold">'.
                   ($langText['active'] ?? 'Active').'</span>';
        } elseif ($status === 'pending') {
            $tag = '<span class="bg-yellow-500 text-white px-3 py-1 rounded-full text-[12px] font-semibold">'.
                   ($langText['pending'] ?? 'Pending').'</span>';
        } else {
            $tag = '<span class="bg-green-500 text-white px-3 py-1 rounded-full text-[12px] font-semibold">'.
                   ($langText['completed'] ?? 'Completed').'</span>';
        }
        // Consulta as tarefas reais deste projeto
        $stmtTasks = $pdo->prepare("SELECT * FROM tasks WHERE project_id = ? ORDER BY created_at ASC");
        $stmtTasks->execute([$project['id']]);
        $tasks = $stmtTasks->fetchAll(PDO::FETCH_ASSOC);

        // Calcula o progresso com base nas tarefas
        $totalTasks = count($tasks);
        $completedTasks = 0;
        foreach ($tasks as $task) {
            if ($task['completed']) {
                $completedTasks++;
            }
        }
        $progress = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;
        ?>
        <div class="project-item bg-white p-6 rounded-xl shadow flex flex-col hover:shadow-md transition-all relative">
          <div class="flex items-center justify-between mb-2">
            <h4 class="text-xl font-bold flex-1"><?= htmlspecialchars($project['name']) ?></h4>
            <?= $tag ?>
            <!-- Botão para expandir detalhes -->
            <button class="toggleDetails ml-2 text-gray-500 hover:text-gray-800">&#x25BC;</button>
          </div>
          <span>
            <h1 class="text-[13px] text-gray-600"><?= $langText['client'] ?? 'Client' ?></h1>
            <p class="text-sm font-semibold -mt-1"><?= htmlspecialchars($project['client_name']) ?></p>
          </span>


          <!-- Painel de detalhes com as tarefas reais -->
          <div class="project-details hidden mt-4 border-t pt-4">
            <div class="tasks-list">
              <h5 class="font-semibold mb-2"><?= $langText['tasks'] ?? 'Tasks' ?></h5>
              <ul>
                <?php if (!empty($tasks)): ?>
                  <?php foreach ($tasks as $task): ?>
                    <li>
                      <label>
                        <input type="checkbox" class="task-checkbox" data-project-id="<?= $project['id'] ?>" data-task-id="<?= $task['id'] ?>" <?= $task['completed'] ? 'checked' : '' ?>>
                        <?= htmlspecialchars($task['description']) ?>
                      </label>
                    </li>
                  <?php endforeach; ?>
                <?php else: ?>
                  <li><?= $langText['no_tasks'] ?? 'No tasks available.' ?></li>
                <?php endif; ?>
              </ul>
            </div>
            <!-- A barra de progresso nos detalhes é atualizada conforme os checkboxes forem marcados -->
            <div class="w-full bg-gray-200 rounded-full h-2 mt-3">
              <div class="progress-bar bg-blue-500 h-2 rounded-full" style="width: <?= $progress ?>%;"></div>
            </div>
            <p class="mt-1 text-sm text-gray-600">
              <?= $langText['progress'] ?? 'Progress' ?>: 
              <span class="progress-value"><?= $progress ?></span>%
            </p>
          </div>

          <!-- Botões de ação -->
          <div class="mt-4 flex justify-end space-x-2">
            <button class="text-blue-500 hover:underline text-sm editProjectBtn"
              data-id="<?= $project['id'] ?>"
              data-name="<?= htmlspecialchars($project['name']) ?>"
              data-client_name="<?= htmlspecialchars($project['client_name']) ?>"
              data-description="<?= htmlspecialchars($project['description']) ?>"
              data-start_date="<?= htmlspecialchars($project['start_date']) ?>"
              data-end_date="<?= htmlspecialchars($project['end_date']) ?>"
              data-total_hours="<?= htmlspecialchars($project['total_hours']) ?>"
              data-status="<?= htmlspecialchars($project['status']) ?>"
              data-progress="<?= htmlspecialchars($progress) ?>"
            >
              <?= $langText['edit'] ?? 'Edit' ?>
            </button>
            <a href="<?= $baseUrl ?>/projects/delete?id=<?= $project['id'] ?>" class="text-red-500 hover:underline text-sm">
              <?= $langText['delete'] ?? 'Delete' ?>
            </a>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Botão Flutuante para Adicionar Projeto -->
  <button id="addProjectBtn" class="fixed bottom-8 right-8 bg-green-500 text-white rounded-full p-4 shadow-lg hover:bg-green-600">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
    </svg>
  </button>

  <!-- Modal de Criação de Projeto -->
  <div id="projectModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white rounded-md p-8 w-90 max-h-[90vh] overflow-y-auto mt-10">
      <h3 class="text-xl font-bold mb-4"><?= $langText['add_project'] ?? 'Add Project' ?></h3>
      <form id="projectForm" action="<?= $baseUrl ?>/projects/store" method="POST">
        <!-- Dados do projeto -->
        <div class="mb-4">
          <label class="block text-gray-700"><?= $langText['name'] ?? 'Name' ?></label>
          <input type="text" name="name" class="w-full p-2 border rounded" required>
        </div>
        <div class="mb-4">
          <label class="block text-gray-700"><?= $langText['client_name'] ?? 'Client Name' ?></label>
          <input type="text" name="client_name" class="w-full p-2 border rounded" required>
        </div>
        <div class="mb-4">
          <label class="block text-gray-700"><?= $langText['description'] ?? 'Description' ?></label>
          <textarea name="description" class="w-full p-2 border rounded" rows="3"></textarea>
        </div>
        <div class="mb-4">
          <label class="block text-gray-700"><?= $langText['start_date'] ?? 'Start Date' ?></label>
          <input type="date" name="start_date" class="w-full p-2 border rounded" required>
        </div>
        <div class="mb-4">
          <label class="block text-gray-700"><?= $langText['end_date'] ?? 'End Date' ?></label>
          <input type="date" name="end_date" class="w-full p-2 border rounded" required>
        </div>
        <div class="mb-4">
          <label class="block text-gray-700"><?= $langText['total_hours'] ?? 'Total Hours' ?></label>
          <input type="number" name="total_hours" class="w-full p-2 border rounded" required>
        </div>
        <div class="mb-4">
          <label class="block text-gray-700"><?= $langText['status'] ?? 'Status' ?></label>
          <select name="status" class="w-full p-2 border rounded">
            <option value="in_progress"><?= $langText['in_progress'] ?? 'In Progress' ?></option>
            <option value="pending"><?= $langText['pending'] ?? 'Pending' ?></option>
            <option value="completed"><?= $langText['completed'] ?? 'Completed' ?></option>
          </select>
        </div>
        
        <!-- Seção de Tarefas -->
        <div class="mb-4">
          <label class="block text-gray-700"><?= $langText['tasks'] ?? 'Tasks' ?></label>
          <div id="tasksContainer"></div>
          <div class="flex mt-2">
            <input type="text" id="newTaskInput" class="w-full p-2 border rounded" placeholder="<?= $langText['task_placeholder'] ?? 'Task description' ?>">
            <button type="button" id="addTaskBtn" class="ml-2 bg-blue-500 text-white px-3 py-2 rounded">
              <?= $langText['add'] ?? 'Add' ?>
            </button>
          </div>
        </div>
        
        <!-- Seção de Funcionários (select) -->
        <div class="mb-4">
          <label class="block text-gray-700">Funcionários</label>
          <div id="employeesContainer"></div>
          <div class="flex mt-2">
            <select id="employeeSelect" class="w-full p-2 border rounded">
              <option value="">Selecione um funcionário</option>
              <?php foreach ($activeEmployees as $emp): ?>
                <option value="<?= $emp['id'] ?>">
                  <?= htmlspecialchars($emp['name'] . ' ' . $emp['last_name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <button type="button" id="addEmployeeBtn" class="ml-2 bg-blue-500 text-white px-3 py-2 rounded">
              Adicionar
            </button>
          </div>
          <small class="text-gray-500">Somente funcionários ativos serão adicionados.</small>
        </div>
        
        <!-- Seção de Materiais / Inventário (select + quantidade) -->
        <div class="mb-4">
          <label class="block text-gray-700">Materiais / Inventário</label>
          <div id="inventoryContainer"></div>
          <div class="flex mt-2 space-x-2">
            <select id="inventorySelect" class="p-2 border rounded">
              <option value="">Selecione um material</option>
              <?php foreach ($inventoryItems as $item): ?>
                <option value="<?= $item['id'] ?>">
                  <?= htmlspecialchars($item['name']) ?> (Disponível: <?= $item['quantity'] ?>)
                </option>
              <?php endforeach; ?>
            </select>
            <input type="number" id="inventoryQuantity" class="p-2 border rounded" placeholder="Quantidade" min="1">
            <button type="button" id="addInventoryBtn" class="bg-blue-500 text-white px-3 py-2 rounded">
              Adicionar
            </button>
          </div>
          <small class="text-gray-500">Verifique se há quantidade suficiente no estoque.</small>
        </div>
        
        <!-- Ocultos para enviar os arrays (tarefas, funcionários, inventário) via JSON -->
        <input type="hidden" name="tasks" id="tasksData">
        <input type="hidden" name="employees" id="employeesData">
        <input type="hidden" name="inventoryResources" id="inventoryData">
        
        <div class="flex justify-end mt-4">
          <button type="button" id="closeModal" class="mr-2 px-4 py-2 border rounded"><?= $langText['cancel'] ?? 'Cancel' ?></button>
          <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded">Submit</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal de Edição de Projeto -->
  <!-- Para este exemplo, o modal de edição segue o mesmo padrão, mas você pode adaptá-lo para carregar e editar as tarefas, funcionários e materiais já associados -->
  <div id="projectEditModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white rounded-md p-8 w-90 max-h-[90vh] overflow-y-auto mt-10">
      <h3 class="text-xl font-bold mb-4"><?= $langText['edit_project'] ?? 'Edit Project' ?></h3>
      <form id="projectEditForm" action="<?= $baseUrl ?>/projects/update" method="POST">
        <input type="hidden" name="id" id="editProjectId">
        <!-- Os demais campos seguem o mesmo padrão -->
        <div class="mb-4">
          <label class="block text-gray-700"><?= $langText['name'] ?? 'Name' ?></label>
          <input type="text" name="name" id="editProjectName" class="w-full p-2 border rounded" required>
        </div>
        <div class="mb-4">
          <label class="block text-gray-700"><?= $langText['client_name'] ?? 'Client Name' ?></label>
          <input type="text" name="client_name" id="editProjectClientName" class="w-full p-2 border rounded" required>
        </div>
        <div class="mb-4">
          <label class="block text-gray-700"><?= $langText['description'] ?? 'Description' ?></label>
          <textarea name="description" id="editProjectDescription" class="w-full p-2 border rounded" rows="3"></textarea>
        </div>
        <div class="mb-4">
          <label class="block text-gray-700"><?= $langText['start_date'] ?? 'Start Date' ?></label>
          <input type="date" name="start_date" id="editProjectStartDate" class="w-full p-2 border rounded" required>
        </div>
        <div class="mb-4">
          <label class="block text-gray-700"><?= $langText['end_date'] ?? 'End Date' ?></label>
          <input type="date" name="end_date" id="editProjectEndDate" class="w-full p-2 border rounded" required>
        </div>
        <div class="mb-4">
          <label class="block text-gray-700"><?= $langText['total_hours'] ?? 'Total Hours' ?></label>
          <input type="number" name="total_hours" id="editProjectTotalHours" class="w-full p-2 border rounded" required>
        </div>
        <div class="mb-4">
          <label class="block text-gray-700"><?= $langText['status'] ?? 'Status' ?></label>
          <select name="status" id="editProjectStatus" class="w-full p-2 border rounded">
            <option value="in_progress"><?= $langText['in_progress'] ?? 'In Progress' ?></option>
            <option value="pending"><?= $langText['pending'] ?? 'Pending' ?></option>
            <option value="completed"><?= $langText['completed'] ?? 'Completed' ?></option>
          </select>
        </div>
        <!-- O progresso real será calculado a partir das tarefas já cadastradas -->
        <div class="mb-4">
          <label class="block text-gray-700"><?= $langText['progress'] ?? 'Progress' ?> (%)</label>
          <input type="number" name="progress" id="editProjectProgress" readonly class="w-full p-2 border rounded">
        </div>
        <!-- Seções para edição de tarefas, funcionários e inventário podem ser incluídas aqui -->
        <div class="flex justify-end">
          <button type="button" id="closeProjectEditModal" class="mr-2 px-4 py-2 border rounded"><?= $langText['cancel'] ?? 'Cancel' ?></button>
          <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded"><?= $langText['submit'] ?? 'Submit' ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="<?= $baseUrl ?>/js/projects.js"></script>
