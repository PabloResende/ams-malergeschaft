<?php
require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../../../config/Database.php';

$pdo = Database::connect();
$baseUrl = '/ams-malergeschaft/public';

$stmt = $pdo->query("SELECT * FROM employees ORDER BY name ASC");
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="ml-56 pt-20 p-4">
    <h1 class="text-2xl font-bold mb-4"><?= $langText['employees_list'] ?? 'Employees List' ?></h1>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php if (empty($employees)): ?>
            <p><?= $langText['no_employees_available'] ?? 'No employees available.' ?></p>
        <?php else: ?>
            <?php foreach ($employees as $employee): ?>
                <?php
                // Calcular tempo na empresa
                $startDate = new DateTime($employee['start_date']);
                $currentDate = new DateTime();
                $interval = $startDate->diff($currentDate);
                $years = $interval->y;
                $months = $interval->m;
                $timeInCompany = ($years > 0 ? $years . " " . ($years == 1 ? ($langText['year'] ?? 'year') : ($langText['years'] ?? 'years')) : '')
                                 . ($months > 0 ? " " . $months . " " . ($months == 1 ? ($langText['month'] ?? 'month') : ($langText['months'] ?? 'months')) : '');
                ?>
                <div class="bg-white p-4 rounded-lg shadow flex">
                    <div class="w-20 flex-shrink-0">
                        <img src="<?= !empty($employee['profile_picture']) ? $baseUrl . '/uploads/' . $employee['profile_picture'] : 'https://via.placeholder.com/96x128'; ?>" 
                             alt="<?= htmlspecialchars($employee['name']) ?>" 
                             class="w-full h-auto object-cover rounded-lg">
                    </div>
                    <div class="ml-4">
                        <h2 class="text-xl font-bold"><?= htmlspecialchars($employee['name']) ?></h2>
                        <p class="text-gray-600"><?= $langText['role'] ?? 'Role' ?>: <strong><?= htmlspecialchars($employee['role']) ?></strong></p>
                        <p class="text-gray-600"><?= $langText['time_in_company'] ?? 'Time in Company' ?>: <strong><?= trim($timeInCompany) ?></strong></p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Botão Flutuante para Abrir o Modal de Criação de Funcionário -->
<button id="addEmployeeBtn" class="fixed bottom-8 right-8 bg-green-500 text-white rounded-full p-4 shadow-lg hover:bg-green-600">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
    </svg>
</button>

<!-- Modal para Cadastrar Funcionário -->
<div id="employeeModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white rounded-lg p-6 w-80 max-h-[80vh] overflow-y-auto">
        <h2 class="text-2xl font-bold mb-4"><?= $langText['create_employee'] ?? 'Create Employee' ?></h2>
        <form action="<?= $baseUrl ?>/employees/store" method="POST" enctype="multipart/form-data" class="space-y-4">
            <div>
                <label class="block mb-2 font-medium"><?= $langText['name'] ?? 'Name' ?></label>
                <input type="text" name="name" required class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
            </div>
            <div>
                <label class="block mb-2 font-medium"><?= $langText['role'] ?? 'Role' ?></label>
                <input type="text" name="role" required class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
            </div>
            <div>
                <label class="block mb-2 font-medium"><?= $langText['birth_date'] ?? 'Birth Date' ?></label>
                <input type="date" name="birth_date" required class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
            </div>
            <div>
                <label class="block mb-2 font-medium"><?= $langText['start_date'] ?? 'Start Date' ?></label>
                <input type="date" name="start_date" required class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
            </div>
            <div>
                <label class="block mb-2 font-medium"><?= $langText['address'] ?? 'Address' ?></label>
                <input type="text" name="address" class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
            </div>
            <div>
                <label class="block mb-2 font-medium"><?= $langText['about_me'] ?? 'About Me' ?></label>
                <textarea name="about" class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300"></textarea>
            </div>
            <div>
                <label class="block mb-2 font-medium"><?= $langText['phone'] ?? 'Phone' ?></label>
                <input type="text" name="phone" class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
            </div>
            <div>
                <label class="block mb-2 font-medium"><?= $langText['profile_picture'] ?? 'Profile Picture' ?></label>
                <input type="file" name="profile_picture" class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
            </div>
            <div class="flex justify-end">
                <button type="button" id="closeEmployeeModal" class="mr-2 px-4 py-2 border rounded"><?= $langText['cancel'] ?? 'Cancel' ?></button>
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded"><?= $langText['submit'] ?? 'Submit' ?></button>
            </div>
        </form>
    </div>
</div>

<script>
    const addEmployeeBtn = document.getElementById('addEmployeeBtn');
    const employeeModal = document.getElementById('employeeModal');
    const closeEmployeeModal = document.getElementById('closeEmployeeModal');

    addEmployeeBtn.addEventListener('click', function() {
        employeeModal.classList.remove('hidden');
    });

    closeEmployeeModal.addEventListener('click', function() {
        employeeModal.classList.add('hidden');
    });

    window.addEventListener('click', function(event) {
        if (event.target === employeeModal) {
            employeeModal.classList.add('hidden');
        }
    });
</script>