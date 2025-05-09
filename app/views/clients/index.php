<?php
// app/views/clients/index.php

require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../../../config/Database.php';

$pdo     = Database::connect();
$clients = $pdo->query("SELECT * FROM client ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$baseUrl = '/ams-malergeschaft/public';
?>
<div class="ml-56 pt-20 p-4">
  <h1 class="text-3xl font-extrabold mb-6">
    <?= $langText['clients_list'] ?? 'Clients List' ?>
  </h1>

  <!-- Grid de clientes -->
  <div id="clientsContainer" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if (empty($clients)): ?>
      <p class="text-gray-500"><?= $langText['no_clients_available'] ?? 'No clients available.' ?></p>
    <?php else: ?>
      <?php foreach ($clients as $c): ?>
        <div
          class="client-item bg-white rounded-lg overflow-hidden shadow hover:shadow-xl transition-shadow cursor-pointer"
          data-id="<?= $c['id'] ?>"
        >
          <img
            src="<?= !empty($c['profile_picture'])
              ? $baseUrl . '/uploads/' . $c['profile_picture']
              : 'https://via.placeholder.com/400x240' ?>"
            alt="<?= htmlspecialchars($c['name'], ENT_QUOTES) ?>"
            class="w-full h-40 object-cover"
          >
          <div class="p-4">
            <h2 class="text-xl font-semibold mb-1"><?= htmlspecialchars($c['name'], ENT_QUOTES) ?></h2>
            <p class="text-sm text-gray-600">
              <?= $langText['loyalty_points'] ?? 'Loyalty Points' ?>:
              <span class="font-medium"><?= (int)$c['loyalty_points'] ?></span>
            </p>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
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
<div
  id="createModal"
  class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden"
  role="dialog" aria-modal="true"
>
  <div id="createModalContent"
       class="bg-white rounded-lg shadow-xl w-full max-w-md p-6 relative">
    <button type="button" id="closeCreateModal"
            class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 text-2xl focus:outline-none"
            aria-label="Close">&times;</button>
    <h2 class="text-2xl font-bold mb-4"><?= $langText['create_client'] ?? 'Create Client' ?></h2>
    <form action="<?= $baseUrl ?>/index.php/clients/store" method="POST" enctype="multipart/form-data" class="space-y-4">
      <div>
        <label for="newName" class="block mb-1 font-medium"><?= $langText['name'] ?? 'Name' ?></label>
        <input id="newName" name="name" type="text" required
               class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300"/>
      </div>
      <div>
        <label for="newAddress" class="block mb-1 font-medium"><?= $langText['address'] ?? 'Address' ?></label>
        <input id="newAddress" name="address" type="text"
               class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300"/>
      </div>
      <div>
        <label for="newAbout" class="block mb-1 font-medium"><?= $langText['about_me'] ?? 'About Me' ?></label>
        <textarea id="newAbout" name="about" rows="3"
                  class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300"></textarea>
      </div>
      <div>
        <label for="newPhone" class="block mb-1 font-medium"><?= $langText['phone'] ?? 'Phone' ?></label>
        <input id="newPhone" name="phone" type="text"
               class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300"/>
      </div>
      <div>
        <label for="newProfilePicture" class="block mb-1 font-medium"><?= $langText['profile_picture'] ?? 'Profile Picture' ?></label>
        <input id="newProfilePicture" name="profile_picture" type="file" accept="image/*"
               class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300"/>
      </div>
      <div class="flex justify-end space-x-2 pt-4">
        <button type="button" id="cancelCreate"
                class="px-4 py-2 border rounded hover:bg-gray-100 focus:outline-none">
          <?= $langText['cancel'] ?? 'Cancel' ?>
        </button>
        <button type="submit"
                class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded focus:outline-none">
          <?= $langText['submit'] ?? 'Submit' ?>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal de Detalhes / Edição -->
<div
  id="detailsModal"
  class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden"
  role="dialog" aria-modal="true"
>
  <div id="detailsModalContent"
       class="bg-white rounded-lg shadow-xl w-full max-w-md p-6 relative">
    <button type="button" id="closeDetailsModal"
            class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 text-2xl focus:outline-none"
            aria-label="Close">&times;</button>
    <h2 class="text-2xl font-bold mb-4"><?= $langText['client_details'] ?? 'Client Details' ?></h2>
    <form id="detailsForm" action="<?= $baseUrl ?>/index.php/clients/update" method="POST" class="space-y-4">
      <input type="hidden" name="id" id="detailId" value="">

      <div>
        <label for="detailName" class="block mb-1 font-medium"><?= $langText['name'] ?? 'Name' ?></label>
        <input id="detailName" name="name" type="text" required
               class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300"/>
      </div>
      <div>
        <label for="detailAddress" class="block mb-1 font-medium"><?= $langText['address'] ?? 'Address' ?></label>
        <input id="detailAddress" name="address" type="text"
               class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300"/>
      </div>
      <div>
        <label for="detailAbout" class="block mb-1 font-medium"><?= $langText['about_me'] ?? 'About Me' ?></label>
        <textarea id="detailAbout" name="about" rows="3"
                  class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300"></textarea>
      </div>
      <div>
        <label for="detailPhone" class="block mb-1 font-medium"><?= $langText['phone'] ?? 'Phone' ?></label>
        <input id="detailPhone" name="phone" type="text"
               class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300"/>
      </div>

      <div class="flex justify-between items-center pt-4">
        <div class="text-sm text-gray-600">
          <?= $langText['loyalty_points'] ?? 'Loyalty Points' ?>:
          <span id="detailLoyalty">0</span><br>
          <?= $langText['projects_done'] ?? 'Projects Done' ?>:
          <span id="detailProjects">0</span>
        </div>
        <div class="space-x-2">
          <a href="#" id="deleteClientLink"
             class="px-4 py-2 border rounded text-red-600 hover:bg-red-100 focus:outline-none">
            <?= $langText['delete'] ?? 'Delete' ?>
          </a>
          <button type="button" id="cancelDetails"
                  class="px-4 py-2 border rounded hover:bg-gray-100 focus:outline-none">
            <?= $langText['cancel'] ?? 'Cancel' ?>
          </button>
          <button type="submit"
                  class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded focus:outline-none">
            <?= $langText['save_changes'] ?? 'Save Changes' ?>
          </button>
        </div>
      </div>
    </form>

    <!-- Form para exclusão -->
    <form id="deleteForm" action="<?= $baseUrl ?>/clients/delete" method="POST" class="hidden">
      <input type="hidden" name="id" id="deleteIdField" value="">
    </form>
  </div>
</div>

<!-- scripts -->
<script>
  window.baseUrl         = '<?= $baseUrl ?>';
  window.confirmDeleteMsg = '<?= addslashes($langText['confirm_delete'] ?? 'Are you sure you want to delete this client?') ?>';
</script>
<script src="<?= $baseUrl ?>/js/clients.js?v=<?= time() ?>" defer></script>

