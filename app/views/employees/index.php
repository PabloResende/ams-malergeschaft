<?php 
require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../../../config/Database.php';

$pdo = Database::connect();
$baseUrl = '/ams-malergeschaft/public';

$stmt = $pdo->query("SELECT * FROM employees ORDER BY name ASC");
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php require_once __DIR__ . '/edit.php'; ?>
<div class="ml-56 pt-20 p-4">
    <h1 class="text-2xl font-bold mb-4"><?= $langText['employees_list'] ?? 'Employees List' ?></h1>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php if (empty($employees)): ?>
            <p><?= $langText['no_employees_available'] ?? 'No employees available.' ?></p>
        <?php else: ?>
            <?php foreach ($employees as $employee): ?>
                <?php
                $startDate = new DateTime($employee['start_date']);
                $currentDate = new DateTime();
                $interval = $startDate->diff($currentDate);
                $years = $interval->y;
                $months = $interval->m;
                $timeInCompany = ($years > 0 ? $years . " " . ($years == 1 ? ($langText['year'] ?? 'year') : ($langText['years'] ?? 'years')) : '')
                                 . ($months > 0 ? " " . $months . " " . ($months == 1 ? ($langText['month'] ?? 'month') : ($langText['months'] ?? 'months')) : '');
                ?>
                <div class="bg-white p-4 rounded-lg shadow flex flex-col">
                    <div class="flex items-center">
                        <div class="w-20 flex-shrink-0">
                            <?php if (!empty($employee['profile_picture'])): ?>
                                <img src="<?= $baseUrl ?>/employees/document?id=<?= $employee['id'] ?>&type=profile_picture" 
                                    alt="<?= htmlspecialchars($employee['name']) ?>" 
                                    class="w-full h-auto object-cover rounded-lg">
                            <?php else: ?>
                                <!-- Placeholder: insira aqui o caminho para seu SVG ou imagem padrão -->
                                <img src="caminho/para/seu-placeholder.svg" 
                                    alt="Placeholder" 
                                    class="w-full h-auto object-cover rounded-lg">
                            <?php endif; ?>
                        </div>
                        <div class="ml-4">
                            <h2 class="text-xl font-bold"><?= htmlspecialchars($employee['name']) ?> <?= htmlspecialchars($employee['last_name']) ?></h2>
                            <p class="text-gray-600"><?= $langText['role'] ?? 'Role' ?>: <strong><?= htmlspecialchars($employee['role']) ?></strong></p>
                            <p class="text-gray-600"><?= $langText['time_in_company'] ?? 'Time in Company' ?>: <strong><?= trim($timeInCompany) ?></strong></p>
                        </div>
                    </div>
                    <!-- Container dos Botões: alinhados uniformemente -->
                    <div class="mt-4 flex justify-around">
                        <button 
                            class="viewEmployeeBtn text-green-500  rounded"
                            data-id="<?= $employee['id'] ?>">
                            <?= $langText['view'] ?? 'View' ?>
                        </button>
                        <button 
                            class="editEmployeeBtn text-blue-500 rounded"
                            data-id="<?= $employee['id'] ?>"
                            data-name="<?= htmlspecialchars($employee['name']) ?>"
                            data-last_name="<?= htmlspecialchars($employee['last_name']) ?>"
                            data-role="<?= htmlspecialchars($employee['role']) ?>"
                            data-birth_date="<?= $employee['birth_date'] ?>"
                            data-start_date="<?= $employee['start_date'] ?>"
                            data-address="<?= htmlspecialchars($employee['address']) ?>"
                            data-about="<?= htmlspecialchars($employee['about']) ?>"
                            data-phone="<?= htmlspecialchars($employee['phone']) ?>"
                            data-sex="<?= htmlspecialchars($employee['sex']) ?>"
                            data-nationality="<?= htmlspecialchars($employee['nationality']) ?>"
                            data-permission_type="<?= htmlspecialchars($employee['permission_type']) ?>"
                            data-email="<?= htmlspecialchars($employee['email']) ?>"
                            data-ahv_number="<?= htmlspecialchars($employee['ahv_number']) ?>"
                            data-religion="<?= htmlspecialchars($employee['religion']) ?>"
                            data-marital_status="<?= htmlspecialchars($employee['marital_status']) ?>"
                        >
                            <?= $langText['edit'] ?? 'Edit' ?>
                        </button>
                        <a href="<?= $baseUrl ?>/employees/delete?id=<?= $employee['id'] ?>" 
                           class="text-red-500  rounded text-center text-sm"
                           onclick="return confirm('Are you sure you want to delete this employee?');">
                            <?= $langText['delete'] ?? 'Delete' ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/create.php'; ?>
<!-- Botão Flutuante para Abrir o Modal de Criação de Funcionário -->
<button id="addEmployeeBtn" class="fixed bottom-8 right-8 text-green-500">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
    </svg>
</button>
<?php require_once __DIR__ . '/details.php'; ?>
<script>
  const baseUrl = "<?= $baseUrl ?>";
</script>
<script src="<?= $baseUrl ?>/js/employees.js"></script>
