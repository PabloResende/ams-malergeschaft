<?php
// app/views/calendar/index.php

// Inclui o header (sidebar, navbar e notificações)
require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../../../config/Database.php';

// Conecta ao banco de dados
$pdo = Database::connect();

// Eventos de projetos já existentes (exemplo)
$stmt = $pdo->query("SELECT id, name, end_date FROM projects WHERE end_date IS NOT NULL");
$projectEvents = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Consideramos que a data vem no formato YYYY-MM-DD
    $projectEvents[] = [
        'id'    => $row['id'],
        'title' => $row['name'],
        'start' => $row['end_date'], 
        'type'  => 'projeto',
        'color' => '#38a169'
    ];
}
// Outros eventos estáticos podem ser adicionados aqui
$allEvents = $projectEvents;
$eventsJson = json_encode($allEvents);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Calendários do Ano</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    /* Layout fixo para as tabelas */
    table {
      width: 100%;
      table-layout: fixed;
      border-collapse: collapse;
    }
    /* Garante que cada célula não ultrapasse seus limites */
    td {
      overflow: hidden;
    }
    /* Estilo básico para o tooltip flutuante */
    #tooltip {
      position: fixed;
      background: rgba(0, 0, 0, 0.75);
      color: #fff;
      padding: 0.5rem;
      border-radius: 0.25rem;
      font-size: 0.75rem;
      white-space: nowrap;
      pointer-events: none;
      display: none;
      z-index: 1000;
    }
  </style>
</head>
<body class="bg-gray-50">
  <!-- Área principal -->
  <main class="ml-56 pt-20 p-6 min-h-screen">
    <div class="container mx-auto max-w-7xl">
      <!-- Cabeçalho com seletor de ano e botão para adicionar lembrete -->
      <div class="flex items-center mb-6">
        <div>
          <label for="yearSelector" class="block text-sm font-medium text-gray-700">Ano</label>
          <select id="yearSelector" class="mt-1 block rounded-md border-gray-300 shadow-sm focus:ring-blue-500">
            <?php 
              // Opções de 2021 a 2030, com o ano atual selecionado
              for ($year = 2021; $year <= 2030; $year++) {
                  $selected = ($year == date("Y")) ? "selected" : "";
                  echo "<option value='{$year}' {$selected}>{$year}</option>";
              }
            ?>
          </select>
        </div>
        <div class="ml-auto">
          <button id="addReminderBtn" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            + Adicionar Lembrete
          </button>
        </div>
      </div>

      <!-- Grid para os 12 calendários -->
      <div id="calendarsGrid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4"></div>
    </div>
  </main>

  <!-- Tooltip flutuante -->
  <div id="tooltip"></div>

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
          <button type="button" id="closeModal" class="px-4 py-2 rounded-md text-gray-700 bg-gray-200 hover:bg-gray-300">
            Cancelar
          </button>
          <button type="submit" class="px-4 py-2 rounded-md text-white bg-blue-600 hover:bg-blue-700">
            Salvar
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Scripts para renderização dos calendários -->
  <script>
  // Eventos vindos do servidor
  const serverEvents = <?php echo $eventsJson; ?>;
  // Recupera lembretes do localStorage ou inicia com array vazio
  let storedReminders = localStorage.getItem('reminders');
  let reminderEvents = storedReminders ? JSON.parse(storedReminders) : [];

  // Filtra lembretes expirados (remove se data for menor que hoje)
  function filterExpiredReminders() {
    const today = new Date();
    today.setHours(0,0,0,0);
    reminderEvents = reminderEvents.filter(evt => {
      const evtDate = new Date(evt.start);
      evtDate.setHours(0,0,0,0);
      return evtDate >= today;
    });
    localStorage.setItem('reminders', JSON.stringify(reminderEvents));
  }
  filterExpiredReminders();

  // Combina eventos do servidor e lembretes
  function getAllEvents() {
    return serverEvents.concat(reminderEvents);
  }

  // Função para renderizar o calendário (tabela) para um determinado mês e ano
  function renderCalendar(year, month) {
    const monthNames = [
      'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
      'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'
    ];
    // Container do mês
    const container = document.createElement('div');
    container.className = "bg-white rounded-xl shadow-lg p-4";
  
    // Cabeçalho com nome do mês e ano
    const header = document.createElement('h3');
    header.className = "text-center text-lg font-semibold mb-2";
    header.innerText = `${monthNames[month]} ${year}`;
    container.appendChild(header);
  
    // Criação da tabela do calendário
    const table = document.createElement('table');
    table.className = "w-full text-center";
  
    // Cabeçalho com os dias da semana
    const weekdays = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
    const thead = document.createElement('thead');
    const trWeek = document.createElement('tr');
    weekdays.forEach(day => {
      const th = document.createElement('th');
      th.innerText = day;
      th.className = "border p-1 text-xs bg-gray-100";
      trWeek.appendChild(th);
    });
    thead.appendChild(trWeek);
    table.appendChild(thead);
  
    // Corpo da tabela: cálculo do primeiro dia e número de dias do mês
    const tbody = document.createElement('tbody');
    const firstDay = new Date(year, month, 1);
    const startingDay = firstDay.getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
  
    let date = 1;
    for (let i = 0; i < 6; i++) { // máximo de 6 semanas
      const tr = document.createElement('tr');
      for (let j = 0; j < 7; j++) {
        const td = document.createElement('td');
        td.className = "border p-1 h-16 align-top relative";
  
        if (i === 0 && j < startingDay) {
          td.innerHTML = "";
        } else if (date > daysInMonth) {
          td.innerHTML = "";
        } else {
          // Exibe o número do dia
          const dayDiv = document.createElement('div');
          dayDiv.className = "text-xs font-semibold absolute top-0 left-0 m-1";
          dayDiv.innerText = date;
          td.appendChild(dayDiv);
  
          // Wrapper para os eventos com altura limitada
          const eventsWrapper = document.createElement('div');
          eventsWrapper.className = "mt-4 overflow-hidden max-h-12";
  
          // Filtra eventos para o dia corrente
          const cellDate = new Date(year, month, date);
          cellDate.setHours(0,0,0,0);
          const eventsForDay = getAllEvents().filter(evt => {
            const evtDate = new Date(evt.start);
            evtDate.setHours(0,0,0,0);
            return evtDate.getTime() === cellDate.getTime();
          });
  
          // Para cada evento, cria o elemento com tooltip
          eventsForDay.forEach(evt => {
            const evtDiv = document.createElement('div');
            evtDiv.className = "mt-1 text-xs text-white rounded px-1 truncate";
            evtDiv.style.backgroundColor = evt.color || "#3b82f6";
            evtDiv.title = evt.title;
            evtDiv.innerText = evt.title;
  
            // Eventos para mostrar o tooltip flutuante
            evtDiv.addEventListener('mouseenter', function(e) {
              const tooltip = document.getElementById('tooltip');
              tooltip.innerText = evt.title;
              tooltip.style.left = (e.clientX + 10) + 'px';
              tooltip.style.top = (e.clientY + 10) + 'px';
              tooltip.style.display = 'block';
            });
            evtDiv.addEventListener('mousemove', function(e) {
              const tooltip = document.getElementById('tooltip');
              tooltip.style.left = (e.clientX + 10) + 'px';
              tooltip.style.top = (e.clientY + 10) + 'px';
            });
            evtDiv.addEventListener('mouseleave', function(e) {
              const tooltip = document.getElementById('tooltip');
              tooltip.style.display = 'none';
            });
  
            eventsWrapper.appendChild(evtDiv);
          });
  
          td.appendChild(eventsWrapper);
          date++;
        }
        tr.appendChild(td);
      }
      tbody.appendChild(tr);
      if (date > daysInMonth) break;
    }
    table.appendChild(tbody);
    container.appendChild(table);
    return container;
  }
  
  // Renderiza os 12 calendários na grid
  function renderYearCalendars() {
    const year = parseInt(document.getElementById('yearSelector').value);
    const grid = document.getElementById('calendarsGrid');
    grid.innerHTML = "";
    for (let month = 0; month < 12; month++) {
      const calendarEl = renderCalendar(year, month);
      grid.appendChild(calendarEl);
    }
  }
  
  // Renderiza ao carregar a página e ao trocar o ano
  document.addEventListener('DOMContentLoaded', renderYearCalendars);
  document.getElementById('yearSelector').addEventListener('change', renderYearCalendars);
  
  // Configuração do modal para adicionar lembrete
  const addReminderBtn = document.getElementById('addReminderBtn');
  const reminderModal = document.getElementById('reminderModal');
  const closeModal = document.getElementById('closeModal');
  const reminderForm = document.getElementById('reminderForm');
  
  addReminderBtn.addEventListener('click', () => {
    reminderModal.classList.remove('hidden');
  });
  closeModal.addEventListener('click', () => {
    reminderModal.classList.add('hidden');
  });
  
  reminderForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const title = document.getElementById('reminderTitle').value;
    const date = document.getElementById('reminderDate').value;
    const color = document.getElementById('reminderColor').value;
    if (!date) return;
    const newReminder = {
      id: 'reminder-' + Date.now(),
      title: title,
      start: date,
      type: 'lembrete',
      color: color
    };
    reminderEvents.push(newReminder);
    localStorage.setItem('reminders', JSON.stringify(reminderEvents));
    reminderModal.classList.add('hidden');
    reminderForm.reset();
    renderYearCalendars();
  });
  </script>
</body>
</html>
