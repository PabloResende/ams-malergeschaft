// public/js/projects.js

const baseUrl = window.location.origin + '/ams-malergeschaft/public';

document.addEventListener("DOMContentLoaded", () => {
  // ─── Criação de Projetos ────────────────────────────────────────────────
  const addProjectBtn       = document.getElementById("addProjectBtn");
  const projectModal        = document.getElementById("projectModal");
  const closeModalBtns      = document.querySelectorAll("#closeModal, #closeProjectModal");
  const projectForm         = document.getElementById("projectForm");
  const tasksContainer      = document.getElementById("tasksContainer");
  const employeesContainer  = document.getElementById("employeesContainer");
  const newTaskInput        = document.getElementById("newTaskInput");
  const addTaskBtn          = document.getElementById("addTaskBtn");
  const employeeSelect      = document.getElementById("employeeSelect");
  const addEmployeeBtn      = document.getElementById("addEmployeeBtn");
  const tasksDataInput      = document.getElementById("projectTasks");
  const employeesDataInput  = document.getElementById("projectEmployees");
  const employeeCountCreate = document.getElementById("employeeCountDataCreate");

  let createTasks     = [];
  let createEmployees = [];

  // abrir/fechar modal criação
  addProjectBtn.addEventListener("click", () => projectModal.classList.remove("hidden"));
  closeModalBtns.forEach(btn => btn.addEventListener("click", resetCreateModal));
  window.addEventListener("click", e => {
    if (e.target === projectModal) resetCreateModal();
  });

  function resetCreateModal() {
    projectModal.classList.add("hidden");
    createTasks = [];
    createEmployees = [];
    renderCreateTasks();
    renderCreateEmployees();
    syncCreateData();
  }

  // adicionar task
  addTaskBtn.addEventListener("click", () => {
    const desc = newTaskInput.value.trim();
    if (!desc) return;
    createTasks.push({ id: Date.now(), description: desc, completed: false });
    newTaskInput.value = "";
    renderCreateTasks();
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

  // sincronizar hidden inputs
  function syncCreateData() {
    tasksDataInput.value      = JSON.stringify(createTasks);
    employeesDataInput.value  = JSON.stringify(createEmployees.map(e => e.id));
    if (employeeCountCreate) {
      employeeCountCreate.value = createEmployees.length;
    }
  }

  // ─── Detalhes de Projetos ───────────────────────────────────────────────
  const projectItems              = document.querySelectorAll(".project-item");
  const detailsModal              = document.getElementById("projectDetailsModal");
  const closeDetailsBtn           = document.getElementById("closeProjectDetailsModal");
  const cancelDetailsBtn          = document.getElementById("cancelDetailsBtn");
  const detailsProjectIdInput     = document.getElementById("detailsProjectId");
  const detailsProjectNameInput   = document.getElementById("detailsProjectName");
  const detailsProjectLocation    = document.getElementById("detailsProjectLocation");
  const detailsProjectStartDate   = document.getElementById("detailsProjectStartDate");
  const detailsProjectEndDate     = document.getElementById("detailsProjectEndDate");
  const detailsProjectStatus      = document.getElementById("detailsProjectStatus");
  const detailsProgressBar        = document.getElementById("detailsProgressBar");
  const detailsProgressText       = document.getElementById("detailsProgressText");
  const detailsTasksContainer     = document.getElementById("detailsTasksContainer");
  const detailsEmployeesContainer = document.getElementById("detailsEmployeesContainer");
  const detailsInventoryContainer = document.getElementById("detailsInventoryContainer");
  const detailsNewTaskInput       = document.getElementById("detailsNewTaskInput");
  const detailsAddTaskBtn         = document.getElementById("detailsAddTaskBtn");
  const detailsEmployeeSelect     = document.getElementById("detailsEmployeeSelect");
  const detailsAddEmployeeBtn     = document.getElementById("detailsAddEmployeeBtn");
  const detailsTasksData          = document.getElementById("detailsTasksData");
  const detailsEmployeesData      = document.getElementById("detailsEmployeesData");
  const detailsEmployeeCount      = document.getElementById("detailsEmployeeCountData");

  let detailTasks     = [];
  let detailEmployees = [];
  let detailInventory = [];

  // abrir modal detalhes
  projectItems.forEach(item =>
    item.addEventListener("click", () => loadDetails(item.dataset.projectId))
  );
  closeDetailsBtn.addEventListener("click", closeDetails);
  cancelDetailsBtn.addEventListener("click", closeDetails);
  window.addEventListener("click", e => {
    if (e.target === detailsModal) closeDetails();
  });

  function loadDetails(projectId) {
    fetch(`${baseUrl}/projects/show?id=${projectId}`, {
      credentials: 'same-origin'
    })
      .then(res => {
        if (!res.ok) throw new Error("HTTP " + res.status);
        return res.json();
      })
      .then(data => {
        if (data.error) {
          alert(data.error);
          return;
        }

        // cliente
        document.getElementById("detailsProjectClientName")
                .textContent = data.client_name || '—';

        // preencher formulário
        detailsProjectIdInput.value                 = data.id;
        detailsProjectNameInput.value               = data.name;
        detailsProjectLocation.value                = data.location;
        detailsProjectStartDate.value               = data.start_date;
        detailsProjectEndDate.value                 = data.end_date;
        detailsProjectStatus.value                  = data.status;

        // progresso
        const prog = data.progress || 0;
        detailsProgressBar.style.width  = prog + '%';
        detailsProgressText.textContent = prog + '%';

        // tasks
        detailTasks = (data.tasks || []).map(t => ({
          id: t.id,
          description: t.description,
          completed: !!t.completed
        }));
        renderDetailTasks();
        detailsTasksData.value = JSON.stringify(detailTasks);

        // employees
        detailEmployees = (data.employees || []).map(e => ({
          id: e.id,
          text: e.name + ' ' + e.last_name
        }));
        renderDetailEmployees();
        detailsEmployeesData.value = JSON.stringify(detailEmployees.map(e => e.id));
        detailsEmployeeCount.value = detailEmployees.length;

        // inventory
        detailInventory = data.inventory || [];
        renderDetailInventory();

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

  // detalhar tarefas
  detailsAddTaskBtn.addEventListener("click", () => {
    const desc = detailsNewTaskInput.value.trim();
    if (!desc) return;
    detailTasks.push({ id: Date.now(), description: desc, completed: false });
    renderDetailTasks();
    detailsTasksData.value = JSON.stringify(detailTasks);
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
  }

  // detalhar funcionários
  detailsAddEmployeeBtn.addEventListener("click", () => {
    const empId   = detailsEmployeeSelect.value;
    const empText = detailsEmployeeSelect.options[detailsEmployeeSelect.selectedIndex].text;
    if (!empId || detailEmployees.find(e => e.id == empId)) return;
    detailEmployees.push({ id: empId, text: empText });
    renderDetailEmployees();
    detailsEmployeesData.value = JSON.stringify(detailEmployees.map(e => e.id));
    detailsEmployeeCount.value = detailEmployees.length;
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
        detailsEmployeesData.value = JSON.stringify(detailEmployees.map(e => e.id));
        detailsEmployeeCount.value = detailEmployees.length;
      })
    );
  }

  // detalhar inventário
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
