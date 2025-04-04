const addClientBtn = document.getElementById("addClientBtn");
const clientModal = document.getElementById("clientModal");
const closeClientModal = document.getElementById("closeClientModal");

addClientBtn.addEventListener("click", function () {
  clientModal.classList.remove("hidden");
});

closeClientModal.addEventListener("click", function () {
  clientModal.classList.add("hidden");
});

window.addEventListener("click", function (event) {
  if (event.target === clientModal) {
    clientModal.classList.add("hidden");
  }
});

const editButtons = document.querySelectorAll(".editClientBtn");
const clientEditModal = document.getElementById("clientEditModal");
const closeClientEditModal = document.getElementById("closeClientEditModal");

// Função para preencher os dados do modal
editButtons.forEach((button) => {
  button.addEventListener("click", function () {
    document.getElementById("editClientId").value =
      this.getAttribute("data-id");
    document.getElementById("editClientName").value =
      this.getAttribute("data-name");
    document.getElementById("editClientRole").value =
      this.getAttribute("data-role");
    document.getElementById("editClientBirthDate").value =
      this.getAttribute("data-birth_date");
    document.getElementById("editClientStartDate").value =
      this.getAttribute("data-start_date");
    document.getElementById("editClientAddress").value =
      this.getAttribute("data-address");
    document.getElementById("editClientAbout").value =
      this.getAttribute("data-about");
    document.getElementById("editClientPhone").value =
      this.getAttribute("data-phone");
    clientEditModal.classList.remove("hidden");
    document.getElementById("editClientActive").checked =
      this.getAttribute("data-active") === "1";
  });
});

closeClientEditModal.addEventListener("click", function () {
  clientEditModal.classList.add("hidden");
});

window.addEventListener("click", function (event) {
  if (event.target === clientEditModal) {
    clientEditModal.classList.add("hidden");
  }
});
