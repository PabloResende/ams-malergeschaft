// public/js/projects.js
const baseUrl = window.location.origin + '/ams-malergeschaft/public';

document.addEventListener("DOMContentLoaded", () => {
  // — MODAL DE CRIAÇÃO
  const projectModal       = document.getElementById("projectModal");
  const addProjectBtn      = document.getElementById("addProjectBtn");
  const closeModal         = document.getElementById("closeModal");
  const projectForm        = document.getElementById("projectForm");

  const tasksContainer     = document.getElementById("tasksContainer");
  const employeesContainer = document.getElementById("employeesContainer");
  const inventoryContainer = document.getElementById("inventoryContainer");

  const newTaskInput       = document.getElementById("newTaskInput");
  const addTaskBtn         = document.getElementById("addTaskBtn");
  const employeeSelect     = document.getElementById("employeeSelect");
  const addEmployeeBtn     = document.getElementById("addEmployeeBtn");
  const inventorySelect    = document.getElementById("inventorySelect");
  const inventoryQuantity  = document.getElementById("inventoryQuantity");
  const addInventoryBtn    = document.getElementById("addInventoryBtn");

  let taskList = [], employeeList = [], inventoryList = [];

  function renderTasks() {
    tasksContainer.innerHTML = "";
    taskList.forEach((t, i) => {
      const row = document.createElement("div");
      row.className = "flex items-center mb-2";
      row.innerHTML = `<input type="checkbox" class="mr-2" ${t.completed ? "checked" : ""}><span>${t.description}</span>`;
      row.querySelector("input").addEventListener("change", e => {
        taskList[i].completed = e.target.checked;
      });
      tasksContainer.appendChild(row);
    });
  }

  function renderEmployees() {
    employeesContainer.innerHTML = "";
    employeeList.forEach((e, i) => {
      const row = document.createElement("div");
      row.className = "flex items-center mb-2";
      row.innerHTML = `<span>${e.name}</span> <button class="ml-2 text-red-500">&times;</button>`;
      row.querySelector("button").addEventListener("click", () => {
        employeeList.splice(i, 1);
        renderEmployees();
      });
      employeesContainer.appendChild(row);
    });
  }

  function renderInventory() {
    inventoryContainer.innerHTML = "";
    inventoryList.forEach((it, i) => {
      const row = document.createElement("div");
      row.className = "flex items-center mb-2";
      row.innerHTML = `<span>${it.name} (Qtd: ${it.quantity})</span> <button class="ml-2 text-red-500">&times;</button>`;
      row.querySelector("button").addEventListener("click", () => {
        inventoryList.splice(i, 1);
        renderInventory();
      });
      inventoryContainer.appendChild(row);
    });
  }

  addProjectBtn.addEventListener("click", () => projectModal.classList.remove("hidden"));
  closeModal.addEventListener("click", () => projectModal.classList.add("hidden"));
  window.addEventListener("click", e => {
    if (e.target === projectModal) projectModal.classList.add("hidden");
  });

  addTaskBtn.addEventListener("click", () => {
    const desc = newTaskInput.value.trim();
    if (!desc) return;
    taskList.push({ description: desc, completed: false });
    newTaskInput.value = "";
    renderTasks();
  });

  addEmployeeBtn.addEventListener("click", () => {
    const id   = employeeSelect.value;
    const name = employeeSelect.selectedOptions[0]?.text;
    if (!id || employeeList.some(e => e.id === id)) return;
    fetch(`${baseUrl}/employees/checkAllocation`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ emp_id: id })
    })
    .then(r => r.json())
    .then(j => {
      if (j.allocated) alert(`Já alocado em ${j.count} projeto(s)!`);
      employeeList.push({ id, name });
      renderEmployees();
    });
  });

  addInventoryBtn.addEventListener("click", () => {
    const id   = inventorySelect.value;
    const name = inventorySelect.selectedOptions[0]?.text;
    const qty  = parseInt(inventoryQuantity.value, 10);
    if (!id || qty <= 0) return;
    const ex = inventoryList.find(i => i.id === id);
    if (ex) ex.quantity += qty;
    else inventoryList.push({ id, name, quantity: qty });
    inventoryQuantity.value = "";
    renderInventory();
  });

  projectForm.addEventListener("submit", () => {
    document.getElementById("tasksData").value      = JSON.stringify(taskList);
    document.getElementById("employeesData").value  = JSON.stringify(employeeList.map(e => e.id));
    document.getElementById("inventoryData").value  = JSON.stringify(inventoryList);
  });

  // — MODAL DE DETALHES / EDIÇÃO
  const detailsModal                  = document.getElementById("projectDetailsModal");
  const closeDetailsBtn               = document.getElementById("closeProjectDetailsModal");
  const cancelDetailsBtn              = document.getElementById("cancelDetailsBtn");
  const detailsProjectIdEl            = document.getElementById("detailsProjectId");
  const detailsProjectNameEl          = document.getElementById("detailsProjectName");
  const detailsProjectLocationEl      = document.getElementById("detailsProjectLocation");
  const detailsProjectTotalHoursEl    = document.getElementById("detailsProjectTotalHours");
  const detailsProjectBudgetEl        = document.getElementById("detailsProjectBudget");
  const detailsProjectEmployeeCountEl = document.getElementById("detailsProjectEmployeeCount");
  const detailsProjectStartDateEl     = document.getElementById("detailsProjectStartDate");
  const detailsProjectEndDateEl       = document.getElementById("detailsProjectEndDate");
  const detailsProjectStatusEl        = document.getElementById("detailsProjectStatus");
  const detailsTasksContainer         = document.getElementById("detailsTasksContainer");
  const detailsEmployeesContainer     = document.getElementById("detailsEmployeesContainer");
  const detailsInventoryContainer     = document.getElementById("detailsInventoryContainer");

  // Fecha o modal de detalhes
  closeDetailsBtn.addEventListener("click", () => detailsModal.classList.add("hidden"));
  cancelDetailsBtn.addEventListener("click", () => detailsModal.classList.add("hidden"));
  window.addEventListener("click", e => {
    if (e.target === detailsModal) detailsModal.classList.add("hidden");
  });

  // Evento de clique nos cards ('.project-item')
  document.querySelectorAll(".project-item").forEach(card => {
    card.addEventListener("click", function(event) {
      event.preventDefault();
      const projectId = this.dataset.projectId || this.getAttribute("data-id");
      console.log(`Projeto selecionado: ID = ${projectId}`);

      fetch(`${baseUrl}/projects/show?id=${projectId}`)
        .then(response => {
          if (!response.ok) throw new Error(`HTTP ${response.status}`);
          return response.json();
        })
        .then(data => {
          console.log("Dados do projeto recebidos:", data);
          // Preenche campos básicos
          detailsProjectIdEl.value            = data.id || "";
          detailsProjectNameEl.value          = data.name || "";
          detailsProjectLocationEl.value      = data.location || "";
          detailsProjectTotalHoursEl.value    = data.total_hours || "";
          detailsProjectBudgetEl.value        = data.budget || "";
          detailsProjectEmployeeCountEl.value = data.employee_count || "";
          detailsProjectStartDateEl.value     = data.start_date || "";
          detailsProjectEndDateEl.value       = data.end_date || "";
          detailsProjectStatusEl.value        = data.status || "";

          // Preenche tarefas (somente leitura)
          detailsTasksContainer.innerHTML = "";
          (data.tasks || []).forEach(t => {
            const row = document.createElement("div");
            row.className = "flex items-center mb-2";
            row.innerHTML = `<input type="checkbox" class="mr-2" ${t.completed ? "checked" : ""} disabled><span>${t.description}</span>`;
            detailsTasksContainer.appendChild(row);
          });
          // Preenche funcionários
          detailsEmployeesContainer.innerHTML = "";
          (data.employees || []).forEach(e => {
            const row = document.createElement("div");
            row.className = "flex items-center mb-2";
            row.innerHTML = `<span>${e.name} ${e.last_name}</span>`;
            detailsEmployeesContainer.appendChild(row);
          });
          // Preenche inventário
          detailsInventoryContainer.innerHTML = "";
          (data.inventory || []).forEach(i => {
            const row = document.createElement("div");
            row.className = "flex items-center mb-2";
            row.innerHTML = `<span>${i.name} (Qtd: ${i.quantity})</span>`;
            detailsInventoryContainer.appendChild(row);
          });

          // Exibe o modal
          detailsModal.classList.remove("hidden");
        })
        .catch(error => {
          console.error("Erro ao buscar detalhes do projeto:", error);
          alert("Não foi possível carregar os detalhes do projeto.");
        });
    });
  });

});
