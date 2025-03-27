<?php
require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../../../config/Database.php';

$pdo = Database::connect();

// Se for solicitado um projeto específico, exibe os detalhes
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        echo "<div class='ml-56 pt-20 p-8'><p>Projeto não encontrado.</p></div>";
        exit;
    }
    
    // Dados fictícios para funcionários e materiais (substitua por consultas reais, se necessário)
    $employees = [
        ['name' => 'John Doe', 'role' => 'Engineer'],
        ['name' => 'Jane Smith', 'role' => 'Architect'],
    ];
    $materials = [
        ['name' => 'Cement', 'quantity' => '20 bags'],
        ['name' => 'Bricks', 'quantity' => '500 units'],
    ];
    ?>
    <div class="ml-56 pt-20 p-8">
        <a href="<?= $baseUrl ?>/projects" class="text-blue-500 underline">&larr; Voltar</a>
        <h1 class="text-2xl font-bold mt-4"><?= htmlspecialchars($project['name']) ?></h1>
        <p class="mt-2 text-gray-600">Status: <strong><?= ucfirst($project['status']) ?></strong></p>
        <p class="mt-2 text-gray-600">Progress: <strong><?= $project['progress'] ?>%</strong></p>
        <p class="mt-2 text-gray-600">Delivery Date: <strong><?= htmlspecialchars($project['end_date']) ?></strong></p>
        
        <h3 class="mt-6 text-xl font-semibold">Team Members</h3>
        <ul class="mt-2 list-disc list-inside">
            <?php foreach ($employees as $emp): ?>
                <li><?= $emp['name'] ?> (<?= $emp['role'] ?>)</li>
            <?php endforeach; ?>
        </ul>
        
        <h3 class="mt-6 text-xl font-semibold">Materials</h3>
        <ul class="mt-2 list-disc list-inside">
            <?php foreach ($materials as $mat): ?>
                <li><?= $mat['name'] ?> (<?= $mat['quantity'] ?>)</li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php
    exit;
}

// Caso não haja um 'id', exibe a lista de projetos com filtros
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
    <h1 class="text-2xl font-bold mb-4">Projects</h1>
    
    <!-- Filtros -->
    <div class="mb-6">
        <span class="mr-4 font-semibold">Filter by status:</span>
        <a href="<?= $baseUrl ?>/projects" class="mr-2 px-3 py-1 rounded-full border <?= $filter=='' ? 'bg-gray-300' : 'bg-white' ?>">All</a>
        <a href="<?= $baseUrl ?>/projects?filter=active" class="mr-2 px-3 py-1 rounded-full border <?= $filter=='active' ? 'bg-blue-200 text-blue-800' : 'bg-white' ?>">Active</a>
        <a href="<?= $baseUrl ?>/projects?filter=pending" class="mr-2 px-3 py-1 rounded-full border <?= $filter=='pending' ? 'bg-yellow-200 text-yellow-800' : 'bg-white' ?>">Pending</a>
        <a href="<?= $baseUrl ?>/projects?filter=completed" class="mr-2 px-3 py-1 rounded-full border <?= $filter=='completed' ? 'bg-green-200 text-green-800' : 'bg-white' ?>">Completed</a>
    </div>
    
    <!-- Grid de Projetos -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (empty($projects)): ?>
            <p>No projects available.</p>
        <?php else: ?>
            <?php foreach ($projects as $project): ?>
                <?php
                $status = $project['status'];
                if ($status === 'in_progress') {
                    $tag = '<span class="bg-blue-200 text-blue-800 px-2 py-1 rounded-full text-xs">Active</span>';
                } elseif ($status === 'pending') {
                    $tag = '<span class="bg-yellow-200 text-yellow-800 px-2 py-1 rounded-full text-xs">Pending</span>';
                } else {
                    $tag = '<span class="bg-green-200 text-green-800 px-2 py-1 rounded-full text-xs">Completed</span>';
                }
                $progress = $project['progress'] ?? 0;
                ?>
                <a href="<?= $baseUrl ?>/projects?id=<?= $project['id'] ?>" class="block">
                  <div class="bg-white p-4 rounded-lg shadow flex flex-col">
                    <h4 class="text-lg font-semibold"><?= htmlspecialchars($project['name']) ?></h4>
                    <p class="text-sm text-gray-600 mt-1">Delivery: <?= htmlspecialchars($project['end_date']) ?></p>
                    <div class="mt-2"><?= $tag ?></div>
                    <div class="w-full bg-gray-200 rounded-full h-1 mt-2">
                      <div class="bg-blue-500 h-1 rounded-full" style="width: <?= $progress ?>%;"></div>
                    </div>
                    <p class="mt-1 text-sm text-gray-600">Progress: <?= $progress ?>%</p>
                  </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

