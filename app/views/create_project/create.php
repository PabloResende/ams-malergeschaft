<?php require __DIR__ . '/../layout/header.php'; ?>

<div class="p-6 max-w-lg mx-auto">
    <h1 class="text-2xl font-bold mb-4">Novo Projeto</h1>

    <form action="/projects/store" method="POST">
        <label class="block mb-2">Nome do Projeto:</label>
        <input type="text" name="name" required class="w-full border p-2 rounded">

        <label class="block mt-4">Descrição:</label>
        <textarea name="description" required class="w-full border p-2 rounded"></textarea>

        <label class="block mt-4">Data de Início:</label>
        <input type="date" name="start_date" required class="w-full border p-2 rounded">

        <label class="block mt-4">Data de Entrega:</label>
        <input type="date" name="end_date" required class="w-full border p-2 rounded">

        <label class="block mt-4">Carga Horária (horas):</label>
        <input type="number" name="total_hours" required class="w-full border p-2 rounded">

        <button type="submit" class="mt-4 bg-blue-500 text-white px-4 py-2 rounded">Criar Projeto</button>
    </form>
</div>
