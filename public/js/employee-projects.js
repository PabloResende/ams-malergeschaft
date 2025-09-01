// public/js/employee-projects.js
document.addEventListener('DOMContentLoaded', () => {
  console.log('employee-projects.js carregado');

  const baseUrl      = window.baseUrl || '';
  const translations = window.langText || {};
  let currentProjectId = null;

  const modal    = document.getElementById('projectDetailsModal');
  const btnClose = document.getElementById('closeProjectDetailsModal');
  const form     = document.getElementById('workLogForm');

  // Mostra/oculta modal
  function showModal() {
    modal.classList.remove('hidden');
    switchTab('geral');
  }
  function hideModal() {
    modal.classList.add('hidden');
  }

  // --------------------------
  // 1) Abrir modal ao clicar no card
  // --------------------------
  document.querySelectorAll('.project-item').forEach(item => {
    item.addEventListener('click', () => {
      const id = item.dataset.projectId;
      console.log('Projeto clicado:', id);
      if (!id) return;
      currentProjectId = id;
      showModal();
      loadProjectDetails(id);
    });
  });

  // --------------------------
  // 2) Fechar modal
  // --------------------------
  btnClose.addEventListener('click', hideModal);
  modal.addEventListener('click', e => {
    if (e.target === modal) hideModal();
  });

  // --------------------------
  // 3) Alternar abas
  // --------------------------
  document.querySelectorAll('#projectDetailsModal .tab-btn').forEach(btn => {
    btn.addEventListener('click', () => switchTab(btn.dataset.tab));
  });

  function switchTab(tab) {
    document.querySelectorAll('#projectDetailsModal .tab-panel').forEach(panel => {
      panel.id === `tab-${tab}` 
        ? panel.classList.remove('hidden') 
        : panel.classList.add('hidden');
    });
    document.querySelectorAll('#projectDetailsModal .tab-btn').forEach(btn => {
      if (btn.dataset.tab === tab) {
        btn.classList.add('border-b-2', 'border-blue-600');
      } else {
        btn.classList.remove('border-b-2', 'border-blue-600');
      }
    });
  }

  // --------------------------
  // 4) Carregar detalhes do projeto (via AJAX)
  // --------------------------
  function loadProjectDetails(id) {
    fetch(`${baseUrl}/projects/show?id=${id}`, { credentials: 'same-origin' })
      .then(r => r.json())
      .then(data => {
        if (data.error) {
          alert(data.error);
          return hideModal();
        }

        // Geral
        document.getElementById('roName').textContent     = data.name || '';
        document.getElementById('roClient').textContent   = data.client_name || '';
        document.getElementById('roLocation').textContent = data.location || '';
        document.getElementById('roStart').textContent    = data.start_date || '';
        document.getElementById('roEnd').textContent      = data.end_date || '';

        // Tarefas
        const tasksEl = document.getElementById('roTasks');
        tasksEl.innerHTML = '';
        if (data.tasks?.length) {
          data.tasks.forEach(t => {
            const li = document.createElement('li');
            if (t.completed) {
              // marca concluída
              li.innerHTML = `<span class="text-green-600">✔️</span> ${t.description}`;
            } else {
              li.textContent = t.description;
            }
            tasksEl.appendChild(li);
          });
        } else {
          tasksEl.innerHTML = `<li class="text-gray-500">${
            translations['no_tasks'] || 'Nenhuma tarefa'
          }</li>`;
        }

        // Funcionários
        const empEl = document.getElementById('roEmployees');
        empEl.innerHTML = '';
        if (data.employees?.length) {
          data.employees.forEach(e => {
            const li = document.createElement('li');
            // agora mostra nome + sobrenome
            li.textContent = `${e.name} ${e.last_name || ''}`.trim();
            empEl.appendChild(li);
          });
        } else {
          empEl.innerHTML = `<li class="text-gray-500">${
            translations['no_employees'] || 'Nenhum funcionário'
          }</li>`;
        }

        // Inventário
        const invEl = document.getElementById('roInventory');
        invEl.innerHTML = '';
        if (data.inventory?.length) {
          data.inventory.forEach(i => {
            const li = document.createElement('li');
            li.textContent = `${i.name}: ${i.quantity}`;
            invEl.appendChild(li);
          });
        } else {
          invEl.innerHTML = `<li class="text-gray-500">${translations['no_inventory']||'Nenhum item'}</li>`;
        }

        // Horas
        const logs  = data.work_logs || [];
        const total = logs.reduce((sum, l) => sum + parseFloat(l.hours), 0);
        document.getElementById('workLogTotal').textContent = total.toFixed(2);

        const listEl = document.getElementById('workLogList');
        listEl.innerHTML = '';
        if (logs.length) {
          logs.forEach(l => {
            const li = document.createElement('li');
            li.textContent = `${new Date(l.date).toLocaleDateString('pt-BR')} – ${l.hours}h`;
            listEl.appendChild(li);
          });
        } else {
          listEl.innerHTML = `<li class="text-gray-500">${translations['no_logs']||'Nenhum registro'}</li>`;
        }

        // Ajusta formulário
        document.getElementById('workLogProjectId').value = id;
        form.reset();
      })
      .catch(err => console.error('Erro ao carregar projeto:', err));
  }

  // --------------------------
  // 5) Enviar novo log de horas
  // --------------------------
  form.addEventListener('submit', e => {
    e.preventDefault();
    const fdata = new FormData(form);
    fetch(`${baseUrl}/work_logs/store`, {
      method: 'POST',
      credentials: 'same-origin',
      body: fdata
    })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        loadProjectDetails(currentProjectId);
      } else {
        alert(res.error || translations['error_saving_work_log']);
      }
    })
    .catch(err => console.error('Erro ao salvar log:', err));
  });
});
