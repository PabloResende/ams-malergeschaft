<?php
// app/views/clients/index.php

require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../../../config/Database.php';

$pdo     = Database::connect();
$clients = $pdo->query("SELECT * FROM client ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Base URL usada no JS
$baseUrl = '/ams-malergeschaft/public';
?>

<div class="ml-56 pt-20 p-4">
  <h1 class="text-2xl font-bold mb-4">
    <?= $langText['clients_list'] ?? 'Clients List' ?>
  </h1>

  <!-- Grid de cards -->
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
                  : 'https://via.placeholder.com/96x128' ?>"
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

  <!-- Botão para criar novo -->
  <button
    id="addClientBtn"
    class="fixed bottom-8 right-8 bg-green-500 text-white rounded-full p-4 shadow-lg hover:bg-green-600"
    aria-label="Criar novo cliente"
  >
    <svg class="w-6 h-6" viewBox="0 0 24 24"><path stroke="currentColor" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
  </button>

  <!-- Modal de Criação -->
  <div
    id="clientModal"
    class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden"
    aria-modal="true" role="dialog"
  >
    <div class="bg-white rounded-md p-8 w-90 max-h-[80vh] overflow-y-auto mt-10 relative">
      <button
        type="button"
        id="closeCreateModal"
        class="absolute top-4 right-4 text-gray-700 text-2xl"
        aria-label="Fechar criação"
      >&times;</button>

      <h2 class="text-2xl font-bold mb-4"><?= $langText['create_client'] ?? 'Create Client' ?></h2>
      <form action="<?= $baseUrl ?>/clients/store" method="POST" enctype="multipart/form-data" class="space-y-4">
        <!-- campos do formulário -->
        <div>
          <label for="clientName" class="block mb-2"><?= $langText['name'] ?? 'Name' ?></label>
          <input id="clientName" name="name" type="text" required class="w-full border p-2 rounded focus:outline-none focus:ring"/>
        </div>
        <div>
          <label for="clientAddress" class="block mb-2"><?= $langText['address'] ?? 'Address' ?></label>
          <input id="clientAddress" name="address" type="text" class="w-full border p-2 rounded focus:outline-none focus:ring"/>
        </div>
        <div>
          <label for="clientAbout" class="block mb-2"><?= $langText['about_me'] ?? 'About Me' ?></label>
          <textarea id="clientAbout" name="about" rows="4" class="w-full border p-2 rounded focus:outline-none focus:ring"></textarea>
        </div>
        <div>
          <label for="clientPhone" class="block mb-2"><?= $langText['phone'] ?? 'Phone' ?></label>
          <input id="clientPhone" name="phone" type="text" class="w-full border p-2 rounded focus:outline-none focus:ring"/>
        </div>
        <div>
          <label for="clientProfilePicture" class="block mb-2"><?= $langText['profile_picture'] ?? 'Profile Picture' ?></label>
          <input id="clientProfilePicture" name="profile_picture" type="file" accept="image/*" class="w-full border p-2 rounded focus:outline-none focus:ring"/>
        </div>
        <div class="flex justify-end">
          <button type="button" id="cancelCreate" class="mr-2 px-4 py-2 border rounded hover:bg-gray-100">
            <?= $langText['cancel'] ?? 'Cancel' ?>
          </button>
          <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
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
    aria-modal="true" role="dialog"
  >
    <div class="bg-white rounded-md p-8 w-90 max-h-[80vh] overflow-y-auto mt-10 relative">
      <button
        type="button"
        id="closeDetailsX"
        class="absolute top-4 right-4 text-gray-700 text-2xl"
        aria-label="Fechar detalhes"
      >&times;</button>

      <h2 class="text-2xl font-bold mb-4"><?= $langText['client_details'] ?? 'Client Details' ?></h2>

      <p class="mb-4"><strong><?= $langText['name'] ?? 'Name' ?>:</strong> <span id="detailsClientName">—</span></p>
      <p class="mb-4"><strong><?= $langText['address'] ?? 'Address' ?>:</strong> <span id="detailsClientAddress">—</span></p>
      <p class="mb-4"><strong><?= $langText['about_me'] ?? 'About Me' ?>:</strong> <span id="detailsClientAbout">—</span></p>
      <p class="mb-4"><strong><?= $langText['phone'] ?? 'Phone' ?>:</strong> <span id="detailsClientPhone">—</span></p>
      <p class="mb-4"><strong><?= $langText['loyalty_points'] ?? 'Loyalty Points' ?>:</strong> <span id="detailsClientLoyalty">0</span></p>
      <p class="mb-6"><strong><?= $langText['projects_done'] ?? 'Projects Done' ?>:</strong> <span id="detailsClientProjects">0</span></p>

      <div class="flex justify-end">
        <button id="closeDetailsBtn" class="px-4 py-2 border rounded hover:bg-gray-100">
          <?= $langText['close'] ?? 'Close' ?>
        </button>
      </div>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const baseUrl = '<?= $baseUrl ?>';
    console.log('→ clientes.js carregado, baseUrl=', baseUrl);

    // criação
    document.getElementById('addClientBtn').addEventListener('click', () => {
      document.getElementById('clientModal').classList.remove('hidden');
    });
    ['closeCreateModal','cancelCreate'].forEach(id =>
      document.getElementById(id).addEventListener('click', () => {
        document.getElementById('clientModal').classList.add('hidden');
      })
    );

    // detalhes
    const detailsModal = document.getElementById('clientDetailsModal');
    ['closeDetailsX','closeDetailsBtn'].forEach(id =>
      document.getElementById(id).addEventListener('click', () => {
        detailsModal.classList.add('hidden');
      })
    );

    document.querySelectorAll('.client-item').forEach(item => {
      item.addEventListener('click', () => {
        const id = item.dataset.id;
        console.log('→ clicou em client-item id=', id);
        fetch(`${baseUrl}/clients/show?id=${encodeURIComponent(id)}`, {
          credentials: 'same-origin'
        })
        .then(res => {
          console.log('→ status', res.status);
          if (!res.ok) throw new Error(res.statusText);
          return res.json();
        })
        .then(data => {
          console.log('→ data', data);
          document.getElementById('detailsClientName').textContent    = data.name;
          document.getElementById('detailsClientAddress').textContent = data.address || '—';
          document.getElementById('detailsClientAbout').textContent   = data.about   || '—';
          document.getElementById('detailsClientPhone').textContent   = data.phone   || '—';
          document.getElementById('detailsClientLoyalty').textContent = data.loyalty_points;
          document.getElementById('detailsClientProjects').textContent= data.project_count;
          detailsModal.classList.remove('hidden');
        })
        .catch(err => {
          console.error('→ erro ao carregar detalhes:', err);
          alert('Não foi possível carregar detalhes do cliente:\n' + err.message);
        });
      });
    });
  });
</script>
