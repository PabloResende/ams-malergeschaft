<?php
require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../../../config/Database.php';

$pdo = Database::connect();

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

if ($filter === 'active') {
    $query .= " ORDER BY end_date ASC";
} else {
    $query .= " ORDER BY created_at DESC";
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="ml-56 pt-20 p-8 relative">
    <h1 class="text-2xl font-bold mb-4"><?= $langText['projects'] ?? 'Projects' ?></h1>

    <div class="mb-6">
        <span class="mr-4 font-semibold"><?= $langText['filter_by_status'] ?? 'Filter by status:' ?></span>
        <a href="<?= $baseUrl ?>/projects" class="mr-2 px-3 py-1 rounded-full border <?= $filter=='' ? 'bg-gray-300' : 'bg-white' ?>"><?= $langText['all'] ?? 'All' ?></a>
        <a href="<?= $baseUrl ?>/projects?filter=active" class="mr-2 px-3 py-1 rounded-full border <?= $filter=='active' ? 'bg-blue-200 text-blue-800' : 'bg-white' ?>"><?= $langText['active'] ?? 'Active' ?></a>
        <a href="<?= $baseUrl ?>/projects?filter=pending" class="mr-2 px-3 py-1 rounded-full border <?= $filter=='pending' ? 'bg-yellow-200 text-yellow-800' : 'bg-white' ?>"><?= $langText['pending'] ?? 'Pending' ?></a>
        <a href="<?= $baseUrl ?>/projects?filter=completed" class="mr-2 px-3 py-1 rounded-full border <?= $filter=='completed' ? 'bg-green-200 text-green-800' : 'bg-white' ?>"><?= $langText['completed'] ?? 'Completed' ?></a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php if (empty($projects)): ?>
            <p><?= $langText['no_projects_available'] ?? 'No projects available.' ?></p>
        <?php else: ?>
            <?php foreach ($projects as $project): ?>
                <?php
                $status = $project['status'];
                if ($status === 'in_progress') {
                    $tag = '<span class="bg-blue-500 text-white px-3 py-1 rounded-full text-[12px] font-semibold">'.($langText['active'] ?? 'Active').'</span>';
                } elseif ($status === 'pending') {
                    $tag = '<span class="bg-yellow-500 text-white px-3 py-1 rounded-full text-[12px] font-semibold">'.($langText['pending'] ?? 'Pending').'</span>';
                } else {
                    $tag = '<span class="bg-green-500 text-white px-3 py-1 rounded-full text-[12px] font-semibold">'.($langText['completed'] ?? 'Completed').'</span>';
                }
                $progress = $project['progress'] ?? 0;
                ?>
                <div class="bg-white p-6 rounded-xl shadow flex flex-col hover:shadow-md transition-all">
                    <!-- Cabeçalho -->
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="text-xl font-bold flex-1"><?= htmlspecialchars($project['name']) ?></h4>
                        <?= $tag ?>
                    </div>

                    <!-- Cliente -->
                    <span>
                        <h1 class="text-[13px] text-gray-600"><?= $langText['client'] ?? 'Client' ?></h1>
                        <p class="text-sm font-semibold -mt-1"><?= htmlspecialchars($project['client_name']) ?></p>
                    </span>

                    <!-- Barra de progresso -->
                    <div class="w-full bg-gray-200 rounded-full h-2 mt-3">
                        <div class="bg-blue-500 h-2 rounded-full" style="width: <?= $progress ?>%;"></div>
                    </div>
                    <p class="mt-1 text-sm text-gray-600"><?= $langText['progress'] ?? 'Progress' ?>: <?= $progress ?>%</p>

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
                            data-progress="<?= htmlspecialchars($project['progress']) ?>"
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

    <!-- Modal de Adição de Projeto -->
    <div id="projectModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
        <div class="bg-white rounded-lg p-6 w-96 max-h-[90vh] overflow-y-auto">
            <h3 class="text-xl font-bold mb-4"><?= $langText['add_project'] ?? 'Add Project' ?></h3>
            <form id="projectForm" action="<?= $baseUrl ?>/projects/store" method="POST">
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
                <div class="mb-4">
                    <label class="block text-gray-700"><?= $langText['progress'] ?? 'Progress' ?> (%)</label>
                    <input type="number" name="progress" min="0" max="100" class="w-full p-2 border rounded" required>
                </div>
                <div class="flex justify-end">
                    <button type="button" id="closeModal" class="mr-2 px-4 py-2 border rounded"><?= $langText['cancel'] ?? 'Cancel' ?></button>
                    <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded"><?= $langText['submit'] ?? 'Submit' ?></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de Edição de Projeto -->
    <div id="projectEditModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
        <div class="bg-white rounded-lg p-6 w-96 max-h-[90vh] overflow-y-auto">
            <h3 class="text-xl font-bold mb-4"><?= $langText['edit_project'] ?? 'Edit Project' ?></h3>
            <form id="projectEditForm" action="<?= $baseUrl ?>/projects/update" method="POST">
                <input type="hidden" name="id" id="editProjectId">
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
                <div class="mb-4">
                    <label class="block text-gray-700"><?= $langText['progress'] ?? 'Progress' ?> (%)</label>
                    <input type="number" name="progress" id="editProjectProgress" min="0" max="100" class="w-full p-2 border rounded" required>
                </div>
                <div class="flex justify-end">
                    <button type="button" id="closeProjectEditModal" class="mr-2 px-4 py-2 border rounded"><?= $langText['cancel'] ?? 'Cancel' ?></button>
                    <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded"><?= $langText['submit'] ?? 'Submit' ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Project - Create Modal
    const addProjectBtn = document.getElementById('addProjectBtn');
    const projectModal = document.getElementById('projectModal');
    const closeModal = document.getElementById('closeModal');

    addProjectBtn.addEventListener('click', function() {
        projectModal.classList.remove('hidden');
    });
    closeModal.addEventListener('click', function() {
        projectModal.classList.add('hidden');
    });
    window.addEventListener('click', function(event) {
        if (event.target === projectModal) {
            projectModal.classList.add('hidden');
        }
    });

    // Project - Edit Modal
    const editProjectBtns = document.querySelectorAll('.editProjectBtn');
    const projectEditModal = document.getElementById('projectEditModal');
    const closeProjectEditModal = document.getElementById('closeProjectEditModal');

    editProjectBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('editProjectId').value = this.getAttribute('data-id');
            document.getElementById('editProjectName').value = this.getAttribute('data-name');
            document.getElementById('editProjectClientName').value = this.getAttribute('data-client_name');
            document.getElementById('editProjectDescription').value = this.getAttribute('data-description');
            document.getElementById('editProjectStartDate').value = this.getAttribute('data-start_date');
            document.getElementById('editProjectEndDate').value = this.getAttribute('data-end_date');
            document.getElementById('editProjectTotalHours').value = this.getAttribute('data-total_hours');
            document.getElementById('editProjectStatus').value = this.getAttribute('data-status');
            document.getElementById('editProjectProgress').value = this.getAttribute('data-progress');
            
            projectEditModal.classList.remove('hidden');
        });
    });
    closeProjectEditModal.addEventListener('click', function() {
        projectEditModal.classList.add('hidden');
    });
    window.addEventListener('click', function(event) {
        if (event.target === projectEditModal) {
            projectEditModal.classList.add('hidden');
        }
    });
    
</script>