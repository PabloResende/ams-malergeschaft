<?php
// app/views/calendar/index.php

// Inclui o header (sidebar, navbar e notificações)
require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../../../config/Database.php';

$pdo = Database::connect();

// Eventos de projetos (projetos já existentes)
$stmt = $pdo->query("SELECT id, name, end_date FROM projects WHERE end_date IS NOT NULL");
$projectEvents = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $projectEvents[] = [
        'id'       => $row['id'],
        'title'    => $row['name'],
        'start'    => $row['end_date'],
        'type'     => 'projeto',
        'color'    => '#38a169' 
    ];
}
$reminderEvents = [];

// Junta todos os eventos
$allEvents = array_merge($projectEvents, $reminderEvents);
$eventsJson = json_encode($allEvents);
?>
<!-- Área principal compensando o espaço do sidebar e do header -->
<main class="ml-56 pt-20 p-6 min-h-screen bg-gray-50">
    <div class="container mx-auto max-w-7xl">
        <!-- Cabeçalho dos Filtros -->
        <div class="flex flex-wrap gap-4 items-center mb-6">
            <!-- Filtro de Ano -->
            <div>
                <label for="filterYear" class="block text-sm font-medium text-gray-700">Ano</label>
                <select id="filterYear" class="mt-1 block rounded-md border-gray-300 shadow-sm focus:ring-blue-500">
                    <option value="">Todos</option>
                    <?php 
                        // Exemplo: gera opções de 2021 a 2030
                        for ($year = 2021; $year <= 2030; $year++) {
                            echo "<option value='{$year}'>{$year}</option>";
                        }
                    ?>
                </select>
            </div>
            <!-- Filtro de Semestre -->
            <div>
                <label for="filterSemester" class="block text-sm font-medium text-gray-700">Semestre</label>
                <select id="filterSemester" class="mt-1 block rounded-md border-gray-300 shadow-sm focus:ring-blue-500">
                    <option value="">Todos</option>
                    <option value="1">1º Semestre</option>
                    <option value="2">2º Semestre</option>
                </select>
            </div>
            <!-- Filtro de Trimestre -->
            <div>
                <label for="filterTrimester" class="block text-sm font-medium text-gray-700">Trimestre</label>
                <select id="filterTrimester" class="mt-1 block rounded-md border-gray-300 shadow-sm focus:ring-blue-500">
                    <option value="">Todos</option>
                    <option value="1">1º Trimestre</option>
                    <option value="2">2º Trimestre</option>
                    <option value="3">3º Trimestre</option>
                    <option value="4">4º Trimestre</option>
                </select>
            </div>
            <!-- Filtro de Mês -->
            <div>
                <label for="filterMonth" class="block text-sm font-medium text-gray-700">Mês</label>
                <select id="filterMonth" class="mt-1 block rounded-md border-gray-300 shadow-sm focus:ring-blue-500">
                    <option value="">Todos</option>
                    <?php
                        $meses = [
                            '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março',
                            '04' => 'Abril', '05' => 'Maio', '06' => 'Junho',
                            '07' => 'Julho', '08' => 'Agosto', '09' => 'Setembro',
                            '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro'
                        ];
                        foreach($meses as $num => $nome) {
                            echo "<option value='{$num}'>{$nome}</option>";
                        }
                    ?>
                </select>
            </div>
            <!-- Filtro de Tipo -->
            <div>
                <label for="filterType" class="block text-sm font-medium text-gray-700">Tipo</label>
                <select id="filterType" class="mt-1 block rounded-md border-gray-300 shadow-sm focus:ring-blue-500">
                    <option value="">Todos</option>
                    <option value="projeto">Projeto</option>
                    <option value="lembrete">Lembrete</option>
                </select>
            </div>
            <!-- Botão para abrir modal de lembrete -->
            <div class="ml-auto">
                <button id="addReminderBtn" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500">
                    + Adicionar Lembrete
                </button>
            </div>
        </div>

        <!-- Card que contém o calendário ocupando 100% do espaço -->
        <div class="bg-white rounded-xl shadow-lg p-4 h-[800px]">
            <div id="calendar" class="h-full"></div>
        </div>
    </div>
</main>

<!-- Modal para Adicionar Lembrete -->
<div id="reminderModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white rounded-lg shadow-xl w-11/12 max-w-md p-6">
        <h2 class="text-xl font-bold mb-4">Adicionar Lembrete</h2>
        <form id="reminderForm" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Título</label>
                <input type="text" id="reminderTitle" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Data</label>
                <input type="date" id="reminderDate" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Cor</label>
                <input type="color" id="reminderColor" value="#e53e3e" class="mt-1 block w-16 h-10 rounded-md border-gray-300 shadow-sm focus:ring-blue-500">
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" id="closeModal" class="px-4 py-2 rounded-md text-gray-700 bg-gray-200 hover:bg-gray-300">Cancelar</button>
                <button type="submit" class="px-4 py-2 rounded-md text-white bg-blue-600 hover:bg-blue-700">Salvar</button>
            </div>
        </form>
    </div>
</div>

<!-- Scripts do FullCalendar -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales-all.min.js"></script>

<style>
  /* Customizações para o FullCalendar */
  .fc {
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
    // Converte eventos do PHP
    var allEvents = <?php echo $eventsJson; ?>;
    // Array para armazenar lembretes adicionados dinamicamente
    var dynamicReminders = [];
    
    // Inicializa o calendário ocupando 100% da altura do card
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'pt',
        headerToolbar: {
            left: '',   // Removido os botões à esquerda (prev, next, today)
            center: 'title', // Apenas o título é exibido
            right: ''   // Removido os botões à direita (dayGridMonth, timeGridWeek, timeGridDay)
        },
        height: '100%',
        events: function(fetchInfo, successCallback, failureCallback) {
            // Junta os eventos de projetos e os lembretes adicionados dinamicamente
            var events = allEvents.concat(dynamicReminders);
            successCallback(events);
        },
        eventDidMount: function(info) {
            // Se o evento for do tipo projeto, e estiver a 3 dias do vencimento, adicione destaque
            if(info.event.extendedProps.type === 'projeto') {
                var eventDate = new Date(info.event.start);
                var currentDate = new Date();
                eventDate.setHours(0,0,0,0);
                currentDate.setHours(0,0,0,0);
                var diffTime = eventDate.getTime() - currentDate.getTime();
                var diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                if(diffDays === 3) {
                    info.el.style.boxShadow = '0 2px 4px rgba(239, 68, 68, 0.6)';
                }
            }
        }
    });
    calendar.render();
    
    // Função para aplicar filtros
    function applyFilters() {
        var year      = document.getElementById('filterYear').value;
        var semester  = document.getElementById('filterSemester').value;
        var trimester = document.getElementById('filterTrimester').value;
        var month     = document.getElementById('filterMonth').value;
        var type      = document.getElementById('filterType').value;
        
        var filteredEvents = allEvents.concat(dynamicReminders).filter(function(event) {
            var eventDate = new Date(event.start);
            var eventYear = eventDate.getFullYear().toString();
            var eventMonth = ("0" + (eventDate.getMonth()+1)).slice(-2);
            var pass = true;
            
            if(year && eventYear !== year) {
                pass = false;
            }
            if(month && eventMonth !== month) {
                pass = false;
            }
            if(semester) {
                if(semester == "1" && (eventDate.getMonth() > 5)) pass = false;
                if(semester == "2" && (eventDate.getMonth() < 6)) pass = false;
            }
            if(trimester) {
                var monthIndex = eventDate.getMonth();
                var tri = Math.floor(monthIndex / 3) + 1;
                if(parseInt(trimester) !== tri) pass = false;
            }
            if(type && event.type !== type) {
                pass = false;
            }
            return pass;
        });
        calendar.removeAllEvents();
        calendar.addEventSource(filteredEvents);
    }
    
    document.getElementById('filterYear').addEventListener('change', applyFilters);
    document.getElementById('filterSemester').addEventListener('change', applyFilters);
    document.getElementById('filterTrimester').addEventListener('change', applyFilters);
    document.getElementById('filterMonth').addEventListener('change', applyFilters);
    document.getElementById('filterType').addEventListener('change', applyFilters);

    // Modal para adicionar lembrete
    var reminderModal = document.getElementById('reminderModal');
    var addReminderBtn = document.getElementById('addReminderBtn');
    var closeModal = document.getElementById('closeModal');
    var reminderForm = document.getElementById('reminderForm');
    
    addReminderBtn.addEventListener('click', function() {
        reminderModal.classList.remove('hidden');
    });
    closeModal.addEventListener('click', function() {
        reminderModal.classList.add('hidden');
    });
    
    reminderForm.addEventListener('submit', function(e) {
        e.preventDefault();
        var title = document.getElementById('reminderTitle').value;
        var date  = document.getElementById('reminderDate').value;
        var color = document.getElementById('reminderColor').value;
        var reminder = {
            id: 'lembrete-' + Date.now(),
            title: title,
            start: date,
            type: 'lembrete',
            color: color
        };
        dynamicReminders.push(reminder);
        calendar.addEvent(reminder);
        reminderModal.classList.add('hidden');
        reminderForm.reset();
        applyFilters();
    });
});
</script>
