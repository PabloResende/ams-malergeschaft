<?php require __DIR__ . '/../layout/header.php'; 

// A variável $projects deve ser definida pelo controlador (e.g., UserController->dashboard())
$projects = isset($projects) && is_array($projects) ? $projects : [];
?>
<div class="ml-56 pt-20 p-8">
  <h2 class="text-2xl font-bold mb-4">Projects Overview</h2>
  <p class="text-lg text-gray-600 mb-8">Track and manage your renovation projects efficiently</p>

  <!-- Cards de Relatórios -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    <div class="bg-white p-4 rounded-lg shadow flex flex-col items-center">
      <h3 class="text-lg font-semibold mb-2">Active Projects</h3>
      <p class="text-3xl font-bold">12</p>
      <p class="text-sm text-green-500">+2.5% vs last month</p>
    </div>
    <div class="bg-white p-4 rounded-lg shadow flex flex-col items-center">
      <h3 class="text-lg font-semibold mb-2">Total Hours</h3>
      <p class="text-3xl font-bold">164h</p>
      <p class="text-sm text-green-500">+12.3% vs last month</p>
    </div>
    <div class="bg-white p-4 rounded-lg shadow flex flex-col items-center">
      <h3 class="text-lg font-semibold mb-2">Team Members</h3>
      <p class="text-3xl font-bold">8</p>
      <p class="text-sm text-green-500">+1 vs last month</p>
    </div>
    <div class="bg-white p-4 rounded-lg shadow flex flex-col items-center">
      <h3 class="text-lg font-semibold mb-2">Completed Projects</h3>
      <p class="text-3xl font-bold">7</p>
      <p class="text-sm text-green-500">+10% vs last month</p>
    </div>
  </div>

  <!-- Tabela de Projetos Recentes -->
  <div class="bg-white p-6 rounded-lg shadow mt-8">
      <h3 class="text-lg font-semibold mb-4">Recent Projects</h3>
      <table class="min-w-full table-auto">
          <thead class="bg-gray-200">
              <tr>
                  <th class="px-4 py-2 text-left">Name</th>
                  <th class="px-4 py-2 text-left">Status</th>
                  <th class="px-4 py-2 text-left">Progress</th>
                  <th class="px-4 py-2 text-left">Delivery</th>
              </tr>
          </thead>
          <tbody>
              <?php foreach($projects as $project): ?>
              <tr class="border-b">
                  <td class="px-4 py-2"><?= htmlspecialchars($project['name']) ?></td>
                  <td class="px-4 py-2"><?= htmlspecialchars($project['status']) ?></td>
                  <td class="px-4 py-2"><?= htmlspecialchars($project['progress']) ?>%</td>
                  <td class="px-4 py-2"><?= htmlspecialchars($project['end_date']) ?></td>
              </tr>
              <?php endforeach; ?>
          </tbody>
      </table>
  </div>
</div>
