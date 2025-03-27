<?php require __DIR__ . '/../layout/header.php'; ?>

<div class="ml-56 pt-20 p-8 max-w-lg mx-auto">
    <h1 class="text-2xl font-bold mb-4"><?= $langText['new_employee'] ?? 'Novo Funcionário' ?></h1>

    <form action="<?= $baseUrl ?>/employees/store" method="POST" enctype="multipart/form-data" class="space-y-4">

        <div>
            <label class="block mb-2 font-medium"><?= $langText['employee_name'] ?? 'Nome do Funcionário' ?>:</label>
            <input type="text" name="name" required class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
        </div>

        <div>
            <label class="block mb-2 font-medium"><?= $langText['role'] ?? 'Função' ?>:</label>
            <input type="text" name="role" required class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
        </div>

        <div>
            <label class="block mb-2 font-medium"><?= $langText['birth_date'] ?? 'Data de Nascimento' ?>:</label>
            <input type="date" name="birth_date" required class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
        </div>

        <div>
            <label class="block mb-2 font-medium"><?= $langText['start_date'] ?? 'Data de Início na Empresa' ?>:</label>
            <input type="date" name="start_date" required class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
        </div>

        <div>
            <label class="block mb-2 font-medium"><?= $langText['address'] ?? 'Endereço' ?>:</label>
            <input type="text" name="address" class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
        </div>

        <div>
            <label class="block mb-2 font-medium"><?= $langText['about_me'] ?? 'Sobre' ?>:</label>
            <textarea name="about" class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300"></textarea>
        </div>

        <div>
            <label class="block mb-2 font-medium"><?= $langText['phone'] ?? 'Telefone' ?>:</label>
            <input type="text" name="phone" class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300" placeholder="(99) 99999-9999">
        </div>

        <div>
            <label class="block mb-2 font-medium"><?= $langText['profile_picture'] ?? 'Foto de Perfil' ?>:</label>
            <input type="file" name="profile_picture" accept="image/*" class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
        </div>

        <div>
            <button type="submit" class="mt-4 bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition-colors">
                <?= $langText['create_employee'] ?? 'Cadastrar Funcionário' ?>
            </button>
        </div>

    </form>
</div>
