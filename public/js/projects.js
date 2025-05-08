const baseUrl = window.location.origin + '/ams-malergeschaft/public';

document.addEventListener("DOMContentLoaded", () => {
  // ─── Criação de Projetos ────────────────────────────────────────────────
  const addProjectBtn       = document.getElementById("addProjectBtn");
  const projectModal        = document.getElementById("projectModal");
  const closeModalBtns      = document.querySelectorAll("#closeModal, #closeProjectModal");
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

  addProjectBtn.addEventListener("click", () => projectModal.classList.remove("hidden"));
  closeModalBtns.forEach(btn => btn.addEventListener("click", resetCreateModal));
  window.addEventListener("click", e => { if (e.target === projectModal) resetCreateModal(); });

  function resetCreateModal() {
    projectModal.classList.add("hidden");
    createTasks = [];
    createEmployees = [];
    renderCreateTasks();
    renderCreateEmployees();
    syncCreateData();
  }

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
      div.innerHTML = `<span class="flex-1">${t.description}</span><button data-id="${t.id}" class="remove-task text-red-500">×</button>`;
      tasksContainer.appendChild(div);
    });
    tasksContainer.querySelectorAll(".remove-task").forEach(btn =>
      btn.addEventListener("click", () => {
        createTasks = createTasks.filter(x => x.id != btn.dataset.id);
        renderCreateTasks();
        syncCreateData();
      })
    );
  }

  addEmployeeBtn.addEventListener("click", () => {
    const empId   = employeeSelect.value;
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
      div.innerHTML = `<span class="flex-1">${e.text}</span><button data-id="${e.id}" class="remove-emp text-red-500">×</button>`;
      employeesContainer.appendChild(div);
    });
    employeesContainer.querySelectorAll(".remove-emp").forEach(btn =>
      btn.addEventListener("click", () => {
        createEmployees = createEmployees.filter(x => x.id != btn.dataset.id);
        renderCreateEmployees();
        syncCreateData();
      })
    );
  }

  function syncCreateData() {
    tasksDataInput.value     = JSON.stringify(createTasks);
    employeesDataInput.value = JSON.stringify(createEmployees.map(e => e.id));
    if (employeeCountCreate) employeeCountCreate.value = createEmployees.length;
  }

  // ─── Detalhes de Projetos ───────────────────────────────────────────────
  const projectItems               = document.querySelectorAll(".project-item");
  const detailsModal               = document.getElementById("projectDetailsModal");
  const closeDetailsBtn            = document.getElementById("closeProjectDetailsModal");
  const cancelDetailsBtn           = document.getElementById("cancelDetailsBtn");
  const detailsProjectIdInput      = document.getElementById("detailsProjectId");
  const detailsClientSelect        = document.getElementById("detailsClientSelect");
  const detailsProjectStatusText   = document.getElementById("detailsProjectStatusText");
  const detailsProjectStatusHidden = document.getElementById("detailsProjectStatusHidden");
  const detailsProjectStartDate    = document.getElementById("detailsProjectStartDate");
  const detailsProjectEndDate      = document.getElementById("detailsProjectEndDate");
  const detailsProgressBar         = document.getElementById("detailsProgressBar");
  const detailsProgressText        = document.getElementById("detailsProgressText");
  const detailsTasksContainer      = document.getElementById("detailsTasksContainer");
  const detailsEmployeesContainer  = document.getElementById("detailsEmployeesContainer");
  const detailsInventoryContainer  = document.getElementById("detailsInventoryContainer");
  const detailsNewTaskInput        = document.getElementById("detailsNewTaskInput");
  const detailsAddTaskBtn          = document.getElementById("detailsAddTaskBtn");
  const detailsEmployeeSelect      = document.getElementById("detailsEmployeeSelect");
  const detailsAddEmployeeBtn      = document.getElementById("detailsAddEmployeeBtn");
  const detailsTasksData           = document.getElementById("detailsTasksData");
  const detailsEmployeesData       = document.getElementById("detailsEmployeesData");
  const detailsEmployeeCount       = document.getElementById("detailsEmployeeCountData");

  let detailTasks     = [];
  let detailEmployees = [];
  let detailInventory = [];

  projectItems.forEach(item =>
    item.addEventListener("click", () => loadDetails(item.dataset.projectId))
  );
  closeDetailsBtn.addEventListener("click", closeDetails);
  cancelDetailsBtn.addEventListener("click", closeDetails);
  window.addEventListener("click", e => { if (e.target === detailsModal) closeDetails(); });

  function loadDetails(projectId) {
    fetch(`${baseUrl}/projects/show?id=${projectId}`, { credentials: 'same-origin' })
      .then(res => res.ok ? res.json() : Promise.reject(res.status))
      .then(data => {
        if (data.error) return alert(data.error);

        detailsProjectIdInput.value      = data.id;
        detailsClientSelect.value        = data.client_id || "";
        detailsProjectStatusHidden.value = data.status;
        detailsProjectStatusText.value   = { in_progress:'In Progress', pending:'Pending', completed:'Completed' }[data.status] || data.status;
        detailsProjectStartDate.value    = data.start_date;
        detailsProjectEndDate.value      = data.end_date;

        detailTasks = (data.tasks || []).map(t => ({ id:t.id, description:t.description, completed:!!t.completed }));
        renderDetailTasks();

        detailEmployees = (data.employees || []).map(e => ({ id:e.id, text:e.name+' '+e.last_name }));
        renderDetailEmployees();

        detailInventory = data.inventory || [];
        renderDetailInventory();

        detailsModal.classList.remove("hidden");
      })
      .catch(err => { console.error(err); alert("Não foi possível carregar detalhes."); });
  }

  function closeDetails() { detailsModal.classList.add("hidden"); }

  detailsAddTaskBtn.addEventListener("click", () => {
    const desc = detailsNewTaskInput.value.trim();
    if (!desc) return;
    detailTasks.push({ id: Date.now(), description: desc, completed: false });
    detailsNewTaskInput.value = "";
    renderDetailTasks();
  });

  function renderDetailTasks() {
    detailsTasksContainer.innerHTML = "";
    detailTasks.forEach((t,idx) => {
      const div = document.createElement("div");
      div.className = "flex items-center mb-2";
      div.innerHTML = `<input type="checkbox" data-idx="${idx}" ${t.completed?'checked':''} class="mr-2"><span class="flex-1">${t.description}</span>`;
      detailsTasksContainer.appendChild(div);
    });
    detailsTasksData.value = JSON.stringify(detailTasks);
    updateProgress();
  }

  detailsTasksContainer.addEventListener("change", e => {
    if (e.target.matches('input[type="checkbox"]')) {
      const idx = e.target.dataset.idx;
      detailTasks[idx].completed = e.target.checked;
      detailsTasksData.value      = JSON.stringify(detailTasks);
      updateProgress();
    }
  });

  function updateProgress() {
    const total = detailTasks.length, done = detailTasks.filter(t=>t.completed).length;
    const pct = total?Math.round(done/total*100):0;
    detailsProgressBar.style.width  = pct+'%';
    detailsProgressText.textContent = pct+'%';
    if (total>0 && done===total) {
      detailsProjectStatusHidden.value = 'completed';
      detailsProjectStatusText.value   = 'Completed';
    }
  }

  detailsAddEmployeeBtn.addEventListener("click", () => {
    const empId   = detailsEmployeeSelect.value;
    const empText = detailsEmployeeSelect.options[detailsEmployeeSelect.selectedIndex].text;
    if (!empId || detailEmployees.find(e=>e.id==empId)) return;
    detailEmployees.push({ id:empId, text:empText });
    renderDetailEmployees();
  });

  function renderDetailEmployees() {
    detailsEmployeesContainer.innerHTML = "";
    detailEmployees.forEach(e=>{
      const div = document.createElement("div");
      div.className = "flex items-center mb-2";
      div.innerHTML = `<span class="flex-1">${e.text}</span><button data-id="${e.id}" class="remove-detail-emp text-red-500">×</button>`;
      detailsEmployeesContainer.appendChild(div);
    });
    detailsEmployeesData.value = JSON.stringify(detailEmployees.map(e=>e.id));
    detailsEmployeeCount.value = detailEmployees.length;
    detailsEmployeesContainer.querySelectorAll(".remove-detail-emp").forEach(btn=>
      btn.addEventListener("click", ()=>{
        detailEmployees = detailEmployees.filter(x=>x.id!=btn.dataset.id);
        renderDetailEmployees();
      })
    );
  }

  function renderDetailInventory() {
    detailsInventoryContainer.innerHTML = "";
    if (detailInventory.length===0) {
      detailsInventoryContainer.textContent = '— Nenhum item alocado';
      return;
    }
    detailInventory.forEach(i=>{
      const div = document.createElement("div");
      div.textContent = `${i.name} (qtde: ${i.quantity})`;
      detailsInventoryContainer.appendChild(div);
    });
  }
});
