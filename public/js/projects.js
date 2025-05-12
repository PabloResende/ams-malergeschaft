// public/js/projects.js

const baseUrl = window.location.origin + '/ams-malergeschaft/public';

document.addEventListener("DOMContentLoaded", () => {
  // ─── Criação de Projetos ────────────────────────────────────────────────
  const addProjectBtn            = document.getElementById("addProjectBtn");
  const projectModal             = document.getElementById("projectModal");
  const closeCreateBtns          = document.querySelectorAll("#closeModal, #closeCreateModal");
  const createTasksContainer     = document.getElementById("createTasksContainer");
  const createEmployeesContainer = document.getElementById("createEmployeesContainer");
  const createNewTaskInput       = document.getElementById("createNewTaskInput");
  const createAddTaskBtn         = document.getElementById("createAddTaskBtn");
  const createEmployeeSelect     = document.getElementById("createEmployeeSelect");
  const createAddEmployeeBtn     = document.getElementById("createAddEmployeeBtn");
  const createTasksData          = document.getElementById("createTasksData");
  const createEmployeesData      = document.getElementById("createEmployeesData");
  const createEmployeeCount      = document.getElementById("createEmployeeCount");
  const createProjectStatus      = document.getElementById("createProjectStatus");

  let createTasks     = [];
  let createEmployees = [];

  addProjectBtn.addEventListener("click", () => projectModal.classList.remove("hidden"));
  closeCreateBtns.forEach(btn => btn.addEventListener("click", resetCreateModal));
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

  createAddTaskBtn.addEventListener("click", () => {
    const desc = createNewTaskInput.value.trim();
    if (!desc) return;
    createTasks.push({ id: Date.now(), description: desc });
    createNewTaskInput.value = "";
    renderCreateTasks();
    syncCreateData();
  });

  function renderCreateTasks() {
    createTasksContainer.innerHTML = "";
    createTasks.forEach((t, i) => {
      const div = document.createElement("div");
      div.className = "flex items-center mb-2";
      div.innerHTML = `
        <span class="flex-1">${t.description}</span>
        <button data-index="${i}" class="remove-create-task text-red-500">×</button>
      `;
      createTasksContainer.appendChild(div);
    });
    createTasksContainer.querySelectorAll(".remove-create-task").forEach(btn =>
      btn.addEventListener("click", () => {
        createTasks.splice(btn.dataset.index, 1);
        renderCreateTasks();
        syncCreateData();
      })
    );
  }

  createAddEmployeeBtn.addEventListener("click", () => {
    const empId   = createEmployeeSelect.value;
    const empText = createEmployeeSelect.options[createEmployeeSelect.selectedIndex].text;
    if (!empId || createEmployees.find(e => e.id == empId)) return;

    fetch(`${baseUrl}/projects/checkEmployee?id=${empId}`)
      .then(res => res.ok ? res.json() : Promise.reject(res.status))
      .then(json => {
        if (json.count > 0) {
          alert(`Este funcionário já está alocado em ${json.count} projeto(s) em andamento.`);
        }
        createEmployees.push({ id: empId, text: empText });
        renderCreateEmployees();
        syncCreateData();
      })
      .catch(() => {
        createEmployees.push({ id: empId, text: empText });
        renderCreateEmployees();
        syncCreateData();
      });
  });

  function renderCreateEmployees() {
    createEmployeesContainer.innerHTML = "";
    createEmployees.forEach((e, i) => {
      const div = document.createElement("div");
      div.className = "flex items-center mb-2";
      div.innerHTML = `
        <span class="flex-1">${e.text}</span>
        <button data-index="${i}" class="remove-create-emp text-red-500">×</button>
      `;
      createEmployeesContainer.appendChild(div);
    });
    createEmployeesContainer.querySelectorAll(".remove-create-emp").forEach(btn =>
      btn.addEventListener("click", () => {
        createEmployees.splice(btn.dataset.index, 1);
        renderCreateEmployees();
        syncCreateData();
      })
    );
  }

  function syncCreateData() {
    createTasksData.value      = JSON.stringify(createTasks);
    createEmployeesData.value  = JSON.stringify(createEmployees.map(e => e.id));
    createEmployeeCount.value  = createEmployees.length;
    // status automático: sem tarefas = pending, com tarefas = in_progress
    createProjectStatus.value  = createTasks.length === 0 ? 'pending' : 'in_progress';
  }

  // ─── Detalhes de Projetos ───────────────────────────────────────────────
  const projectItems               = document.querySelectorAll(".project-item");
  const detailsModal               = document.getElementById("projectDetailsModal");
  const closeDetailsBtn            = document.getElementById("closeProjectDetailsModal");
  const cancelDetailsBtn           = document.getElementById("cancelDetailsBtn");
  const deleteDetailsBtn           = document.getElementById("deleteDetailsBtn");
  const detailsProjectId           = document.getElementById("detailsProjectId");
  const detailsProjectStatusHidden = document.getElementById("detailsProjectStatus");
  const detailsStartDate           = document.getElementById("detailsProjectStartDate");
  const detailsEndDate             = document.getElementById("detailsProjectEndDate");
  const detailsName                = document.getElementById("detailsProjectName");
  const detailsDescription         = document.getElementById("detailsProjectDescription");
  const detailsProgressBar         = document.getElementById("detailsProgressBar");
  const detailsProgressText        = document.getElementById("detailsProgressText");
  const detailsTasksContainer      = document.getElementById("detailsTasksContainer");
  const detailsNewTaskInput        = document.getElementById("detailsNewTaskInput");
  const detailsAddTaskBtn          = document.getElementById("detailsAddTaskBtn");
  const detailsEmployeesContainer  = document.getElementById("detailsEmployeesContainer");
  const detailsEmployeeSelect      = document.getElementById("detailsEmployeeSelect");
  const detailsAddEmployeeBtn      = document.getElementById("detailsAddEmployeeBtn");
  const detailsTasksData           = document.getElementById("detailsTasksData");
  const detailsEmployeesData       = document.getElementById("detailsEmployeesData");
  const detailsEmployeeCount       = document.getElementById("detailsEmployeeCountData");
  const detailsInventoryContainer  = document.getElementById("detailsInventoryContainer");

  let detailTasks     = [];
  let detailEmployees = [];
  let detailInventory = [];

  // abrir/fechar detalhes
  projectItems.forEach(item =>
    item.addEventListener("click", () => loadDetails(item.dataset.projectId))
  );
  closeDetailsBtn.addEventListener("click", closeDetails);
  cancelDetailsBtn.addEventListener("click", closeDetails);
  window.addEventListener("click", e => {
    if (e.target === detailsModal) closeDetails();
  });
  deleteDetailsBtn.addEventListener("click", () => {
    const id = detailsProjectId.value;
    if (!id) return;
    if (confirm("Tem certeza que deseja excluir este projeto?")) {
      window.location.href = `${baseUrl}/projects/delete?id=${id}`;
    }
  });

  function loadDetails(projectId) {
    fetch(`${baseUrl}/projects/show?id=${projectId}`, { credentials: 'same-origin' })
      .then(res => res.ok ? res.json() : Promise.reject(res.status))
      .then(data => {
        if (data.error) return alert(data.error);

        detailsProjectId.value     = data.id;
        detailsStartDate.value     = data.start_date;
        detailsEndDate.value       = data.end_date;
        detailsName.value          = data.name;
        detailsDescription.value   = data.description || '';

        detailTasks     = (data.tasks     || []).map(t => ({
          id: t.id,
          description: t.description,
          completed: !!t.completed
        }));
        detailEmployees = (data.employees || []).map(e => ({
          id: e.id,
          text: e.name + ' ' + e.last_name
        }));
        detailInventory = data.inventory || [];

        renderDetailTasks();
        renderDetailEmployees();
        renderDetailInventory();
        updateDetailProgress();   // gera status e progress
        activateDetailTab('geral');
        detailsModal.classList.remove("hidden");
      })
      .catch(err => {
        console.error(err);
        alert("Não foi possível carregar detalhes do projeto.");
      });
  }

  function closeDetails() {
    detailsModal.classList.add("hidden");
  }

  // Tabs Detalhes
  const detailTabButtons = document.querySelectorAll("#projectDetailsModal .tab-btn");
  const detailTabPanels  = document.querySelectorAll("#projectDetailsModal .tab-panel");
  function activateDetailTab(tabName) {
    detailTabButtons.forEach(b => {
      if (b.dataset.tab === tabName) {
        b.classList.replace('text-gray-600','text-blue-600');
        b.classList.add('border-b-2','border-blue-600');
      } else {
        b.classList.replace('text-blue-600','text-gray-600');
        b.classList.remove('border-b-2','border-blue-600');
      }
    });
    detailTabPanels.forEach(p => {
      p.id === `tab-${tabName}` ? p.classList.remove('hidden') : p.classList.add('hidden');
    });
  }
  detailTabButtons.forEach(btn =>
    btn.addEventListener("click", () => activateDetailTab(btn.dataset.tab))
  );

  // Tasks
  detailsAddTaskBtn.addEventListener("click", () => {
    const desc = detailsNewTaskInput.value.trim();
    if (!desc) return;
    detailTasks.push({ id: Date.now(), description: desc, completed: false });
    detailsNewTaskInput.value = "";
    renderDetailTasks();
  });

  function renderDetailTasks() {
    detailsTasksContainer.innerHTML = "";
    detailTasks.forEach((t, i) => {
      const div = document.createElement("div");
      div.className = "flex items-center mb-2";
      div.innerHTML = `
        <input type="checkbox" data-index="${i}" ${t.completed ? 'checked' : ''} class="mr-2">
        <span class="flex-1">${t.description}</span>
      `;
      detailsTasksContainer.appendChild(div);
    });
    detailsTasksData.value = JSON.stringify(detailTasks);
    updateDetailProgress();
  }

  detailsTasksContainer.addEventListener("change", e => {
    if (e.target.matches('input[type="checkbox"]')) {
      const i = e.target.dataset.index;
      detailTasks[i].completed = e.target.checked;
      detailsTasksData.value    = JSON.stringify(detailTasks);
      updateDetailProgress();
    }
  });

  function updateDetailProgress() {
    const total = detailTasks.length;
    const done  = detailTasks.filter(t => t.completed).length;
    const pct   = total ? Math.round(done / total * 100) : 0;
    detailsProgressBar.style.width  = pct + '%';
    detailsProgressText.textContent = pct + '%';
    const status = total === 0
      ? 'pending'
      : (done === total ? 'completed' : 'in_progress');
    detailsProjectStatusHidden.value = status;
  }

  // Employees
  detailsAddEmployeeBtn.addEventListener("click", () => {
    const empId   = detailsEmployeeSelect.value;
    const empText = detailsEmployeeSelect.options[detailsEmployeeSelect.selectedIndex].text;
    if (!empId || detailEmployees.find(e => e.id == empId)) return;

    fetch(`${baseUrl}/projects/checkEmployee?id=${empId}`)
      .then(res => res.ok ? res.json() : Promise.reject(res.status))
      .then(json => {
        if (json.count > 0) {
          alert(`Este funcionário já está alocado em ${json.count} projeto(s) em andamento.`);
        }
        detailEmployees.push({ id: empId, text: empText });
        renderDetailEmployees();
      })
      .catch(() => {
        detailEmployees.push({ id: empId, text: empText });
        renderDetailEmployees();
      });
  });

  function renderDetailEmployees() {
    detailsEmployeesContainer.innerHTML = "";
    detailEmployees.forEach((e, i) => {
      const div = document.createElement("div");
      div.className = "flex items-center mb-2";
      div.innerHTML = `
        <span class="flex-1">${e.text}</span>
        <button data-index="${i}" class="remove-detail-emp text-red-500">×</button>
      `;
      detailsEmployeesContainer.appendChild(div);
    });
    detailsEmployeesData.value = JSON.stringify(detailEmployees.map(e => e.id));
    detailsEmployeeCount.value = detailEmployees.length;
    detailsEmployeesContainer.querySelectorAll(".remove-detail-emp").forEach(btn =>
      btn.addEventListener("click", () => {
        detailEmployees.splice(btn.dataset.index, 1);
        renderDetailEmployees();
      })
    );
  }

  // Inventory
  function renderDetailInventory() {
    detailsInventoryContainer.innerHTML = "";
    if (!detailInventory.length) {
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
