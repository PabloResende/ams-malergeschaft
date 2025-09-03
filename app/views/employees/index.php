<?php
// app/views/employees/index.php - ARQUIVO COMPLETO CORRIGIDO COM NOVA ABA DE HORAS

require __DIR__.'/../layout/header.php';

// Busca funcionários - CORRIGIDO: mudando getAll() para all()
require_once __DIR__.'/../../models/Employees.php';
$employeeModel = new Employee();
$employees = $employeeModel->all();

// Busca roles para dropdown
global $pdo;
$stmt = $pdo->query('SELECT id, name FROM roles ORDER BY name ASC');
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="pt-20 px-4 py-6 sm:px-8 sm:py-8 ml-0 lg:ml-56">
  <div class="flex justify-between items-center mb-6">
    <h1 class="text-3xl font-bold">
      <?= htmlspecialchars($langText['employees'] ?? 'Funcionários', ENT_QUOTES); ?>
    </h1>
    <button id="openEmployeeModalBtn"
            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
      <?= htmlspecialchars($langText['add_employee'] ?? 'Adicionar Funcionário', ENT_QUOTES); ?>
    </button>
  </div>

  <!-- Grid de funcionários -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if (empty($employees)): ?>
      <div class="col-span-full text-center text-gray-500 py-8">
        <?= htmlspecialchars($langText['no_employees'] ?? 'Nenhum funcionário cadastrado', ENT_QUOTES); ?>
      </div>
    <?php else: ?>
      <?php foreach ($employees as $emp): ?>
        <div class="employee-card bg-white rounded-lg shadow p-6 cursor-pointer hover:shadow-lg transition-shadow"
             data-id="<?= (int) $emp['id']; ?>">
          <div class="flex items-center space-x-4">
            <div class="flex-shrink-0">
              <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center text-white font-semibold">
                <?= strtoupper(substr($emp['name'], 0, 1)); ?>
              </div>
            </div>
            <div class="flex-grow">
              <h3 class="text-lg font-semibold text-gray-900">
                <?= htmlspecialchars($emp['name'].' '.$emp['last_name'], ENT_QUOTES); ?>
              </h3>
              <p class="text-sm text-gray-600"><?= htmlspecialchars($emp['function'] ?? 'Função não definida', ENT_QUOTES); ?></p>
              <p class="text-xs text-gray-500"><?= htmlspecialchars($emp['phone'] ?? '', ENT_QUOTES); ?></p>
            </div>
            <div class="flex-shrink-0">
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                <?= $langText['active'] ?? 'Ativo'; ?>
              </span>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<!-- Modal de Criação -->
<div id="employeeCreateModal"
     class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm hidden z-50">
  <div class="bg-white rounded-2xl w-full sm:w-11/12 max-w-6xl mx-4 p-6 lg:p-10 max-h-[90vh] overflow-y-auto relative">
    <button id="closeEmployeeModal" class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
    <h2 class="text-2xl font-bold mb-4">
      <?= htmlspecialchars($langText['add_employee'] ?? 'Adicionar Funcionário', ENT_QUOTES); ?>
    </h2>

    <form id="employeeCreateForm" method="POST" action="<?= BASE_URL; ?>/employees/store" enctype="multipart/form-data">

      <!-- Abas de Criação -->
      <div class="mb-4">
        <nav class="flex space-x-1 bg-gray-100 p-1 rounded-lg">
          <button type="button" data-tab="general-create" class="tab-btn-create flex-1 py-2 px-3 text-sm font-medium rounded-md bg-white text-blue-600 shadow-sm">
            <?= htmlspecialchars($langText['general'] ?? 'Geral', ENT_QUOTES); ?>
          </button>
          <button type="button" data-tab="documents-create" class="tab-btn-create flex-1 py-2 px-3 text-sm font-medium rounded-md text-gray-600 hover:text-gray-800">
            <?= htmlspecialchars($langText['documents'] ?? 'Documentos', ENT_QUOTES); ?>
          </button>
          <button type="button" data-tab="login-create" class="tab-btn-create flex-1 py-2 px-3 text-sm font-medium rounded-md text-gray-600 hover:text-gray-800">
            <?= htmlspecialchars($langText['account'] ?? 'Acesso', ENT_QUOTES); ?>
          </button>
        </nav>
      </div>

      <div class="tab-content">
        <!-- Painel Geral -->
        <div id="panel-general-create" class="tab-panel">
          <h3 class="text-lg font-semibold mb-2">
            <?= htmlspecialchars($langText['personal_information'] ?? 'Informações Pessoais', ENT_QUOTES); ?>
          </h3>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['name'] ?? 'Nome', ENT_QUOTES); ?></label>
              <input type="text" name="name" required class="w-full border rounded-lg p-2">
            </div>
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['last_name'] ?? 'Sobrenome', ENT_QUOTES); ?></label>
              <input type="text" name="last_name" required class="w-full border rounded-lg p-2">
            </div>
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['function'] ?? 'Função', ENT_QUOTES); ?></label>
              <input type="text" name="function" class="w-full border rounded-lg p-2">
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['address'] ?? 'Endereço', ENT_QUOTES); ?></label>
              <input type="text" name="address" class="w-full border rounded-lg p-2">
            </div>
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['city'] ?? 'Cidade', ENT_QUOTES); ?></label>
              <input type="text" name="city" class="w-full border rounded-lg p-2">
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['zip_code'] ?? 'CEP', ENT_QUOTES); ?></label>
              <input type="text" name="zip_code" class="w-full border rounded-lg p-2">
            </div>
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['birth_date'] ?? 'Data Nascimento', ENT_QUOTES); ?></label>
              <input type="date" name="birth_date" class="w-full border rounded-lg p-2">
            </div>
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['nationality'] ?? 'Nacionalidade', ENT_QUOTES); ?></label>
              <input type="text" name="nationality" class="w-full border rounded-lg p-2">
            </div>
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['phone'] ?? 'Telefone', ENT_QUOTES); ?></label>
              <input type="text" name="phone" class="w-full border rounded-lg p-2">
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['permission_type'] ?? 'Tipo Permissão', ENT_QUOTES); ?></label>
              <input type="text" name="permission_type" class="w-full border rounded-lg p-2">
            </div>
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['ahv_number'] ?? 'Número AHV', ENT_QUOTES); ?></label>
              <input type="text" name="ahv_number" class="w-full border rounded-lg p-2">
            </div>
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['religion'] ?? 'Religião', ENT_QUOTES); ?></label>
              <input type="text" name="religion" class="w-full border rounded-lg p-2">
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['start_date'] ?? 'Data Início', ENT_QUOTES); ?></label>
              <input type="date" name="start_date" class="w-full border rounded-lg p-2">
            </div>
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['sex'] ?? 'Sexo', ENT_QUOTES); ?></label>
              <select name="sex" class="w-full border rounded-lg p-2">
                <option value=""><?= htmlspecialchars($langText['select'] ?? 'Selecione...', ENT_QUOTES); ?></option>
                <option value="M"><?= htmlspecialchars($langText['male'] ?? 'Masculino', ENT_QUOTES); ?></option>
                <option value="F"><?= htmlspecialchars($langText['female'] ?? 'Feminino', ENT_QUOTES); ?></option>
              </select>
            </div>
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['marital_status'] ?? 'Estado Civil', ENT_QUOTES); ?></label>
              <select name="marital_status" class="w-full border rounded-lg p-2">
                <option value=""><?= htmlspecialchars($langText['select'] ?? 'Selecione...', ENT_QUOTES); ?></option>
                <option value="single"><?= htmlspecialchars($langText['single'] ?? 'Solteiro', ENT_QUOTES); ?></option>
                <option value="married"><?= htmlspecialchars($langText['married'] ?? 'Casado', ENT_QUOTES); ?></option>
                <option value="divorced"><?= htmlspecialchars($langText['divorced'] ?? 'Divorciado', ENT_QUOTES); ?></option>
              </select>
            </div>
          </div>

          <div class="mt-4">
            <label class="block mb-2"><?= htmlspecialchars($langText['about'] ?? 'Sobre', ENT_QUOTES); ?></label>
            <textarea name="about" rows="3" class="w-full border rounded-lg p-2"></textarea>
          </div>
        </div>

        <!-- Painel Documentos -->
        <div id="panel-documents-create" class="tab-panel hidden">
          <h3 class="text-lg font-semibold mb-2"><?= htmlspecialchars($langText['documents'] ?? 'Documentos', ENT_QUOTES); ?></h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php foreach ([
              'passport' => 'Passaporte',
              'permission_photo_front' => 'Foto Permissão (Frente)',
              'permission_photo_back' => 'Foto Permissão (Verso)',
              'health_card_front' => 'Cartão Saúde (Frente)',
              'health_card_back' => 'Cartão Saúde (Verso)',
              'bank_card_front' => 'Cartão Banco (Frente)',
              'bank_card_back' => 'Cartão Banco (Verso)',
              'marriage_certificate' => 'Certidão Casamento',
            ] as $field => $label): ?>
            <div>
              <label class="block mb-2">
                <?= htmlspecialchars($langText[$field] ?? $label, ENT_QUOTES); ?>
              </label>
              <input type="file" name="<?= $field; ?>" class="w-full border rounded-lg p-2">
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Painel Login -->
        <div id="panel-login-create" class="tab-panel hidden">
          <h3 class="text-lg font-semibold mb-2">
            <?= htmlspecialchars($langText['account'] ?? 'Acesso', ENT_QUOTES); ?>
          </h3>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['email'] ?? 'Email', ENT_QUOTES); ?></label>
              <input type="email" name="email" required class="w-full border rounded-lg p-2">
            </div>
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['password'] ?? 'Senha', ENT_QUOTES); ?></label>
              <input type="password" name="password" required class="w-full border rounded-lg p-2">
            </div>
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['role'] ?? 'Nível', ENT_QUOTES); ?></label>
              <select name="role_id" required class="w-full border rounded-lg p-2">
                <option value=""><?= htmlspecialchars($langText['select_role'] ?? 'Selecione...', ENT_QUOTES); ?></option>
                <?php foreach ($roles as $r): ?>
                  <option value="<?= (int) $r['id']; ?>">
                    <?= htmlspecialchars(ucfirst($r['name']), ENT_QUOTES); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>

      </div>

      <div class="flex justify-end space-x-2 pt-4 border-t">
        <button type="button" id="cancelEmployeeModal"
                class="px-4 py-2 border rounded-lg hover:bg-gray-100">
          <?= htmlspecialchars($langText['cancel'] ?? 'Cancelar', ENT_QUOTES); ?>
        </button>
        <button type="submit"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
          <?= htmlspecialchars($langText['submit'] ?? 'Salvar', ENT_QUOTES); ?>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal de Edição/Detalhes -->
<div id="employeeDetailsModal"
     class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm hidden z-50">
  <div class="bg-white rounded-2xl w-full sm:w-11/12 max-w-6xl mx-4 p-6 lg:p-10 max-h-[90vh] overflow-y-auto relative">
    <button class="closeEmployeeDetailsModal absolute top-4 right-4 text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
    <h2 class="text-2xl font-bold mb-4">
      <?= htmlspecialchars($langText['employee_details'] ?? 'Detalhes do Funcionário', ENT_QUOTES); ?>
    </h2>

    <form id="employeeDetailsForm" method="POST" action="<?= BASE_URL; ?>/employees/update" enctype="multipart/form-data">
      <input type="hidden" id="detailsLoginUserId" name="user_id">
      <input type="hidden" id="detailsEmployeeId" name="id">

      <!-- Abas de Detalhes -->
      <div class="mb-4">
        <nav class="flex flex-wrap space-x-1 bg-gray-100 p-1 rounded-lg">
          <button type="button" data-tab="general-details" class="tab-btn flex-1 py-2 px-3 text-sm font-medium rounded-md text-gray-600 hover:text-gray-800 border-b-2 border-transparent">
            <?= htmlspecialchars($langText['general'] ?? 'Geral', ENT_QUOTES); ?>
          </button>
          <button type="button" data-tab="documents-details" class="tab-btn flex-1 py-2 px-3 text-sm font-medium rounded-md text-gray-600 hover:text-gray-800 border-b-2 border-transparent">
            <?= htmlspecialchars($langText['documents'] ?? 'Documentos', ENT_QUOTES); ?>
          </button>
          <button type="button" data-tab="login-details" class="tab-btn flex-1 py-2 px-3 text-sm font-medium rounded-md text-gray-600 hover:text-gray-800 border-b-2 border-transparent">
            <?= htmlspecialchars($langText['account'] ?? 'Acesso', ENT_QUOTES); ?>
          </button>
          <button type="button" data-tab="transactions-details" class="tab-btn flex-1 py-2 px-3 text-sm font-medium rounded-md text-gray-600 hover:text-gray-800 border-b-2 border-transparent">
            <?= htmlspecialchars($langText['transactions'] ?? 'Transações', ENT_QUOTES); ?>
          </button>
          <!-- NOVA ABA HORAS - SEGUINDO O MESMO PADRÃO -->
          <button type="button" data-tab="hours-details" class="tab-btn flex-1 py-2 px-3 text-sm font-medium rounded-md text-gray-600 hover:text-gray-800 border-b-2 border-transparent">
            <?= htmlspecialchars($langText['work_hours'] ?? 'Horas', ENT_QUOTES); ?>
          </button>
        </nav>
      </div>

      <div class="tab-content">
        <!-- Painel Geral Detalhes -->
        <div id="panel-general-details" class="tab-panel">
          <h3 class="text-lg font-semibold mb-2">
            <?= htmlspecialchars($langText['personal_information'] ?? 'Informações Pessoais', ENT_QUOTES); ?>
          </h3>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['name'] ?? 'Nome', ENT_QUOTES); ?></label>
              <input type="text" id="detailsEmployeeName" name="name" required class="w-full border rounded-lg p-2">
            </div>
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['last_name'] ?? 'Sobrenome', ENT_QUOTES); ?></label>
              <input type="text" id="detailsEmployeeLastName" name="last_name" required class="w-full border rounded-lg p-2">
            </div>
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['function'] ?? 'Função', ENT_QUOTES); ?></label>
              <input type="text" id="detailsEmployeeFunction" name="function" class="w-full border rounded-lg p-2">
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['address'] ?? 'Endereço', ENT_QUOTES); ?></label>
              <input type="text" id="detailsEmployeeAddress" name="address" class="w-full border rounded-lg p-2">
            </div>
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['city'] ?? 'Cidade', ENT_QUOTES); ?></label>
              <input type="text" id="detailsEmployeeCity" name="city" class="w-full border rounded-lg p-2">
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['zip_code'] ?? 'CEP', ENT_QUOTES); ?></label>
              <input type="text" id="detailsEmployeeZipCode" name="zip_code" class="w-full border rounded-lg p-2">
            </div>
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['birth_date'] ?? 'Data Nascimento', ENT_QUOTES); ?></label>
              <input type="date" id="detailsEmployeeBirthDate" name="birth_date" class="w-full border rounded-lg p-2">
            </div>
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['nationality'] ?? 'Nacionalidade', ENT_QUOTES); ?></label>
              <input type="text" id="detailsEmployeeNationality" name="nationality" class="w-full border rounded-lg p-2">
            </div>
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['phone'] ?? 'Telefone', ENT_QUOTES); ?></label>
              <input type="text" id="detailsEmployeePhone" name="phone" class="w-full border rounded-lg p-2">
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['permission_type'] ?? 'Tipo Permissão', ENT_QUOTES); ?></label>
              <input type="text" id="detailsEmployeePermissionType" name="permission_type" class="w-full border rounded-lg p-2">
            </div>
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['ahv_number'] ?? 'Número AHV', ENT_QUOTES); ?></label>
              <input type="text" id="detailsEmployeeAhvNumber" name="ahv_number" class="w-full border rounded-lg p-2">
            </div>
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['religion'] ?? 'Religião', ENT_QUOTES); ?></label>
              <input type="text" id="detailsEmployeeReligion" name="religion" class="w-full border rounded-lg p-2">
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['start_date'] ?? 'Data Início', ENT_QUOTES); ?></label>
              <input type="date" id="detailsEmployeeStartDate" name="start_date" class="w-full border rounded-lg p-2">
            </div>
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['sex'] ?? 'Sexo', ENT_QUOTES); ?></label>
              <select id="detailsEmployeeSex" name="sex" class="w-full border rounded-lg p-2">
                <option value=""><?= htmlspecialchars($langText['select'] ?? 'Selecione...', ENT_QUOTES); ?></option>
                <option value="M"><?= htmlspecialchars($langText['male'] ?? 'Masculino', ENT_QUOTES); ?></option>
                <option value="F"><?= htmlspecialchars($langText['female'] ?? 'Feminino', ENT_QUOTES); ?></option>
              </select>
            </div>
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['marital_status'] ?? 'Estado Civil', ENT_QUOTES); ?></label>
              <select id="detailsEmployeeMaritalStatus" name="marital_status" class="w-full border rounded-lg p-2">
                <option value=""><?= htmlspecialchars($langText['select'] ?? 'Selecione...', ENT_QUOTES); ?></option>
                <option value="single"><?= htmlspecialchars($langText['single'] ?? 'Solteiro', ENT_QUOTES); ?></option>
                <option value="married"><?= htmlspecialchars($langText['married'] ?? 'Casado', ENT_QUOTES); ?></option>
                <option value="divorced"><?= htmlspecialchars($langText['divorced'] ?? 'Divorciado', ENT_QUOTES); ?></option>
              </select>
            </div>
          </div>

          <div class="mt-4">
            <label class="block mb-2"><?= htmlspecialchars($langText['about'] ?? 'Sobre', ENT_QUOTES); ?></label>
            <textarea id="detailsEmployeeAbout" name="about" rows="4" class="w-full border rounded-lg p-2"></textarea>
          </div>
        </div>

        <!-- Painel Documentos Detalhes -->
        <div id="panel-documents-details" class="tab-panel hidden">
          <h3 class="text-lg font-semibold mb-2"><?= htmlspecialchars($langText['documents'] ?? 'Documentos', ENT_QUOTES); ?></h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php foreach ([
              'passport' => 'Passaporte',
              'permission_photo_front' => 'Foto Permissão (Frente)',
              'permission_photo_back' => 'Foto Permissão (Verso)',
              'health_card_front' => 'Cartão Saúde (Frente)',
              'health_card_back' => 'Cartão Saúde (Verso)',
              'bank_card_front' => 'Cartão Banco (Frente)',
              'bank_card_back' => 'Cartão Banco (Verso)',
              'marriage_certificate' => 'Certidão Casamento',
            ] as $field => $label): ?>
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText[$field] ?? $label, ENT_QUOTES); ?></label>
              <img id="view<?= ucfirst(str_replace('_', '', $field)); ?>" class="w-full h-32 object-cover rounded border mb-2" style="display:none;">
              <a id="link<?= ucfirst(str_replace('_', '', $field)); ?>" class="text-blue-600 underline block mb-2" target="_blank" style="display:none;"></a>
              <input type="file" name="<?= $field; ?>" class="w-full border rounded-lg p-2">
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Painel Acesso Detalhes -->
        <div id="panel-login-details" class="tab-panel hidden">
          <h3 class="text-lg font-semibold mb-2"><?= htmlspecialchars($langText['account'] ?? 'Acesso', ENT_QUOTES); ?></h3>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['email'] ?? 'Email', ENT_QUOTES); ?></label>
              <input type="email" id="detailsLoginEmail" name="email" class="w-full border rounded-lg p-2" required>
            </div>
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['new_password'] ?? 'Nova Senha', ENT_QUOTES); ?></label>
              <input type="password" id="detailsLoginPassword" name="password" class="w-full border rounded-lg p-2" placeholder="••••••••">
            </div>
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText['role'] ?? 'Nível', ENT_QUOTES); ?></label>
              <select id="detailsEmployeeRoleId" name="role_id" required class="w-full border rounded-lg p-2">
                <option value=""><?= htmlspecialchars($langText['select_role'] ?? 'Selecione...', ENT_QUOTES); ?></option>
                <?php foreach ($roles as $r): ?>
                  <option value="<?= (int) $r['id']; ?>">
                    <?= htmlspecialchars(ucfirst($r['name']), ENT_QUOTES); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>

        <!-- Painel Transações Detalhes -->
        <div id="panel-transactions-details" class="tab-panel hidden">
          <h3 class="text-lg font-semibold mb-2"><?= htmlspecialchars($langText['transactions'] ?? 'Transações', ENT_QUOTES); ?></h3>
          <div class="overflow-x-auto">
            <table class="min-w-full bg-white rounded-lg shadow">
              <thead class="bg-gray-100">
                <tr>
                  <th class="p-2 text-left text-sm font-medium text-gray-700"><?= htmlspecialchars($langText['date'] ?? 'Data', ENT_QUOTES); ?></th>
                  <th class="p-2 text-left text-sm font-medium text-gray-700"><?= htmlspecialchars($langText['type'] ?? 'Tipo', ENT_QUOTES); ?></th>
                  <th class="p-2 text-right text-sm font-medium text-gray-700"><?= htmlspecialchars($langText['amount'] ?? 'Valor', ENT_QUOTES); ?></th>
                </tr>
              </thead>
              <tbody id="empTransBody">
                <tr>
                  <td colspan="3" class="p-4 text-center text-gray-500"><?= htmlspecialchars($langText['no_transactions'] ?? 'Sem transações', ENT_QUOTES); ?></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Painel Horas de Trabalho - COPIADO EXATAMENTE DO DASHBOARD FUNCIONÁRIO -->
        <div id="panel-hours-details" class="tab-panel hidden">
          <h3 class="text-lg font-semibold mb-2"><?= htmlspecialchars($langText['work_hours'] ?? 'Horas de Trabalho', ENT_QUOTES); ?></h3>
          
          <!-- Estatísticas -->
          <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white p-4 rounded-lg shadow">
              <h3 class="text-sm font-medium text-gray-500"><?= htmlspecialchars($langText['total_hours'] ?? 'Total de Horas', ENT_QUOTES); ?></h3>
              <p id="employeeModalTotalHours" class="text-2xl font-bold text-blue-600">0.00h</p>
            </div>
          </div>

          <!-- Filtros -->
          <div class="mb-6">
            <div class="flex flex-wrap gap-2">
              <button type="button" id="adminFilterToday" class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm hover:bg-blue-200">
                <?= htmlspecialchars($langText['today'] ?? 'Hoje', ENT_QUOTES); ?>
              </button>
              <button type="button" id="adminFilterWeek" class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm hover:bg-gray-200">
                <?= htmlspecialchars($langText['this_week'] ?? 'Esta Semana', ENT_QUOTES); ?>
              </button>
              <button type="button" id="adminFilterMonth" class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm hover:bg-gray-200">
                <?= htmlspecialchars($langText['this_month'] ?? 'Este Mês', ENT_QUOTES); ?>
              </button>
              <button type="button" id="adminFilterAll" class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm hover:bg-gray-200">
                <?= htmlspecialchars($langText['all_time'] ?? 'Todo Período', ENT_QUOTES); ?>
              </button>
            </div>
          </div>

          <!-- Lista de Horas - EXATAMENTE IGUAL AO DASHBOARD DO FUNCIONÁRIO -->
          <div class="bg-white rounded-lg shadow">
            <div class="p-4 border-b border-gray-200">
              <h3 class="text-lg font-medium text-gray-900"><?= htmlspecialchars($langText['hours_log'] ?? 'Registro de Horas', ENT_QUOTES); ?></h3>
            </div>
            <div class="p-4">
              <div id="employeeHoursList" class="space-y-3 max-h-80 overflow-y-auto">
                <!-- Loading inicial -->
                <div class="flex items-center justify-center py-8">
                  <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                  <span class="ml-2 text-gray-600"><?= htmlspecialchars($langText['loading_hours'] ?? 'Carregando registros de horas...', ENT_QUOTES); ?></span>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>

      <div class="flex justify-end space-x-2 pt-4 border-t">
        <button type="button" class="closeEmployeeDetailsModal px-4 py-2 border rounded-lg hover:bg-gray-100">
          <?= htmlspecialchars($langText['cancel'] ?? 'Cancelar', ENT_QUOTES); ?>
        </button>
        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">
          <?= htmlspecialchars($langText['save_changes'] ?? 'Salvar Alterações', ENT_QUOTES); ?>
        </button>
        <button type="button" id="deleteEmployeeBtn" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg">
          <?= htmlspecialchars($langText['delete'] ?? 'Excluir', ENT_QUOTES); ?>
        </button>
      </div>
    </form>
  </div>
</div>

<script>
  window.baseUrl = '<?= BASE_URL; ?>';
  window.langText = <?= json_encode($langText, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_APOS); ?>;
  window.confirmDeleteMsg = '<?= htmlspecialchars($langText['confirm_delete_employee'] ?? 'Tem certeza que deseja excluir este funcionário?', ENT_QUOTES); ?>';
</script>
<script src="<?= BASE_URL; ?>/public/js/employees.js?v=<?= time(); ?>" defer></script>

<?php require __DIR__.'/../layout/footer.php'; ?>