<?php
// app/views/employees/index.php
require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../../../config/Database.php';

$pdo     = Database::connect();
$baseUrl = '/ams-malergeschaft/public';
?>
<script>window.baseUrl = '<?= $baseUrl ?>';</script>

<?php
$employees = $pdo
    ->query("SELECT * FROM employees ORDER BY name ASC")
    ->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="ml-56 pt-20 p-4">
  <h1 class="text-2xl font-bold mb-6">
    <?= htmlspecialchars($langText['employees_list'] ?? 'Employees List', ENT_QUOTES, 'UTF-8') ?>
  </h1>

  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if (empty($employees)): ?>
      <p><?= htmlspecialchars($langText['no_employees_available'] ?? 'No employees available.', ENT_QUOTES, 'UTF-8') ?></p>
    <?php else: ?>
      <?php foreach ($employees as $emp): ?>
        <?php
          $start  = new DateTime($emp['start_date']);
          $now    = new DateTime();
          $diff   = $start->diff($now);
          $years  = $diff->y;
          $months = $diff->m;
          $tenure = trim(
            ($years   ? "$years " . ($years == 1 ? ($langText['year']   ?? 'year')   : ($langText['years']   ?? 'years'))   : '') .
            ($months  ? " $months " . ($months == 1 ? ($langText['month']  ?? 'month')  : ($langText['months']  ?? 'months'))  : '')
          );
        ?>
        <div
          class="employee-card bg-white p-4 rounded-lg shadow hover:shadow-md transition cursor-pointer"
          data-id="<?= htmlspecialchars($emp['id'], ENT_QUOTES, 'UTF-8') ?>"
        >
          <div class="flex items-center">
            <div class="w-20 h-20 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0">
              <?php if ($emp['profile_picture']): ?>
                <img
                  src="<?= $baseUrl ?>/employees/serveDocument?id=<?= $emp['id'] ?>&type=profile_picture"
                  alt="Foto de <?= htmlspecialchars($emp['name'], ENT_QUOTES, 'UTF-8') ?>"
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
                <?= htmlspecialchars($emp['name'] . ' ' . $emp['last_name'], ENT_QUOTES, 'UTF-8') ?>
              </h2>
              <p class="text-sm text-gray-600">
                <?= htmlspecialchars($langText['role'] ?? 'Role', ENT_QUOTES, 'UTF-8') ?>:
                <strong><?= htmlspecialchars($emp['role'], ENT_QUOTES, 'UTF-8') ?></strong>
              </p>
              <p class="text-sm text-gray-600">
                <?= htmlspecialchars($langText['time_in_company'] ?? 'Time in Company', ENT_QUOTES, 'UTF-8') ?>:
                <strong><?= $tenure ?: '0 ' . htmlspecialchars($langText['months'] ?? 'months', ENT_QUOTES, 'UTF-8') ?></strong>
              </p>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <button id="addEmployeeBtn"
          class="fixed bottom-8 right-8 bg-green-500 text-white rounded-full p-4 shadow-lg hover:bg-green-600">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
    </svg>
  </button>
</div>

<!-- Modal de Criação -->
<div id="employeeModal"
     class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
  <div class="bg-white rounded-md p-8 w-11/12 max-h-[80vh] overflow-y-auto" style="max-width:800px;">
    <button id="closeEmployeeModal"
            class="absolute top-4 right-4 text-gray-700 text-2xl">&times;</button>
    <h2 class="text-2xl font-bold mb-4">
      <?= htmlspecialchars($langText['create_employee'] ?? 'Create Employee', ENT_QUOTES, 'UTF-8') ?>
    </h2>

    <nav class="mb-6">
      <ul class="flex space-x-4 border-b">
        <li>
          <button type="button"
                  class="tab-btn border-b-2 pb-2 border-blue-500 text-blue-600"
                  data-tab="general-create">
            <?= htmlspecialchars($langText['general'] ?? 'Geral', ENT_QUOTES, 'UTF-8') ?>
          </button>
        </li>
        <li>
          <button type="button"
                  class="tab-btn border-b-2 pb-2 border-transparent text-gray-600 hover:text-gray-800"
                  data-tab="documents-create">
            <?= htmlspecialchars($langText['documents'] ?? 'Documents', ENT_QUOTES, 'UTF-8') ?>
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
              <label class="block mb-2">
                <?= htmlspecialchars($langText[$field] ?? $label, ENT_QUOTES, 'UTF-8') ?>
              </label>
              <?php if ($field==='sex' || $field==='marital_status'): ?>
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
              <?php elseif ($field==='birth_date' || $field==='start_date'): ?>
                <input type="date" name="<?= $field ?>" required class="w-full border p-2 rounded">
              <?php else: ?>
                <input type="<?= $field==='email'?'email':'text' ?>"
                       name="<?= $field ?>"
                       <?= in_array($field,['name','last_name','birth_date','role','start_date'])?'required':''?>
                       class="w-full border p-2 rounded">
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
          </div>

          <div class="mt-4">
            <label class="block mb-2">
              <?= htmlspecialchars($langText['about_me'] ?? 'About Me', ENT_QUOTES, 'UTF-8') ?>
            </label>
            <textarea name="about" class="w-full border p-2 rounded"></textarea>
          </div>
        </div>

        <!-- Documentos -->
        <div id="panel-documents-create" class="tab-panel hidden">
          <h3 class="text-lg font-semibold mb-2">
            <?= htmlspecialchars($langText['documents'] ?? 'Documents', ENT_QUOTES, 'UTF-8') ?>
          </h3>
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
              <label class="block mb-2">
                <?= htmlspecialchars($langText[$field] ?? $label, ENT_QUOTES, 'UTF-8') ?>
              </label>
              <input type="file" name="<?= $field ?>" class="w-full border p-2 rounded">
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div class="flex justify-end mt-6 space-x-2">
        <button type="button"
                id="closeEmployeeModal"
                class="px-4 py-2 border rounded">
          <?= htmlspecialchars($langText['cancel'] ?? 'Cancel', ENT_QUOTES, 'UTF-8') ?>
        </button>
        <button type="submit"
                class="bg-blue-500 text-white px-4 py-2 rounded">
          <?= htmlspecialchars($langText['submit'] ?? 'Submit', ENT_QUOTES, 'UTF-8') ?>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal de Detalhes / Edição -->
<div id="employeeDetailsModal"
     class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
  <div class="bg-white rounded-md p-8 w-11/12 max-h-[80vh] overflow-y-auto" style="max-width:800px;">
    <div class="flex justify-between mb-4">
      <h2 class="text-2xl font-bold">
        <?= htmlspecialchars($langText['employee_details'] ?? 'Employee Details', ENT_QUOTES, 'UTF-8') ?>
      </h2>
      <button type="button"
              class="closeEmployeeDetailsModal text-gray-500 hover:text-gray-700">&times;</button>
    </div>

    <nav class="mb-6">
      <ul class="flex space-x-4 border-b">
        <li>
          <button type="button"
                  class="tab-btn border-b-2 pb-2 border-blue-500 text-blue-600"
                  data-tab="general-details">
            <?= htmlspecialchars($langText['general'] ?? 'Geral', ENT_QUOTES, 'UTF-8') ?>
          </button>
        </li>
        <li>
          <button type="button"
                  class="tab-btn border-b-2 pb-2 border-transparent text-gray-600 hover:text-gray-800"
                  data-tab="documents-details">
            <?= htmlspecialchars($langText['documents'] ?? 'Documents', ENT_QUOTES, 'UTF-8') ?>
          </button>
        </li>
      </ul>
    </nav>

    <form id="employeeDetailsForm"
          action="<?= $baseUrl ?>/employees/update"
          method="POST"
          enctype="multipart/form-data">
      <input type="hidden" name="id" id="detailsEmployeeId">

      <div class="tab-content">
        <!-- Geral -->
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
              <label class="block mb-2">
                <?= htmlspecialchars($langText[$field] ?? ucfirst(str_replace('_',' ',$field)), ENT_QUOTES, 'UTF-8') ?>
              </label>
              <?php if ($cfg['type']==='select'): ?>
                <select name="<?= $field ?>" id="<?= $cfg['id'] ?>" class="w-full border p-2 rounded">
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

        <!-- Documentos -->
        <div id="panel-documents-details" class="tab-panel hidden">
          <h3 class="text-lg font-semibold mb-2">
            <?= htmlspecialchars($langText['documents'] ?? 'Documents', ENT_QUOTES, 'UTF-8') ?>
          </h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php foreach ([
              'profile_picture'=>'viewProfilePicture',
              'passport'=>'viewPassport',
              'permission_photo_front'=>'viewPermissionPhotoFront',
              'permission_photo_back'=>'viewPermissionPhotoBack',
              'health_card_front'=>'viewHealthCardFront',
              'health_card_back'=>'viewHealthCardBack',
              'bank_card_front'=>'viewBankCardFront',
              'bank_card_back'=>'viewBankCardBack',
              'marriage_certificate'=>'viewMarriageCertificate'
            ] as $field=>$imgId): ?>
            <div id="<?= $field==='marriage_certificate'?'marriageCertificateContainer':'' ?>">
              <label class="block mb-2">
                <?= htmlspecialchars($langText[$field] ?? ucfirst(str_replace('_',' ',$field)), ENT_QUOTES, 'UTF-8') ?>
              </label>
              <img id="<?= $imgId ?>" src="" alt="" class="w-full h-32 object-cover rounded border mb-2" style="display:none">
              <input type="file" name="<?= $field ?>" class="w-full border p-2 rounded">
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div class="flex justify-between mt-6">
        <button type="button" id="deleteEmployeeBtn" class="text-red-500">
          <?= htmlspecialchars($langText['delete'] ?? 'Delete', ENT_QUOTES, 'UTF-8') ?>
        </button>
        <div class="flex items-center space-x-2">
          <button type="button" class="closeEmployeeDetailsModal px-4 py-2 border rounded">
            <?= htmlspecialchars($langText['cancel'] ?? 'Cancel', ENT_QUOTES, 'UTF-8') ?>
          </button>
          <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">
            <?= htmlspecialchars($langText['save_changes'] ?? 'Save Changes', ENT_QUOTES, 'UTF-8') ?>
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<script defer src="<?= $baseUrl ?>/js/employees.js"></script>
