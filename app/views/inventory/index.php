<?php
// app/views/inventory/index.php
require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../../../config/Database.php';
require_once __DIR__ . '/../../models/Inventory.php';
require_once __DIR__ . '/../../models/InventoryHistoryModel.php';
require_once __DIR__ . '/../../models/Project.php';

$pdo = Database::connect();

// Para listagem normal
$filter = $_GET['filter'] ?? 'all';
if ($filter !== 'all') {
    $stmt = $pdo->prepare("SELECT * FROM inventory WHERE type = ? ORDER BY created_at DESC");
    $stmt->execute([$filter]);
} else {
    $stmt = $pdo->query("SELECT * FROM inventory ORDER BY created_at DESC");
}
$inventoryItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Para o modal de controle
$invModel        = new InventoryModel();
$allItems        = $invModel->getAll('all');
$activeProjects  = ProjectModel::getActiveProjects();

// Para o modal de histórico
$histModel = new InventoryHistoryModel();
$movements = $histModel->getAllMovements();

$baseUrl = '/ams-malergeschaft/public';
?>
<div class="ml-56 pt-20 p-8">
  <h2 class="text-2xl font-bold mb-4"><?= $langText['inventory'] ?? 'Inventory' ?></h2>
  <p class="text-lg text-gray-600 mb-8"><?= $langText['manage_inventory'] ?? 'Manage your inventory items' ?></p>

  <!-- Botões que abrem modais -->
  <div class="flex space-x-2 mb-6">
    <button id="openControlModal"
            class="bg-indigo-500 hover:bg-indigo-600 text-white px-4 py-2 rounded">
      Controle de Estoque
    </button>
    <button id="openHistoryModal"
            class="bg-gray-700 hover:bg-gray-800 text-white px-4 py-2 rounded">
      Histórico de Estoque
    </button>
  </div>

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
            case 'material':  $iconCls='text-red-600';   break;
            case 'equipment': $iconCls='text-purple-600';break;
            case 'rented':    $iconCls='text-yellow-600';break;
            default:          $iconCls='text-gray-600';
          }
        ?>
        <div class="bg-white p-4 rounded-lg shadow">
          <div class="flex items-center mb-2">
            <svg class="w-6 h-6 <?= $iconCls ?>" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <circle cx="12" cy="12" r="10"></circle>
            </svg>
            <h3 class="text-lg font-bold ml-2"><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></h3>
          </div>
          <p class="text-sm text-gray-600"><?= ucfirst(htmlspecialchars($item['type'], ENT_QUOTES, 'UTF-8')) ?></p>
          <p class="mt-2 text-sm"><?= $langText['quantity'] ?? 'Quantity' ?>: <?= htmlspecialchars($item['quantity'], ENT_QUOTES, 'UTF-8') ?></p>
          <div class="mt-4 flex justify-end space-x-2">
            <a href="<?= $baseUrl ?>/inventory/delete?id=<?= $item['id'] ?>"
               class="text-red-500 hover:underline text-sm"
               onclick="return confirm('<?= $langText['confirm_delete'] ?? 'Are you sure?' ?>');">
              <?= $langText['delete'] ?? 'Delete' ?>
            </a>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<!-- Modal de Controle de Estoque -->
<div id="inventoryControlModal"
     class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
  <div class="bg-white rounded-md p-8 w-11/12 max-w-2xl max-h-[90vh] overflow-y-auto relative">
    <button id="closeControlModal" class="absolute top-4 right-6 text-gray-700 text-2xl">&times;</button>
    <h3 class="text-xl font-bold mb-4">Controle de Estoque</h3>
    <form id="controlForm" action="<?= $baseUrl ?>/inventory/control/store" method="POST">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <!-- Nome -->
        <div>
          <label class="block text-gray-700">Seu nome</label>
          <input type="text" name="user_name" class="w-full p-2 border rounded" required>
        </div>
        <!-- Data e hora -->
        <div>
          <label class="block text-gray-700">Data e hora</label>
          <input type="text" name="datetime" value="<?= date('Y-m-d H:i:s') ?>"
                 class="w-full p-2 border rounded bg-gray-100" readonly>
        </div>
        <!-- Motivo -->
        <div>
          <label class="block text-gray-700">Motivo</label>
          <select name="reason" id="reasonSelect" class="w-full p-2 border rounded">
              <option value="perda">Perda</option>
              <option value="projeto">Projeto</option>
            <option value="outros">Outros</option>
          </select>
        </div>
        <!-- Motivo personalizado -->
        <div id="customReasonDiv" class="hidden">
          <label class="block text-gray-700">Descreva o motivo</label>
          <input type="text" name="custom_reason" class="w-full p-2 border rounded">
        </div>
        <!-- Seleção de projeto -->
        <div id="projectSelectDiv" class="hidden">
          <label class="block text-gray-700">Projeto</label>
          <select name="project_id" class="w-full p-2 border rounded">
            <option value="">-- Selecione --</option>
            <?php foreach ($activeProjects as $p): ?>
              <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <h4 class="font-semibold mb-2">Itens em Estoque</h4>
      <div class="mb-6">
        <?php if (empty($allItems)): ?>
          <p>Nenhum item disponível.</p>
        <?php else: ?>
          <?php foreach ($allItems as $it): ?>
            <?php if ($it['quantity'] <= 0) continue; ?>
            <div class="flex items-center mb-2">
              <input type="checkbox"
                     class="mr-2 item-checkbox"
                     data-max="<?= $it['quantity'] ?>"
                     value="<?= $it['id'] ?>">
              <span class="flex-1"><?= htmlspecialchars($it['name'], ENT_QUOTES, 'UTF-8') ?>
                (Disponível: <?= $it['quantity'] ?>)
              </span>
              <input type="number"
                     class="w-20 p-1 border rounded qty-input"
                     min="1"
                     max="<?= $it['quantity'] ?>"
                     value="1"
                     disabled>
            </div>
          <?php endforeach; ?>
          <input type="hidden" name="items" id="itemsData">
        <?php endif; ?>
      </div>

      <div class="flex justify-end">
        <button type="button" id="cancelControlBtn" class="mr-2 px-4 py-2 border rounded">Cancelar</button>
        <button type="submit" class="bg-indigo-500 text-white px-4 py-2 rounded">Registrar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal de Histórico de Estoque -->
<div id="inventoryHistoryModal"
     class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
  <div class="bg-white rounded-md p-8 w-11/12 max-w-2xl max-h-[90vh] overflow-y-auto relative">
    <button id="closeHistoryModal" class="absolute top-4 right-6 text-gray-700 text-2xl">&times;</button>
    <h3 class="text-xl font-bold mb-4">Histórico de Movimentações</h3>
    <ul id="historyList" class="space-y-2 mb-4">
      <?php foreach ($movements as $m):
        $color = $m['reason']==='projeto' ? 'bg-green-200'
               : ($m['reason']==='perda'    ? 'bg-red-200'
                                           : 'bg-yellow-200');
      ?>
        <li class="p-4 rounded <?= $color ?> cursor-pointer history-item"
            data-id="<?= $m['id'] ?>">
          <strong><?= htmlspecialchars($m['user_name'], ENT_QUOTES, 'UTF-8') ?></strong>
          — <?= $m['datetime'] ?> (<?= ucfirst($m['reason']) ?>)
        </li>
      <?php endforeach; ?>
    </ul>
    <div id="historyDetails" class="hidden p-4 bg-gray-50 rounded"></div>
  </div>
</div>

<!-- Scripts -->
<script src="<?= $baseUrl ?>/js/inventory.js"></script>
<script src="<?= $baseUrl ?>/js/inventory_control.js"></script>
<script>
// Abre / fecha modais
const ctrlModal = document.getElementById('inventoryControlModal');
const histModal = document.getElementById('inventoryHistoryModal');

document.getElementById('openControlModal')
        .addEventListener('click', () => ctrlModal.classList.remove('hidden'));
document.getElementById('cancelControlBtn')
        .addEventListener('click', () => ctrlModal.classList.add('hidden'));
document.getElementById('closeControlModal')
        .addEventListener('click', () => ctrlModal.classList.add('hidden'));
window.addEventListener('click', e => {
  if (e.target === ctrlModal) ctrlModal.classList.add('hidden');
});

document.getElementById('openHistoryModal')
        .addEventListener('click', () => histModal.classList.remove('hidden'));
document.getElementById('closeHistoryModal')
        .addEventListener('click', () => histModal.classList.add('hidden'));
window.addEventListener('click', e => {
  if (e.target === histModal) histModal.classList.add('hidden');
});

// Expande detalhes no modal de histórico
document.querySelectorAll('.history-item').forEach(li => {
  li.addEventListener('click', () => {
    const id = li.dataset.id;
    fetch('<?= $baseUrl ?>/inventory/history/details?id=' + id)
      .then(r => r.json())
      .then(data => {
        let html = '<h4 class="font-semibold mb-2">Detalhes</h4><ul class="list-disc pl-5">';
        data.forEach(d => html += `<li>${d.item_name}: ${d.quantity}</li>`);
        html += '</ul>';
        const det = document.getElementById('historyDetails');
        det.innerHTML = html;
        det.classList.remove('hidden');
      });
  });
});
</script>
