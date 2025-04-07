<!-- app/views/employees/details.php -->
<div id="employeeViewModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white rounded-md p-8 w-90 max-h-[80vh] overflow-y-auto mt-10" style="width: 90%; max-width: 800px;">
        <!-- Cabeçalho com título e botão de fechar -->
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-2xl font-bold"><?= $langText['employee_details'] ?? 'Employee Details' ?></h2>
            <button type="button" id="closeEmployeeViewModal" class="text-gray-500 hover:text-gray-700 focus:outline-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Coluna 1: Informações Pessoais -->
            <div class="col-span-1">
                <div class="flex justify-center mb-4">
                    <img id="viewProfilePicture" src="" alt="Profile Picture" 
                         class="w-48 h-64 object-cover rounded-lg border"
                         onerror="this.onerror=null;this.src='path/to/placeholder.svg';">
                </div>
                <h3 class="text-lg font-semibold mb-2 border-b pb-2"><?= $langText['personal_info'] ?? 'Personal Information' ?></h3>
                <div class="space-y-2 text-sm">
                    <p><span class="font-medium"><?= $langText['name'] ?? 'Name' ?>:</span> <span id="viewName"></span></p>
                    <p><span class="font-medium"><?= $langText['last_name'] ?? 'Last Name' ?>:</span> <span id="viewLastName"></span></p>
                    <p><span class="font-medium"><?= $langText['birth_date'] ?? 'Birth Date' ?>:</span> <span id="viewBirthDate"></span></p>
                    <p><span class="font-medium"><?= $langText['sex'] ?? 'Sex' ?>:</span> <span id="viewSex"></span></p>
                    <p><span class="font-medium"><?= $langText['nationality'] ?? 'Nationality' ?>:</span> <span id="viewNationality"></span></p>
                    <p><span class="font-medium"><?= $langText['marital_status'] ?? 'Marital Status' ?>:</span> <span id="viewMaritalStatus"></span></p>
                    <p><span class="font-medium"><?= $langText['religion'] ?? 'Religion' ?>:</span> <span id="viewReligion"></span></p>
                </div>
            </div>
            
            <!-- Coluna 2: Informações Profissionais e de Contato -->
            <div class="col-span-1">
                <h3 class="text-lg font-semibold mb-2 border-b pb-2"><?= $langText['professional_info'] ?? 'Professional Information' ?></h3>
                <div class="space-y-2 text-sm">
                    <p><span class="font-medium"><?= $langText['role'] ?? 'Role' ?>:</span> <span id="viewRole"></span></p>
                    <p><span class="font-medium"><?= $langText['start_date'] ?? 'Start Date' ?>:</span> <span id="viewStartDate"></span></p>
                    <p><span class="font-medium"><?= $langText['permission_type'] ?? 'Permission Type' ?>:</span> <span id="viewPermissionType"></span></p>
                    <p><span class="font-medium"><?= $langText['ahv_number'] ?? 'AHV Number' ?>:</span> <span id="viewAhvNumber"></span></p>
                </div>
                <h3 class="text-lg font-semibold mt-4 mb-2 border-b pb-2"><?= $langText['contact_info'] ?? 'Contact Information' ?></h3>
                <div class="space-y-2 text-sm">
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
                        <h4 class="font-medium text-sm"><?= $langText['passport'] ?? 'Passport' ?></h4>
                        <img id="viewPassport" src="" alt="Passport" 
                             class="w-full h-auto border rounded mt-1" style="max-height: 150px;"
                             onerror="this.onerror=null;this.src='path/to/placeholder.svg';">
                    </div>
                    
                    <div>
                        <h4 class="font-medium text-sm"><?= $langText['permission_photos'] ?? 'Permission Photos' ?></h4>
                        <div class="grid grid-cols-2 gap-2 mt-1">
                            <div>
                                <small>Front</small>
                                <img id="viewPermissionPhotoFront" src="" alt="Permission Front" 
                                     class="w-full h-auto border rounded" style="max-height: 120px;"
                                     onerror="this.onerror=null;this.src='path/to/placeholder.svg';">
                            </div>
                            <div>
                                <small>Back</small>
                                <img id="viewPermissionPhotoBack" src="" alt="Permission Back" 
                                     class="w-full h-auto border rounded" style="max-height: 120px;"
                                     onerror="this.onerror=null;this.src='path/to/placeholder.svg';">
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h4 class="font-medium text-sm"><?= $langText['health_card'] ?? 'Health Card' ?></h4>
                        <div class="grid grid-cols-2 gap-2 mt-1">
                            <div>
                                <small>Front</small>
                                <img id="viewHealthCardFront" src="" alt="Health Card Front" 
                                     class="w-full h-auto border rounded" style="max-height: 120px;"
                                     onerror="this.onerror=null;this.src='path/to/placeholder.svg';">
                            </div>
                            <div>
                                <small>Back</small>
                                <img id="viewHealthCardBack" src="" alt="Health Card Back" 
                                     class="w-full h-auto border rounded" style="max-height: 120px;"
                                     onerror="this.onerror=null;this.src='path/to/placeholder.svg';">
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h4 class="font-medium text-sm"><?= $langText['bank_card'] ?? 'Bank Card' ?></h4>
                        <div class="grid grid-cols-2 gap-2 mt-1">
                            <div>
                                <small>Front</small>
                                <img id="viewBankCardFront" src="" alt="Bank Card Front" 
                                     class="w-full h-auto border rounded" style="max-height: 120px;"
                                     onerror="this.onerror=null;this.src='path/to/placeholder.svg';">
                            </div>
                            <div>
                                <small>Back</small>
                                <img id="viewBankCardBack" src="" alt="Bank Card Back" 
                                     class="w-full h-auto border rounded" style="max-height: 120px;"
                                     onerror="this.onerror=null;this.src='path/to/placeholder.svg';">
                            </div>
                        </div>
                    </div>
                    
                    <div id="marriageCertificateContainer">
                        <h4 class="font-medium text-sm"><?= $langText['marriage_certificate'] ?? 'Marriage Certificate' ?></h4>
                        <img id="viewMarriageCertificate" src="" alt="Marriage Certificate" 
                             class="w-full h-auto border rounded mt-1" style="max-height: 150px;"
                             onerror="this.onerror=null;this.src='path/to/placeholder.svg';">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Seção "About Me" -->
        <div class="mt-6">
            <h3 class="text-lg font-semibold mb-2 border-b pb-2"><?= $langText['about_me'] ?? 'About Me' ?></h3>
            <p id="viewAbout" class="text-gray-700 text-sm"></p>
        </div>
    </div>
</div>
