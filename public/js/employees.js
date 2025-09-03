// employees.js - Completo seguindo o padrão da aplicação

document.addEventListener("DOMContentLoaded", function () {
  // Elementos do DOM
  const openModalBtn = document.getElementById("openEmployeeModalBtn");
  const createModal = document.getElementById("employeeCreateModal");
  const detailsModal = document.getElementById("employeeDetailsModal");
  const closeModalBtns = document.querySelectorAll(
    "#closeEmployeeModal, #cancelEmployeeModal"
  );
  const closeDetailsBtns = document.querySelectorAll(
    ".closeEmployeeDetailsModal"
  );
  const employeeCards = document.querySelectorAll(".employee-card");
  const deleteBtn = document.getElementById("deleteEmployeeBtn");

  // Abrir modal de criação
  if (openModalBtn) {
    openModalBtn.addEventListener("click", function () {
      createModal.classList.remove("hidden");
      activateCreateTab("general-create");
    });
  }

  // Fechar modal de criação
  closeModalBtns.forEach((btn) => {
    btn.addEventListener("click", function () {
      createModal.classList.add("hidden");
    });
  });

  // Fechar modal de detalhes
  closeDetailsBtns.forEach((btn) => {
    btn.addEventListener("click", function () {
      detailsModal.classList.add("hidden");
    });
  });

  // Fechar modais ao clicar fora
  [createModal, detailsModal].forEach((modal) => {
    if (modal) {
      modal.addEventListener("click", function (e) {
        if (e.target === modal) {
          modal.classList.add("hidden");
        }
      });
    }
  });

  // Abas do modal de criação
  const createTabBtns = document.querySelectorAll(".tab-btn-create");
  createTabBtns.forEach((btn) => {
    btn.addEventListener("click", function () {
      const tab = this.dataset.tab;
      activateCreateTab(tab);
    });
  });

  // Abas do modal de detalhes
  const detailsTabBtns = document.querySelectorAll(".tab-btn");
  detailsTabBtns.forEach((btn) => {
    btn.addEventListener("click", function () {
      const tab = this.dataset.tab;
      activateDetailsTab(tab);
    });
  });

  // Clique nos cards dos funcionários
  employeeCards.forEach((card) => {
    card.addEventListener("click", function () {
      const employeeId = this.dataset.id;
      if (employeeId) {
        loadEmployeeDetails(employeeId);
      }
    });
  });

  // Botão de delete
  if (deleteBtn) {
    deleteBtn.addEventListener("click", function () {
      const employeeId = document.getElementById("detailsEmployeeId").value;
      if (employeeId && confirm(window.confirmDeleteMsg)) {
        window.location.href = `${window.baseUrl}/employees/delete?id=${employeeId}`;
      }
    });
  }

  // Filtros da aba de horas
  const filterBtns = [
    "adminFilterToday",
    "adminFilterWeek",
    "adminFilterMonth",
    "adminFilterAll",
  ];
  filterBtns.forEach((btnId) => {
    const btn = document.getElementById(btnId);
    if (btn) {
      btn.addEventListener("click", function () {
        const filterMap = {
          adminFilterToday: "today",
          adminFilterWeek: "week",
          adminFilterMonth: "month",
          adminFilterAll: "all",
        };
        const filter = filterMap[btnId];
        window.currentFilter = filter;
        updateFilterButtons(filter);
        renderFilteredHours(filter);
      });
    }
  });

  // Função para ativar aba de criação
  function activateCreateTab(tabName) {
    createTabBtns.forEach((btn) => {
      btn.classList.remove("bg-white", "text-blue-600", "shadow-sm");
      btn.classList.add("text-gray-600", "hover:text-gray-800");
    });

    const activeBtn = document.querySelector(`[data-tab="${tabName}"]`);
    if (activeBtn && activeBtn.classList.contains("tab-btn-create")) {
      activeBtn.classList.add("bg-white", "text-blue-600", "shadow-sm");
      activeBtn.classList.remove("text-gray-600", "hover:text-gray-800");
    }

    const allPanels = document.querySelectorAll(
      "#employeeCreateModal .tab-panel"
    );
    allPanels.forEach((panel) => panel.classList.add("hidden"));

    const activePanel = document.getElementById(`panel-${tabName}`);
    if (activePanel) {
      activePanel.classList.remove("hidden");
    }
  }

  // Função para ativar aba de detalhes
  function activateDetailsTab(tabName) {
    detailsTabBtns.forEach((btn) => {
      btn.classList.remove("bg-blue-100", "text-blue-600", "border-blue-600");
      btn.classList.add(
        "text-gray-600",
        "hover:text-gray-800",
        "border-transparent"
      );
    });

    const activeBtn = document.querySelector(`[data-tab="${tabName}"]`);
    if (activeBtn && activeBtn.classList.contains("tab-btn")) {
      activeBtn.classList.add(
        "bg-blue-100",
        "text-blue-600",
        "border-blue-600"
      );
      activeBtn.classList.remove(
        "text-gray-600",
        "hover:text-gray-800",
        "border-transparent"
      );
    }

    const allPanels = document.querySelectorAll(
      "#employeeDetailsModal .tab-panel"
    );
    allPanels.forEach((panel) => panel.classList.add("hidden"));

    const activePanel = document.getElementById(`panel-${tabName}`);
    if (activePanel) {
      activePanel.classList.remove("hidden");
    }

    // Se for a aba de horas, carregar os dados
    if (tabName === "hours-details" && window.currentEmployeeId) {
      loadEmployeeHours(window.currentEmployeeId);
    }
  }

  // Função para carregar detalhes do funcionário
  async function loadEmployeeDetails(employeeId) {
    try {
      const response = await fetch(
        `${window.baseUrl}/employees/get?id=${employeeId}`
      );
      const employee = await response.json();

      if (employee.error) {
        alert(employee.error);
        return;
      }

      window.currentEmployeeId = employeeId;
      fillEmployeeDetails(employee);
      fillTransactions(employee.transactions || []);
      detailsModal.classList.remove("hidden");
      activateDetailsTab("general-details");
    } catch (error) {
      console.error("Erro ao carregar funcionário:", error);
      alert("Erro ao carregar dados do funcionário");
    }
  }

  // Função para preencher detalhes do funcionário
  function fillEmployeeDetails(employee) {
    document.getElementById("detailsEmployeeId").value = employee.id || "";
    document.getElementById("detailsLoginUserId").value =
      employee.user_id || "";

    const fields = [
      "detailsEmployeeName:name",
      "detailsEmployeeLastName:last_name",
      "detailsEmployeeFunction:function",
      "detailsEmployeeAddress:address",
      "detailsEmployeeCity:city",
      "detailsEmployeeZipCode:zip_code",
      "detailsEmployeeBirthDate:birth_date",
      "detailsEmployeeNationality:nationality",
      "detailsEmployeePhone:phone",
      "detailsEmployeePermissionType:permission_type",
      "detailsEmployeeAhvNumber:ahv_number",
      "detailsEmployeeReligion:religion",
      "detailsEmployeeStartDate:start_date",
      "detailsEmployeeSex:sex",
      "detailsEmployeeMaritalStatus:marital_status",
      "detailsEmployeeAbout:about",
      "detailsLoginEmail:login_email",
      "detailsEmployeeRoleId:role_id",
    ];

    fields.forEach((fieldMap) => {
      const [domId, dataKey] = fieldMap.split(":");
      const element = document.getElementById(domId);
      if (element) {
        element.value = employee[dataKey] || "";
      }
    });

    const documents = [
      "passport",
      "permissionphotofront",
      "permissionphotoback",
      "healthcardfront",
      "healthcardback",
      "bankcardfront",
      "bankcardback",
      "marriagecertificate",
    ];

    documents.forEach((doc) => {
      const img = document.getElementById(
        `view${doc.charAt(0).toUpperCase() + doc.slice(1)}`
      );
      const link = document.getElementById(
        `link${doc.charAt(0).toUpperCase() + doc.slice(1)}`
      );

      if (employee[doc]) {
        if (img) {
          img.src = `${window.baseUrl}/public/${employee[doc]}`;
          img.style.display = "block";
        }
        if (link) {
          link.href = `${window.baseUrl}/public/${employee[doc]}`;
          link.textContent = "Ver documento";
          link.style.display = "block";
        }
      } else {
        if (img) img.style.display = "none";
        if (link) link.style.display = "none";
      }
    });
  }

  // Função para preencher transações - seguindo o padrão existente
  function fillTransactions(transactions) {
    const tbody = document.getElementById("empTransBody");
    if (!tbody) return;

    if (!transactions.length) {
      tbody.innerHTML = `
                <tr>
                    <td colspan="3" class="p-4 text-center text-gray-500">
                        ${window.langText.no_transactions || "Sem transações"}
                    </td>
                </tr>`;
      return;
    }

    tbody.innerHTML = transactions
      .map(
        (tx) => `
            <tr class="border-t">
                <td class="p-2 text-sm">${new Date(tx.date).toLocaleDateString(
                  "pt-BR"
                )}</td>
                <td class="p-2 text-sm">${tx.type || "N/A"}</td>
                <td class="p-2 text-sm text-right">CHF ${parseFloat(
                  tx.amount || 0
                ).toFixed(2)}</td>
            </tr>
        `
      )
      .join("");
  }

  // Carregar horas do funcionário
  async function loadEmployeeHours(employeeId) {
    const tbody = document.getElementById("empHoursBody");
    const totalElement = document.getElementById("employeeModalTotalHours");

    // Mostrar loading seguindo o padrão da tabela
    if (tbody) {
      tbody.innerHTML = `
                <tr>
                    <td colspan="4" class="p-4 text-center text-gray-500">
                        ${
                          window.langText.loading_hours ||
                          "Carregando registros de horas..."
                        }
                    </td>
                </tr>`;
    }

    try {
      const response = await fetch(
        `${window.baseUrl}/api/employees/hours/${employeeId}`
      );
      const data = await response.json();

      if (data.error) {
        throw new Error(data.error);
      }

      window.allHoursData = data.entries || [];
      window.currentFilter = "all";
      updateFilterButtons("all");
      renderFilteredHours("all");
    } catch (error) {
      console.error("Erro ao carregar horas:", error);
      if (tbody) {
        tbody.innerHTML = `
                    <tr>
                        <td colspan="4" class="p-4 text-center text-red-500">
                            ${
                              window.langText.error_loading_hours ||
                              "Erro ao carregar horas"
                            }
                        </td>
                    </tr>`;
      }
    }
  }

  // Filtrar horas por período
  function filterHoursByPeriod(entries, filter) {
    const now = new Date();
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());

    switch (filter) {
      case "today":
        return entries.filter((entry) => {
          const entryDate = new Date(entry.date);
          return entryDate >= today;
        });

      case "week":
        const startOfWeek = new Date(today);
        startOfWeek.setDate(today.getDate() - today.getDay());
        return entries.filter((entry) => {
          const entryDate = new Date(entry.date);
          return entryDate >= startOfWeek;
        });

      case "month":
        const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
        return entries.filter((entry) => {
          const entryDate = new Date(entry.date);
          return entryDate >= startOfMonth;
        });

      default: // 'all'
        return entries;
    }
  }

  // Renderizar horas filtradas - seguindo EXATAMENTE o padrão da tabela de transações
  function renderFilteredHours(filter = "all") {
    const tbody = document.getElementById("empHoursBody");
    const totalElement = document.getElementById("employeeModalTotalHours");

    if (!window.allHoursData || !window.allHoursData.length) {
      if (tbody) {
        tbody.innerHTML = `
                    <tr>
                        <td colspan="4" class="p-4 text-center text-gray-500">
                            ${
                              window.langText.no_hours_registered ||
                              "Nenhuma hora cadastrada"
                            }
                        </td>
                    </tr>`;
      }

      if (totalElement) {
        totalElement.textContent = "0.00h";
      }
      return;
    }

    const filteredEntries = filterHoursByPeriod(window.allHoursData, filter);
    const totalHours = filteredEntries.reduce(
      (sum, entry) => sum + parseFloat(entry.total_hours || 0),
      0
    );

    if (totalElement) {
      totalElement.textContent = totalHours.toFixed(2) + "h";
    }

    if (!tbody) return;

    if (!filteredEntries.length) {
      tbody.innerHTML = `
                <tr>
                    <td colspan="4" class="p-4 text-center text-gray-500">
                        ${
                          window.langText.no_hours_found ||
                          "Nenhum registro encontrado para este período"
                        }
                    </td>
                </tr>`;
      return;
    }

    // Renderizar seguindo exatamente o padrão da tabela de transações
    tbody.innerHTML = filteredEntries
      .map((entry) => {
        const date = new Date(entry.date).toLocaleDateString("pt-BR");
        const schedule = extractScheduleFromFormatted(entry.formatted_display);
        const project = entry.project_name || "-";
        const hours = parseFloat(entry.total_hours).toFixed(2);

        return `
                <tr class="border-t">
                    <td class="p-2 text-sm">${date}</td>
                    <td class="p-2 text-sm">${schedule}</td>
                    <td class="p-2 text-sm">${project}</td>
                    <td class="p-2 text-sm text-right">${hours}h</td>
                </tr>`;
      })
      .join("");
  }

  // Função auxiliar para extrair horário do texto formatado
  function extractScheduleFromFormatted(formatted) {
    if (!formatted) return "-";

    const withoutDate = formatted.replace(/\s*\d{2}\/\d{2}\/\d{4}$/, "");

    if (withoutDate.includes("Sistema antigo:")) {
      return "Sistema antigo";
    }

    return withoutDate || "-";
  }

  // Atualizar aparência dos botões de filtro - seguindo o padrão visual
  function updateFilterButtons(activeFilter) {
    const filters = [
      "adminFilterToday",
      "adminFilterWeek",
      "adminFilterMonth",
      "adminFilterAll",
    ];
    const filterMap = {
      adminFilterToday: "today",
      adminFilterWeek: "week",
      adminFilterMonth: "month",
      adminFilterAll: "all",
    };

    filters.forEach((filterId) => {
      const btn = document.getElementById(filterId);
      if (!btn) return;

      if (filterMap[filterId] === activeFilter) {
        btn.className =
          "px-4 py-2 border rounded-lg text-sm bg-blue-50 text-blue-600 border-blue-300";
      } else {
        btn.className = "px-4 py-2 border rounded-lg hover:bg-gray-100 text-sm";
      }
    });
  }

  // Tornar funções globais
  window.currentEmployeeId = null;
  window.currentFilter = "all";
  window.allHoursData = [];
  window.renderFilteredHours = renderFilteredHours;
  window.updateFilterButtons = updateFilterButtons;
  window.loadEmployeeHours = loadEmployeeHours;
  window.activateDetailsTab = activateDetailsTab;
  window.filterHoursByPeriod = filterHoursByPeriod;
});
