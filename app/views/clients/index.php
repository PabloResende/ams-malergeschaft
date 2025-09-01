<?php
// app/views/clients/index.php

require_once __DIR__ . '/../layout/header.php';

// Busca lista de clientes
$clients = $pdo
    ->query("SELECT * FROM client ORDER BY name ASC")
    ->fetchAll(PDO::FETCH_ASSOC);
?>
<script>
  window.baseUrl  = <?= json_encode(BASE_URL, JSON_UNESCAPED_SLASHES) ?>;
  window.langText = <?= json_encode($langText, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_QUOT|JSON_HEX_APOS) ?>;
</script>

<!-- Toast container -->
<div id="toastContainer" class="fixed top-4 right-4 space-y-2 z-50"></div>

<div class="pt-20 p-4 ml-0 lg:ml-56">
  <h1 class="text-3xl font-bold mb-6">
    <?= htmlspecialchars($langText['clients_list']   ?? 'Lista de Clientes', ENT_QUOTES) ?>
  </h1>

  <div id="clientsContainer" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if (empty($clients)): ?>
      <p class="text-gray-500">
        <?= htmlspecialchars($langText['no_clients'] ?? 'Nenhum cliente disponível.', ENT_QUOTES) ?>
      </p>
    <?php else: foreach ($clients as $c): ?>
      <div
        class="client-item bg-white rounded-lg shadow hover:shadow-xl p-4 cursor-pointer"
        data-id="<?= (int)$c['id'] ?>"
      >
        <div class="flex justify-between items-center mb-2">
          <h2 class="text-xl font-semibold"><?= htmlspecialchars($c['name'], ENT_QUOTES) ?></h2>
          <span class="text-sm font-medium text-green-600">
            <?= htmlspecialchars($langText['loyalty_points'] ?? 'Pontos de Fidelidade', ENT_QUOTES) ?>:
            <?= (int)$c['loyalty_points'] ?>
          </span>
        </div>
        <p class="text-sm text-gray-600">
          <?= htmlspecialchars($langText['address'] ?? 'Endereço', ENT_QUOTES) ?>:
          <span class="font-medium"><?= htmlspecialchars($c['address'], ENT_QUOTES) ?></span>
        </p>
        <p class="text-sm text-gray-600 mt-2">
          <?= htmlspecialchars($langText['phone'] ?? 'Telefone', ENT_QUOTES) ?>:
          <span class="font-medium"><?= htmlspecialchars($c['phone'], ENT_QUOTES) ?></span>
        </p>
      </div>
    <?php endforeach; endif; ?>
  </div>

  <button
    id="addClientBtn"
    class="fixed bottom-16 right-8 bg-green-500 hover:bg-green-600 text-white rounded-full p-4 shadow-lg"
    aria-label="<?= htmlspecialchars($langText['create_client'] ?? 'Adicionar Cliente', ENT_QUOTES) ?>"
  >
    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
      <path d="M12 4v16m8-8H4" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
  </button>
</div>

<div id="clientModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm hidden z-50">
  <div class="bg-white rounded-2xl p-6 lg:p-10 w-full sm:w-11/12 max-w-5xl max-h-[90vh] overflow-y-auto relative mx-4">
    <button type="button" id="closeClientModal"
            class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 text-2xl">&times;</button>

    <h2 id="modalTitle" class="text-2xl font-bold mb-4">
      <?= htmlspecialchars($langText['create_client'] ?? 'Criar Cliente', ENT_QUOTES) ?>
    </h2>

    <div class="flex border-b mb-6 overflow-x-auto">
      <button id="tabStammdaten" class="px-4 py-2 whitespace-nowrap -mb-px border-b-2 border-transparent font-medium">
        <?= htmlspecialchars($langText['stammdaten'] ?? 'Dados Principais', ENT_QUOTES) ?>
      </button>
      <button id="tabZusatzinfo" class="px-4 py-2 whitespace-nowrap -mb-px border-b-2 border-transparent font-medium">
        <?= htmlspecialchars($langText['zusatzinfo'] ?? 'Informações Adicionais', ENT_QUOTES) ?>
      </button>
      <button id="tabKommunikation" class="px-4 py-2 whitespace-nowrap -mb-px border-b-2 border-transparent font-medium">
        <?= htmlspecialchars($langText['kommunikation'] ?? 'Comunicação', ENT_QUOTES) ?>
      </button>
      <button id="tabWeitereKontakt" class="px-4 py-2 whitespace-nowrap -mb-px border-b-2 border-transparent font-medium">
        <?= htmlspecialchars($langText['weitere_kontakt'] ?? 'Outras Informações', ENT_QUOTES) ?>
      </button>
    </div>

    <button
      type="button"
      id="deleteClientBtn"
      data-url="<?= url('clients/delete') ?>"
      class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded hidden mb-4"
    >
      <?= htmlspecialchars($langText['delete'] ?? 'Excluir', ENT_QUOTES) ?>
    </button>

    <form id="clientForm" action="<?= url('clients/save') ?>" method="POST"
          enctype="multipart/form-data" class="space-y-4">
      <input type="hidden" name="id" id="clientId" value="">

      <!-- Dados Principais -->
      <div id="paneStammdaten">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block mb-1 font-medium">
              <?= htmlspecialchars($langText['contact_number'] ?? 'Nº do Contato', ENT_QUOTES) ?> *
            </label>
            <input name="contact_number" id="contactNumber" type="text" required class="w-full border rounded px-3 py-2">
          </div>
          <div>
            <label class="block mb-1 font-medium">
              <?= htmlspecialchars($langText['name'] ?? 'Empresa', ENT_QUOTES) ?> *
            </label>
            <input name="name" id="clientName" type="text" required class="w-full border rounded px-3 py-2">
          </div>
          <div>
            <label class="block mb-1 font-medium">
              <?= htmlspecialchars($langText['complement'] ?? 'Complemento', ENT_QUOTES) ?>
            </label>
            <input name="complement" id="complement" type="text" class="w-full border rounded px-3 py-2">
          </div>
          <div class="md:col-span-2">
            <label class="block mb-1 font-medium">
              <?= htmlspecialchars($langText['address'] ?? 'Endereço', ENT_QUOTES) ?>
            </label>
            <input name="address" id="address" type="text" class="w-full border rounded px-3 py-2">
          </div>
          <div>
            <label class="block mb-1 font-medium">
              <?= htmlspecialchars($langText['zip_code'] ?? 'CEP', ENT_QUOTES) ?>
            </label>
            <input name="zip_code" id="zipCode" type="text" class="w-full border rounded px-3 py-2">
          </div>
          <div>
            <label class="block mb-1 font-medium">
              <?= htmlspecialchars($langText['city'] ?? 'Cidade', ENT_QUOTES) ?>
            </label>
            <input name="city" id="city" type="text" class="w-full border rounded px-3 py-2">
          </div>
          <div class="md:col-span-2">
            <label class="block mb-1 font-medium">
              <?= htmlspecialchars($langText['country'] ?? 'País', ENT_QUOTES) ?>
            </label>
            <input name="country" id="country" type="text" class="w-full border rounded px-3 py-2">
          </div>
          <div class="col-span-2">
            <label class="block mb-1 font-medium">
              <?= htmlspecialchars($langText['company_private'] ?? 'Empresa / Pessoa Física', ENT_QUOTES) ?>
            </label>
            <select name="category" id="category" class="w-full border rounded px-3 py-2">
              <option value="company"><?= htmlspecialchars($langText['company'] ?? 'Empresa', ENT_QUOTES) ?></option>
              <option value="private"><?= htmlspecialchars($langText['private_person'] ?? 'Pessoa Física', ENT_QUOTES) ?></option>
            </select>
          </div>
        </div>
      </div>

      <!-- Informações Adicionais -->
      <div id="paneZusatzinfo" class="hidden">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block mb-1 font-medium">
              <?= htmlspecialchars($langText['contact_person'] ?? 'Pessoa de Contato', ENT_QUOTES) ?> *
            </label>
            <input name="contact_person" id="contactPerson" type="text" required class="w-full border rounded px-3 py-2">
          </div>
          <div>
            <label class="block mb-1 font-medium">
              <?= htmlspecialchars($langText['owner'] ?? 'Responsável', ENT_QUOTES) ?> *
            </label>
            <input name="owner" id="owner" type="text" required class="w-full border rounded px-3 py-2">
          </div>
          <div>
            <label class="block mb-1 font-medium">
              <?= htmlspecialchars($langText['correspondence'] ?? 'Forma de Correspondência', ENT_QUOTES) ?> *
            </label>
            <select name="correspondence" id="correspondence" required class="w-full border rounded px-3 py-2">
              <option value="Mail">Mail</option>
              <option value="Email">E-mail</option>
            </select>
          </div>
          <div>
            <label class="block mb-1 font-medium">
              <?= htmlspecialchars($langText['language'] ?? 'Idioma', ENT_QUOTES) ?> *
            </label>
            <select name="language" id="language" required class="w-full border rounded px-3 py-2">
              <option value="pt">Português</option>
              <option value="en">English</option>
              <option value="de">Deutsch</option>
              <option value="fr">Français</option>
            </select>
          </div>
          <div class="md:col-span-2">
            <label class="block mb-1 font-medium">
              <?= htmlspecialchars($langText['about'] ?? 'Observações', ENT_QUOTES) ?>
            </label>
            <textarea name="about" id="about" class="w-full border rounded px-3 py-2 h-24"></textarea>
          </div>
          <div>
            <label class="block mb-1 font-medium">
              <?= htmlspecialchars($langText['branch'] ?? 'Setor / Ramo de Atividade', ENT_QUOTES) ?>
            </label>
            <input name="branch" id="branch" type="text" class="w-full border rounded px-3 py-2">
          </div>
        </div>
      </div>

      <!-- Comunicação -->
      <div id="paneKommunikation" class="hidden">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block mb-1 font-medium">
              <?= htmlspecialchars($langText['email'] ?? 'E-mail', ENT_QUOTES) ?>
            </label>
            <input name="email" id="email" type="email" class="w-full border rounded px-3 py-2">
          </div>
          <div>
            <label class="block mb-1 font-medium">
              <?= htmlspecialchars($langText['email2'] ?? 'E-mail 2', ENT_QUOTES) ?>
            </label>
            <input name="email2" id="email2" type="email" class="w-full border rounded px-3 py-2">
          </div>
          <div>
            <label class="block mb-1 font-medium">
              <?= htmlspecialchars($langText['phone'] ?? 'Telefone', ENT_QUOTES) ?>
            </label>
            <input name="phone" id="phone" type="text" class="w-full border rounded px-3 py-2">
          </div>
          <div>
            <label class="block mb-1 font-medium">
              <?= htmlspecialchars($langText['phone2'] ?? 'Telefone 2', ENT_QUOTES) ?>
            </label>
            <input name="phone2" id="phone2" type="text" class="w-full border rounded px-3 py-2">
          </div>
          <div>
            <label class="block mb-1 font-medium">
              <?= htmlspecialchars($langText['mobile'] ?? 'Celular', ENT_QUOTES) ?>
            </label>
            <input name="mobile" id="mobile" type="text" class="w-full border rounded px-3 py-2">
          </div>
          <div>
            <label class="block mb-1 font-medium">
              <?= htmlspecialchars($langText['fax'] ?? 'Fax', ENT_QUOTES) ?>
            </label>
            <input name="fax" id="fax" type="text" class="w-full border rounded px-3 py-2">
          </div>
          <div>
            <label class="block mb-1 font-medium">
              <?= htmlspecialchars($langText['website'] ?? 'Website', ENT_QUOTES) ?>
            </label>
            <input name="website" id="website" type="url" class="w-full border rounded px-3 py-2">
          </div>
          <div>
            <label class="block mb-1 font-medium">
              <?= htmlspecialchars($langText['skype'] ?? 'Skype', ENT_QUOTES) ?>
            </label>
            <input name="skype" id="skype" type="text" class="w-full border rounded px-3 py-2">
          </div>
        </div>
      </div>

      <!-- Outras Informações -->
      <div id="paneWeitereKontakt" class="hidden">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block mb-1 font-medium">
              <?= htmlspecialchars($langText['employee_count'] ?? 'Nº de Funcionários', ENT_QUOTES) ?>
            </label>
            <input name="employee_count" id="employeeCount" type="number" class="w-full border rounded px-3 py-2">
          </div>
          <div>
            <label class="block mb-1 font-medium">
              <?= htmlspecialchars($langText['registry_number'] ?? 'Nº de Registro Comercial', ENT_QUOTES) ?>
            </label>
            <input name="registry_number" id="registryNumber" type="text" class="w-full border rounded px-3 py-2">
          </div>
          <div>
            <label class="block mb-1 font-medium">
              <?= htmlspecialchars($langText['vat_number'] ?? 'Nº do IVA', ENT_QUOTES) ?>
            </label>
            <input name="vat_number" id="vatNumber" type="text" class="w-full border rounded px-3 py-2">
          </div>
          <div>
            <label class="block mb-1 font-medium">
              <?= htmlspecialchars($langText['tax_id_number'] ?? 'Nº de Identificação Fiscal', ENT_QUOTES) ?>
            </label>
            <input name="tax_id_number" id="taxIdNumber" type="text" class="w-full border rounded px-3 py-2">
          </div>
        </div>
      </div>

      <div class="flex justify-end space-x-2 pt-4">
        <button type="button" id="cancelClient" class="px-4 py-2 border rounded">
          <?= htmlspecialchars($langText['cancel'] ?? 'Cancelar', ENT_QUOTES) ?>
        </button>
        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg font-medium">
          <?= htmlspecialchars($langText['save'] ?? 'Salvar', ENT_QUOTES) ?>
        </button>
      </div>
    </form>
  </div>
</div>

<script src="<?= asset('js/clients.js') ?>" defer></script>
<?php require __DIR__ . '/../layout/footer.php'; ?>
