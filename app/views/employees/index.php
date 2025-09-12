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
        // Corrigir erro de start_date
        $tenure = '0 ' . htmlspecialchars($langText['months'] ?? 'months', ENT_QUOTES);
        if (!empty($emp['start_date'])) {
            try {
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
                if (empty($tenure)) {
                    $tenure = '0 ' . htmlspecialchars($langText['months'] ?? 'months', ENT_QUOTES);
                }
            } catch (Exception $e) {
                $tenure = '0 ' . htmlspecialchars($langText['months'] ?? 'months', ENT_QUOTES);
            }
        }
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
            <?= htmlspecialchars($langText['function'] ?? 'Função', ENT_QUOTES) ?>:
            <strong><?= htmlspecialchars($emp['function'] ?? 'Não definida', ENT_QUOTES) ?></strong>
          </p>
          <p class="text-sm text-gray-600">
            <?= htmlspecialchars($langText['time_in_company'] ?? 'Time in Company', ENT_QUOTES) ?>:
            <strong><?= $tenure ?></strong>
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
              <input type="text" name="zip_code" class="w-full border rounded-lg p-2">
            </div>
            <div>
              <label class="block mb-2">
                <?= htmlspecialchars($langText['city'] ?? 'Cidade', ENT_QUOTES) ?>
              </label>
              <input type="text" name="city" class="w-full border rounded-lg p-2">
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
              <input type="text" name="function" required class="w-full border rounded-lg p-2">
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

<!-- Modal de Detalhes/Edição de Funcionário -->
<div id="employeeDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
  <div class="bg-white rounded-lg shadow-xl max-w-6xl w-full mx-4 max-h-[90vh] overflow-hidden">
    <div class="flex items-center justify-between p-6 border-b border-gray-200">
      <h2 class="text-xl font-semibold text-gray-900">
        <?= htmlspecialchars($langText['employee_details'] ?? 'Detalhes do Funcionário', ENT_QUOTES); ?>
      </h2>
      <button class="closeEmployeeDetailsModal text-gray-400 hover:text-gray-600">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
      </button>
    </div>

    <!-- Tabs Navigation -->
    <div class="border-b border-gray-200">
      <nav class="flex space-x-8 px-6">
        <button data-tab="general-details" class="py-3 px-1 border-b-2 border-blue-600 text-blue-600 text-sm font-medium">
          General
        </button>
        <button data-tab="documents-details" class="py-3 px-1 border-b-2 border-transparent text-gray-600 hover:text-gray-800 text-sm font-medium">
          Documentos
        </button>
        <button data-tab="access-details" class="py-3 px-1 border-b-2 border-transparent text-gray-600 hover:text-gray-800 text-sm font-medium">
          Acesso
        </button>
        <button data-tab="transactions-details" class="py-3 px-1 border-b-2 border-transparent text-gray-600 hover:text-gray-800 text-sm font-medium">
          Transações
        </button>
        <button data-tab="work-hours" class="py-3 px-1 border-b-2 border-transparent text-gray-600 hover:text-gray-800 text-sm font-medium">
          Horas de Trabalho
        </button>
      </nav>
    </div>

    <!-- Tab Content -->
    <div class="max-h-[60vh] overflow-y-auto">
      
      <!-- GENERAL TAB -->
      <div id="tab-general-details" class="tab-panel">
        <form id="employeeDetailsForm" method="POST" enctype="multipart/form-data" class="p-6">
          <!-- CORREÇÃO: IDs corretos que o JavaScript espera -->
          <input type="hidden" name="id" id="detailsEmployeeId">
          <input type="hidden" name="user_id" id="detailsLoginUserId">
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Coluna Esquerda -->
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  <?= htmlspecialchars($langText['name'] ?? 'Nome', ENT_QUOTES); ?>
                </label>
                <input type="text" name="name" id="detailsEmployeeName" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  Último Nome
                </label>
                <input type="text" name="last_name" id="detailsEmployeeLastName" class="w-full border border-gray-300 rounded-lg px-3 py-2">
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  <?= htmlspecialchars($langText['function'] ?? 'Função', ENT_QUOTES); ?>
                </label>
                <input type="text" name="function" id="detailsEmployeeFunction" class="w-full border border-gray-300 rounded-lg px-3 py-2">
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  <?= htmlspecialchars($langText['address'] ?? 'Endereço', ENT_QUOTES); ?>
                </label>
                <input type="text" name="address" id="detailsEmployeeAddress" class="w-full border border-gray-300 rounded-lg px-3 py-2">
              </div>
              
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    <?= htmlspecialchars($langText['zip_code'] ?? 'CEP', ENT_QUOTES); ?>
                  </label>
                  <input type="text" name="zip_code" id="detailsEmployeeZipCode" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    <?= htmlspecialchars($langText['city'] ?? 'Cidade', ENT_QUOTES); ?>
                  </label>
                  <input type="text" name="city" id="detailsEmployeeCity" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sex</label>
                <select name="sex" id="detailsEmployeeSex" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                  <option value="male"><?= htmlspecialchars($langText['male'] ?? 'Masculino', ENT_QUOTES); ?></option>
                  <option value="female"><?= htmlspecialchars($langText['female'] ?? 'Feminino', ENT_QUOTES); ?></option>
                  <option value="other">Outro</option>
                </select>
              </div>
            </div>
            
            <!-- Coluna Direita -->
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  <?= htmlspecialchars($langText['birth_date'] ?? 'Data de Nascimento', ENT_QUOTES); ?>
                </label>
                <input type="date" name="birth_date" id="detailsEmployeeBirthDate" class="w-full border border-gray-300 rounded-lg px-3 py-2">
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  <?= htmlspecialchars($langText['nationality'] ?? 'Nacionalidade', ENT_QUOTES); ?>
                </label>
                <input type="text" name="nationality" id="detailsEmployeeNationality" class="w-full border border-gray-300 rounded-lg px-3 py-2">
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Permissão</label>
                <input type="text" name="permission_type" id="detailsEmployeePermissionType" class="w-full border border-gray-300 rounded-lg px-3 py-2">
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">AHV Number</label>
                <input type="text" name="ahv_number" id="detailsEmployeeAhvNumber" class="w-full border border-gray-300 rounded-lg px-3 py-2">
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  <?= htmlspecialchars($langText['phone'] ?? 'Telefone', ENT_QUOTES); ?>
                </label>
                <input type="tel" name="phone" id="detailsEmployeePhone" class="w-full border border-gray-300 rounded-lg px-3 py-2">
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Religião</label>
                <input type="text" name="religion" id="detailsEmployeeReligion" class="w-full border border-gray-300 rounded-lg px-3 py-2">
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Marital Status</label>
                <select name="marital_status" id="detailsEmployeeMaritalStatus" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                  <option value="single"><?= htmlspecialchars($langText['single'] ?? 'Solteiro', ENT_QUOTES); ?></option>
                  <option value="married"><?= htmlspecialchars($langText['married'] ?? 'Casado', ENT_QUOTES); ?></option>
                  <option value="divorced"><?= htmlspecialchars($langText['divorced'] ?? 'Divorciado', ENT_QUOTES); ?></option>
                  <option value="widowed">Viúvo</option>
                </select>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Data Início</label>
                <input type="date" name="start_date" id="detailsEmployeeStartDate" class="w-full border border-gray-300 rounded-lg px-3 py-2">
              </div>
            </div>
          </div>
          
          <div class="mt-6">
            <label class="block text-sm font-medium text-gray-700 mb-1">About Me</label>
            <textarea name="about" id="detailsEmployeeAbout" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2"></textarea>
          </div>
        </form>
      </div>

      <!-- DOCUMENTS TAB -->
      <div id="tab-documents-details" class="tab-panel hidden">
        <div class="p-6">
          <h3 class="text-lg font-semibold mb-4">Documentos do Funcionário</h3>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <!-- Passaporte -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                <?= htmlspecialchars($langText['passport'] ?? 'Passaporte', ENT_QUOTES); ?>
              </label>
              <input type="file" 
                    name="passport" 
                    id="detailsEmployeePassport"
                    accept="image/*,.pdf"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2">
              <div id="viewPassport" class="mt-2 hidden">
                <img id="imgPassport" class="max-w-xs rounded" alt="Passaporte">
                <a id="linkPassport" class="block text-blue-600 text-sm mt-1" target="_blank">Ver documento</a>
              </div>
            </div>

            <!-- Permissão Frente -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                <?= htmlspecialchars($langText['permission_photo_front'] ?? 'Permissão (Frente)', ENT_QUOTES); ?>
              </label>
              <input type="file" 
                    name="permission_photo_front" 
                    id="detailsEmployeePermissionFront"
                    accept="image/*,.pdf"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2">
              <div id="viewPermissionPhotoFront" class="mt-2 hidden">
                <img id="imgPermissionPhotoFront" class="max-w-xs rounded" alt="Permissão Frente">
                <a id="linkPermissionPhotoFront" class="block text-blue-600 text-sm mt-1" target="_blank">Ver documento</a>
              </div>
            </div>

            <!-- Permissão Verso -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                <?= htmlspecialchars($langText['permission_photo_back'] ?? 'Permissão (Verso)', ENT_QUOTES); ?>
              </label>
              <input type="file" 
                    name="permission_photo_back" 
                    id="detailsEmployeePermissionBack"
                    accept="image/*,.pdf"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2">
              <div id="viewPermissionPhotoBack" class="mt-2 hidden">
                <img id="imgPermissionPhotoBack" class="max-w-xs rounded" alt="Permissão Verso">
                <a id="linkPermissionPhotoBack" class="block text-blue-600 text-sm mt-1" target="_blank">Ver documento</a>
              </div>
            </div>

            <!-- Cartão de Saúde Frente -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                <?= htmlspecialchars($langText['health_card_front'] ?? 'Cartão de Saúde (Frente)', ENT_QUOTES); ?>
              </label>
              <input type="file" 
                    name="health_card_front" 
                    id="detailsEmployeeHealthCardFront"
                    accept="image/*,.pdf"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2">
              <div id="viewHealthCardFront" class="mt-2 hidden">
                <img id="imgHealthCardFront" class="max-w-xs rounded" alt="Cartão Saúde Frente">
                <a id="linkHealthCardFront" class="block text-blue-600 text-sm mt-1" target="_blank">Ver documento</a>
              </div>
            </div>

            <!-- Cartão de Saúde Verso -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                <?= htmlspecialchars($langText['health_card_back'] ?? 'Cartão de Saúde (Verso)', ENT_QUOTES); ?>
              </label>
              <input type="file" 
                    name="health_card_back" 
                    id="detailsEmployeeHealthCardBack"
                    accept="image/*,.pdf"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2">
              <div id="viewHealthCardBack" class="mt-2 hidden">
                <img id="imgHealthCardBack" class="max-w-xs rounded" alt="Cartão Saúde Verso">
                <a id="linkHealthCardBack" class="block text-blue-600 text-sm mt-1" target="_blank">Ver documento</a>
              </div>
            </div>

            <!-- Cartão Bancário Frente -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                <?= htmlspecialchars($langText['bank_card_front'] ?? 'Cartão Bancário (Frente)', ENT_QUOTES); ?>
              </label>
              <input type="file" 
                    name="bank_card_front" 
                    id="detailsEmployeeBankCardFront"
                    accept="image/*,.pdf"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2">
              <div id="viewBankCardFront" class="mt-2 hidden">
                <img id="imgBankCardFront" class="max-w-xs rounded" alt="Cartão Bancário Frente">
                <a id="linkBankCardFront" class="block text-blue-600 text-sm mt-1" target="_blank">Ver documento</a>
              </div>
            </div>

            <!-- Cartão Bancário Verso -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                <?= htmlspecialchars($langText['bank_card_back'] ?? 'Cartão Bancário (Verso)', ENT_QUOTES); ?>
              </label>
              <input type="file" 
                    name="bank_card_back" 
                    id="detailsEmployeeBankCardBack"
                    accept="image/*,.pdf"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2">
              <div id="viewBankCardBack" class="mt-2 hidden">
                <img id="imgBankCardBack" class="max-w-xs rounded" alt="Cartão Bancário Verso">
                <a id="linkBankCardBack" class="block text-blue-600 text-sm mt-1" target="_blank">Ver documento</a>
              </div>
            </div>

            <!-- Certidão de Casamento -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                <?= htmlspecialchars($langText['marriage_certificate'] ?? 'Certidão de Casamento', ENT_QUOTES); ?>
              </label>
              <input type="file" 
                    name="marriage_certificate" 
                    id="detailsEmployeeMarriageCertificate"
                    accept="image/*,.pdf"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2">
              <div id="viewMarriageCertificate" class="mt-2 hidden">
                <img id="imgMarriageCertificate" class="max-w-xs rounded" alt="Certidão Casamento">
                <a id="linkMarriageCertificate" class="block text-blue-600 text-sm mt-1" target="_blank">Ver documento</a>
              </div>
            </div>

          </div>

          <div class="mt-6 p-4 bg-yellow-50 rounded-lg">
            <p class="text-sm text-yellow-800">
              <strong>Nota:</strong> Os documentos são salvos automaticamente quando você clica em "Salvar Alterações". 
              Formatos aceitos: JPG, PNG, PDF (máximo 5MB por arquivo).
            </p>
          </div>
        </div>
      </div>

      <!-- ACCESS TAB -->
      <div id="tab-access-details" class="tab-panel hidden">
        <div class="p-6">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
              <input type="email" name="email" id="detailsLoginEmail" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Nível de Acesso</label>
              <select name="role" id="detailsEmployeeRoleId" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                <option value="employee">Funcionário</option>
                <option value="admin">Administrador</option>
                <option value="finance">Financeiro</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Nova Senha (deixe em branco para manter)</label>
              <input type="password" name="new_password" id="detailsLoginPassword" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>
          </div>
        </div>
      </div>

      <!-- TRANSACTIONS TAB -->
      <div id="tab-transactions-details" class="tab-panel hidden">
        <div class="p-6">
          <p class="text-gray-600">Transações do funcionário...</p>
        </div>
      </div>

      <!-- WORK HOURS TAB -->
      <div id="tab-work-hours" class="tab-panel hidden">
        <div class="p-6">
          <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-semibold text-gray-900">
              <?= htmlspecialchars($langText['work_hours'] ?? 'Horas de Trabalho', ENT_QUOTES); ?>
            </h3>
            <div class="text-2xl font-bold text-blue-600" id="employeeModalTotalHours">0.00h</div>
          </div>

          <!-- Filtros -->
          <div class="flex space-x-2 mb-6">
            <button type="button" id="adminFilterall" class="px-4 py-2 rounded-lg text-sm font-medium bg-blue-100 text-blue-700">
              Hoje
            </button>
            <button type="button" id="adminFilterweek" class="px-4 py-2 rounded-lg text-sm font-medium bg-gray-100 text-gray-700 hover:bg-gray-200">
              Esta Semana
            </button>
            <button type="button" id="adminFiltermonth" class="px-4 py-2 rounded-lg text-sm font-medium bg-gray-100 text-gray-700 hover:bg-gray-200">
              Este Mês
            </button>
            <button type="button" id="adminFilterperiod" class="px-4 py-2 rounded-lg text-sm font-medium bg-gray-100 text-gray-700 hover:bg-gray-200">
              Todo Período
            </button>
          </div>

          <!-- Formulário de registro de ponto -->
          <div class="bg-gray-50 rounded-lg p-4 mb-6">
            <h5 class="font-medium text-gray-900 mb-3">
              <?= htmlspecialchars($langText['register_time_entry'] ?? 'Registrar Ponto', ENT_QUOTES); ?>
            </h5>
            
            <form id="timeTrackingForm" method="POST" action="<?= BASE_URL ?>/api/work_logs/admin_time_entry">
              <input type="hidden" id="timeTrackingEmployeeId" name="employee_id" value="">
              
              <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    Projeto <span class="text-red-500">*</span>
                  </label>
                  <select id="timeTrackingProject" 
                          name="project_id"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                          required>
                    <option value="">Carregando projetos...</option>
                  </select>
                </div>
                
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    <?= htmlspecialchars($langText['input_date_label'] ?? 'Data', ENT_QUOTES); ?>
                  </label>
                  <input type="date" 
                         id="timeTrackingDate" 
                         name="date"
                         class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                         value="<?= date('Y-m-d') ?>"
                         required>
                </div>
                
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    <?= htmlspecialchars($langText['input_time_label'] ?? 'Horário', ENT_QUOTES); ?>
                  </label>
                  <input type="time" 
                         id="timeTrackingTime" 
                         name="time"
                         class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                         value="<?= date('H:i') ?>"
                         required>
                </div>
                
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    <?= htmlspecialchars($langText['entry_type'] ?? 'Tipo', ENT_QUOTES); ?>
                  </label>
                  <select id="timeTrackingType" 
                          name="entry_type" 
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                          required>
                    <option value="entry"><?= htmlspecialchars($langText['entry'] ?? 'Entrada', ENT_QUOTES); ?></option>
                    <option value="exit"><?= htmlspecialchars($langText['exit'] ?? 'Saída', ENT_QUOTES); ?></option>
                  </select>
                </div>
                
                <div class="flex items-end">
                  <button type="submit" 
                          class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <?= htmlspecialchars($langText['button_register_entry'] ?? 'Registrar', ENT_QUOTES); ?>
                  </button>
                </div>
              </div>
            </form>
          </div>

          <!-- Registro de Horas -->
          <div>
            <h5 class="font-medium text-gray-900 mb-3">Registro de Horas</h5>
            <div id="employeeHoursList" class="bg-white rounded-lg border">
              <div class="p-8 text-center">
                <div class="text-sm text-gray-500">
                  <?= htmlspecialchars($langText['loading_hours'] ?? 'Carregando registros de horas...', ENT_QUOTES); ?>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Footer -->
    <div class="flex items-center justify-between px-6 py-4 bg-gray-50 border-t border-gray-200">
      <div class="flex space-x-3">
        <button type="button" class="closeEmployeeDetailsModal px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
          <?= htmlspecialchars($langText['cancel'] ?? 'Cancelar', ENT_QUOTES); ?>
        </button>
      </div>
      
      <div class="flex space-x-3">
        <button type="button" id="saveEmployee" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
          <?= htmlspecialchars($langText['save_changes'] ?? 'Salvar Alterações', ENT_QUOTES); ?>
        </button>
        <button type="button" id="deleteEmployeeBtn" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">
          <?= htmlspecialchars($langText['delete'] ?? 'Excluir', ENT_QUOTES); ?>
        </button>
      </div>
    </div>
  </div>
</div>

<script src="<?= asset('js/employees.js') ?>" defer></script>
<script defer src="<?= asset('js/header.js') ?>"></script>