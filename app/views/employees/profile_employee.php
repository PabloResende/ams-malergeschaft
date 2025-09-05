<?php
// app/views/employees/profile_employee.php

require __DIR__ . '/../layout/header.php';

$userEmail = $_SESSION['user']['email'] ?? '';
?>
<div class="pt-20 px-4 sm:px-8 ml-0 sm:ml-56 pb-8 max-w-4xl mx-auto">
  <h1 class="text-3xl font-bold mb-6">
    <?= htmlspecialchars($langText['profile'] ?? 'Meu Perfil', ENT_QUOTES) ?>
  </h1>

  <form action="<?= url('employees/profile') ?>"
        method="POST"
        enctype="multipart/form-data"
        class="space-y-8">

    <!-- campos necessários para o update -->
    <input type="hidden" name="id"      value="<?= (int)($emp['id'] ?? 0) ?>">
    <input type="hidden" name="user_id" value="<?= (int)($emp['user_id'] ?? 0) ?>">
    <input type="hidden" name="email"   value="<?= htmlspecialchars($userEmail, ENT_QUOTES) ?>">
    <input type="hidden" name="role_id" value="<?= (int)($emp['role_id'] ?? 0) ?>">

    <fieldset disabled class="space-y-6">
      <legend class="sr-only">Dados Pessoais</legend>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Name -->
        <div>
          <label class="block mb-1 font-medium"><?= htmlspecialchars($langText['name'] ?? 'Nome', ENT_QUOTES) ?></label>
          <input
            type="text"
            value="<?= htmlspecialchars($emp['name']    ?? '', ENT_QUOTES) ?>"
            class="w-full border rounded-lg p-2 bg-gray-100"
          />
        </div>
        <!-- Last name -->
        <div>
          <label class="block mb-1 font-medium"><?= htmlspecialchars($langText['last_name'] ?? 'Sobrenome', ENT_QUOTES) ?></label>
          <input
            type="text"
            value="<?= htmlspecialchars($emp['last_name'] ?? '', ENT_QUOTES) ?>"
            class="w-full border rounded-lg p-2 bg-gray-100"
          />
        </div>
        <!-- Function -->
        <div>
          <label class="block mb-1 font-medium"><?= htmlspecialchars($langText['function'] ?? 'Função', ENT_QUOTES) ?></label>
          <input
            type="text"
            value="<?= htmlspecialchars($emp['function']  ?? '', ENT_QUOTES) ?>"
            class="w-full border rounded-lg p-2 bg-gray-100"
          />
        </div>
        <!-- Email -->
        <div class="md:col-span-2">
          <label class="block mb-1 font-medium"><?= htmlspecialchars($langText['email'] ?? 'Email', ENT_QUOTES) ?></label>
          <input
            type="email"
            value="<?= htmlspecialchars($userEmail, ENT_QUOTES) ?>"
            class="w-full border rounded-lg p-2 bg-gray-100"
          />
        </div>
      </div>

      <hr class="my-6">

      <legend class="sr-only">Detalhes</legend>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Address -->
        <div>
          <label class="block mb-1 font-medium"><?= htmlspecialchars($langText['address'] ?? 'Endereço', ENT_QUOTES) ?></label>
          <input
            type="text"
            value="<?= htmlspecialchars($emp['address'] ?? '', ENT_QUOTES) ?>"
            class="w-full border rounded-lg p-2 bg-gray-100"
          />
        </div>
        <!-- Zip Code -->
        <div>
          <label class="block mb-1 font-medium"><?= htmlspecialchars($langText['zip_code'] ?? 'CEP', ENT_QUOTES) ?></label>
          <input
            type="text"
            value="<?= htmlspecialchars($emp['zip_code'] ?? '', ENT_QUOTES) ?>"
            class="w-full border rounded-lg p-2 bg-gray-100"
          />
        </div>
        <!-- City -->
        <div>
          <label class="block mb-1 font-medium"><?= htmlspecialchars($langText['city'] ?? 'Cidade', ENT_QUOTES) ?></label>
          <input
            type="text"
            value="<?= htmlspecialchars($emp['city'] ?? '', ENT_QUOTES) ?>"
            class="w-full border rounded-lg p-2 bg-gray-100"
          />
        </div>
        <!-- Phone -->
        <div>
          <label class="block mb-1 font-medium"><?= htmlspecialchars($langText['phone'] ?? 'Telefone', ENT_QUOTES) ?></label>
          <input
            type="text"
            value="<?= htmlspecialchars($emp['phone'] ?? '', ENT_QUOTES) ?>"
            class="w-full border rounded-lg p-2 bg-gray-100"
          />
        </div>
        <!-- Birth Date -->
        <div>
          <label class="block mb-1 font-medium"><?= htmlspecialchars($langText['birth_date'] ?? 'Data de Nascimento', ENT_QUOTES) ?></label>
          <input
            type="date"
            value="<?= htmlspecialchars($emp['birth_date'] ?? '', ENT_QUOTES) ?>"
            class="w-full border rounded-lg p-2 bg-gray-100"
          />
        </div>
        <!-- Nationality -->
        <div>
          <label class="block mb-1 font-medium"><?= htmlspecialchars($langText['nationality'] ?? 'Nacionalidade', ENT_QUOTES) ?></label>
          <input
            type="text"
            value="<?= htmlspecialchars($emp['nationality'] ?? '', ENT_QUOTES) ?>"
            class="w-full border rounded-lg p-2 bg-gray-100"
          />
        </div>
      </div>

      <hr class="my-6">
    </fieldset>

    <fieldset class="space-y-6">
      <legend class="text-lg font-bold mb-4"><?= htmlspecialchars($langText['documents'] ?? 'Documentos', ENT_QUOTES) ?></legend>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <?php foreach ([
          'passport'                   => 'Passport',
          'permission_photo_front'     => 'Permission (Frente)',
          'permission_photo_back'      => 'Permission (Verso)',
          'health_card_front'          => 'Health Card (Frente)',
          'health_card_back'           => 'Health Card (Verso)',
          'bank_card_front'            => 'Cartão Bancário (Frente)',
          'bank_card_back'             => 'Cartão Bancário (Verso)',
          'marriage_certificate'       => 'Certidão de Casamento'
        ] as $field => $label): ?>
          <div>
            <label class="block mb-1 font-medium"><?= htmlspecialchars($langText[$field] ?? $label, ENT_QUOTES) ?></label>

            <?php if (!empty($emp[$field])): ?>
              <div class="mb-2">
                <a href="<?= htmlspecialchars(BASE_URL . '/' . $emp[$field], ENT_QUOTES) ?>"
                   target="_blank"
                   class="text-blue-600 hover:underline break-words">
                  <?= basename($emp[$field]) ?>
                </a>
              </div>
            <?php endif; ?>

            <input type="file"
                   name="<?= $field ?>"
                   class="w-full border rounded-lg p-2 bg-white" />
          </div>
        <?php endforeach; ?>
      </div>
    </fieldset>

    <!-- Botão de salvar -->
    <div class="flex justify-end">
      <button type="submit"
              class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
        <?= htmlspecialchars($langText['save'] ?? 'Salvar', ENT_QUOTES) ?>
      </button>
    </div>
  </form>
</div>

<script defer src="<?= asset('js/header.js') ?>"></script>
