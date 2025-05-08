<?php
// app/views/inventory/index.php
require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../../../config/Database.php';
require_once __DIR__ . '/../../models/Inventory.php';
require_once __DIR__ . '/../../models/InventoryHistoryModel.php';
require_once __DIR__ . '/../../models/Project.php';

$pdo = Database::connect();

date_default_timezone_set('Europe/Zurich');
// 1) Listagem principal
$filter = $_GET['filter'] ?? 'all';
if ($filter !== 'all') {
    $stmt = $pdo->prepare("SELECT * FROM inventory WHERE type = ? ORDER BY created_at DESC");
    $stmt->execute([$filter]);
} else {
    $stmt = $pdo->query("SELECT * FROM inventory ORDER BY created_at DESC");
}
$inventoryItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2) Dados para modal de controle
$invModel       = new InventoryModel();
$allItems       = $invModel->getAll('all');
$activeProjects = ProjectModel::getActiveProjects();

// 3) Dados para modal de histórico
$histModel = new InventoryHistoryModel();
$movements = $histModel->getAllMovements();

$baseUrl = '/ams-malergeschaft/public';
?>
<div class="ml-56 pt-20 p-8">
  <h2 class="text-2xl font-bold mb-4"><?= $langText['inventory'] ?? 'Inventory' ?></h2>

  <div class="flex justify-between mb-6">
    <div class="flex space-x-2">
      <a href="<?= $baseUrl ?>/inventory?filter=all"
         class="px-3 py-1 rounded-full border <?= $filter==='all' ? 'bg-gray-300' : 'bg-white' ?>">All</a>
      <a href="<?= $baseUrl ?>/inventory?filter=material"
         class="px-3 py-1 rounded-full border <?= $filter==='material' ? 'bg-blue-200 text-blue-800' : 'bg-white' ?>">
        Material
      </a>
      <a href="<?= $baseUrl ?>/inventory?filter=equipment"
         class="px-3 py-1 rounded-full border <?= $filter==='equipment' ? 'bg-purple-200 text-purple-800' : 'bg-white' ?>">
        Equipment
      </a>
      <a href="<?= $baseUrl ?>/inventory?filter=rented"
         class="px-3 py-1 rounded-full border <?= $filter==='rented' ? 'bg-yellow-200 text-yellow-800' : 'bg-white' ?>">
        Rented
      </a>
    </div>
    <div class="flex space-x-2">
      <button id="openControlModal" class="bg-indigo-500 hover:bg-indigo-600 text-white px-4 py-2 rounded">
        Controle de Estoque
      </button>
      <button id="openHistoryModal" class="bg-gray-700 hover:bg-gray-800 text-white px-4 py-2 rounded">
        Histórico de Estoque
      </button>
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if (empty($inventoryItems)): ?>
      <p><?= $langText['no_inventory'] ?? 'No inventory items found.' ?></p>
    <?php else: foreach($inventoryItems as $item): ?>
      <?php
        $iconCls = match($item['type']) {
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
        <p class="text-sm text-gray-600"><?= ucfirst(htmlspecialchars($item['type'], ENT_QUOTES)) ?></p>
        <p class="mt-2 text-sm"><?= $langText['quantity'] ?? 'Quantity' ?>: <?= (int)$item['quantity'] ?></p>
      </div>
    <?php endforeach; endif; ?>
  </div>
</div>

<!-- Modal: Controle de Estoque -->
<div id="inventoryControlModal"
     class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
  <div class="bg-white rounded-md p-8 w-11/12 max-w-2xl max-h-[90vh] overflow-y-auto relative">
    <button id="closeControlModal" class="absolute top-4 right-6 text-gray-700 text-2xl">&times;</button>
    <h3 class="text-xl font-bold mb-4">Controle de Estoque</h3>
    <form id="controlForm" action="<?= $baseUrl ?>/inventory/control/store" method="POST">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div>
          <label class="block text-gray-700">Seu nome</label>
          <input type="text" id="userNameInput" name="user_name"
                 class="w-full p-2 border rounded" required>
        </div>
        <div>
          <label class="block text-gray-700">Data e hora</label>
          <input
            type="text"
            id="datetimeInput"
            name="datetime"
            value="<?= (new DateTime('now', new DateTimeZone('Europe/Zurich')))->format('Y-m-d H:i:s') ?>"
            class="w-full p-2 border rounded bg-gray-100"
            readonly
            required
          >
        </div>
        <div>
          <label class="block text-gray-700">Motivo</label>
          <select name="reason" id="reasonSelect"
                  class="w-full p-2 border rounded">
            <option value="">-- Selecione --</option>
            <option value="projeto">Projeto</option>
            <option value="perda">Perda</option>
            <option value="adição">Adição</option>
            <option value="outros">Outros</option>
            <option value="criar">Criar Novo Item</option>
          </select>
        </div>
      </div>

      <div id="projectSelectDiv" class="hidden mb-4">
        <label class="block text-gray-700">Projeto</label>
        <select name="project_id" class="w-full p-2 border rounded">
          <option value="">-- Selecione --</option>
          <?php foreach ($activeProjects as $p): ?>
            <option value="<?= htmlspecialchars($p['id'], ENT_QUOTES) ?>">
              <?= htmlspecialchars($p['name'], ENT_QUOTES) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div id="customReasonDiv" class="hidden mb-4">
        <label class="block text-gray-700">Descreva o motivo</label>
        <input type="text" name="custom_reason"
               class="w-full p-2 border rounded">
      </div>

      <div id="newItemDiv" class="hidden mb-6">
        <h4 class="font-semibold mb-2">Criar Novo Item</h4>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-gray-700">Nome do Item</label>
            <input type="text" id="newItemName" name="new_item_name"
                   class="w-full p-2 border rounded">
          </div>
          <div>
            <label class="block text-gray-700">Tipo</label>
            <select id="newItemType" name="new_item_type"
                    class="w-full p-2 border rounded">
              <option value="material">Material</option>
              <option value="equipment">Equipment</option>
              <option value="rented">Rented</option>
            </select>
          </div>
          <div>
            <label class="block text-gray-700">Quantidade</label>
            <input type="number" id="newItemQty" name="new_item_qty" min="1"
                   class="w-full p-2 border rounded">
          </div>
        </div>
      </div>

      <div id="stockItemsDiv" class="mb-6">
        <h4 class="font-semibold mb-2">Itens em Estoque</h4>
        <?php if (empty($allItems)): ?>
          <p>Nenhum item disponível.</p>
        <?php else: foreach ($allItems as $it): if ($it['quantity'] <= 0) continue; ?>
          <div class="flex items-center mb-2">
            <input type="checkbox"
                   class="mr-2 item-checkbox"
                   data-max="<?= (int)$it['quantity'] ?>"
                   value="<?= htmlspecialchars($it['id'], ENT_QUOTES) ?>">
            <span class="flex-1">
              <?= htmlspecialchars($it['name'], ENT_QUOTES) ?>
              (<?= (int)$it['quantity'] ?> disponíveis)
            </span>
            <input type="number"
                   class="w-20 p-1 border rounded qty-input"
                   min="1"
                   max="<?= (int)$it['quantity'] ?>"
                   value="1"
                   disabled>
          </div>
        <?php endforeach; endif; ?>
        <input type="hidden" name="items" id="itemsData">
      </div>

      <div class="flex justify-end">
        <button type="button" id="cancelControlBtn"
                class="mr-2 px-4 py-2 border rounded">Cancelar</button>
        <button type="submit"
                class="bg-indigo-500 text-white px-4 py-2 rounded">Registrar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Histórico de Estoque -->
<div id="inventoryHistoryModal"
     class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
  <div class="bg-white rounded-md p-8 w-11/12 max-w-2xl max-h-[90vh] overflow-y-auto relative">
    <button id="closeHistoryModal" class="absolute top-4 right-6 text-gray-700 text-2xl">&times;</button>
    <h3 class="text-xl font-bold mb-4">Histórico de Movimentações</h3>

    <?php if (empty($movements)): ?>
      <p>Nenhum histórico encontrado.</p>
    <?php else: foreach ($movements as $m):
      $border = match ($m['reason']) {
        'projeto' => 'border-l-4 border-green-500',
        'perda'   => 'border-l-4 border-red-500',
        'adição'  => 'border-l-4 border-blue-500',
        'outros'  => 'border-l-4 border-yellow-500',
        'criar'   => 'border-l-4 border-purple-500',
        default   => 'border-l-4 border-gray-300'
      };
    ?>
      <div class="history-item p-4 mb-2 bg-gray-50 rounded <?= $border ?>"
           data-id="<?= htmlspecialchars($m['id'], ENT_QUOTES) ?>">
        <div class="flex items-center justify-between">
          <div>
            <span class="font-semibold"><?= htmlspecialchars($m['user_name'], ENT_QUOTES) ?></span>
            &mdash; <?= htmlspecialchars($m['datetime'], ENT_QUOTES) ?>
          </div>
          <span class="arrow text-gray-600 text-xl cursor-pointer select-none">▸</span>
        </div>
        <div class="history-details mt-2 hidden"></div>
      </div>
    <?php endforeach; endif; ?>
  </div>
</div>

<script>window.baseUrl = '<?= $baseUrl ?>';</script>
<script src="<?= $baseUrl ?>/js/inventory_control.js"></script>
