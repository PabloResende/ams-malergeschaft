<?php
// employees.php
session_start();
require __DIR__ . '/layout/header.php';
?>
<div class="container mx-auto p-6 pt-20">
    <h1 class="text-2xl font-bold mb-4">Cadastro de Funcionários</h1>
    <form action="/ams-malergeschaft/public/app/controllers/EmployeeController.php?action=create" method="POST" class="bg-white p-6 rounded shadow">
        <div class="mb-4">
            <label class="block text-gray-700">Nome do Funcionário</label>
            <input type="text" name="employee_name" class="w-full px-3 py-2 border rounded" required>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700">Função</label>
            <input type="text" name="employee_role" class="w-full px-3 py-2 border rounded" required>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700">Contato</label>
            <input type="text" name="employee_contact" class="w-full px-3 py-2 border rounded">
        </div>
        <div class="flex justify-end">
            <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                Cadastrar Funcionário
            </button>
        </div>
    </form>
</div>
<?php require __DIR__ . '/layout/footer.php'; ?>
