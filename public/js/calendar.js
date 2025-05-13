// public/js/calendar.js

document.addEventListener("DOMContentLoaded", () => {
  const {
    yearLabel, addReminder, modalTitle,
    titleLabel, dateLabel, colorLabel,
    cancel, save, eventsOn,
    monthNames, weekdays
  } = window.langText;

  // ————— Tradução de elementos estáticos —————

  // Label acima do seletor de ano
  const yearLabelEl = document.querySelector('label[for="yearSelector"]');
  if (yearLabelEl) yearLabelEl.textContent = yearLabel;

  // Botão “Adicionar Lembrete”
  const addReminderBtn = document.getElementById("addReminderBtn");
  if (addReminderBtn) addReminderBtn.textContent = addReminder;

  // Modal de lembrete
  const reminderModal = document.getElementById("reminderModal");
  if (reminderModal) {
    // Título do modal
    const modalTitleEl = reminderModal.querySelector("h2");
    if (modalTitleEl) modalTitleEl.textContent = modalTitle;

    // Labels do formulário
    const formLabels = reminderModal.querySelectorAll("#reminderForm label");
    if (formLabels.length >= 3) {
      formLabels[0].textContent = titleLabel;
      formLabels[1].textContent = dateLabel;
      formLabels[2].textContent = colorLabel;
    }

    // Botões “Cancelar” e “Salvar”
    const cancelBtn = reminderModal.querySelector("#closeModal");
    if (cancelBtn) cancelBtn.textContent = cancel;
    const saveBtn = reminderModal.querySelector("button[type='submit']");
    if (saveBtn) saveBtn.textContent = save;
  }

  // ————— Fim da tradução —————

  const closeModal              = document.getElementById("closeModal");
  const yearSelector            = document.getElementById("yearSelector");
  const expandedCalendarModal   = document.getElementById("expandedCalendarModal");
  const expandedCalendarContent = document.getElementById("expandedCalendarContent");
  const closeExpandedCalendar   = document.getElementById("closeExpandedCalendar");
  const tooltip                 = document.getElementById("tooltip");
  const serverEvents            = JSON.parse(
    document.getElementById("serverEventsData").textContent
  );

  let reminders = [];

  // Abre/fecha o modal de lembrete
  addReminderBtn.addEventListener("click", () => {
    reminderModal.classList.remove("hidden");
  });
  reminderModal.addEventListener("click", e => {
    if (e.target === reminderModal) {
      reminderModal.classList.add("hidden");
    }
  });
  closeModal.addEventListener("click", () => {
    reminderModal.classList.add("hidden");
  });

  // Abre/fecha o calendário expandido
  closeExpandedCalendar.addEventListener("click", () => {
    expandedCalendarModal.classList.add("hidden");
  });
  expandedCalendarModal.addEventListener("click", e => {
    if (e.target === expandedCalendarModal) {
      expandedCalendarModal.classList.add("hidden");
    }
  });

  // Busca lembretes do backend
  function fetchReminders() {
    return fetch(`${baseUrl}/calendar/fetch`)
      .then(res => res.json())
      .catch(err => {
        console.error("Erro ao buscar lembretes:", err);
        return [];
      });
  }

  // Combina eventos do servidor e lembretes
  function getAllEvents() {
    return serverEvents.concat(reminders);
  }

  // Renderiza um calendário mensal resumido
  function renderCalendar(year, month) {
    const container = document.createElement("div");
    container.className = "bg-white rounded-xl shadow-lg p-4";

    // Cabeçalho
    const header = document.createElement("h3");
    header.className = "text-center text-lg font-semibold mb-2 cursor-pointer";
    header.textContent = `${monthNames[month]} ${year}`;
    header.addEventListener("click", () => {
      if (window.innerWidth >= 768) {
        showExpandedCalendar(year, month);
      }
    });
    container.appendChild(header);

    // Tabela
    const table = document.createElement("table");
    table.className = "w-full text-center";
    const thead = document.createElement("thead");
    const trWeek = document.createElement("tr");
    weekdays.forEach(day => {
      const th = document.createElement("th");
      th.textContent = day;
      th.className = "border p-1 text-xs bg-gray-100";
      trWeek.appendChild(th);
    });
    thead.appendChild(trWeek);
    table.appendChild(thead);

    const tbody = document.createElement("tbody");
    const firstDay    = new Date(year, month, 1);
    const startingDay = firstDay.getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    let date = 1;
    const maxVisibleEvents = 3;

    for (let i = 0; i < 6; i++) {
      const tr = document.createElement("tr");
      for (let j = 0; j < 7; j++) {
        const td = document.createElement("td");
        td.className = "border p-1 h-16 align-top relative";
        if ((i === 0 && j < startingDay) || date > daysInMonth) {
          td.innerHTML = "";
        } else {
          // Número do dia
          const dayDiv = document.createElement("div");
          dayDiv.className = "text-xs font-semibold absolute top-0 left-0 m-1";
          dayDiv.textContent = date;
          td.appendChild(dayDiv);

          // Eventos
          const eventsWrapper = document.createElement("div");
          eventsWrapper.className = "mt-4 overflow-hidden max-h-12";

          const cellDate = new Date(year, month, date);
          cellDate.setHours(0,0,0,0);

          const eventsForDay = getAllEvents().filter(evt => {
            const d = new Date(evt.reminder_date);
            d.setHours(0,0,0,0);
            return d.getTime() === cellDate.getTime();
          });

          // Exibe até maxVisibleEvents
          eventsForDay.slice(0, maxVisibleEvents).forEach(evt => {
            const evtDiv = document.createElement("div");
            evtDiv.className = "mt-1 text-xs text-white rounded px-1 truncate cursor-pointer";
            evtDiv.style.backgroundColor = evt.color || "#3b82f6";
            evtDiv.textContent = evt.title;

            // Tooltip
            evtDiv.addEventListener("mouseenter", e => {
              tooltip.textContent = evt.title;
              tooltip.style.left = e.clientX + 10 + "px";
              tooltip.style.top  = e.clientY + 10 + "px";
              tooltip.style.display = "block";
            });
            evtDiv.addEventListener("mousemove", e => {
              tooltip.style.left = e.clientX + 10 + "px";
              tooltip.style.top  = e.clientY + 10 + "px";
            });
            evtDiv.addEventListener("mouseleave", () => {
              tooltip.style.display = "none";
            });

            eventsWrapper.appendChild(evtDiv);
          });

          // Indicador de “+X”
          if (eventsForDay.length > maxVisibleEvents) {
            const moreDiv = document.createElement("div");
            moreDiv.className = "mt-1 text-xs text-blue-600 cursor-pointer";
            moreDiv.textContent = `+${eventsForDay.length - maxVisibleEvents}`;
            moreDiv.addEventListener("click", () => {
              showDayEventsModal(cellDate, eventsForDay);
            });
            eventsWrapper.appendChild(moreDiv);
          }

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

  // Renderiza um calendário mensal expandido
  function renderExpandedCalendar(year, month) {
    const container = document.createElement("div");
    container.className = "bg-white rounded-xl shadow-lg p-6";

    const header = document.createElement("h2");
    header.className = "text-center text-2xl font-bold mb-4";
    header.textContent = `${monthNames[month]} ${year}`;
    container.appendChild(header);

    const table = document.createElement("table");
    table.className = "w-full text-center border-collapse";
    const thead = document.createElement("thead");
    const trWeek = document.createElement("tr");
    weekdays.forEach(day => {
      const th = document.createElement("th");
      th.textContent = day;
      th.className = "border p-3 text-sm bg-gray-200";
      trWeek.appendChild(th);
    });
    thead.appendChild(trWeek);
    table.appendChild(thead);

    const tbody = document.createElement("tbody");
    const firstDay    = new Date(year, month, 1);
    const startingDay = firstDay.getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    let date = 1;
    for (let i = 0; i < 6; i++) {
      const tr = document.createElement("tr");
      for (let j = 0; j < 7; j++) {
        const td = document.createElement("td");
        td.className = "border p-3 h-20 align-top relative";
        if ((i === 0 && j < startingDay) || date > daysInMonth) {
          td.innerHTML = "";
        } else {
          const dayDiv = document.createElement("div");
          dayDiv.className = "text-lg font-semibold absolute top-0 left-0 m-1";
          dayDiv.textContent = date;
          td.appendChild(dayDiv);

          const eventsWrapper = document.createElement("div");
          eventsWrapper.className = "mt-6 overflow-hidden";

          const cellDate = new Date(year, month, date);
          cellDate.setHours(0,0,0,0);

          const eventsForDay = getAllEvents().filter(evt => {
            const d = new Date(evt.reminder_date);
            d.setHours(0,0,0,0);
            return d.getTime() === cellDate.getTime();
          });

          eventsForDay.forEach(evt => {
            const evtDiv = document.createElement("div");
            evtDiv.className = "mt-1 text-sm text-white rounded px-2 truncate cursor-pointer";
            evtDiv.style.backgroundColor = evt.color || "#3b82f6";
            evtDiv.textContent = evt.title;

            evtDiv.addEventListener("mouseenter", e => {
              tooltip.textContent = evt.title;
              tooltip.style.left = e.clientX + 10 + "px";
              tooltip.style.top  = e.clientY + 10 + "px";
              tooltip.style.display = "block";
            });
            evtDiv.addEventListener("mousemove", e => {
              tooltip.style.left = e.clientX + 10 + "px";
              tooltip.style.top  = e.clientY + 10 + "px";
            });
            evtDiv.addEventListener("mouseleave", () => {
              tooltip.style.display = "none";
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

  // Mostra todos os calendários do ano
  function renderYearCalendars() {
    const grid = document.getElementById("calendarsGrid");
    grid.innerHTML = "";
    const year = parseInt(yearSelector.value, 10);
    for (let m = 0; m < 12; m++) {
      grid.appendChild(renderCalendar(year, m));
    }
  }

  // Carrega lembretes e redesenha
  function loadAndRenderCalendars() {
    fetchReminders().then(fetchedReminders => {
      reminders = fetchedReminders;
      renderYearCalendars();
    });
  }

  // Submissão do formulário de lembrete
  const reminderForm = document.getElementById("reminderForm");
  reminderForm.addEventListener("submit", e => {
    e.preventDefault();
    const formData = new FormData(reminderForm);
    fetch(`${baseUrl}/calendar/store`, {
      method: "POST",
      body: formData
    })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          alert(data.message);
          reminderModal.classList.add("hidden");
          reminderForm.reset();
          loadAndRenderCalendars();
        } else {
          alert(data.message || "Erro desconhecido");
        }
      })
      .catch(err => {
        console.error("Erro ao salvar lembrete:", err);
        alert("Falha na comunicação com o servidor");
      });
  });

  // Exibe calendário expandido
  function showExpandedCalendar(year, month) {
    expandedCalendarContent.innerHTML = "";
    expandedCalendarContent.appendChild(renderExpandedCalendar(year, month));
    expandedCalendarModal.classList.remove("hidden");
  }

  // Exibe modal de eventos de um dia
  function showDayEventsModal(cellDate, events) {
    const options = { year: "numeric", month: "long", day: "numeric" };
    const dateString = cellDate.toLocaleDateString("pt-BR", options);

    const modal       = document.getElementById("dayEventsModal");
    const titleEl     = document.getElementById("dayModalTitle");
    const contentEl   = document.getElementById("dayEventsContent");

    titleEl.textContent = `${eventsOn} ${dateString}`;
    contentEl.innerHTML = "";

    events.forEach(evt => {
      const div = document.createElement("div");
      div.className = "p-2 border rounded mb-2";
      div.style.backgroundColor = evt.color || "#3b82f6";
      div.textContent = evt.title;
      contentEl.appendChild(div);
    });

    modal.classList.remove("hidden");

    modal.addEventListener("click", e => {
      if (e.target === modal) modal.classList.add("hidden");
    });
  }

  // Inicialização
  loadAndRenderCalendars();
  yearSelector.addEventListener("change", loadAndRenderCalendars);
});
