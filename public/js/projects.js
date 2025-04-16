document.addEventListener("DOMContentLoaded", function() {
  // --- MODAL DE CRIAÇÃO DE PROJETO ---
  const addProjectBtn = document.getElementById("addProjectBtn");
  const projectModal = document.getElementById("projectModal");
  const closeModal = document.getElementById("closeModal");

  addProjectBtn.addEventListener("click", () => {
    projectModal.classList.remove("hidden");
  });
  closeModal.addEventListener("click", () => {
    projectModal.classList.add("hidden");
  });
  window.addEventListener("click", (event) => {
    if (event.target === projectModal) {
      projectModal.classList.add("hidden");
    }
  });

  // --- LÓGICA PARA TAREFAS (CRIAÇÃO) ---
  const addTaskBtn = document.getElementById("addTaskBtn");
  const newTaskInput = document.getElementById("newTaskInput");
  const tasksContainer = document.getElementById("tasksContainer");
  let taskList = [];

  addTaskBtn.addEventListener("click", () => {
    const taskDesc = newTaskInput.value.trim();
    if (taskDesc !== "") {
      const task = { description: taskDesc, completed: false };
      taskList.push(task);
      renderTasks();
      newTaskInput.value = "";
      updateTaskProgress();
    }
  });

  function renderTasks() {
    tasksContainer.innerHTML = "";
    taskList.forEach((task, index) => {
      const taskItem = document.createElement("div");
      taskItem.classList.add("flex", "items-center", "mb-2");

      const checkbox = document.createElement("input");
      checkbox.type = "checkbox";
      checkbox.classList.add("mr-2");
      checkbox.addEventListener("change", function () {
        taskList[index].completed = this.checked;
        updateTaskProgress();
      });

      const span = document.createElement("span");
      span.textContent = task.description;

      taskItem.appendChild(checkbox);
      taskItem.appendChild(span);
      tasksContainer.appendChild(taskItem);
    });
  }

  function updateTaskProgress() {
    const total = taskList.length;
    const completed = taskList.filter(t => t.completed).length;
    const percent = total === 0 ? 0 : Math.round((completed / total) * 100);
    const createProgressBar = document.getElementById("createProgressBar");
    if (createProgressBar) {
      createProgressBar.style.width = `${percent}%`;
      createProgressBar.textContent = `${percent}%`;
    }
  }

  // --- LÓGICA PARA FUNCIONÁRIOS (CRIAÇÃO) via SELECT ---
  const addEmployeeBtn = document.getElementById("addEmployeeBtn");
  const employeeSelect = document.getElementById("employeeSelect");
  const employeesContainer = document.getElementById("employeesContainer");
  let employeeList = [];

  addEmployeeBtn.addEventListener("click", () => {
    const empId = employeeSelect.value;
    const empName = employeeSelect.options[employeeSelect.selectedIndex].text;
    if (empId !== "") {
      // Verifica alocação com Ajax
      fetch('/ams-malergeschaft/public/employees/checkAllocation', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ emp_id: empId })
      })
      .then(response => response.json())
      .then(data => {
        if (data.allocated) {
          alert(`Atenção: Este funcionário já está alocado em ${data.count} outro(s) projeto(s)!`);
        }
        if (!employeeList.some(e => e.id === empId)) {
          employeeList.push({ id: empId, name: empName });
          renderEmployees();
        }
      })
      .catch(error => console.error('Erro ao verificar alocação:', error));
    }
  });

  function renderEmployees() {
    employeesContainer.innerHTML = "";
    employeeList.forEach((emp, index) => {
      const empItem = document.createElement("div");
      empItem.classList.add("flex", "items-center", "mb-2");
      empItem.textContent = `Funcionário: ${emp.name}`;
      const removeBtn = document.createElement("button");
      removeBtn.textContent = "X";
      removeBtn.classList.add("ml-2", "text-red-500");
      removeBtn.addEventListener("click", () => {
        employeeList.splice(index, 1);
        renderEmployees();
      });
      empItem.appendChild(removeBtn);
      employeesContainer.appendChild(empItem);
    });
  }

  // --- LÓGICA PARA MATERIAIS/INVENTÁRIO (CRIAÇÃO) via SELECT ---
  const addInventoryBtn = document.getElementById("addInventoryBtn");
  const inventorySelect = document.getElementById("inventorySelect");
  const inventoryQuantityInput = document.getElementById("inventoryQuantity");
  const inventoryContainer = document.getElementById("inventoryContainer");
  let inventoryList = [];

  addInventoryBtn.addEventListener("click", () => {
    const invId = inventorySelect.value;
    const invName = inventorySelect.options[inventorySelect.selectedIndex].text;
    const invQuantity = parseInt(inventoryQuantityInput.value.trim());
    if (invId !== "" && invQuantity > 0) {
      let existing = inventoryList.find(item => item.id === invId);
      if (existing) {
        existing.quantity += invQuantity;
      } else {
        inventoryList.push({ id: invId, name: invName, quantity: invQuantity });
      }
      renderInventory();
    }
    inventorySelect.selectedIndex = 0;
    inventoryQuantityInput.value = "";
  });

  function renderInventory() {
    inventoryContainer.innerHTML = "";
    inventoryList.forEach((item, index) => {
      const invItem = document.createElement("div");
      invItem.classList.add("flex", "items-center", "mb-2");
      invItem.textContent = `Material: ${item.name} (Quantidade: ${item.quantity})`;
      const removeBtn = document.createElement("button");
      removeBtn.textContent = "X";
      removeBtn.classList.add("ml-2", "text-red-500");
      removeBtn.addEventListener("click", () => {
        inventoryList.splice(index, 1);
        renderInventory();
      });
      invItem.appendChild(removeBtn);
      inventoryContainer.appendChild(invItem);
    });
  }

  // --- Envio dos dados do formulário de criação ---
  const projectForm = document.getElementById("projectForm");
  projectForm.addEventListener("submit", () => {
    document.getElementById("tasksData").value = JSON.stringify(taskList);
    document.getElementById("employeesData").value = JSON.stringify(employeeList.map(emp => emp.id));
    document.getElementById("inventoryData").value = JSON.stringify(inventoryList);
  });

  // --- LÓGICA DE UPDATE DE STATUS DE TAREFA VIA AJAX (para exibição em painel) ---
  document.querySelectorAll(".task-checkbox").forEach(checkbox => {
    checkbox.addEventListener("change", function () {
      const taskId = this.getAttribute("data-task-id");
      const completed = this.checked;
      updateTaskInDB(taskId, completed);
      const detailsPanel = this.closest(".project-details");
      updateProjectProgress(detailsPanel);
    });
  });

  function updateTaskInDB(taskId, completed) {
    fetch('/ams-malergeschaft/public/tasks/update', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ task_id: taskId, completed: completed ? 1 : 0 })
    })
    .then(response => response.json())
    .then(data => {
      if (!data.success) {
        console.error("Erro ao atualizar a tarefa.");
      }
    })
    .catch(error => console.error('Erro:', error));
  }

  function updateProjectProgress(projectContainer) {
    const checkboxes = projectContainer.querySelectorAll(".task-checkbox");
    const total = checkboxes.length;
    if (total === 0) return;
    let completed = 0;
    checkboxes.forEach(cb => { if (cb.checked) completed++; });
    const percent = Math.round((completed / total) * 100);
    const progressBar = projectContainer.querySelector(".progress-bar");
    const progressValue = projectContainer.querySelector(".progress-value");
    progressBar.style.width = `${percent}%`;
    progressValue.textContent = percent;
  }
});
