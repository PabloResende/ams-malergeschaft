// public/js/employee-time-tracking.js - Sistema de Ponto Completo

console.log('🔧 employee-time-tracking.js carregado');

window.timeTracking = {
  baseUrl: window.baseUrl || "",
  translations: window.langText || {},
  currentProjectId: null,
  currentFilter: "today",
  currentEmployeeId: null,

  // ========== INICIALIZAÇÃO ==========
  init() {
    console.log("🚀 Inicializando sistema de ponto");
    this.setupEventListeners();
    this.loadInitialData();
  },

  // ========== EVENT LISTENERS ==========
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

    // Filtros
    this.setupFilters();
  },

  // ========== SISTEMA DE TABS ==========
  setupTabs() {
    const tabs = document.querySelectorAll("[data-tab]");
    const panels = document.querySelectorAll(".tab-panel");

    tabs.forEach((tab) => {
      tab.addEventListener("click", () => {
        const targetId = tab.getAttribute("data-tab");

        // Remove active de todas as tabs
        tabs.forEach((t) => {
          t.classList.remove("border-blue-500", "text-blue-600");
          t.classList.add("border-transparent", "text-gray-500");
        });

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

  // ========== SISTEMA DE FILTROS ==========
  setupFilters() {
    const filterButtons = document.querySelectorAll('[id^="adminFilter"]');
    filterButtons.forEach((btn) => {
      btn.addEventListener("click", () => {
        const filter = btn.id.replace("adminFilter", "").toLowerCase();
        this.applyFilter(filter, btn);
      });
    });
  },

  applyFilter(filter, clickedBtn) {
    this.currentFilter = filter;
    
    // Atualiza visual dos botões
    const allFilterBtns = document.querySelectorAll('[id^="adminFilter"]');
    allFilterBtns.forEach(btn => {
      btn.classList.remove('bg-blue-100', 'text-blue-700');
      btn.classList.add('bg-gray-100', 'text-gray-700');
    });
    
    clickedBtn.classList.remove('bg-gray-100', 'text-gray-700');
    clickedBtn.classList.add('bg-blue-100', 'text-blue-700');
    
    // Recarrega dados
    if (this.currentProjectId) {
      this.loadTimeEntries(this.currentProjectId, filter);
    }
  },

  // ========== MODAL DE PROJETO ==========
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
    
    // Carrega registros de tempo
    this.loadTimeEntries(projectId);
  },

  closeModal() {
    const modal = document.getElementById("projectDetailsModal");
    if (modal) {
      modal.classList.add("hidden");
      modal.classList.remove("flex");
      this.currentProjectId = null;
    }
  },

  // ========== CARREGAR DADOS ==========
  async loadInitialData() {
    // Se estivermos na página do funcionário, pegar o ID da sessão
    const userEmail = window.userEmail;
    if (userEmail) {
      this.currentEmployeeId = await this.getEmployeeIdByEmail(userEmail);
    }
  },

  async getEmployeeIdByEmail(email) {
    try {
      const response = await fetch(`${this.baseUrl}/api/employees/by-email?email=${encodeURIComponent(email)}`);
      if (response.ok) {
        const data = await response.json();
        return data.employee_id;
      }
    } catch (error) {
      console.error('Erro ao buscar ID do funcionário:', error);
    }
    return null;
  },

  // Atualizar a função loadProjectDetails (se estiver usando)
async loadProjectDetails(projectId) {
  try {
    const response = await fetch(`${baseUrl}/api/projects/${projectId}`);
    const result = await response.json();
    
    if (result.success && result.project) {
      const project = result.project;
      
      // Atualiza informações do projeto incluindo horas totais
      const generalInfo = document.getElementById("projectGeneralInfo");
      if (generalInfo) {
        generalInfo.innerHTML = `
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <h5 class="font-semibold text-gray-900">${project.name}</h5>
              <p class="text-gray-600 mt-1">${project.description || 'Sem descrição'}</p>
            </div>
            <div>
              <p class="text-sm text-gray-600">
                <strong>Cliente:</strong> ${project.client_name || 'Não definido'}
              </p>
              <p class="text-sm text-gray-600">
                <strong>Status:</strong> ${project.status}
              </p>
              <p class="text-sm text-gray-600">
                <strong>Total de Horas:</strong> ${project.total_hours_calculated}h
              </p>
            </div>
          </div>
          
          ${project.employees_hours && project.employees_hours.length > 0 ? `
            <div class="mt-4">
              <h6 class="font-medium text-gray-800 mb-2">Horas por Funcionário:</h6>
              <div class="space-y-1">
                ${project.employees_hours.map(emp => `
                  <div class="flex justify-between text-sm">
                    <span>${emp.name} ${emp.last_name}</span>
                    <span>${parseFloat(emp.hours_worked).toFixed(1)}h</span>
                  </div>
                `).join('')}
              </div>
            </div>
          ` : ''}
        `;
      }
    }
  } catch (error) {
    console.error('Erro ao carregar detalhes do projeto:', error);
  }
},

  async loadTimeEntries(projectId, filter = null) {
    const currentFilter = filter || this.currentFilter;
    const entriesList = document.querySelector('#projectDetailsModal .tab-panel:not(.hidden) #timeEntriesList');
    
    if (!entriesList) return;

    try {
      entriesList.innerHTML = '<div class="p-4 text-center text-gray-500">Carregando registros...</div>';

      // Para funcionários, usar seu próprio ID
      const employeeParam = this.currentEmployeeId ? `&employee_id=${this.currentEmployeeId}` : '';
      const response = await fetch(`${this.baseUrl}/api/worklog/time-entries-by-day?project_id=${projectId}&filter=${currentFilter}${employeeParam}`);

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }

      const dayEntries = await response.json();

      if (dayEntries.length === 0) {
        entriesList.innerHTML = '<div class="p-4 text-center text-gray-500">Nenhum registro encontrado</div>';
        return;
      }

      // Renderizar registros organizados por dia
      let html = '';
      let totalHours = 0;

      dayEntries.forEach(day => {
        const formattedDate = this.formatDate(day.date);
        totalHours += day.total_hours;

        html += `
          <div class="border-b border-gray-100 p-4">
            <div class="flex justify-between items-center mb-3">
              <h6 class="font-semibold text-gray-900">${formattedDate}</h6>
              <span class="text-sm font-medium text-blue-600">${day.total_hours.toFixed(2)}h</span>
            </div>
        `;

        // Agrupar em períodos
        const periods = this.groupIntoPeriods(day.entries);
        
        if (periods.length === 0) {
          html += '<div class="text-sm text-gray-500">Registros incompletos</div>';
        } else {
          periods.forEach((period, index) => {
            html += `
              <div class="flex items-center justify-between text-sm mb-1">
                <span class="text-gray-600">Período ${index + 1}:</span>
                <span class="font-medium">${period.entry} - ${period.exit || 'Em aberto'}</span>
              </div>
            `;
          });
        }

        html += '</div>';
      });

      entriesList.innerHTML = html;

      // Atualizar total geral
      const totalElement = document.querySelector('#projectDetailsModal .tab-panel:not(.hidden) .text-2xl');
      if (totalElement) {
        totalElement.textContent = `${totalHours.toFixed(2)}h`;
      }

    } catch (error) {
      console.error('Erro ao carregar registros:', error);
      entriesList.innerHTML = '<div class="p-4 text-center text-red-500">Erro ao carregar registros</div>';
    }
  },

  // ========== REGISTRAR PONTO ==========
  async handleTimeEntrySubmit(e) {
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);
    const submitButton = form.querySelector('button[type="submit"]');
    const originalText = submitButton.textContent;

    // Validações
    if (!this.currentProjectId) {
      this.showNotification('Projeto não identificado', 'error');
      return;
    }

    try {
      submitButton.textContent = "Registrando...";
      submitButton.disabled = true;

      const requestData = {
        project_id: this.currentProjectId,
        date: formData.get("date"),
        time: formData.get("time"),
        type: formData.get("type"),
      };

      // Se for admin, incluir employee_id
      const employeeIdInput = document.getElementById("timeTrackingEmployeeId");
      if (employeeIdInput && employeeIdInput.value) {
        requestData.employee_id = employeeIdInput.value;
      }

      const response = await fetch(`${this.baseUrl}/api/worklog/add-time-entry`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(requestData),
      });

      const data = await response.json();

      if (data.success) {
        this.showSuccessMessage(submitButton, originalText, "Registrado!");
        
        // Reset form mantendo data atual
        form.reset();
        const dateInput = form.querySelector('[name="date"]');
        const timeInput = form.querySelector('[name="time"]');
        if (dateInput) dateInput.value = new Date().toISOString().split('T')[0];
        if (timeInput) timeInput.value = new Date().toTimeString().slice(0, 5);

        // Recarregar dados
        this.loadTimeEntries(this.currentProjectId, this.currentFilter);
      } else {
        this.showNotification(data.message || "Erro ao registrar ponto", "error");
        submitButton.textContent = originalText;
        submitButton.disabled = false;
      }
    } catch (error) {
      console.error("Erro ao registrar ponto:", error);
      this.showNotification("Erro de conexão", "error");
      submitButton.textContent = originalText;
      submitButton.disabled = false;
    }
  },

  // ========== UTILITÁRIOS ==========
  groupIntoPeriods(entries) {
    const periods = [];
    let currentPeriod = null;

    entries.forEach(entry => {
      if (entry.entry_type === 'entry') {
        currentPeriod = { entry: entry.time, exit: null };
      } else if (entry.entry_type === 'exit' && currentPeriod) {
        currentPeriod.exit = entry.time;
        periods.push(currentPeriod);
        currentPeriod = null;
      }
    });

    return periods;
  },

  formatDate(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('pt-BR');
  },

  getStatusText(status) {
    const statusMap = {
      'in_progress': 'Em Andamento',
      'pending': 'Pendente',
      'completed': 'Concluído',
      'archived': 'Arquivado'
    };
    return statusMap[status] || status;
  },

  showSuccessMessage(button, originalText, successText) {
    button.textContent = successText;
    button.classList.add('bg-green-600');
    
    setTimeout(() => {
      button.textContent = originalText;
      button.classList.remove('bg-green-600');
      button.disabled = false;
    }, 2000);
  },

  showNotification(message, type = 'info') {
    // Remove notificação existente
    const existing = document.querySelector('.time-tracking-notification');
    if (existing) {
      existing.remove();
    }
    
    // Cria nova notificação
    const notification = document.createElement('div');
    notification.className = `time-tracking-notification fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 transition-all duration-300 ${
      type === 'success' ? 'bg-green-500 text-white' :
      type === 'error' ? 'bg-red-500 text-white' :
      'bg-blue-500 text-white'
    }`;
    
    notification.innerHTML = `
      <div class="flex items-center space-x-2">
        <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
        <span>${message}</span>
        <button onclick="this.parentElement.parentElement.remove()" class="ml-2 text-white hover:text-gray-200">
          <i class="fas fa-times"></i>
        </button>
      </div>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remover após 4 segundos
    setTimeout(() => {
      if (notification.parentNode) {
        notification.remove();
      }
    }, 4000);
  }
};

// ========== AUTO-INICIALIZAÇÃO ==========
document.addEventListener('DOMContentLoaded', () => {
  if (typeof window.timeTracking !== 'undefined') {
    window.timeTracking.init();
  }
});

console.log('✅ Sistema de time tracking carregado com sucesso');