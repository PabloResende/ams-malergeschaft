// public/js/employee-time-tracking.js - Sistema de Ponto Completo
window.timeTracking = {
  baseUrl: window.baseUrl || "",
  translations: window.translations || {},
  currentProjectId: null,
  currentFilter: "all",

  init() {
    console.log("🔧 Inicializando sistema de ponto");
    this.setupEventListeners();
    this.loadInitialData();
  },

  setupEventListeners() {
    // Formulário de registro de ponto
    const form = document.getElementById("timeTrackingForm");
    if (form) {
      form.addEventListener("submit", (e) => this.handleTimeEntrySubmit(e));
    }

    // Clique nos projetos para abrir modal
    document.querySelectorAll(".project-item").forEach((card) => {
      card.addEventListener("click", () => {
        const id = card.getAttribute("data-project-id");
        this.openProjectModal(id);
      });
    });

    // Fechar modal
    const modal = document.getElementById("projectDetailsModal");
    if (modal) {
      modal.addEventListener("click", (e) => {
        if (e.target === modal || e.target.closest(".close-modal")) {
          this.closeModal();
        }
      });

      // ESC para fechar
      document.addEventListener("keydown", (e) => {
        if (e.key === "Escape" && !modal.classList.contains("hidden")) {
          this.closeModal();
        }
      });
    }

    // Tabs do modal
    this.setupTabs();

    // Filtros (se existirem)
    this.setupFilters();
  },

  setupTabs() {
    const tabs = document.querySelectorAll("[data-tab]");
    const panels = document.querySelectorAll(".tab-panel");

    tabs.forEach((tab) => {
      tab.addEventListener("click", () => {
        const targetId = tab.getAttribute("data-tab");

        // Remove active de todas as tabs
        tabs.forEach((t) =>
          t.classList.remove("border-blue-500", "text-blue-600")
        );
        tabs.forEach((t) =>
          t.classList.add("border-transparent", "text-gray-500")
        );

        // Adiciona active na tab clicada
        tab.classList.remove("border-transparent", "text-gray-500");
        tab.classList.add("border-blue-500", "text-blue-600");

        // Esconde todos os painéis
        panels.forEach((panel) => panel.classList.add("hidden"));

        // Mostra o painel ativo
        const activePanel = document.getElementById(targetId);
        if (activePanel) {
          activePanel.classList.remove("hidden");

          // Se for a aba de ponto, carrega os dados
          if (targetId === "tab-ponto" && this.currentProjectId) {
            this.loadTimeEntries(this.currentProjectId);
          }
        }
      });
    });
  },

  setupFilters() {
    const filterButtons = document.querySelectorAll('[id^="adminFilter"]');
    filterButtons.forEach((btn) => {
      btn.addEventListener("click", () => {
        const filter = btn.id.replace("adminFilter", "").toLowerCase();
        this.applyFilter(filter, btn);
      });
    });
  },

  openProjectModal(projectId) {
    const modal = document.getElementById("projectDetailsModal");
    if (!modal) return;

    this.currentProjectId = projectId;
    modal.classList.remove("hidden");
    modal.classList.add("flex");

    // Define o project_id no formulário
    const projectIdInput = document.getElementById("timeTrackingProjectId");
    if (projectIdInput) {
      projectIdInput.value = projectId;
    }

    // Carrega detalhes do projeto
    this.loadProjectDetails(projectId);
  },

  closeModal() {
    const modal = document.getElementById("projectDetailsModal");
    if (modal) {
      modal.classList.add("hidden");
      modal.classList.remove("flex");
    }
    this.currentProjectId = null;
  },

  async loadProjectDetails(projectId) {
    try {
      const response = await fetch(`${this.baseUrl}/api/projects/${projectId}`);
      const data = await response.json();

      // Preenche dados gerais
      this.updateElement("roName", data.name || "");
      this.updateElement("roClient", data.client_name || "—");
      this.updateElement("roLocation", data.location || "—");
      this.updateElement("roStart", data.start_date || "");
      this.updateElement("roEnd", data.end_date || "");

      // Preenche tarefas
      this.updateList(
        "roTasks",
        data.tasks || [],
        "description",
        "Nenhuma tarefa"
      );

      // Preenche funcionários
      this.updateList(
        "roEmployees",
        data.employees || [],
        "name",
        "Nenhum funcionário"
      );

      // Preenche inventário
      this.updateList(
        "roInventory",
        data.inventory || [],
        "name",
        "Nenhum item"
      );

      // Ativa a primeira aba
      const firstTab = document.querySelector("[data-tab]");
      if (firstTab) {
        firstTab.click();
      }
    } catch (error) {
      console.error("Erro ao carregar detalhes do projeto:", error);
    }
  },

  async loadTimeEntries(projectId, filter = "all") {
    const container = document.getElementById("timeEntriesList");
    const totalElement = document.getElementById("workLogTotal");

    if (!container) return;

    // Loading state
    container.innerHTML = `
      <div class="flex items-center justify-center py-8">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        <span class="ml-2 text-gray-600">Carregando registros...</span>
      </div>
    `;

    try {
      let url = `${this.baseUrl}/api/work_logs/time_entries/${projectId}`;
      if (filter && filter !== "all") {
        url += `?filter=${filter}`;
      }

      const response = await fetch(url, {
        credentials: "same-origin",
      });
      const data = await response.json();

      this.renderTimeEntries(data, container, totalElement);
    } catch (error) {
      console.error("Erro ao carregar registros:", error);
      container.innerHTML = `
        <div class="text-red-500 text-center py-4">
          <i class="fas fa-exclamation-triangle"></i>
          Erro ao carregar registros
        </div>
      `;
    }
  },

  renderTimeEntries(data, container, totalElement) {
    if (!data.entries || data.entries.length === 0) {
      container.innerHTML = `
        <div class="text-gray-500 text-center py-8">
          <i class="fas fa-clock text-4xl mb-2"></i>
          <p>Nenhum registro de ponto encontrado</p>
        </div>
      `;
      if (totalElement) totalElement.textContent = "0.00";
      return;
    }

    // Agrupa por data
    const groupedByDate = this.groupEntriesByDate(data.entries);

    let html = "";
    Object.keys(groupedByDate)
      .sort((a, b) => new Date(b) - new Date(a)) // Mais recente primeiro
      .forEach((date) => {
        const dayEntries = groupedByDate[date];
        const totalHours = dayEntries.reduce(
          (sum, entry) => sum + parseFloat(entry.total_hours || 0),
          0
        );
        const dateFormatted = new Date(date).toLocaleDateString("pt-BR");

        html += `
          <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
              <div class="flex justify-between items-center">
                <h4 class="font-semibold text-gray-900">${dateFormatted}</h4>
                <span class="bg-blue-100 text-blue-800 text-sm px-2 py-1 rounded-full">
                  ${totalHours.toFixed(2)}h
                </span>
              </div>
            </div>
            <div class="p-4">
              ${dayEntries
                .map((entry) => this.renderSingleEntry(entry))
                .join("")}
            </div>
          </div>
        `;
      });

    container.innerHTML = html;

    if (totalElement) {
      totalElement.textContent = data.total_hours || "0.00";
    }
  },

  groupEntriesByDate(entries) {
    return entries.reduce((groups, entry) => {
      const date = entry.date;
      if (!groups[date]) {
        groups[date] = [];
      }
      groups[date].push(entry);
      return groups;
    }, {});
  },

  renderSingleEntry(entry) {
    return `
      <div class="flex justify-between items-center py-2 border-b border-gray-100 last:border-b-0">
        <div class="flex-1">
          <div class="text-gray-900 font-medium">
            ${entry.formatted_display || this.formatEntryDisplay(entry)}
          </div>
        </div>
        <div class="ml-4 text-right">
          <div class="text-sm text-gray-600">
            ${parseFloat(entry.total_hours || 0).toFixed(2)}h
          </div>
        </div>
      </div>
    `;
  },

  formatEntryDisplay(entry) {
    // Fallback caso não tenha formatted_display
    if (entry.entries && entry.entries.length > 0) {
      const pairs = [];
      let currentEntry = null;

      entry.entries.forEach((e) => {
        if (e.type === "entry") {
          if (currentEntry) {
            pairs.push(`entrada ${currentEntry} saída ?`);
          }
          currentEntry = e.time;
        } else if (e.type === "exit") {
          if (currentEntry) {
            pairs.push(`entrada ${currentEntry} saída ${e.time}`);
            currentEntry = null;
          } else {
            pairs.push(`entrada ? saída ${e.time}`);
          }
        }
      });

      if (currentEntry) {
        pairs.push(`entrada ${currentEntry} saída ?`);
      }

      return pairs.join(" - ");
    }

    return "Registro inválido";
  },

  async handleTimeEntrySubmit(e) {
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);
    const submitButton = form.querySelector('button[type="submit"]');
    const originalText = submitButton.textContent;

    // Estado de loading
    submitButton.disabled = true;
    submitButton.innerHTML = `
      <div class="flex items-center">
        <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
        Registrando...
      </div>
    `;

    try {
      const response = await fetch(form.action, {
        method: "POST",
        body: formData,
        credentials: "same-origin",
      });

      const data = await response.json();

      if (data.success) {
        // Feedback de sucesso
        this.showSuccessMessage(submitButton, originalText, "Registrado!");

        // Recarrega a lista
        if (this.currentProjectId) {
          await this.loadTimeEntries(this.currentProjectId, this.currentFilter);
        }

        // Atualiza o horário para o atual (mantém data)
        const timeInput = form.querySelector('[name="time"]');
        if (timeInput) {
          timeInput.value = new Date().toTimeString().substring(0, 5);
        }

        // Se houver callback de sucesso
        if (window.onTimeEntrySuccess) {
          window.onTimeEntrySuccess(data);
        }
      } else {
        throw new Error(data.message || "Erro ao registrar ponto");
      }
    } catch (error) {
      console.error("Erro ao registrar ponto:", error);
      this.showErrorMessage(submitButton, originalText, "Erro!");

      // Mostra alerta
      alert(error.message || "Erro ao registrar ponto");
    }
  },

  showSuccessMessage(button, originalText, successText) {
    button.textContent = successText;
    button.classList.remove("bg-blue-600", "hover:bg-blue-700");
    button.classList.add("bg-green-600", "hover:bg-green-700");
    button.disabled = false;

    setTimeout(() => {
      button.textContent = originalText;
      button.classList.remove("bg-green-600", "hover:bg-green-700");
      button.classList.add("bg-blue-600", "hover:bg-blue-700");
    }, 2000);
  },

  showErrorMessage(button, originalText, errorText) {
    button.textContent = errorText;
    button.classList.remove("bg-blue-600", "hover:bg-blue-700");
    button.classList.add("bg-red-600", "hover:bg-red-700");
    button.disabled = false;

    setTimeout(() => {
      button.textContent = originalText;
      button.classList.remove("bg-red-600", "hover:bg-red-700");
      button.classList.add("bg-blue-600", "hover:bg-blue-700");
    }, 2000);
  },

  applyFilter(filter, buttonElement) {
    this.currentFilter = filter;

    // Atualiza visual dos botões
    document.querySelectorAll('[id^="adminFilter"]').forEach((btn) => {
      btn.classList.remove("bg-blue-100", "text-blue-700");
      btn.classList.add("bg-gray-100", "text-gray-700");
    });

    if (buttonElement) {
      buttonElement.classList.remove("bg-gray-100", "text-gray-700");
      buttonElement.classList.add("bg-blue-100", "text-blue-700");
    }

    // Recarrega dados com filtro
    if (this.currentProjectId) {
      this.loadTimeEntries(this.currentProjectId, filter);
    }
  },

  updateElement(elementId, content) {
    const element = document.getElementById(elementId);
    if (element) {
      element.textContent = content;
    }
  },

  updateList(elementId, items, property, emptyMessage) {
    const element = document.getElementById(elementId);
    if (!element) return;

    if (!items || items.length === 0) {
      element.innerHTML = `<li class="text-gray-500">${emptyMessage}</li>`;
      return;
    }

    element.innerHTML = items
      .map(
        (item) =>
          `<li>${typeof item === "string" ? item : item[property] || ""}</li>`
      )
      .join("");
  },

  loadInitialData() {
    // Se há um projeto selecionado por padrão
    const defaultProject = document.querySelector(".project-item");
    if (defaultProject && !this.currentProjectId) {
      const projectId = defaultProject.getAttribute("data-project-id");
      if (projectId) {
        this.currentProjectId = projectId;

        // Define no formulário se existir
        const projectIdInput = document.getElementById("timeTrackingProjectId");
        if (projectIdInput) {
          projectIdInput.value = projectId;
        }
      }
    }
  },
};

// Para compatibilidade com o código existente
window.loadTimeEntries = (projectId) => {
  window.timeTracking.loadTimeEntries(projectId);
};

// Inicialização automática
document.addEventListener("DOMContentLoaded", () => {
  if (typeof window.timeTracking !== "undefined") {
    window.timeTracking.init();
  }
});

// Sistema para administradores (adicional)
window.adminTimeTracking = {
  currentEmployeeId: null,

  init() {
    this.setupAdminEventListeners();
  },

  setupAdminEventListeners() {
    // Modal de funcionário
    document.addEventListener("click", (e) => {
      if (e.target.closest(".view-employee-hours")) {
        const employeeId = e.target.closest(".view-employee-hours").dataset
          .employeeId;
        this.openEmployeeModal(employeeId);
      }
    });

    // Filtros de admin
    document.querySelectorAll('[id^="adminFilter"]').forEach((btn) => {
      btn.addEventListener("click", () => {
        const filter = btn.id.replace("adminFilter", "").toLowerCase();
        this.applyEmployeeFilter(filter, btn);
      });
    });
  },

  async openEmployeeModal(employeeId) {
    this.currentEmployeeId = employeeId;

    const modal = document.getElementById("employeeModal");
    if (modal) {
      modal.classList.remove("hidden");
      modal.classList.add("flex");

      // Carrega dados do funcionário
      await this.loadEmployeeHours(employeeId);
    }
  },

  async loadEmployeeHours(employeeId, filter = "all") {
    const container = document.getElementById("employeeHoursList");
    const totalElement = document.getElementById("employeeModalTotalHours");

    if (!container) return;

    container.innerHTML = `
      <div class="flex items-center justify-center py-8">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        <span class="ml-2 text-gray-600">Carregando...</span>
      </div>
    `;

    try {
      let url = `${window.baseUrl}/api/employees/hours/${employeeId}`;
      if (filter !== "all") {
        url += `?filter=${filter}`;
      }

      const response = await fetch(url);
      const data = await response.json();

      this.renderEmployeeHours(data, container, totalElement);
    } catch (error) {
      console.error("Erro ao carregar horas do funcionário:", error);
      container.innerHTML =
        '<div class="text-red-500 text-center py-4">Erro ao carregar dados</div>';
    }
  },

  renderEmployeeHours(data, container, totalElement) {
    if (!data.entries || data.entries.length === 0) {
      container.innerHTML = `
        <div class="text-gray-500 text-center py-8">
          <i class="fas fa-clock text-4xl mb-2"></i>
          <p>Nenhum registro encontrado</p>
        </div>
      `;
      if (totalElement) totalElement.textContent = "0.00h";
      return;
    }

    let html = "";
    data.entries.forEach((entry) => {
      html += `
        <div class="bg-gray-50 rounded-lg p-4 mb-3">
          <div class="flex justify-between items-start mb-2">
            <div>
              <h4 class="font-semibold text-gray-900">${
                entry.project_name || "Projeto não encontrado"
              }</h4>
              <p class="text-sm text-gray-600">${new Date(
                entry.date
              ).toLocaleDateString("pt-BR")}</p>
            </div>
            <div class="text-right">
              <span class="bg-blue-100 text-blue-800 text-sm px-2 py-1 rounded-full">
                ${parseFloat(entry.total_hours).toFixed(2)}h
              </span>
            </div>
          </div>
          <div class="text-sm text-gray-700">
            ${entry.formatted_display || "Registro sem detalhes"}
          </div>
        </div>
      `;
    });

    container.innerHTML = html;

    if (totalElement) {
      totalElement.textContent = `${data.total_hours || "0.00"}h`;
    }
  },

  applyEmployeeFilter(filter, buttonElement) {
    // Atualiza visual dos botões
    document.querySelectorAll('[id^="adminFilter"]').forEach((btn) => {
      btn.classList.remove("bg-blue-100", "text-blue-700");
      btn.classList.add("bg-gray-100", "text-gray-700");
    });

    if (buttonElement) {
      buttonElement.classList.remove("bg-gray-100", "text-gray-700");
      buttonElement.classList.add("bg-blue-100", "text-blue-700");
    }

    // Recarrega dados
    if (this.currentEmployeeId) {
      this.loadEmployeeHours(this.currentEmployeeId, filter);
    }
  },
};

// Inicialização do sistema admin
document.addEventListener("DOMContentLoaded", () => {
  if (typeof window.adminTimeTracking !== "undefined") {
    window.adminTimeTracking.init();
  }
});
