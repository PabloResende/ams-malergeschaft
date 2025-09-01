<?php
// system/app/views/inventory/index.php

require_once __DIR__ . '/../layout/header.php';

// Filtros
$filter      = $_GET['filter'] ?? 'all';
$brandFilter = $_GET['brand']  ?? null;

// Monta lista única de marcas de equipment
$brands = array_unique(array_filter(array_map(fn($it) =>
    $it['type'] === 'equipment' && !empty($it['brand']) ? $it['brand'] : null,
    $items
)));
sort($brands);

// Aplica filtros
if ($brandFilter) {
    $inventoryItems = array_filter($items, fn($it) =>
        $it['type'] === 'equipment' &&
        (($it['brand'] ?? '') === $brandFilter)
    );
} elseif ($filter === 'all') {
    $inventoryItems = $items;
} else {
    $inventoryItems = array_filter($items, fn($it) =>
        isset($it['type']) && $it['type'] === $filter
    );
}
?>
<script>
  window.baseUrl  = <?= json_encode(BASE_URL, JSON_UNESCAPED_SLASHES) ?>;
  window.langText = <?= json_encode($langText, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_QUOT|JSON_HEX_APOS) ?>;
</script>

<div class="pt-20 px-4 sm:px-8 ml-0 lg:ml-56 pb-8">
  <h2 class="text-3xl font-bold mb-6"><?= htmlspecialchars($langText['inventory'] ?? 'Estoque', ENT_QUOTES) ?></h2>

  <div class="flex flex-wrap justify-between items-center mb-8 gap-4">
    <div class="flex flex-wrap gap-2">
      <!-- botões de filtro por tipo -->
      <?php foreach (['all','material','equipment','rented'] as $f): ?>
        <a href="<?= url("inventory?filter={$f}") ?>"
           class="px-4 py-2 rounded-full transition
                  <?= $filter === $f && !$brandFilter
                      ? 'bg-gray-300 text-gray-800'
                      : 'bg-white text-gray-600 hover:bg-gray-100' ?>">
          <?= htmlspecialchars($langText[$f] ?? ucfirst($f), ENT_QUOTES) ?>
        </a>
      <?php endforeach; ?>

      <!-- botões de filtro por marca -->
      <?php foreach ($brands as $b): ?>
        <a href="<?= url('inventory?brand='.urlencode($b)) ?>"
           class="px-4 py-2 rounded-full transition
                  <?= $brandFilter === $b
                      ? 'bg-gray-300 text-gray-800'
                      : 'bg-white text-gray-600 hover:bg-gray-100' ?>">
          <?= htmlspecialchars($b, ENT_QUOTES) ?>
        </a>
      <?php endforeach; ?>
    </div>

    <div class="flex gap-2">
      <button id="openControlModal"
              class="bg-indigo-500 hover:bg-indigo-600 text-white px-4 py-2 rounded-lg shadow">
        <?= htmlspecialchars($langText['stock_control'] ?? 'Controle de Estoque', ENT_QUOTES) ?>
      </button>
      <button id="openHistoryModal"
              class="bg-gray-700 hover:bg-gray-800 text-white px-4 py-2 rounded-lg shadow">
        <?= htmlspecialchars($langText['stock_history'] ?? 'Histórico de Estoque', ENT_QUOTES) ?>
      </button>
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if (empty($inventoryItems)): ?>
      <p class="text-gray-500">
        <?= htmlspecialchars($langText['no_inventory'] ?? 'Sem itens em estoque.', ENT_QUOTES) ?>
      </p>
    <?php else: foreach ($inventoryItems as $item):
      if ((int)$item['quantity'] <= 0) continue;
      $iconCls = match ($item['type']) {
        'material'  => 'text-blue-600',
        'equipment' => 'text-purple-600',
        'rented'    => 'text-yellow-600',
        default     => 'text-gray-600'
      };
    ?>
      <div class="inventory-card bg-white rounded-2xl shadow-lg p-6 transition hover:shadow-xl cursor-pointer"
           data-id="<?= $item['id'] ?>">
        <div class="flex items-center mb-4">
          <svg class="w-8 h-8 <?= $iconCls ?>" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10"/>
          </svg>
          <h3 class="text-xl font-semibold ml-3"><?= htmlspecialchars($item['name'], ENT_QUOTES) ?></h3>
        </div>
        <p class="text-sm text-gray-600 flex items-center space-x-2">
          <span><?= ucfirst(htmlspecialchars($langText[$item['type']] ?? $item['type'], ENT_QUOTES)) ?></span>
          <?php if ($item['type'] === 'equipment' && !empty($item['brand'])): ?>
            <span class="bg-gray-200 text-gray-800 px-2 py-1 rounded-full text-xs">
              <?= htmlspecialchars($item['brand'], ENT_QUOTES) ?>
            </span>
          <?php endif; ?>
        </p>
        <p class="mt-2 text-sm text-gray-700">
          <?= htmlspecialchars($langText['quantity'] ?? 'Quantidade', ENT_QUOTES) ?>:
          <span class="font-medium"><?= (int)$item['quantity'] ?></span>
        </p>
        <?php if (!empty($item['description'])): ?>
          <p class="mt-2 text-sm text-gray-500">
            <?= htmlspecialchars($item['description'], ENT_QUOTES) ?>
          </p>
        <?php endif; ?>
      </div>
    <?php endforeach; endif; ?>
  </div>
</div>

<!-- Modal: Controle de Estoque -->
<div id="inventoryControlModal"
     class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm hidden">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-4 sm:mx-auto p-6 sm:p-8 relative max-h-[90vh] overflow-y-auto">
    <button id="closeControlModal"
            class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
    <h3 class="text-2xl font-bold mb-6">
      <?= htmlspecialchars($langText['stock_control'] ?? 'Controle de Estoque', ENT_QUOTES) ?>
    </h3>

    <form id="controlForm" action="<?= url('inventory/control/store') ?>" method="POST" class="space-y-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
          <label class="block mb-2 text-gray-700">
            <?= htmlspecialchars($langText['operator'] ?? 'Usuário', ENT_QUOTES) ?>
          </label>
          <input type="text" name="user_name"
                 class="w-full border rounded-lg p-3 bg-gray-100"
                 readonly
                 value="<?= htmlspecialchars($currentUser, ENT_QUOTES) ?>"
                 required>
        </div>
        <div>
          <label class="block mb-2 text-gray-700">
            <?= htmlspecialchars($langText['date'] ?? 'Data e hora', ENT_QUOTES) ?>
          </label>
          <input type="text" id="datetimeInput" name="datetime"
                 class="w-full border rounded-lg p-3 bg-gray-100"
                 readonly required>
        </div>
        <div>
          <label class="block mb-2 text-gray-700">
            <?= htmlspecialchars($langText['select_reason'] ?? '-- Selecione --', ENT_QUOTES) ?>
          </label>
          <select name="reason" id="reasonSelect" class="w-full border rounded-lg p-3">
            <option value=""><?= htmlspecialchars($langText['select'] ?? '-- Selecione --', ENT_QUOTES) ?></option>
            <option value="projeto">Adicionar ao projeto</option>
            <option value="criar"><?= htmlspecialchars($langText['add_inventory_item'] ?? 'Criar Novo Item', ENT_QUOTES) ?></option>
          </select>
        </div>
      </div>

      <div id="projectSelectDiv" class="hidden space-y-2">
        <label class="block mb-2 text-gray-700">
          <?= htmlspecialchars($langText['projects_list'] ?? 'Lista de Projetos', ENT_QUOTES) ?>
        </label>
        <select name="project_id" class="w-full border rounded-lg p-3">
          <option value=""><?= htmlspecialchars($langText['select'] ?? '-- Selecione --', ENT_QUOTES) ?></option>
          <?php foreach ($activeProjects as $p): ?>
            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name'], ENT_QUOTES) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div id="newItemDiv" class="hidden space-y-4">
        <h4 class="text-lg font-semibold mb-4">
          <?= htmlspecialchars($langText['add_inventory_item'] ?? 'Criar Novo Item', ENT_QUOTES) ?>
        </h4>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div>
            <label class="block mb-2 text-gray-700">Nome do item</label>
            <input type="text" name="new_item_name" class="w-full border rounded-lg p-3">
          </div>
          <div>
            <label class="block mb-2 text-gray-700">Tipo do item</label>
            <select name="new_item_type" id="newItemType" class="w-full border rounded-lg p-3">
              <option value="material"><?= htmlspecialchars($langText['material'] ?? 'Material', ENT_QUOTES) ?></option>
              <option value="equipment"><?= htmlspecialchars($langText['equipment'] ?? 'Equipamento', ENT_QUOTES) ?></option>
              <option value="rented"><?= htmlspecialchars($langText['rented'] ?? 'Alugado', ENT_QUOTES) ?></option>
            </select>
          </div>
          <div>
            <label class="block mb-2 text-gray-700"><?= htmlspecialchars($langText['quantity'] ?? 'Quantidade', ENT_QUOTES) ?></label>
            <input type="number" name="new_item_qty" min="1"
                   class="w-full border rounded-lg p-3" value="1">
          </div>
        </div>

        <div id="brandDiv" class="hidden">
          <label class="block mb-2 text-gray-700">Marca</label>
          <input type="text" name="new_item_brand" class="w-full border rounded-lg p-3" placeholder="Ex: Samsung">
        </div>

        <div>
          <label class="block mb-2 text-gray-700">
            <?= htmlspecialchars($langText['new_item_description'] ?? 'Descrição do Novo Item', ENT_QUOTES) ?>
          </label>
          <textarea name="new_item_description" class="w-full border rounded-lg p-3" rows="3"
                    placeholder="<?= htmlspecialchars($langText['new_item_description'] ?? 'Descrição do Novo Item', ENT_QUOTES) ?>"></textarea>
        </div>
      </div>

      <div id="stockItemsDiv" class="space-y-4">
        <h4 class="text-lg font-semibold"><?= htmlspecialchars($langText['inventory'] ?? 'Estoque', ENT_QUOTES) ?></h4>
        <?php $available = array_filter($items, fn($it) => (int)$it['quantity'] > 0); ?>
        <?php if (empty($available)): ?>
          <p class="text-gray-500"><?= htmlspecialchars($langText['no_inventory'] ?? 'Sem itens disponíveis.', ENT_QUOTES) ?></p>
        <?php else: foreach ($available as $it): ?>
          <div class="flex items-center gap-4">
            <input type="checkbox" class="item-checkbox h-5 w-5"
                   data-max="<?= $it['quantity'] ?>" value="<?= $it['id'] ?>">
            <span class="flex-1"><?= htmlspecialchars($it['name'], ENT_QUOTES) ?> (<?= $it['quantity'] ?>)</span>
            <input type="number" class="qty-input w-20 p-2 border rounded-lg"
                   min="1" max="<?= $it['quantity'] ?>" value="1" disabled>
          </div>
        <?php endforeach; endif; ?>
        <input type="hidden" name="items" id="itemsData" value="[]">
      </div>

      <div class="flex justify-end gap-4">
        <button type="button" id="cancelControlBtn"
                class="px-4 py-2 border rounded-lg hover:bg-gray-100">
          <?= htmlspecialchars($langText['cancel'] ?? 'Cancelar', ENT_QUOTES) ?>
        </button>
        <button type="submit"
                class="bg-indigo-500 hover:bg-indigo-600 text-white px-6 py-2 rounded-lg shadow">
          <?= htmlspecialchars($langText['save'] ?? 'Salvar', ENT_QUOTES) ?>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Histórico de Estoque -->
<div id="inventoryHistoryModal"
     class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm hidden">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-4 sm:mx-auto p-6 sm:p-8 relative max-h-[90vh] overflow-y-auto">
    <button id="closeHistoryModal"
            class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
    <h3 class="text-2xl font-bold mb-6">
      <?= htmlspecialchars($langText['stock_history'] ?? 'Histórico de Estoque', ENT_QUOTES) ?>
    </h3>

    <?php if (empty($movements)): ?>
      <p class="text-gray-500"><?= htmlspecialchars($langText['no_inventory'] ?? 'Sem histórico.', ENT_QUOTES) ?></p>
    <?php else: foreach ($movements as $m): ?>
      <div class="history-item p-4 mb-4 bg-gray-50 rounded-2xl border-l-4
                  <?= match($m['reason']) {
                       'projeto' => 'border-green-500',
                       'perda'   => 'border-red-500',
                       'criar'   => 'border-purple-500',
                       default   => 'border-gray-300'
                     } ?>"
           data-id="<?= htmlspecialchars($m['id'], ENT_QUOTES) ?>">
        <div class="flex justify-between items-center">
          <span class="font-medium"><?= htmlspecialchars($m['user_name'], ENT_QUOTES) ?></span>
          <span class="text-sm text-gray-500"><?= htmlspecialchars($m['datetime'], ENT_QUOTES) ?></span>
          <button class="toggleHistory arrow text-gray-400 hover:text-gray-600 select-none">▸</button>
        </div>
        <div class="history-details mt-2 hidden"></div>
      </div>
    <?php endforeach; endif; ?>
  </div>
</div>

<!-- Modal: Detalhes / Ações Rápidas -->
<div id="quickActionsModal"
     class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm hidden">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 sm:mx-auto p-6 relative">
    <button id="closeQuickActionsModal"
            class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
    <h3 id="quickActionsTitle" class="text-2xl font-bold mb-4"></h3>
    <input type="hidden" id="quickActionsId">

    <div id="qrContainer" class="mb-4 text-center hidden">
      <img id="qrImg" alt="QR Code" class="mx-auto mb-2"/>
      <a id="downloadQr" download class="text-sm hover:underline block">Baixar QR Code</a>
    </div>

    <div class="space-y-4">
      <div>
        <label class="block mb-1">Adicionar quantidade</label>
        <div class="flex gap-2">
          <input type="number" id="quickAddQty" min="1" value="1"
                 class="w-full border rounded-lg p-2">
          <button id="quickAdd"
                  class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg">
            Adicionar
          </button>
        </div>
      </div>
      <div>
        <label class="block mb-1">Editar descrição</label>
        <textarea id="quickDescTxt" rows="3"
                  class="w-full border rounded-lg p-2"></textarea>
        <button id="quickDescSave"
                class="mt-2 px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg">
          Salvar descrição
        </button>
      </div>
      <div>
        <button id="quickDelete"
                class="w-full px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg">
          Excluir item
        </button>
      </div>
    </div>
  </div>
</div>

<script>
// Deep-link para abrir modal de detalhe via ?detailId=<id>
document.addEventListener('DOMContentLoaded', () => {
  const params   = new URLSearchParams(window.location.search);
  const detailId = params.get('detailId');
  if (detailId) {
    const card = document.querySelector(`.inventory-card[data-id="${detailId}"]`);
    if (card) card.click();
  }
});
</script>

<script src="<?= asset('js/inventory_control.js') ?>" defer></script>
<?php require __DIR__ . '/../layout/footer.php'; ?>
