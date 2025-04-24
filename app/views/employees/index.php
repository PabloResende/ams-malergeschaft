<?php
// app/views/employees/index.php
require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../../../config/Database.php';

$pdo     = Database::connect();
$baseUrl = '/ams-malergeschaft/public';

$employees = $pdo
    ->query("SELECT * FROM employees ORDER BY name ASC")
    ->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="ml-56 pt-20 p-4">
  <h1 class="text-2xl font-bold mb-6"><?= $langText['employees_list'] ?? 'Employees List' ?></h1>

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
            ($years   ? "$years " . ($years   == 1 ? ($langText['year']   ?? 'year')   : ($langText['years']   ?? 'years'))   : '') .
            ($months  ? " $months " . ($months  == 1 ? ($langText['month']  ?? 'month')  : ($langText['months']  ?? 'months'))  : '')
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
                  src="<?= $baseUrl ?>/employees/document?id=<?= $emp['id'] ?>&type=profile_picture"
                  alt=""
                  class="w-full h-full object-cover"
                >
              <?php else: ?>
                <svg class="w-full h-full text-gray-300" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2s2.1 4.8 4.8 4.8zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8V22h19.2v-2.8c0-3.2-6.4-4.8-9.6-4.8z"/>
                </svg>
              <?php endif; ?>
            </div>
            <div class="ml-4">
              <h2 class="text-lg font-semibold"><?= htmlspecialchars($emp['name'] . ' ' . $emp['last_name']) ?></h2>
              <p class="text-sm text-gray-600">
                <?= $langText['role'] ?? 'Role' ?>: <strong><?= htmlspecialchars($emp['role']) ?></strong>
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
  <div class="bg-white rounded-md p-8 w-90 max-h-[80vh] overflow-y-auto" style="width:90%;max-width:800px;">
    <h2 class="text-2xl font-bold mb-4"><?= $langText['create_employee'] ?? 'Create Employee' ?></h2>
    <form action="<?= $baseUrl ?>/employees/store" method="POST" enctype="multipart/form-data" class="space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
        <div>
          <label class="block mb-2"><?= $langText['profile_picture'] ?? 'Profile Picture' ?></label>
          <input type="file" name="profile_picture" class="w-full border p-2 rounded">
        </div>
        <div>
          <label class="block mb-2"><?= $langText['passport'] ?? 'Passport' ?></label>
          <input type="file" name="passport" class="w-full border p-2 rounded">
        </div>
        <div>
          <label class="block mb-2"><?= $langText['permission_photo_front'] ?? 'Permission Photo (Front)' ?></label>
          <input type="file" name="permission_photo_front" class="w-full border p-2 rounded">
        </div>
        <div>
          <label class="block mb-2"><?= $langText['permission_photo_back'] ?? 'Permission Photo (Back)' ?></label>
          <input type="file" name="permission_photo_back" class="w-full border p-2 rounded">
        </div>
        <div>
          <label class="block mb-2"><?= $langText['health_card_front'] ?? 'Health Card (Front)' ?></label>
          <input type="file" name="health_card_front" class="w-full border p-2 rounded">
        </div>
        <div>
          <label class="block mb-2"><?= $langText['health_card_back'] ?? 'Health Card (Back)' ?></label>
          <input type="file" name="health_card_back" class="w-full border p-2 rounded">
        </div>
        <div>
          <label class="block mb-2"><?= $langText['bank_card_front'] ?? 'Bank Card (Front)' ?></label>
          <input type="file" name="bank_card_front" class="w-full border p-2 rounded">
        </div>
        <div>
          <label class="block mb-2"><?= $langText['bank_card_back'] ?? 'Bank Card (Back)' ?></label>
          <input type="file" name="bank_card_back" class="w-full border p-2 rounded">
        </div>
        <div>
          <label class="block mb-2"><?= $langText['marriage_certificate'] ?? 'Marriage Certificate' ?></label>
          <input type="file" name="marriage_certificate" class="w-full border p-2 rounded">
        </div>
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
  <div class="bg-white rounded-md p-8 w-90 max-h-[80vh] overflow-y-auto" style="width:90%;max-width:800px;">
    <div class="flex justify-between mb-4">
      <h2 class="text-2xl font-bold"><?= $langText['employee_details'] ?? 'Employee Details' ?></h2>
      <button type="button" class="closeEmployeeDetailsModal text-gray-500 hover:text-gray-700">&times;</button>
    </div>
    <form id="employeeDetailsForm" action="<?= $baseUrl ?>/employees/update" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="id" id="detailsEmployeeId">

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block mb-2"><?= $langText['name'] ?? 'Name' ?></label>
          <input type="text" name="name" id="detailsEmployeeName" class="w-full border p-2 rounded">
        </div>
        <div>
          <label class="block mb-2"><?= $langText['last_name'] ?? 'Last Name' ?></label>
          <input type="text" name="last_name" id="detailsEmployeeLastName" class="w-full border p-2 rounded">
        </div>
        <div>
          <label class="block mb-2"><?= $langText['address'] ?? 'Address' ?></label>
          <input type="text" name="address" id="detailsEmployeeAddress" class="w-full border p-2 rounded">
        </div>
        <div>
          <label class="block mb-2"><?= $langText['sex'] ?? 'Sex' ?></label>
          <select name="sex" id="detailsEmployeeSex" class="w-full border p-2 rounded">
            <option value="male">Male</option>
            <option value="female">Female</option>
            <option value="other">Other</option>
          </select>
        </div>
        <div>
          <label class="block mb-2"><?= $langText['birth_date'] ?? 'Birth Date' ?></label>
          <input type="date" name="birth_date" id="detailsEmployeeBirthDate" class="w-full border p-2 rounded">
        </div>
        <div>
          <label class="block mb-2"><?= $langText['nationality'] ?? 'Nationality' ?></label>
          <input type="text" name="nationality" id="detailsEmployeeNationality" class="w-full border p-2 rounded">
        </div>
        <div>
          <label class="block mb-2"><?= $langText['permission_type'] ?? 'Permission Type' ?></label>
          <input type="text" name="permission_type" id="detailsEmployeePermissionType" class="w-full border p-2 rounded">
        </div>
        <div>
          <label class="block mb-2"><?= $langText['email'] ?? 'Email' ?></label>
          <input type="email" name="email" id="detailsEmployeeEmail" class="w-full border p-2 rounded">
        </div>
        <div>
          <label class="block mb-2"><?= $langText['ahv_number'] ?? 'AHV Number' ?></label>
          <input type="text" name="ahv_number" id="detailsEmployeeAhvNumber" class="w-full border p-2 rounded">
        </div>
        <div>
          <label class="block mb-2"><?= $langText['phone'] ?? 'Phone' ?></label>
          <input type="text" name="phone" id="detailsEmployeePhone" class="w-full border p-2 rounded">
        </div>
        <div>
          <label class="block mb-2"><?= $langText['religion'] ?? 'Religion' ?></label>
          <input type="text" name="religion" id="detailsEmployeeReligion" class="w-full border p-2 rounded">
        </div>
        <div>
          <label class="block mb-2"><?= $langText['marital_status'] ?? 'Marital Status' ?></label>
          <select name="marital_status" id="detailsEmployeeMaritalStatus" class="w-full border p-2 rounded">
            <option value="single">Single</option>
            <option value="married">Married</option>
            <option value="divorced">Divorced</option>
            <option value="widowed">Widowed</option>
          </select>
        </div>
        <div>
          <label class="block mb-2"><?= $langText['role'] ?? 'Role' ?></label>
          <input type="text" name="role" id="detailsEmployeeRole" class="w-full border p-2 rounded">
        </div>
        <div>
          <label class="block mb-2"><?= $langText['start_date'] ?? 'Start Date' ?></label>
          <input type="date" name="start_date" id="detailsEmployeeStartDate" class="w-full border p-2 rounded">
        </div>
      </div>

      <h3 class="text-lg font-semibold mt-4 mb-2"><?= $langText['documents'] ?? 'Documents' ?></h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block mb-2"><?= $langText['profile_picture'] ?? 'Profile Picture' ?></label>
          <img id="viewProfilePicture" src="" alt="" class="w-full h-32 object-cover rounded border mb-2">
          <input type="file" name="profile_picture" class="w-full border p-2 rounded">
        </div>
        <div>
          <label class="block mb-2"><?= $langText['passport'] ?? 'Passport' ?></label>
          <img id="viewPassport" src="" alt="" class="w-full h-32 object-cover rounded border mb-2">
          <input type="file" name="passport" class="w-full border p-2 rounded">
        </div>
        <div>
          <label class="block mb-2"><?= $langText['permission_photo_front'] ?? 'Permission Photo (Front)' ?></label>
          <img id="viewPermissionPhotoFront" src="" alt="" class="w-full h-32 object-cover rounded border mb-2">
          <input type="file" name="permission_photo_front" class="w-full border p-2 rounded">
        </div>
        <div>
          <label class="block mb-2"><?= $langText['permission_photo_back'] ?? 'Permission Photo (Back)' ?></label>
          <img id="viewPermissionPhotoBack" src="" alt="" class="w-full h-32 object-cover rounded border mb-2">
          <input type="file" name="permission_photo_back" class="w-full border p-2 rounded">
        </div>
        <div>
          <label class="block mb-2"><?= $langText['health_card_front'] ?? 'Health Card (Front)' ?></label>
          <img id="viewHealthCardFront" src="" alt="" class="w-full h-32 object-cover rounded border mb-2">
          <input type="file" name="health_card_front" class="w-full border p-2 rounded">
        </div>
        <div>
          <label class="block mb-2"><?= $langText['health_card_back'] ?? 'Health Card (Back)' ?></label>
          <img id="viewHealthCardBack" src="" alt="" class="w-full h-32 object-cover rounded border mb-2">
          <input type="file" name="health_card_back" class="w-full border p-2 rounded">
        </div>
        <div>
          <label class="block mb-2"><?= $langText['bank_card_front'] ?? 'Bank Card (Front)' ?></label>
          <img id="viewBankCardFront" src="" alt="" class="w-full h-32 object-cover rounded border mb-2">
          <input type="file" name="bank_card_front" class="w-full border p-2 rounded">
        </div>
        <div>
          <label class="block mb-2"><?= $langText['bank_card_back'] ?? 'Bank Card (Back)' ?></label>
          <img id="viewBankCardBack" src="" alt="" class="w-full h-32 object-cover rounded border mb-2">
          <input type="file" name="bank_card_back" class="w-full border p-2 rounded">
        </div>
        <div id="marriageCertificateContainer">
          <label class="block mb-2"><?= $langText['marriage_certificate'] ?? 'Marriage Certificate' ?></label>
          <img id="viewMarriageCertificate" src="" alt="" class="w-full h-32 object-cover rounded border mb-2">
          <input type="file" name="marriage_certificate" class="w-full border p-2 rounded">
        </div>
      </div>

      <div class="mt-4">
        <label class="block mb-2"><?= $langText['about_me'] ?? 'About Me' ?></label>
        <textarea name="about" id="detailsEmployeeAbout" class="w-full border p-2 rounded"></textarea>
      </div>

      <div class="flex justify-between mt-6">
        <button type="button" id="deleteEmployeeBtn" class="text-red-500">
          <?= $langText['delete'] ?? 'Delete' ?>
        </button>
        <div>
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
