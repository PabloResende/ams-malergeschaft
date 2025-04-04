<?php 
require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../../../config/Database.php';
$pdo = Database::connect();
$baseUrl = '/ams-malergeschaft/public';

$stmt = $pdo->query("SELECT * FROM client ORDER BY name ASC");
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="ml-56 pt-20 p-4">
    <h1 class="text-2xl font-bold mb-4"><?= $langText['clients_list'] ?? 'Clients List' ?></h1>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php if (empty($clients)): ?>
            <p><?= $langText['no_clients_available'] ?? 'No clients available.' ?></p>
        <?php else: ?>
            <?php foreach ($clients as $client): ?>
                <div class="bg-white p-4 rounded-lg shadow flex flex-col">
                    <div class="flex items-center">
                        <div class="w-20 flex-shrink-0">
                            <img src="<?= !empty($client['profile_picture']) ? $baseUrl . '/uploads/' . $client['profile_picture'] : 'https://via.placeholder.com/96x128'; ?>" 
                                 alt="<?= htmlspecialchars($client['name']) ?>" 
                                 class="w-full h-auto object-cover rounded-lg">
                        </div>
                        <div class="ml-4">
                            <h2 class="text-xl font-bold"><?= htmlspecialchars($client['name']) ?></h2>
                        </div>
                    </div>
                    <!-- Botões de Ação -->
                    <div class="mt-4 flex justify-end space-x-2">
                        <!-- Botão Editar com data attributes -->
                        <button 
                            class="text-blue-500 hover:underline text-sm editClientBtn" 
                            data-id="<?= $client['id'] ?>"
                            data-name="<?= htmlspecialchars($client['name']) ?>"
                            data-address="<?= htmlspecialchars($client['address']) ?>"
                            data-about="<?= htmlspecialchars($client['about']) ?>"
                            data-phone="<?= htmlspecialchars($client['phone']) ?>"
                        >
                            <?= $langText['edit'] ?? 'Edit' ?>
                        </button>
                        <!-- Botão Excluir -->
                        <a href="<?= $baseUrl ?>/clients/delete?id=<?= $client['id'] ?>" class="text-red-500 hover:underline text-sm" onclick="return confirm('Are you sure you want to delete this client?');">
                            <?= $langText['delete'] ?? 'Delete' ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Botão Flutuante para Abrir o Modal de Criação de Funcionário -->
<button id="addClientBtn" class="fixed bottom-8 right-8 bg-green-500 text-white rounded-full p-4 shadow-lg hover:bg-green-600">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
    </svg>
</button>

<!-- Modal para Cadastrar Funcionário -->
<div id="clientModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white rounded-md p-8 w-90 max-h-[80vh] overflow-y-auto mt-10">
        <h2 class="text-2xl font-bold mb-4"><?= $langText['create_client'] ?? 'Create Client' ?></h2>
        <form action="<?= $baseUrl ?>/clients/store" method="POST" enctype="multipart/form-data" class="space-y-4">
            <div>
                <label class="block mb-2 font-medium"><?= $langText['name'] ?? 'Name' ?></label>
                <input type="text" name="name" required class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
            </div>
            <div>
                <label class="block mb-2 font-medium"><?= $langText['address'] ?? 'Address' ?></label>
                <input type="text" name="address" class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
            </div>
            <div>
                <label class="block mb-2 font-medium"><?= $langText['about_me'] ?? 'About Me' ?></label>
                <textarea name="about" class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300"></textarea>
            </div>
            <div>
                <label class="block mb-2 font-medium"><?= $langText['phone'] ?? 'Phone' ?></label>
                <input type="text" name="phone" class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
            </div>
            <div>
                <label class="block mb-2 font-medium"><?= $langText['profile_picture'] ?? 'Profile Picture' ?></label>
                <input type="file" name="profile_picture" class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
            </div>
            <div class="flex justify-end">
                <button type="button" id="closeClientModal" class="mr-2 px-4 py-2 border rounded"><?= $langText['cancel'] ?? 'Cancel' ?></button>
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded"><?= $langText['submit'] ?? 'Submit' ?></button>
            </div>
        </form>
    </div>
</div>


<!-- Modal de Edição de Funcionário -->
<div id="clientEditModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white rounded-md p-8 w-90 max-h-[80vh] overflow-y-auto mt-10">
        <h2 class="text-2xl font-bold mb-4"><?= $langText['edit_client'] ?? 'Edit Client' ?></h2>
        <form id="clientEditForm" action="<?= $baseUrl ?>/clients/update" method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="id" id="editClientId">
            <div>
                <label class="block mb-2 font-medium"><?= $langText['name'] ?? 'Name' ?></label>
                <input type="text" name="name" id="editClientName" required class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
            </div>
            <div>
                <label class="block mb-2 font-medium"><?= $langText['role'] ?? 'Role' ?></label>
                <input type="text" name="role" id="editClientRole" required class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
            </div>
            <div>
                <label class="block mb-2 font-medium"><?= $langText['birth_date'] ?? 'Birth Date' ?></label>
                <input type="date" name="birth_date" id="editClientBirthDate" required class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
            </div>
            <div>
                <label class="block mb-2 font-medium"><?= $langText['start_date'] ?? 'Start Date' ?></label>
                <input type="date" name="start_date" id="editClientStartDate" required class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
            </div>
            <div>
                <label class="block mb-2 font-medium"><?= $langText['address'] ?? 'Address' ?></label>
                <input type="text" name="address" id="editClientAddress" class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
            </div>
            <div>
                <label class="block mb-2 font-medium"><?= $langText['about_me'] ?? 'About Me' ?></label>
                <textarea name="about" id="editClientAbout" class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300"></textarea>
            </div>
            <div>
                <label class="block mb-2 font-medium"><?= $langText['phone'] ?? 'Phone' ?></label>
                <input type="text" name="phone" id="editClientPhone" class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
            </div>
            <div>
                <label class="block mb-2 font-medium"><?= $langText['status'] ?? 'Status' ?></label>
                <label class="inline-flex items-center">
                    <input type="checkbox" name="active" id="editClientActive" class="form-checkbox" checked>
                    <span class="ml-2"><?= $langText['active'] ?? 'Active' ?></span>
                </label>
            </div>
            <div class="flex justify-end">
                <button type="button" id="closeClientEditModal" class="mr-2 px-4 py-2 border rounded"><?= $langText['cancel'] ?? 'Cancel' ?></button>
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded"><?= $langText['submit'] ?? 'Submit' ?></button>
            </div>
        </form>
    </div>
</div>

<script src="<?= $baseUrl ?>/js/clients.js"></script>


