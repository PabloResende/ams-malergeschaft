const baseUrl = window.location.origin + '/ams-malergeschaft/public';

document.addEventListener("DOMContentLoaded", () => {
  // —————— MODAL DE CRIAÇÃO ——————
  const projectModal       = document.getElementById("projectModal");
  const addProjectBtn      = document.getElementById("addProjectBtn");
  const closeModal         = document.getElementById("closeModal");
  const projectForm        = document.getElementById("projectForm");

  const tasksContainer     = document.getElementById("tasksContainer");
  const employeesContainer = document.getElementById("employeesContainer");

  const newTaskInput       = document.getElementById("newTaskInput");
  const addTaskBtn         = document.getElementById("addTaskBtn");
  const employeeSelect     = document.getElementById("employeeSelect");

  const tasksData          = document.getElementById("tasksData");
  const empCountDataCreate = document.getElementById("employeeCountDataCreate");

  let taskList     = [];
  let employeeList = [];

  function renderTasks() {
    tasksContainer.innerHTML = "";
    taskList.forEach((t, i) => {
      const row = document.createElement("div");
      row.className = "flex items-center mb-2";
      row.innerHTML = `<input type="checkbox" class="mr-2" ${t.completed ? "checked" : ""}><span>${t.description}</span>`;
      row.querySelector("input").addEventListener("change", e => {
        taskList[i].completed = e.target.checked;
      });
      tasksContainer.appendChild(row);
    });
  }

  function renderEmployees() {
    employeesContainer.innerHTML = "";
    employeeList.forEach((e, i) => {
      const row = document.createElement("div");
      row.className = "flex items-center mb-2";
      row.innerHTML = `<span class="flex-1">${e.name}</span><button class="ml-2 text-red-500">&times;</button>`;
      row.querySelector("button").addEventListener("click", () => {
        employeeList.splice(i, 1);
        renderEmployees();
      });
      employeesContainer.appendChild(row);
    });
  }

  // abrir modal de criação
  addProjectBtn.addEventListener("click", () => {
    projectModal.classList.remove("hidden");
  });
  closeModal.addEventListener("click", () => {
    projectModal.classList.add("hidden");
  });
  window.addEventListener("click", e => {
    if (e.target === projectModal) projectModal.classList.add("hidden");
  });

  // adicionar tarefa
  addTaskBtn.addEventListener("click", () => {
    const desc = newTaskInput.value.trim();
    if (!desc) return;
    taskList.push({ description: desc, completed: false });
    newTaskInput.value = "";
    renderTasks();
  });

  // adicionar funcionário
  employeeSelect.addEventListener("change", () => {
    const id   = employeeSelect.value;
    const name = employeeSelect.selectedOptions[0]?.text;
    if (!id || employeeList.some(e => e.id === id)) return;
    fetch(`${baseUrl}/employees/checkAllocation`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ emp_id: id })
    })
    .then(r => r.json())
    .then(j => {
      if (j.allocated) alert(`Já alocado em ${j.count} projeto(s)!`);
      employeeList.push({ id, name });
      renderEmployees();
    });
  });

  // antes de enviar o form, popula hidden fields
  projectForm.addEventListener("submit", () => {
    tasksData.value = JSON.stringify(taskList);
    empCountDataCreate.value = employeeList.length;
  });

  // —————— MODAL DE DETALHES / EDIÇÃO ——————
  const detailsModal           = document.getElementById("projectDetailsModal");
  const closeDetailsBtn        = document.getElementById("closeProjectDetailsModal");
  const cancelDetailsBtn       = document.getElementById("cancelDetailsBtn");
  const deleteProjectBtn       = document.getElementById("deleteProjectBtn");
  const detailsForm            = document.getElementById("projectDetailsForm");
  const saveDetailsBtn         = document.getElementById("saveDetailsBtn");

  const detailsProjectIdEl     = document.getElementById("detailsProjectId");
  const detailsProjectStatusEl = document.getElementById("detailsProjectStatus");

  const detailsProgressBar     = document.getElementById("detailsProgressBar");
  const detailsProgressText    = document.getElementById("detailsProgressText");

  const detailsTasksContainer    = document.getElementById("detailsTasksContainer");
  const detailsEmployeesContainer= document.getElementById("detailsEmployeesContainer");

  const detailsNewTaskInput     = document.getElementById("detailsNewTaskInput");
  const detailsAddTaskBtn       = document.getElementById("detailsAddTaskBtn");
  const detailsEmployeeSelect   = document.getElementById("detailsEmployeeSelect");

  const detailsTasksData        = document.getElementById("detailsTasksData");
  const detailsEmpCountData     = document.getElementById("detailsEmployeeCountData");

  let detailsTaskList     = [];
  let detailsEmployeeList = [];

  function updateDetailsProgress() {
    const total = detailsTaskList.length;
    const done  = detailsTaskList.filter(t => t.completed).length;
    const pct   = total ? Math.round(done / total * 100) : 0;
    detailsProgressBar.style.width = pct + "%";
    detailsProgressText.innerText  = pct + "%";
    detailsProjectStatusEl.disabled = pct < 100;
    let h = detailsForm.querySelector("input[name=progress]");
    if (!h) {
      h = document.createElement("input");
      h.type = "hidden";
      h.name = "progress";
      detailsForm.appendChild(h);
    }
    h.value = pct;
  }

  function renderDetailsTasks() {
    detailsTasksContainer.innerHTML = "";
    detailsTaskList.forEach((t, i) => {
      const row = document.createElement("div");
      row.className = "flex items-center mb-2";
      row.innerHTML = `
        <input type="checkbox" class="mr-2" ${t.completed ? "checked" : ""}>
        <span class="flex-1">${t.description}</span>
        <button class="ml-2 text-red-500">&times;</button>
      `;
      row.querySelector("input").addEventListener("change", e => {
        detailsTaskList[i].completed = e.target.checked;
        updateDetailsProgress();
      });
      row.querySelector("button").addEventListener("click", () => {
        detailsTaskList.splice(i, 1);
        renderDetailsTasks();
      });
      detailsTasksContainer.appendChild(row);
    });
    updateDetailsProgress();
  }

  function renderDetailsEmployees() {
    detailsEmployeesContainer.innerHTML = "";
    detailsEmployeeList.forEach((e, i) => {
      const row = document.createElement("div");
      row.className = "flex items-center mb-2";
      row.innerHTML = `<span class="flex-1">${e.name}</span><button class="ml-2 text-red-500">&times;</button>`;
      row.querySelector("button").addEventListener("click", () => {
        detailsEmployeeList.splice(i, 1);
        renderDetailsEmployees();
      });
      detailsEmployeesContainer.appendChild(row);
    });
    detailsEmpCountData.value = detailsEmployeeList.length;
  }

  closeDetailsBtn.addEventListener("click", () => detailsModal.classList.add("hidden"));
  cancelDetailsBtn.addEventListener("click", () => detailsModal.classList.add("hidden"));
  window.addEventListener("click", e => {
    if (e.target === detailsModal) detailsModal.classList.add("hidden");
  });

  deleteProjectBtn.addEventListener("click", () => {
    if (!confirm("Deseja realmente excluir este projeto?")) return;
    const id = detailsProjectIdEl.value;
    window.location.href = `${baseUrl}/projects/delete?id=${id}`;
  });

  detailsAddTaskBtn.addEventListener("click", () => {
    const desc = detailsNewTaskInput.value.trim();
    if (!desc) return;
    detailsTaskList.push({ description: desc, completed: false });
    detailsNewTaskInput.value = "";
    renderDetailsTasks();
  });

  detailsEmployeeSelect.addEventListener("change", () => {
    const id   = detailsEmployeeSelect.value;
    const name = detailsEmployeeSelect.selectedOptions[0]?.text;
    if (!id || detailsEmployeeList.some(e => e.id === id)) return;
    fetch(`${baseUrl}/employees/checkAllocation`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ emp_id: id })
    })
    .then(r => r.json())
    .then(j => {
      if (j.allocated) alert(`Já alocado em ${j.count} projeto(s)!`);
      detailsEmployeeList.push({ id, name });
      renderDetailsEmployees();
    });
  });

  detailsForm.addEventListener("submit", () => {
    detailsTasksData.value  = JSON.stringify(detailsTaskList);
    detailsEmpCountData.value = detailsEmployeeList.length;
  });

  // abrir detalhe ao clicar no card
  document.querySelectorAll(".project-item").forEach(card => {
    card.addEventListener("click", function(event) {
      event.preventDefault();
      const projectId = this.dataset.projectId;
      fetch(`${baseUrl}/projects/show?id=${projectId}`)
        .then(r => r.ok ? r.json() : Promise.reject(r.status))
        .then(data => {
          // popula campos
          document.getElementById("detailsProjectId").value        = data.id || "";
          document.getElementById("detailsProjectName").value      = data.name || "";
          document.getElementById("detailsProjectLocation").value  = data.location || "";
          document.getElementById("detailsProjectTotalHours").value= data.total_hours || "";
          document.getElementById("detailsProjectBudget").value    = data.budget || "";
          document.getElementById("detailsProjectStatus").value    = data.status || "";

          // popula listas
          detailsTaskList     = (data.tasks     || []).map(t => ({ description: t.description, completed: t.completed }));
          detailsEmployeeList = (data.employees || []).map(e => ({ id: e.id, name: `${e.name} ${e.last_name}` }));

          renderDetailsTasks();
          renderDetailsEmployees();

          saveDetailsBtn.classList.remove("hidden");
          deleteProjectBtn.classList.remove("hidden");
          detailsModal.classList.remove("hidden");
        })
        .catch(err => {
          console.error("Erro ao carregar detalhes:", err);
          alert("Não foi possível carregar os detalhes do projeto.");
        });
    });
  });
});
