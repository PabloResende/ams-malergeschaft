<?php
// app/views/dashboard/index.php - VERSÃO ORIGINAL COM APENAS CORREÇÕES MÍNIMAS

function pctChange($atual, $anterior)
{
    if ($anterior == 0) {
        return 0;
    }

    return round((($atual - $anterior) / $anterior) * 100, 1);
}

require_once __DIR__.'/../layout/header.php';

// CORREÇÃO: Definir $userName no início
$userName = $_SESSION['user']['name'] ?? 'Usuário';

// ——— 1) Projetos ativos/concluídos ———
$stmt = $pdo->query("
    SELECT COUNT(*) AS cur_active 
    FROM projects 
    WHERE status = 'active' OR status = 'in_progress'
");
$P1 = $stmt->fetch(PDO::FETCH_ASSOC);
$curActive = (int) ($P1['cur_active'] ?? 0);

$stmt = $pdo->query("
    SELECT COUNT(*) AS prev_active 
    FROM projects 
    WHERE (status = 'active' OR status = 'in_progress')
      AND MONTH(created_at) = MONTH(CURRENT_DATE - INTERVAL 1 MONTH)
      AND YEAR(created_at) = YEAR(CURRENT_DATE - INTERVAL 1 MONTH)
");
$P2 = $stmt->fetch(PDO::FETCH_ASSOC);
$prevActive = (int) ($P2['prev_active'] ?? 0);
$pctActive = pctChange($curActive, $prevActive);

$stmt = $pdo->query("
    SELECT COUNT(*) AS cur_completed 
    FROM projects 
    WHERE status = 'completed'
");
$P3 = $stmt->fetch(PDO::FETCH_ASSOC);
$curCompleted = (int) ($P3['cur_completed'] ?? 0);

$stmt = $pdo->query("
    SELECT COUNT(*) AS prev_completed 
    FROM projects 
    WHERE status = 'completed'
      AND MONTH(created_at) = MONTH(CURRENT_DATE - INTERVAL 1 MONTH)
      AND YEAR(created_at) = YEAR(CURRENT_DATE - INTERVAL 1 MONTH)
");
$P4 = $stmt->fetch(PDO::FETCH_ASSOC);
$prevCompleted = (int) ($P4['prev_completed'] ?? 0);
$pctCompleted = pctChange($curCompleted, $prevCompleted);

// ——— 2) Horas de trabalho (CORRIGIDO: usar time_entries + project_work_logs) ———
$curHours = 0;
$prevHours = 0;

// Sistema novo: time_entries
try {
    $stmt = $pdo->query('
        SELECT COALESCE(SUM(total_hours), 0) AS cur_hours 
        FROM time_entries 
        WHERE MONTH(created_at) = MONTH(CURRENT_DATE) 
          AND YEAR(created_at) = YEAR(CURRENT_DATE)
    ');
    $H1 = $stmt->fetch(PDO::FETCH_ASSOC);
    $curHours += (float) ($H1['cur_hours'] ?? 0);

    $stmt = $pdo->query('
        SELECT COALESCE(SUM(total_hours), 0) AS prev_hours 
        FROM time_entries 
        WHERE MONTH(created_at) = MONTH(CURRENT_DATE - INTERVAL 1 MONTH) 
          AND YEAR(created_at) = YEAR(CURRENT_DATE - INTERVAL 1 MONTH)
    ');
    $H2 = $stmt->fetch(PDO::FETCH_ASSOC);
    $prevHours += (float) ($H2['prev_hours'] ?? 0);
} catch (Exception $e) {
    // Tabela time_entries pode não existir
}

// Sistema antigo: project_work_logs
try {
    $stmt = $pdo->query('
        SELECT COALESCE(SUM(hours), 0) AS cur_hours 
        FROM project_work_logs 
        WHERE MONTH(created_at) = MONTH(CURRENT_DATE) 
          AND YEAR(created_at) = YEAR(CURRENT_DATE)
    ');
    $H3 = $stmt->fetch(PDO::FETCH_ASSOC);
    $curHours += (float) ($H3['cur_hours'] ?? 0);

    $stmt = $pdo->query('
        SELECT COALESCE(SUM(hours), 0) AS prev_hours 
        FROM project_work_logs 
        WHERE MONTH(created_at) = MONTH(CURRENT_DATE - INTERVAL 1 MONTH) 
          AND YEAR(created_at) = YEAR(CURRENT_DATE - INTERVAL 1 MONTH)
    ');
    $H4 = $stmt->fetch(PDO::FETCH_ASSOC);
    $prevHours += (float) ($H4['prev_hours'] ?? 0);
} catch (Exception $e) {
    // Tabela project_work_logs pode não existir
}

$pctHours = pctChange($curHours, $prevHours);

// ——— 3) Ranking funcionários (CORRIGIDO: usar 'name' e 'last_name') ———
$employeeHours = [];

// Busca funcionários e suas horas de uma vez só
try {
    $sql = '
        SELECT 
            e.id,
            e.name,
            e.last_name,
            COALESCE(
                (SELECT SUM(total_hours) FROM time_entries te 
                 WHERE te.employee_id = e.id 
                   AND MONTH(te.created_at) = MONTH(CURRENT_DATE) 
                   AND YEAR(te.created_at) = YEAR(CURRENT_DATE)
                ), 0
            ) + 
            COALESCE(
                (SELECT SUM(hours) FROM project_work_logs pwl 
                 WHERE pwl.employee_id = e.id 
                   AND MONTH(pwl.created_at) = MONTH(CURRENT_DATE) 
                   AND YEAR(pwl.created_at) = YEAR(CURRENT_DATE)
                ), 0
            ) AS total_hours
        FROM employees e
        WHERE e.active = 1
        HAVING total_hours > 0
        ORDER BY total_hours DESC
        LIMIT 10
    ';

    $stmt = $pdo->query($sql);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($results as $emp) {
        $employeeHours[] = [
            'id' => $emp['id'],
            'name' => trim(($emp['name'] ?? '').' '.($emp['last_name'] ?? '')),
            'total_hours' => (float) ($emp['total_hours'] ?? 0),
        ];
    }
} catch (Exception $e) {
    // Se der erro na query complexa, usa método simples
    error_log('Erro no ranking de funcionários: '.$e->getMessage());
    $employeeHours = [];
}

// ——— 4) Outros dados do dashboard (CORRIGIDO: usar 'client' não 'clients') ———
// Removido: consulta de projetos ativos (não precisa mais)

// Clientes (CORRIGIDO: usar tabela 'client')
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
try {
    require_once __DIR__.'/../../models/Calendar.php';
    $calModel = new CalendarModel();
    $today = date('Y-m-d');
    $farFuture = '9999-12-31';
    $events = $calModel->getEventsInRange($today, $farFuture);
    $upcoming = is_array($events) ? count($events) : 0;
} catch (Exception $e) {
    $upcoming = 0;
}

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

  <!-- === Ranking de Funcionários === -->
  <?php if (!empty($employeeHours)): ?>
  <div class="mb-12">
    <div class="bg-white rounded-lg shadow overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold">
          <?= htmlspecialchars($langText['employee_ranking'] ?? 'Ranking de Funcionários (Horas no Mês)', ENT_QUOTES); ?>
        </h3>
      </div>
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
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Dashboard carregado com sucesso');
    console.log('Total de horas:', <?= $curHours; ?>);
    console.log('Funcionários no ranking:', <?= count($employeeHours); ?>);
});
</script>

<?php require_once __DIR__.'/../layout/footer.php'; ?>