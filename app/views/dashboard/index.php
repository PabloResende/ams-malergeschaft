<?php require __DIR__ . '/../layout/header.php'; 

$projects = isset($projects) && is_array($projects) ? $projects : [];
?>

<div class="p-6">
    <h1 class="text-2xl font-bold mb-4">Dashboard</h1>


    <div class="grid grid-cols-3 gap-4">
        <div class="bg-blue-100 p-4 rounded">
            <p class="text-lg font-bold">Projetos Ativos</p>
            <p class="text-2xl"><?php echo count(array_filter($projects, fn($p) => $p['status'] == 'in_progress')); ?></p>
        </div>

        <div class="bg-green-100 p-4 rounded">
            <p class="text-lg font-bold">Horas de Projetos no Mês</p>
            <p class="text-2xl">??? h</p> 
        </div>

        <div class="bg-red-100 p-4 rounded">
            <p class="text-lg font-bold">Projetos Finalizados</p>
            <p class="text-2xl"><?php echo count(array_filter($projects, fn($p) => $p['status'] == 'completed')); ?></p>
        </div>
    </div>

    <h2 class="text-xl font-bold mt-6">Projetos Recentes</h2>
    <?php require __DIR__ . '/../projects/index.php'; ?>
</div>
