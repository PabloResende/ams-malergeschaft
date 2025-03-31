<?php
require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../../../config/Database.php';

$pdo = Database::connect();

// Definição do período padrão (mês atual)
$periodo = $_GET['periodo'] ?? 'mes';

// Obter o intervalo de datas com base no filtro
switch ($periodo) {
    case 'ano':
        $startDate = date('Y-01-01');
        $endDate = date('Y-12-31');
        $previousStart = date('Y-m-d', strtotime('-1 year', strtotime($startDate)));
        $previousEnd = date('Y-m-d', strtotime('-1 year', strtotime($endDate)));
        break;
    case 'semestre':
        $month = date('n');
        $semester = ($month <= 6) ? '1' : '2';
        $startDate = date('Y-' . ($semester == '1' ? '01' : '07') . '-01');
        $endDate = date('Y-' . ($semester == '1' ? '06' : '12') . '-30');
        $previousStart = date('Y-m-d', strtotime('-6 months', strtotime($startDate)));
        $previousEnd = date('Y-m-d', strtotime('-6 months', strtotime($endDate)));
        break;
    case 'trimestre':
        $currentQuarter = ceil(date('n') / 3);
        $startDate = date('Y-m-d', strtotime('first day of -' . (($currentQuarter - 1) * 3) . ' months'));
        $endDate = date('Y-m-d', strtotime('last day of -' . (($currentQuarter - 1) * 3) . ' months +2 months'));
        $previousStart = date('Y-m-d', strtotime('-3 months', strtotime($startDate)));
        $previousEnd = date('Y-m-d', strtotime('-3 months', strtotime($endDate)));
        break;
    default: // Mês atual
        $startDate = date('Y-m-01');
        $endDate = date('Y-m-t');
        $previousStart = date('Y-m-01', strtotime('-1 month'));
        $previousEnd = date('Y-m-t', strtotime('-1 month'));
}

// Consultar métricas do período selecionado
$stmt = $pdo->prepare("
    SELECT 
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) AS active_projects,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_projects,
        SUM(total_hours) AS total_hours
    FROM projects
    WHERE created_at BETWEEN :startDate AND :endDate
");
$stmt->execute(['startDate' => $startDate, 'endDate' => $endDate]);
$metrics = $stmt->fetch(PDO::FETCH_ASSOC);

$activeProjects = $metrics['active_projects'] ?? 0;
$completedProjects = $metrics['completed_projects'] ?? 0;
$totalHours = $metrics['total_hours'] ?? 0;

// Comparação com período anterior
$stmt = $pdo->prepare("
    SELECT 
        SUM(total_hours) AS previous_total_hours,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) AS previous_active_projects,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS previous_completed_projects
    FROM projects
    WHERE created_at BETWEEN :previousStart AND :previousEnd
");
$stmt->execute(['previousStart' => $previousStart, 'previousEnd' => $previousEnd]);
$previousMetrics = $stmt->fetch(PDO::FETCH_ASSOC);

$previousHours = $previousMetrics['previous_total_hours'] ?? 0;
$previousActive = $previousMetrics['previous_active_projects'] ?? 0;
$previousCompleted = $previousMetrics['previous_completed_projects'] ?? 0;
?>

<div class="ml-56 pt-20 p-8">
  <h2 class="text-2xl font-bold mb-4">📊 Dashboard de Projetos</h2>

  <!-- Filtros -->
  <div class="mb-6 flex space-x-4">
    <?php foreach (['mes' => 'Mês', 'trimestre' => 'Trimestre', 'semestre' => 'Semestre', 'ano' => 'Ano'] as $key => $label): ?>
      <a href="?periodo=<?= $key ?>" class="px-4 py-2 rounded-lg <?= $periodo == $key ? 'bg-blue-500 text-white' : 'bg-gray-200' ?>">
        <?= $label ?>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- Cards de Métricas -->
  <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
    <div class="bg-white p-4 rounded-lg shadow flex flex-col items-center">
      <h3 class="text-lg font-semibold mb-2">Projetos Ativos</h3>
      <p class="text-3xl font-bold"><?= $activeProjects ?></p>
      <p class="text-sm <?= $activeProjects >= $previousActive ? 'text-green-500' : 'text-red-500' ?>">
        <?= ($activeProjects - $previousActive) ?> em relação ao período anterior
      </p>
    </div>
    <div class="bg-white p-4 rounded-lg shadow flex flex-col items-center">
      <h3 class="text-lg font-semibold mb-2">Total de Horas</h3>
      <p class="text-3xl font-bold"><?= $totalHours ?>h</p>
      <p class="text-sm <?= $totalHours >= $previousHours ? 'text-green-500' : 'text-red-500' ?>">
        <?= ($totalHours - $previousHours) ?>h em relação ao período anterior
      </p>
    </div>
    <div class="bg-white p-4 rounded-lg shadow flex flex-col items-center">
      <h3 class="text-lg font-semibold mb-2">Projetos Concluídos</h3>
      <p class="text-3xl font-bold"><?= $completedProjects ?></p>
      <p class="text-sm <?= $completedProjects >= $previousCompleted ? 'text-green-500' : 'text-red-500' ?>">
        <?= ($completedProjects - $previousCompleted) ?> em relação ao período anterior
      </p>
    </div>
  </div>

  <div class="bg-white p-6 rounded-lg shadow mt-8">
  <h3 class="text-xl font-semibold mb-4">📊 Comparação de Dados</h3>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Gráfico de Pizza -->
    <div class="flex flex-col items-center">
      <h4 class="text-lg font-semibold mb-2">Projetos Ativos x Concluídos</h4>
      <canvas id="projectsPieChart" class="max-w-[200px]"></canvas>
    </div>

    <!-- Gráfico de Barras com Filtros -->
    <div class="flex flex-col">
      <h4 class="text-lg font-semibold mb-2">Selecione o Dado para Comparação</h4>
      <select id="dataFilter" class="p-2 border rounded">
        <option value="active">Projetos Ativos</option>
        <option value="completed">Projetos Concluídos</option>
        <option value="hours">Horas Trabalhadas</option>
      </select>
      <canvas id="comparisonChart" class="mt-4"></canvas>
    </div>
  </div>
</div>
<!-- Scripts Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  // Gráfico de Pizza
  const pieCtx = document.getElementById('projectsPieChart').getContext('2d');
  new Chart(pieCtx, {
    type: 'pie',
    data: {
      labels: ['Ativos', 'Concluídos'],
      datasets: [{
        data: [<?= $activeProjects ?>, <?= $completedProjects ?>],
        backgroundColor: ['#3B82F6', '#10B981']
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false
    }
  });

  // Gráfico de Barras Dinâmico
  const barCtx = document.getElementById('comparisonChart').getContext('2d');
  let comparisonChart = new Chart(barCtx, {
    type: 'bar',
    data: {
      labels: ['Atual', 'Anterior'],
      datasets: [{
        label: 'Projetos Ativos',
        data: [<?= $activeProjects ?>, <?= $previousActive ?>],
        backgroundColor: '#3B82F6'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: { beginAtZero: true }
      }
    }
  });

  // Atualizar gráfico ao mudar filtro
  document.getElementById('dataFilter').addEventListener('change', function () {
    let newData;
    let newLabel;
    let newColor;

    switch (this.value) {
      case 'completed':
        newLabel = 'Projetos Concluídos';
        newData = [<?= $completedProjects ?>, <?= $previousCompleted ?>];
        newColor = '#10B981';
        break;
      case 'hours':
        newLabel = 'Horas Trabalhadas';
        newData = [<?= $totalHours ?>, <?= $previousHours ?>];
        newColor = '#F59E0B';
        break;
      default:
        newLabel = 'Projetos Ativos';
        newData = [<?= $activeProjects ?>, <?= $previousActive ?>];
        newColor = '#3B82F6';
    }

    comparisonChart.data.datasets[0].label = newLabel;
    comparisonChart.data.datasets[0].data = newData;
    comparisonChart.data.datasets[0].backgroundColor = newColor;
    comparisonChart.update();
  });
</script>