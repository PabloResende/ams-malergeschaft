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
    <?= $langText['employees_list'] ?? 'Employees List' ?>
  </h1>

  <!-- Grid de cards -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if (empty($employees)): ?>
      <p><?= $langText['no_employees_available'] ?? 'No employees available.' ?></p>
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
          data-id="<?= $emp['id'] ?>"
        >
          <div class="flex items-center">
            <div class="w-20 h-20 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0">
              <?php if ($emp['profile_picture']): ?>
                <img
                  src="<?= $baseUrl ?>/employees/serveDocument?id=<?= $emp['id'] ?>&type=profile_picture"
                  alt="Foto de <?= htmlspecialchars($emp['name']) ?>"
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
                <?= htmlspecialchars($emp['name'] . ' ' . $emp['last_name']) ?>
              </h2>
              <p class="text-sm text-gray-600">
                <?= $langText['role'] ?? 'Role' ?>:
                <strong><?= htmlspecialchars($emp['role']) ?></strong>
              </p>
              <p class="text-sm text-gray-600">
                <?= $langText['time_in_company'] ?? 'Time in Company' ?>:
                <strong><?= $tenure ?: '0 ' . ($langText['months'] ?? 'months') ?></strong>
              </p>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Botão flutuante de criar -->
  <button id="addEmployeeBtn" class="fixed bottom-8 right-8 bg-green-500 text-white rounded-full p-4 shadow-lg hover:bg-green-600">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
    </svg>
  </button>
</div>

<!-- Modal de Criação -->
<div id="employeeModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
  <div class="bg-white rounded-md p-8 w-11/12 max-h-[80vh] overflow-y-auto" style="max-width:800px;">
    <h2 class="text-2xl font-bold mb-4"><?= $langText['create_employee'] ?? 'Create Employee' ?></h2>
    <form action="<?= $baseUrl ?>/employees/store" method="POST" enctype="multipart/form-data" class="space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Campos de texto -->
        <div>
          <label class="block mb-2"><?= $langText['name'] ?? 'Name' ?></label>
          <input type="text" name="name" required class="w-full border p-2 rounded">
        </div>
        <div>
          <label class="block mb-2"><?= $langText['last_name'] ?? 'Last Name' ?></label>
          <input type="text" name="last_name" required class="w-full border p-2 rounded">
        </div>
        <div>
          <label class="block mb-2"><?= $langText['address'] ?? 'Address' ?></label>
          <input type="text" name="address" class="w-full border p-2 rounded">
        </div>
        <div>
          <label class="block mb-2"><?= $langText['sex'] ?? 'Sex' ?></label>
          <select name="sex" class="w-full border p-2 rounded">
            <option value="male">Male</option>
            <option value="female">Female</option>
            <option value="other">Other</option>
          </select>
        </div>
        <div>
          <label class="block mb-2"><?= $langText['birth_date'] ?? 'Birth Date' ?></label>
          <input type="date" name="birth_date" required class="w-full border p-2 rounded">
        </div>
        <div>
          <label class="block mb-2"><?= $langText['nationality'] ?? 'Nationality' ?></label>
          <input type="text" name="nationality" class="w-full border p-2 rounded">
        </div>
        <div>
          <label class="block mb-2"><?= $langText['permission_type'] ?? 'Permission Type' ?></label>
          <input type="text" name="permission_type" class="w-full border p-2 rounded">
        </div>
        <div>
          <label class="block mb-2"><?= $langText['email'] ?? 'Email' ?></label>
          <input type="email" name="email" class="w-full border p-2 rounded">
        </div>
        <div>
          <label class="block mb-2"><?= $langText['ahv_number'] ?? 'AHV Number' ?></label>
          <input type="text" name="ahv_number" class="w-full border p-2 rounded">
        </div>
        <div>
          <label class="block mb-2"><?= $langText['phone'] ?? 'Phone' ?></label>
          <input type="text" name="phone" class="w-full border p-2 rounded">
        </div>
        <div>
          <label class="block mb-2"><?= $langText['religion'] ?? 'Religion' ?></label>
          <input type="text" name="religion" class="w-full border p-2 rounded">
        </div>
        <div>
          <label class="block mb-2"><?= $langText['marital_status'] ?? 'Marital Status' ?></label>
          <select name="marital_status" class="w-full border p-2 rounded">
            <option value="single">Single</option>
            <option value="married">Married</option>
            <option value="divorced">Divorced</option>
            <option value="widowed">Widowed</option>
          </select>
        </div>
        <div>
          <label class="block mb-2"><?= $langText['role'] ?? 'Role' ?></label>
          <input type="text" name="role" required class="w-full border p-2 rounded">
        </div>
        <div>
          <label class="block mb-2"><?= $langText['start_date'] ?? 'Start Date' ?></label>
          <input type="date" name="start_date" required class="w-full border p-2 rounded">
        </div>
      </div>

      <h3 class="text-lg font-semibold mt-4 mb-2"><?= $langText['documents'] ?? 'Documents' ?></h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Campos de arquivo -->
        <?php foreach ([
          'profile_picture'        => ($langText['profile_picture'] ?? 'Profile Picture'),
          'passport'               => ($langText['passport'] ?? 'Passport'),
          'permission_photo_front' => ($langText['permission_photo_front'] ?? 'Permission Photo (Front)'),
          'permission_photo_back'  => ($langText['permission_photo_back'] ?? 'Permission Photo (Back)'),
          'health_card_front'      => ($langText['health_card_front'] ?? 'Health Card (Front)'),
          'health_card_back'       => ($langText['health_card_back'] ?? 'Health Card (Back)'),
          'bank_card_front'        => ($langText['bank_card_front'] ?? 'Bank Card (Front)'),
          'bank_card_back'         => ($langText['bank_card_back'] ?? 'Bank Card (Back)'),
          'marriage_certificate'   => ($langText['marriage_certificate'] ?? 'Marriage Certificate'),
        ] as $field => $label): ?>
          <div>
            <label class="block mb-2"><?= $label ?></label>
            <input type="file" name="<?= $field ?>" class="w-full border p-2 rounded">
          </div>
        <?php endforeach; ?>
      </div>

      <div class="mt-4">
        <label class="block mb-2"><?= $langText['about_me'] ?? 'About Me' ?></label>
        <textarea name="about" class="w-full border p-2 rounded"></textarea>
      </div>

      <div class="flex justify-end mt-6">
        <button type="button" id="closeEmployeeModal" class="mr-2 px-4 py-2 border rounded">
          <?= $langText['cancel'] ?? 'Cancel' ?>
        </button>
        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">
          <?= $langText['submit'] ?? 'Submit' ?>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal de Detalhes / Edição -->
<div id="employeeDetailsModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
  <div class="bg-white rounded-md p-8 w-11/12 max-h-[80vh] overflow-y-auto" style="max-width:800px;">
    <div class="flex justify-between mb-4">
      <h2 class="text-2xl font-bold"><?= $langText['employee_details'] ?? 'Employee Details' ?></h2>
      <button type="button" class="closeEmployeeDetailsModal text-gray-500 hover:text-gray-700">&times;</button>
    </div>
    <form id="employeeDetailsForm" action="<?= $baseUrl ?>/employees/update" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="id" id="detailsEmployeeId">

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Mesmos campos de texto do Create, com IDs para JS -->
        <?php foreach ([
          'name'             => 'detailsEmployeeName',
          'last_name'        => 'detailsEmployeeLastName',
          'address'          => 'detailsEmployeeAddress',
          'sex'              => 'detailsEmployeeSex',
          'birth_date'       => 'detailsEmployeeBirthDate',
          'nationality'      => 'detailsEmployeeNationality',
          'permission_type'  => 'detailsEmployeePermissionType',
          'email'            => 'detailsEmployeeEmail',
          'ahv_number'       => 'detailsEmployeeAhvNumber',
          'phone'            => 'detailsEmployeePhone',
          'religion'         => 'detailsEmployeeReligion',
          'marital_status'   => 'detailsEmployeeMaritalStatus',
          'role'             => 'detailsEmployeeRole',
          'start_date'       => 'detailsEmployeeStartDate',
          'about'            => 'detailsEmployeeAbout',
        ] as $field => $elId): ?>
          <div>
            <label class="block mb-2"><?= $langText[$field] ?? ucfirst(str_replace('_',' ',$field)) ?></label>
            <?php if ($field === 'about'): ?>
              <textarea name="about" id="<?= $elId ?>" class="w-full border p-2 rounded"></textarea>
            <?php elseif ($field === 'sex' || $field === 'marital_status'): ?>
              <select name="<?= $field ?>" id="<?= $elId ?>" class="w-full border p-2 rounded">
                <?php
                  $opts = $field === 'sex'
                    ? ['male'=>'Male','female'=>'Female','other'=>'Other']
                    : ['single'=>'Single','married'=>'Married','divorced'=>'Divorced','widowed'=>'Widowed'];
                  foreach($opts as $val=>$lbl): ?>
                    <option value="<?= $val ?>"><?= $lbl ?></option>
                <?php endforeach; ?>
              </select>
            <?php elseif ($field === 'birth_date' || $field === 'start_date'): ?>
              <input type="date" name="<?= $field ?>" id="<?= $elId ?>" class="w-full border p-2 rounded">
            <?php else: ?>
              <input type="text" name="<?= $field ?>" id="<?= $elId ?>" class="w-full border p-2 rounded">
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>

      <h3 class="text-lg font-semibold mt-4 mb-2"><?= $langText['documents'] ?? 'Documents' ?></h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Previews + file inputs -->
        <?php foreach ([
          'profile_picture'        => 'viewProfilePicture',
          'passport'               => 'viewPassport',
          'permission_photo_front' => 'viewPermissionPhotoFront',
          'permission_photo_back'  => 'viewPermissionPhotoBack',
          'health_card_front'      => 'viewHealthCardFront',
          'health_card_back'       => 'viewHealthCardBack',
          'bank_card_front'        => 'viewBankCardFront',
          'bank_card_back'         => 'viewBankCardBack',
          'marriage_certificate'   => 'viewMarriageCertificate',
        ] as $field => $imgId): ?>
          <div id="<?= $field === 'marriage_certificate' ? 'marriageCertificateContainer' : '' ?>">
            <label class="block mb-2"><?= $langText[$field] ?? ucfirst(str_replace('_',' ',$field)) ?></label>
            <img id="<?= $imgId ?>" src="" alt="" class="w-full h-32 object-cover rounded border mb-2" style="display:none">
            <input type="file" name="<?= $field ?>" class="w-full border p-2 rounded">
          </div>
        <?php endforeach; ?>
      </div>

      <div class="flex justify-between mt-6">
        <button type="button" id="deleteEmployeeBtn" class="text-red-500">
          <?= $langText['delete'] ?? 'Delete' ?>
        </button>
        <div class="flex items-center">
          <button type="button" class="closeEmployeeDetailsModal mr-2 px-4 py-2 border rounded">
            <?= $langText['cancel'] ?? 'Cancel' ?>
          </button>
          <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">
            <?= $langText['save_changes'] ?? 'Save Changes' ?>
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<script defer src="<?= $baseUrl ?>/js/employees.js"></script>
