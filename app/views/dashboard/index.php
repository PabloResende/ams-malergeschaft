<?php
require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../../../config/Database.php';

$pdo = Database::connect();
// Métricas gerais (mês atual)
$stmt = $pdo->query("SELECT 
  SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) AS active_projects,
  SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_projects,
  SUM(total_hours) AS total_hours
FROM projects");
$metrics = $stmt->fetch(PDO::FETCH_ASSOC);

$activeProjectsCount = $metrics['active_projects'] ?? 0;
$completedProjects = $metrics['completed_projects'] ?? 0;
$totalHours = $metrics['total_hours'] ?? 0;

// Métricas do mês anterior
$stmt = $pdo->query("SELECT 
  SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) AS active_projects_last_month,
  SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_projects_last_month,
  SUM(total_hours) AS total_hours_last_month
FROM projects 
WHERE MONTH(created_at) = MONTH(CURRENT_DATE - INTERVAL 1 MONTH)");
$lastMonthMetrics = $stmt->fetch(PDO::FETCH_ASSOC);

$activeProjectsLastMonth = $lastMonthMetrics['active_projects_last_month'] ?? 0;
$completedProjectsLastMonth = $lastMonthMetrics['completed_projects_last_month'] ?? 0;
$totalHoursLastMonth = $lastMonthMetrics['total_hours_last_month'] ?? 0;

// Membros da equipe
$stmt = $pdo->query("SELECT COUNT(DISTINCT id) AS team_members FROM employees");
$team = $stmt->fetch(PDO::FETCH_ASSOC);
$teamMembers = $team['team_members'] ?? 0;

// Função para calcular a variação percentual
function calculatePercentageChange($current, $previous) {
    if ($previous == 0) {
        return $current > 0 ? 100 : 0;
    }
    return round((($current - $previous) / $previous) * 100, 1);
}

$activeProjectsChange = calculatePercentageChange($activeProjectsCount, $activeProjectsLastMonth);
$completedProjectsChange = calculatePercentageChange($completedProjects, $completedProjectsLastMonth);
$totalHoursChange = calculatePercentageChange($totalHours, $totalHoursLastMonth);

// Projetos ativos (limite de 9)
$stmt = $pdo->query("SELECT * FROM projects WHERE status = 'in_progress' ORDER BY created_at DESC LIMIT 9");
$activeProjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="ml-56 pt-20 p-8">
  <h2 class="text-2xl font-bold mb-4"><?= $langText['projects_overview'] ?? 'Projects Overview' ?></h2>
  <p class="text-lg text-gray-600 mb-8"><?= $langText['track_and_manage'] ?? 'Track and manage your renovation projects efficiently' ?></p>

  <!-- Cards de Métricas -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    <div class="bg-white p-4 rounded-lg shadow flex flex-col items-center">
      <h3 class="text-lg font-semibold mb-2"><?= $langText['active_projects'] ?? 'Active Projects' ?></h3>
      <p class="text-3xl font-bold"><?= $activeProjectsCount ?></p>
      <p class="mt-1 text-sm text-green-500"><?= $activeProjectsChange ?>% <?= $langText['vs_last_month'] ?? 'vs last month' ?></p>
    </div>
    <div class="bg-white p-4 rounded-lg shadow flex flex-col items-center">
      <h3 class="text-lg font-semibold mb-2"><?= $langText['total_hours'] ?? 'Total Hours' ?></h3>
      <p class="text-3xl font-bold"><?= $totalHours ?>h</p>
      <p class="mt-1 text-sm text-green-500"><?= $totalHoursChange ?>% <?= $langText['vs_last_month'] ?? 'vs last month' ?></p>
    </div>
    <div class="bg-white p-4 rounded-lg shadow flex flex-col items-center">
      <h3 class="text-lg font-semibold mb-2"><?= $langText['team_members'] ?? 'Team Members' ?></h3>
      <p class="text-3xl font-bold"><?= $teamMembers ?></p>
      <p class="mt-1 text-sm text-green-500">+1 <?= $langText['vs_last_month'] ?? 'vs last month' ?></p>
    </div>
    <div class="bg-white p-4 rounded-lg shadow flex flex-col items-center">
      <h3 class="text-lg font-semibold mb-2"><?= $langText['completed_projects'] ?? 'Completed Projects' ?></h3>
      <p class="text-3xl font-bold"><?= $completedProjects ?></p>
      <p class="mt-1 text-sm text-green-500"><?= $completedProjectsChange ?>% <?= $langText['vs_last_month'] ?? 'vs last month' ?></p>
    </div>
  </div>

  <!-- Grid de Projetos Ativos -->
  <div class="mt-12">
    <h3 class="text-xl font-semibold mb-6"><?= $langText['active_projects'] ?? 'Active Projects' ?></h3>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($activeProjects as $project): ?>
            <?php
                $progress = $project['progress'] ?? 0;
                $status = $project['status'];

                // Definindo as tags de status com cores
                if ($status === 'in_progress') {
                    $tag = '<span class="bg-blue-200 text-blue-800 px-2 py-1 rounded-full text-xs">' . ($langText['active'] ?? 'Active') . '</span>';
                } elseif ($status === 'pending') {
                    $tag = '<span class="bg-yellow-200 text-yellow-800 px-2 py-1 rounded-full text-xs">' . ($langText['pending'] ?? 'Pending') . '</span>';
                } else {
                    $tag = '<span class="bg-green-200 text-green-800 px-2 py-1 rounded-full text-xs">' . ($langText['completed'] ?? 'Completed') . '</span>';
                }
            ?>
            <a href="<?= $baseUrl ?>/projects/details?id=<?= $project['id'] ?>" class="block">
                <div class="bg-white p-6 rounded-xl shadow flex flex-col hover:shadow-md transition-all">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="text-xl font-bold flex-1"><?= htmlspecialchars($project['name']) ?></h4>
                        <?= $tag ?>
                    </div>
                    <span>
                        <h1 class="text-[13px] text-gray-600"><?= $langText['client'] ?? 'Client' ?></h1>
                        <p class="text-sm font-semibold -mt-1"><?= htmlspecialchars($project['client_name']) ?></p>
                    </span>

                    <div class="w-full bg-gray-200 rounded-full h-2 mt-3">
                        <div class="bg-blue-500 h-2 rounded-full" style="width: <?= $progress ?>%;"></div>
                    </div>
                    <p class="mt-1 text-sm text-gray-600"><?= $langText['progress'] ?? 'Progress' ?>: <?= $progress ?>%</p>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
  </div>
</div>
