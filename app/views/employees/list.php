<?php
require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../../../config/Database.php';

$pdo = Database::connect();

// Retrieve all employees ordered by created_at descending
$stmt = $pdo->query("SELECT * FROM employees ORDER BY created_at DESC");
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
                // Calculate time in company from start_date until now
                $startDate = new DateTime($employee['start_date']);
                $currentDate = new DateTime();
                $interval = $startDate->diff($currentDate);
                $years = $interval->y;
                $months = $interval->m;
                $timeInCompany = ($years > 0 ? $years . " " . ($years == 1 ? ($langText['year'] ?? 'year') : ($langText['years'] ?? 'years')) : '')
                                 . ($months > 0 ? " " . $months . " " . ($months == 1 ? ($langText['month'] ?? 'month') : ($langText['months'] ?? 'months')) : '');
                ?>
                <div class="bg-white p-4 rounded-lg shadow flex">
                    <!-- Profile picture (3x4 aspect ratio) -->
                    <div class="w-20 h-30 flex-shrink-0">
                        <img src="<?= !empty($employee['profile_picture']) ? $baseUrl . '/uploads/' . $employee['profile_picture'] : 'https://via.placeholder.com/96x128'; ?>" 
                             alt="<?= htmlspecialchars($employee['name']) ?>" 
                             class="w-full h-full object-cover rounded-lg">
                    </div>
                    <!-- Employee Information -->
                    <div class="ml-4">
                        <h2 class="text-xl font-bold"><?= htmlspecialchars($employee['name']) ?></h2>
                        <p class="text-gray-600">
                            <?= $langText['role'] ?? 'Role' ?>: 
                            <strong><?= htmlspecialchars($employee['role']) ?></strong>
                        </p>
                        <p class="text-gray-600">
                            <?= $langText['time_in_company'] ?? 'Time in Company' ?>: 
                            <strong><?= trim($timeInCompany) ?></strong>
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
