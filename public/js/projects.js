
let editTaskList = [];
let editEmployeeList = [];
let editInventoryList = [];

document.addEventListener("DOMContentLoaded", () => {
  //
  // 1) MODAL DE CRIAÇÃO
  //
  const addProjectBtn = document.getElementById("addProjectBtn");
  const projectModal = document.getElementById("projectModal");
  const closeModal = document.getElementById("closeModal");

  addProjectBtn.addEventListener("click", () => projectModal.classList.remove("hidden"));
  closeModal .addEventListener("click", () => projectModal.classList.add("hidden"));
  window.addEventListener("click", e => {
    if (e.target === projectModal) projectModal.classList.add("hidden");
  });

  //
  // 2) CRIAÇÃO: TAREFAS, FUNCIONÁRIOS E INVENTÁRIO
  //
  let taskList      = [];
  let employeeList  = [];
  let inventoryList = [];

  // tarefas
  document.getElementById("addTaskBtn").addEventListener("click", () => {
    const desc = document.getElementById("newTaskInput").value.trim();
    if (!desc) return;
    taskList.push({ description: desc, completed: false });
    renderTasks(); updateTaskProgress();
    document.getElementById("newTaskInput").value = "";
  });
  function renderTasks() {
    const c = document.getElementById("tasksContainer");
    c.innerHTML = "";
    taskList.forEach((t,i) => {
      const row = document.createElement("div");
      row.className = "flex items-center mb-2";
      row.innerHTML = `
        <input type="checkbox" class="mr-2" ${t.completed?"checked":""} data-idx="${i}">
        <span>${t.description}</span>
      `;
      row.querySelector("input").addEventListener("change", e => {
        taskList[i].completed = e.target.checked;
        updateTaskProgress();
      });
      c.appendChild(row);
    });
  }
  function updateTaskProgress() {
    const total = taskList.length;
    const done  = taskList.filter(t=>t.completed).length;
    const pct   = total ? Math.round(done/total*100) : 0;
    const bar = document.getElementById("createProgressBar");
    if (bar) {
      bar.style.width = pct + "%";
      bar.textContent = pct + "%";
    }
  }

  // funcionários
  document.getElementById("addEmployeeBtn").addEventListener("click", () => {
    const sel = document.getElementById("employeeSelect");
    const id  = sel.value;
    const name= sel.options[sel.selectedIndex]?.text;
    if (!id || employeeList.some(e=>e.id===id)) return;
    // checa alocação
    fetch('/ams-malergeschaft/public/employees/checkAllocation', {
      method:"POST", headers:{"Content-Type":"application/json"},
      body: JSON.stringify({ emp_id: id })
    })
    .then(r=>r.json())
    .then(j=>{
      if (j.allocated) alert(`Atenção: já alocado em ${j.count} projeto(s)!`);
      employeeList.push({ id, name });
      renderEmployees();
    });
  });
  function renderEmployees() {
    const c = document.getElementById("employeesContainer");
    c.innerHTML = "";
    employeeList.forEach((e,i) => {
      const row = document.createElement("div");
      row.className = "flex items-center mb-2";
      row.innerHTML = `
        <span>Funcionário: ${e.name}</span>
        <button class="ml-2 text-red-500" data-idx="${i}">X</button>
      `;
      row.querySelector("button").addEventListener("click", () => {
        employeeList.splice(i,1);
        renderEmployees();
      });
      c.appendChild(row);
    });
  }

  // inventário
  document.getElementById("addInventoryBtn").addEventListener("click", () => {
    const sel = document.getElementById("inventorySelect");
    const id  = sel.value;
    const name= sel.options[sel.selectedIndex]?.text;
    const qty = parseInt(document.getElementById("inventoryQuantity").value,10);
    if (!id||qty<=0) return;
    const ex = inventoryList.find(x=>x.id===id);
    if (ex) ex.quantity += qty; else inventoryList.push({id,name,quantity:qty});
    renderInventory();
  });
  function renderInventory() {
    const c = document.getElementById("inventoryContainer");
    c.innerHTML = "";
    inventoryList.forEach((it,i) => {
      const row = document.createElement("div");
      row.className = "flex items-center mb-2";
      row.innerHTML = `
        <span>Material: ${it.name} (Qtd: ${it.quantity})</span>
        <button class="ml-2 text-red-500" data-idx="${i}">X</button>
      `;
      row.querySelector("button").addEventListener("click", () => {
        inventoryList.splice(i,1);
        renderInventory();
      });
      c.appendChild(row);
    });
  }

  // ao submeter criação
  document.getElementById("projectForm").addEventListener("submit", () => {
    document.getElementById("tasksData").value     = JSON.stringify(taskList);
    document.getElementById("employeesData").value = JSON.stringify(employeeList.map(e=>e.id));
    document.getElementById("inventoryData").value = JSON.stringify(inventoryList);
  });


  //
  // 3) ABRIR / FECHAR MODAL DE EDIÇÃO via DELEGATION
  //
  const editModal = document.getElementById("projectEditModal");
  document.body.addEventListener("click", e => {
    if (e.target.classList.contains("editProjectBtn")) {
      const btn = e.target;
      // preenche campos
      document.getElementById("editProjectId").value          = btn.dataset.id;
      document.getElementById("editProjectName").value        = btn.dataset.name;
      document.getElementById("editProjectClientName").value  = btn.dataset.client_name;
      document.getElementById("editProjectDescription").value = btn.dataset.description;
      document.getElementById("editProjectStartDate").value   = btn.dataset.start_date;
      document.getElementById("editProjectEndDate").value     = btn.dataset.end_date;
      document.getElementById("editProjectTotalHours").value  = btn.dataset.total_hours;
      document.getElementById("editProjectStatus").value      = btn.dataset.status;

      // tarefas
      try { editTaskList = JSON.parse(btn.dataset.tasks); } catch { editTaskList = []; }
      renderEditTasks();

      // limpa listas de funcionários e inventário
      editEmployeeList = [];
      renderEditEmployees();
      editInventoryList = [];
      renderEditInventory();

      editModal.classList.remove("hidden");
    }
    // fechar
    if (e.target.id === "closeProjectEditModal") {
      editModal.classList.add("hidden");
    }
    // click fora
    if (e.target === editModal) {
      editModal.classList.add("hidden");
    }
  });

  //
  // 4) EDIÇÃO: tarefas, funcionários e inventário
  //
  const editTasksContainer     = document.getElementById("editTasksContainer");
  const editEmployeesContainer = document.getElementById("editEmployeesContainer");
  const editInventoryContainer = document.getElementById("editInventoryContainer");

  document.getElementById("editAddTaskBtn").addEventListener("click", () => {
    const desc = document.getElementById("editNewTaskInput").value.trim();
    if (!desc) return;
    editTaskList.push({ description: desc, completed: false });
    renderEditTasks();
    document.getElementById("editNewTaskInput").value = "";
  });
  function renderEditTasks() {
    editTasksContainer.innerHTML = "";
    editTaskList.forEach((t,i) => {
      const row = document.createElement("div");
      row.className = "flex items-center mb-2";
      row.innerHTML = `
        <input type="checkbox" class="mr-2" ${t.completed?"checked":""} data-idx="${i}">
        <input type="text" class="w-full p-1 border rounded" value="${t.description}" data-idx="${i}">
        <button class="ml-2 text-red-500" data-idx="${i}">X</button>
      `;
      row.querySelector("input[type=text]").addEventListener("change", e => {
        editTaskList[i].description = e.target.value;
      });
      row.querySelector("input[type=checkbox]").addEventListener("change", e => {
        editTaskList[i].completed = e.target.checked;
      });
      row.querySelector("button").addEventListener("click", () => {
        editTaskList.splice(i,1);
        renderEditTasks();
      });
      editTasksContainer.appendChild(row);
    });
  }

  document.getElementById("editAddEmployeeBtn").addEventListener("click", () => {
    const sel = document.getElementById("editEmployeeSelect");
    const id  = sel.value;
    const name= sel.options[sel.selectedIndex]?.text;
    if (!id || editEmployeeList.some(e=>e.id===id)) return;
    editEmployeeList.push({ id, name });
    renderEditEmployees();
  });
  function renderEditEmployees() {
    editEmployeesContainer.innerHTML = "";
    editEmployeeList.forEach((e,i) => {
      const row = document.createElement("div");
      row.className = "flex items-center mb-2";
      row.innerHTML = `
        <span>Funcionário: ${e.name}</span>
        <button class="ml-2 text-red-500" data-idx="${i}">X</button>
      `;
      row.querySelector("button").addEventListener("click", () => {
        editEmployeeList.splice(i,1);
        renderEditEmployees();
      });
      editEmployeesContainer.appendChild(row);
    });
  }

  document.getElementById("editAddInventoryBtn").addEventListener("click", () => {
    const sel = document.getElementById("editInventorySelect");
    const id  = sel.value;
    const name= sel.options[sel.selectedIndex]?.text;
    const qty = parseInt(document.getElementById("editInventoryQuantity").value,10);
    if (!id||qty<=0) return;
    const ex = editInventoryList.find(x=>x.id===id);
    if (ex) ex.quantity+=qty; else editInventoryList.push({id,name,quantity:qty});
    renderEditInventory();
  });
  function renderEditInventory() {
    editInventoryContainer.innerHTML = "";
    editInventoryList.forEach((it,i) => {
      const row = document.createElement("div");
      row.className = "flex items-center mb-2";
      row.innerHTML = `
        <span>Material: ${it.name} (Qtd: ${it.quantity})</span>
        <button class="ml-2 text-red-500" data-idx="${i}">X</button>
      `;
      row.querySelector("button").addEventListener("click", () => {
        editInventoryList.splice(i,1);
        renderEditInventory();
      });
      editInventoryContainer.appendChild(row);
    });
  }

  // ao submeter edição
  document.getElementById("projectEditForm").addEventListener("submit", () => {
    document.getElementById("editTasksData").value     = JSON.stringify(editTaskList);
    document.getElementById("editEmployeesData").value = JSON.stringify(editEmployeeList.map(e=>e.id));
    document.getElementById("editInventoryData").value = JSON.stringify(editInventoryList);
  });


  //
  // 5) ATUALIZAÇÃO DE TAREFAS EM PAINEL (persistência)
  //
  document.querySelectorAll(".task-checkbox").forEach(cb => {
    cb.addEventListener("change", () => {
      const id  = cb.dataset.taskId;
      const ok  = cb.checked ? 1 : 0;
      fetch('/ams-malergeschaft/public/tasks/update', {
        method:"POST", headers:{"Content-Type":"application/json"},
        body: JSON.stringify({ task_id:id, completed: ok })
      })
      .then(r=>r.json())
      .then(j=>{
        if (j.success) {
          const panel = cb.closest(".project-details");
          // recalcula e atualiza a barra dentro do details
          const checks = panel.querySelectorAll(".task-checkbox");
          const total  = checks.length,
                done   = [...checks].filter(x=>x.checked).length,
                pct    = total?Math.round(done/total*100):0;
          panel.querySelector(".progress-bar").style.width = pct + "%";
          panel.querySelector(".progress-value").textContent = pct;
        }
      });
    });
  });

});