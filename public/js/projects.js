// public/js/projects.js
const baseUrl = window.location.origin + '/ams-malergeschaft/public';

document.addEventListener("DOMContentLoaded", () => {
  // —————————————— MODAL DE CRIAÇÃO ——————————————
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

  let taskList      = [];
  let employeeList  = [];
  let inventoryList = [];

  function renderTasks() {
    tasksContainer.innerHTML = "";
    taskList.forEach((t, i) => {
      const row = document.createElement("div");
      row.className = "flex items-center mb-2";
      row.innerHTML = `<input type="checkbox" class="mr-2" ${t.completed ? "checked" : ""}><span>${t.description}</span>`;
      const cb = row.querySelector("input");
      cb.addEventListener("change", e => {
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
      row.innerHTML = `<span>${e.name}</span><button class="ml-2 text-red-500">&times;</button>`;
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
      row.innerHTML = `<span>${it.name} (Qtd: ${it.quantity})</span><button class="ml-2 text-red-500">&times;</button>`;
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
    const selected  = inventorySelect.selectedOptions[0];
    const available = parseInt(selected?.dataset.stock || "0", 10);
    const qty       = parseInt(inventoryQuantity.value, 10);
    if (qty > available) {
      alert(`Quantidade excede o estoque disponível (${available}).`);
      return;
    }
    const id   = inventorySelect.value;
    const name = selected.text;
    if (!id) return;
    const ex = inventoryList.find(i => i.id === id);
    if (ex) {
      ex.quantity += qty;
    } else {
      inventoryList.push({ id, name, quantity: qty });
    }
    inventoryQuantity.value = "";
    renderInventory();
  });

  projectForm.addEventListener("submit", () => {
    document.getElementById("tasksData").value     = JSON.stringify(taskList);
    document.getElementById("employeesData").value = JSON.stringify(employeeList.map(e => e.id));
    document.getElementById("inventoryData").value = JSON.stringify(inventoryList);
  });

  // —————————————— MODAL DE DETALHES / EDIÇÃO ——————————————
  const detailsModal               = document.getElementById("projectDetailsModal");
  const closeDetailsBtn            = document.getElementById("closeProjectDetailsModal");
  const cancelDetailsBtn           = document.getElementById("cancelDetailsBtn");
  const deleteProjectBtn           = document.getElementById("deleteProjectBtn");
  const detailsForm                = document.getElementById("projectDetailsForm");
  const saveDetailsBtn             = document.getElementById("saveDetailsBtn");

  const detailsProjectIdEl         = document.getElementById("detailsProjectId");
  const detailsProjectNameEl       = document.getElementById("detailsProjectName");
  const detailsProjectLocationEl   = document.getElementById("detailsProjectLocation");
  const detailsProjectTotalHoursEl = document.getElementById("detailsProjectTotalHours");
  const detailsProjectBudgetEl     = document.getElementById("detailsProjectBudget");
  const detailsProjectEmployeeCountEl = document.getElementById("detailsProjectEmployeeCount");
  const detailsProjectStartDateEl  = document.getElementById("detailsProjectStartDate");
  const detailsProjectEndDateEl    = document.getElementById("detailsProjectEndDate");
  const detailsProjectStatusEl     = document.getElementById("detailsProjectStatus");

  const detailsProgressBar         = document.getElementById("detailsProgressBar");
  const detailsProgressText        = document.getElementById("detailsProgressText");

  const detailsTasksContainer      = document.getElementById("detailsTasksContainer");
  const detailsEmployeesContainer  = document.getElementById("detailsEmployeesContainer");
  const detailsInventoryContainer  = document.getElementById("detailsInventoryContainer");

  const detailsNewTaskInput        = document.getElementById("detailsNewTaskInput");
  const detailsAddTaskBtn          = document.getElementById("detailsAddTaskBtn");
  const detailsEmployeeSelect      = document.getElementById("detailsEmployeeSelect");
  const detailsAddEmployeeBtn      = document.getElementById("detailsAddEmployeeBtn");
  const detailsInventorySelect     = document.getElementById("detailsInventorySelect");
  const detailsInventoryQuantity   = document.getElementById("detailsInventoryQuantity");
  const detailsAddInventoryBtn     = document.getElementById("detailsAddInventoryBtn");

  const detailsTasksData           = document.getElementById("detailsTasksData");
  const detailsEmployeesData       = document.getElementById("detailsEmployeesData");
  const detailsInventoryData       = document.getElementById("detailsInventoryData");

  let detailsTaskList      = [];
  let detailsEmployeeList  = [];
  let detailsInventoryList = [];

  function updateDetailsProgress() {
    const total = detailsTaskList.length;
    const done  = detailsTaskList.filter(t => t.completed).length;
    const pct   = total ? Math.round(done / total * 100) : 0;

    detailsProgressBar.style.width       = pct + "%";
    detailsProgressText.innerText        = pct + "%";
    detailsProjectStatusEl.disabled      = pct < 100;
    // insere/atualiza hidden progress
    const prev = detailsForm.querySelector("input[name=progress]");
    if (prev) prev.value = pct;
    else {
      const h = document.createElement("input");
      h.type  = "hidden";
      h.name  = "progress";
      h.value = pct;
      detailsForm.appendChild(h);
    }
  }

  function renderDetailsTasks() {
    detailsTasksContainer.innerHTML = "";
    detailsTaskList.forEach((t, i) => {
      const row = document.createElement("div");
      row.className = "flex items-center mb-2";
      row.innerHTML = `
        <input type="checkbox" class="mr-2" ${t.completed ? "checked" : ""}>
        <span class="flex-1">${t.description}</span>
        <button class="ml-2 text-red-500">&times;</button>
      `;
      const cb = row.querySelector("input");
      cb.addEventListener("change", e => {
        detailsTaskList[i].completed = e.target.checked;
        updateDetailsProgress();
      });
      row.querySelector("button").addEventListener("click", () => {
        detailsTaskList.splice(i, 1);
        renderDetailsTasks();
      });
      detailsTasksContainer.appendChild(row);
    });
    updateDetailsProgress();
  }

  function renderDetailsEmployees() {
    detailsEmployeesContainer.innerHTML = "";
    detailsEmployeeList.forEach((e, i) => {
      const row = document.createElement("div");
      row.className = "flex items-center mb-2";
      row.innerHTML = `
        <span class="flex-1">${e.name}</span>
        <button class="ml-2 text-red-500">&times;</button>
      `;
      row.querySelector("button").addEventListener("click", () => {
        detailsEmployeeList.splice(i, 1);
        renderDetailsEmployees();
      });
      detailsEmployeesContainer.appendChild(row);
    });
    detailsProjectEmployeeCountEl.value = detailsEmployeeList.length;
  }

  function renderDetailsInventory() {
    detailsInventoryContainer.innerHTML = "";
    detailsInventoryList.forEach((it, i) => {
      const row = document.createElement("div");
      row.className = "flex items-center mb-2";
      row.innerHTML = `
        <span class="flex-1">${it.name} (Qtd: ${it.quantity})</span>
        <button class="ml-2 text-red-500">&times;</button>
      `;
      row.querySelector("button").addEventListener("click", () => {
        detailsInventoryList.splice(i, 1);
        renderDetailsInventory();
      });
      detailsInventoryContainer.appendChild(row);
    });
  }

  closeDetailsBtn.addEventListener("click", () => detailsModal.classList.add("hidden"));
  cancelDetailsBtn.addEventListener("click", () => detailsModal.classList.add("hidden"));
  window.addEventListener("click", e => {
    if (e.target === detailsModal) detailsModal.classList.add("hidden");
  });

  deleteProjectBtn.addEventListener("click", () => {
    if (!confirm("Deseja realmente excluir este projeto?")) return;
    const id = detailsProjectIdEl.value;
    window.location.href = `${baseUrl}/projects/delete?id=${id}`;
  });

  detailsAddTaskBtn.addEventListener("click", () => {
    const desc = detailsNewTaskInput.value.trim();
    if (!desc) return;
    detailsTaskList.push({ description: desc, completed: false });
    detailsNewTaskInput.value = "";
    renderDetailsTasks();
  });

  detailsAddEmployeeBtn.addEventListener("click", () => {
    const id   = detailsEmployeeSelect.value;
    const name = detailsEmployeeSelect.selectedOptions[0]?.text;
    if (!id || detailsEmployeeList.some(e => e.id === id)) return;
    fetch(`${baseUrl}/employees/checkAllocation`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ emp_id: id })
    })
      .then(r => r.json())
      .then(j => {
        if (j.allocated) alert(`Já alocado em ${j.count} projeto(s)!`);
        detailsEmployeeList.push({ id, name });
        renderDetailsEmployees();
      });
  });

  detailsAddInventoryBtn.addEventListener("click", () => {
    const selected  = detailsInventorySelect.selectedOptions[0];
    const available = parseInt(selected?.dataset.stock || "0", 10);
    const qty       = parseInt(detailsInventoryQuantity.value, 10);
    if (qty > available) {
      alert(`Quantidade excede o estoque disponível (${available}).`);
      return;
    }
    const id   = detailsInventorySelect.value;
    const name = selected.text;
    if (!id) return;
    const ex = detailsInventoryList.find(i => i.id === id);
    if (ex) ex.quantity += qty;
    else detailsInventoryList.push({ id, name, quantity: qty });
    detailsInventoryQuantity.value = "";
    renderDetailsInventory();
  });

  detailsForm.addEventListener("submit", () => {
    detailsTasksData.value      = JSON.stringify(detailsTaskList);
    detailsEmployeesData.value  = JSON.stringify(detailsEmployeeList.map(e => e.id));
    detailsInventoryData.value  = JSON.stringify(detailsInventoryList);
  });

  document.querySelectorAll(".project-item").forEach(card => {
    card.addEventListener("click", function(event) {
      event.preventDefault();
      const projectId = this.dataset.projectId || this.getAttribute("data-id");
      fetch(`${baseUrl}/projects/show?id=${projectId}`)
        .then(r => {
          if (!r.ok) throw new Error(`HTTP ${r.status}`);
          return r.json();
        })
        .then(data => {
          detailsProjectIdEl.value            = data.id || "";
          detailsProjectNameEl.value          = data.name || "";
          detailsProjectLocationEl.value      = data.location || "";
          detailsProjectTotalHoursEl.value    = data.total_hours || "";
          detailsProjectBudgetEl.value        = data.budget || "";
          detailsProjectEmployeeCountEl.value = data.employee_count || "";
          detailsProjectStartDateEl.value     = data.start_date || "";
          detailsProjectEndDateEl.value       = data.end_date || "";
          detailsProjectStatusEl.value        = data.status || "";

          detailsTaskList      = (data.tasks     || []).map(t => ({ description: t.description, completed: t.completed }));
          detailsEmployeeList  = (data.employees || []).map(e => ({ id: e.id, name: e.name + " " + e.last_name }));
          detailsInventoryList = (data.inventory || []).map(i => ({ id: i.id, name: i.name, quantity: i.quantity }));

          renderDetailsTasks();
          renderDetailsEmployees();
          renderDetailsInventory();

          saveDetailsBtn.classList.remove("hidden");
          deleteProjectBtn.classList.remove("hidden");
          detailsModal.classList.remove("hidden");
        })
        .catch(err => {
          console.error("Erro ao carregar detalhes:", err);
          alert("Não foi possível carregar os detalhes do projeto.");
        });
    });
  });
});
