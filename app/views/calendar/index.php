<?php
// app/views/calendar/index.php

require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../../../config/database.php';

// Dados do calendário
$baseUrl = BASE_URL;
$stmt = $pdo->query("SELECT id, name, end_date FROM projects WHERE end_date IS NOT NULL");
$projectEvents = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $projectEvents[] = [
        'id'    => $row['id'],
        'title' => $row['name'],
        'start' => $row['end_date'],
        'type'  => 'projeto',
        'color' => '#38a169',
    ];
}
$eventsJson = json_encode($projectEvents);

// Força arrays válidos para meses e dias da semana, mesmo se a tradução vier como string
$monthNames = (isset($langText['month_names']) && is_array($langText['month_names']))
    ? $langText['month_names']
    : ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];

$weekdays = (isset($langText['weekdays']) && is_array($langText['weekdays']))
    ? $langText['weekdays']
    : ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];

// Locale da página
$locale = $langText['locale'] ?? 'pt-BR';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($locale, ENT_QUOTES) ?>">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($langText['calendar_title'] ?? 'Calendário do Ano', ENT_QUOTES) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    table { width:100%; table-layout:fixed; border-collapse:collapse; }
    td { overflow:hidden; }
    #tooltip {
      position:fixed;
      background:rgba(0,0,0,0.75);
      color:#fff;
      padding:.5rem;
      border-radius:.25rem;
      font-size:.75rem;
      white-space:nowrap;
      pointer-events:none;
      display:none;
      z-index:1000;
    }
  </style>
</head>
<body class="bg-gray-50">

  <script>
    window.baseUrl = <?= json_encode($baseUrl, JSON_UNESCAPED_SLASHES) ?>;
    window.langText = {
      yearLabel:   <?= json_encode($langText['year'] ?? 'Ano', JSON_UNESCAPED_SLASHES) ?>,
      addReminder: <?= json_encode($langText['add_reminder'] ?? '+ Adicionar Lembrete', JSON_UNESCAPED_SLASHES) ?>,
      modalTitle:  <?= json_encode($langText['add_reminder'] ?? 'Adicionar Lembrete', JSON_UNESCAPED_SLASHES) ?>,
      titleLabel:  <?= json_encode($langText['title'] ?? 'Título', JSON_UNESCAPED_SLASHES) ?>,
      dateLabel:   <?= json_encode($langText['date'] ?? 'Data', JSON_UNESCAPED_SLASHES) ?>,
      colorLabel:  <?= json_encode($langText['color'] ?? 'Cor', JSON_UNESCAPED_SLASHES) ?>,
      cancel:      <?= json_encode($langText['cancel'] ?? 'Cancelar', JSON_UNESCAPED_SLASHES) ?>,
      save:        <?= json_encode($langText['save'] ?? 'Salvar', JSON_UNESCAPED_SLASHES) ?>,
      eventsOn:    <?= json_encode($langText['events_on'] ?? 'Eventos em', JSON_UNESCAPED_SLASHES) ?>,
      monthNames:  <?= json_encode($monthNames) ?>,
      weekdays:    <?= json_encode($weekdays) ?>
    };
  </script>

  <script id="serverEventsData" type="application/json">
    <?= $eventsJson ?>
  </script>

  <main class="ml-0 sm:ml-56 pt-20 px-4 sm:px-6 min-h-screen flex flex-col">
    <div class="container mx-auto max-w-7xl flex flex-col gap-6">

      <!-- Filtro de Ano e Botão -->
      <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <div class="flex-1">
          <label for="yearSelector" class="block text-sm font-medium text-gray-700">
            <?= htmlspecialchars($langText['year'] ?? 'Ano', ENT_QUOTES) ?>
          </label>
          <select id="yearSelector"
                  class="mt-1 block w-full sm:w-1/3 rounded-lg border-gray-300 shadow-sm focus:ring-blue-500">
            <?php for ($year = 2025; $year <= 2030; $year++): ?>
              <option value="<?= $year ?>" <?= ($year == date("Y") ? "selected" : "") ?>>
                <?= $year ?>
              </option>
            <?php endfor; ?>
          </select>
        </div>
        <button id="addReminderBtn"
                class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded-lg shadow transition duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
          <?= htmlspecialchars($langText['add_reminder'] ?? '+ Adicionar Lembrete', ENT_QUOTES) ?>
        </button>
      </div>

      <!-- Grid de Meses -->
      <div id="calendarsGrid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 flex-1"></div>
    </div>
  </main>

  <div id="tooltip"></div>

  <!-- Modal: Adicionar Lembrete -->
  <div id="reminderModal"
       class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm hidden z-50">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 max-h-[90vh] overflow-y-auto p-6">
      <h2 class="text-2xl font-semibold mb-4">
        <?= htmlspecialchars($langText['add_reminder'] ?? 'Adicionar Lembrete', ENT_QUOTES) ?>
      </h2>
      <form id="reminderForm" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">
            <?= htmlspecialchars($langText['title'] ?? 'Título', ENT_QUOTES) ?>
          </label>
          <input type="text" id="reminderTitle" name="title"
                 class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500"
                 required>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">
            <?= htmlspecialchars($langText['date'] ?? 'Data', ENT_QUOTES) ?>
          </label>
          <input type="date" id="reminderDate" name="reminder_date"
                 class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500"
                 required>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">
            <?= htmlspecialchars($langText['color'] ?? 'Cor', ENT_QUOTES) ?>
          </label>
          <input type="color" id="reminderColor" name="color" value="#38a169"
                 class="mt-1 block w-16 h-10 rounded-lg border-gray-300 shadow-sm focus:ring-blue-500">
        </div>
        <div class="flex justify-end gap-3 pt-4 border-t">
          <button type="button" id="closeModal"
                  class="px-5 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition duration-200">
            <?= htmlspecialchars($langText['cancel'] ?? 'Cancelar', ENT_QUOTES) ?>
          </button>
          <button type="submit"
                  class="px-5 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition duration-200">
            <?= htmlspecialchars($langText['save'] ?? 'Salvar', ENT_QUOTES) ?>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal: Calendário Expandido -->
  <div id="expandedCalendarModal"
       class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm hidden z-50">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl mx-4 max-h-[90vh] overflow-y-auto p-6 relative">
      <button id="closeExpandedCalendar"
              class="absolute top-4 right-4 text-gray-700 text-2xl hover:text-gray-900 transition duration-200">&times;</button>
      <div id="expandedCalendarContent"></div>
    </div>
  </div>

  <script defer src="<?= BASE_URL ?>/public/js/calendar.js?v=<?= time() ?>"></script>
<?php require __DIR__ . '/../layout/footer.php'; ?>
