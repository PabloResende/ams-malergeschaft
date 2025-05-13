<?php
// app/views/calendar/index.php

require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../../../config/Database.php';

// Dados do calendário
$pdo = Database::connect();
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
$allEvents  = $projectEvents;
$eventsJson = json_encode($allEvents);

// Traduções de meses e dias
$monthNames = $langText['month_names'] ?? [
    'Janeiro','Fevereiro','Março','Abril','Maio','Junho',
    'Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'
];
$weekdays = $langText['weekdays'] ?? ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];

// Locale da página
$locale = $langText['locale'] ?? 'pt-BR';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($locale) ?>">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($langText['calendar_title'] ?? 'Calendários do Ano') ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    table { width:100%; table-layout:fixed; border-collapse:collapse; }
    td { overflow:hidden; }
    #tooltip { position:fixed; background:rgba(0,0,0,0.75); color:#fff;
               padding:.5rem; border-radius:.25rem; font-size:.75rem;
               white-space:nowrap; pointer-events:none; display:none; z-index:1000; }
  </style>
</head>
<body class="bg-gray-50">

  <script>
    window.baseUrl = '<?= addslashes($baseUrl) ?>';
    window.langText = {
      yearLabel:      '<?= addslashes($langText['year'] ?? 'Ano') ?>',
      addReminder:    '<?= addslashes($langText['add_reminder'] ?? '+ Adicionar Lembrete') ?>',
      modalTitle:     '<?= addslashes($langText['add_reminder'] ?? 'Adicionar Lembrete') ?>',
      titleLabel:     '<?= addslashes($langText['title'] ?? 'Título') ?>',
      dateLabel:      '<?= addslashes($langText['date'] ?? 'Data') ?>',
      colorLabel:     '<?= addslashes($langText['color'] ?? 'Cor') ?>',
      cancel:         '<?= addslashes($langText['cancel'] ?? 'Cancelar') ?>',
      save:           '<?= addslashes($langText['save'] ?? 'Salvar') ?>',
      eventsOn:       '<?= addslashes($langText['events_on'] ?? 'Eventos em') ?>',
      monthNames:     <?= json_encode($monthNames) ?>,
      weekdays:       <?= json_encode($weekdays) ?>
    };
  </script>

  <script id="serverEventsData" type="application/json">
    <?= $eventsJson ?>
  </script>

  <main class="ml-0 sm:ml-56 pt-20 px-4 sm:px-6 min-h-screen">
    <div class="container mx-auto max-w-7xl">

      <div class="flex flex-col sm:flex-row sm:items-end gap-4 mb-6">
        <div class="flex-1">
          <label for="yearSelector" class="block text-sm font-medium text-gray-700">
            <?= htmlspecialchars($langText['year'] ?? 'Ano') ?>
          </label>
          <select id="yearSelector" class="mt-1 rounded-md border-gray-300 shadow-sm focus:ring-blue-500">
            <?php for ($year = 2025; $year <= 2030; $year++): ?>
              <option value="<?= $year ?>" <?= ($year === date("Y") ? "selected" : "") ?>>
                <?= $year ?>
              </option>
            <?php endfor; ?>
          </select>
        </div>
        <div class="flex justify-end">
          <button id="addReminderBtn"
                  class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            <?= htmlspecialchars($langText['add_reminder'] ?? '+ Adicionar Lembrete') ?>
          </button>
        </div>
      </div>

      <div id="calendarsGrid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4"></div>
    </div>
  </main>

  <div id="tooltip"></div>

  <!-- Modal: Adicionar Lembrete -->
  <div id="reminderModal"
       class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50">
    <div class="bg-white rounded-lg shadow-xl w-11/12 max-w-md p-6">
      <h2 class="text-xl font-bold mb-4">
        <?= htmlspecialchars($langText['add_reminder'] ?? 'Adicionar Lembrete') ?>
      </h2>
      <form id="reminderForm" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">
            <?= htmlspecialchars($langText['title'] ?? 'Título') ?>
          </label>
          <input type="text" id="reminderTitle" name="title"
                 class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500"
                 required>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">
            <?= htmlspecialchars($langText['date'] ?? 'Data') ?>
          </label>
          <input type="date" id="reminderDate" name="reminder_date"
                 class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500"
                 required>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">
            <?= htmlspecialchars($langText['color'] ?? 'Cor') ?>
          </label>
          <input type="color" id="reminderColor" name="color" value="#e53e3e"
                 class="mt-1 block w-16 h-10 rounded-md border-gray-300 shadow-sm focus:ring-blue-500">
        </div>
        <div class="flex justify-end gap-2">
          <button type="button" id="closeModal"
                  class="px-4 py-2 rounded-md text-gray-700 bg-gray-200 hover:bg-gray-300">
            <?= htmlspecialchars($langText['cancel'] ?? 'Cancelar') ?>
          </button>
          <button type="submit"
                  class="px-4 py-2 rounded-md text-white bg-blue-600 hover:bg-blue-700">
            <?= htmlspecialchars($langText['save'] ?? 'Salvar') ?>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal: Calendário Expandido -->
  <div id="expandedCalendarModal"
       class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50">
    <div class="bg-white rounded-lg shadow-xl w-11/12 max-w-3xl p-6 relative">
      <button id="closeExpandedCalendar"
              class="absolute top-2 right-2 text-gray-700 font-bold text-xl">&times;</button>
      <div id="expandedCalendarContent"></div>
    </div>
  </div>

  <script src="<?= $baseUrl ?>/js/calendar.js"></script>
</body>
</html>
