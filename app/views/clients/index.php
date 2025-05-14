<?php
// app/views/clients/index.php

require_once __DIR__ . '/../layout/header.php';

$pdo     = Database::connect();
$clients = $pdo->query("SELECT * FROM client ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$baseUrl = '/ams-malergeschaft/public';
?>
<script>
  window.baseUrl  = '<?= $baseUrl ?>';
  window.langText = <?= json_encode($langText, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_QUOT|JSON_HEX_APOS) ?>;
</script>

<div class="ml-56 pt-20 p-4">
  <h1 class="text-3xl font-extrabold mb-6">
    <?= $langText['clients_list'] ?? 'Lista de Clientes' ?>
  </h1>

  <!-- Grid de clientes -->
  <div id="clientsContainer" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if (empty($clients)): ?>
      <p class="text-gray-500"><?= $langText['no_clients_available'] ?? 'Nenhum cliente disponível.' ?></p>
    <?php else: foreach ($clients as $c): ?>
      <div
        class="client-item bg-white rounded-lg overflow-hidden shadow hover:shadow-xl transition-shadow cursor-pointer p-4"
        data-id="<?= $c['id'] ?>"
      >
        <h2 class="text-xl font-semibold mb-1"><?= htmlspecialchars($c['name'], ENT_QUOTES) ?></h2>
        <p class="text-sm text-gray-600">
          <?= $langText['address'] ?? 'Endereço' ?>:
          <span class="font-medium"><?= htmlspecialchars($c['address'], ENT_QUOTES) ?></span>
        </p>
        <p class="text-sm text-gray-600 mt-2">
          <?= $langText['phone'] ?? 'Telefone' ?>:
          <span class="font-medium"><?= htmlspecialchars($c['phone'], ENT_QUOTES) ?></span>
        </p>

        <?php /* 
        <!-- coração do sistema de fidelidade comentado -->
        <p class="text-sm text-gray-600 mt-2">
          <?= $langText['loyalty_points'] ?? 'Pontos de Fidelidade' ?>:
          <span class="font-medium"><?= (int)$c['loyalty_points'] ?></span>
        </p>
        */ ?>
      </div>
    <?php endforeach; endif; ?>
  </div>

  <!-- Botão flutuante de adicionar -->
  <button
    id="addClientBtn"
    class="fixed bottom-8 right-8 bg-green-500 hover:bg-green-600 text-white rounded-full p-4 shadow-lg focus:outline-none"
    aria-label="Add client"
  >
    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
      <path d="M12 4v16m8-8H4" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
  </button>
</div>

<!-- Modal de Criação -->
<div id="createModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
  <div id="createModalContent" class="bg-white rounded-lg shadow-xl w-full max-w-md p-6 relative">
    <button type="button" id="closeCreateModal"
            class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
    <h2 class="text-2xl font-bold mb-4"><?= $langText['create_client'] ?? 'Criar Cliente' ?></h2>
    <form action="<?= $baseUrl ?>/clients/store" method="POST" class="space-y-4">
      <div>
        <label for="newName" class="block mb-1 font-medium"><?= $langText['name'] ?? 'Nome' ?></label>
        <input id="newName" name="name" type="text" required class="w-full border rounded px-3 py-2"/>
      </div>
      <div>
        <label for="newAddress" class="block mb-1 font-medium"><?= $langText['address'] ?? 'Endereço' ?></label>
        <input id="newAddress" name="address" type="text" class="w-full border rounded px-3 py-2"/>
      </div>
      <div>
        <label for="newPhone" class="block mb-1 font-medium"><?= $langText['phone'] ?? 'Telefone' ?></label>
        <input id="newPhone" name="phone" type="text" class="w-full border rounded px-3 py-2"/>
      </div>
      <div class="flex justify-end space-x-2 pt-4">
        <button type="button" id="cancelCreate" class="px-4 py-2 border rounded">Cancelar</button>
        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">Enviar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal de Detalhes / Transações -->
<div id="detailsModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
  <div class="bg-white rounded-lg shadow-xl w-full max-w-lg mx-4 overflow-hidden p-6 relative">
    <button type="button" id="closeDetailsModal"
            class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 text-2xl">&times;</button>

    <h2 class="text-2xl font-bold mb-4"><?= $langText['client_details'] ?? 'Detalhes do Cliente' ?></h2>

    <!-- Abas -->
    <div class="flex border-b mb-4">
      <button id="tabInfoBtn" class="flex-1 px-4 py-2 text-center border-b-2 border-blue-600 font-medium">
        <?= $langText['details'] ?? 'Detalhes' ?>
      </button>
      <button id="tabTransBtn" class="flex-1 px-4 py-2 text-center text-gray-500 hover:text-gray-700">
        <?= $langText['transactions'] ?? 'Transações' ?>
      </button>
    </div>

    <!-- Detalhes -->
    <div id="infoPane" class="space-y-4">
      <form id="detailsForm" action="<?= $baseUrl ?>/clients/update" method="POST" class="space-y-4">
        <input type="hidden" name="id" id="detailId" value="">
        <div>
          <label class="block mb-1 font-medium"><?= $langText['name'] ?? 'Nome' ?></label>
          <input name="name" id="detailName" type="text" required class="w-full border rounded px-3 py-2"/>
        </div>
        <div>
          <label class="block mb-1 font-medium"><?= $langText['address'] ?? 'Endereço' ?></label>
          <input name="address" id="detailAddress" type="text" class="w-full border rounded px-3 py-2"/>
        </div>
        <div>
          <label class="block mb-1 font-medium"><?= $langText['phone'] ?? 'Telefone' ?></label>
          <input name="phone" id="detailPhone" type="text" class="w-full border rounded px-3 py-2"/>
        </div>
        <div class="text-sm text-gray-600">
          <?php /*
          <?= $langText['loyalty_points'] ?? 'Pontos de Fidelidade' ?>:
          <span id="detailLoyalty">0</span><br>
          */ ?>
          <?= $langText['projects_done'] ?? 'Projetos Concluídos' ?>:
          <span id="detailProjects">0</span>
        </div>
        <div class="flex justify-end space-x-2">
          <button type="button" id="cancelDetails" class="px-4 py-2 border rounded">Cancelar</button>
          <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">Salvar</button>
        </div>
      </form>
    </div>

    <!-- Transações -->
    <div id="transPane" class="hidden">
      <h3 class="text-lg font-semibold mb-2"><?= $langText['client_transactions'] ?? 'Transações do Cliente' ?></h3>
      <div class="overflow-x-auto">
        <table class="w-full bg-white rounded shadow">
          <thead class="bg-gray-100">
            <tr>
              <th class="p-2 text-left"><?= $langText['date'] ?? 'Data' ?></th>
              <th class="p-2 text-left"><?= $langText['type'] ?? 'Tipo' ?></th>
              <th class="p-2 text-right"><?= $langText['amount'] ?? 'Valor' ?></th>
            </tr>
          </thead>
          <tbody id="transTableBody">
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script src="<?= $baseUrl ?>/js/clients.js?v=<?= time() ?>" defer></script>
