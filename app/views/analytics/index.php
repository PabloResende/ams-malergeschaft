
<?php include __DIR__ . '/../layout/header.php'; ?>
<div class='p-6'>
  <h1 class='text-2xl font-bold mb-4'>Painel de Análises</h1>
  <div class='grid grid-cols-1 md:grid-cols-2 gap-6'>
    <canvas id='chartProjects'></canvas>
    <canvas id='chartExpenses'></canvas>
    <canvas id='chartStatus'></canvas>
  </div>
</div>

<script src='https://cdn.jsdelivr.net/npm/chart.js'></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  new Chart(document.getElementById('chartProjects'), {
    type: 'line',
    data: {
      labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'],
      datasets: [{
        label: 'Projetos Novos',
        data: [3, 6, 4, 8, 5, 7],
        borderColor: '#3b82f6',
        fill: false
      }]
    }
  });

  new Chart(document.getElementById('chartExpenses'), {
    type: 'bar',
    data: {
      labels: ['Projeto A', 'Projeto B', 'Projeto C'],
      datasets: [{
        label: 'Gasto em R$',
        data: [5200, 8700, 4300],
        backgroundColor: ['#facc15', '#10b981', '#ef4444']
      }]
    }
  });

  new Chart(document.getElementById('chartStatus'), {
    type: 'doughnut',
    data: {
      labels: ['Ativos', 'Concluídos'],
      datasets: [{
        data: [9, 11],
        backgroundColor: ['#fbbf24', '#4ade80']
      }]
    }
  });
});
</script>
<?php include __DIR__ . '/../layout/footer.php'; ?>
