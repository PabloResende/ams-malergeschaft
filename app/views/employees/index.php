<?php 
require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../../../config/Database.php';
$pdo = Database::connect();
$baseUrl = '/ams-malergeschaft/public';

$stmt = $pdo->query("SELECT * FROM employees ORDER BY name ASC");
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="ml-56 pt-20 p-4">
    <h1 class="text-2xl font-bold mb-4"><?= $langText['employees_list'] ?? 'Employees List' ?></h1>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php if (empty($employees)): ?>
            <p><?= $langText['no_employees_available'] ?? 'No employees available.' ?></p>
        <?php else: ?>
            <?php foreach ($employees as $employee): ?>
                <?php
                $startDate = new DateTime($employee['start_date']);
                $currentDate = new DateTime();
                $interval = $startDate->diff($currentDate);
                $years = $interval->y;
                $months = $interval->m;
                $timeInCompany = ($years > 0 ? $years . " " . ($years == 1 ? ($langText['year'] ?? 'year') : ($langText['years'] ?? 'years')) : '')
                                 . ($months > 0 ? " " . $months . " " . ($months == 1 ? ($langText['month'] ?? 'month') : ($langText['months'] ?? 'months')) : '');
                ?>
                <div class="bg-white p-4 rounded-lg shadow flex flex-col">
                    <div class="flex items-center">
                        <div class="w-20 flex-shrink-0">
                            <img src="<?= !empty($employee['profile_picture']) ? $baseUrl . '/uploads/' . $employee['profile_picture'] : 'https://via.placeholder.com/96x128'; ?>" 
                                alt="<?= htmlspecialchars($employee['name']) ?>" 
                                class="w-full h-auto object-cover rounded-lg">
                        </div>
                        <div class="ml-4">
                            <h2 class="text-xl font-bold"><?= htmlspecialchars($employee['name']) ?> <?= htmlspecialchars($employee['last_name']) ?></h2>
                            <p class="text-gray-600"><?= $langText['role'] ?? 'Role' ?>: <strong><?= htmlspecialchars($employee['role']) ?></strong></p>
                            <p class="text-gray-600"><?= $langText['time_in_company'] ?? 'Time in Company' ?>: <strong><?= trim($timeInCompany) ?></strong></p>
                        </div>
                    </div>
                    <div class="mt-4 flex justify-end space-x-2">
                        <button 
                            class="text-blue-500 hover:underline text-sm editEmployeeBtn" 
                            data-id="<?= $employee['id'] ?>"
                            data-name="<?= htmlspecialchars($employee['name']) ?>"
                            data-last_name="<?= htmlspecialchars($employee['last_name']) ?>"
                            data-role="<?= htmlspecialchars($employee['role']) ?>"
                            data-birth_date="<?= $employee['birth_date'] ?>"
                            data-start_date="<?= $employee['start_date'] ?>"
                            data-address="<?= htmlspecialchars($employee['address']) ?>"
                            data-about="<?= htmlspecialchars($employee['about']) ?>"
                            data-phone="<?= htmlspecialchars($employee['phone']) ?>"
                            data-sex="<?= htmlspecialchars($employee['sex']) ?>"
                            data-nationality="<?= htmlspecialchars($employee['nationality']) ?>"
                            data-permission_type="<?= htmlspecialchars($employee['permission_type']) ?>"
                            data-email="<?= htmlspecialchars($employee['email']) ?>"
                            data-ahv_number="<?= htmlspecialchars($employee['ahv_number']) ?>"
                            data-religion="<?= htmlspecialchars($employee['religion']) ?>"
                            data-marital_status="<?= htmlspecialchars($employee['marital_status']) ?>"
                        >
                            <?= $langText['edit'] ?? 'Edit' ?>
                        </button>
                        <a href="<?= $baseUrl ?>/employees/delete?id=<?= $employee['id'] ?>" class="text-red-500 hover:underline text-sm" onclick="return confirm('Are you sure you want to delete this employee?');">
                            <?= $langText['delete'] ?? 'Delete' ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Botão Flutuante para Abrir o Modal de Criação de Funcionário -->
<button id="addEmployeeBtn" class="fixed bottom-8 right-8 bg-green-500 text-white rounded-full p-4 shadow-lg hover:bg-green-600">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
    </svg>
</button>

<!-- Modal para Cadastrar Funcionário -->
<div id="employeeModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white rounded-md p-8 w-90 max-h-[80vh] overflow-y-auto mt-10" style="width: 90%; max-width: 800px;">
        <h2 class="text-2xl font-bold mb-4"><?= $langText['create_employee'] ?? 'Create Employee' ?></h2>
        <form action="<?= $baseUrl ?>/employees/store" method="POST" enctype="multipart/form-data" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block mb-2 font-medium"><?= $langText['name'] ?? 'Name' ?></label>
                    <input type="text" name="name" required class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
                </div>
                <div>
                    <label class="block mb-2 font-medium"><?= $langText['last_name'] ?? 'Last Name' ?></label>
                    <input type="text" name="last_name" required class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
                </div>
                <div>
                    <label class="block mb-2 font-medium"><?= $langText['address'] ?? 'Address' ?></label>
                    <input type="text" name="address" class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
                </div>
                <div>
                    <label class="block mb-2 font-medium"><?= $langText['sex'] ?? 'Sex' ?></label>
                    <select name="sex" class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block mb-2 font-medium"><?= $langText['birth_date'] ?? 'Birth Date' ?></label>
                    <input type="date" name="birth_date" required class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
                </div>
                <div>
                    <label class="block mb-2 font-medium"><?= $langText['nationality'] ?? 'Nationality' ?></label>
                    <input type="text" name="nationality" class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
                </div>
                <div>
                    <label class="block mb-2 font-medium"><?= $langText['permission_type'] ?? 'Permission Type' ?></label>
                    <input type="text" name="permission_type" class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
                </div>
                <div>
                    <label class="block mb-2 font-medium"><?= $langText['email'] ?? 'Email' ?></label>
                    <input type="email" name="email" class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
                </div>
                <div>
                    <label class="block mb-2 font-medium"><?= $langText['ahv_number'] ?? 'AHV Number' ?></label>
                    <input type="text" name="ahv_number" class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
                </div>
                <div>
                    <label class="block mb-2 font-medium"><?= $langText['phone'] ?? 'Phone' ?></label>
                    <input type="text" name="phone" class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
                </div>
                <div>
                    <label class="block mb-2 font-medium"><?= $langText['religion'] ?? 'Religion' ?></label>
                    <input type="text" name="religion" class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
                </div>
                <div>
                    <label class="block mb-2 font-medium"><?= $langText['marital_status'] ?? 'Marital Status' ?></label>
                    <select name="marital_status" class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
                        <option value="single">Single</option>
                        <option value="married">Married</option>
                        <option value="divorced">Divorced</option>
                        <option value="widowed">Widowed</option>
                    </select>
                </div>
                <div>
                    <label class="block mb-2 font-medium"><?= $langText['role'] ?? 'Role' ?></label>
                    <input type="text" name="role" required class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
                </div>
                <div>
                    <label class="block mb-2 font-medium"><?= $langText['start_date'] ?? 'Start Date' ?></label>
                    <input type="date" name="start_date" required class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
                </div>
            </div>
            
            <div class="mt-4">
                <h3 class="text-lg font-medium mb-2"><?= $langText['documents'] ?? 'Documents' ?></h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-2 font-medium"><?= $langText['profile_picture'] ?? 'Profile Picture' ?></label>
                        <input type="file" name="profile_picture" class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
                    </div>
                    <div>
                        <label class="block mb-2 font-medium"><?= $langText['passport'] ?? 'Passport' ?></label>
                        <input type="file" name="passport" class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
                    </div>
                    <div>
                        <label class="block mb-2 font-medium"><?= $langText['permission_photo_front'] ?? 'Permission Photo (Front)' ?></label>
                        <input type="file" name="permission_photo_front" class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
                    </div>
                    <div>
                        <label class="block mb-2 font-medium"><?= $langText['permission_photo_back'] ?? 'Permission Photo (Back)' ?></label>
                        <input type="file" name="permission_photo_back" class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
                    </div>
                    <div>
                        <label class="block mb-2 font-medium"><?= $langText['health_card_front'] ?? 'Health Card (Front)' ?></label>
                        <input type="file" name="health_card_front" class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
                    </div>
                    <div>
                        <label class="block mb-2 font-medium"><?= $langText['health_card_back'] ?? 'Health Card (Back)' ?></label>
                        <input type="file" name="health_card_back" class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
                    </div>
                    <div>
                        <label class="block mb-2 font-medium"><?= $langText['bank_card_front'] ?? 'Bank Card (Front)' ?></label>
                        <input type="file" name="bank_card_front" class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
                    </div>
                    <div>
                        <label class="block mb-2 font-medium"><?= $langText['bank_card_back'] ?? 'Bank Card (Back)' ?></label>
                        <input type="file" name="bank_card_back" class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
                    </div>
                    <div>
                        <label class="block mb-2 font-medium"><?= $langText['marriage_certificate'] ?? 'Marriage Certificate' ?></label>
                        <input type="file" name="marriage_certificate" class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
                    </div>
                </div>
            </div>
            
            <div class="mt-4">
                <label class="block mb-2 font-medium"><?= $langText['about_me'] ?? 'About Me' ?></label>
                <textarea name="about" class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300"></textarea>
            </div>
            
            <div class="flex justify-end mt-4">
                <button type="button" id="closeEmployeeModal" class="mr-2 px-4 py-2 border rounded"><?= $langText['cancel'] ?? 'Cancel' ?></button>
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded"><?= $langText['submit'] ?? 'Submit' ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Modal de Edição de Funcionário -->
<div id="employeeEditModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white rounded-md p-8 w-90 max-h-[80vh] overflow-y-auto mt-10" style="width: 90%; max-width: 800px;">
        <h2 class="text-2xl font-bold mb-4"><?= $langText['edit_employee'] ?? 'Edit Employee' ?></h2>
        <form id="employeeEditForm" action="<?= $baseUrl ?>/employees/update" method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="id" id="editEmployeeId">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block mb-2 font-medium"><?= $langText['name'] ?? 'Name' ?></label>
                    <input type="text" name="name" id="editEmployeeName" required class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
                </div>
                <div>
                    <label class="block mb-2 font-medium"><?= $langText['last_name'] ?? 'Last Name' ?></label>
                    <input type="text" name="last_name" id="editEmployeeLastName" required class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
                </div>
                <div>
                    <label class="block mb-2 font-medium"><?= $langText['address'] ?? 'Address' ?></label>
                    <input type="text" name="address" id="editEmployeeAddress" class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
                </div>
                <div>
                    <label class="block mb-2 font-medium"><?= $langText['sex'] ?? 'Sex' ?></label>
                    <select name="sex" id="editEmployeeSex" class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block mb-2 font-medium"><?= $langText['birth_date'] ?? 'Birth Date' ?></label>
                    <input type="date" name="birth_date" id="editEmployeeBirthDate" required class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
                </div>
                <div>
                    <label class="block mb-2 font-medium"><?= $langText['nationality'] ?? 'Nationality' ?></label>
                    <input type="text" name="nationality" id="editEmployeeNationality" class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
                </div>
                <div>
                    <label class="block mb-2 font-medium"><?= $langText['permission_type'] ?? 'Permission Type' ?></label>
                    <input type="text" name="permission_type" id="editEmployeePermissionType" class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
                </div>
                <div>
                    <label class="block mb-2 font-medium"><?= $langText['email'] ?? 'Email' ?></label>
                    <input type="email" name="email" id="editEmployeeEmail" class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
                </div>
                <div>
                    <label class="block mb-2 font-medium"><?= $langText['ahv_number'] ?? 'AHV Number' ?></label>
                    <input type="text" name="ahv_number" id="editEmployeeAhvNumber" class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
                </div>
                <div>
                    <label class="block mb-2 font-medium"><?= $langText['phone'] ?? 'Phone' ?></label>
                    <input type="text" name="phone" id="editEmployeePhone" class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
                </div>
                <div>
                    <label class="block mb-2 font-medium"><?= $langText['religion'] ?? 'Religion' ?></label>
                    <input type="text" name="religion" id="editEmployeeReligion" class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
                </div>
                <div>
                    <label class="block mb-2 font-medium"><?= $langText['marital_status'] ?? 'Marital Status' ?></label>
                    <select name="marital_status" id="editEmployeeMaritalStatus" class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
                        <option value="single">Single</option>
                        <option value="married">Married</option>
                        <option value="divorced">Divorced</option>
                        <option value="widowed">Widowed</option>
                    </select>
                </div>
                <div>
                    <label class="block mb-2 font-medium"><?= $langText['role'] ?? 'Role' ?></label>
                    <input type="text" name="role" id="editEmployeeRole" required class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
                </div>
                <div>
                    <label class="block mb-2 font-medium"><?= $langText['start_date'] ?? 'Start Date' ?></label>
                    <input type="date" name="start_date" id="editEmployeeStartDate" required class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
                </div>
            </div>
            
            <div class="mt-4">
                <h3 class="text-lg font-medium mb-2"><?= $langText['documents'] ?? 'Documents' ?></h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-2 font-medium"><?= $langText['profile_picture'] ?? 'Profile Picture' ?></label>
                        <input type="file" name="profile_picture" class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
                    </div>
                    <div>
                        <label class="block mb-2 font-medium"><?= $langText['passport'] ?? 'Passport' ?></label>
                        <input type="file" name="passport" class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
                    </div>
                    <div>
                        <label class="block mb-2 font-medium"><?= $langText['permission_photo_front'] ?? 'Permission Photo (Front)' ?></label>
                        <input type="file" name="permission_photo_front" class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
                    </div>
                    <div>
                        <label class="block mb-2 font-medium"><?= $langText['permission_photo_back'] ?? 'Permission Photo (Back)' ?></label>
                        <input type="file" name="permission_photo_back" class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
                    </div>
                    <div>
                        <label class="block mb-2 font-medium"><?= $langText['health_card_front'] ?? 'Health Card (Front)' ?></label>
                        <input type="file" name="health_card_front" class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
                    </div>
                    <div>
                        <label class="block mb-2 font-medium"><?= $langText['health_card_back'] ?? 'Health Card (Back)' ?></label>
                        <input type="file" name="health_card_back" class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
                    </div>
                    <div>
                        <label class="block mb-2 font-medium"><?= $langText['bank_card_front'] ?? 'Bank Card (Front)' ?></label>
                        <input type="file" name="bank_card_front" class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
                    </div>
                    <div>
                        <label class="block mb-2 font-medium"><?= $langText['bank_card_back'] ?? 'Bank Card (Back)' ?></label>
                        <input type="file" name="bank_card_back" class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
                    </div>
                    <div>
                        <label class="block mb-2 font-medium"><?= $langText['marriage_certificate'] ?? 'Marriage Certificate' ?></label>
                        <input type="file" name="marriage_certificate" class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
                    </div>
                </div>
            </div>
            
            <div class="mt-4">
                <label class="block mb-2 font-medium"><?= $langText['about_me'] ?? 'About Me' ?></label>
                <textarea name="about" id="editEmployeeAbout" class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300"></textarea>
            </div>
            
            <div class="flex justify-end mt-4">
                <button type="button" id="closeEmployeeEditModal" class="mr-2 px-4 py-2 border rounded"><?= $langText['cancel'] ?? 'Cancel' ?></button>
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded"><?= $langText['submit'] ?? 'Submit' ?></button>
            </div>
        </form>
    </div>
</div>

<script>
    const addEmployeeBtn = document.getElementById('addEmployeeBtn');
    const employeeModal = document.getElementById('employeeModal');
    const closeEmployeeModal = document.getElementById('closeEmployeeModal');

    addEmployeeBtn.addEventListener('click', function() {
        employeeModal.classList.remove('hidden');
    });

    closeEmployeeModal.addEventListener('click', function() {
        employeeModal.classList.add('hidden');
    });

    window.addEventListener('click', function(event) {
        if (event.target === employeeModal) {
            employeeModal.classList.add('hidden');
        }
    });

    const editButtons = document.querySelectorAll('.editEmployeeBtn');
    const employeeEditModal = document.getElementById('employeeEditModal');
    const closeEmployeeEditModal = document.getElementById('closeEmployeeEditModal');

    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('editEmployeeId').value = this.getAttribute('data-id');
            document.getElementById('editEmployeeName').value = this.getAttribute('data-name');
            document.getElementById('editEmployeeLastName').value = this.getAttribute('data-last_name');
            document.getElementById('editEmployeeRole').value = this.getAttribute('data-role');
            document.getElementById('editEmployeeBirthDate').value = this.getAttribute('data-birth_date');
            document.getElementById('editEmployeeStartDate').value = this.getAttribute('data-start_date');
            document.getElementById('editEmployeeAddress').value = this.getAttribute('data-address');
            document.getElementById('editEmployeeAbout').value = this.getAttribute('data-about');
            document.getElementById('editEmployeePhone').value = this.getAttribute('data-phone');
            document.getElementById('editEmployeeSex').value = this.getAttribute('data-sex');
            document.getElementById('editEmployeeNationality').value = this.getAttribute('data-nationality');
            document.getElementById('editEmployeePermissionType').value = this.getAttribute('data-permission_type');
            document.getElementById('editEmployeeEmail').value = this.getAttribute('data-email');
            document.getElementById('editEmployeeAhvNumber').value = this.getAttribute('data-ahv_number');
            document.getElementById('editEmployeeReligion').value = this.getAttribute('data-religion');
            document.getElementById('editEmployeeMaritalStatus').value = this.getAttribute('data-marital_status');
            
            employeeEditModal.classList.remove('hidden');
        });
    });

    closeEmployeeEditModal.addEventListener('click', function() {
        employeeEditModal.classList.add('hidden');
    });

    window.addEventListener('click', function(event) {
        if (event.target === employeeEditModal) {
            employeeEditModal.classList.add('hidden');
        }
    });
</script>