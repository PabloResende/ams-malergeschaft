<?php
require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../../../config/Database.php';

$pdo = Database::connect();

// Métricas gerais (mês atual)
$stmt = $pdo->query("
    SELECT 
      SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) AS active_projects,
      SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_projects,
      SUM(total_hours) AS total_hours
    FROM projects
");
$metrics = $stmt->fetch(PDO::FETCH_ASSOC);

$activeProjectsCount   = $metrics['active_projects']   ?? 0;
$completedProjects      = $metrics['completed_projects']?? 0;
$totalHours             = $metrics['total_hours']       ?? 0;

// Métricas do mês anterior
$stmt = $pdo->query("
    SELECT 
      SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) AS active_projects_last_month,
      SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_projects_last_month,
      SUM(total_hours) AS total_hours_last_month
    FROM projects 
    WHERE MONTH(created_at) = MONTH(CURRENT_DATE - INTERVAL 1 MONTH)
");
$lastMonthMetrics = $stmt->fetch(PDO::FETCH_ASSOC);

$activeProjectsLastMonth    = $lastMonthMetrics['active_projects_last_month'] ?? 0;
$completedProjectsLastMonth = $lastMonthMetrics['completed_projects_last_month'] ?? 0;
$totalHoursLastMonth        = $lastMonthMetrics['total_hours_last_month']        ?? 0;

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

$activeProjectsChange    = calculatePercentageChange($activeProjectsCount, $activeProjectsLastMonth);
$completedProjectsChange = calculatePercentageChange($completedProjects,    $completedProjectsLastMonth);
$totalHoursChange        = calculatePercentageChange($totalHours,           $totalHoursLastMonth);

// Projetos ativos (limite de 9) – com JOIN para obter client_name
$stmt = $pdo->query("
    SELECT p.*, c.name AS client_name
    FROM projects p
    LEFT JOIN client c ON p.client_id = c.id
    WHERE p.status = 'in_progress'
    ORDER BY p.created_at DESC
    LIMIT 9
");
$activeProjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

$baseUrl = '/ams-malergeschaft/public';
?>

<div class="ml-56 pt-20 p-8">
  <h2 class="text-2xl font-bold mb-4"><?= $langText['projects_overview'] ?? 'Projects Overview' ?></h2>
  <p class="text-lg text-gray-600 mb-8"><?= $langText['track_and_manage'] ?? 'Track and manage your renovation projects efficiently' ?></p>

  <!-- Cards de Métricas -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    <div class="bg-white p-4 rounded-lg shadow flex flex-col items-center">
      <h3 class="text-lg font-semibold mb-2"><?= $langText['active_projects'] ?? 'Active Projects' ?></h3>
      <p class="text-3xl font-bold"><?= $activeProjectsCount ?></p>
      <p class="mt-1 text-sm <?= $activeProjectsChange >= 0 ? 'text-green-500' : 'text-red-500' ?>">
        <?= $activeProjectsChange >= 0 ? '+' : '' ?><?= $activeProjectsChange ?>% <?= $langText['vs_last_month'] ?? 'vs last month' ?>
      </p>
    </div>
    <div class="bg-white p-4 rounded-lg shadow flex flex-col items-center">
      <h3 class="text-lg font-semibold mb-2"><?= $langText['total_hours'] ?? 'Total Hours' ?></h3>
      <p class="text-3xl font-bold"><?= $totalHours ?>h</p>
      <p class="mt-1 text-sm <?= $totalHoursChange >= 0 ? 'text-green-500' : 'text-red-500' ?>">
        <?= $totalHoursChange >= 0 ? '+' : '' ?><?= $totalHoursChange ?>% <?= $langText['vs_last_month'] ?? 'vs last month' ?>
      </p>
    </div>
    <div class="bg-white p-4 rounded-lg shadow flex flex-col items-center">
      <h3 class="text-lg font-semibold mb-2"><?= $langText['team_members'] ?? 'Team Members' ?></h3>
      <p class="text-3xl font-bold"><?= $teamMembers ?></p>
      <p class="mt-1 text-sm <?= $teamMembers >= 0 ? 'text-green-500' : 'text-red-500' ?>">
        <?= '+' . $activeProjectsChange ?> <?= $langText['vs_last_month'] ?? 'vs last month' ?>
      </p>
    </div>
    <div class="bg-white p-4 rounded-lg shadow flex flex-col items-center">
      <h3 class="text-lg font-semibold mb-2"><?= $langText['completed_projects'] ?? 'Completed Projects' ?></h3>
      <p class="text-3xl font-bold"><?= $completedProjects ?></p>
      <p class="mt-1 text-sm <?= $completedProjectsChange >= 0 ? 'text-green-500' : 'text-red-500' ?>">
        <?= $completedProjectsChange >= 0 ? '+' : '' ?><?= $completedProjectsChange ?>% <?= $langText['vs_last_month'] ?? 'vs last month' ?>
      </p>
    </div>
  </div>

  <!-- Grid de Projetos Ativos (estilo igual à página de projetos) -->
  <div class="mt-12">
    <h3 class="text-xl font-semibold mb-6"><?= $langText['active_projects'] ?? 'Active Projects' ?></h3>
    <div id="projectsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
      <?php if (empty($activeProjects)): ?>
        <p class="text-gray-600"><?= $langText['no_projects_available'] ?? 'No active projects.' ?></p>
      <?php else: ?>
        <?php foreach ($activeProjects as $project): ?>
          <?php
            // Tag de status
            switch ($project['status'] ?? '') {
              case 'in_progress':
                $tagClass = 'bg-blue-500';
                $tagText  = $langText['active'] ?? 'Active';
                break;
              case 'pending':
                $tagClass = 'bg-yellow-500';
                $tagText  = $langText['pending'] ?? 'Pending';
                break;
              default:
                $tagClass = 'bg-green-500';
                $tagText  = $langText['completed'] ?? 'Completed';
                break;
            }
            $tag = "<span class=\"{$tagClass} text-white px-3 py-1 rounded-full text-[12px] font-semibold\">"
                 . htmlspecialchars($tagText, ENT_QUOTES, 'UTF-8')
                 . "</span>";

            // Progresso via tasks
            $tStmt = $pdo->prepare("SELECT completed FROM tasks WHERE project_id = ?");
            $tStmt->execute([$project['id']]);
            $tasksData = $tStmt->fetchAll(PDO::FETCH_ASSOC);
            $done     = array_reduce($tasksData, fn($c,$t) => $c + (int)$t['completed'], 0);
            $progress = count($tasksData) ? round($done / count($tasksData) * 100) : 0;
          ?>
          <div
            class="project-item bg-white p-6 rounded-xl shadow hover:shadow-md transition-all cursor-pointer"
            onclick="location.href='<?= $baseUrl ?>/projects'"
          >
            <div class="flex items-center justify-between mb-2">
              <h4 class="text-xl font-bold flex-1">
                <?= htmlspecialchars($project['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
              </h4>
              <?= $tag ?>
            </div>

            <p class="text-sm text-gray-600 mb-1">
              <span class="font-semibold"><?= htmlspecialchars($langText['client'] ?? 'Client', ENT_QUOTES, 'UTF-8') ?>:</span>
              <?= htmlspecialchars($project['client_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
            </p>

            <p class="text-sm text-gray-600 mb-1">
              <span class="font-semibold"><?= htmlspecialchars($langText['location'] ?? 'Location', ENT_QUOTES, 'UTF-8') ?>:</span>
              <?= htmlspecialchars($project['location'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
            </p>

            <p class="text-sm text-gray-600 mb-2">
              <span class="font-semibold"><?= htmlspecialchars($langText['budget'] ?? 'Budget', ENT_QUOTES, 'UTF-8') ?>:</span>
              <?= number_format((float)($project['budget'] ?? 0), 2, ',', '.') ?>
            </p>

            <div class="w-full bg-gray-200 rounded-full h-2 mb-1">
              <div class="bg-blue-500 h-2 rounded-full" style="width:<?= $progress ?>%;"></div>
            </div>
            <p class="text-sm text-gray-600">
              <?= htmlspecialchars($langText['employee_count'] ?? 'Employees', ENT_QUOTES, 'UTF-8') ?>:
              <?= (int)($project['employee_count'] ?? 0) ?> |
              <?= htmlspecialchars($langText['progress'] ?? 'Progress', ENT_QUOTES, 'UTF-8') ?>:
              <?= $progress ?>%
            </p>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
