// MODAL DE CRIAÇÃO DE PROJETO
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

// --- LÓGICA PARA TAREFAS ---
const addTaskBtn = document.getElementById("addTaskBtn");
const newTaskInput = document.getElementById("newTaskInput");
const tasksContainer = document.getElementById("tasksContainer");

let taskList = []; // Armazena as tarefas inseridas

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

// Atualiza a barra de progresso (real, baseada nas tarefas inseridas)
function updateTaskProgress() {
  const total = taskList.length;
  const completed = taskList.filter(task => task.completed).length;
  const percent = total === 0 ? 0 : Math.round((completed / total) * 100);
  const createProgressBar = document.getElementById("createProgressBar");
  if (createProgressBar) {
    createProgressBar.style.width = `${percent}%`;
    createProgressBar.textContent = `${percent}%`;
  }
}

// --- LÓGICA PARA FUNCIONÁRIOS via SELECT ---
const addEmployeeBtn = document.getElementById("addEmployeeBtn");
const employeeSelect = document.getElementById("employeeSelect");
const employeesContainer = document.getElementById("employeesContainer");

let employeeList = []; // Armazena objetos { id, name }

addEmployeeBtn.addEventListener("click", () => {
  const empId = employeeSelect.value;
  const empName = employeeSelect.options[employeeSelect.selectedIndex].text;
  if (empId !== "" && !employeeList.some(e => e.id === empId)) {
    employeeList.push({ id: empId, name: empName });
    renderEmployees();
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

// --- LÓGICA PARA MATERIAIS / INVENTÁRIO via SELECT ---
const addInventoryBtn = document.getElementById("addInventoryBtn");
const inventorySelect = document.getElementById("inventorySelect");
const inventoryQuantityInput = document.getElementById("inventoryQuantity");
const inventoryContainer = document.getElementById("inventoryContainer");

let inventoryList = []; // Armazena objetos { id, name, quantity }

addInventoryBtn.addEventListener("click", () => {
  const invId = inventorySelect.value;
  const invName = inventorySelect.options[inventorySelect.selectedIndex].text;
  const invQuantity = parseInt(inventoryQuantityInput.value.trim());
  if (invId !== "" && invQuantity > 0) {
    // Se o item já estiver na lista, atualiza a quantidade
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

// Antes de submeter o formulário, converte os arrays em JSON e preenche os campos ocultos
const projectForm = document.getElementById("projectForm");
projectForm.addEventListener("submit", () => {
  document.getElementById("tasksData").value = JSON.stringify(taskList);
  document.getElementById("employeesData").value = JSON.stringify(employeeList.map(emp => emp.id));
  document.getElementById("inventoryData").value = JSON.stringify(inventoryList);
});

// --- LÓGICA PARA DETALHES E MODAL DE EDIÇÃO ---
function updateProjectProgress(projectContainer) {
  const checkboxes = projectContainer.querySelectorAll(".task-checkbox");
  const total = checkboxes.length;
  if (total === 0) return;
  let completed = 0;
  checkboxes.forEach(checkbox => {
    if (checkbox.checked) completed++;
  });
  const percent = Math.round((completed / total) * 100);
  const progressBar = projectContainer.querySelector(".progress-bar");
  const progressValue = projectContainer.querySelector(".progress-value");
  progressBar.style.width = `${percent}%`;
  progressValue.textContent = percent;
}

document.querySelectorAll(".project-details").forEach(detailsPanel => {
  const checkboxes = detailsPanel.querySelectorAll(".task-checkbox");
  checkboxes.forEach(checkbox => {
    checkbox.addEventListener("change", () => {
      updateProjectProgress(detailsPanel);
    });
  });
});

// Toggle para mostrar/ocultar detalhes do projeto
document.querySelectorAll(".toggleDetails").forEach(btn => {
  btn.addEventListener("click", function () {
    const projectContainer = this.closest(".project-item");
    const detailsPanel = projectContainer.querySelector(".project-details");
    detailsPanel.classList.toggle("hidden");
  });
});

// MODAL DE EDIÇÃO DE PROJETO
const editProjectBtns = document.querySelectorAll(".editProjectBtn");
const projectEditModal = document.getElementById("projectEditModal");
const closeProjectEditModal = document.getElementById("closeProjectEditModal");

editProjectBtns.forEach((btn) => {
  btn.addEventListener("click", function () {
    // Preenche os campos com os dados do projeto (para tarefas e recursos, a implementação pode variar)
    document.getElementById("editProjectId").value = this.getAttribute("data-id");
    document.getElementById("editProjectName").value = this.getAttribute("data-name");
    document.getElementById("editProjectClientName").value = this.getAttribute("data-client_name");
    document.getElementById("editProjectDescription").value = this.getAttribute("data-description");
    document.getElementById("editProjectStartDate").value = this.getAttribute("data-start_date");
    document.getElementById("editProjectEndDate").value = this.getAttribute("data-end_date");
    document.getElementById("editProjectTotalHours").value = this.getAttribute("data-total_hours");
    document.getElementById("editProjectStatus").value = this.getAttribute("data-status");
    // Aqui, o campo de progresso é calculado (para edição, você pode optar por recarregar as tarefas para calcular o progresso real)
    document.getElementById("editProjectProgress").value = this.getAttribute("data-progress");

    projectEditModal.classList.remove("hidden");
  });
});

closeProjectEditModal.addEventListener("click", () => {
  projectEditModal.classList.add("hidden");
});
window.addEventListener("click", (event) => {
  if (event.target === projectEditModal) {
    projectEditModal.classList.add("hidden");
  }
});
