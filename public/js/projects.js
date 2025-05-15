const translations = window.langText || {};
const baseUrl      = window.baseUrl  || '';

document.addEventListener("DOMContentLoaded", () => {
  //
  // ─── Criação de Projetos ────────────────────────────────────────────────
  //
  const addProjectBtn   = document.getElementById("addProjectBtn");
  const projectModal    = document.getElementById("projectModal");
  const closeCreateBtns = document.querySelectorAll("#closeModal, #closeCreateModal");
  const createTasksCont = document.getElementById("createTasksContainer");
  const createEmpsCont  = document.getElementById("createEmployeesContainer");
  const newTaskInput    = document.getElementById("createNewTaskInput");
  const addTaskBtn      = document.getElementById("createAddTaskBtn");
  const empSelect       = document.getElementById("createEmployeeSelect");
  const addEmpBtn       = document.getElementById("createAddEmployeeBtn");
  const tasksData       = document.getElementById("createTasksData");
  const empsData        = document.getElementById("createEmployeesData");
  const empCount        = document.getElementById("createEmployeeCount");
  const projStatusInput = document.getElementById("createProjectStatus");

  let tasks = [];

  addProjectBtn.addEventListener("click", () => projectModal.classList.remove("hidden"));
  closeCreateBtns.forEach(b => b.addEventListener("click", resetCreate));
  window.addEventListener("click", e => {
    if (e.target === projectModal) resetCreate();
  });

  function resetCreate() {
    projectModal.classList.add("hidden");
    tasks = [];
    createEmpsCont.innerHTML = '';
    renderTasks();
    syncCreate();
  }

  // ─── Tasks ─────────────────────────────────────────────────────────────
  addTaskBtn.addEventListener("click", () => {
    const desc = newTaskInput.value.trim();
    if (!desc) return;
    tasks.push({ id: Date.now(), description: desc });
    newTaskInput.value = '';
    renderTasks();
    syncCreate();
  });

  function renderTasks() {
    createTasksCont.innerHTML = '';
    tasks.forEach((t,i) => {
      const div = document.createElement("div");
      div.className = "flex items-center mb-2";
      div.innerHTML = `
        <span class="flex-1">${t.description}</span>
        <button data-i="${i}" class="remove-task text-red-500">×</button>
      `;
      createTasksCont.appendChild(div);
    });
    createTasksCont.querySelectorAll(".remove-task").forEach(btn =>
      btn.addEventListener("click", e => {
        tasks.splice(e.target.dataset.i,1);
        renderTasks();
        syncCreate();
      })
    );
  }

  // ─── Employees (Criação) ───────────────────────────────────────────────
  addEmpBtn.addEventListener("click", () => {
    const id   = empSelect.value;
    const name = empSelect.options[empSelect.selectedIndex].text;
    if (!id) return;

    fetch(`${baseUrl}/projects/checkEmployee?id=${id}`)
      .then(res => res.json())
      .then(json => {
        if (json.count > 0) {
          const msg = translations['employee_already_assigned_message']
                        .replace('{name}', name)
                        .replace('{count}', json.count);
          alert(msg);
        }
        addCreateEmployee(id, name);
      })
      .catch(() => {
        addCreateEmployee(id, name);
      });
  });

  function addCreateEmployee(id, text) {
    if ([...createEmpsCont.querySelectorAll("div")].some(d => d.dataset.id == id)) return;
    const div = document.createElement("div");
    div.dataset.id = id;
    div.className = "flex items-center mb-2";
    div.innerHTML = `
      <span class="flex-1">${text}</span>
      <button class="remove-create-emp text-red-500">×</button>
    `;
    createEmpsCont.appendChild(div);
    div.querySelector(".remove-create-emp").onclick = () => {
      div.remove();
      syncCreate();
    };
    syncCreate();
  }

  function syncCreate() {
    const empIds = [...createEmpsCont.querySelectorAll("div")].map(d => d.dataset.id);
    tasksData.value = JSON.stringify(tasks);
    empsData.value  = JSON.stringify(empIds);
    empCount.value  = empIds.length;
    projStatusInput.value = tasks.length === 0 ? 'pending' : 'in_progress';
  }

  //
  // ─── Detalhes de Projetos ───────────────────────────────────────────────
  //
  const items            = document.querySelectorAll(".project-item");
  const detailsModal     = document.getElementById("projectDetailsModal");
  const closeDetailsBtn  = document.getElementById("closeProjectDetailsModal");
  const cancelDetBtn     = document.getElementById("cancelDetailsBtn");
  const deleteDetBtn     = document.getElementById("deleteDetailsBtn");
  const detId            = document.getElementById("detailsProjectId");
  const detStatusInput   = document.getElementById("detailsProjectStatus");
  const detStart         = document.getElementById("detailsProjectStartDate");
  const detEnd           = document.getElementById("detailsProjectEndDate");
  const detName          = document.getElementById("detailsProjectName");
  const detDesc          = document.getElementById("detailsProjectDescription");
  const detTasksCont     = document.getElementById("detailsTasksContainer");
  const detNewTaskInput  = document.getElementById("detailsNewTaskInput");
  const detAddTaskBtn    = document.getElementById("detailsAddTaskBtn");
  const detEmpsCont      = document.getElementById("detailsEmployeesContainer");
  const detEmpSelect     = document.getElementById("detailsEmployeeSelect");
  const detAddEmpBtn     = document.getElementById("detailsAddEmployeeBtn");
  const detInvCont       = document.getElementById("detailsInventoryContainer");
  const detProjTransBody = document.getElementById("detailsProjTransBody");
  const detTasksData     = document.getElementById("detailsTasksData");
  const detEmpsData      = document.getElementById("detailsEmployeesData");
  const detEmpCount      = document.getElementById("detailsEmployeeCountData");
  const detProgBar       = document.getElementById("detailsProgressBar");
  const detProgText      = document.getElementById("detailsProgressText");

  let detTasks = [], detEmps = [], detInv = [];

  function openDetails()  { detailsModal.classList.remove("hidden"); }
  function closeDetails() { detailsModal.classList.add("hidden"); }

  closeDetailsBtn.addEventListener("click", closeDetails);
  cancelDetBtn.addEventListener("click", closeDetails);
  window.addEventListener("click", e => { if (e.target === detailsModal) closeDetails(); });
  deleteDetBtn.addEventListener("click", () => {
    if (!confirm(translations['confirm_delete_project'])) return;
    window.location.href = `${baseUrl}/projects/delete?id=${detId.value}`;
  });

  items.forEach(i => i.addEventListener("click", () => loadDetails(i.dataset.projectId)));

  function loadDetails(id) {
    fetch(`${baseUrl}/projects/show?id=${id}`, { credentials:'same-origin' })
      .then(r => r.json())
      .then(data => {
        if (data.error) { alert(data.error); return; }
        detId.value          = data.id;
        detStart.value       = data.start_date;
        detEnd.value         = data.end_date;
        detName.value        = data.name;
        detDesc.value        = data.description || '';
        detStatusInput.value = data.status;

        // NÃO exibe dias restantes/atrasado no modal de detalhes

        // tarefas
        detTasks = (data.tasks||[]).map(t => ({
          id: t.id,
          description: t.description,
          completed: !!t.completed
        }));
        renderDetTasks();

        // funcionários
        detEmps = (data.employees||[]).map(e => ({
          id: e.id,
          text: `${e.name} ${e.last_name}`
        }));
        renderDetEmps();

        // inventário
        detInv = data.inventory||[];
        renderDetInv();

        // transações
        fetch(`${baseUrl}/projects/transactions?id=${data.id}`, { credentials:'same-origin' })
          .then(r => r.json())
          .then(renderProjTrans)
          .catch(() => console.warn(translations['error_loading_transactions']));

        activateDetailTab('geral');
        openDetails();
      })
      .catch(() => alert(translations['error_loading_project_details']));
  }

  // Tabs
  const tabBtns = document.querySelectorAll("#projectDetailsModal .tab-btn");
  const tabPans = document.querySelectorAll("#projectDetailsModal .tab-panel");
  function activateDetailTab(tab) {
    tabBtns.forEach(b => {
      const is = b.dataset.tab===tab;
      b.classList.toggle('text-blue-600', is);
      b.classList.toggle('border-b-2', is);
      b.classList.toggle('border-blue-600', is);
      b.classList.toggle('text-gray-600', !is);
    });
    tabPans.forEach(p => p.id===`tab-${tab}` ? p.classList.remove('hidden') : p.classList.add('hidden'));
  }
  tabBtns.forEach(b => b.addEventListener("click", ()=>activateDetailTab(b.dataset.tab)));

  // ─── Detalhes: Tasks ───────────────────────────────────────────────
  detAddTaskBtn.addEventListener("click", () => {
    const d = detNewTaskInput.value.trim();
    if (!d) return;
    detTasks.push({ id: Date.now(), description: d, completed: false });
    detNewTaskInput.value = '';
    renderDetTasks();
  });
  function renderDetTasks() {
    detTasksCont.innerHTML = '';
    detTasks.forEach((t,i) => {
      const div = document.createElement("div");
      div.className = "flex items-center mb-2";
      div.innerHTML = `
        <input type="checkbox" data-i="${i}" ${t.completed?'checked':''} class="mr-2">
        <span class="flex-1">${t.description}</span>`;
      detTasksCont.appendChild(div);
    });
    detTasksData.value = JSON.stringify(detTasks);
    detTasksCont.querySelectorAll('input[type="checkbox"]').forEach(cb =>
      cb.addEventListener("change", e => {
        detTasks[e.target.dataset.i].completed = e.target.checked;
        detTasksData.value = JSON.stringify(detTasks);
        updateDetProgress();
      })
    );
    updateDetProgress();
  }
  function updateDetProgress() {
    const total = detTasks.length, done = detTasks.filter(t=>t.completed).length;
    const pct   = total?Math.round(done/total*100):0;
    detProgBar.style.width  = pct+'%';
    detProgText.textContent = pct+'%';
    detStatusInput.value    = total===0?'pending':(done===total?'completed':'in_progress');
  }

  // ─── Detalhes: Employees ───────────────────────────────────────────────
  detAddEmpBtn.addEventListener("click", () => {
    const id   = detEmpSelect.value;
    const name = detEmpSelect.options[detEmpSelect.selectedIndex].text;
    if (!id) return;

    fetch(`${baseUrl}/projects/checkEmployee?id=${id}&project_id=${detId.value}`)
      .then(res => res.json())
      .then(json => {
        if (json.count > 0) {
          const msg = translations['employee_already_assigned_message']
                        .replace('{name}', name)
                        .replace('{count}', json.count);
          alert(msg);
        }
        addDetailsEmployee(id, name);
      })
      .catch(() => {
        addDetailsEmployee(id, name);
      });
  });

  function addDetailsEmployee(id, text) {
    if ([...detEmpsCont.querySelectorAll("div")].some(d=>d.dataset.id==id)) return;
    const div = document.createElement("div");
    div.dataset.id = id;
    div.className = "flex items-center mb-2";
    div.innerHTML = `
      <span class="flex-1">${text}</span>
      <button class="remove-details-emp text-red-500">×</button>`;
    detEmpsCont.appendChild(div);
    div.querySelector(".remove-details-emp").onclick = () => {
      div.remove();
      syncDetailsEmps();
    };
    syncDetailsEmps();
  }

  function renderDetEmps() {
    detEmpsCont.innerHTML = '';
    detEmps.forEach(e => addDetailsEmployee(e.id, e.text));
  }

  function syncDetailsEmps() {
    const ids = [...detEmpsCont.querySelectorAll("div")].map(d=>d.dataset.id);
    detEmpsData.value = JSON.stringify(ids);
    detEmpCount.value  = ids.length;
  }

  // ─── Detalhes: Inventário ───────────────────────────────────────────────
  function renderDetInv() {
    detInvCont.innerHTML = '';
    if (!detInv.length) {
      detInvCont.textContent = translations['no_inventory_allocated'];
      return;
    }
    detInv.forEach(i => {
      const d = document.createElement("div");
      d.textContent = `${i.name} (qtde: ${i.quantity})`;
      detInvCont.appendChild(d);
    });
  }

  // ─── Detalhes: Transações ───────────────────────────────────────────────
  function renderProjTrans(trans) {
    detProjTransBody.innerHTML = '';
    if (!trans.length) {
      detProjTransBody.innerHTML = `
        <tr><td colspan="3" class="p-4 text-center text-gray-500">
          ${translations['no_project_transactions']}
        </td></tr>`;
      return;
    }
    trans.forEach(tx => {
      const tr = document.createElement("tr");
      tr.className = 'border-t';
      tr.innerHTML = `
        <td class="p-2">${new Date(tx.date).toLocaleDateString(translations['locale'])}</td>
        <td class="p-2">${tx.type.charAt(0).toUpperCase()+tx.type.slice(1)}</td>
        <td class="p-2 text-right">R$ ${parseFloat(tx.amount).toFixed(2).replace('.', ',')}</td>`;
      detProjTransBody.appendChild(tr);
    });
  }

});
