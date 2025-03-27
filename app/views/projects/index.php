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

<div class="ml-56 pt-20 p-8">
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
                <a href="<?= $baseUrl ?>/projects?id=<?= $project['id'] ?>" class="block">
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
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
