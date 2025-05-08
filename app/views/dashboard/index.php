<?php
require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../../../config/Database.php';

$pdo = Database::connect();
$baseUrl = '/ams-malergeschaft/public';

// ——— 1) Métricas de Projetos ———
// Projetos no mês atual
$stmt = $pdo->query("
  SELECT 
    SUM(status = 'in_progress') AS active_projects,
    SUM(status = 'completed') AS completed_projects,
    SUM(total_hours)           AS total_hours
  FROM projects
");
$metrics = $stmt->fetch(PDO::FETCH_ASSOC);
$activeProjectsCount   = $metrics['active_projects']    ?? 0;
$completedProjectsCount= $metrics['completed_projects'] ?? 0;
$totalHours            = $metrics['total_hours']        ?? 0;

// Projetos mês anterior
$stmt = $pdo->query("
  SELECT 
    SUM(status = 'in_progress') AS active_last,
    SUM(status = 'completed')   AS completed_last,
    SUM(total_hours)            AS hours_last
  FROM projects
  WHERE MONTH(created_at) = MONTH(CURRENT_DATE - INTERVAL 1 MONTH)
");
$last = $stmt->fetch(PDO::FETCH_ASSOC);
$activeLast     = $last['active_last']    ?? 0;
$completedLast  = $last['completed_last'] ?? 0;
$hoursLast      = $last['hours_last']     ?? 0;

// Variações percentuais
function pct($cur,$prev){
  if($prev==0) return $cur>0?100:0;
  return round((($cur-$prev)/$prev)*100,1);
}
$activePct    = pct($activeProjectsCount, $activeLast);
$completedPct = pct($completedProjectsCount, $completedLast);
$hoursPct     = pct($totalHours,             $hoursLast);

// Detalhe dos projetos ativos para o grid
$stmt = $pdo->query("
  SELECT p.*, c.name AS client_name
  FROM projects p
  LEFT JOIN client c ON p.client_id = c.id
  WHERE p.status = 'in_progress'
  ORDER BY p.created_at DESC
  LIMIT 9
");
$activeProjects = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ——— 2) Métricas de Clientes ———
$stmt = $pdo->query("
  SELECT 
    COUNT(*)       AS total_clients,
    SUM(loyalty_points) AS total_points
  FROM client
  WHERE active = 1
");
$cli = $stmt->fetch(PDO::FETCH_ASSOC);
$totalClients = $cli['total_clients'] ?? 0;
$totalPoints  = $cli['total_points']  ?? 0;


// ——— 3) Métricas de Inventário ———
$stmt = $pdo->query("
  SELECT 
    COUNT(*)   AS skus,
    SUM(quantity) AS total_stock
  FROM inventory
");
$inv = $stmt->fetch(PDO::FETCH_ASSOC);
$totalSKUs   = $inv['skus']        ?? 0;
$totalStock  = $inv['total_stock'] ?? 0;


// ——— 4) Métricas de Tarefas ———
$stmt = $pdo->query("
  SELECT 
    SUM(completed = 0) AS pending_tasks,
    SUM(completed = 1) AS done_tasks
  FROM tasks
");
$tsk = $stmt->fetch(PDO::FETCH_ASSOC);
$pendingTasks = $tsk['pending_tasks'] ?? 0;


// ——— 5) Métricas de Lembretes ———
$stmt = $pdo->query("
  SELECT COUNT(*) AS upcoming_reminders
  FROM reminders
  WHERE reminder_date >= CURRENT_DATE()
");
$rem = $stmt->fetch(PDO::FETCH_ASSOC);
$upcomingReminders = $rem['upcoming_reminders'] ?? 0;
?>

<div class="ml-56 pt-20 p-8">

  <!-- === Cards de Projetos === -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
    <?php 
      $cards = [
        [
          'title' => $langText['active_projects']   ?? 'Active Projects',
          'value' => $activeProjectsCount,
          'pct'   => $activePct,
        ],
        [
          'title' => $langText['total_hours']       ?? 'Total Hours',
          'value' => $totalHours . 'h',
          'pct'   => $hoursPct,
        ],
        [
          'title' => $langText['team_members']      ?? 'Team Members',
          'value' => $pdo->query("SELECT COUNT(*) FROM employees WHERE active=1")->fetchColumn(),
          'pct'   => $activePct,
        ],
        [
          'title' => $langText['completed_projects'] ?? 'Completed Projects',
          'value' => $completedProjectsCount,
          'pct'   => $completedPct,
        ]
      ];
      foreach($cards as $c): ?>
      <div class="bg-white p-4 rounded-lg shadow flex flex-col items-center">
        <h3 class="text-lg font-semibold mb-2"><?= $c['title'] ?></h3>
        <p class="text-3xl font-bold"><?= $c['value'] ?></p>
        <p class="mt-1 text-sm <?= $c['pct']>=0?'text-green-500':'text-red-500' ?>">
          <?= $c['pct']>=0?'+':'' ?><?= $c['pct'] ?>% <?= $langText['vs_last_month'] ?? 'vs last month' ?>
        </p>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- === Cards Extras === -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
    <div class="bg-white p-4 rounded-lg shadow">
      <h3 class="font-semibold"><?= $langText['active_clients'] ?? 'Active Clients' ?></h3>
      <p class="text-2xl font-bold"><?= $totalClients ?></p>
      <p class="text-sm"><?= $langText['total_loyalty_points'] ?? 'Total Loyalty Points' ?>: <?= $totalPoints ?></p>
    </div>
    <div class="bg-white p-4 rounded-lg shadow">
      <h3 class="font-semibold"><?= $langText['inventory_items'] ?? 'Inventory SKUs' ?></h3>
      <p class="text-2xl font-bold"><?= $totalSKUs ?></p>
      <p class="text-sm"><?= $langText['total_stock'] ?? 'Total Stock' ?>: <?= $totalStock ?></p>
    </div>
    <div class="bg-white p-4 rounded-lg shadow">
      <h3 class="font-semibold"><?= $langText['pending_tasks'] ?? 'Pending Tasks' ?></h3>
      <p class="text-2xl font-bold"><?= $pendingTasks ?></p>
    </div>
    <div class="bg-white p-4 rounded-lg shadow">
      <h3 class="font-semibold"><?= $langText['upcoming_reminders'] ?? 'Upcoming Reminders' ?></h3>
      <p class="text-2xl font-bold"><?= $upcomingReminders ?></p>
    </div>
  </div>


  <!-- === Grid de Projetos Ativos === -->
  <div class="mt-12">
    <h3 class="text-xl font-semibold mb-6"><?= $langText['active_projects'] ?? 'Active Projects' ?></h3>
    <div id="projectsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
      <?php if (empty($activeProjects)): ?>
        <p class="text-gray-600"><?= $langText['no_projects_available'] ?? 'No active projects.' ?></p>
      <?php else: ?>
        <?php foreach ($activeProjects as $project): ?>
          <?php
            // status tag
            switch ($project['status']) {
              case 'in_progress': $c='bg-blue-500';  $t=$langText['active']   ?? 'Active';   break;
              case 'pending':     $c='bg-yellow-500';$t=$langText['pending']  ?? 'Pending';  break;
              default:            $c='bg-green-500'; $t=$langText['completed']?? 'Completed';break;
            }
            // progresso via tasks
            $tStmt = $pdo->prepare("SELECT completed FROM tasks WHERE project_id=?");
            $tStmt->execute([$project['id']]);
            $d = array_sum(array_column($tStmt->fetchAll(), 'completed'));
            $pr = $tStmt->rowCount()? round($d/$tStmt->rowCount()*100):0;
          ?>
          <div
            class="project-item bg-white p-6 rounded-xl shadow hover:shadow-md transition-all cursor-pointer"
            onclick="location.href='<?= $baseUrl ?>/projects'"
          >
            <div class="flex items-center justify-between mb-2">
              <h4 class="text-xl font-bold"><?= htmlspecialchars($project['name'],ENT_QUOTES) ?></h4>
              <span class="<?= $c ?> text-white px-3 py-1 rounded-full text-[12px] font-semibold"><?= $t ?></span>
            </div>
            <p class="text-sm text-gray-600 mb-1">
              <strong><?= $langText['client'] ?? 'Client' ?>:</strong>
              <?= htmlspecialchars($project['client_name'] ?? '—',ENT_QUOTES) ?>
            </p>
            <p class="text-sm text-gray-600 mb-2">
              <strong><?= $langText['budget'] ?? 'Budget' ?>:</strong>
              <?= number_format($project['budget'],2,',','.') ?>
            </p>
            <div class="w-full bg-gray-200 rounded-full h-2 mb-1">
              <div class="bg-blue-500 h-2 rounded-full" style="width:<?= $pr ?>%;"></div>
            </div>
            <p class="text-sm text-gray-600">
              <?= $langText['employees'] ?? 'Employees' ?>: <?= $project['employee_count'] ?> |
              <?= $langText['progress']  ?? 'Progress'  ?>: <?= $pr ?>%
            </p>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
