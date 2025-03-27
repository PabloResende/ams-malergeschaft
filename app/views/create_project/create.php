<?php require __DIR__ . '/../layout/header.php'; ?>

<div class="ml-56 pt-20 p-8 max-w-lg mx-auto">
    <h1 class="text-2xl font-bold mb-4">Novo Projeto</h1>
    <form action="<?= $baseUrl ?>/projects/store" method="POST" class="space-y-4">
        <div>
            <label class="block mb-2 font-medium">Nome do Projeto:</label>
            <input type="text" name="name" required class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
        </div>
        <div>
            <label class="block mb-2 font-medium">Descrição:</label>
            <textarea name="description" required class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300"></textarea>
        </div>
        <div>
            <label class="block mb-2 font-medium">Data de Início:</label>
            <input type="date" name="start_date" required class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
        </div>
        <div>
            <label class="block mb-2 font-medium">Data de Entrega:</label>
            <input type="date" name="end_date" required class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
        </div>
        <div>
            <label class="block mb-2 font-medium">Carga Horária (horas):</label>
            <input type="number" name="total_hours" required class="w-full border p-2 rounded focus:outline-none focus:ring focus:ring-blue-300">
        </div>
        <div>
            <button type="submit" class="mt-4 bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition-colors">
                Criar Projeto
            </button>
        </div>
    </form>
</div>
