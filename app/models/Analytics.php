// app/views/analytics/index.php
<?php
require_once __DIR__ . '/../../config/Database.php';

$pdo = Database::connect();
$years = $pdo
    ->query("SELECT DISTINCT YEAR(start_date) AS year FROM projects ORDER BY year DESC")
    ->fetchAll(PDO::FETCH_COLUMN);
$currentYear = date('Y');
?>

<div class="ml-64 p-6 min-h-screen">
  <h1 class="text-2xl font-bold mb-6">Painel de Análises</h1>

  <!-- Filtros -->
  <form id="filterForm" class="flex flex-wrap gap-4 mb-6">
    <select id="filterYear" name="year" class="border p-2 rounded">
      <?php foreach ($years as $year): ?>
        <option value="<?= $year ?>" <?= $year == $currentYear ? 'selected' : '' ?>>
          <?= $year ?>
        </option>
      <?php endforeach; ?>
    </select>
    <select id="filterQuarter" name="quarter" class="border p-2 rounded">
      <option value="">Todos os Trimestres</option>
      <option value="1">1º Trimestre</option>
      <option value="2">2º Trimestre</option>
      <option value="3">3º Trimestre</option>
      <option value="4">4º Trimestre</option>
    </select>
    <select id="filterSemester" name="semester" class="border p-2 rounded">
      <option value="">Todos os Semestres</option>
      <option value="1">1º Semestre</option>
      <option value="2">2º Semestre</option>
    </select>
    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">
      Filtrar
    </button>
  </form>

  <!-- Indicadores -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
    <div class="bg-white shadow rounded p-4">
      <div class="text-sm font-medium text-gray-500">Materiais Usados</div>
      <div id="totalMaterials" class="text-2xl font-bold text-gray-800 mt-1">0</div>
    </div>
    <div class="bg-white shadow rounded p-4">
      <div class="text-sm font-medium text-gray-500">Horas Trabalhadas</div>
      <div id="totalHours" class="text-2xl font-bold text-gray-800 mt-1">0</div>
    </div>
    <div class="bg-white shadow rounded p-4">
      <div class="text-sm font-medium text-gray-500">
        Orçamento Planejado vs Usado
      </div>
      <div class="relative w-full h-48">
        <canvas id="chartBudget" class="absolute inset-0 w-full h-full"></canvas>
      </div>
    </div>
  </div>

  <!-- Gráficos -->
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Criados -->
    <div class="bg-white shadow rounded p-4">
      <h2 class="text-lg font-semibold mb-2">Projetos Criados por Mês</h2>
      <div class="relative w-full h-48">
        <canvas id="chartCreated" class="absolute inset-0 w-full h-full"></canvas>
      </div>
    </div>

    <!-- Finalizados -->
    <div class="bg-white shadow rounded p-4">
      <h2 class="text-lg font-semibold mb-2">Projetos Finalizados por Mês</h2>
      <div class="relative w-full h-48">
        <canvas id="chartCompleted" class="absolute inset-0 w-full h-full"></canvas>
      </div>
    </div>

    <!-- Comparação -->
    <div class="bg-white shadow rounded p-4 lg:col-span-2">
      <h2 class="text-lg font-semibold mb-2">Criados vs Finalizados</h2>
      <div class="relative w-full h-48">
        <canvas id="chartComparison" class="absolute inset-0 w-full h-full"></canvas>
      </div>
    </div>

    <!-- Status -->
    <div class="bg-white shadow rounded p-4 lg:col-span-2">
      <h2 class="text-lg font-semibold mb-2">Status dos Projetos</h2>
      <div class="relative w-full h-48">
        <canvas id="chartStatus" class="absolute inset-0 w-full h-full"></canvas>
      </div>
    </div>
  </div>

  <!-- Botões -->
  <div class="flex gap-4 mt-4">
    <a href="#" id="btnExportPdf" class="bg-gray-700 text-white px-4 py-2 rounded">
      Exportar PDF
    </a>
    <a href="#" id="btnExportExcel" class="bg-gray-700 text-white px-4 py-2 rounded">
      Exportar Excel
    </a>
    <button id="btnSendEmail" class="bg-gray-600 text-white px-4 py-2 rounded">
      Enviar por E-mail
    </button>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0"></script>
<script src="/ams-malergeschaft/public/js/analytics.js"></script>
