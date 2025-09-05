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
      
      if (data.success) {
        const project = data.data;
        
        // Atualiza informações gerais do projeto
        const generalInfo = document.getElementById("projectGeneralInfo");
        if (generalInfo && project) {
          generalInfo.innerHTML = `
            <div class="space-y-4">
              <div>
                <h5 class="font-medium text-gray-900 mb-1">Nome do Projeto</h5>
                <p class="text-gray-600">${project.name || 'Nome não definido'}</p>
              </div>
              <div>
                <h5 class="font-medium text-gray-900 mb-1">Descrição</h5>
                <p class="text-gray-600">${project.description || 'Sem descrição'}</p>
              </div>
              <div>
                <h5 class="font-medium text-gray-900 mb-1">Status</h5>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                  project.active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                }">
                  ${project.active ? 'Ativo' : 'Inativo'}
                </span>
              </div>
            </div>
          `;
        }
        
      } else {
        console.error("Erro ao carregar projeto:", data.message);
      }
    } catch (error) {
      console.error("Erro ao carregar detalhes do projeto:", error);
    }
  },

  async loadTimeEntries(projectId, filter = "all") {
    const container = document.getElementById("timeEntriesList");
    const totalElement = document.getElementById("modalTotalHours");

    if (!container) return;

    // Mostra loading
    container.innerHTML = `
      <div class="flex items-center justify-center py-8">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        <span class="ml-2 text-gray-600">Carregando...</span>
      </div>
    `;

    try {
      let url = `${this.baseUrl}/api/worklog/time-entries?project_id=${projectId}`;
      if (filter !== "all") {
        url += `&filter=${filter}`;
      }

      const response = await fetch(url);
      const data = await response.json();

      this.renderTimeEntries(data, container, totalElement);
    } catch (error) {
      console.error("Erro ao carregar registros:", error);
      container.innerHTML =
        '<div class="text-red-500 text-center py-4">Erro ao carregar registros</div>';
    }
  },

  renderTimeEntries(data, container, totalElement) {
    if (!data.entries || data.entries.length === 0) {
      container.innerHTML = `
        <div class="text-gray-500 text-center py-8">
          <i class="fas fa-clock text-4xl mb-2"></i>
          <p>${this.translations.no_time_entries || "Nenhum registro de ponto"}</p>
        </div>
      `;
      if (totalElement) totalElement.textContent = "0.00h";
      return;
    }

    let html = "";
    data.entries.forEach((entry) => {
      const typeLabel = entry.type === 'new_system' 
        ? '<span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">Novo Sistema</span>'
        : '<span class="text-xs bg-gray-100 text-gray-800 px-2 py-1 rounded">Sistema Antigo</span>';
        
      html += `
        <div class="p-4 flex justify-between items-center hover:bg-gray-50">
          <div class="flex-1">
            <div class="flex items-center space-x-3">
              <div class="w-3 h-3 rounded-full ${entry.type === 'new_system' ? 'bg-blue-500' : 'bg-gray-400'}"></div>
              <div>
                <p class="font-medium text-gray-900">${entry.description}</p>
                <p class="text-sm text-gray-600">${this.formatDate(entry.date)}</p>
                ${typeLabel}
              </div>
            </div>
          </div>
          <div class="text-right flex items-center space-x-3">
            <span class="font-semibold text-gray-900">${entry.hours}h</span>
            <button class="text-red-600 hover:text-red-800 text-sm" onclick="timeTracking.deleteEntry('${entry.id}')">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </div>
      `;
    });

    container.innerHTML = html;
    
    if (totalElement) {
      totalElement.textContent = `${data.total_hours || 0}h`;
    }
  },

  async deleteEntry(entryId) {
    if (!confirm('Tem certeza que deseja excluir este registro?')) {
      return;
    }

    try {
      const response = await fetch(`${this.baseUrl}/api/worklog/delete-time-entry`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          entry_id: entryId
        })
      });

      const data = await response.json();

      if (data.success) {
        // Recarrega a lista
        if (this.currentProjectId) {
          await this.loadTimeEntries(this.currentProjectId, this.currentFilter);
        }
      } else {
        alert(data.message || 'Erro ao excluir registro');
      }
    } catch (error) {
      console.error('Erro ao excluir:', error);
      alert('Erro ao excluir registro');
    }
  },

  formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('pt-BR');
  },

  /**
   * Formata os períodos de trabalho SIMPLIFICADO
   */
  formatPeriods(description, type) {
    if (type === 'old_system') {
      return `<div class="flex items-center"><i class="fas fa-clock mr-2 text-gray-500"></i>${description}</div>`;
    }

    // Para o novo sistema, apenas separa os períodos
    const pairs = description.split(' - ');
    let html = '<div class="space-y-1">';
    
    pairs.forEach((pair, index) => {
      const period = index + 1;
      
      html += `
        <div class="flex items-center space-x-2">
          <i class="fas fa-circle text-blue-400 text-xs"></i>
          <span class="font-medium text-gray-600">Período ${period}:</span>
          <span class="text-gray-800">${pair}</span>
        </div>
      `;
    });
    
    html += '</div>';
    return html;
  },

  async handleTimeEntrySubmit(e) {
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);
    const submitButton = form.querySelector('button[type="submit"]');
    const originalText = submitButton.textContent;

    try {
      submitButton.textContent = "Registrando...";
      submitButton.disabled = true;

      const response = await fetch(`${this.baseUrl}/api/worklog/add-time-entry`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          project_id: formData.get("project_id"),
          date: formData.get("date"),
          time: formData.get("time"),
          type: formData.get("type"),
        }),
      });

      const data = await response.json();

      if (data.success) {
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
      const typeLabel = entry.type === 'new_system' 
        ? '<span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">Novo Sistema</span>'
        : '<span class="text-xs bg-gray-100 text-gray-800 px-2 py-1 rounded">Sistema Antigo</span>';
        
      html += `
        <div class="p-4 flex justify-between items-center hover:bg-gray-50">
          <div class="flex-1">
            <div class="flex items-center space-x-3">
              <div class="w-3 h-3 rounded-full ${entry.type === 'new_system' ? 'bg-blue-500' : 'bg-gray-400'}"></div>
              <div>
                <p class="font-medium text-gray-900">${entry.description}</p>
                <p class="text-sm text-gray-600">${this.formatDate(entry.date)}</p>
                ${typeLabel}
              </div>
            </div>
          </div>
          <div class="text-right flex items-center space-x-3">
            <span class="font-semibold text-gray-900">${entry.hours}h</span>
            <button class="text-red-600 hover:text-red-800 text-sm" onclick="adminTimeTracking.deleteEmployeeEntry('${entry.id}')">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </div>
      `;
    });

    container.innerHTML = html;
    
    if (totalElement) {
      totalElement.textContent = `${data.total_hours || 0}h`;
    }
  },

  async deleteEmployeeEntry(entryId) {
    if (!confirm('Tem certeza que deseja excluir este registro?')) {
      return;
    }

    try {
      const response = await fetch(`${window.baseUrl}/api/worklog/delete-time-entry`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          entry_id: entryId
        })
      });

      const data = await response.json();

      if (data.success) {
        // Recarrega a lista
        if (this.currentEmployeeId) {
          await this.loadEmployeeHours(this.currentEmployeeId);
        }
      } else {
        alert(data.message || 'Erro ao excluir registro');
      }
    } catch (error) {
      console.error('Erro ao excluir:', error);
      alert('Erro ao excluir registro');
    }
  },

  formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('pt-BR');
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

    // Recarrega dados com filtro
    if (this.currentEmployeeId) {
      this.loadEmployeeHours(this.currentEmployeeId, filter);
    }
  },
};

// Inicializa sistema de admin se necessário
document.addEventListener("DOMContentLoaded", () => {
  if (typeof window.adminTimeTracking !== "undefined") {
    window.adminTimeTracking.init();
  }
});