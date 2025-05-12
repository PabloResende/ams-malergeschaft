// public/js/employees.js
const baseUrl = window.baseUrl;

document.addEventListener("DOMContentLoaded", () => {
  //
  // ─── Modal de Criação ──────────────────────────────────────────────
  //
  const addEmployeeBtn     = document.getElementById("addEmployeeBtn");
  const employeeModal      = document.getElementById("employeeModal");
  const closeEmployeeModal = document.getElementById("closeEmployeeModal");

  addEmployeeBtn.addEventListener("click", () =>
    employeeModal.classList.remove("hidden")
  );
  closeEmployeeModal.addEventListener("click", () =>
    employeeModal.classList.add("hidden")
  );
  window.addEventListener("click", e => {
    if (e.target === employeeModal) {
      employeeModal.classList.add("hidden");
    }
  });

  // Tabs Criação
  const createTabButtons = Array.from(document.querySelectorAll("#employeeModal .tab-btn"));
  const createTabPanels  = Array.from(document.querySelectorAll("#employeeModal .tab-panel"));
  function activateCreateTab(tab) {
    createTabButtons.forEach(b => {
      if (b.dataset.tab === tab) {
        b.classList.replace("border-transparent","border-blue-500");
        b.classList.replace("text-gray-600","text-blue-600");
      } else {
        b.classList.replace("border-blue-500","border-transparent");
        b.classList.replace("text-blue-600","text-gray-600");
      }
    });
    createTabPanels.forEach(p => {
      p.id === `panel-${tab}` ? p.classList.remove("hidden") : p.classList.add("hidden");
    });
  }
  createTabButtons.forEach(b =>
    b.addEventListener("click", () => activateCreateTab(b.dataset.tab))
  );
  activateCreateTab("general-create");

  //
  // ─── Modal de Detalhes / Edição ─────────────────────────────────────
  //
  const detailsModal     = document.getElementById("employeeDetailsModal");
  const closeDetailsBtns = Array.from(document.querySelectorAll(".closeEmployeeDetailsModal"));
  const deleteBtn        = document.getElementById("deleteEmployeeBtn");

  closeDetailsBtns.forEach(btn =>
    btn.addEventListener("click", () => detailsModal.classList.add("hidden"))
  );
  window.addEventListener("click", e => {
    if (e.target === detailsModal) detailsModal.classList.add("hidden");
  });

  // Tabs Detalhes
  const detailTabButtons = Array.from(document.querySelectorAll("#employeeDetailsModal .tab-btn"));
  const detailTabPanels  = Array.from(document.querySelectorAll("#employeeDetailsModal .tab-panel"));
  function activateDetailTab(tab) {
    detailTabButtons.forEach(b => {
      if (b.dataset.tab === tab) {
        b.classList.replace("border-transparent","border-blue-500");
        b.classList.replace("text-gray-600","text-blue-600");
      } else {
        b.classList.replace("border-blue-500","border-transparent");
        b.classList.replace("text-blue-600","text-gray-600");
      }
    });
    detailTabPanels.forEach(p => {
      p.id === `panel-${tab}` ? p.classList.remove("hidden") : p.classList.add("hidden");
    });
  }
  detailTabButtons.forEach(b =>
    b.addEventListener("click", () => activateDetailTab(b.dataset.tab))
  );

  // Abre Detalhes
  document.querySelectorAll(".employee-card").forEach(card => {
    card.addEventListener("click", () => openDetails(card.dataset.id));
  });

  function openDetails(id) {
    fetch(`${baseUrl}/employees/get?id=${id}`)
      .then(res => res.ok ? res.json() : Promise.reject(res.status))
      .then(data => {
        // campos gerais
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
          if (el) el.value = val || "";
        });

        // documentos
        const images = {
          profile_picture: "viewProfilePicture",
          passport: "viewPassport",
          permission_photo_front: "viewPermissionPhotoFront",
          permission_photo_back: "viewPermissionPhotoBack",
          health_card_front: "viewHealthCardFront",
          health_card_back: "viewHealthCardBack",
          bank_card_front: "viewBankCardFront",
          bank_card_back: "viewBankCardBack",
          marriage_certificate: "viewMarriageCertificate"
        };
        Object.entries(images).forEach(([field, imgId]) => {
          const img = document.getElementById(imgId);
          if (!img) return;
          if (data[field]) {
            img.src = `${baseUrl}/employees/serveDocument?id=${data.id}&type=${field}`;
            img.style.display = "block";
          } else {
            img.style.display = "none";
          }
        });
        // exibe/esconde certidão
        const mc = document.getElementById("marriageCertificateContainer");
        if (mc) mc.style.display = data.marriage_certificate ? "block" : "none";

        activateDetailTab("general-details");
        detailsModal.classList.remove("hidden");
      })
      .catch(err => {
        console.error(err);
        alert("Não foi possível carregar os dados do funcionário.");
      });
  }

  // Excluir
  deleteBtn.addEventListener("click", () => {
    if (!confirm("Deseja realmente excluir este funcionário?")) return;
    const id = document.getElementById("detailsEmployeeId").value;
    window.location.href = `${baseUrl}/employees/delete?id=${id}`;
  });
});
