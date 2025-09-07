<?php
// app/views/dashboard/index.php - COMPLETO COM CORREÇÕES

require_once __DIR__.'/../layout/header.php';
require_once __DIR__.'/../../models/WorkLogModel.php';
require_once __DIR__.'/../../models/TimeEntryModel.php';
require_once __DIR__.'/../../models/Employees.php';

global $pdo;
$baseUrl = BASE_URL;
$userName = $_SESSION['user']['name'] ?? '';

// Função de cálculo de variação percentual
function pctChange(float $cur, float $prev): float
{
    if ($prev === 0.0) {
        return $cur > 0.0 ? 100.0 : 0.0;
    }

    return round((($cur - $prev) / $prev) * 100, 1);
}

// FUNÇÃO PARA CALCULAR HORAS COM FILTROS (CORRIGIDA)
function getHoursWithFilter(\PDO $pdo, string $whereClause): float
{
    // Horas do sistema antigo (project_work_logs)
    $oldSystemHours = 0;
    try {
        $stmt = $pdo->query("
            SELECT COALESCE(SUM(hours),0) AS total
            FROM project_work_logs
            WHERE {$whereClause}
        ");
        $oldSystemHours = (float) $stmt->fetchColumn();
    } catch (Exception $e) {
        $oldSystemHours = 0;
    }

    // Horas do novo sistema (time_entries)
    $newSystemHours = 0;
    try {
        $stmt = $pdo->query("
            SELECT COALESCE(SUM(total_hours),0) AS total
            FROM time_entries
            WHERE {$whereClause}
        ");
        $newSystemHours = (float) $stmt->fetchColumn();
    } catch (Exception $e) {
        $newSystemHours = 0;
    }

    return $oldSystemHours + $newSystemHours;
}

// ——— 1) Projetos - CORRIGIDO ———
// Contagem atual (independente da data de criação)
$stmt = $pdo->query("
  SELECT
    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) AS active,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed
  FROM projects
");
$M = $stmt->fetch(PDO::FETCH_ASSOC);
$curActive = (int) ($M['active'] ?? 0);
$curCompleted = (int) ($M['completed'] ?? 0);

// Contagem do mês anterior (projetos criados no mês anterior)
$stmt = $pdo->query("
  SELECT
    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) AS active,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed
  FROM projects
  WHERE MONTH(created_at) = MONTH(CURRENT_DATE - INTERVAL 1 MONTH)
    AND YEAR(created_at) = YEAR(CURRENT_DATE - INTERVAL 1 MONTH)
");
$L = $stmt->fetch(PDO::FETCH_ASSOC);
$prevActive = (int) ($L['active'] ?? 0);
$prevCompleted = (int) ($L['completed'] ?? 0);

// ——— 2) Horas COM SISTEMA COMBINADO (CORRIGIDO) ———
// Horas MÊS ATUAL
$curHours = getHoursWithFilter($pdo, 'MONTH(date) = MONTH(CURRENT_DATE()) AND YEAR(date) = YEAR(CURRENT_DATE())');

// Horas MÊS ANTERIOR
$prevHours = getHoursWithFilter($pdo, 'MONTH(date) = MONTH(CURRENT_DATE - INTERVAL 1 MONTH) AND YEAR(date) = YEAR(CURRENT_DATE - INTERVAL 1 MONTH)');

// Calcula % de variação
$pctActive = pctChange($curActive, $prevActive);
$pctCompleted = pctChange($curCompleted, $prevCompleted);
$pctHours = pctChange($curHours, $prevHours);

// ——— 3) RANKING DE FUNCIONÁRIOS COM SISTEMA COMBINADO (CORRIGIDO) ———
$workLogModel = new WorkLogModel();
$timeEntryModel = new TimeEntryModel();
$employeeModel = new Employee();

$employees = (new Employee())->all();
$allEmployees = $employees; // CORREÇÃO: Adicionar esta linha

$employeeHours = [];

// CORREÇÃO: Simplificar o foreach e remover duplicação
if (!empty($allEmployees) && is_array($allEmployees)) {
    foreach ($allEmployees as $emp) {
        if ($emp['active']) {
            $empId = $emp['id'];

            // Sistema antigo
            $oldSystemHours = $workLogModel->getTotalHoursByEmployee($empId);

            // Sistema novo
            $newSystemHours = 0;
            try {
                $newSystemHours = $timeEntryModel->getTotalHoursByEmployee($empId);
            } catch (Exception $e) {
                $newSystemHours = 0;
            }

            $totalHours = $oldSystemHours + $newSystemHours;

            if ($totalHours > 0) {
                $employeeHours[] = [
                    'id' => $empId,
                    'name' => trim($emp['name'].' '.$emp['last_name']),
                    'total_hours' => $totalHours,
                    'old_system_hours' => $oldSystemHours,
                    'new_system_hours' => $newSystemHours,
                ];
            }
        }
    }
}

// Ordena por total de horas
usort($employeeHours, function ($a, $b) {
    return $b['total_hours'] <=> $a['total_hours'];
});

// ——— 4) Outros dados do dashboard (mantidos) ———
$stmt = $pdo->query("
  SELECT p.*, c.name AS client_name
  FROM projects p
  LEFT JOIN client c ON p.client_id = c.id
  WHERE p.status = 'in_progress'
  ORDER BY p.created_at DESC
  LIMIT 9
");
$activeProjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Clientes
$stmt = $pdo->query('SELECT SUM(active = 1) AS cur_clients FROM client');
$C1 = $stmt->fetch(PDO::FETCH_ASSOC);
$curClients = (int) ($C1['cur_clients'] ?? 0);

$stmt = $pdo->query('
  SELECT SUM(active = 1) AS prev_clients FROM client
  WHERE MONTH(created_at) = MONTH(CURRENT_DATE - INTERVAL 1 MONTH)
    AND YEAR(created_at) = YEAR(CURRENT_DATE - INTERVAL 1 MONTH)
');
$C2 = $stmt->fetch(PDO::FETCH_ASSOC);
$prevClients = (int) ($C2['prev_clients'] ?? 0);
$pctClients = pctChange($curClients, $prevClients);

// Inventário
$stmt = $pdo->query('SELECT COUNT(*) AS cur_skus FROM inventory');
$I1 = $stmt->fetch(PDO::FETCH_ASSOC);
$curSKUs = (int) ($I1['cur_skus'] ?? 0);

$stmt = $pdo->query('
  SELECT COUNT(*) AS prev_skus FROM inventory
  WHERE MONTH(created_at) = MONTH(CURRENT_DATE - INTERVAL 1 MONTH)
    AND YEAR(created_at) = YEAR(CURRENT_DATE - INTERVAL 1 MONTH)
');
$I2 = $stmt->fetch(PDO::FETCH_ASSOC);
$prevSKUs = (int) ($I2['prev_skus'] ?? 0);
$pctSKUs = pctChange($curSKUs, $prevSKUs);

// Tarefas pendentes
$stmt = $pdo->query('SELECT SUM(completed = 0) AS cur_pending FROM tasks');
$T1 = $stmt->fetch(PDO::FETCH_ASSOC);
$curPending = (int) ($T1['cur_pending'] ?? 0);

$stmt = $pdo->query('
  SELECT SUM(completed = 0) AS prev_pending FROM tasks
  WHERE MONTH(created_at) = MONTH(CURRENT_DATE - INTERVAL 1 MONTH)
    AND YEAR(created_at) = YEAR(CURRENT_DATE - INTERVAL 1 MONTH)
');
$T2 = $stmt->fetch(PDO::FETCH_ASSOC);
$prevPending = (int) ($T2['prev_pending'] ?? 0);
$pctPending = pctChange($curPending, $prevPending);

// Lembretes
require_once __DIR__.'/../../models/Calendar.php';
$calModel = new CalendarModel();
$today = date('Y-m-d');
$farFuture = '9999-12-31';
$events = $calModel->getEventsInRange($today, $farFuture);
$upcoming = is_array($events) ? count($events) : 0;

// Membros de equipe
$teamCount = (int) $pdo->query('SELECT COUNT(*) FROM employees WHERE active=1')->fetchColumn();
?>

<div class="pt-20 px-4 py-6 sm:px-8 sm:py-8 ml-0 lg:ml-56">
  <h1 class="text-3xl font-bold mb-6 max-w-full break-words">
    <?= htmlspecialchars($langText['welcome'] ?? 'Bem-vindo', ENT_QUOTES); ?>,
    <?= htmlspecialchars($userName, ENT_QUOTES); ?>!
  </h1>

  <!-- === Cards Principais === -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
    <div class="bg-white p-4 rounded-lg shadow flex flex-col items-center">
      <h3 class="text-lg font-semibold mb-2 text-center">
        <?= htmlspecialchars($langText['active_projects'] ?? 'Projetos Ativos', ENT_QUOTES); ?>
      </h3>
      <p class="text-3xl font-bold"><?= $curActive; ?></p>
      <p class="mt-1 text-sm <?= $pctActive >= 0 ? 'text-green-500' : 'text-red-500'; ?> text-center">
        <?= ($pctActive >= 0 ? '+' : '').$pctActive; ?>%
        <?= htmlspecialchars($langText['vs_last_month'] ?? 'vs mês anterior', ENT_QUOTES); ?>
      </p>
    </div>

    <div class="bg-white p-4 rounded-lg shadow flex flex-col items-center">
      <h3 class="text-lg font-semibold mb-2 text-center">
        <?= htmlspecialchars($langText['total_hours_month'] ?? 'Total de Horas no Mês', ENT_QUOTES); ?>
      </h3>
      <p class="text-3xl font-bold"><?= number_format($curHours, 1, ',', '.'); ?>h</p>
      <p class="mt-1 text-sm <?= $pctHours >= 0 ? 'text-green-500' : 'text-red-500'; ?> text-center">
        <?= ($pctHours >= 0 ? '+' : '').$pctHours; ?>%
        <?= htmlspecialchars($langText['vs_last_month'] ?? 'vs mês anterior', ENT_QUOTES); ?>
      </p>
    </div>

    <div class="bg-white p-4 rounded-lg shadow flex flex-col items-center">
      <h3 class="text-lg font-semibold mb-2 text-center">
        <?= htmlspecialchars($langText['team_members'] ?? 'Funcionários', ENT_QUOTES); ?>
      </h3>
      <p class="text-3xl font-bold"><?= $teamCount; ?></p>
      <p class="mt-1 text-sm text-gray-500 text-center">
        <?= htmlspecialchars($langText['total'] ?? 'Total', ENT_QUOTES); ?>
      </p>
    </div>

    <div class="bg-white p-4 rounded-lg shadow flex flex-col items-center">
      <h3 class="text-lg font-semibold mb-2 text-center">
        <?= htmlspecialchars($langText['completed_projects'] ?? 'Projetos Concluídos', ENT_QUOTES); ?>
      </h3>
      <p class="text-3xl font-bold"><?= $curCompleted; ?></p>
      <p class="mt-1 text-sm <?= $pctCompleted >= 0 ? 'text-green-500' : 'text-red-500'; ?> text-center">
        <?= ($pctCompleted >= 0 ? '+' : '').$pctCompleted; ?>%
        <?= htmlspecialchars($langText['vs_last_month'] ?? 'vs mês anterior', ENT_QUOTES); ?>
      </p>
    </div>
  </div>

  <!-- === Cards Extras === -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
    <div class="bg-white p-4 rounded-lg shadow">
      <h3 class="font-semibold mb-2 text-center">
        <?= htmlspecialchars($langText['active_clients'] ?? 'Clientes Ativos', ENT_QUOTES); ?>
      </h3>
      <p class="text-2xl font-bold text-center"><?= $curClients; ?></p>
      <p class="text-sm <?= $pctClients >= 0 ? 'text-green-500' : 'text-red-500'; ?> text-center">
        <?= ($pctClients >= 0 ? '+' : '').$pctClients; ?>%
        <?= htmlspecialchars($langText['vs_last_month'] ?? 'vs mês anterior', ENT_QUOTES); ?>
      </p>
    </div>

    <div class="bg-white p-4 rounded-lg shadow">
      <h3 class="font-semibold mb-2 text-center">
        <?= htmlspecialchars($langText['inventory_items'] ?? 'Itens Inventário', ENT_QUOTES); ?>
      </h3>
      <p class="text-2xl font-bold text-center"><?= $curSKUs; ?></p>
      <p class="text-sm <?= $pctSKUs >= 0 ? 'text-green-500' : 'text-red-500'; ?> text-center">
        <?= ($pctSKUs >= 0 ? '+' : '').$pctSKUs; ?>%
        <?= htmlspecialchars($langText['vs_last_month'] ?? 'vs mês anterior', ENT_QUOTES); ?>
      </p>
    </div>

    <div class="bg-white p-4 rounded-lg shadow">
      <h3 class="font-semibold mb-2 text-center">
        <?= htmlspecialchars($langText['pending_tasks'] ?? 'Tarefas Pendentes', ENT_QUOTES); ?>
      </h3>
      <p class="text-2xl font-bold text-center"><?= $curPending; ?></p>
      <p class="text-sm <?= $pctPending >= 0 ? 'text-green-500' : 'text-red-500'; ?> text-center">
        <?= ($pctPending >= 0 ? '+' : '').$pctPending; ?>%
        <?= htmlspecialchars($langText['vs_last_month'] ?? 'vs mês anterior', ENT_QUOTES); ?>
      </p>
    </div>

    <div class="bg-white p-4 rounded-lg shadow">
      <h3 class="font-semibold mb-2 text-center">
        <?= htmlspecialchars($langText['upcoming_reminders'] ?? 'Próximos Lembretes', ENT_QUOTES); ?>
      </h3>
      <p class="text-2xl font-bold text-center"><?= $upcoming; ?></p>
      <p class="text-sm text-gray-500 text-center">
        <?= htmlspecialchars($langText['total'] ?? 'Total', ENT_QUOTES); ?>
      </p>
    </div>
  </div>

  <!-- === RANKING DE FUNCIONÁRIOS === -->
  <?php if (!empty($employeeHours)): ?>
  <div class="mb-12">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-2xl font-bold">
        <?= htmlspecialchars($langText['employee_hours_ranking'] ?? 'Ranking de Horas - Funcionários', ENT_QUOTES); ?>
      </h2>
      <a href="<?= BASE_URL; ?>/employees" class="text-blue-500 hover:text-blue-700">
        <?= htmlspecialchars($langText['manage_employees'] ?? 'Gerenciar Funcionários', ENT_QUOTES); ?>
      </a>
    </div>
    
    <div class="bg-white rounded-lg shadow overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                <?= htmlspecialchars($langText['position'] ?? 'Posição', ENT_QUOTES); ?>
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                <?= htmlspecialchars($langText['employee'] ?? 'Funcionário', ENT_QUOTES); ?>
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                <?= htmlspecialchars($langText['total_hours'] ?? 'Total de Horas', ENT_QUOTES); ?>
              </th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                <?= htmlspecialchars($langText['actions'] ?? 'Ações', ENT_QUOTES); ?>
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <?php foreach ($employeeHours as $index => $emp): ?>
            <tr class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-medium
                              <?= $index < 3 ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800'; ?>">
                    <?= $index + 1; ?>
                  </span>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($emp['name'], ENT_QUOTES); ?></div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900">
                <span class="font-semibold"><?= number_format($emp['total_hours'], 1, ',', '.'); ?>h</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                <a href="<?= BASE_URL; ?>/employees#emp-<?= $emp['id']; ?>" 
                   class="text-blue-600 hover:text-blue-800 font-medium">
                  <?= htmlspecialchars($langText['view_details'] ?? 'Ver Detalhes', ENT_QUOTES); ?>
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- === PROJETOS ATIVOS === -->
  <div class="mb-12">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-2xl font-bold">
        <?= htmlspecialchars($langText['active_projects'] ?? 'Projetos Ativos', ENT_QUOTES); ?>
      </h2>
      <a href="<?= BASE_URL; ?>/projects" class="text-blue-500 hover:text-blue-700">
        <?= htmlspecialchars($langText['view_all'] ?? 'Ver Todos', ENT_QUOTES); ?>
      </a>
    </div>
    
    <?php if (empty($activeProjects)): ?>
      <div class="bg-white rounded-lg shadow p-8 text-center">
        <p class="text-gray-500"><?= htmlspecialchars($langText['no_active_projects'] ?? 'Nenhum projeto ativo no momento', ENT_QUOTES); ?></p>
      </div>
    <?php else: ?>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($activeProjects as $project): ?>
          <div class="bg-white rounded-lg shadow p-6">
            <h3 class="font-semibold text-lg mb-2"><?= htmlspecialchars($project['name'], ENT_QUOTES); ?></h3>
            <p class="text-gray-600 mb-4"><?= htmlspecialchars($project['client_name'] ?? 'Cliente não definido', ENT_QUOTES); ?></p>
            
            <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
              <div class="bg-blue-600 h-2 rounded-full" style="width: <?= min(100, max(0, (int) ($project['progress'] ?? 0))); ?>%"></div>
            </div>
            
            <div class="flex justify-between text-sm text-gray-500">
              <span><?= htmlspecialchars($project['location'] ?? 'Local não definido', ENT_QUOTES); ?></span>
              <span><?= (int) ($project['progress'] ?? 0); ?>%</span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__.'/../layout/footer.php'; ?>