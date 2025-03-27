<?php require __DIR__ . '/../layout/header.php'; ?>

<div class="p-6">
    <h1 class="text-2xl font-bold mb-4">Projetos</h1>

    <?php if (empty($projects)): ?>
        <p>Nenhum projeto criado ainda.</p>
    <?php else: ?>
        <table class="w-full border-collapse border border-gray-300">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border p-2">Nome</th>
                    <th class="border p-2">Status</th>
                    <th class="border p-2">Progresso</th>
                    <th class="border p-2">Entrega</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($projects as $project): ?>
                    <tr>
                        <td class="border p-2"><?php echo htmlspecialchars($project['name']); ?></td>
                        <td class="border p-2"><?php echo ucfirst($project['status']); ?></td>
                        <td class="border p-2"><?php echo $project['progress']; ?>%</td>
                        <td class="border p-2"><?php echo $project['end_date']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
