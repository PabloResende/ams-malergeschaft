<?php 
require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../../../config/Database.php';

$pdo = Database::connect();
$baseUrl = '/ams-malergeschaft/public';

$stmt = $pdo->query("SELECT * FROM employees ORDER BY name ASC");
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php require_once __DIR__ . '/edit.php'; ?>
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
                    <div class="flex items-center">
                        <div class="w-20 flex-shrink-0">
                            <?php if (!empty($employee['profile_picture'])): ?>
                                <img src="<?= $baseUrl ?>/employees/document?id=<?= $employee['id'] ?>&type=profile_picture" 
                                    alt="<?= htmlspecialchars($employee['name']) ?>" 
                                    class="w-full h-auto object-cover rounded-lg">
                            <?php else: ?>
                                <img src="https://via.placeholder.com/96x128" 
                                    alt="Placeholder" 
                                    class="w-full h-auto object-cover rounded-lg">
                            <?php endif; ?>
                        </div>
                        <div class="ml-4">
                            <h2 class="text-xl font-bold"><?= htmlspecialchars($employee['name']) ?> <?= htmlspecialchars($employee['last_name']) ?></h2>
                            <p class="text-gray-600"><?= $langText['role'] ?? 'Role' ?>: <strong><?= htmlspecialchars($employee['role']) ?></strong></p>
                            <p class="text-gray-600"><?= $langText['time_in_company'] ?? 'Time in Company' ?>: <strong><?= trim($timeInCompany) ?></strong></p>
                        </div>
                    </div>
                    
                    <div class="mt-4 flex justify-end space-x-2">
                        <!-- Botão Detalhes -->
                        <button class="text-green-500 hover:underline text-sm viewEmployeeBtn" 
                        data-id="<?= $employee['id'] ?>">
                        <?= $langText['details'] ?? 'Details' ?>
                    </button>
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
    </div>
    <?php require_once __DIR__ . '/create.php'; ?>
<!-- Botão Flutuante para Abrir o Modal de Criação de Funcionário -->
<button id="addEmployeeBtn" class="fixed bottom-8 right-8 bg-green-500 text-white rounded-full p-4 shadow-lg hover:bg-green-600">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
    </svg>
</button>

<!-- Modal de Detalhes do Funcionário -->
<div id="employeeViewModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white rounded-md p-8 w-90 max-h-[90vh] overflow-y-auto" style="width: 90%; max-width: 800px;">
        <h2 class="text-2xl font-bold mb-4"><?= $langText['employee_details'] ?? 'Employee Details' ?></h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Coluna 1: Informações Pessoais -->
            <div class="col-span-1">
                <div class="flex justify-center mb-4">
                    <img id="viewProfilePicture" src="" alt="Profile Picture" 
                         class="w-48 h-64 object-cover rounded-lg border">
                </div>
                
                <h3 class="text-lg font-semibold mb-2 border-b pb-2"><?= $langText['personal_info'] ?? 'Personal Information' ?></h3>
                <div class="space-y-2">
                    <p><span class="font-medium"><?= $langText['name'] ?? 'Name' ?>:</span> <span id="viewName"></span></p>
                    <p><span class="font-medium"><?= $langText['last_name'] ?? 'Last Name' ?>:</span> <span id="viewLastName"></span></p>
                    <p><span class="font-medium"><?= $langText['birth_date'] ?? 'Birth Date' ?>:</span> <span id="viewBirthDate"></span></p>
                    <p><span class="font-medium"><?= $langText['sex'] ?? 'Sex' ?>:</span> <span id="viewSex"></span></p>
                    <p><span class="font-medium"><?= $langText['nationality'] ?? 'Nationality' ?>:</span> <span id="viewNationality"></span></p>
                    <p><span class="font-medium"><?= $langText['marital_status'] ?? 'Marital Status' ?>:</span> <span id="viewMaritalStatus"></span></p>
                    <p><span class="font-medium"><?= $langText['religion'] ?? 'Religion' ?>:</span> <span id="viewReligion"></span></p>
                </div>
            </div>
            
            <!-- Coluna 2: Informações Profissionais -->
            <div class="col-span-1">
                <h3 class="text-lg font-semibold mb-2 border-b pb-2"><?= $langText['professional_info'] ?? 'Professional Information' ?></h3>
                <div class="space-y-2">
                    <p><span class="font-medium"><?= $langText['role'] ?? 'Role' ?>:</span> <span id="viewRole"></span></p>
                    <p><span class="font-medium"><?= $langText['start_date'] ?? 'Start Date' ?>:</span> <span id="viewStartDate"></span></p>
                    <p><span class="font-medium"><?= $langText['permission_type'] ?? 'Permission Type' ?>:</span> <span id="viewPermissionType"></span></p>
                    <p><span class="font-medium"><?= $langText['ahv_number'] ?? 'AHV Number' ?>:</span> <span id="viewAhvNumber"></span></p>
                </div>
                
                <h3 class="text-lg font-semibold mt-4 mb-2 border-b pb-2"><?= $langText['contact_info'] ?? 'Contact Information' ?></h3>
                <div class="space-y-2">
                    <p><span class="font-medium"><?= $langText['address'] ?? 'Address' ?>:</span> <span id="viewAddress"></span></p>
                    <p><span class="font-medium"><?= $langText['email'] ?? 'Email' ?>:</span> <span id="viewEmail"></span></p>
                    <p><span class="font-medium"><?= $langText['phone'] ?? 'Phone' ?>:</span> <span id="viewPhone"></span></p>
                </div>
            </div>
            
            <!-- Coluna 3: Documentos -->
            <div class="col-span-1">
                <h3 class="text-lg font-semibold mb-2 border-b pb-2"><?= $langText['documents'] ?? 'Documents' ?></h3>
                <div class="space-y-4">
                    <div>
                        <h4 class="font-medium"><?= $langText['passport'] ?? 'Passport' ?></h4>
                        <img id="viewPassport" src="" alt="Passport" class="w-full h-auto border rounded mt-1" style="max-height: 150px;">
                    </div>
                    
                    <div>
                        <h4 class="font-medium"><?= $langText['permission_photos'] ?? 'Permission Photos' ?></h4>
                        <div class="grid grid-cols-2 gap-2 mt-1">
                            <div>
                                <small>Front</small>
                                <img id="viewPermissionFront" src="" alt="Permission Front" class="w-full h-auto border rounded" style="max-height: 120px;">
                            </div>
                            <div>
                                <small>Back</small>
                                <img id="viewPermissionBack" src="" alt="Permission Back" class="w-full h-auto border rounded" style="max-height: 120px;">
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h4 class="font-medium"><?= $langText['health_card'] ?? 'Health Card' ?></h4>
                        <div class="grid grid-cols-2 gap-2 mt-1">
                            <div>
                                <small>Front</small>
                                <img id="viewHealthCardFront" src="" alt="Health Card Front" class="w-full h-auto border rounded" style="max-height: 120px;">
                            </div>
                            <div>
                                <small>Back</small>
                                <img id="viewHealthCardBack" src="" alt="Health Card Back" class="w-full h-auto border rounded" style="max-height: 120px;">
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h4 class="font-medium"><?= $langText['bank_card'] ?? 'Bank Card' ?></h4>
                        <div class="grid grid-cols-2 gap-2 mt-1">
                            <div>
                                <small>Front</small>
                                <img id="viewBankCardFront" src="" alt="Bank Card Front" class="w-full h-auto border rounded" style="max-height: 120px;">
                            </div>
                            <div>
                                <small>Back</small>
                                <img id="viewBankCardBack" src="" alt="Bank Card Back" class="w-full h-auto border rounded" style="max-height: 120px;">
                            </div>
                        </div>
                    </div>
                    
                    <div id="marriageCertificateContainer">
                        <h4 class="font-medium"><?= $langText['marriage_certificate'] ?? 'Marriage Certificate' ?></h4>
                        <img id="viewMarriageCertificate" src="" alt="Marriage Certificate" class="w-full h-auto border rounded mt-1" style="max-height: 150px;">
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-6">
            <h3 class="text-lg font-semibold mb-2 border-b pb-2"><?= $langText['about_me'] ?? 'About Me' ?></h3>
            <p id="viewAbout" class="text-gray-700"></p>
        </div>
        
        <div class="flex justify-end mt-6">
            <button type="button" id="closeEmployeeViewModal" class="bg-blue-500 text-white px-4 py-2 rounded">
                <?= $langText['close'] ?? 'Close' ?>
            </button>
        </div>
    </div>
</div>

<script>
  const baseUrl = "<?= $baseUrl ?>";
</script>

<script src="<?= $baseUrl ?>/js/employees.js"></script>