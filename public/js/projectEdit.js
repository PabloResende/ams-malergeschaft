document.addEventListener("DOMContentLoaded", function() {
    // --- LÓGICA PARA MODAL DE EDIÇÃO DE PROJETO ---
    const editProjectBtns = document.querySelectorAll(".editProjectBtn");
    const projectEditModal = document.getElementById("projectEditModal");
    const closeProjectEditModal = document.getElementById("closeProjectEditModal");
  
    // Variáveis globais para edição
    let editTaskList = [];      // Tarefas para edição
    let editEmployeeList = [];  // Funcionários para edição
    let editInventoryList = []; // Inventário para edição
  
    editProjectBtns.forEach(btn => {
      btn.addEventListener("click", function () {
        console.log("Edit button clicked, project ID:", this.getAttribute("data-id"));
        // Preenche campos básicos
        document.getElementById("editProjectId").value = this.getAttribute("data-id");
        document.getElementById("editProjectName").value = this.getAttribute("data-name");
        document.getElementById("editProjectClientName").value = this.getAttribute("data-client_name");
        document.getElementById("editProjectDescription").value = this.getAttribute("data-description");
        document.getElementById("editProjectStartDate").value = this.getAttribute("data-start_date");
        document.getElementById("editProjectEndDate").value = this.getAttribute("data-end_date");
        document.getElementById("editProjectTotalHours").value = this.getAttribute("data-total_hours");
        document.getElementById("editProjectStatus").value = this.getAttribute("data-status");
        document.getElementById("editProjectProgress").value = this.getAttribute("data-progress");
  
        // Preenche as tarefas – o atributo "data-tasks" contém um JSON com as tarefas salvas
        try {
          editTaskList = JSON.parse(this.getAttribute("data-tasks"));
        } catch (e) {
          editTaskList = [];
        }
        renderEditTasks();
  
        // Reinicia as listas de funcionários e inventário para edição
        editEmployeeList = [];
        renderEditEmployees();
        editInventoryList = [];
        renderEditInventory();
  
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
  
    // --- Funções para Tarefas no Modal de Edição ---
    const editAddTaskBtn = document.getElementById("editAddTaskBtn");
    const editNewTaskInput = document.getElementById("editNewTaskInput");
    const editTasksContainer = document.getElementById("editTasksContainer");
  
    if (editAddTaskBtn) {
      editAddTaskBtn.addEventListener("click", () => {
        const taskDesc = editNewTaskInput.value.trim();
        if (taskDesc !== "") {
          editTaskList.push({ description: taskDesc, completed: false });
          renderEditTasks();
          editNewTaskInput.value = "";
        }
      });
    }
  
    function renderEditTasks() {
      if (!editTasksContainer) return;
      editTasksContainer.innerHTML = "";
      editTaskList.forEach((task, index) => {
        const taskItem = document.createElement("div");
        taskItem.classList.add("flex", "items-center", "mb-2");
        const checkbox = document.createElement("input");
        checkbox.type = "checkbox";
        checkbox.classList.add("mr-2");
        checkbox.checked = task.completed;
        checkbox.addEventListener("change", function () {
          editTaskList[index].completed = this.checked;
        });
        const inputText = document.createElement("input");
        inputText.type = "text";
        inputText.value = task.description;
        inputText.classList.add("w-full", "p-1", "border", "rounded");
        inputText.addEventListener("change", function () {
          editTaskList[index].description = this.value;
        });
        const removeBtn = document.createElement("button");
        removeBtn.textContent = "X";
        removeBtn.classList.add("ml-2", "text-red-500");
        removeBtn.addEventListener("click", () => {
          editTaskList.splice(index, 1);
          renderEditTasks();
        });
        taskItem.appendChild(checkbox);
        taskItem.appendChild(inputText);
        taskItem.appendChild(removeBtn);
        editTasksContainer.appendChild(taskItem);
      });
    }
  
    // --- Funções para Funcionários na Edição ---
    const editAddEmployeeBtn = document.getElementById("editAddEmployeeBtn");
    const editEmployeeSelect = document.getElementById("editEmployeeSelect");
    const editEmployeesContainer = document.getElementById("editEmployeesContainer");
  
    if (editAddEmployeeBtn) {
      editAddEmployeeBtn.addEventListener("click", () => {
        const empId = editEmployeeSelect.value;
        const empName = editEmployeeSelect.options[editEmployeeSelect.selectedIndex].text;
        if (empId !== "" && !editEmployeeList.some(e => e.id === empId)) {
          editEmployeeList.push({ id: empId, name: empName });
          renderEditEmployees();
        }
      });
    }
  
    function renderEditEmployees() {
      editEmployeesContainer.innerHTML = "";
      editEmployeeList.forEach((emp, index) => {
        const empItem = document.createElement("div");
        empItem.classList.add("flex", "items-center", "mb-2");
        empItem.textContent = `Funcionário: ${emp.name}`;
        const removeBtn = document.createElement("button");
        removeBtn.textContent = "X";
        removeBtn.classList.add("ml-2", "text-red-500");
        removeBtn.addEventListener("click", () => {
          editEmployeeList.splice(index, 1);
          renderEditEmployees();
        });
        empItem.appendChild(removeBtn);
        editEmployeesContainer.appendChild(empItem);
      });
    }
  
    // --- Funções para Inventário na Edição ---
    const editAddInventoryBtn = document.getElementById("editAddInventoryBtn");
    const editInventorySelect = document.getElementById("editInventorySelect");
    const editInventoryQuantity = document.getElementById("editInventoryQuantity");
    const editInventoryContainer = document.getElementById("editInventoryContainer");
    // editInventoryList já foi declarada globalmente
  
    if (editAddInventoryBtn) {
      editAddInventoryBtn.addEventListener("click", () => {
        const invId = editInventorySelect.value;
        const invName = editInventorySelect.options[editInventorySelect.selectedIndex].text;
        const invQty = parseInt(editInventoryQuantity.value.trim());
        if (invId !== "" && invQty > 0) {
          let existing = editInventoryList.find(item => item.id === invId);
          if (existing) {
            existing.quantity += invQty;
          } else {
            editInventoryList.push({ id: invId, name: invName, quantity: invQty });
          }
          renderEditInventory();
        }
        editInventorySelect.selectedIndex = 0;
        editInventoryQuantity.value = "";
      });
    }
  
    function renderEditInventory() {
      editInventoryContainer.innerHTML = "";
      editInventoryList.forEach((item, index) => {
        const invItem = document.createElement("div");
        invItem.classList.add("flex", "items-center", "mb-2");
        invItem.textContent = `Material: ${item.name} (Quantidade: ${item.quantity})`;
        const removeBtn = document.createElement("button");
        removeBtn.textContent = "X";
        removeBtn.classList.add("ml-2", "text-red-500");
        removeBtn.addEventListener("click", () => {
          editInventoryList.splice(index, 1);
          renderEditInventory();
        });
        invItem.appendChild(removeBtn);
        editInventoryContainer.appendChild(invItem);
      });
    }
  
    // --- Envio dos dados do formulário de Edição ---
    const projectEditForm = document.getElementById("projectEditForm");
    projectEditForm.addEventListener("submit", () => {
      document.getElementById("editTasksData").value = JSON.stringify(editTaskList);
      document.getElementById("editEmployeesData").value = JSON.stringify(editEmployeeList.map(emp => emp.id));
      document.getElementById("editInventoryData").value = JSON.stringify(editInventoryList);
    });
  });
  