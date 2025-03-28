<?php
require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../../../config/Database.php';
$pdo = Database::connect();
$baseUrl = '/ams-malergeschaft/public';

$filter = $_GET['filter'] ?? 'all';
if ($filter !== 'all') {
    $stmt = $pdo->prepare("SELECT * FROM inventory WHERE type = ? ORDER BY created_at DESC");
    $stmt->execute([$filter]);
} else {
    $stmt = $pdo->query("SELECT * FROM inventory ORDER BY created_at DESC");
}
$inventoryItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="ml-56 pt-20 p-8 relative">
    <h2 class="text-2xl font-bold mb-4"><?= $langText['inventory'] ?? 'Inventory' ?></h2>
    <p class="text-lg text-gray-600 mb-8"><?= $langText['manage_inventory'] ?? 'Manage your inventory items' ?></p>

    <!-- Filtros -->
    <div class="mb-6">
        <span class="mr-4 font-semibold"><?= $langText['filter_by'] ?? 'Filter by' ?>:</span>
        <a href="<?= $baseUrl ?>/inventory?filter=all" class="mr-2 px-3 py-1 rounded-full border <?= $filter=='all' ? 'bg-gray-300' : 'bg-white' ?>">All</a>
        <a href="<?= $baseUrl ?>/inventory?filter=material" class="mr-2 px-3 py-1 rounded-full border <?= $filter=='material' ? 'bg-blue-200 text-blue-800' : 'bg-white' ?>"><?= $langText['material'] ?? 'Material' ?></a>
        <a href="<?= $baseUrl ?>/inventory?filter=equipment" class="mr-2 px-3 py-1 rounded-full border <?= $filter=='equipment' ? 'bg-purple-200 text-purple-800' : 'bg-white' ?>"><?= $langText['equipment'] ?? 'Equipment' ?></a>
        <a href="<?= $baseUrl ?>/inventory?filter=rented" class="mr-2 px-3 py-1 rounded-full border <?= $filter=='rented' ? 'bg-yellow-200 text-yellow-800' : 'bg-white' ?>"><?= $langText['rented'] ?? 'Rented' ?></a>
    </div>

    <!-- Grid de Itens do Estoque -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (empty($inventoryItems)): ?>
            <p><?= $langText['no_inventory'] ?? 'No inventory items found.' ?></p>
        <?php else: ?>
            <?php foreach ($inventoryItems as $item): ?>
                <?php
                switch ($item['type']) {
                    case 'material':
                        $icon = '<svg class="w-6 h-6 text-red-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="9" width="18" height="6" rx="1" ry="1"></rect>
                                    <line x1="3" y1="12" x2="21" y2="12"></line>
                                </svg>';
                        break;
                    case 'equipment':
                        $icon = '<svg class="w-6 h-6 text-purple-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M3 16h18M6 16v4M18 16v4M10 16V4h4v12"></path>
                                </svg>';
                        break;
                    case 'rented':
                        $icon = '<svg class="w-6 h-6 text-yellow-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="1" y="3" width="15" height="13" rx="2" ry="2"></rect>
                                    <path d="M16 8h6l3 5v5h-3"></path>
                                    <circle cx="6" cy="20" r="2"></circle>
                                    <circle cx="16" cy="20" r="2"></circle>
                                </svg>';
                        break;
                    default:
                        $icon = '';
                        break;
                }
                ?>
                <div class="bg-white p-4 rounded-lg shadow">
                    <div class="flex items-center mb-2">
                        <?= $icon ?>
                        <h3 class="text-lg font-bold ml-2"><?= htmlspecialchars($item['name']) ?></h3>
                    </div>
                    <p class="text-sm text-gray-600"><?= ucfirst(htmlspecialchars($item['type'])) ?></p>
                    <p class="mt-2 text-sm"><?= $langText['quantity'] ?? 'Quantity' ?>: <?= htmlspecialchars($item['quantity']) ?></p>
                    <div class="mt-4 flex justify-end space-x-2">
                        <button class="text-blue-500 hover:underline text-sm editInventoryBtn"
                            data-id="<?= $item['id'] ?>"
                            data-type="<?= htmlspecialchars($item['type']) ?>"
                            data-name="<?= htmlspecialchars($item['name']) ?>"
                            data-quantity="<?= htmlspecialchars($item['quantity']) ?>"
                        >
                            <?= $langText['edit'] ?? 'Edit' ?>
                        </button>
                        <a href="<?= $baseUrl ?>/inventory/delete?id=<?= $item['id'] ?>" class="text-red-500 hover:underline text-sm" onclick="return confirm('Are you sure you want to delete this item?');">
                            <?= $langText['delete'] ?? 'Delete' ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Botão Flutuante para Adicionar Item -->
    <button id="addInventoryBtn" class="fixed bottom-8 right-8 bg-green-500 text-white rounded-full p-4 shadow-lg hover:bg-green-600">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
    </button>

    <!-- Modal de Adição de Item (igual ao anterior) -->
    <div id="inventoryModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
        <div class="bg-white rounded-lg p-6 w-80 max-h-[80vh] overflow-y-auto">
            <h3 class="text-xl font-bold mb-4"><?= $langText['add_inventory_item'] ?? 'Add Inventory Item' ?></h3>
            <form id="inventoryForm" action="<?= $baseUrl ?>/inventory/store" method="POST">
                <div class="mb-4">
                    <label class="block text-gray-700"><?= $langText['type'] ?? 'Type' ?></label>
                    <select name="type" class="w-full p-2 border rounded">
                        <option value="material"><?= $langText['material'] ?? 'Material' ?></option>
                        <option value="equipment"><?= $langText['equipment'] ?? 'Equipment' ?></option>
                        <option value="rented"><?= $langText['rented'] ?? 'Rented' ?></option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700"><?= $langText['name'] ?? 'Name' ?></label>
                    <input type="text" name="name" class="w-full p-2 border rounded" required>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700"><?= $langText['quantity'] ?? 'Quantity' ?></label>
                    <input type="number" name="quantity" class="w-full p-2 border rounded" required>
                </div>
                <div class="flex justify-end">
                    <button type="button" id="closeModal" class="mr-2 px-4 py-2 border rounded"><?= $langText['cancel'] ?? 'Cancel' ?></button>
                    <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded"><?= $langText['submit'] ?? 'Submit' ?></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de Edição de Item do Estoque -->
    <div id="inventoryEditModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
        <div class="bg-white rounded-lg p-6 w-80 max-h-[80vh] overflow-y-auto">
            <h3 class="text-xl font-bold mb-4"><?= $langText['edit_inventory_item'] ?? 'Edit Inventory Item' ?></h3>
            <form id="inventoryEditForm" action="<?= $baseUrl ?>/inventory/update" method="POST">
                <input type="hidden" name="id" id="editInventoryId">
                <div class="mb-4">
                    <label class="block text-gray-700"><?= $langText['type'] ?? 'Type' ?></label>
                    <select name="type" id="editInventoryType" class="w-full p-2 border rounded">
                        <option value="material"><?= $langText['material'] ?? 'Material' ?></option>
                        <option value="equipment"><?= $langText['equipment'] ?? 'Equipment' ?></option>
                        <option value="rented"><?= $langText['rented'] ?? 'Rented' ?></option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700"><?= $langText['name'] ?? 'Name' ?></label>
                    <input type="text" name="name" id="editInventoryName" class="w-full p-2 border rounded" required>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700"><?= $langText['quantity'] ?? 'Quantity' ?></label>
                    <input type="number" name="quantity" id="editInventoryQuantity" class="w-full p-2 border rounded" required>
                </div>
                <div class="flex justify-end">
                    <button type="button" id="closeInventoryEditModal" class="mr-2 px-4 py-2 border rounded"><?= $langText['cancel'] ?? 'Cancel' ?></button>
                    <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded"><?= $langText['submit'] ?? 'Submit' ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Inventory - Create Modal
    const addInventoryBtn = document.getElementById('addInventoryBtn');
    const inventoryModal = document.getElementById('inventoryModal');
    const closeModal = document.getElementById('closeModal');

    addInventoryBtn.addEventListener('click', function() {
        inventoryModal.classList.remove('hidden');
    });
    closeModal.addEventListener('click', function() {
        inventoryModal.classList.add('hidden');
    });
    window.addEventListener('click', function(event) {
        if (event.target === inventoryModal) {
            inventoryModal.classList.add('hidden');
        }
    });

    // Inventory - Edit Modal
    const editInventoryBtns = document.querySelectorAll('.editInventoryBtn');
    const inventoryEditModal = document.getElementById('inventoryEditModal');
    const closeInventoryEditModal = document.getElementById('closeInventoryEditModal');

    editInventoryBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('editInventoryId').value = this.getAttribute('data-id');
            document.getElementById('editInventoryType').value = this.getAttribute('data-type');
            document.getElementById('editInventoryName').value = this.getAttribute('data-name');
            document.getElementById('editInventoryQuantity').value = this.getAttribute('data-quantity');
            inventoryEditModal.classList.remove('hidden');
        });
    });
    closeInventoryEditModal.addEventListener('click', function() {
        inventoryEditModal.classList.add('hidden');
    });
    window.addEventListener('click', function(event) {
        if (event.target === inventoryEditModal) {
            inventoryEditModal.classList.add('hidden');
        }
    });
</script>

