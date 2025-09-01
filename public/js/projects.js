const translations = window.langText || {};
const baseUrl = window.baseUrl || "";
const clients = window.clients || [];

document.addEventListener("DOMContentLoaded", () => {
  // ─── Criar Projeto ──────────────────────────────────────────────────────
  const addProjectBtn = document.getElementById("addProjectBtn");
  const projectModal = document.getElementById("projectModal");
  const closeCreateBtns = document.querySelectorAll(
    "#closeModal, #closeCreateModal"
  );
  const createTasksCont = document.getElementById("createTasksContainer");
  const createEmpsCont = document.getElementById("createEmployeesContainer");
  const newTaskInput = document.getElementById("createNewTaskInput");
  const addTaskBtn = document.getElementById("createAddTaskBtn");
  const empSelect = document.getElementById("createEmployeeSelect");
  const addEmpBtn = document.getElementById("createAddEmployeeBtn");
  const tasksData = document.getElementById("createTasksData");
  const empsData = document.getElementById("createEmployeesData");
  const empCount = document.getElementById("createEmployeeCount");
  const projStatusInput = document.getElementById("createProjectStatus");

  let tasks = [];

  if (addProjectBtn) {
    addProjectBtn.addEventListener("click", () =>
      projectModal.classList.remove("hidden")
    );
  }

  closeCreateBtns.forEach((b) => b.addEventListener("click", resetCreate));
  window.addEventListener("click", (e) => {
    if (e.target === projectModal) resetCreate();
  });

  function resetCreate() {
    if (projectModal) {
      projectModal.classList.add("hidden");
    }
    tasks = [];
    if (createEmpsCont) {
      createEmpsCont.innerHTML = "";
    }
    renderTasks();
    syncCreate();
  }

  if (addTaskBtn) {
    addTaskBtn.addEventListener("click", () => {
      const desc = newTaskInput.value.trim();
      if (!desc) return;
      tasks.push({ id: Date.now(), description: desc });
      newTaskInput.value = "";
      renderTasks();
      syncCreate();
    });
  }

  function renderTasks() {
    if (!createTasksCont) return;
    createTasksCont.innerHTML = "";
    tasks.forEach((t, i) => {
      const div = document.createElement("div");
      div.className = "flex items-center mb-2";
      div.innerHTML = `
        <span class="flex-1">${escapeHtml(t.description)}</span>
        <button data-i="${i}" class="remove-task text-red-500">×</button>
      `;
      createTasksCont.appendChild(div);
    });
    createTasksCont.querySelectorAll(".remove-task").forEach((btn) =>
      btn.addEventListener("click", (e) => {
        tasks.splice(e.target.dataset.i, 1);
        renderTasks();
        syncCreate();
      })
    );
  }

  if (addEmpBtn && empSelect) {
    addEmpBtn.addEventListener("click", () => {
      const id = empSelect.value;
      const name = empSelect.options[empSelect.selectedIndex].text;
      if (!id) return;

      fetch(`${baseUrl}/projects/checkEmployee?id=${id}`)
        .then((res) => res.json())
        .then((json) => {
          if (json.count > 0) {
            const msg = (
              translations["employee_already_assigned_message"] ||
              "Employee {name} already assigned to {count} projects"
            )
              .replace("{name}", name)
              .replace("{count}", json.count);
            alert(msg);
          }
          addCreateEmployee(id, name);
        })
        .catch(() => addCreateEmployee(id, name));
    });
  }

  function addCreateEmployee(id, text) {
    if (!createEmpsCont) return;
    if ([...createEmpsCont.children].some((d) => d.dataset.id == id)) return;

    const div = document.createElement("div");
    div.dataset.id = id;
    div.className = "flex items-center mb-2";
    div.innerHTML = `
      <span class="flex-1">${escapeHtml(text)}</span>
      <button class="remove-create-emp text-red-500">×</button>
    `;
    createEmpsCont.appendChild(div);

    const removeBtn = div.querySelector(".remove-create-emp");
    if (removeBtn) {
      removeBtn.onclick = () => {
        div.remove();
        syncCreate();
      };
    }
    syncCreate();
  }

  function syncCreate() {
    const empIds = createEmpsCont
      ? [...createEmpsCont.children].map((d) => d.dataset.id)
      : [];

    if (tasksData) tasksData.value = JSON.stringify(tasks);
    if (empsData) empsData.value = JSON.stringify(empIds);
    if (empCount) empCount.value = empIds.length;
    if (projStatusInput) {
      projStatusInput.value = tasks.length === 0 ? "pending" : "in_progress";
    }
  }

  // ─── Detalhes / Edição de Projeto ───────────────────────────────────────
  const items = document.querySelectorAll(".project-item");
  const detailsModal = document.getElementById("projectDetailsModal");
  const closeDetailsBtn = document.getElementById("closeProjectDetailsModal");
  const cancelDetBtn = document.getElementById("cancelDetailsBtn");
  const deleteDetBtn = document.getElementById("deleteDetailsBtn");

  const detForm = document.getElementById("projectDetailsForm");
  const detId = document.getElementById("detailsProjectId");
  const detOldClient = document.getElementById("detailsOldClientId");
  const detClientSelect = document.getElementById("detailsProjectClientId");
  const detStatusInput = document.getElementById("detailsProjectStatus");
  const detStart = document.getElementById("detailsProjectStartDate");
  const detEnd = document.getElementById("detailsProjectEndDate");
  const detName = document.getElementById("detailsProjectName");
  const detDesc = document.getElementById("detailsProjectDescription");
  const detTasksCont = document.getElementById("detailsTasksContainer");
  const detNewTaskInput = document.getElementById("detailsNewTaskInput");
  const detAddTaskBtn = document.getElementById("detailsAddTaskBtn");
  const detEmpsCont = document.getElementById("detailsEmployeesContainer");
  const detEmpSelect = document.getElementById("detailsEmployeeSelect");
  const detAddEmpBtn = document.getElementById("detailsAddEmployeeBtn");
  const detInvCont = document.getElementById("detailsInventoryContainer");
  const detProjTransBody = document.getElementById("detailsProjTransBody");
  const detTasksData = document.getElementById("detailsTasksData");
  const detEmpsData = document.getElementById("detailsEmployeesData");
  const detEmpCount = document.getElementById("detailsEmployeeCountData");
  const detProgBar = document.getElementById("detailsProgressBar");
  const detProgText = document.getElementById("detailsProgressText");

  let detTasks = [],
    detEmps = [],
    detInv = [];

  function openDetails() {
    if (detailsModal) detailsModal.classList.remove("hidden");
  }
  function closeDetails() {
    if (detailsModal) detailsModal.classList.add("hidden");
  }

  if (closeDetailsBtn) {
    closeDetailsBtn.addEventListener("click", closeDetails);
  }
  if (cancelDetBtn) {
    cancelDetBtn.addEventListener("click", closeDetails);
  }

  window.addEventListener("click", (e) => {
    if (e.target === detailsModal) closeDetails();
  });

  if (deleteDetBtn) {
    deleteDetBtn.addEventListener("click", () => {
      if (
        !confirm(
          translations["confirm_delete_project"] || "Remove this project?"
        )
      )
        return;
      if (detId && detId.value) {
        window.location.href = `${baseUrl}/projects/delete?id=${detId.value}`;
      }
    });
  }

  items.forEach((i) =>
    i.addEventListener("click", () => {
      const projectId = i.dataset.projectId;
      if (projectId) loadDetails(projectId);
    })
  );

  function loadDetails(id) {
    fetch(`${baseUrl}/projects/show?id=${id}`, { credentials: "same-origin" })
      .then((r) => r.json())
      .then((data) => {
        if (data.error) {
          alert(data.error);
          return;
        }

        if (detId) detId.value = data.id;
        if (detName) detName.value = data.name || "";
        if (detDesc) detDesc.value = data.description || "";
        if (detStart) detStart.value = data.start_date || "";
        if (detEnd) detEnd.value = data.end_date || "";
        if (detStatusInput) detStatusInput.value = data.status || "pending";

        // CLIENT
        if (detClientSelect) {
          detClientSelect.value = data.client_id || "";
        }
        if (detOldClient) {
          detOldClient.value = data.client_id || "";
        }

        // TASKS
        detTasks = (data.tasks || []).map((t) => ({
          id: t.id,
          description: t.description,
          completed: !!t.completed,
        }));
        renderDetTasks();

        // EMPLOYEES
        detEmps = (data.employees || []).map((e) => ({
          id: e.id,
          text: `${e.name || ""} ${e.last_name || ""}`.trim(),
        }));
        renderDetEmps();

        // INVENTORY
        detInv = data.inventory || [];
        renderDetInv();

        // TRANSACTIONS
        fetch(`${baseUrl}/projects/transactions?id=${data.id}`, {
          credentials: "same-origin",
        })
          .then((r) => r.json())
          .then(renderProjTrans)
          .catch(() =>
            console.warn(
              translations["error_loading_transactions"] ||
                "Error loading transactions"
            )
          );

        // HORAS (admin: total por funcionário)
        fetch(`${baseUrl}/work_logs/project_totals?project_id=${data.id}`, {
          credentials: "same-origin",
        })
          .then((r) => r.json())
          .then(renderProjEmployeeTotals)
          .catch(() =>
            console.error(
              translations["error_loading_work_logs"] ||
                "Error loading work logs"
            )
          );

        activateDetailTab("geral");
        openDetails();
      })
      .catch((err) => {
        console.error("Error loading project details:", err);
        alert(
          translations["error_loading_project_details"] ||
            "Error loading details"
        );
      });
  }

  // ─── Tabs ────────────────────────────────────────────────────────────────
  const tabBtns = document.querySelectorAll("#projectDetailsModal .tab-btn");
  const tabPans = document.querySelectorAll("#projectDetailsModal .tab-panel");

  function activateDetailTab(tab) {
    tabBtns.forEach((b) => {
      const isActive = b.dataset.tab === tab;
      b.classList.toggle("text-blue-600", isActive);
      b.classList.toggle("border-b-2", isActive);
      b.classList.toggle("border-blue-600", isActive);
      b.classList.toggle("text-gray-600", !isActive);
    });
    tabPans.forEach((p) => {
      if (p.id === `tab-${tab}`) {
        p.classList.remove("hidden");
      } else {
        p.classList.add("hidden");
      }
    });
  }

  tabBtns.forEach((b) =>
    b.addEventListener("click", () => {
      const tab = b.dataset.tab;
      if (tab) activateDetailTab(tab);
    })
  );

  // ─── Detalhes: Tasks ───────────────────────────────────────────────────
  if (detAddTaskBtn && detNewTaskInput) {
    detAddTaskBtn.addEventListener("click", () => {
      const d = detNewTaskInput.value.trim();
      if (!d) return;
      detTasks.push({ id: Date.now(), description: d, completed: false });
      detNewTaskInput.value = "";
      renderDetTasks();
    });
  }

  function renderDetTasks() {
    if (!detTasksCont) return;

    detTasksCont.innerHTML = "";
    detTasks.forEach((t, i) => {
      const div = document.createElement("div");
      div.className = "flex items-center mb-2";
      div.innerHTML = `
        <input type="checkbox" data-i="${i}" ${
        t.completed ? "checked" : ""
      } class="mr-2">
        <span class="flex-1">${escapeHtml(t.description)}</span>`;
      detTasksCont.appendChild(div);
    });

    if (detTasksData) {
      detTasksData.value = JSON.stringify(detTasks);
    }

    detTasksCont.querySelectorAll('input[type="checkbox"]').forEach((cb) =>
      cb.addEventListener("change", (e) => {
        const index = parseInt(e.target.dataset.i);
        if (!isNaN(index) && detTasks[index]) {
          detTasks[index].completed = e.target.checked;
          if (detTasksData) {
            detTasksData.value = JSON.stringify(detTasks);
          }
          updateDetProgress();
        }
      })
    );
    updateDetProgress();
  }

  function updateDetProgress() {
    const total = detTasks.length;
    const done = detTasks.filter((t) => t.completed).length;
    const pct = total ? Math.round((done / total) * 100) : 0;

    if (detProgBar) {
      detProgBar.style.width = pct + "%";
    }
    if (detProgText) {
      detProgText.textContent = pct + "%";
    }
    if (detStatusInput) {
      detStatusInput.value =
        total === 0 ? "pending" : done === total ? "completed" : "in_progress";
    }
  }

  // ─── Detalhes: Employees ───────────────────────────────────────────────
  if (detAddEmpBtn && detEmpSelect) {
    detAddEmpBtn.addEventListener("click", () => {
      const id = detEmpSelect.value;
      const name = detEmpSelect.options[detEmpSelect.selectedIndex].text;
      if (!id) return;

      const projectId = detId ? detId.value : "";
      const url = `${baseUrl}/projects/checkEmployee?id=${id}${
        projectId ? "&project_id=" + projectId : ""
      }`;

      fetch(url)
        .then((res) => res.json())
        .then((json) => {
          if (json.count > 0) {
            const msg = (
              translations["employee_already_assigned_message"] ||
              "Employee {name} already assigned to {count} projects"
            )
              .replace("{name}", name)
              .replace("{count}", json.count);
            alert(msg);
          }
          addDetailsEmployee(id, name);
        })
        .catch(() => addDetailsEmployee(id, name));
    });
  }

  function addDetailsEmployee(id, text) {
    if (!detEmpsCont) return;
    if ([...detEmpsCont.children].some((d) => d.dataset.id == id)) return;

    const div = document.createElement("div");
    div.dataset.id = id;
    div.className = "flex items-center mb-2";
    div.innerHTML = `
      <span class="flex-1">${escapeHtml(text)}</span>
      <button class="remove-details-emp text-red-500">×</button>`;
    detEmpsCont.appendChild(div);

    const removeBtn = div.querySelector(".remove-details-emp");
    if (removeBtn) {
      removeBtn.onclick = () => {
        div.remove();
        syncDetailsEmps();
      };
    }
    syncDetailsEmps();
  }

  function renderDetEmps() {
    if (!detEmpsCont) return;
    detEmpsCont.innerHTML = "";
    detEmps.forEach((e) => addDetailsEmployee(e.id, e.text));
  }

  function syncDetailsEmps() {
    if (!detEmpsCont) return;
    const ids = [...detEmpsCont.children].map((d) => d.dataset.id);
    if (detEmpsData) detEmpsData.value = JSON.stringify(ids);
    if (detEmpCount) detEmpCount.value = ids.length;
  }

  // ─── Detalhes: Inventário ───────────────────────────────────────────────
  function renderDetInv() {
    if (!detInvCont) return;

    detInvCont.innerHTML = "";
    if (!detInv.length) {
      detInvCont.textContent =
        translations["no_inventory_allocated"] || "— None allocated";
      return;
    }
    detInv.forEach((i) => {
      const d = document.createElement("div");
      d.textContent = `${i.name} (qty: ${i.quantity})`;
      detInvCont.appendChild(d);
    });
  }

  // ─── Detalhes: Transações ───────────────────────────────────────────────
  function renderProjTrans(trans) {
    if (!detProjTransBody) return;

    detProjTransBody.innerHTML = "";
    if (!trans || !trans.length) {
      detProjTransBody.innerHTML = `
        <tr><td colspan="3" class="p-4 text-center text-gray-500">
          ${translations["no_project_transactions"] || "No transactions"}
        </td></tr>`;
      return;
    }
    trans.forEach((tx) => {
      const tr = document.createElement("tr");
      tr.className = "border-t";
      const date = new Date(tx.date);
      const locale = translations["locale"] || "pt-BR";
      tr.innerHTML = `
        <td class="p-2">${date.toLocaleDateString(locale)}</td>
        <td class="p-2">${
          tx.type.charAt(0).toUpperCase() + tx.type.slice(1)
        }</td>
        <td class="p-2 text-right">${parseFloat(tx.amount).toFixed(2)}</td>`;
      detProjTransBody.appendChild(tr);
    });
  }

  // ─── Detalhes: Horas por Funcionário (Admin) ────────────────────────────
  const empTotalsList = document.getElementById("projectEmployeeTotalsList");

  function renderProjEmployeeTotals(data) {
    if (!empTotalsList) return;

    empTotalsList.innerHTML = "";
    if (!data || !data.length) {
      const li = document.createElement("li");
      li.textContent =
        translations["no_employees"] || "No employees logged hours";
      empTotalsList.appendChild(li);
      return;
    }
    data.forEach((item) => {
      const li = document.createElement("li");
      li.textContent = `${item.employee_name}: ${parseFloat(
        item.total_hours || 0
      ).toFixed(2)}h`;
      empTotalsList.appendChild(li);
    });
  }

  // ─── Dashboard do Funcionário ───────────────────────────────────────────
  const empDash = document.getElementById("employeeDashboard");
  if (empDash) {
    const projectSelect = document.getElementById("workLogProjectSelect");
    const hoursInput = document.getElementById("workLogHoursInput");
    const submitBtn = document.getElementById("submitWorkLogBtn");
    const logsList = document.getElementById("workLogList");
    const totalHoursEl = document.getElementById("employeeTotalHoursValue");

    function renderLogs(logs) {
      if (!logsList) return;

      logsList.innerHTML = "";
      let total = 0;

      if (!logs || !logs.length) {
        const li = document.createElement("li");
        li.textContent = translations["no_work_logs"] || "No work logs";
        logsList.appendChild(li);
      } else {
        logs.forEach((log) => {
          const li = document.createElement("li");
          const dt = new Date(log.date);
          const locale = translations["locale"] || "pt-BR";
          li.textContent = `${dt.toLocaleDateString(locale)}: ${parseFloat(
            log.hours || 0
          ).toFixed(2)}h`;
          logsList.appendChild(li);
          total += parseFloat(log.hours) || 0;
        });
      }

      if (totalHoursEl) {
        totalHoursEl.value = total.toFixed(2);
      }
    }

    function loadLogsForProject(projId) {
      if (!projId) return;

      fetch(`${baseUrl}/work_logs/index?project_id=${projId}`, {
        credentials: "same-origin",
      })
        .then((r) => r.json())
        .then(renderLogs)
        .catch((err) => {
          console.error("Error loading work logs:", err);
          console.error(
            translations["error_loading_work_logs"] || "Error loading work logs"
          );
        });
    }

    if (projectSelect) {
      projectSelect.addEventListener("change", () => {
        const projId = projectSelect.value;
        if (projId) loadLogsForProject(projId);
      });
    }

    if (submitBtn && projectSelect && hoursInput) {
      submitBtn.addEventListener("click", () => {
        const projId = projectSelect.value;
        const hours = parseFloat(hoursInput.value) || 0;

        if (!projId || hours <= 0) {
          return alert(
            translations["error_saving_work_log"] ||
              "Please select a project and enter valid hours"
          );
        }

        const formData = new FormData();
        formData.append("project_id", projId);
        formData.append("hours", hours);
        formData.append("date", new Date().toISOString().slice(0, 10));

        fetch(`${baseUrl}/work_logs/store`, {
          method: "POST",
          credentials: "same-origin",
          body: formData,
        })
          .then((r) => r.json())
          .then((json) => {
            if (json.success) {
              hoursInput.value = "";
              loadLogsForProject(projId);
            } else {
              alert(
                json.error ||
                  translations["error_saving_work_log"] ||
                  "Error saving work log"
              );
            }
          })
          .catch((err) => {
            console.error("Error saving work log:", err);
            alert(
              translations["error_saving_work_log"] || "Error saving work log"
            );
          });
      });
    }

    // Load initial logs if project is already selected
    if (projectSelect && projectSelect.value) {
      loadLogsForProject(projectSelect.value);
    }
  }

  // Helper function to escape HTML
  function escapeHtml(text) {
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
  }
});
