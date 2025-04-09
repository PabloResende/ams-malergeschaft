document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM carregado');
  
    // Obtém os elementos necessários
    const addReminderBtn = document.getElementById('addReminderBtn');
    const reminderModal = document.getElementById('reminderModal');
    const closeModal = document.getElementById('closeModal');
    const yearSelector = document.getElementById('yearSelector');
  
    if (!addReminderBtn) {
      console.error('Botão addReminderBtn não encontrado.');
    }
    if (!reminderModal) {
      console.error('Modal reminderModal não encontrado.');
    }
    if (!closeModal) {
      console.error('Botão closeModal não encontrado.');
    }
    if (!yearSelector) {
      console.error('Seletor yearSelector não encontrado.');
    }
  
    // Abre o modal quando o botão é clicado
    addReminderBtn.addEventListener('click', () => {
      console.log('Botão de adicionar lembrete clicado.');
      reminderModal.classList.remove('hidden');
    });
  
    // Fecha o modal quando clica fora da área de conteúdo
    reminderModal.addEventListener('click', (e) => {
      if (e.target === reminderModal) {
        reminderModal.classList.add('hidden');
      }
    });
  
    // Fecha o modal quando o botão "Cancelar" é clicado
    closeModal.addEventListener('click', () => {
      reminderModal.classList.add('hidden');
    });
  
    // Função para buscar os lembretes via AJAX do backend
    function fetchReminders() {
      return fetch('/calendar/fetch')
        .then(response => response.json())
        .catch(err => {
          console.error(err);
          return [];
        });
    }
  
    let reminders = [];
    const serverEvents = JSON.parse(document.getElementById('serverEventsData').textContent);
  
    // Combina eventos do servidor e os lembretes vindos do banco de dados
    function getAllEvents() {
      return serverEvents.concat(reminders);
    }
  
    // Função para renderizar o calendário de um mês
    function renderCalendar(year, month) {
      const monthNames = [
        'Janeiro', 'Fevereiro', 'Março', 'Abril',
        'Maio', 'Junho', 'Julho', 'Agosto',
        'Setembro', 'Outubro', 'Novembro', 'Dezembro'
      ];
      const container = document.createElement('div');
      container.className = "bg-white rounded-xl shadow-lg p-4";
  
      const header = document.createElement('h3');
      header.className = "text-center text-lg font-semibold mb-2";
      header.textContent = `${monthNames[month]} ${year}`;
      container.appendChild(header);
  
      const table = document.createElement('table');
      table.className = "w-full text-center";
  
      const weekdays = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
      const thead = document.createElement('thead');
      const trWeek = document.createElement('tr');
      weekdays.forEach(day => {
        const th = document.createElement('th');
        th.textContent = day;
        th.className = "border p-1 text-xs bg-gray-100";
        trWeek.appendChild(th);
      });
      thead.appendChild(trWeek);
      table.appendChild(thead);
  
      const tbody = document.createElement('tbody');
      const firstDay = new Date(year, month, 1);
      const startingDay = firstDay.getDay();
      const daysInMonth = new Date(year, month + 1, 0).getDate();
  
      let date = 1;
      for (let i = 0; i < 6; i++) {
        const tr = document.createElement('tr');
        for (let j = 0; j < 7; j++) {
          const td = document.createElement('td');
          td.className = "border p-1 h-16 align-top relative";
          if (i === 0 && j < startingDay) {
            td.innerHTML = "";
          } else if (date > daysInMonth) {
            td.innerHTML = "";
          } else {
            const dayDiv = document.createElement('div');
            dayDiv.className = "text-xs font-semibold absolute top-0 left-0 m-1";
            dayDiv.textContent = date;
            td.appendChild(dayDiv);
  
            const eventsWrapper = document.createElement('div');
            eventsWrapper.className = "mt-4 overflow-hidden max-h-12";
  
            const cellDate = new Date(year, month, date);
            cellDate.setHours(0, 0, 0, 0);
            const eventsForDay = getAllEvents().filter(evt => {
              const evtDate = new Date(evt.reminder_date);
              evtDate.setHours(0, 0, 0, 0);
              return evtDate.getTime() === cellDate.getTime();
            });
  
            eventsForDay.forEach(evt => {
              const evtDiv = document.createElement('div');
              evtDiv.className = "mt-1 text-xs text-white rounded px-1 truncate cursor-pointer";
              evtDiv.style.backgroundColor = evt.color || "#3b82f6";
              evtDiv.textContent = evt.title;
              evtDiv.addEventListener('mouseenter', e => {
                const tooltip = document.getElementById('tooltip');
                tooltip.textContent = evt.title;
                tooltip.style.left = (e.clientX + 10) + 'px';
                tooltip.style.top = (e.clientY + 10) + 'px';
                tooltip.style.display = 'block';
              });
              evtDiv.addEventListener('mousemove', e => {
                const tooltip = document.getElementById('tooltip');
                tooltip.style.left = (e.clientX + 10) + 'px';
                tooltip.style.top = (e.clientY + 10) + 'px';
              });
              evtDiv.addEventListener('mouseleave', () => {
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
  
    function renderYearCalendars() {
      const year = parseInt(yearSelector.value);
      const grid = document.getElementById('calendarsGrid');
      grid.innerHTML = "";
      for (let month = 0; month < 12; month++) {
        const calendarEl = renderCalendar(year, month);
        grid.appendChild(calendarEl);
      }
    }
  
    function loadAndRenderCalendars() {
      fetchReminders().then(fetchedReminders => {
        reminders = fetchedReminders;
        renderYearCalendars();
      });
    }
  
    loadAndRenderCalendars();
    yearSelector.addEventListener('change', loadAndRenderCalendars);
  
    // Submissão do formulário via AJAX
    const reminderForm = document.getElementById('reminderForm');
    reminderForm.addEventListener('submit', e => {
        e.preventDefault();
        const formData = new FormData(reminderForm);
        
        // Debug: Mostra os dados do formulário
        for (const [key, value] of formData.entries()) {
            console.log(`${key}: ${value}`);
        }
    
        fetch('/calendar/store', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Resposta:', data); // Debug da resposta
            if (data.success) {
                alert(data.message);
                reminderModal.classList.add('hidden');
                reminderForm.reset();
                loadAndRenderCalendars();
            } else {
                alert(data.message || 'Erro desconhecido');
            }
        })
        .catch(err => {
            console.error('Erro:', err);
            alert('Falha na comunicação com o servidor');
        });
    });
  });