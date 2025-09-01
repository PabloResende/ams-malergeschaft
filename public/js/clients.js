// public/js/clients.js

document.addEventListener('DOMContentLoaded', () => {
  const baseUrl        = window.baseUrl  || '';
  const langText       = window.langText || {};
  const container      = document.getElementById('clientsContainer');
  const clientModal    = document.getElementById('clientModal');
  const openCreateBtn  = document.getElementById('addClientBtn');
  const closeBtn       = document.getElementById('closeClientModal');
  const cancelBtn      = document.getElementById('cancelClient');
  const form           = document.getElementById('clientForm');
  const titleEl        = document.getElementById('modalTitle');
  const deleteBtn      = document.getElementById('deleteClientBtn');
  const toastContainer = document.getElementById('toastContainer');

  const tabs = {
    stammdaten:     document.getElementById('tabStammdaten'),
    zusatzinfo:     document.getElementById('tabZusatzinfo'),
    kommunikation:  document.getElementById('tabKommunikation'),
    weitereKontakt: document.getElementById('tabWeitereKontakt'),
  };
  const panes = {
    stammdaten:     document.getElementById('paneStammdaten'),
    zusatzinfo:     document.getElementById('paneZusatzinfo'),
    kommunikation:  document.getElementById('paneKommunikation'),
    weitereKontakt: document.getElementById('paneWeitereKontakt'),
  };

  function notify(message, type = 'info') {
    const bg = type === 'success'
      ? 'bg-green-500'
      : type === 'error'
        ? 'bg-red-500'
        : 'bg-blue-500';
    const toast = document.createElement('div');
    toast.className = `${bg} text-white px-4 py-2 rounded shadow transition-opacity`;
    toast.textContent = message;
    toastContainer.appendChild(toast);
    setTimeout(() => {
      toast.classList.add('opacity-0');
      setTimeout(() => toast.remove(), 500);
    }, 3000);
  }

  function activateTab(key) {
    for (let k in tabs) {
      tabs[k].classList.toggle('border-blue-600', k === key);
      panes[k].classList.toggle('hidden', k !== key);
    }
  }

  function showModal() {
    clientModal.classList.remove('hidden');
    activateTab('stammdaten');
  }

  function hideModal() {
    clientModal.classList.add('hidden');
    form.reset();
    form.action         = `${baseUrl}/clients/save`;
    titleEl.textContent = langText['create_client'] || 'Criar Cliente';
    deleteBtn.classList.add('hidden');
  }

  // Create mode: reset form and id
  openCreateBtn.addEventListener('click', () => {
    form.reset();
    document.getElementById('clientId').value = '';
    form.action         = `${baseUrl}/clients/save`;
    titleEl.textContent = langText['create_client'] || 'Criar Cliente';
    deleteBtn.classList.add('hidden');
    showModal();
  });

  closeBtn.addEventListener('click', hideModal);
  cancelBtn.addEventListener('click', hideModal);
  clientModal.addEventListener('click', e => {
    if (e.target === clientModal) hideModal();
  });

  tabs.stammdaten.addEventListener('click', () => activateTab('stammdaten'));
  tabs.zusatzinfo.addEventListener('click', () => activateTab('zusatzinfo'));
  tabs.kommunikation.addEventListener('click', () => activateTab('kommunikation'));
  tabs.weitereKontakt.addEventListener('click', () => activateTab('weitereKontakt'));

  const fields = [
    'id','contact_number','name','complement','address','zip_code',
    'city','country','category','contact_person','owner','correspondence',
    'language','about','branch','email','email2','phone','phone2',
    'mobile','fax','website','skype','employee_count',
    'registry_number','vat_number','tax_id_number'
  ];

  function fillForm(data) {
    fields.forEach(f => {
      let elId;
      switch (f) {
        case 'id':              elId = 'clientId';      break;
        case 'name':            elId = 'clientName';    break;
        case 'contact_number':  elId = 'contactNumber'; break;
        case 'zip_code':        elId = 'zipCode';       break;
        case 'contact_person':  elId = 'contactPerson'; break;
        case 'employee_count':  elId = 'employeeCount'; break;
        case 'registry_number': elId = 'registryNumber';break;
        case 'vat_number':      elId = 'vatNumber';     break;
        case 'tax_id_number':   elId = 'taxIdNumber';   break;
        default:
          elId = f.replace(/_([a-z])/g, (_, c) => c.toUpperCase());
      }
      const el = document.getElementById(elId);
      if (!el) return;
      if (el.type === 'checkbox') {
        el.checked = data[f] === 1;
      } else {
        el.value = data[f] ?? '';
      }
    });
  }

  // Open details on card click
  container.addEventListener('click', e => {
    const item = e.target.closest('.client-item');
    if (!item) return;
    fetch(`${baseUrl}/clients/show?id=${item.dataset.id}`, {
      credentials: 'same-origin'
    })
      .then(r => r.json())
      .then(data => {
        if (data.error) return notify(data.error, 'error');
        fillForm(data);
        titleEl.textContent = langText['client_details'] || 'Detalhes do Cliente';
        form.action = `${baseUrl}/clients/save`;
        deleteBtn.dataset.id  = data.id;
        deleteBtn.dataset.url = deleteBtn.getAttribute('data-url');
        deleteBtn.classList.remove('hidden');
        showModal();
      })
      .catch(() => notify(langText['load_error'] || 'Erro ao carregar detalhes.', 'error'));
  });

  // Hard delete via AJAX
  deleteBtn.addEventListener('click', () => {
    if (!confirm(langText['confirm_delete'] || 'Confirma exclusão?')) return;
    const id  = deleteBtn.dataset.id;
    const url = `${baseUrl}/clients/delete?id=${id}`;
    fetch(url, {
      method: 'GET',
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(response => {
        if (!response.ok) throw new Error('Erro ao excluir');
        hideModal();
        const card = container.querySelector(`.client-item[data-id="${id}"]`);
        if (card) card.remove();
        notify(langText['client_deleted'] || 'Você excluiu um cliente', 'success');
      })
      .catch(() => notify(langText['delete_error'] || 'Erro ao excluir.', 'error'));
  });

  // Create/Update via AJAX
  form.addEventListener('submit', e => {
    e.preventDefault();
    const formData = new FormData(form);
    const isNew    = !formData.get('id');
    fetch(form.action, {
      method: 'POST',
      body:   formData,
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(r => r.json().catch(() => ({ success: true, id: formData.get('id') })))
      .then(data => {
        hideModal();
        if (isNew) {
          notify(langText['client_created'] || 'Cliente criado com sucesso', 'success');
          setTimeout(() => window.location.reload(), 1000);
        } else {
          notify(langText['client_updated'] || 'Você atualizou as informações de um cliente', 'success');
          fetch(`${baseUrl}/clients/show?id=${data.id}`, {
            credentials: 'same-origin'
          })
            .then(r => r.json())
            .then(d => {
              const card = container.querySelector(`.client-item[data-id="${d.id}"]`);
              if (card) {
                card.querySelector('h2').textContent = d.name;
                card.querySelector('.text-green-600').textContent =
                  `${langText['loyalty_points']||'Pontos'}: ${d.loyalty_points}`;
                card.querySelector('p span').textContent = d.address || '';
              }
            });
        }
      })
      .catch(() => notify(langText['save_error'] || 'Erro ao salvar.', 'error'));
  });
});
