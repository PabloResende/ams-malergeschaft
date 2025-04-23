// public/js/projects.js
const baseUrl = window.location.origin + '/ams-malergeschaft/public';

document.addEventListener("DOMContentLoaded", () => {

  // ——————————————   edição/exclusão no modal de detalhes   ——————————————
  const detailsModal               = document.getElementById("projectDetailsModal");
  const closeDetailsBtn            = document.getElementById("closeProjectDetailsModal");
  const cancelDetailsBtn           = document.getElementById("cancelDetailsBtn");
  const deleteProjectBtn           = document.getElementById("deleteProjectBtn");
  const detailsForm                = document.getElementById("projectDetailsForm");
  const saveDetailsBtn             = document.getElementById("saveDetailsBtn");

  // Campos básicos
  const detailsProjectIdEl         = document.getElementById("detailsProjectId");
  const detailsProjectNameEl       = document.getElementById("detailsProjectName");
  const detailsProjectLocationEl   = document.getElementById("detailsProjectLocation");
  const detailsProjectTotalHoursEl = document.getElementById("detailsProjectTotalHours");
  const detailsProjectBudgetEl     = document.getElementById("detailsProjectBudget");
  const detailsProjectEmployeeCountEl = document.getElementById("detailsProjectEmployeeCount");
  const detailsProjectStartDateEl  = document.getElementById("detailsProjectStartDate");
  const detailsProjectEndDateEl    = document.getElementById("detailsProjectEndDate");
  const detailsProjectStatusEl     = document.getElementById("detailsProjectStatus");

  // Contêineres dinâmicos
  const detailsTasksContainer      = document.getElementById("detailsTasksContainer");
  const detailsEmployeesContainer  = document.getElementById("detailsEmployeesContainer");
  const detailsInventoryContainer  = document.getElementById("detailsInventoryContainer");

  // Controles de adição
  const detailsNewTaskInput        = document.getElementById("detailsNewTaskInput");
  const detailsAddTaskBtn          = document.getElementById("detailsAddTaskBtn");
  const detailsEmployeeSelect      = document.getElementById("detailsEmployeeSelect");
  const detailsAddEmployeeBtn      = document.getElementById("detailsAddEmployeeBtn");
  const detailsInventorySelect     = document.getElementById("detailsInventorySelect");
  const detailsInventoryQuantity   = document.getElementById("detailsInventoryQuantity");
  const detailsAddInventoryBtn     = document.getElementById("detailsAddInventoryBtn");

  // Campos ocultos para JSON
  const detailsTasksData           = document.getElementById("detailsTasksData");
  const detailsEmployeesData       = document.getElementById("detailsEmployeesData");
  const detailsInventoryData       = document.getElementById("detailsInventoryData");

  // Arrays internos de edição
  let detailsTaskList = [], detailsEmployeeList = [], detailsInventoryList = [];

  // — Funções de renderização para o modo edição —
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
      const checkbox = row.querySelector("input");
      const removeBtn = row.querySelector("button");
      checkbox.addEventListener("change", e => {
        detailsTaskList[i].completed = e.target.checked;
      });
      removeBtn.addEventListener("click", () => {
        detailsTaskList.splice(i, 1);
        renderDetailsTasks();
      });
      detailsTasksContainer.appendChild(row);
    });
  }

  function renderDetailsEmployees() {
    detailsEmployeesContainer.innerHTML = "";
    detailsEmployeeList.forEach((e, i) => {
      const row = document.createElement("div");
      row.className = "flex items-center mb-2";
      row.innerHTML = `
        <span class="flex-1">${e.name}</span>
        <button class="ml-2 text-red-500">&times;</button>
      `;
      row.querySelector("button").addEventListener("click", () => {
        detailsEmployeeList.splice(i, 1);
        renderDetailsEmployees();
      });
      detailsEmployeesContainer.appendChild(row);
    });
  }

  function renderDetailsInventory() {
    detailsInventoryContainer.innerHTML = "";
    detailsInventoryList.forEach((it, i) => {
      const row = document.createElement("div");
      row.className = "flex items-center mb-2";
      row.innerHTML = `
        <span class="flex-1">${it.name} (Qtd: ${it.quantity})</span>
        <button class="ml-2 text-red-500">&times;</button>
      `;
      row.querySelector("button").addEventListener("click", () => {
        detailsInventoryList.splice(i, 1);
        renderDetailsInventory();
      });
      detailsInventoryContainer.appendChild(row);
    });
  }

  // — Fechar modal —
  closeDetailsBtn.addEventListener("click", () => detailsModal.classList.add("hidden"));
  cancelDetailsBtn.addEventListener("click", () => detailsModal.classList.add("hidden"));
  window.addEventListener("click", e => {
    if (e.target === detailsModal) detailsModal.classList.add("hidden");
  });

  // — Excluir projeto —
  deleteProjectBtn.addEventListener("click", () => {
    if (!confirm("Deseja realmente excluir este projeto?")) return;
    const id = detailsProjectIdEl.value;
    window.location.href = `${baseUrl}/projects/delete?id=${id}`;
  });

  // — Adicionar itens —
  detailsAddTaskBtn.addEventListener("click", () => {
    const desc = detailsNewTaskInput.value.trim();
    if (!desc) return;
    detailsTaskList.push({ description: desc, completed: false });
    detailsNewTaskInput.value = "";
    renderDetailsTasks();
  });

  detailsAddEmployeeBtn.addEventListener("click", () => {
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

  detailsAddInventoryBtn.addEventListener("click", () => {
    const id   = detailsInventorySelect.value;
    const name = detailsInventorySelect.selectedOptions[0]?.text;
    const qty  = parseInt(detailsInventoryQuantity.value, 10);
    if (!id || qty <= 0) return;
    const ex = detailsInventoryList.find(i => i.id === id);
    if (ex) ex.quantity += qty;
    else detailsInventoryList.push({ id, name, quantity: qty });
    detailsInventoryQuantity.value = "";
    renderDetailsInventory();
  });

  // — Preparar dados antes de enviar o form de atualização —
  detailsForm.addEventListener("submit", () => {
    detailsTasksData.value      = JSON.stringify(detailsTaskList);
    detailsEmployeesData.value  = JSON.stringify(detailsEmployeeList.map(e => e.id));
    detailsInventoryData.value  = JSON.stringify(detailsInventoryList);
  });

  // — Quando clicar em um card, carrega dados e habilita edição —
  document.querySelectorAll(".project-item").forEach(card => {
    card.addEventListener("click", function(event) {
      event.preventDefault();
      const projectId = this.dataset.projectId || this.getAttribute("data-id");

      fetch(`${baseUrl}/projects/show?id=${projectId}`)
        .then(r => {
          if (!r.ok) throw new Error(`HTTP ${r.status}`);
          return r.json();
        })
        .then(data => {
          // campos básicos
          detailsProjectIdEl.value            = data.id || "";
          detailsProjectNameEl.value          = data.name || "";
          detailsProjectLocationEl.value      = data.location || "";
          detailsProjectTotalHoursEl.value    = data.total_hours || "";
          detailsProjectBudgetEl.value        = data.budget || "";
          detailsProjectEmployeeCountEl.value = data.employee_count || "";
          detailsProjectStartDateEl.value     = data.start_date || "";
          detailsProjectEndDateEl.value       = data.end_date || "";
          detailsProjectStatusEl.value        = data.status || "";

          // inicializa listas
          detailsTaskList      = (data.tasks || []).map(t => ({ description: t.description, completed: t.completed }));
          detailsEmployeeList  = (data.employees || []).map(e => ({ id: e.id, name: e.name + " " + e.last_name }));
          detailsInventoryList = (data.inventory || []).map(i => ({ id: i.id, name: i.name, quantity: i.quantity }));

          // renderiza
          renderDetailsTasks();
          renderDetailsEmployees();
          renderDetailsInventory();

          // mostra botões
          saveDetailsBtn.classList.remove("hidden");
          deleteProjectBtn.classList.remove("hidden");

          // exibe modal
          detailsModal.classList.remove("hidden");
        })
        .catch(err => {
          console.error("Erro ao carregar detalhes:", err);
          alert("Não foi possível carregar os detalhes do projeto.");
        });
    });
  });
});
