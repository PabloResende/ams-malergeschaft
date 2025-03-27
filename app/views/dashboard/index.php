<?php
require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../../../config/Database.php';

$pdo = Database::connect();

// Métricas gerais
$stmt = $pdo->query("SELECT 
  SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) AS active_projects,
  SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_projects,
  SUM(total_hours) AS total_hours
FROM projects");
$metrics = $stmt->fetch(PDO::FETCH_ASSOC);

$activeProjectsCount = $metrics['active_projects'] ?? 0;
$completedProjects = $metrics['completed_projects'] ?? 0;
$totalHours = $metrics['total_hours'] ?? 0;

// Membros da equipe
$stmt = $pdo->query("SELECT COUNT(DISTINCT id) AS team_members FROM employees");
$team = $stmt->fetch(PDO::FETCH_ASSOC);
$teamMembers = $team['team_members'] ?? 0;

// Projetos ativos (limite de 9)
$stmt = $pdo->query("SELECT * FROM projects WHERE status = 'in_progress' ORDER BY created_at DESC LIMIT 9");
$activeProjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="ml-56 pt-20 p-8">
  <h2 class="text-2xl font-bold mb-4">Projects Overview</h2>
  <p class="text-lg text-gray-600 mb-8">Track and manage your renovation projects efficiently</p>

  <!-- Cards de Métricas -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    <div class="bg-white p-4 rounded-lg shadow flex flex-col items-center">
      <h3 class="text-lg font-semibold mb-2">Active Projects</h3>
      <p class="text-3xl font-bold"><?= $activeProjectsCount ?></p>
      <p class="mt-1 text-sm text-green-500">+2.5% vs last month</p>
    </div>
    <div class="bg-white p-4 rounded-lg shadow flex flex-col items-center">
      <h3 class="text-lg font-semibold mb-2">Total Hours</h3>
      <p class="text-3xl font-bold"><?= $totalHours ?>h</p>
      <p class="mt-1 text-sm text-green-500">+12.3% vs last month</p>
    </div>
    <div class="bg-white p-4 rounded-lg shadow flex flex-col items-center">
      <h3 class="text-lg font-semibold mb-2">Team Members</h3>
      <p class="text-3xl font-bold"><?= $teamMembers ?></p>
      <p class="mt-1 text-sm text-green-500">+1 vs last month</p>
    </div>
    <div class="bg-white p-4 rounded-lg shadow flex flex-col items-center">
      <h3 class="text-lg font-semibold mb-2">Completed Projects</h3>
      <p class="text-3xl font-bold"><?= $completedProjects ?></p>
      <p class="mt-1 text-sm text-green-500">+10% vs last month</p>
    </div>
  </div>

  <!-- Grid de Projetos Ativos -->
  <div class="mt-12">
    <h3 class="text-xl font-semibold mb-6">Active Projects</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php foreach ($activeProjects as $project): ?>
        <?php $progress = $project['progress'] ?? 0; ?>
        <a href="<?= $baseUrl ?>/projects/details?id=<?= $project['id'] ?>" class="block">
          <div class="bg-white p-4 rounded-lg shadow flex flex-col">
            <h4 class="text-lg font-semibold"><?= htmlspecialchars($project['name']) ?></h4>
            <p class="text-sm text-gray-600 mt-1">Delivery: <?= htmlspecialchars($project['end_date']) ?></p>
            <div class="w-full bg-gray-200 rounded-full h-1 mt-2">
              <div class="bg-blue-500 h-1 rounded-full" style="width: <?= $progress ?>%;"></div>
            </div>
            <p class="mt-1 text-sm text-gray-600">Progress: <?= $progress ?>%</p>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>
