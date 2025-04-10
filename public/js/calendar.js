document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM carregado');

    // Variável baseUrl definida na view como variável global
    // baseUrl já está disponível

    // Obtém os elementos necessários
    const addReminderBtn = document.getElementById('addReminderBtn');
    const reminderModal = document.getElementById('reminderModal');
    const closeModal = document.getElementById('closeModal');
    const yearSelector = document.getElementById('yearSelector');
    
    // Elementos para o modal do calendário expandido
    const expandedCalendarModal = document.getElementById('expandedCalendarModal');
    const expandedCalendarContent = document.getElementById('expandedCalendarContent');
    const closeExpandedCalendar = document.getElementById('closeExpandedCalendar');

    if (!addReminderBtn) { console.error('Botão addReminderBtn não encontrado.'); }
    if (!reminderModal) { console.error('Modal reminderModal não encontrado.'); }
    if (!closeModal) { console.error('Botão closeModal não encontrado.'); }
    if (!yearSelector) { console.error('Seletor yearSelector não encontrado.'); }
    if (!expandedCalendarModal) { console.error('Modal expandedCalendarModal não encontrado.'); }
    if (!closeExpandedCalendar) { console.error('Botão closeExpandedCalendar não encontrado.'); }

    // Abre o modal de lembrete quando o botão é clicado
    addReminderBtn.addEventListener('click', () => {
        console.log('Botão de adicionar lembrete clicado.');
        reminderModal.classList.remove('hidden');
    });

    // Fecha o modal de lembrete clicando fora do conteúdo
    reminderModal.addEventListener('click', (e) => {
        if (e.target === reminderModal) {
            reminderModal.classList.add('hidden');
        }
    });
    
    // Fecha o modal de lembrete quando o botão "Cancelar" é clicado
    closeModal.addEventListener('click', () => {
        reminderModal.classList.add('hidden');
    });
    
    // Fecha o modal do calendário expandido
    closeExpandedCalendar.addEventListener('click', () => {
        expandedCalendarModal.classList.add('hidden');
    });
    
    expandedCalendarModal.addEventListener('click', (e) => {
        if (e.target === expandedCalendarModal) {
            expandedCalendarModal.classList.add('hidden');
        }
    });
    
    // Função para buscar os lembretes via AJAX do backend (rota fetch)
    function fetchReminders() {
        return fetch(`${baseUrl}/calendar/fetch`)
            .then(response => response.json())
            .catch(err => {
                console.error('Erro ao buscar lembretes:', err);
                return [];
            });
    }
    
    let reminders = [];
    const serverEvents = JSON.parse(document.getElementById('serverEventsData').textContent);
    
    // Combina eventos do servidor e os lembretes vindos do banco de dados
    function getAllEvents() {
        return serverEvents.concat(reminders);
    }
    
    // Renderiza um calendário para um mês (modo normal)
    function renderCalendar(year, month) {
        const monthNames = [
            'Janeiro', 'Fevereiro', 'Março', 'Abril',
            'Maio', 'Junho', 'Julho', 'Agosto',
            'Setembro', 'Outubro', 'Novembro', 'Dezembro'
        ];
        const container = document.createElement('div');
        container.className = "bg-white rounded-xl shadow-lg p-4 cursor-pointer"; // cursor-pointer indica que é clicável
        
        // Cabeçalho com o nome do mês (clica para expandir)
        const header = document.createElement('h3');
        header.className = "text-center text-lg font-semibold mb-2";
        header.textContent = `${monthNames[month]} ${year}`;
        
        // Ao clicar no header, exibe o calendário expandido
        header.addEventListener('click', () => {
            showExpandedCalendar(year, month);
        });
        container.appendChild(header);
    
        // Tabela do calendário
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
                        // Tooltip para cada evento
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
    
    // Função para renderizar um calendário expandido (modo detalhado) para um mês
   // Função para renderizar um calendário expandido para um mês (mantém os mesmos dados e eventos)
function renderExpandedCalendar(year, month) {
    const monthNames = [
        'Janeiro', 'Fevereiro', 'Março', 'Abril',
        'Maio', 'Junho', 'Julho', 'Agosto',
        'Setembro', 'Outubro', 'Novembro', 'Dezembro'
    ];
    const container = document.createElement('div');
    container.className = "bg-white rounded-xl shadow-lg p-6"; // container maior

    // Cabeçalho do mês (sem o listener de clique, pois já está expandido)
    const header = document.createElement('h2');
    header.className = "text-center text-2xl font-bold mb-4";
    header.textContent = `${monthNames[month]} ${year}`;
    container.appendChild(header);

    // Cria a tabela do calendário com tamanhos maiores
    const table = document.createElement('table');
    table.className = "w-full text-center border-collapse";

    const weekdays = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
    const thead = document.createElement('thead');
    const trWeek = document.createElement('tr');
    weekdays.forEach(day => {
        const th = document.createElement('th');
        th.textContent = day;
        th.className = "border p-3 text-sm bg-gray-200"; // células cabeçalho maiores
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
            td.className = "border p-3 h-20 align-top relative"; // células maiores
            if (i === 0 && j < startingDay) {
                td.innerHTML = "";
            } else if (date > daysInMonth) {
                td.innerHTML = "";
            } else {
                // Número do dia
                const dayDiv = document.createElement('div');
                dayDiv.className = "text-lg font-semibold absolute top-0 left-0 m-1";
                dayDiv.textContent = date;
                td.appendChild(dayDiv);

                // Container para os eventos
                const eventsWrapper = document.createElement('div');
                eventsWrapper.className = "mt-6 overflow-hidden max-h-16"; // espaço maior para eventos

                // Define a data de referência (sem horários)
                const cellDate = new Date(year, month, date);
                cellDate.setHours(0, 0, 0, 0);

                // Filtra os eventos para o dia
                const eventsForDay = getAllEvents().filter(evt => {
                    const evtDate = new Date(evt.reminder_date);
                    evtDate.setHours(0, 0, 0, 0);
                    return evtDate.getTime() === cellDate.getTime();
                });

                eventsForDay.forEach(evt => {
                    const evtDiv = document.createElement('div');
                    evtDiv.className = "mt-1 text-sm text-white rounded px-2 truncate cursor-pointer";
                    evtDiv.style.backgroundColor = evt.color || "#3b82f6";
                    evtDiv.textContent = evt.title;
                    // Mesmo comportamento do tooltip
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

    
    // Renderiza os calendários para os 12 meses do ano selecionado
    function renderYearCalendars() {
        const year = parseInt(yearSelector.value);
        const grid = document.getElementById('calendarsGrid');
        grid.innerHTML = "";
        for (let month = 0; month < 12; month++) {
            const calendarEl = renderCalendar(year, month);
            grid.appendChild(calendarEl);
        }
    }
    
    // Função para carregar e renderizar os calendários com os lembretes
    function loadAndRenderCalendars() {
        fetchReminders().then(fetchedReminders => {
            reminders = fetchedReminders;
            renderYearCalendars();
        });
    }
    
    loadAndRenderCalendars();
    yearSelector.addEventListener('change', loadAndRenderCalendars);
    
    // Submissão do formulário via AJAX para salvar lembrete
    const reminderForm = document.getElementById('reminderForm');
    reminderForm.addEventListener('submit', e => {
        e.preventDefault();
        const formData = new FormData(reminderForm);
    
        // Debug: exibe os dados do formulário
        for (const [key, value] of formData.entries()) {
            console.log(`${key}: ${value}`);
        }
    
        fetch(`${baseUrl}/calendar/store`, {
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
            console.log('Resposta:', data);
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
    
    // Função para exibir o calendário expandido no modal
// Função para exibir o calendário expandido no modal
function showExpandedCalendar(year, month) {
    // Renderiza o calendário expandido (com a mesma lógica e eventos)
    const expandedContent = renderExpandedCalendar(year, month);
    expandedCalendarContent.innerHTML = '';
    expandedCalendarContent.appendChild(expandedContent);
    expandedCalendarModal.classList.remove('hidden');
}

});
