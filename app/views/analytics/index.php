<?php
// app/views/analytics/index.php

require_once __DIR__ . '/../layout/header.php';

$baseUrl = BASE_URL;

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
<div class="pt-20 px-4 sm:px-6 lg:px-8 ml-0 lg:ml-64 pb-8">
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
    <h1 class="text-2xl font-bold mb-4 sm:mb-0"><?= htmlspecialchars($langText['analytics'] ?? 'Analytics Dashboard', ENT_QUOTES) ?></h1>
    <button id="openFilterBtn"
            class="px-4 py-2 bg-blue-600 text-white rounded-full hover:bg-blue-700 transition w-full sm:w-auto text-center">
      <?= htmlspecialchars($langText['filter'] ?? 'Filter', ENT_QUOTES) ?>
    </button>
  </div>

  <!-- Filter Modal -->
  <div id="filterModal"
       class="fixed inset-0 hidden items-center justify-center bg-black bg-opacity-50 z-50 px-4 sm:px-6">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md sm:max-w-3xl p-4 sm:p-6 lg:p-8 max-h-[85vh] overflow-y-auto relative">
      <button id="closeFilterBtn"
              class="absolute top-4 right-4 text-gray-500 hover:text-gray-700">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
             viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
      <h2 class="text-xl font-semibold mb-4"><?= htmlspecialchars($langText['filter'] ?? 'Filter', ENT_QUOTES) ?></h2>
      <form id="filterForm" class="flex flex-wrap gap-4 mb-4">
        <select id="filterYear" name="year"
                class="w-full sm:w-auto border p-2 rounded-lg">
          <?php foreach ($years as $y): ?>
            <option value="<?= $y ?>" <?= $y === $currentYear ? 'selected' : '' ?>>
              <?= $y ?>
            </option>
          <?php endforeach; ?>
        </select>
        <select id="filterQuarter" name="quarter"
                class="w-full sm:w-auto border p-2 rounded-lg">
          <option value=""><?= htmlspecialchars($langText['all_quarters']   ?? 'All Quarters', ENT_QUOTES) ?></option>
          <option value="1">Q1</option>
          <option value="2">Q2</option>
          <option value="3">Q3</option>
          <option value="4">Q4</option>
        </select>
        <select id="filterSemester" name="semester"
                class="w-full sm:w-auto border p-2 rounded-lg">
          <option value=""><?= htmlspecialchars($langText['all_semesters'] ?? 'All Semesters', ENT_QUOTES) ?></option>
          <option value="1">S1</option>
          <option value="2">S2</option>
        </select>
        <button type="submit"
                class="mt-2 sm:mt-0 ml-auto px-4 sm:px-6 py-2 bg-blue-600 text-white rounded-full hover:bg-blue-700 transition">
          <?= htmlspecialchars($langText['apply'] ?? 'Apply', ENT_QUOTES) ?>
        </button>
      </form>
    </div>
  </div>

  <!-- Charts -->
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white shadow rounded-lg p-4 sm:p-6">
      <h2 class="text-lg font-semibold mb-2"><?= htmlspecialchars($langText['projects_created'] ?? 'Projects Created by Month', ENT_QUOTES) ?></h2>
      <div class="w-full h-48 sm:h-64">
        <canvas id="chartCreated" class="w-full h-full"></canvas>
      </div>
    </div>
    <div class="bg-white shadow rounded-lg p-4 sm:p-6">
      <h2 class="text-lg font-semibold mb-2"><?= htmlspecialchars($langText['projects_completed'] ?? 'Projects Completed by Month', ENT_QUOTES) ?></h2>
      <div class="w-full h-48 sm:h-64">
        <canvas id="chartCompleted" class="w-full h-full"></canvas>
      </div>
    </div>
    <div class="bg-white shadow rounded-lg p-4 sm:p-6 lg:col-span-2">
      <h2 class="text-lg font-semibold mb-2"><?= htmlspecialchars($langText['created_vs_completed'] ?? 'Created vs Completed', ENT_QUOTES) ?></h2>
      <div class="w-full h-48 sm:h-64">
        <canvas id="chartComparison" class="w-full h-full"></canvas>
      </div>
    </div>
    <div class="bg-white shadow rounded-lg p-4 sm:p-6 lg:col-span-2">
      <h2 class="text-lg font-semibold mb-2"><?= htmlspecialchars($langText['project_status'] ?? 'Project Status', ENT_QUOTES) ?></h2>
      <div class="w-full h-48 sm:h-64">
        <canvas id="chartStatus" class="w-full h-full"></canvas>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0"></script>
<script>
  document.getElementById('openFilterBtn').addEventListener('click', () => {
    const modal = document.getElementById('filterModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
  });
  document.getElementById('closeFilterBtn').addEventListener('click', () => {
    const modal = document.getElementById('filterModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
  });
  window.apiBase  = '<?= $baseUrl ?>/analytics';
  window.langText = <?= json_encode($langText, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_QUOT|JSON_HEX_APOS) ?>;
  window.baseUrl  = <?= json_encode(BASE_URL, JSON_UNESCAPED_SLASHES) ?>;
</script>
<script src="<?= asset('js/analytics.js') ?>" defer></script>
<?php require __DIR__ . '/../layout/footer.php'; ?>
