<?php
// app/views/dashboard/index.php

require_once __DIR__ . '/../layout/header.php';

global $pdo;
$baseUrl  = BASE_URL;
$userName = $_SESSION['user']['name'] ?? '';

// Função de cálculo de variação percentual 
function pctChange(float $cur, float $prev): float {
    if ($prev === 0.0) {
        return $cur > 0.0 ? 100.0 : 0.0;
    }
    return round((($cur - $prev) / $prev) * 100, 1);
}

// ——— 1) Projetos ———
// Métricas atuais de projetos (somente mês corrente)
$stmt = $pdo->query("
  SELECT
    SUM(status = 'in_progress') AS active,
    SUM(status = 'completed')   AS completed
  FROM projects
  WHERE MONTH(created_at) = MONTH(CURRENT_DATE())
    AND YEAR(created_at)  = YEAR(CURRENT_DATE())
");
$M = $stmt->fetch(PDO::FETCH_ASSOC);
$curActive    = (int)($M['active']    ?? 0);
$curCompleted = (int)($M['completed'] ?? 0);

// Métricas mês anterior de projetos
$stmt = $pdo->query("
  SELECT
    SUM(status = 'in_progress') AS active,
    SUM(status = 'completed')   AS completed
  FROM projects
  WHERE MONTH(created_at) = MONTH(CURRENT_DATE - INTERVAL 1 MONTH)
    AND YEAR(created_at)  = YEAR(CURRENT_DATE - INTERVAL 1 MONTH)
");
$L = $stmt->fetch(PDO::FETCH_ASSOC);
$prevActive    = (int)($L['active']    ?? 0);
$prevCompleted = (int)($L['completed'] ?? 0);

// Horas registradas no MÊS ATUAL
$stmt = $pdo->query("
  SELECT COALESCE(SUM(hours),0) AS hours
    FROM project_work_logs
   WHERE MONTH(date) = MONTH(CURRENT_DATE())
     AND YEAR(date)  = YEAR(CURRENT_DATE())
");
$H = $stmt->fetch(PDO::FETCH_ASSOC);
$curHours = (float)($H['hours'] ?? 0.0);

// Horas registradas no MÊS ANTERIOR
$stmt = $pdo->query("
  SELECT COALESCE(SUM(hours),0) AS hours
    FROM project_work_logs
   WHERE MONTH(date) = MONTH(CURRENT_DATE - INTERVAL 1 MONTH)
     AND YEAR(date)  = YEAR(CURRENT_DATE - INTERVAL 1 MONTH)
");
$Lh = $stmt->fetch(PDO::FETCH_ASSOC);
$prevHours = (float)($Lh['hours'] ?? 0.0);

// Calcula % de variação para projetos e horas
$pctActive    = pctChange($curActive,    $prevActive);
$pctCompleted = pctChange($curCompleted, $prevCompleted);
$pctHours     = pctChange($curHours,     $prevHours);

// Projetos ativos limitados (últimos 9) — mantém sem filtro mensal
$stmt = $pdo->query("
  SELECT p.*, c.name AS client_name
  FROM projects p
  LEFT JOIN client c ON p.client_id = c.id
  WHERE p.status = 'in_progress'
  ORDER BY p.created_at DESC
  LIMIT 9
");
$activeProjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ——— 2) Clientes ———
// Não filtrar por mês (permanece total)
$stmt = $pdo->query("
  SELECT
    SUM(active = 1)     AS cur_clients,
    SUM(loyalty_points) AS cur_points
  FROM client
");
$C1 = $stmt->fetch(PDO::FETCH_ASSOC);

// Para cálculo de variação, mantém como estava (mês anterior)
$stmt = $pdo->query("
  SELECT
    SUM(active = 1)     AS prev_clients,
    SUM(loyalty_points) AS prev_points
  FROM client
  WHERE MONTH(created_at) = MONTH(CURRENT_DATE - INTERVAL 1 MONTH)
    AND YEAR(created_at)  = YEAR(CURRENT_DATE - INTERVAL 1 MONTH)
");
$C2 = $stmt->fetch(PDO::FETCH_ASSOC);

$curClients  = (int)($C1['cur_clients']  ?? 0);
$prevClients = (int)($C2['prev_clients'] ?? 0);
$pctClients  = pctChange($curClients, $prevClients);

// ——— 3) Inventário ———
// Mantém contabilização total (sem filtro mensal)
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
    AND YEAR(created_at)  = YEAR(CURRENT_DATE - INTERVAL 1 MONTH)
");
$I2 = $stmt->fetch(PDO::FETCH_ASSOC);

$curSKUs  = (int)($I1['cur_skus']  ?? 0);
$prevSKUs = (int)($I2['prev_skus'] ?? 0);
$pctSKUs  = pctChange($curSKUs, $prevSKUs);

// ——— 4) Tarefas pendentes ———
// Mantém contabilização total de pendentes
$stmt = $pdo->query("
  SELECT SUM(completed = 0) AS cur_pending
  FROM tasks
");
$T1 = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->query("
  SELECT SUM(completed = 0) AS prev_pending
  FROM tasks
  WHERE MONTH(created_at) = MONTH(CURRENT_DATE - INTERVAL 1 MONTH)
    AND YEAR(created_at)  = YEAR(CURRENT_DATE - INTERVAL 1 MONTH)
");
$T2 = $stmt->fetch(PDO::FETCH_ASSOC);

$curPending  = (int)($T1['cur_pending']  ?? 0);
$prevPending = (int)($T2['prev_pending'] ?? 0);
$pctPending  = pctChange($curPending, $prevPending);

// ——— 5) Lembretes futuros (usando CalendarModel) ———
// Corrigido o caminho para Calendar.php
require_once __DIR__ . '/../../models/Calendar.php';
$calModel  = new CalendarModel();

// Pega todos os eventos a partir de hoje (start = hoje, end = fim de prazo longo)
$today     = date('Y-m-d');
$farFuture = '9999-12-31';
$events    = $calModel->getEventsInRange($today, $farFuture);

// Conta quantos eventos ainda estão por vir
$upcoming = is_array($events) ? count($events) : 0;
?>

<div class="pt-20 px-4 py-6 sm:px-8 sm:py-8 ml-0 lg:ml-56">
  <h1 class="text-3xl font-bold mb-6 max-w-full break-words">
    <?= htmlspecialchars($langText['welcome'] ?? 'Bem-vindo', ENT_QUOTES) ?>,
    <?= htmlspecialchars($userName, ENT_QUOTES) ?>!
  </h1>

  <!-- === Cards Principais (Somente mês corrente, exceto Team Members) === -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
    <div class="bg-white p-4 rounded-lg shadow flex flex-col items-center">
      <h3 class="text-lg font-semibold mb-2 text-center">
        <?= htmlspecialchars($langText['active_projects'] ?? 'Active Projects', ENT_QUOTES) ?>
      </h3>
      <p class="text-3xl font-bold"><?= $curActive ?></p>
      <p class="mt-1 text-sm <?= $pctActive >= 0 ? 'text-green-500' : 'text-red-500' ?> text-center">
        <?= ($pctActive >= 0 ? '+' : '') . $pctActive ?>%
        <?= htmlspecialchars($langText['vs_last_month'] ?? 'vs last month', ENT_QUOTES) ?>
      </p>
    </div>

    <div class="bg-white p-4 rounded-lg shadow flex flex-col items-center">
      <h3 class="text-lg font-semibold mb-2 text-center">
        <?= htmlspecialchars($langText['total_hours_month'] ?? 'Horas este Mês', ENT_QUOTES) ?>
      </h3>
      <p class="text-3xl font-bold"><?= number_format($curHours, 2, ',', '.') ?>h</p>
      <p class="mt-1 text-sm <?= $pctHours >= 0 ? 'text-green-500' : 'text-red-500' ?> text-center">
        <?= ($pctHours >= 0 ? '+' : '') . $pctHours ?>%
        <?= htmlspecialchars($langText['vs_last_month'] ?? 'vs last month', ENT_QUOTES) ?>
      </p>
    </div>

    <div class="bg-white p-4 rounded-lg shadow flex flex-col items-center">
      <h3 class="text-lg font-semibold mb-2 text-center">
        <?= htmlspecialchars($langText['team_members'] ?? 'Team Members', ENT_QUOTES) ?>
      </h3>
      <?php 
        // Membros de equipe não filtrados por mês
        $teamCount = (int)$pdo->query("SELECT COUNT(*) FROM employees WHERE active=1")->fetchColumn();
      ?>
      <p class="text-3xl font-bold"><?= $teamCount ?></p>
      <p class="mt-1 text-sm text-gray-500 text-center">
        <?= htmlspecialchars($langText['total'] ?? 'Total', ENT_QUOTES) ?>
      </p>
    </div>

    <div class="bg-white p-4 rounded-lg shadow flex flex-col items-center">
      <h3 class="text-lg font-semibold mb-2 text-center">
        <?= htmlspecialchars($langText['completed_projects'] ?? 'Completed Projects', ENT_QUOTES) ?>
      </h3>
      <p class="text-3xl font-bold"><?= $curCompleted ?></p>
      <p class="mt-1 text-sm <?= $pctCompleted >= 0 ? 'text-green-500' : 'text-red-500' ?> text-center">
        <?= ($pctCompleted >= 0 ? '+' : '') . $pctCompleted ?>%
        <?= htmlspecialchars($langText['vs_last_month'] ?? 'vs last month', ENT_QUOTES) ?>
      </p>
    </div>
  </div>

  <!-- === Cards Extras (mantidos sem filtro de mês, exceto Active Clients) === -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
    <div class="bg-white p-4 rounded-lg shadow">
      <h3 class="font-semibold mb-2 text-center"><?= htmlspecialchars($langText['active_clients'] ?? 'Active Clients', ENT_QUOTES) ?></h3>
      <p class="text-2xl font-bold text-center"><?= $curClients ?></p>
      <p class="text-sm <?= $pctClients >= 0 ? 'text-green-500' : 'text-red-500' ?> text-center">
        <?= ($pctClients >= 0 ? '+' : '') . $pctClients ?>%
        <?= htmlspecialchars($langText['vs_last_month'] ?? 'vs last month', ENT_QUOTES) ?>
      </p>
    </div>

    <div class="bg-white p-4 rounded-lg shadow">
      <h3 class="font-semibold mb-2 text-center"><?= htmlspecialchars($langText['inventory_items'] ?? 'Inventory SKUs', ENT_QUOTES) ?></h3>
      <p class="text-2xl font-bold text-center"><?= $curSKUs ?></p>
      <p class="text-sm <?= $pctSKUs >= 0 ? 'text-green-500' : 'text-red-500' ?> text-center">
        <?= ($pctSKUs >= 0 ? '+' : '') . $pctSKUs ?>%
        <?= htmlspecialchars($langText['vs_last_month'] ?? 'vs last month', ENT_QUOTES) ?>
      </p>
    </div>

    <div class="bg-white p-4 rounded-lg shadow">
      <h3 class="font-semibold mb-2 text-center"><?= htmlspecialchars($langText['pending_tasks'] ?? 'Pending Tasks', ENT_QUOTES) ?></h3>
      <p class="text-2xl font-bold text-center"><?= $curPending ?></p>
      <p class="text-sm <?= $pctPending >= 0 ? 'text-green-500' : 'text-red-500' ?> text-center">
        <?= ($pctPending >= 0 ? '+' : '') . $pctPending ?>%
        <?= htmlspecialchars($langText['vs_last_month'] ?? 'vs last month', ENT_QUOTES) ?>
      </p>
    </div>

    <div class="bg-white p-4 rounded-lg shadow">
      <h3 class="font-semibold mb-2 text-center"><?= htmlspecialchars($langText['upcoming_reminders'] ?? 'Upcoming Reminders', ENT_QUOTES) ?></h3>
      <p class="text-2xl font-bold text-center"><?= $upcoming ?></p>
    </div>
  </div>

  <!-- === Projetos Ativos === -->
  <div class="mt-12">
    <h3 class="text-xl font-semibold mb-6"><?= htmlspecialchars($langText['active_projects'] ?? 'Active Projects', ENT_QUOTES) ?></h3>
    <div id="projectsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
      <?php if (empty($activeProjects)): ?>
        <p class="text-gray-600"><?= htmlspecialchars($langText['no_projects_available'] ?? 'No active projects.', ENT_QUOTES) ?></p>
      <?php else: foreach ($activeProjects as $project):
          switch ($project['status']) {
            case 'in_progress': $badgeClass = 'bg-blue-500';  $badgeText = $langText['active']    ?? 'Active';   break;
            case 'pending':     $badgeClass = 'bg-yellow-500';$badgeText = $langText['pending']   ?? 'Pending';  break;
            default:            $badgeClass = 'bg-green-500'; $badgeText = $langText['completed'] ?? 'Completed';break;
          }
          $tStmt = $pdo->prepare("SELECT completed FROM tasks WHERE project_id = ?");
          $tStmt->execute([$project['id']]);
          $done = array_sum(array_column($tStmt->fetchAll(), 'completed'));
          $totalTasks = $tStmt->rowCount();
          $pr = $totalTasks ? round($done / $totalTasks * 100) : 0;
      ?>
        <div
          class="project-item bg-white p-6 rounded-xl shadow hover:shadow-md transition-all cursor-pointer"
          onclick="location.href='<?= $baseUrl ?>/projects';"
        >
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-2">
            <h4 class="text-xl font-bold"><?= htmlspecialchars($project['name'], ENT_QUOTES) ?></h4>
            <span class="<?= $badgeClass ?> text-white px-3 py-1 rounded-full text-[12px] font-semibold mt-2 sm:mt-0">
              <?= htmlspecialchars($badgeText, ENT_QUOTES) ?>
            </span>
          </div>
          <p class="text-sm text-gray-600 mb-1">
            <strong><?= htmlspecialchars($langText['client'] ?? 'Client', ENT_QUOTES) ?>:</strong>
            <?= htmlspecialchars($project['client_name'] ?? '—', ENT_QUOTES) ?>
          </p>
          <p class="text-sm text-gray-600 mb-2">
            <strong><?= htmlspecialchars($langText['budget'] ?? 'Budget', ENT_QUOTES) ?>:</strong>
            <?= number_format($project['budget'], 2, ',', '.') ?>
          </p>
          <div class="w-full bg-gray-200 rounded-full h-2 mb-1">
            <div class="bg-blue-500 h-2 rounded-full" style="width:<?= $pr ?>%;"></div>
          </div>
          <p class="text-sm text-gray-600">
            <?= htmlspecialchars($langText['employees'] ?? 'Employees', ENT_QUOTES) ?>: <?= $project['employee_count'] ?> |
            <?= htmlspecialchars($langText['progress'] ?? 'Progress', ENT_QUOTES) ?>: <?= $pr ?>%
          </p>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
