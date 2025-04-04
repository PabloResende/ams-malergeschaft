// Controle do Modal de Criação
const addEmployeeBtn = document.getElementById("addEmployeeBtn");
const employeeModal = document.getElementById("employeeModal");
const closeEmployeeModal = document.getElementById("closeEmployeeModal");

addEmployeeBtn.addEventListener("click", () =>
  employeeModal.classList.remove("hidden")
);
closeEmployeeModal.addEventListener("click", () =>
  employeeModal.classList.add("hidden")
);
window.addEventListener(
  "click",
  (e) => e.target === employeeModal && employeeModal.classList.add("hidden")
);

// Controle do Modal de Edição
const editButtons = document.querySelectorAll(".editEmployeeBtn");
const employeeEditModal = document.getElementById("employeeEditModal");
const closeEmployeeEditModal = document.getElementById(
  "closeEmployeeEditModal"
);

editButtons.forEach((button) => {
  button.addEventListener("click", function () {
    document.getElementById("editEmployeeId").value =
      this.getAttribute("data-id");
    document.getElementById("editEmployeeName").value =
      this.getAttribute("data-name");
    document.getElementById("editEmployeeLastName").value =
      this.getAttribute("data-last_name");
    document.getElementById("editEmployeeRole").value =
      this.getAttribute("data-role");
    document.getElementById("editEmployeeBirthDate").value =
      this.getAttribute("data-birth_date");
    document.getElementById("editEmployeeStartDate").value =
      this.getAttribute("data-start_date");
    document.getElementById("editEmployeeAddress").value =
      this.getAttribute("data-address");
    document.getElementById("editEmployeeAbout").value =
      this.getAttribute("data-about");
    document.getElementById("editEmployeePhone").value =
      this.getAttribute("data-phone");
    document.getElementById("editEmployeeSex").value =
      this.getAttribute("data-sex");
    document.getElementById("editEmployeeNationality").value =
      this.getAttribute("data-nationality");
    document.getElementById("editEmployeePermissionType").value =
      this.getAttribute("data-permission_type");
    document.getElementById("editEmployeeEmail").value =
      this.getAttribute("data-email");
    document.getElementById("editEmployeeAhvNumber").value =
      this.getAttribute("data-ahv_number");
    document.getElementById("editEmployeeReligion").value =
      this.getAttribute("data-religion");
    document.getElementById("editEmployeeMaritalStatus").value =
      this.getAttribute("data-marital_status");

    employeeEditModal.classList.remove("hidden");
  });
});

closeEmployeeEditModal.addEventListener("click", () =>
  employeeEditModal.classList.add("hidden")
);
window.addEventListener(
  "click",
  (e) =>
    e.target === employeeEditModal && employeeEditModal.classList.add("hidden")
);

// Controle do Modal de Visualização
const viewButtons = document.querySelectorAll(".viewEmployeeBtn");
const employeeViewModal = document.getElementById("employeeViewModal");
const closeEmployeeViewModal = document.getElementById(
  "closeEmployeeViewModal"
);
viewButtons.forEach((button) => {
  button.addEventListener("click", async function () {
    const employeeId = this.getAttribute("data-id");

    try {
      const response = await fetch(
        `<?= $baseUrl ?>/employees/get?id=${employeeId}`
      );

      if (!response.ok) {
        const error = await response.json();
        throw new Error(error.error || "Failed to load employee data");
      }

      const employee = await response.json();

      // Preenche os campos de texto
      const textFields = {
        name: "Name",
        last_name: "LastName",
        address: "Address",
        sex: "Sex",
        birth_date: "BirthDate",
        nationality: "Nationality",
        permission_type: "PermissionType",
        email: "Email",
        ahv_number: "AhvNumber",
        phone: "Phone",
        religion: "Religion",
        marital_status: "MaritalStatus",
        role: "Role",
        start_date: "StartDate",
        about: "About",
      };

      Object.entries(textFields).forEach(([key, field]) => {
        const element = document.getElementById(`view${field}`);
        if (element) element.textContent = employee[key] || "N/A";
      });

      // Preenche as imagens
      const imageFields = {
        profile_picture: "ProfilePicture",
        passport: "Passport",
        permission_photo_front: "PermissionPhotoFront",
        permission_photo_back: "PermissionPhotoBack",
        health_card_front: "HealthCardFront",
        health_card_back: "HealthCardBack",
        bank_card_front: "BankCardFront",
        bank_card_back: "BankCardBack",
        marriage_certificate: "MarriageCertificate",
      };

      Object.entries(imageFields).forEach(([key, field]) => {
        const element = document.getElementById(`view${field}`);
        if (element) {
          if (employee[key]) {
            element.src = employee[key];
            element.onerror = function () {
              this.src =
                "https://via.placeholder.com/150?text=Image+Not+Loaded";
            };
            element.style.display = "block";

            // Adiciona link para abrir em nova aba
            const parent = element.parentElement;
            parent.innerHTML = `
                            <a href="${employee[key]}" target="_blank" class="block">
                                <img src="${employee[key]}" 
                                     alt="${field}" 
                                     class="w-full h-auto border rounded mt-1" 
                                     style="max-height: 150px;"
                                     onerror="this.src='https://via.placeholder.com/150?text=Image+Not+Loaded'">
                                <small class="text-blue-500 hover:underline">Ver em tamanho real</small>
                            </a>
                        `;
          } else {
            element.src = `https://via.placeholder.com/150?text=No+${key.replace(
              /_/g,
              "+"
            )}`;
            element.style.display = "block";
          }
        }
      });

      employeeViewModal.classList.remove("hidden");
    } catch (error) {
      console.error("Error:", error);
      alert(error.message || "Error loading employee details");
    }
  });
});

closeEmployeeViewModal.addEventListener("click", () =>
  employeeViewModal.classList.add("hidden")
);
window.addEventListener(
  "click",
  (e) =>
    e.target === employeeViewModal && employeeViewModal.classList.add("hidden")
);
