const baseUrl = window.location.origin + '/ams-malergeschaft/public';

document.addEventListener("DOMContentLoaded", () => {
  // —————— Modal de Criação ——————
  const projectModal        = document.getElementById("projectModal");
  const addProjectBtn       = document.getElementById("addProjectBtn");
  const closeModalBtns      = document.querySelectorAll("#closeModal");
  const projectForm         = document.getElementById("projectForm");
  const tasksContainer      = document.getElementById("tasksContainer");
  const employeesContainer  = document.getElementById("employeesContainer");
  const newTaskInput        = document.getElementById("newTaskInput");
  const addTaskBtn          = document.getElementById("addTaskBtn");
  const employeeSelect      = document.getElementById("employeeSelect");
  const addEmployeeBtn      = document.getElementById("addEmployeeBtn");
  const tasksDataInput      = document.getElementById("tasksData");
  const employeesDataInput  = document.getElementById("employeesData");
  const employeeCountCreate = document.getElementById("employeeCountDataCreate");

  let createTasks     = [];
  let createEmployees = [];

  // abre/fecha modal criação
  addProjectBtn.addEventListener("click", () => projectModal.classList.remove("hidden"));
  closeModalBtns.forEach(btn => btn.addEventListener("click", () => {
    projectModal.classList.add("hidden");
    createTasks = [];
    createEmployees = [];
    tasksContainer.innerHTML = "";
    employeesContainer.innerHTML = "";
    syncCreateData();
  }));
  window.addEventListener("click", e => {
    if (e.target === projectModal) closeModalBtns[0].click();
  });

  // adicionar task
  addTaskBtn.addEventListener("click", () => {
    const desc = newTaskInput.value.trim();
    if (!desc) return;
    const id = Date.now();
    createTasks.push({ id, description: desc, completed: false });
    renderCreateTasks();
    newTaskInput.value = "";
    syncCreateData();
  });

  function renderCreateTasks() {
    tasksContainer.innerHTML = "";
    createTasks.forEach(t => {
      const div = document.createElement("div");
      div.className = "flex items-center mb-2";
      div.innerHTML = `
        <span class="flex-1">${t.description}</span>
        <button data-id="${t.id}" class="remove-task text-red-500">×</button>
      `;
      tasksContainer.appendChild(div);
    });
    tasksContainer.querySelectorAll(".remove-task").forEach(btn =>
      btn.addEventListener("click", () => {
        createTasks = createTasks.filter(t => t.id != btn.dataset.id);
        renderCreateTasks();
        syncCreateData();
      })
    );
    // atualiza checkboxes se quiser permitir edição direta
  }

  // adicionar funcionário
  addEmployeeBtn.addEventListener("click", () => {
    const empId = employeeSelect.value;
    const empText = employeeSelect.options[employeeSelect.selectedIndex].text;
    if (!empId || createEmployees.find(e => e.id == empId)) return;
    createEmployees.push({ id: empId, text: empText });
    renderCreateEmployees();
    syncCreateData();
  });

  function renderCreateEmployees() {
    employeesContainer.innerHTML = "";
    createEmployees.forEach(e => {
      const div = document.createElement("div");
      div.className = "flex items-center mb-2";
      div.innerHTML = `
        <span class="flex-1">${e.text}</span>
        <button data-id="${e.id}" class="remove-emp text-red-500">×</button>
      `;
      employeesContainer.appendChild(div);
    });
    employeesContainer.querySelectorAll(".remove-emp").forEach(btn =>
      btn.addEventListener("click", () => {
        createEmployees = createEmployees.filter(e => e.id != btn.dataset.id);
        renderCreateEmployees();
        syncCreateData();
      })
    );
  }

  // sincroniza hidden inputs
  function syncCreateData() {
    tasksDataInput.value      = JSON.stringify(createTasks);
    employeesDataInput.value  = JSON.stringify(createEmployees.map(e => e.id));
    employeeCountCreate.value = createEmployees.length;
  }

  // —————— Modal de Detalhes / Edição ——————
  const detailsModal             = document.getElementById("projectDetailsModal");
  const closeDetailsBtn          = document.getElementById("closeProjectDetailsModal");
  const cancelDetailsBtn         = document.getElementById("cancelDetailsBtn");
  const projectItems             = document.querySelectorAll(".project-item");
  const detailsForm              = document.getElementById("projectDetailsForm");
  const detailsTasksContainer    = document.getElementById("detailsTasksContainer");
  const detailsEmployeesContainer= document.getElementById("detailsEmployeesContainer");
  const detailsInventoryContainer= document.getElementById("detailsInventoryContainer");
  const detailsNewTaskInput      = document.getElementById("detailsNewTaskInput");
  const detailsAddTaskBtn        = document.getElementById("detailsAddTaskBtn");
  const detailsEmployeeSelect    = document.getElementById("detailsEmployeeSelect");
  const detailsAddEmployeeBtn    = document.getElementById("detailsAddEmployeeBtn");
  const detailsTasksData         = document.getElementById("detailsTasksData");
  const detailsEmployeesData     = document.getElementById("detailsEmployeesData");
  const detailsEmployeeCount     = document.getElementById("detailsEmployeeCountData");
  const detailsProjectIdInput    = document.getElementById("detailsProjectId");
  const detailsProgressBar       = document.getElementById("detailsProgressBar");
  const detailsProgressText      = document.getElementById("detailsProgressText");

  let detailTasks      = [];
  let detailEmployees  = [];
  let detailInventory  = [];

  // abre modal detalhes
  projectItems.forEach(item =>
    item.addEventListener("click", () => loadDetails(item.dataset.projectId))
  );
  closeDetailsBtn.addEventListener("click", closeDetails);
  cancelDetailsBtn.addEventListener("click", closeDetails);
  window.addEventListener("click", e => {
    if (e.target === detailsModal) closeDetails();
  });

  function loadDetails(projectId) {
    fetch(`${baseUrl}/projects/show?id=${projectId}`)
      .then(res => res.json())
      .then(data => {
        // campos fixos
        detailsProjectIdInput.value                   = data.id;
        document.getElementById("detailsProjectName").value      = data.name;
        document.getElementById("detailsProjectLocation").value  = data.location;
        document.getElementById("detailsProjectTotalHours").value= data.total_hours;
        document.getElementById("detailsProjectBudget").value    = data.budget;
        document.getElementById("detailsProjectStartDate").value = data.start_date;
        document.getElementById("detailsProjectEndDate").value   = data.end_date;
        document.getElementById("detailsProjectStatus").value    = data.status;

        // progresso
        const prog = data.progress ?? 0;
        detailsProgressBar.style.width = prog + '%';
        detailsProgressText.textContent = prog + '%';

        // tasks
        detailTasks = (data.tasks || []).map((t,i) => ({
          id: i,
          description: t.description,
          completed: !!t.completed
        }));
        renderDetailTasks();

        // funcionários
        detailEmployees = (data.employees || []).map(e => ({
          id: e.id,
          text: e.name + ' ' + e.last_name
        }));
        renderDetailEmployees();

        // inventário
        detailInventory = data.inventory || [];
        renderDetailInventory();

        // mostra modal
        detailsModal.classList.remove("hidden");
      })
      .catch(err => {
        console.error("Erro ao carregar detalhes:", err);
        alert("Não foi possível carregar detalhes do projeto.");
      });
  }

  function closeDetails() {
    detailsModal.classList.add("hidden");
  }

  // renderiza tasks no modal detalhes
  detailsAddTaskBtn.addEventListener("click", () => {
    const desc = detailsNewTaskInput.value.trim();
    if (!desc) return;
    detailTasks.push({ id: Date.now(), description: desc, completed: false });
    renderDetailTasks();
  });

  function renderDetailTasks() {
    detailsTasksContainer.innerHTML = "";
    detailTasks.forEach(t => {
      const div = document.createElement("div");
      div.className = "flex items-center mb-2";
      div.innerHTML = `
        <input type="checkbox" ${t.completed ? 'checked' : ''} disabled class="mr-2">
        <span class="flex-1">${t.description}</span>
      `;
      detailsTasksContainer.appendChild(div);
    });
    detailsTasksData.value = JSON.stringify(detailTasks);
  }

  // renderiza funcionários no modal detalhes
  detailsAddEmployeeBtn.addEventListener("click", () => {
    const empId = detailsEmployeeSelect.value;
    const empText = detailsEmployeeSelect.options[detailsEmployeeSelect.selectedIndex].text;
    if (!empId || detailEmployees.find(e => e.id == empId)) return;
    detailEmployees.push({ id: empId, text: empText });
    renderDetailEmployees();
  });

  function renderDetailEmployees() {
    detailsEmployeesContainer.innerHTML = "";
    detailEmployees.forEach(e => {
      const div = document.createElement("div");
      div.className = "flex items-center mb-2";
      div.innerHTML = `
        <span class="flex-1">${e.text}</span>
        <button data-id="${e.id}" class="remove-detail-emp text-red-500">×</button>
      `;
      detailsEmployeesContainer.appendChild(div);
    });
    detailsEmployeesContainer.querySelectorAll(".remove-detail-emp").forEach(btn =>
      btn.addEventListener("click", () => {
        detailEmployees = detailEmployees.filter(e => e.id != btn.dataset.id);
        renderDetailEmployees();
      })
    );
    detailsEmployeesData.value = JSON.stringify(detailEmployees.map(e => e.id));
    detailsEmployeeCount.value = detailEmployees.length;
  }

  // renderiza inventário no modal detalhes
  function renderDetailInventory() {
    detailsInventoryContainer.innerHTML = "";
    if (detailInventory.length === 0) {
      detailsInventoryContainer.textContent = '— Nenhum item alocado';
      return;
    }
    detailInventory.forEach(i => {
      const div = document.createElement("div");
      div.textContent = `${i.name} (qtde: ${i.quantity})`;
      detailsInventoryContainer.appendChild(div);
    });
  }
});
