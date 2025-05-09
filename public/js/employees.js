// public/js/employees.js
// usa exatamente o baseUrl definido no PHP
const baseUrl = window.baseUrl;

document.addEventListener("DOMContentLoaded", () => {
  //
  // — MODAL DE CRIAÇÃO —
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

  //
  // — MODAL DE DETALHES / EDIÇÃO —
  //
  const detailsModal     = document.getElementById("employeeDetailsModal");
  const closeDetailsBtns = document.querySelectorAll(".closeEmployeeDetailsModal");
  const deleteBtn        = document.getElementById("deleteEmployeeBtn");

  const detailFields = {
    id:               "detailsEmployeeId",
    name:             "detailsEmployeeName",
    last_name:        "detailsEmployeeLastName",
    address:          "detailsEmployeeAddress",
    sex:              "detailsEmployeeSex",
    birth_date:       "detailsEmployeeBirthDate",
    nationality:      "detailsEmployeeNationality",
    permission_type:  "detailsEmployeePermissionType",
    email:            "detailsEmployeeEmail",
    ahv_number:       "detailsEmployeeAhvNumber",
    phone:            "detailsEmployeePhone",
    religion:         "detailsEmployeeReligion",
    marital_status:   "detailsEmployeeMaritalStatus",
    role:             "detailsEmployeeRole",
    start_date:       "detailsEmployeeStartDate",
    about:            "detailsEmployeeAbout"
  };

  const imageFields = {
    profile_picture:        "viewProfilePicture",
    passport:               "viewPassport",
    permission_photo_front: "viewPermissionPhotoFront",
    permission_photo_back:  "viewPermissionPhotoBack",
    health_card_front:      "viewHealthCardFront",
    health_card_back:       "viewHealthCardBack",
    bank_card_front:        "viewBankCardFront",
    bank_card_back:         "viewBankCardBack",
    marriage_certificate:   "viewMarriageCertificate"
  };

  function closeDetails() {
    detailsModal.classList.add("hidden");
  }
  closeDetailsBtns.forEach(btn =>
    btn.addEventListener("click", closeDetails)
  );
  window.addEventListener("click", e => {
    if (e.target === detailsModal) closeDetails();
  });

  function openDetails(id) {
    fetch(`${baseUrl}/employees/get?id=${id}`)
      .then(res => {
        if (!res.ok) throw new Error("Erro ao carregar dados");
        return res.json();
      })
      .then(data => {
        // popula inputs
        Object.entries(detailFields).forEach(([key, elId]) => {
          const el = document.getElementById(elId);
          if (el) el.value = data[key] || "";
        });

        // popula imagens
        Object.entries(imageFields).forEach(([key, imgId]) => {
          const img = document.getElementById(imgId);
          if (!img) return;
          if (data[key]) {
            img.src = `${baseUrl}/employees/serveDocument?id=${data.id}&type=${key}`;
            img.style.display = "block";
          } else {
            img.style.display = "none";
          }
        });

        // mostra/esconde certidão de casamento
        const mc = document.getElementById("marriageCertificateContainer");
        if (mc) mc.style.display = data.marriage_certificate ? "block" : "none";

        detailsModal.classList.remove("hidden");
      })
      .catch(err => {
        console.error(err);
        alert("Não foi possível carregar os dados do funcionário.");
      });
  }

  document.querySelectorAll(".employee-card").forEach(card => {
    card.addEventListener("click", () => {
      const id = card.dataset.id;
      if (id) openDetails(id);
    });
  });

  deleteBtn.addEventListener("click", () => {
    if (!confirm("Deseja realmente excluir este funcionário?")) return;
    const id = document.getElementById("detailsEmployeeId").value;
    window.location.href = `${baseUrl}/employees/delete?id=${id}`;
  });
});
