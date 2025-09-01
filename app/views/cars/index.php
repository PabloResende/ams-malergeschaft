<?php
// system/app/views/cars/index.php

require_once __DIR__ . '/../layout/header.php';
?>
<script>
  window.baseUrl  = <?= json_encode(BASE_URL, JSON_UNESCAPED_SLASHES) ?>;
  window.langText = <?= json_encode($langText, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_QUOT|JSON_HEX_APOS) ?>;
</script>

<div class="md:ml-56 ml-0 pt-20 px-4 sm:px-8 pb-8">
  <h2 class="text-3xl font-bold mb-6">
    <?= htmlspecialchars($langText['cars_list'] ?? 'Lista de Veículos', ENT_QUOTES) ?>
  </h2>

  <!-- Botão flutuante “+” -->
  <button id="openCarCreateBtn"
          class="fixed bottom-16 right-4 sm:right-8 bg-green-500 hover:bg-green-600 text-white rounded-full w-14 h-14 flex items-center justify-center shadow-lg z-50"
          aria-label="<?= htmlspecialchars($langText['create_vehicle'] ?? 'Cadastrar Veículo', ENT_QUOTES) ?>">
    <svg xmlns="http://www.w3.org/2000/svg"
         class="w-8 h-8"
         fill="none"
         viewBox="0 0 24 24"
         stroke="currentColor"
         stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
    </svg>
  </button>

  <!-- Botão Histórico -->
  <button id="openUsageHistoryBtn"
          class="bg-gray-700 hover:bg-gray-800 text-white px-4 py-2 rounded-lg shadow mb-6">
    <?= htmlspecialchars($langText['vehicle_history'] ?? 'Histórico de Uso', ENT_QUOTES) ?>
  </button>

  <!-- Lista de carros -->
  <div id="carsContainer" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if (empty($cars)): ?>
      <p class="text-gray-500">
        <?= htmlspecialchars($langText['no_cars'] ?? 'Nenhum veículo cadastrado.', ENT_QUOTES) ?>
      </p>
    <?php else: foreach ($cars as $c): ?>
      <div class="car-card bg-white rounded-2xl shadow-lg p-6 transition hover:shadow-xl cursor-pointer"
           data-id="<?= (int)$c['id'] ?>">
        <h3 class="text-xl font-semibold mb-2">
          <?= htmlspecialchars("{$c['manufacturer']} {$c['model']} ({$c['year']})", ENT_QUOTES) ?>
        </h3>
        <p>
          <strong><?= htmlspecialchars($langText['plate'] ?? 'Placa', ENT_QUOTES) ?>:</strong>
          <?= htmlspecialchars($c['plate'], ENT_QUOTES) ?>
        </p>
        <p>
          <strong><?= htmlspecialchars($langText['mileage'] ?? 'Quilometragem', ENT_QUOTES) ?>:</strong>
          <?= (int)$c['mileage'] ?> km
        </p>
        <p>
          <strong><?= htmlspecialchars($langText['color'] ?? 'Cor', ENT_QUOTES) ?>:</strong>
          <?= htmlspecialchars($c['color'], ENT_QUOTES) ?>
        </p>
      </div>
    <?php endforeach; endif; ?>
  </div>
</div>

<!-- Modal: Criar Veículo -->
<div id="carCreateModal"
     class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm hidden">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4 p-6 sm:p-8 relative max-h-[90vh] overflow-y-auto">
    <button id="closeCarCreateModal"
            class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
    <h3 class="text-2xl font-bold mb-6">
      <?= htmlspecialchars($langText['create_vehicle'] ?? 'Cadastrar Veículo', ENT_QUOTES) ?>
    </h3>
    <form id="carCreateForm" action="<?= url('cars/store') ?>" method="POST" class="space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?php foreach (['manufacturer','model','year','plate','mileage','color'] as $f): ?>
          <div>
            <label class="block mb-1"><?= htmlspecialchars($langText[$f] ?? ucfirst($f), ENT_QUOTES) ?></label>
            <?php if ($f === 'year'): ?>
              <input name="year" type="number" min="1886" max="<?= date('Y') ?>" required class="w-full border rounded-lg p-3">
            <?php elseif ($f === 'mileage'): ?>
              <input name="mileage" type="number" value="0" min="0" required class="w-full border rounded-lg p-3">
            <?php else: ?>
              <input name="<?= $f ?>" type="text" required class="w-full border rounded-lg p-3">
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="text-right pt-4">
        <button type="submit"
                class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg shadow">
          <?= htmlspecialchars($langText['save'] ?? 'Salvar', ENT_QUOTES) ?>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Detalhes/Edição -->
<div id="carDetailModal"
     class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm hidden">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-4 p-6 sm:p-8 relative max-h-[90vh] overflow-y-auto">
    <button id="closeCarDetailModal"
            class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 text-2xl">&times;</button>

    <input type="hidden" id="detailCarId" value="">

    <h3 id="carDetailTitle" class="text-2xl font-bold mb-6"></h3>

    <!-- Abas -->
    <div class="flex border-b mb-6 space-x-4 overflow-x-auto">
      <button data-tab="general"
              class="tab-btn whitespace-nowrap px-4 py-2 -mb-px border-b-2 border-transparent font-medium">
        <?= htmlspecialchars($langText['general'] ?? 'Geral', ENT_QUOTES) ?>
      </button>
      <button data-tab="last_usage"
              class="tab-btn whitespace-nowrap px-4 py-2 -mb-px border-b-2 border-transparent font-medium">
        <?= htmlspecialchars($langText['last_usage'] ?? 'Último Uso', ENT_QUOTES) ?>
      </button>
    </div>

    <!-- Pane Geral -->
    <div id="pane_general" class="tab-pane space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?php foreach (['manufacturer','model','year','plate','mileage','color'] as $f): ?>
          <div>
            <label class="block mb-1 font-medium">
              <?= htmlspecialchars($langText[$f] ?? ucfirst($f), ENT_QUOTES) ?>
            </label>
            <input type="text" readonly id="detail_<?= $f ?>"
                   class="w-full border rounded-lg p-3 bg-gray-100">
          </div>
        <?php endforeach; ?>
      </div>
      <div class="flex justify-end pt-4">
        <button id="deleteCarBtn"
                class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg shadow">
          <?= htmlspecialchars($langText['delete'] ?? 'Excluir', ENT_QUOTES) ?>
        </button>
      </div>
    </div>

    <!-- Pane Último Uso -->
    <div id="pane_last_usage" class="tab-pane hidden space-y-4">
      <form id="usageForm"
            action="<?= url('cars/usage/store') ?>"
            method="POST"
            enctype="multipart/form-data"
            class="space-y-4">
        <input type="hidden" name="car_id" id="usage_car_id" value="">

        <div>
          <label class="block mb-1">
            <?= htmlspecialchars($langText['usage_datetime'] ?? 'Data e Hora', ENT_QUOTES) ?>
          </label>
          <input type="datetime-local" name="usage_datetime" required
                 class="w-full border rounded-lg p-3">
        </div>

        <div>
          <label class="block mb-1">
            <?= htmlspecialchars($langText['distance'] ?? 'Distância Percorrida', ENT_QUOTES) ?>
          </label>
          <div class="flex gap-2">
            <input type="number" name="distance" required
                   class="w-full border rounded-lg p-3">
            <select name="unit" class="border rounded-lg p-3">
              <option value="km">km</option>
              <option value="mph">mph</option>
            </select>
          </div>
        </div>

        <div id="stopsContainer" class="space-y-2">
          <div class="grid grid-cols-3 gap-2 items-end">
            <div>
              <label><?= htmlspecialchars($langText['stops'] ?? 'Litros', ENT_QUOTES) ?></label>
              <input name="stops[1]" type="number" step="0.1" min="0" required
                     class="w-full border rounded p-2">
            </div>
            <div>
              <label><?= htmlspecialchars($langText['cost'] ?? 'Custo', ENT_QUOTES) ?></label>
              <input name="costs[1]" type="number" step="0.01" min="0" required
                     class="w-full border rounded p-2">
            </div>
            <div>
              <label><?= htmlspecialchars($langText['receipt'] ?? 'Comprovante', ENT_QUOTES) ?></label>
              <input name="receipts[1]" type="file" accept="image/png,application/pdf"
                     class="w-full">
            </div>
          </div>
        </div>

        <button type="button" id="addStopBtn"
                class="px-3 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg">
          +
        </button>

        <div>
          <button type="submit"
                  class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg shadow">
            <?= htmlspecialchars($langText['save_usage'] ?? 'Registrar Uso', ENT_QUOTES) ?>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Histórico de Uso -->
<div id="usageHistoryModal"
     class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm hidden">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl mx-4 p-6 sm:p-8 relative max-h-[90vh] overflow-y-auto">
    <button id="closeUsageHistoryModal"
            class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 text-2xl">
      &times;
    </button>
    <h3 class="text-2xl font-bold mb-6">
      <?= htmlspecialchars($langText['vehicle_history'] ?? 'Histórico de Uso', ENT_QUOTES) ?>
    </h3>

    <?php if (empty($history)): ?>
      <p class="text-gray-500">
        <?= htmlspecialchars($langText['no_usage_history'] ?? 'Sem histórico de uso.', ENT_QUOTES) ?>
      </p>
    <?php else: foreach ($history as $u):
        $details = json_decode($u['stops_details_json'] ?? '[]', true);
        $dt      = new DateTime($u['datetime']);
        $fmtDt   = $dt->format('d.m.Y H:i');
    ?>
      <div class="history-item p-4 mb-4 bg-gray-50 rounded-2xl border-l-4 <?= ($u['unit']==='mph'?'border-yellow-500':'border-blue-500') ?>">
        <div class="flex justify-between items-center">
          <span class="font-medium"><?= htmlspecialchars($u['user_name'] ?? '-', ENT_QUOTES) ?></span>
          <span class="text-sm text-gray-500"><?= $fmtDt ?></span>
          <button class="toggleHistory arrow text-gray-400 hover:text-gray-600 select-none">▸</button>
        </div>
        <div class="history-details mt-2 hidden space-y-2">
          <p><strong><?= htmlspecialchars($langText['vehicle'] ?? 'Veículo', ENT_QUOTES) ?>:</strong>
             <?= htmlspecialchars("{$u['manufacturer']} {$u['model']} ({$u['plate']})", ENT_QUOTES) ?></p>
          <p><strong><?= htmlspecialchars($langText['distance'] ?? 'Distância', ENT_QUOTES) ?>:</strong>
             <?= (int)($u['distance'] ?? 0) ?> <?= htmlspecialchars($u['unit'], ENT_QUOTES) ?></p>
          <?php foreach ($details as $d): ?>
            <div class="pl-4 border-l-2 border-gray-200 mb-2">
              <p><strong><?= sprintf(htmlspecialchars($langText['stop_n'] ?? 'Parada %d', ENT_QUOTES), (int)$d['stop']) ?>:</strong>
                 <?= htmlspecialchars($d['liters'], ENT_QUOTES) ?> L — <?= htmlspecialchars(number_format($d['cost'],2,'.',''), ENT_QUOTES) ?> <?= htmlspecialchars($langText['currency'] ?? 'R$', ENT_QUOTES) ?></p>
              <?php if (!empty($d['receipt'])): ?>
                <p class="ml-4 mt-1">
                  <a href="<?= htmlspecialchars(BASE_URL . $d['receipt'], ENT_QUOTES) ?>"
                     download="<?= htmlspecialchars(basename($d['receipt']), ENT_QUOTES) ?>"
                     class="underline">
                    <?= htmlspecialchars($langText['view_receipt'] ?? 'Ver Comprovante', ENT_QUOTES) ?>
                  </a>
                </p>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>
</div>

<script src="<?= asset('js/cars.js') ?>" defer></script>
<?php require __DIR__ . '/../layout/footer.php'; ?>
