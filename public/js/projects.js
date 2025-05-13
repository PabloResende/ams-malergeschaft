// public/js/projects.js

// captura traduções e URL base (injetadas pela view)
const translations = window.langText || {};
const baseUrl      = window.baseUrl  || '';

document.addEventListener("DOMContentLoaded", () => {
  //
  // ─── Criação de Projetos ────────────────────────────────────────────────
  //
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
    if (!empId || createEmployees.some(e => e.id == empId)) return;

    fetch(`${baseUrl}/projects/checkEmployee?id=${empId}`)
      .then(res => res.ok ? res.json() : Promise.reject())
      .then(json => {
        if (json.count > 0) {
          const msgTemplate = translations['employee_already_assigned_message']
            || 'Este funcionário já está alocado em {count} projeto(s) em andamento.';
          alert(msgTemplate.replace('{count}', json.count));
        }
        createEmployees.push({ id: empId, text: empText });
        renderCreateEmployees();
        syncCreateData();
      })
      .catch(() => {
        // fallback mesmo sem servidor
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
    createTasksData.value     = JSON.stringify(createTasks);
    createEmployeesData.value = JSON.stringify(createEmployees.map(e => e.id));
    createEmployeeCount.value = createEmployees.length;
    createProjectStatus.value = createTasks.length === 0 ? 'pending' : 'in_progress';
  }

  //
  // ─── Detalhes de Projetos ───────────────────────────────────────────────
  //
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
  const detailsInventoryContainer  = document.getElementById("detailsInventoryContainer");
  const detailsTasksData           = document.getElementById("detailsTasksData");
  const detailsEmployeesData       = document.getElementById("detailsEmployeesData");
  const detailsEmployeeCount       = document.getElementById("detailsEmployeeCountData");
  const detailsProjTransBody       = document.getElementById("detailsProjTransBody");

  let detailTasks     = [];
  let detailEmployees = [];
  let detailInventory = [];

  function closeDetails() {
    detailsModal.classList.add("hidden");
  }

  closeDetailsBtn.addEventListener("click", closeDetails);
  cancelDetailsBtn.addEventListener("click", closeDetails);
  window.addEventListener("click", e => {
    if (e.target === detailsModal) closeDetails();
  });
  deleteDetailsBtn.addEventListener("click", () => {
    const confirmMsg = translations['confirm_delete_project']
      || 'Tem certeza que deseja excluir este projeto?';
    if (!confirm(confirmMsg)) return;
    const id = detailsProjectId.value;
    window.location.href = `${baseUrl}/projects/delete?id=${id}`;
  });

  projectItems.forEach(item =>
    item.addEventListener("click", () => loadDetails(item.dataset.projectId))
  );

  function loadDetails(id) {
    fetch(`${baseUrl}/projects/show?id=${id}`, { credentials:'same-origin' })
      .then(r => r.ok ? r.json() : Promise.reject())
      .then(data => {
        if (data.error) {
          alert(data.error);
          return;
        }
        // geral
        detailsProjectId.value   = data.id;
        detailsStartDate.value   = data.start_date;
        detailsEndDate.value     = data.end_date;
        detailsName.value        = data.name;
        detailsDescription.value = data.description || '';

        // tarefas
        detailTasks = (data.tasks || []).map(t => ({
          id: t.id,
          description: t.description,
          completed: !!t.completed
        }));
        renderDetailTasks();

        // funcionários
        detailEmployees = (data.employees || []).map(e => ({
          id: e.id,
          text: `${e.name} ${e.last_name}`
        }));
        renderDetailEmployees();

        // inventário
        detailInventory = data.inventory || [];
        renderDetailInventory();

        // transações
        fetch(`${baseUrl}/projects/transactions?id=${data.id}`, { credentials:'same-origin' })
          .then(r => r.ok ? r.json() : Promise.reject())
          .then(renderProjectTransactions)
          .catch(() => console.warn(
            translations['error_loading_transactions']
            || 'Não foi possível carregar transações.'
          ));

        activateDetailTab('geral');
        detailsModal.classList.remove("hidden");
      })
      .catch(() => alert(
        translations['error_loading_project_details']
        || 'Não foi possível carregar detalhes do projeto.'
      ));
  }

  // Tabs
  const detailTabButtons = document.querySelectorAll("#projectDetailsModal .tab-btn");
  const detailTabPanels  = document.querySelectorAll("#projectDetailsModal .tab-panel");
  function activateDetailTab(tab) {
    detailTabButtons.forEach(b => {
      const is = b.dataset.tab === tab;
      b.classList.toggle('text-blue-600', is);
      b.classList.toggle('border-b-2', is);
      b.classList.toggle('border-blue-600', is);
      b.classList.toggle('text-gray-600', !is);
    });
    detailTabPanels.forEach(p => {
      p.id === `tab-${tab}` ? p.classList.remove('hidden') : p.classList.add('hidden');
    });
  }
  detailTabButtons.forEach(b =>
    b.addEventListener("click", () => activateDetailTab(b.dataset.tab))
  );

  // tarefas
  detailsAddTaskBtn.addEventListener("click", () => {
    const d = detailsNewTaskInput.value.trim();
    if (!d) return;
    detailTasks.push({ id: Date.now(), description: d, completed: false });
    detailsNewTaskInput.value = "";
    renderDetailTasks();
  });
  function renderDetailTasks() {
    detailsTasksContainer.innerHTML = '';
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
    detailsTasksContainer.querySelectorAll('input[type="checkbox"]').forEach(cb => {
      cb.addEventListener("change", e => {
        detailTasks[e.target.dataset.index].completed = e.target.checked;
        detailsTasksData.value = JSON.stringify(detailTasks);
        updateDetailProgress();
      });
    });
    updateDetailProgress();
  }
  function updateDetailProgress() {
    const tot  = detailTasks.length;
    const done = detailTasks.filter(t => t.completed).length;
    const pct  = tot ? Math.round(done / tot * 100) : 0;
    detailsProgressBar.style.width = pct + '%';
    detailsProgressText.textContent = pct + '%';
    detailsProjectStatusHidden.value = tot === 0
      ? 'pending'
      : (done === tot ? 'completed' : 'in_progress');
  }

  // funcionários
  detailsAddEmployeeBtn.addEventListener("click", () => {
    const eid = detailsEmployeeSelect.value;
    const txt = detailsEmployeeSelect.options[detailsEmployeeSelect.selectedIndex].text;
    if (!eid || detailEmployees.some(e => e.id == eid)) return;
    fetch(`${baseUrl}/projects/checkEmployee?id=${eid}`)
      .then(r => r.ok ? r.json() : Promise.reject())
      .then(js => {
        if (js.count > 0) {
          const msgTpl = translations['employee_already_assigned_message']
            || 'Este funcionário já está alocado em {count} projeto(s) em andamento.';
          alert(msgTpl.replace('{count}', js.count));
        }
        detailEmployees.push({ id: eid, text: txt });
        renderDetailEmployees();
      })
      .catch(() => {
        detailEmployees.push({ id: eid, text: txt });
        renderDetailEmployees();
      });
  });
  function renderDetailEmployees() {
    detailsEmployeesContainer.innerHTML = '';
    detailEmployees.forEach((e, i) => {
      const div = document.createElement("div");
      div.className = "flex items-center mb-2";
      div.innerHTML = `
        <span class="flex-1">${e.text}</span>
        <button data-index="${i}" class="text-red-500">×</button>
      `;
      detailsEmployeesContainer.appendChild(div);
    });
    detailsEmployeesData.value = JSON.stringify(detailEmployees.map(e => e.id));
    detailsEmployeeCount.value = detailEmployees.length;
    detailsEmployeesContainer.querySelectorAll('button').forEach(btn => {
      btn.addEventListener("click", () => {
        detailEmployees.splice(btn.dataset.index, 1);
        renderDetailEmployees();
      });
    });
  }

  // inventário
  function renderDetailInventory() {
    detailsInventoryContainer.innerHTML = '';
    if (!detailInventory.length) {
      detailsInventoryContainer.textContent = translations['no_inventory_allocated']
        || '— Nenhum item alocado';
      return;
    }
    detailInventory.forEach(i => {
      const div = document.createElement("div");
      div.textContent = `${i.name} (qtde: ${i.quantity})`;
      detailsInventoryContainer.appendChild(div);
    });
  }

  // transações do projeto
  function renderProjectTransactions(trans) {
    detailsProjTransBody.innerHTML = '';
    if (!trans.length) {
      detailsProjTransBody.innerHTML = `
        <tr>
          <td colspan="3" class="p-4 text-center text-gray-500">
            ${translations['no_project_transactions'] || 'Sem transações relacionadas'}
          </td>
        </tr>`;
      return;
    }
    trans.forEach(tx => {
      const tr = document.createElement("tr");
      tr.className = 'border-t';
      tr.innerHTML = `
        <td class="p-2">
          ${new Date(tx.date).toLocaleDateString(
            translations['locale'] || undefined)}
        </td>
        <td class="p-2">
          ${tx.type.charAt(0).toUpperCase() + tx.type.slice(1)}
        </td>
        <td class="p-2 text-right">
          R$ ${parseFloat(tx.amount).toFixed(2).replace('.', ',')}
        </td>
      `;
      detailsProjTransBody.appendChild(tr);
    });
  }
});
