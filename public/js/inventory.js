// Inventory - Create Modal
const addInventoryBtn = document.getElementById("addInventoryBtn");
const inventoryModal = document.getElementById("inventoryModal");
const closeInventoryModal = document.getElementById("closeInventoryModal");

addInventoryBtn.addEventListener("click", function () {
  inventoryModal.classList.remove("hidden");
});
closeInventoryModal.addEventListener("click", function () {
  inventoryModal.classList.add("hidden");
});
window.addEventListener("click", function (event) {
  if (event.target === inventoryModal) {
    inventoryModal.classList.add("hidden");
  }
});

// Inventory - Edit Modal
const editInventoryBtns = document.querySelectorAll(".editInventoryBtn");
const inventoryEditModal = document.getElementById("inventoryEditModal");
const closeInventoryEditModal = document.getElementById(
  "closeInventoryEditModal"
);

editInventoryBtns.forEach((btn) => {
  btn.addEventListener("click", function () {
    document.getElementById("editInventoryId").value =
      this.getAttribute("data-id");
    document.getElementById("editInventoryType").value =
      this.getAttribute("data-type");
    document.getElementById("editInventoryName").value =
      this.getAttribute("data-name");
    document.getElementById("editInventoryQuantity").value =
      this.getAttribute("data-quantity");
    inventoryEditModal.classList.remove("hidden");
  });
});
closeInventoryEditModal.addEventListener("click", function () {
  inventoryEditModal.classList.add("hidden");
});
window.addEventListener("click", function (event) {
  if (event.target === inventoryEditModal) {
    inventoryEditModal.classList.add("hidden");
  }
});
