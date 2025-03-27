<?php
require __DIR__ . '/../layout/header.php';
?>
<div class="container mx-auto p-6 pt-20">
    <h1 class="text-2xl font-bold mb-4">Cadastro de Funcionários</h1>
    <form action="<?= $baseUrl ?>/employees/create" method="POST" class="bg-white p-6 rounded shadow space-y-4">
        <div>
            <label class="block text-gray-700 font-medium">Nome do Funcionário</label>
            <input type="text" name="employee_name" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:ring-blue-300" required>
        </div>
        <div>
            <label class="block text-gray-700 font-medium">Função</label>
            <input type="text" name="employee_role" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:ring-blue-300" required>
        </div>
        <div>
            <label class="block text-gray-700 font-medium">Contato</label>
            <input type="text" name="employee_contact" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:ring-blue-300">
        </div>
        <div class="flex justify-end">
            <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 transition-colors">
                Cadastrar Funcionário
            </button>
        </div>
    </form>
</div>
