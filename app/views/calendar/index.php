<?php
// app/views/calendar/index.php
require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../../../config/Database.php';

$pdo = Database::connect();

// Consulta os projetos que possuem data de entrega definida
$stmt = $pdo->query("SELECT id, name, end_date FROM projects WHERE end_date IS NOT NULL");
$projectEvents = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $projectEvents[] = [
        'id'    => $row['id'],
        'title' => $row['name'],
        'start' => $row['end_date'],
        'color' => '#38a169' // Verde para entrega de projeto
    ];
}
$eventsJson = json_encode($projectEvents);
?>

<!-- Conteúdo principal do Calendário -->
<div class="container mx-auto p-4 pt-20 max-w-7xl min-h-screen">
    <!-- Header com Filtros e Botões -->
    <div class="flex flex-col md:flex-row gap-4 justify-between items-start md:items-center mb-6">
        <div class="w-full md:w-auto flex flex-col md:flex-row items-start md:items-center gap-3">
            <div class="flex items-center gap-2 w-full md:w-auto">
                <label for="projectFilter" class="text-sm font-medium text-gray-700">Filtrar por Projeto:</label>
                <select id="projectFilter" class="block w-full md:w-48 rounded-md border border-gray-300 shadow-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Todos os Projetos</option>
                    <?php
                    $stmtProjects = $pdo->query("SELECT id, name FROM projects ORDER BY name ASC");
                    $projects = $stmtProjects->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($projects as $project) {
                        echo "<option value='" . htmlspecialchars($project['id']) . "'>" . htmlspecialchars($project['name']) . "</option>";
                    }
                    ?>
                </select>
            </div>
        </div>
        <button id="addReminderBtn" class="w-full md:w-auto bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
            + Adicionar Lembrete
        </button>
    </div>

    <!-- Container do Calendário -->
    <div class="w-full lg:w-4/5 xl:w-3/4 bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div id="calendar" class="h-[700px]"></div>
    </div>

    <!-- Legendas -->
    <div class="mt-6 flex flex-wrap gap-4 justify-center">
        <div class="flex items-center gap-2 bg-gray-50 px-3 py-1.5 rounded-full border border-gray-200">
            <span class="w-3 h-3 rounded-full bg-[#38a169]"></span>
            <span class="text-sm text-gray-600">Entrega de Projeto</span>
        </div>
        <div class="flex items-center gap-2 bg-gray-50 px-3 py-1.5 rounded-full border border-gray-200">
            <span class="w-3 h-3 rounded-full bg-[#e53e3e]"></span>
            <span class="text-sm text-gray-600">Lembrete</span>
        </div>
    </div>
</div>

<!-- Scripts do FullCalendar -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales-all.min.js"></script>

<style>
.fc { /* Customização do calendário */
    --fc-border-color: #e5e7eb;
    --fc-today-bg-color: #f3f4f6;
    --fc-neutral-bg-color: #f9fafb;
    --fc-page-bg-color: #ffffff;
}

.fc-event {
    cursor: pointer;
    border-radius: 4px;
    font-size: 0.875rem;
    border: none;
    padding: 2px 4px;
}

.fc-toolbar-title {
    font-size: 1.25rem;
    font-weight: 600;
}

.fc-button-primary {
    background-color: #3b82f6 !important;
    border-color: #3b82f6 !important;
    text-transform: capitalize !important;
    font-weight: 500 !important;
    border-radius: 6px !important;
    padding: 6px 12px !important;
}

.fc-button-primary:hover {
    background-color: #2563eb !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var allEvents = <?php echo $eventsJson; ?>;
    
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'pt',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        events: allEvents,
        eventDidMount: function(info) {
            var eventDate = new Date(info.event.start);
            var currentDate = new Date();
            eventDate.setHours(0,0,0,0);
            currentDate.setHours(0,0,0,0);
            var diffTime = eventDate.getTime() - currentDate.getTime();
            var diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            
            if(diffDays === 3) {
                info.el.style.boxShadow = '0 2px 4px rgba(239, 68, 68, 0.3)';
                info.el.insertAdjacentHTML('beforeend', '<div class="absolute top-0 right-0 -mt-1 -mr-1 w-2 h-2 bg-red-500 rounded-full"></div>');
            }
        }
    });
    
    calendar.render();
    
    // Filtro de projetos
    document.getElementById('projectFilter').addEventListener('change', function() {
        var projectId = this.value;
        calendar.removeAllEvents();
        calendar.addEventSource(projectId ? allEvents.filter(e => e.id == projectId) : allEvents);
    });
    
    document.getElementById('addReminderBtn').addEventListener('click', function() {
        // Implementar lógica de adição de lembrete
    });
});
</script>