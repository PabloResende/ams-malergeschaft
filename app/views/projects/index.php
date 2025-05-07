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
    <div id="detailsEmployeesContainer" class="space-y-1 text-sm text-gray-800">
      <!-- Funcionários alocados serão renderizados aqui -->
    </div>
    <div class="flex mt-2">
      <select id="detailsEmployeeSelect" name="employees[]" class="w-full p-2 border rounded">
        <option value=""><?= $langText['select_employee'] ?? 'Select an employee' ?></option>
        <?php foreach ($activeEmployees as $emp): ?>
          <option value="<?= htmlspecialchars($emp['id']) ?>">
            <?= htmlspecialchars($emp['name'] . ' ' . $emp['last_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <button type="button" id="detailsAddEmployeeBtn" class="ml-2 bg-blue-500 text-white px-3 py-2 rounded"><?= $langText['add'] ?? 'Add' ?></button>
    </div>
  </div>

  <!-- INVENTÁRIO ALOCADO -->
  <div class="mb-4">
    <label class="block text-gray-700"><?= $langText['inventory'] ?? 'Inventory' ?></label>
    <div id="detailsInventoryContainer" class="space-y-1 text-sm text-gray-800">
      <!-- Inventário alocado renderizado via JS -->
    </div>
  </div>

  <!-- HIDDENS PARA JSON & CONTAGEM -->
  <input type="hidden" name="tasks" id="detailsTasksData">
  <input type="hidden" name="employees" id="detailsEmployeesData">
  <input type="hidden" name="employee_count" id="detailsEmployeeCountData">

  <div class="flex justify-end mt-4 space-x-2">
    <button type="button" id="cancelDetailsBtn" class="px-4 py-2 border rounded">
      <?= $langText['cancel'] ?? 'Cancel' ?>
    </button>
    <button type="button" id="deleteProjectBtn" class="px-4 py-2 border rounded text-red-600">
      <?= $langText['delete'] ?? 'Delete' ?>
    </button>
    <button type="submit" id="saveDetailsBtn" class="hidden bg-green-500 text-white px-4 py-2 rounded">
      <?= $langText['save_changes'] ?? 'Save Changes' ?>
    </button>
  </div>
</form>
