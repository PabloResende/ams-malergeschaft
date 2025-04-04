

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