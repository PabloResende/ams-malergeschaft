<?php
// app/views/inventory/index.php
require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../../../config/Database.php';
require_once __DIR__ . '/../../models/Inventory.php';
require_once __DIR__ . '/../../models/InventoryHistoryModel.php';
require_once __DIR__ . '/../../models/Project.php';

// Dados
$pdo             = Database::connect();
$filter          = $_GET['filter'] ?? 'all';
$inventoryItems  = $filter === 'all'
    ? $pdo->query("SELECT * FROM inventory ORDER BY created_at DESC")->fetchAll()
    : (function() use($pdo, $filter){
        $stmt = $pdo->prepare("SELECT * FROM inventory WHERE type = ? ORDER BY created_at DESC");
        $stmt->execute([$filter]);
        return $stmt->fetchAll();
    })();
$allItems        = (new InventoryModel())->getAll('all');
$activeProjects  = ProjectModel::getActiveProjects();
$movements       = (new InventoryHistoryModel())->getAllMovements();
$baseUrl         = '/ams-malergeschaft/public';
?>

<div class="ml-56 pt-20 p-8">
  <h2 class="text-2xl font-bold mb-4"><?= $langText['inventory'] ?></h2>

  <div class="flex justify-between mb-6">
    <div class="flex space-x-2">
      <?php foreach (['all','material','equipment','rented'] as $f): ?>
        <a href="<?= $baseUrl ?>/inventory?filter=<?= $f ?>"
           class="px-3 py-1 rounded-full border <?= $filter=== $f ? 'bg-gray-300' : 'bg-white' ?>">
          <?= $langText[$f] ?? ucfirst($f) ?>
        </a>
      <?php endforeach; ?>
    </div>
    <div class="flex space-x-2">
      <button id="openControlModal" class="bg-indigo-500 hover:bg-indigo-600 text-white px-4 py-2 rounded">
        <?= $langText['stock_control'] ?>
      </button>
      <button id="openHistoryModal" class="bg-gray-700 hover:bg-gray-800 text-white px-4 py-2 rounded">
        <?= $langText['stock_history'] ?>
      </button>
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if (empty($inventoryItems)): ?>
      <p><?= $langText['no_inventory'] ?></p>
    <?php else: foreach($inventoryItems as $item):
      if ((int)$item['quantity'] <= 0) continue;
      $iconCls = match ($item['type']) {
        'material'  => 'text-blue-600',
        'equipment' => 'text-purple-600',
        'rented'    => 'text-yellow-600',
        default     => 'text-gray-600'
      };
    ?>
      <div class="bg-white p-4 rounded-lg shadow">
        <div class="flex items-center mb-2">
          <svg class="w-6 h-6 <?= $iconCls ?>" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10"/>
          </svg>
          <h3 class="text-lg font-bold ml-2"><?= htmlspecialchars($item['name'], ENT_QUOTES) ?></h3>
        </div>
        <p class="text-sm text-gray-600"><?= ucfirst($langText[$item['type']] ?? $item['type']) ?></p>
        <p class="mt-2 text-sm"><?= $langText['quantity'] ?>: <?= (int)$item['quantity'] ?></p>
      </div>
    <?php endforeach; endif; ?>
  </div>
</div>

<!-- Modal: Controle de Estoque -->
<div id="inventoryControlModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
  <div class="bg-white rounded-md p-8 w-11/12 max-w-2xl max-h-[90vh] overflow-y-auto relative">
    <button id="closeControlModal" class="absolute top-4 right-6 text-gray-700 text-2xl">&times;</button>
    <h3 class="text-xl font-bold mb-4"><?= $langText['stock_control'] ?></h3>
    <form id="controlForm" action="<?= $baseUrl ?>/inventory/control/store" method="POST">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div>
          <label class="block text-gray-700"><?= $langText['name'] ?></label>
          <input type="text" name="user_name" class="w-full p-2 border rounded" required>
        </div>
        <div>
          <label class="block text-gray-700"><?= $langText['date'] ?></label>
          <input type="text" id="datetimeInput" name="datetime"
                 class="w-full p-2 border rounded bg-gray-100" readonly required>
        </div>
        <div>
          <label class="block text-gray-700"><?= $langText['filter_by'] ?></label>
          <select name="reason" id="reasonSelect" class="w-full p-2 border rounded">
            <option value=""><?= $langText['select'] ?></option>
            <option value="projeto"><?= $langText['projects'] ?></option>
            <option value="perda"><?= $langText['delete'] ?></option>
            <option value="adição"><?= $langText['add'] ?></option>
            <option value="outros"><?= $langText['others'] ?></option>
            <option value="criar"><?= $langText['add_inventory_item'] ?></option>
          </select>
        </div>
      </div>

      <div id="projectSelectDiv" class="hidden mb-4">
        <label class="block text-gray-700"><?= $langText['projects_list'] ?></label>
        <select name="project_id" class="w-full p-2 border rounded">
          <option value=""><?= $langText['select'] ?></option>
          <?php foreach($activeProjects as $p): ?>
            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name'], ENT_QUOTES) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div id="customReasonDiv" class="hidden mb-4">
        <label class="block text-gray-700"><?= $langText['filter_by'] ?></label>
        <input type="text" name="custom_reason" class="w-full p-2 border rounded">
      </div>

      <div id="newItemDiv" class="hidden mb-6">
        <h4 class="font-semibold mb-2"><?= $langText['add_inventory_item'] ?></h4>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-gray-700"><?= $langText['inventory'] ?></label>
            <input type="text" name="new_item_name" class="w-full p-2 border rounded">
          </div>
          <div>
            <label class="block text-gray-700"><?= $langText['filter_by'] ?></label>
            <select name="new_item_type" class="w-full p-2 border rounded">
              <option value="material"><?= $langText['material'] ?></option>
              <option value="equipment"><?= $langText['equipment'] ?></option>
              <option value="rented"><?= $langText['rented'] ?></option>
            </select>
          </div>
          <div>
            <label class="block text-gray-700"><?= $langText['quantity'] ?></label>
            <input type="number" name="new_item_qty" min="1"
                   class="w-full p-2 border rounded" value="1">
          </div>
        </div>
      </div>

      <div id="stockItemsDiv" class="mb-6">
        <h4 class="font-semibold mb-2"><?= $langText['inventory'] ?></h4>
        <?php if (empty($allItems)): ?>
          <p><?= $langText['no_inventory'] ?></p>
        <?php else: foreach($allItems as $it): ?>
          <div class="flex items-center mb-2">
            <input type="checkbox" class="mr-2 item-checkbox"
                   data-max="<?= $it['quantity'] ?>" value="<?= $it['id'] ?>">
            <span class="flex-1"><?= htmlspecialchars($it['name'], ENT_QUOTES) ?> (<?= $it['quantity'] ?>)</span>
            <input type="number" class="w-20 p-1 border rounded qty-input"
                   min="1" max="<?= $it['quantity'] ?>" value="1" disabled>
          </div>
        <?php endforeach; endif; ?>
        <input type="hidden" name="items" id="itemsData">
      </div>

      <div class="flex justify-end">
        <button type="button" id="cancelControlBtn" class="mr-2 px-4 py-2 border rounded">
          <?= $langText['cancel'] ?>
        </button>
        <button type="submit" class="bg-indigo-500 text-white px-4 py-2 rounded">
          <?= $langText['save'] ?>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Histórico de Estoque -->
<div id="inventoryHistoryModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
  <div class="bg-white rounded-md p-8 w-11/12 max-w-2xl max-h-[90vh] overflow-y-auto relative">
    <button id="closeHistoryModal" class="absolute top-4 right-6 text-gray-700 text-2xl">&times;</button>
    <h3 class="text-xl font-bold mb-4"><?= $langText['stock_history'] ?></h3>
    <?php if (empty($movements)): ?>
      <p><?= $langText['no_inventory'] ?></p>
    <?php else: foreach ($movements as $m): ?>
      <div class="history-item p-4 mb-2 bg-gray-50 rounded <?= match($m['reason']) {
          'projeto' => 'border-l-4 border-green-500',
          'perda'   => 'border-l-4 border-red-500',
          'adição'  => 'border-l-4 border-blue-500',
          'outros'  => 'border-l-4 border-yellow-500',
          'criar'   => 'border-l-4 border-purple-500',
          default   => 'border-l-4 border-gray-300'
        } ?>"
        data-id="<?= $m['id'] ?>">
        <div class="flex justify-between">
          <span>
            <strong><?= htmlspecialchars($m['user_name'], ENT_QUOTES) ?></strong>
            — <?= htmlspecialchars($m['datetime'], ENT_QUOTES) ?>
          </span>
          <span class="arrow cursor-pointer select-none">▸</span>
        </div>
        <div class="history-details mt-2 hidden"></div>
      </div>
    <?php endforeach; endif; ?>
  </div>
</div>

<script>window.baseUrl = '<?= $baseUrl ?>';</script>
<script src="<?= $baseUrl ?>/js/inventory_control.js?v=<?= time() ?>" defer></script>
