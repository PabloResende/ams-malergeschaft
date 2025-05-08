<?php
// app/views/clients/index.php

require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../../../config/Database.php';

$pdo     = Database::connect();
$clients = $pdo->query("SELECT * FROM client ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$baseUrl = '/ams-malergeschaft/public';
?>
<div class="ml-56 pt-20 p-4">
  <h1 class="text-2xl font-bold mb-4">
    <?= $langText['clients_list'] ?? 'Clients List' ?>
  </h1>

  <!-- Container com ID para delegação de eventos -->
  <div id="clientsContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    <?php if (empty($clients)): ?>
      <p><?= $langText['no_clients_available'] ?? 'No clients available.' ?></p>
    <?php else: ?>
      <?php foreach ($clients as $c): ?>
        <div
          class="client-item bg-white p-4 rounded-lg shadow flex flex-col cursor-pointer"
          data-id="<?= $c['id'] ?>"
        >
          <div class="flex items-center">
            <div class="w-20 flex-shrink-0">
              <img
                src="<?= !empty($c['profile_picture'])
                  ? $baseUrl . '/uploads/' . $c['profile_picture']
                  : 'https://via.placeholder.com/96x128'; ?>"
                alt="<?= htmlspecialchars($c['name'], ENT_QUOTES) ?>"
                class="w-full h-auto object-cover rounded-lg"
              >
            </div>
            <div class="ml-4 flex-1">
              <h2 class="text-xl font-bold"><?= htmlspecialchars($c['name'], ENT_QUOTES) ?></h2>
              <p class="text-sm text-gray-600">
                <?= $langText['loyalty_points'] ?? 'Loyalty Points' ?>:
                <span class="font-semibold"><?= (int)$c['loyalty_points'] ?></span>
              </p>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Botão flutuante para criar novo client -->
  <button
    id="addClientBtn"
    class="fixed bottom-8 right-8 bg-green-500 text-white rounded-full p-4 shadow-lg hover:bg-green-600"
  >
    <svg class="w-6 h-6" viewBox="0 0 24 24">
      <path stroke="currentColor" stroke-width="2" d="M12 4v16m8-8H4"/>
    </svg>
  </button>

  <!-- Modal de Criação -->
  <div
    id="clientModal"
    class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden"
  >
    <div class="bg-white rounded-md p-8 w-90 max-h-[80vh] overflow-y-auto mt-10">
      <h2 class="text-2xl font-bold mb-4">
        <?= $langText['create_client'] ?? 'Create Client' ?>
      </h2>
      <form
        action="<?= $baseUrl ?>/clients/store"
        method="POST"
        enctype="multipart/form-data"
        class="space-y-4"
      >
        <div>
          <label class="block mb-2"><?= $langText['name'] ?? 'Name' ?></label>
          <input
            type="text" name="name" required
            class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300"
          >
        </div>
        <div>
          <label class="block mb-2"><?= $langText['address'] ?? 'Address' ?></label>
          <input
            type="text" name="address"
            class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300"
          >
        </div>
        <div>
          <label class="block mb-2"><?= $langText['about_me'] ?? 'About Me' ?></label>
          <textarea
            name="about"
            class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300"
          ></textarea>
        </div>
        <div>
          <label class="block mb-2"><?= $langText['phone'] ?? 'Phone' ?></label>
          <input
            type="text" name="phone"
            class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300"
          >
        </div>
        <div>
          <label class="block mb-2"><?= $langText['profile_picture'] ?? 'Profile Picture' ?></label>
          <input
            type="file" name="profile_picture"
            class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300"
          >
        </div>
        <div class="flex justify-end">
          <button
            type="button" id="closeClientModal"
            class="mr-2 px-4 py-2 border rounded"
          >
            <?= $langText['cancel'] ?? 'Cancel' ?>
          </button>
          <button
            type="submit"
            class="bg-blue-500 text-white px-4 py-2 rounded"
          >
            <?= $langText['submit'] ?? 'Submit' ?>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal de Detalhes -->
  <div
    id="clientDetailsModal"
    class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden"
  >
    <div class="bg-white rounded-md p-8 w-90 max-h-[80vh] overflow-y-auto mt-10 relative">
      <button
        type="button" id="closeClientDetailsModal"
        class="absolute top-4 right-4 text-gray-700 text-2xl"
      >&times;</button>

      <h2 class="text-2xl font-bold mb-2">
        <?= $langText['client_details'] ?? 'Client Details' ?>
      </h2>

      <p class="mb-4">
        <span class="font-semibold"><?= $langText['name'] ?? 'Name' ?>:</span>
        <span id="detailsClientName">—</span>
      </p>
      <p class="mb-4">
        <span class="font-semibold"><?= $langText['address'] ?? 'Address' ?>:</span>
        <span id="detailsClientAddress">—</span>
      </p>
      <p class="mb-4">
        <span class="font-semibold"><?= $langText['about_me'] ?? 'About Me' ?>:</span>
        <span id="detailsClientAbout">—</span>
      </p>
      <p class="mb-4">
        <span class="font-semibold"><?= $langText['phone'] ?? 'Phone' ?>:</span>
        <span id="detailsClientPhone">—</span>
      </p>
      <p class="mb-4">
        <span class="font-semibold"><?= $langText['loyalty_points'] ?? 'Loyalty Points' ?>:</span>
        <span id="detailsClientLoyalty">0</span>
      </p>
      <p class="mb-6">
        <span class="font-semibold"><?= $langText['projects_done'] ?? 'Projects Done' ?>:</span>
        <span id="detailsClientProjects">0</span>
      </p>

      <div class="flex justify-end">
        <button
          id="closeClientDetailsBtn"
          class="px-4 py-2 border rounded"
        >
          <?= $langText['close'] ?? 'Close' ?>
        </button>
      </div>
    </div>
  </div>

  <!-- Script external -->
  <script defer src="<?= $baseUrl ?>/js/clients.js"></script>
</div>
