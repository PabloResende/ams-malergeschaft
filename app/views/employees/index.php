<?php
// app/views/employees/index.php

require_once __DIR__ . '/../layout/header.php';
$baseUrl = BASE_URL;
?>
<script>
  window.baseUrl = <?= json_encode($baseUrl, JSON_UNESCAPED_SLASHES) ?>;
  window.confirmDeleteMsg = <?= json_encode($langText['confirm_delete'] ?? 'Tem certeza que deseja excluir este funcionário?', JSON_UNESCAPED_SLASHES) ?>;
  window.langText = <?= json_encode($langText, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_QUOT|JSON_HEX_APOS) ?>;
</script>

<div class="pt-20 px-4 sm:px-8 ml-0 lg:ml-56">
  
  <?php if (!empty($_SESSION['error'])): ?>
    <div class="fixed top-4 right-4 bg-red-500 text-white p-3 rounded max-w-xs w-full">
      <?= htmlspecialchars($_SESSION['error'], ENT_QUOTES) ?>
    </div>
    <?php unset($_SESSION['error']); ?>
  <?php endif; ?>
  
  <h1 class="text-3xl font-bold mb-6">
    <?= htmlspecialchars($langText['employees_list'] ?? 'Employees List', ENT_QUOTES) ?>
  </h1>

  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if (empty($employees)): ?>
      <p class="text-gray-600">
        <?= htmlspecialchars($langText['no_employees_available'] ?? 'No employees available.', ENT_QUOTES) ?>
      </p>
    <?php else: foreach ($employees as $emp):
        $start  = new DateTime($emp['start_date']);
        $now    = new DateTime();
        $d      = $start->diff($now);
        $tenure = trim(
          ($d->y ? "{$d->y} " . ($d->y == 1
              ? ($langText['year']  ?? 'year')
              : ($langText['years'] ?? 'years'))
           : '') .
          ($d->m ? " {$d->m} " . ($d->m == 1
              ? ($langText['month']  ?? 'month')
              : ($langText['months'] ?? 'months'))
           : '')
        );
    ?>
      <div
        class="employee-card bg-white rounded-lg shadow hover:shadow-xl transition-shadow cursor-pointer p-4 flex flex-col sm:flex-row sm:items-center"
        data-id="<?= (int)$emp['id'] ?>"
      >
        <div class="w-20 h-20 bg-gray-100 rounded-lg overflow-hidden flex items-center justify-center flex-shrink-0">
          <svg class="w-10 h-10 text-gray-300" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2s2.1 4.8 4.8 4.8zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8V22h19.2v-2.8c0-3.2-6.4-4.8-9.6-4.8z"/>
          </svg>
        </div>
        <div class="mt-4 sm:mt-0 sm:ml-4 flex-1">
          <h2 class="text-xl font-semibold">
            <?= htmlspecialchars($emp['name'] . ' ' . $emp['last_name'], ENT_QUOTES) ?>
          </h2>
          <p class="text-sm text-gray-600">
            <?= htmlspecialchars($langText['role'] ?? 'Role', ENT_QUOTES) ?>:
            <strong><?= htmlspecialchars($emp['role'], ENT_QUOTES) ?></strong>
          </p>
          <p class="text-sm text-gray-600">
            <?= htmlspecialchars($langText['time_in_company'] ?? 'Time in Company', ENT_QUOTES) ?>:
            <strong><?= $tenure ?: "0 " . htmlspecialchars($langText['months'] ?? 'months', ENT_QUOTES) ?></strong>
          </p>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>

  <button id="addEmployeeBtn"
          class="fixed bottom-16 right-8 bg-green-500 hover:bg-green-600 text-white rounded-full p-4 shadow-lg"
          aria-label="<?= htmlspecialchars($langText['create_employee'] ?? 'Add Employee', ENT_QUOTES) ?>">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
      <path d="M12 4v16m8-8H4" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
  </button>
</div>

<!-- Modal de Criação -->
<div id="employeeModal"
     class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm hidden z-50">
  <div class="bg-white rounded-2xl w-full sm:w-11/12 max-w-4xl mx-4 p-6 lg:p-10 max-h-[80vh] overflow-y-auto relative">
    <button id="closeEmployeeModal"
            class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
    <h2 class="text-2xl font-bold mb-4">
      <?= htmlspecialchars($langText['create_employee'] ?? 'Create Employee', ENT_QUOTES) ?>
    </h2>

    <div class="overflow-x-auto mb-6">
      <ul class="flex border-b whitespace-nowrap">
        <li class="mr-4">
          <button type="button"
                  class="tab-btn border-b-2 pb-2 border-blue-600 text-blue-600"
                  data-tab="general-create">
            <?= htmlspecialchars($langText['general'] ?? 'General', ENT_QUOTES) ?>
          </button>
        </li>
        <li class="mr-4">
          <button type="button"
                  class="tab-btn pb-2 text-gray-600 hover:text-gray-800"
                  data-tab="documents-create">
            <?= htmlspecialchars($langText['documents'] ?? 'Documents', ENT_QUOTES) ?>
          </button>
        </li>
        <li>
          <button type="button"
                  class="tab-btn pb-2 text-gray-600 hover:text-gray-800"
                  data-tab="login-create">
            <?= htmlspecialchars($langText['account'] ?? 'Acesso', ENT_QUOTES) ?>
          </button>
        </li>
      </ul>
    </div>

    <form action="<?= url('employees/store') ?>"
          method="POST"
          enctype="multipart/form-data"
          class="space-y-6">

      <div class="tab-content">

        <!-- Painel Geral -->
        <div id="panel-general-create" class="tab-panel">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block mb-2">
                <?= htmlspecialchars($langText['name'] ?? 'Name', ENT_QUOTES) ?>
              </label>
              <input type="text" name="name" required class="w-full border rounded-lg p-2">
            </div>
            <div>
              <label class="block mb-2">
                <?= htmlspecialchars($langText['last_name'] ?? 'Last Name', ENT_QUOTES) ?>
              </label>
              <input type="text" name="last_name" required class="w-full border rounded-lg p-2">
            </div>
            <div>
              <label class="block mb-2">
                <?= htmlspecialchars($langText['address'] ?? 'Address', ENT_QUOTES) ?>
              </label>
              <input type="text" name="address" class="w-full border rounded-lg p-2">
            </div>
            <div>
              <label class="block mb-2">
                <?= htmlspecialchars($langText['zip_code'] ?? 'CEP', ENT_QUOTES) ?>
              </label>
              <input type="text" name="zip_code" id="detailsEmployeeZipCode" class="w-full border rounded-lg p-2">
            </div>
            <div>
              <label class="block mb-2">
                <?= htmlspecialchars($langText['city'] ?? 'Cidade', ENT_QUOTES) ?>
              </label>
              <input type="text" name="city" id="detailsEmployeeCity" class="w-full border rounded-lg p-2">
            </div>
            <div>
              <label class="block mb-2">
                <?= htmlspecialchars($langText['sex'] ?? 'Sex', ENT_QUOTES) ?>
              </label>
              <select name="sex" class="w-full border rounded-lg p-2">
                <option value="male"><?= htmlspecialchars($langText['male'] ?? 'Male', ENT_QUOTES) ?></option>
                <option value="female"><?= htmlspecialchars($langText['female'] ?? 'Female', ENT_QUOTES) ?></option>
                <option value="other"><?= htmlspecialchars($langText['other'] ?? 'Other', ENT_QUOTES) ?></option>
              </select>
            </div>
            <div>
              <label class="block mb-2">
                <?= htmlspecialchars($langText['birth_date'] ?? 'Birth Date', ENT_QUOTES) ?>
              </label>
              <input type="date" name="birth_date" required class="w-full border rounded-lg p-2">
            </div>
            <div>
              <label class="block mb-2">
                <?= htmlspecialchars($langText['nationality'] ?? 'Nationality', ENT_QUOTES) ?>
              </label>
              <input type="text" name="nationality" class="w-full border rounded-lg p-2">
            </div>
            <div>
              <label class="block mb-2">
                <?= htmlspecialchars($langText['permission_type'] ?? 'Permission Type', ENT_QUOTES) ?>
              </label>
              <input type="text" name="permission_type" class="w-full border rounded-lg p-2">
            </div>
            <div>
              <label class="block mb-2">
                <?= htmlspecialchars($langText['ahv_number'] ?? 'AHV Number', ENT_QUOTES) ?>
              </label>
              <input type="text" name="ahv_number" class="w-full border rounded-lg p-2">
            </div>
            <div>
              <label class="block mb-2">
                <?= htmlspecialchars($langText['phone'] ?? 'Phone', ENT_QUOTES) ?>
              </label>
              <input type="text" name="phone" class="w-full border rounded-lg p-2">
            </div>
            <div>
              <label class="block mb-2">
                <?= htmlspecialchars($langText['religion'] ?? 'Religion', ENT_QUOTES) ?>
              </label>
              <input type="text" name="religion" class="w-full border rounded-lg p-2">
            </div>
            <div>
              <label class="block mb-2">
                <?= htmlspecialchars($langText['marital_status'] ?? 'Marital Status', ENT_QUOTES) ?>
              </label>
              <select name="marital_status" class="w-full border rounded-lg p-2">
                <option value="single"><?= htmlspecialchars($langText['single'] ?? 'Single', ENT_QUOTES) ?></option>
                <option value="married"><?= htmlspecialchars($langText['married'] ?? 'Married', ENT_QUOTES) ?></option>
                <option value="divorced"><?= htmlspecialchars($langText['divorced'] ?? 'Divorced', ENT_QUOTES) ?></option>
                <option value="widowed"><?= htmlspecialchars($langText['widowed'] ?? 'Widowed', ENT_QUOTES) ?></option>
              </select>
            </div>
            <div>
              <label class="block mb-2">
                <?= htmlspecialchars($langText['function'] ?? 'Função', ENT_QUOTES) ?>
              </label>
              <input type="text" name="function" id="detailsEmployeeFunction" required class="w-full border rounded-lg p-2">
            </div>
            <div>
              <label class="block mb-2">
                <?= htmlspecialchars($langText['start_date'] ?? 'Start Date', ENT_QUOTES) ?>
              </label>
              <input type="date" name="start_date" required class="w-full border rounded-lg p-2">
            </div>
            <div class="md:col-span-2">
              <label class="block mb-2">
                <?= htmlspecialchars($langText['about_me'] ?? 'About Me', ENT_QUOTES) ?>
              </label>
              <textarea name="about" rows="4" class="w-full border rounded-lg p-2"></textarea>
            </div>
          </div>
        </div>

        <!-- Painel Documentos -->
        <div id="panel-documents-create" class="tab-panel hidden">
          <h3 class="text-lg font-semibold mb-2">
            <?= htmlspecialchars($langText['documents'] ?? 'Documents', ENT_QUOTES) ?>
          </h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php foreach ([
              'passport'                   => 'Passport',
              'permission_photo_front'     => 'Permission Photo (Front)',
              'permission_photo_back'      => 'Permission Photo (Back)',
              'health_card_front'          => 'Health Card (Front)',
              'health_card_back'           => 'Health Card (Back)',
              'bank_card_front'            => 'Bank Card (Front)',
              'bank_card_back'             => 'Bank Card (Back)',
              'marriage_certificate'       => 'Marriage Certificate'
            ] as $field => $label): ?>
            <div>
              <label class="block mb-2">
                <?= htmlspecialchars($langText[$field] ?? $label, ENT_QUOTES) ?>
              </label>
              <input type="file" name="<?= $field ?>" class="w-full border rounded-lg p-2">
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Painel Login -->
        <div id="panel-login-create" class="tab-panel hidden">
          <h3 class="text-lg font-semibold mb-2">
            <?= htmlspecialchars($langText['account'] ?? 'Acesso', ENT_QUOTES) ?>
          </h3>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['email'] ?? 'Email', ENT_QUOTES) ?></label>
              <input type="email" name="email" required class="w-full border rounded-lg p-2">
            </div>
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['password'] ?? 'Senha', ENT_QUOTES) ?></label>
              <input type="password" name="password" required class="w-full border rounded-lg p-2">
            </div>
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['role'] ?? 'Nível', ENT_QUOTES) ?></label>
              <select name="role_id" required class="w-full border rounded-lg p-2">
                <option value=""><?= htmlspecialchars($langText['select_role'] ?? 'Selecione...', ENT_QUOTES) ?></option>
                <?php foreach ($roles as $r): ?>
                  <option value="<?= (int)$r['id'] ?>">
                    <?= htmlspecialchars(ucfirst($r['name']), ENT_QUOTES) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>

      </div> <!-- /.tab-content -->

      <div class="flex justify-end space-x-2 pt-4 border-t">
        <button type="button" id="cancelEmployeeModal"
                class="px-4 py-2 border rounded-lg hover:bg-gray-100">
          <?= htmlspecialchars($langText['cancel'] ?? 'Cancel', ENT_QUOTES) ?>
        </button>
        <button type="submit"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
          <?= htmlspecialchars($langText['submit'] ?? 'Submit', ENT_QUOTES) ?>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal de Edição/Detalhes -->
<div id="employeeDetailsModal"
     class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm hidden z-50">
  <div class="bg-white rounded-2xl w-full sm:w-11/12 max-w-4xl mx-4 p-6 lg:p-10 max-h-[80vh] overflow-y-auto relative">
    <button class="closeEmployeeDetailsModal absolute top-4 right-4 text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
    <h2 class="text-2xl font-bold mb-4">
      <?= htmlspecialchars($langText['employee_details'] ?? 'Employee Details', ENT_QUOTES) ?>
    </h2>

    <ul class="flex border-b whitespace-nowrap mb-6 overflow-x-auto">
      <li class="mr-4">
        <button type="button"
                class="tab-btn border-b-2 pb-2 border-blue-600 text-blue-600"
                data-tab="general-details">
          <?= htmlspecialchars($langText['general'] ?? 'General', ENT_QUOTES) ?>
        </button>
      </li>
      <li class="mr-4">
        <button type="button"
                class="tab-btn pb-2 text-gray-600 hover:text-gray-800"
                data-tab="documents-details">
          <?= htmlspecialchars($langText['documents'] ?? 'Documents', ENT_QUOTES) ?>
        </button>
      </li>
      <li class="mr-4">
        <button type="button"
                class="tab-btn pb-2 text-gray-600 hover:text-gray-800"
                data-tab="login-details">
          <?= htmlspecialchars($langText['account'] ?? 'Acesso', ENT_QUOTES) ?>
        </button>
      </li>
      <li>
        <button type="button"
                class="tab-btn pb-2 text-gray-600 hover:text-gray-800"
                data-tab="transactions-details">
          <?= htmlspecialchars($langText['transactions'] ?? 'Transactions', ENT_QUOTES) ?>
        </button>
      </li>
    </ul>

    <form id="employeeDetailsForm"
          action="<?= url('employees/update') ?>"
          method="POST"
          enctype="multipart/form-data"
          class="space-y-6">

      <!-- Hidden IDs -->
      <input type="hidden" name="id"      id="detailsEmployeeId">
      <input type="hidden" name="user_id" id="detailsLoginUserId">

      <div class="tab-content">

        <!-- Painel Geral Detalhes -->
        <div id="panel-general-details" class="tab-panel">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['name'] ?? 'Name', ENT_QUOTES) ?></label>
              <input type="text" name="name" id="detailsEmployeeName" class="w-full border rounded-lg p-2" required>
            </div>
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['last_name'] ?? 'Last Name', ENT_QUOTES) ?></label>
              <input type="text" name="last_name" id="detailsEmployeeLastName" class="w-full border rounded-lg p-2" required>
            </div>
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['function'] ?? 'Função', ENT_QUOTES) ?></label>
              <input type="text" name="function" id="detailsEmployeeFunction" class="w-full border rounded-lg p-2" required>
            </div>
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['address'] ?? 'Address', ENT_QUOTES) ?></label>
              <input type="text" name="address" id="detailsEmployeeAddress" class="w-full border rounded-lg p-2">
            </div>
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['zip_code'] ?? 'CEP', ENT_QUOTES) ?></label>
              <input type="text" name="zip_code" id="detailsEmployeeZipCode" class="w-full border rounded-lg p-2">
            </div>
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['city'] ?? 'Cidade', ENT_QUOTES) ?></label>
              <input type="text" name="city" id="detailsEmployeeCity" class="w-full border rounded-lg p-2">
            </div>
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['sex'] ?? 'Sex', ENT_QUOTES) ?></label>
              <select name="sex" id="detailsEmployeeSex" class="w-full border rounded-lg p-2">
                <option value="male"><?= htmlspecialchars($langText['male'] ?? 'Male', ENT_QUOTES) ?></option>
                <option value="female"><?= htmlspecialchars($langText['female'] ?? 'Female', ENT_QUOTES) ?></option>
                <option value="other"><?= htmlspecialchars($langText['other'] ?? 'Other', ENT_QUOTES) ?></option>
              </select>
            </div>
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['birth_date'] ?? 'Birth Date', ENT_QUOTES) ?></label>
              <input type="date" name="birth_date" id="detailsEmployeeBirthDate" class="w-full border rounded-lg p-2">
            </div>
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['nationality'] ?? 'Nationality', ENT_QUOTES) ?></label>
              <input type="text" name="nationality" id="detailsEmployeeNationality" class="w-full border rounded-lg p-2">
            </div>
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['permission_type'] ?? 'Permission Type', ENT_QUOTES) ?></label>
              <input type="text" name="permission_type" id="detailsEmployeePermissionType" class="w-full border rounded-lg p-2">
            </div>
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['ahv_number'] ?? 'AHV Number', ENT_QUOTES) ?></label>
              <input type="text" name="ahv_number" id="detailsEmployeeAhvNumber" class="w-full border rounded-lg p-2">
            </div>
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['phone'] ?? 'Phone', ENT_QUOTES) ?></label>
              <input type="text" name="phone" id="detailsEmployeePhone" class="w-full border rounded-lg p-2">
            </div>
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['religion'] ?? 'Religion', ENT_QUOTES) ?></label>
              <input type="text" name="religion" id="detailsEmployeeReligion" class="w-full border rounded-lg p-2">
            </div>
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['marital_status'] ?? 'Marital Status', ENT_QUOTES) ?></label>
              <select name="marital_status" id="detailsEmployeeMaritalStatus" class="w-full border rounded-lg p-2">
                <option value="single"><?= htmlspecialchars($langText['single'] ?? 'Single', ENT_QUOTES) ?></option>
                <option value="married"><?= htmlspecialchars($langText['married'] ?? 'Married', ENT_QUOTES) ?></option>
                <option value="divorced"><?= htmlspecialchars($langText['divorced'] ?? 'Divorced', ENT_QUOTES) ?></option>
                <option value="widowed"><?= htmlspecialchars($langText['widowed'] ?? 'Widowed', ENT_QUOTES) ?></option>
              </select>
            </div>
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['start_date'] ?? 'Start Date', ENT_QUOTES) ?></label>
              <input type="date" name="start_date" id="detailsEmployeeStartDate" class="w-full border rounded-lg p-2">
            </div>
            <div class="md:col-span-2">
              <label class="block mb-2"><?= htmlspecialchars($langText['about_me'] ?? 'About Me', ENT_QUOTES) ?></label>
              <textarea name="about" id="detailsEmployeeAbout" rows="4" class="w-full border rounded-lg p-2"></textarea>
            </div>
          </div>
        </div>

        <!-- Painel Documentos Detalhes -->
        <div id="panel-documents-details" class="tab-panel hidden">
          <h3 class="text-lg font-semibold mb-2"><?= htmlspecialchars($langText['documents'] ?? 'Documents', ENT_QUOTES) ?></h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php foreach ([
              'passport'               => 'Passport',
              'permission_photo_front' => 'Permission Photo (Front)',
              'permission_photo_back'  => 'Permission Photo (Back)',
              'health_card_front'      => 'Health Card (Front)',
              'health_card_back'       => 'Health Card (Back)',
              'bank_card_front'        => 'Bank Card (Front)',
              'bank_card_back'         => 'Bank Card (Back)',
              'marriage_certificate'   => 'Marriage Certificate'
            ] as $field => $label): ?>
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText[$field] ?? $label, ENT_QUOTES) ?></label>
              <img id="view<?= ucfirst($field) ?>" class="w-full h-32 object-cover rounded border mb-2" style="display:none;">
              <a   id="link<?= ucfirst($field) ?>" class="text-blue-600 underline block mb-2" target="_blank" style="display:none;"></a>
              <input type="file" name="<?= $field ?>" class="w-full border rounded-lg p-2">
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Painel Acesso Detalhes -->
        <div id="panel-login-details" class="tab-panel hidden">
          <h3 class="text-lg font-semibold mb-2"><?= htmlspecialchars($langText['account'] ?? 'Acesso', ENT_QUOTES) ?></h3>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['email'] ?? 'Email', ENT_QUOTES) ?></label>
              <input type="email"
                     id="detailsLoginEmail"
                     name="email"
                     class="w-full border rounded-lg p-2"
                     required>
            </div>
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['new_password'] ?? 'Nova Senha', ENT_QUOTES) ?></label>
              <input type="password"
                     id="detailsLoginPassword"
                     name="password"
                     class="w-full border rounded-lg p-2"
                     placeholder="••••••••">
            </div>
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['role'] ?? 'Nível', ENT_QUOTES) ?></label>
              <select name="role_id" id="detailsEmployeeRoleId" class="w-full border rounded-lg p-2">
                <?php foreach ($roles as $r): ?>
                  <option value="<?= (int)$r['id'] ?>"><?= htmlspecialchars($r['name'], ENT_QUOTES) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>

        <!-- Painel Transações Detalhes -->
        <div id="panel-transactions-details" class="tab-panel hidden">
          <h3 class="text-lg font-semibold mb-2"><?= htmlspecialchars($langText['transactions'] ?? 'Transactions', ENT_QUOTES) ?></h3>
          <div class="overflow-x-auto">
            <table class="w-full bg-white rounded-lg shadow">
              <thead class="bg-gray-100">
                <tr>
                  <th class="p-3 text-left text-sm font-medium text-gray-700"><?= htmlspecialchars($langText['date'] ?? 'Date', ENT_QUOTES) ?></th>
                  <th class="p-3 text-left text-sm font-medium text-gray-700"><?= htmlspecialchars($langText['type'] ?? 'Type', ENT_QUOTES) ?></th>
                  <th class="p-3 text-right text-sm font-medium text-gray-700"><?= htmlspecialchars($langText['amount'] ?? 'Amount', ENT_QUOTES) ?></th>
                </tr>
              </thead>
              <tbody id="empTransBody">
                <tr>
                  <td colspan="3" class="p-4 text-center text-gray-500"><?= htmlspecialchars($langText['no_transactions'] ?? 'No transactions', ENT_QUOTES) ?></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

      </div> <!-- /.tab-content -->

      <div class="flex justify-between mt-6">
        <button type="button"
                class="closeEmployeeDetailsModal px-4 py-2 border rounded-lg hover:bg-gray-100">
          <?= htmlspecialchars($langText['cancel'] ?? 'Cancel', ENT_QUOTES) ?>
        </button>
        <div class="space-x-2">
          <button type="button"
                  id="deleteEmployeeBtn"
                  class="px-4 py-2 text-red-600 hover:underline">
            <?= htmlspecialchars($langText['delete'] ?? 'Delete', ENT_QUOTES) ?>
          </button>
          <button type="submit"
                  class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">
            <?= htmlspecialchars($langText['save_changes'] ?? 'Save Changes', ENT_QUOTES) ?>
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<script src="<?= asset('js/employees.js') ?>" defer></script>
  <script defer src="<?= asset('js/header.js') ?>"></script>
