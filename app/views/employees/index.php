<?php require __DIR__ . '/../layout/header.php'; ?>

<div class="ml-56 pt-20 p-8 max-w-lg mx-auto">
    <h1 class="text-2xl font-bold mb-4"><?= $langText['create_employee'] ?? 'Create Employee' ?></h1>
    <form action="<?= $baseUrl ?>/employees/store" method="POST" enctype="multipart/form-data" class="space-y-4">
        
        <div>
            <label class="block mb-2 font-medium"><?= $langText['name'] ?? 'Name' ?></label>
            <input type="text" name="name" required class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
        </div>

        <div>
            <label class="block mb-2 font-medium"><?= $langText['role'] ?? 'Role' ?></label>
            <input type="text" name="role" required class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
        </div>

        <div>
            <label class="block mb-2 font-medium"><?= $langText['birth_date'] ?? 'Birth Date' ?></label>
            <input type="date" name="birth_date" required class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
        </div>

        <div>
            <label class="block mb-2 font-medium"><?= $langText['start_date'] ?? 'Start Date' ?></label>
            <input type="date" name="start_date" required class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
        </div>

        <div>
            <label class="block mb-2 font-medium"><?= $langText['address'] ?? 'Address' ?></label>
            <input type="text" name="address" class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
        </div>

        <div>
            <label class="block mb-2 font-medium"><?= $langText['about_me'] ?? 'About Me' ?></label>
            <textarea name="about" class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300"></textarea>
        </div>

        <div>
            <label class="block mb-2 font-medium"><?= $langText['phone'] ?? 'Phone' ?></label>
            <input type="text" name="phone" class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
        </div>

        <div>
            <label class="block mb-2 font-medium"><?= $langText['profile_picture'] ?? 'Profile Picture' ?></label>
            <input type="file" name="profile_picture" class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
        </div>

        <div>
            <button type="submit" class="mt-4 bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition-colors">
                <?= $langText['create_employee'] ?? 'Create Employee' ?>
            </button>
        </div>
    </form>
</div>


