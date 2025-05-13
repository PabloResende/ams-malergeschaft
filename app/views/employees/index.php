<?php
// app/views/employees/index.php
require_once __DIR__ . '/../layout/header.php';

$pdo       = Database::connect();
$employees = $pdo->query("SELECT * FROM employees ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$baseUrl   = '/ams-malergeschaft/public';
?>
<script>window.baseUrl = '<?= $baseUrl ?>';</script>

<div class="ml-56 pt-20 p-4">
  <h1 class="text-2xl font-bold mb-6">
    <?= htmlspecialchars($langText['employees_list'] ?? 'Employees List', ENT_QUOTES) ?>
  </h1>

  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if (empty($employees)): ?>
      <p><?= htmlspecialchars($langText['no_employees_available'] ?? 'No employees available.', ENT_QUOTES) ?></p>
    <?php else: foreach ($employees as $emp):
        $start = new DateTime($emp['start_date']);
        $now   = new DateTime();
        $d     = $start->diff($now);
        $tenure = trim(
          ($d->y ? "{$d->y} " . ($d->y==1?($langText['year']   ?? 'year'):($langText['years']   ?? 'years')) : '') .
          ($d->m ? " {$d->m} " . ($d->m==1?($langText['month']  ?? 'month'):($langText['months']  ?? 'months')): '')
        );
    ?>
      <div
        class="employee-card bg-white p-4 rounded-lg shadow hover:shadow-md cursor-pointer"
        data-id="<?= $emp['id'] ?>"
      >
        <div class="flex items-center">
          <div class="w-20 h-20 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0">
            <?php if ($emp['profile_picture']): ?>
              <img
                src="<?= $baseUrl ?>/employees/serveDocument?id=<?= $emp['id'] ?>&type=profile_picture"
                class="w-full h-full object-cover"
              >
            <?php else: ?>
              <svg class="w-full h-full text-gray-300" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2s2.1 4.8 4.8 4.8zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8V22h19.2v-2.8c0-3.2-6.4-4.8-9.6-4.8z"/>
              </svg>
            <?php endif; ?>
          </div>
          <div class="ml-4">
            <h2 class="text-lg font-semibold">
              <?= htmlspecialchars($emp['name'] . ' ' . $emp['last_name'], ENT_QUOTES) ?>
            </h2>
            <p class="text-sm text-gray-600">
              <?= htmlspecialchars($langText['role'] ?? 'Role', ENT_QUOTES) ?>:
              <strong><?= htmlspecialchars($emp['role'], ENT_QUOTES) ?></strong>
            </p>
            <p class="text-sm text-gray-600">
              <?= htmlspecialchars($langText['time_in_company'] ?? 'Time in Company', ENT_QUOTES) ?>:
              <strong><?= $tenure ?: "0 ".htmlspecialchars($langText['months']??'months',ENT_QUOTES) ?></strong>
            </p>
          </div>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>

  <button id="addEmployeeBtn"
          class="fixed bottom-8 right-8 bg-green-500 text-white rounded-full p-4 shadow-lg hover:bg-green-600"
          aria-label="Add Employee">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
    </svg>
  </button>
</div>

<!-- Modal de Criação -->
<div id="employeeModal"
     class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
  <div class="bg-white rounded-md p-8 w-11/12 max-h-[80vh] overflow-y-auto relative" style="max-width:800px;">
    <button id="closeEmployeeModal"
            class="absolute top-4 right-4 text-gray-700 text-2xl">&times;</button>
    <h2 class="text-2xl font-bold mb-4">
      <?= htmlspecialchars($langText['create_employee'] ?? 'Create Employee', ENT_QUOTES) ?>
    </h2>

    <nav class="mb-6">
      <ul class="flex space-x-4 border-b">
        <li>
          <button type="button"
                  class="tab-btn border-b-2 pb-2 text-blue-600 border-blue-500"
                  data-tab="general-create">
            <?= htmlspecialchars($langText['general'] ?? 'Geral', ENT_QUOTES) ?>
          </button>
        </li>
        <li>
          <button type="button"
                  class="tab-btn border-b-2 pb-2 text-gray-600 border-transparent hover:text-gray-800"
                  data-tab="documents-create">
            <?= htmlspecialchars($langText['documents'] ?? 'Documents', ENT_QUOTES) ?>
          </button>
        </li>
      </ul>
    </nav>

    <form action="<?= $baseUrl ?>/employees/store"
          method="POST"
          enctype="multipart/form-data"
          class="space-y-4">
      <div class="tab-content">
        <!-- Geral -->
        <div id="panel-general-create" class="tab-panel">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php foreach ([
              'name'=> 'Name', 'last_name'=>'Last Name',
              'address'=>'Address', 'sex'=>'Sex',
              'birth_date'=>'Birth Date','nationality'=>'Nationality',
              'permission_type'=>'Permission Type','email'=>'Email',
              'ahv_number'=>'AHV Number','phone'=>'Phone',
              'religion'=>'Religion','marital_status'=>'Marital Status',
              'role'=>'Role','start_date'=>'Start Date'
            ] as $field=>$label): ?>
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText[$field] ?? $label, ENT_QUOTES) ?></label>
              <?php if ($field==='sex'||$field==='marital_status'): ?>
                <select name="<?= $field ?>" class="w-full border p-2 rounded">
                  <?php if ($field==='sex'): ?>
                    <option value="male"><?= $langText['male'] ?? 'Male' ?></option>
                    <option value="female"><?= $langText['female'] ?? 'Female' ?></option>
                    <option value="other"><?= $langText['other'] ?? 'Other' ?></option>
                  <?php else: ?>
                    <option value="single"><?= $langText['single'] ?? 'Single' ?></option>
                    <option value="married"><?= $langText['married'] ?? 'Married' ?></option>
                    <option value="divorced"><?= $langText['divorced'] ?? 'Divorced' ?></option>
                    <option value="widowed"><?= $langText['widowed'] ?? 'Widowed' ?></option>
                  <?php endif; ?>
                </select>
              <?php elseif (in_array($field,['birth_date','start_date'])): ?>
                <input type="date" name="<?= $field ?>" required class="w-full border p-2 rounded">
              <?php else: ?>
                <input type="<?= $field==='email'?'email':'text' ?>"
                       name="<?= $field ?>"
                       <?= in_array($field,['name','last_name','birth_date','role','start_date'])?'required':'' ?>
                       class="w-full border p-2 rounded">
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
          </div>
          <div class="mt-4">
            <label class="block mb-2"><?= htmlspecialchars($langText['about_me'] ?? 'About Me', ENT_QUOTES) ?></label>
            <textarea name="about" class="w-full border p-2 rounded"></textarea>
          </div>
        </div>

        <!-- Documentos -->
        <div id="panel-documents-create" class="tab-panel hidden">
          <h3 class="text-lg font-semibold mb-2"><?= htmlspecialchars($langText['documents'] ?? 'Documents', ENT_QUOTES) ?></h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php foreach ([
              'profile_picture'=>'Profile Picture',
              'passport'=>'Passport',
              'permission_photo_front'=>'Permission Photo (Front)',
              'permission_photo_back'=>'Permission Photo (Back)',
              'health_card_front'=>'Health Card (Front)',
              'health_card_back'=>'Health Card (Back)',
              'bank_card_front'=>'Bank Card (Front)',
              'bank_card_back'=>'Bank Card (Back)',
              'marriage_certificate'=>'Marriage Certificate'
            ] as $field=>$label): ?>
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText[$field] ?? $label, ENT_QUOTES) ?></label>
              <input type="file" name="<?= $field ?>" class="w-full border p-2 rounded">
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div class="flex justify-end mt-6 space-x-2">
        <button type="button" id="closeEmployeeModal" class="px-4 py-2 border rounded">
          <?= htmlspecialchars($langText['cancel'] ?? 'Cancel', ENT_QUOTES) ?>
        </button>
        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">
          <?= htmlspecialchars($langText['submit'] ?? 'Submit', ENT_QUOTES) ?>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal de Detalhes / Transações -->
<div id="employeeDetailsModal"
     class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
  <div class="bg-white rounded-md p-8 w-11/12 max-h-[80vh] overflow-y-auto relative" style="max-width:800px;">
    <button class="closeEmployeeDetailsModal absolute top-4 right-4 text-gray-700 text-2xl">&times;</button>
    <h2 class="text-2xl font-bold mb-4"><?= htmlspecialchars($langText['employee_details'] ?? 'Employee Details', ENT_QUOTES) ?></h2>

    <nav class="mb-6">
      <ul class="flex space-x-4 border-b">
        <li><button class="tab-btn border-b-2 pb-2 text-blue-600 border-blue-500" data-tab="general-details">
          <?= htmlspecialchars($langText['general'] ?? 'Geral', ENT_QUOTES) ?></button></li>
        <li><button class="tab-btn border-b-2 pb-2 text-gray-600 border-transparent hover:text-gray-800" data-tab="transactions-details">
          <?= htmlspecialchars($langText['transactions'] ?? 'Transações', ENT_QUOTES) ?></button></li>
      </ul>
    </nav>

    <form id="employeeDetailsForm" action="<?= $baseUrl ?>/employees/update" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="id" id="detailsEmployeeId">

      <div class="tab-content">
        <div id="panel-general-details" class="tab-panel">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php foreach ([
              'name'=> ['id'=>'detailsEmployeeName','type'=>'text'],
              'last_name'=> ['id'=>'detailsEmployeeLastName','type'=>'text'],
              'address'=> ['id'=>'detailsEmployeeAddress','type'=>'text'],
              'sex'=> ['id'=>'detailsEmployeeSex','type'=>'select'],
              'birth_date'=> ['id'=>'detailsEmployeeBirthDate','type'=>'date'],
              'nationality'=> ['id'=>'detailsEmployeeNationality','type'=>'text'],
              'permission_type'=> ['id'=>'detailsEmployeePermissionType','type'=>'text'],
              'email'=> ['id'=>'detailsEmployeeEmail','type'=>'email'],
              'ahv_number'=> ['id'=>'detailsEmployeeAhvNumber','type'=>'text'],
              'phone'=> ['id'=>'detailsEmployeePhone','type'=>'text'],
              'religion'=> ['id'=>'detailsEmployeeReligion','type'=>'text'],
              'marital_status'=> ['id'=>'detailsEmployeeMaritalStatus','type'=>'select'],
              'role'=> ['id'=>'detailsEmployeeRole','type'=>'text'],
              'start_date'=> ['id'=>'detailsEmployeeStartDate','type'=>'date'],
              'about'=> ['id'=>'detailsEmployeeAbout','type'=>'textarea'],
            ] as $field=>$cfg): ?>
            <div>
              <label class="block mb-2"><?= htmlspecialchars($langText[$field] ?? ucfirst(str_replace('_',' ',$field)), ENT_QUOTES) ?></label>
              <?php if ($cfg['type']==='select'): ?>
                <select name="<?= $field ?>" id="<?= $cfg['id'] ?>" class="w-full border p-2 rounded">
                  <?php if ($field==='sex'): ?>
                    <option value="male"><?= $langText['male']   ?? 'Male' ?></option>
                    <option value="female"><?= $langText['female'] ?? 'Female' ?></option>
                    <option value="other"><?= $langText['other']  ?? 'Other' ?></option>
                  <?php else: ?>
                    <option value="single"><?= $langText['single']   ?? 'Single' ?></option>
                    <option value="married"><?= $langText['married'] ?? 'Married' ?></option>
                    <option value="divorced"><?= $langText['divorced'] ?? 'Divorced' ?></option>
                    <option value="widowed"><?= $langText['widowed'] ?? 'Widowed' ?></option>
                  <?php endif; ?>
                </select>
              <?php elseif ($cfg['type']==='textarea'): ?>
                <textarea name="about" id="<?= $cfg['id'] ?>" class="w-full border p-2 rounded"></textarea>
              <?php else: ?>
                <input type="<?= $cfg['type'] ?>"
                       name="<?= $field ?>"
                       id="<?= $cfg['id'] ?>"
                       class="w-full border p-2 rounded">
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div id="panel-transactions-details" class="tab-panel hidden">
          <h3 class="text-lg font-semibold mb-2"><?= htmlspecialchars($langText['employee_transactions'] ?? 'Transações do Funcionário', ENT_QUOTES) ?></h3>
          <div class="overflow-x-auto">
            <table class="w-full bg-white rounded shadow">
              <thead class="bg-gray-100">
                <tr>
                  <th class="p-2 text-left"><?= htmlspecialchars($langText['date']   ?? 'Data', ENT_QUOTES) ?></th>
                  <th class="p-2 text-left"><?= htmlspecialchars($langText['type']   ?? 'Tipo', ENT_QUOTES) ?></th>
                  <th class="p-2 text-right"><?= htmlspecialchars($langText['amount'] ?? 'Valor',ENT_QUOTES) ?></th>
                </tr>
              </thead>
              <tbody id="empTransBody">
                <!-- JS preencherá -->
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="flex justify-between mt-6">
        <button type="button" id="deleteEmployeeBtn" class="text-red-500">
          <?= htmlspecialchars($langText['delete'] ?? 'Delete', ENT_QUOTES) ?>
        </button>
        <div class="flex space-x-2">
          <button type="button" class="closeEmployeeDetailsModal px-4 py-2 border rounded">
            <?= htmlspecialchars($langText['cancel'] ?? 'Cancel', ENT_QUOTES) ?>
          </button>
          <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded">
            <?= htmlspecialchars($langText['save_changes'] ?? 'Save Changes', ENT_QUOTES) ?>
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<script defer src="<?= $baseUrl ?>/js/employees.js"></script>
