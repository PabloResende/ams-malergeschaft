<?php
// app/views/analytics/index.php

require_once __DIR__ . '/../layout/header.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (!isset($_SESSION['user'])) {
    header("Location: {$baseUrl}/login");
    exit;
}

$currentYear = (int)date('Y');
$years = [];
for ($i = 0; $i <= 10; $i++) {
    $years[] = $currentYear - $i;
}
?>
<div class="content-wrapper ml-64 p-6 min-h-screen">
  <h1 class="text-2xl font-bold mb-6"><?= $langText['analytics'] ?? 'Analytics Dashboard' ?></h1>

  <!-- filtros -->
  <form id="filterForm" class="flex flex-wrap gap-4 mb-8">
    <select id="filterYear" name="year" class="border p-2 rounded">
      <?php foreach ($years as $y): ?>
        <option value="<?= $y ?>" <?= $y === $currentYear ? 'selected' : '' ?>>
          <?= $y ?>
        </option>
      <?php endforeach; ?>
    </select>
    <select id="filterQuarter" name="quarter" class="border p-2 rounded">
      <option value=""><?= $langText['all_quarters']   ?? 'All Quarters' ?></option>
      <option value="1">Q1</option>
      <option value="2">Q2</option>
      <option value="3">Q3</option>
      <option value="4">Q4</option>
    </select>
    <select id="filterSemester" name="semester" class="border p-2 rounded">
      <option value=""><?= $langText['all_semesters'] ?? 'All Semesters' ?></option>
      <option value="1">S1</option>
      <option value="2">S2</option>
    </select>
    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">
      <?= $langText['filter'] ?? 'Filter' ?>
    </button>
  </form>

  <!-- Somente os 4 gráficos finais -->
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="bg-white shadow rounded p-4">
      <h2 class="text-lg font-semibold mb-2">
        <?= $langText['projects_created'] ?? 'Projects Created by Month' ?>
      </h2>
      <div class="w-full h-48">
        <canvas id="chartCreated" class="w-full h-full"></canvas>
      </div>
    </div>
    <div class="bg-white shadow rounded p-4">
      <h2 class="text-lg font-semibold mb-2">
        <?= $langText['projects_completed'] ?? 'Projects Completed by Month' ?>
      </h2>
      <div class="w-full h-48">
        <canvas id="chartCompleted" class="w-full h-full"></canvas>
      </div>
    </div>
    <div class="bg-white shadow rounded p-4 lg:col-span-2">
      <h2 class="text-lg font-semibold mb-2">
        <?= $langText['created_vs_completed'] ?? 'Created vs Completed' ?>
      </h2>
      <div class="w-full h-48">
        <canvas id="chartComparison" class="w-full h-full"></canvas>
      </div>
    </div>
    <div class="bg-white shadow rounded p-4 lg:col-span-2">
      <h2 class="text-lg font-semibold mb-2">
        <?= $langText['project_status'] ?? 'Project Status' ?>
      </h2>
      <div class="w-full h-48">
        <canvas id="chartStatus" class="w-full h-full"></canvas>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0"></script>
<script>
  window.apiBase  = '<?= $baseUrl ?>/analytics';
  window.langText = <?= json_encode($langText, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_QUOT|JSON_HEX_APOS) ?>;
</script>
<script src="<?= $baseUrl ?>/js/analytics.js"></script>
