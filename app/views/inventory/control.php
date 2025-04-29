<?php
// app/views/inventory/control.php
require_once __DIR__ . '/../layout/header.php';
$baseUrl = '/ams-malergeschaft/public';
?>
<div class="ml-56 pt-20 p-8">
  <h2 class="text-2xl font-bold mb-4">Controle de Estoque</h2>
  <form id="controlForm" action="<?= $baseUrl ?>/inventory/control/store" method="POST">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
      <!-- Nome -->
      <div>
        <label class="block text-gray-700">Seu nome</label>
        <input type="text" name="user_name" class="w-full p-2 border rounded" required>
      </div>
      <!-- Data/Hora -->
      <div>
        <label class="block text-gray-700">Data e hora</label>
        <input type="text" name="datetime" value="<?= date('Y-m-d H:i:s') ?>"
               class="w-full p-2 border rounded bg-gray-100" readonly>
      </div>
      <!-- Motivo -->
      <div>
        <label class="block text-gray-700">Motivo</label>
        <select name="reason" id="reasonSelect" class="w-full p-2 border rounded">
          <option value="projeto">Projeto</option>
          <option value="perda">Perda</option>
          <option value="outros">Outros</option>
        </select>
      </div>
      <!-- Outros (custom) -->
      <div id="customReasonDiv" class="hidden">
        <label class="block text-gray-700">Descreva o motivo</label>
        <input type="text" name="custom_reason" class="w-full p-2 border rounded">
      </div>
      <!-- Projeto -->
      <div id="projectSelectDiv" class="hidden">
        <label class="block text-gray-700">Projeto</label>
        <select name="project_id" class="w-full p-2 border rounded">
          <option value="">-- Selecione --</option>
          <?php foreach ($activeProjects as $p): ?>
            <option value="<?= $p['id'] ?>">
              <?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <h3 class="font-semibold mb-2">Itens em Estoque</h3>
    <div class="mb-6">
      <?php if (empty($items)): ?>
        <p>Nenhum item disponível.</p>
      <?php else: ?>
        <?php foreach ($items as $it): ?>
          <?php if ($it['quantity'] <= 0) continue; ?>
          <div class="flex items-center mb-2">
            <input type="checkbox"
                   class="mr-2 item-checkbox"
                   data-max="<?= $it['quantity'] ?>"
                   value="<?= $it['id'] ?>">
            <span class="flex-1">
              <?= htmlspecialchars($it['name'], ENT_QUOTES, 'UTF-8') ?>
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

    <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded">
      Registrar Movimento
    </button>
  </form>
</div>

<script src="<?= $baseUrl ?>/js/inventory_control.js"></script>
