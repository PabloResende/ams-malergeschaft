// public/js/employees.js

const baseUrl = window.baseUrl;

document.addEventListener('DOMContentLoaded', () => {
  // ─── Create Modal ─────────────────────────────────────────
  const addBtn     = document.getElementById('addEmployeeBtn');
  const createMod  = document.getElementById('employeeModal');
  const closeCreate= createMod.querySelectorAll('#closeEmployeeModal');
  addBtn.addEventListener('click', () => createMod.classList.remove('hidden'));
  closeCreate.forEach(b => b.addEventListener('click', () => createMod.classList.add('hidden')));
  window.addEventListener('click', e => { if (e.target === createMod) createMod.classList.add('hidden'); });

  // Create Tabs
  const cTabs = Array.from(createMod.querySelectorAll('.tab-btn'));
  const cPans = Array.from(createMod.querySelectorAll('.tab-panel'));
  function activateCreate(tab) {
    cTabs.forEach(b => {
      const act = b.dataset.tab === tab;
      b.classList.toggle('border-blue-500', act);
      b.classList.toggle('text-blue-600', act);
      b.classList.toggle('border-transparent', !act);
      b.classList.toggle('text-gray-600', !act);
    });
    cPans.forEach(p => p.id.endsWith(tab) ? p.classList.remove('hidden') : p.classList.add('hidden'));
  }
  cTabs.forEach(b => b.addEventListener('click', () => activateCreate(b.dataset.tab)));
  activateCreate('general-create');

  // ─── Details Modal ────────────────────────────────────────
  const detMod = document.getElementById('employeeDetailsModal');
  const closeDet= detMod.querySelectorAll('.closeEmployeeDetailsModal');
  closeDet.forEach(b => b.addEventListener('click', () => detMod.classList.add('hidden')));
  window.addEventListener('click', e => { if (e.target === detMod) detMod.classList.add('hidden'); });

  // Detail Tabs
  const dTabs = Array.from(detMod.querySelectorAll('.tab-btn'));
  const dPans = Array.from(detMod.querySelectorAll('.tab-panel'));
  function activateDetail(tab) {
    dTabs.forEach(b => {
      const act = b.dataset.tab === tab;
      b.classList.toggle('border-blue-500', act);
      b.classList.toggle('text-blue-600', act);
      b.classList.toggle('border-transparent', !act);
      b.classList.toggle('text-gray-600', !act);
    });
    dPans.forEach(p => p.id.endsWith(tab) ? p.classList.remove('hidden') : p.classList.add('hidden'));
  }
  dTabs.forEach(b => b.addEventListener('click', () => activateDetail(b.dataset.tab)));

  // Format currency
  function fmt(v) { return 'R$ ' + parseFloat(v).toFixed(2).replace('.', ','); }

  // Fill employee transactions
  function fillEmpTrans(trans, empId) {
    const body = document.getElementById('empTransBody');
    const only = trans.filter(tx => String(tx.employee_id) === String(empId));
    body.innerHTML = '';
    if (!only.length) {
      body.innerHTML = '<tr><td colspan="3" class="p-4 text-center text-gray-500">Sem transações</td></tr>';
      return;
    }
    only.forEach(tx => {
      const tr = document.createElement('tr');
      tr.className = 'border-t';
      tr.innerHTML = `
        <td class="p-2">${new Date(tx.date).toLocaleDateString()}</td>
        <td class="p-2">${tx.type.charAt(0).toUpperCase() + tx.type.slice(1)}</td>
        <td class="p-2 text-right">${fmt(tx.amount)}</td>
      `;
      body.appendChild(tr);
    });
  }

  // Open details
  document.querySelectorAll('.employee-card').forEach(card => {
    card.addEventListener('click', () => {
      const id = card.dataset.id;
      fetch(`${baseUrl}/employees/get?id=${encodeURIComponent(id)}`, { credentials:'same-origin' })
        .then(r => r.json())
        .then(data => {
          if (data.error) return alert(data.error);

          // fill general fields
          const fields = {
            detailsEmployeeId: data.id,
            detailsEmployeeName: data.name,
            detailsEmployeeLastName: data.last_name,
            detailsEmployeeAddress: data.address,
            detailsEmployeeSex: data.sex,
            detailsEmployeeBirthDate: data.birth_date,
            detailsEmployeeNationality: data.nationality,
            detailsEmployeePermissionType: data.permission_type,
            detailsEmployeeEmail: data.email,
            detailsEmployeeAhvNumber: data.ahv_number,
            detailsEmployeePhone: data.phone,
            detailsEmployeeReligion: data.religion,
            detailsEmployeeMaritalStatus: data.marital_status,
            detailsEmployeeRole: data.role,
            detailsEmployeeStartDate: data.start_date,
            detailsEmployeeAbout: data.about
          };
          Object.entries(fields).forEach(([id, val]) => {
            const el = document.getElementById(id);
            if (el) el.value = val || '';
          });

          // preview docs
          ['profile_picture','passport','permission_photo_front','permission_photo_back',
           'health_card_front','health_card_back','bank_card_front','bank_card_back','marriage_certificate']
           .forEach(field => {
             const img = document.getElementById('view'+field.charAt(0).toUpperCase()+field.slice(1));
             if (!img) return;
             if (data[field]) {
               img.src = `${baseUrl}/employees/serveDocument?id=${data.id}&type=${field}`;
               img.style.display = 'block';
             } else {
               img.style.display = 'none';
             }
           });

          // fill transactions
          fillEmpTrans(data.transactions, data.id);

          activateDetail('general-details');
          detMod.classList.remove('hidden');
        })
        .catch(() => alert('Não foi possível carregar os dados do funcionário.'));
    });
  });

  // Delete employee
  document.getElementById('deleteEmployeeBtn').addEventListener('click', () => {
    if (!confirm('Deseja realmente excluir este funcionário?')) return;
    const id = document.getElementById('detailsEmployeeId').value;
    window.location.href = `${baseUrl}/employees/delete?id=${encodeURIComponent(id)}`;
  });
});
