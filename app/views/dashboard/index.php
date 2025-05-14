<?php
require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../../../config/Database.php';

$pdo = Database::connect();
$baseUrl = '$basePath';

// === Mensagem de boas-vindas ===
$userName = $_SESSION['user']['name'] ?? '';
?>

<div class="ml-56 pt-20 p-8">
  <h1 class="text-3xl font-bold mb-6">
    <?= htmlspecialchars($langText['welcome'] ?? 'Bem-vindo', ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?>!
  </h1>

<?php
// ——— 1) Projetos ———
// Métricas atuais
$stmt = $pdo->query("
  SELECT
    SUM(status = 'in_progress') AS active,
    SUM(status = 'completed')   AS completed,
    SUM(total_hours)            AS hours
  FROM projects
");
$M = $stmt->fetch(PDO::FETCH_ASSOC);
$curActive    = (int)($M['active']   ?? 0);
$curCompleted = (int)($M['completed']?? 0);
$curHours     = (int)($M['hours']    ?? 0);

// Métricas mês anterior
$stmt = $pdo->query("
  SELECT
    SUM(status = 'in_progress') AS active,
    SUM(status = 'completed')   AS completed,
    SUM(total_hours)            AS hours
  FROM projects
  WHERE MONTH(created_at) = MONTH(CURRENT_DATE - INTERVAL 1 MONTH)
");
$L = $stmt->fetch(PDO::FETCH_ASSOC);
$prevActive    = (int)($L['active']   ?? 0);
$prevCompleted = (int)($L['completed']?? 0);
$prevHours     = (int)($L['hours']    ?? 0);

// Função de % real
function pctChange(int $cur, int $prev): float {
    if ($prev === 0) {
        return $cur > 0 ? 100.0 : 0.0;
    }
    return round((($cur - $prev) / $prev) * 100, 1);
}
$pctActive    = pctChange($curActive,    $prevActive);
$pctCompleted = pctChange($curCompleted, $prevCompleted);
$pctHours     = pctChange($curHours,     $prevHours);

// Projetos ativos limitados
$stmt = $pdo->query("
  SELECT p.*, c.name AS client_name
  FROM projects p
  LEFT JOIN client c ON p.client_id = c.id
  WHERE p.status = 'in_progress'
  ORDER BY p.created_at DESC
  LIMIT 9
");
$activeProjects = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ——— 2) Clientes (mês atual vs mês anterior) ———
$stmt = $pdo->query("
  SELECT
    SUM(active = 1)       AS cur_clients,
    SUM(loyalty_points)   AS cur_points
  FROM client
");
$C1 = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->query("
  SELECT
    SUM(active = 1)       AS prev_clients,
    SUM(loyalty_points)   AS prev_points
  FROM client
  WHERE MONTH(created_at) = MONTH(CURRENT_DATE - INTERVAL 1 MONTH)
");
$C2 = $stmt->fetch(PDO::FETCH_ASSOC);

$curClients  = (int)($C1['cur_clients'] ?? 0);
$prevClients = (int)($C2['prev_clients'] ?? 0);
$pctClients  = pctChange($curClients, $prevClients);


// ——— 3) Inventário (mês atual vs mês anterior) ———
$stmt = $pdo->query("
  SELECT
    COUNT(*)       AS cur_skus,
    SUM(quantity) AS cur_stock
  FROM inventory
");
$I1 = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->query("
  SELECT
    COUNT(*)       AS prev_skus,
    SUM(quantity) AS prev_stock
  FROM inventory
  WHERE MONTH(created_at) = MONTH(CURRENT_DATE - INTERVAL 1 MONTH)
");
$I2 = $stmt->fetch(PDO::FETCH_ASSOC);

$curSKUs   = (int)($I1['cur_skus']   ?? 0);
$prevSKUs  = (int)($I2['prev_skus']  ?? 0);
$pctSKUs   = pctChange($curSKUs, $prevSKUs);


// ——— 4) Tarefas pendentes (comparativo com mês anterior) ———
$stmt = $pdo->query("
  SELECT
    SUM(completed = 0) AS cur_pending
  FROM tasks
");
$T1 = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->query("
  SELECT
    SUM(completed = 0) AS prev_pending
  FROM tasks
  WHERE MONTH(created_at) = MONTH(CURRENT_DATE - INTERVAL 1 MONTH)
");
$T2 = $stmt->fetch(PDO::FETCH_ASSOC);

$curPending = (int)($T1['cur_pending'] ?? 0);
$prevPending= (int)($T2['prev_pending']?? 0);
$pctPending = pctChange($curPending, $prevPending);


// ——— 5) Lembretes futuros (sem comparativo) ———
$stmt = $pdo->query("
  SELECT COUNT(*) AS upcoming
  FROM reminders
  WHERE reminder_date >= CURRENT_DATE()
");
$R = $stmt->fetch(PDO::FETCH_ASSOC);
$upcoming = (int)($R['upcoming'] ?? 0);
?>

  <!-- === Cards Principais === -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
    <?php foreach ([
      ['title'=>$langText['active_projects']   ?? 'Active Projects','value'=>$curActive,'pct'=>$pctActive],
      ['title'=>$langText['total_hours']       ?? 'Total Hours','value'=>$curHours.'h','pct'=>$pctHours],
      ['title'=>$langText['team_members']      ?? 'Team Members','value'=>$pdo->query("SELECT COUNT(*) FROM employees WHERE active=1")->fetchColumn(),'pct'=>$pctClients],
      ['title'=>$langText['completed_projects']?? 'Completed Projects','value'=>$curCompleted,'pct'=>$pctCompleted],
    ] as $c): ?>
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
      <p class="text-2xl font-bold"><?= $curClients ?></p>
      <p class="text-sm <?= $pctClients>=0?'text-green-500':'text-red-500' ?>">
        <?= $pctClients>=0?'+':'' ?><?= $pctClients ?>% <?= $langText['vs_last_month'] ?? 'vs last month' ?>
      </p>
    </div>
    <div class="bg-white p-4 rounded-lg shadow">
      <h3 class="font-semibold"><?= $langText['inventory_items'] ?? 'Inventory SKUs' ?></h3>
      <p class="text-2xl font-bold"><?= $curSKUs ?></p>
      <p class="text-sm <?= $pctSKUs>=0?'text-green-500':'text-red-500' ?>">
        <?= $pctSKUs>=0?'+':'' ?><?= $pctSKUs ?>% <?= $langText['vs_last_month'] ?? 'vs last month' ?>
      </p>
    </div>
    <div class="bg-white p-4 rounded-lg shadow">
      <h3 class="font-semibold"><?= $langText['pending_tasks'] ?? 'Pending Tasks' ?></h3>
      <p class="text-2xl font-bold"><?= $curPending ?></p>
      <p class="text-sm <?= $pctPending>=0?'text-green-500':'text-red-500' ?>">
        <?= $pctPending>=0?'+':'' ?><?= $pctPending ?>% <?= $langText['vs_last_month'] ?? 'vs last month' ?>
      </p>
    </div>
    <div class="bg-white p-4 rounded-lg shadow">
      <h3 class="font-semibold"><?= $langText['upcoming_reminders'] ?? 'Upcoming Reminders' ?></h3>
      <p class="text-2xl font-bold"><?= $upcoming ?></p>
      <!-- sem comparativo para lembretes -->
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
            switch ($project['status']) {
              case 'in_progress': $c='bg-blue-500';  $t=$langText['active']   ?? 'Active';   break;
              case 'pending':     $c='bg-yellow-500';$t=$langText['pending']  ?? 'Pending';  break;
              default:            $c='bg-green-500'; $t=$langText['completed']?? 'Completed';break;
            }
            $tStmt = $pdo->prepare("SELECT completed FROM tasks WHERE project_id=?");
            $tStmt->execute([$project['id']]);
            $done = array_sum(array_column($tStmt->fetchAll(), 'completed'));
            $totalTasks = $tStmt->rowCount();
            $pr = $totalTasks ? round($done/$totalTasks*100) : 0;
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
