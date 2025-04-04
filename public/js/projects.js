// Project - Create Modal
const addProjectBtn = document.getElementById("addProjectBtn");
const projectModal = document.getElementById("projectModal");
const closeModal = document.getElementById("closeModal");

addProjectBtn.addEventListener("click", function () {
  projectModal.classList.remove("hidden");
});
closeModal.addEventListener("click", function () {
  projectModal.classList.add("hidden");
});
window.addEventListener("click", function (event) {
  if (event.target === projectModal) {
    projectModal.classList.add("hidden");
  }
});

// Project - Edit Modal
const editProjectBtns = document.querySelectorAll(".editProjectBtn");
const projectEditModal = document.getElementById("projectEditModal");
const closeProjectEditModal = document.getElementById("closeProjectEditModal");

editProjectBtns.forEach((btn) => {
  btn.addEventListener("click", function () {
    document.getElementById("editProjectId").value =
      this.getAttribute("data-id");
    document.getElementById("editProjectName").value =
      this.getAttribute("data-name");
    document.getElementById("editProjectClientName").value =
      this.getAttribute("data-client_name");
    document.getElementById("editProjectDescription").value =
      this.getAttribute("data-description");
    document.getElementById("editProjectStartDate").value =
      this.getAttribute("data-start_date");
    document.getElementById("editProjectEndDate").value =
      this.getAttribute("data-end_date");
    document.getElementById("editProjectTotalHours").value =
      this.getAttribute("data-total_hours");
    document.getElementById("editProjectStatus").value =
      this.getAttribute("data-status");
    document.getElementById("editProjectProgress").value =
      this.getAttribute("data-progress");

    projectEditModal.classList.remove("hidden");
  });
});
closeProjectEditModal.addEventListener("click", function () {
  projectEditModal.classList.add("hidden");
});
window.addEventListener("click", function (event) {
  if (event.target === projectEditModal) {
    projectEditModal.classList.add("hidden");
  }
});
