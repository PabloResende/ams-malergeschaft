// public/js/analytics.js
document.addEventListener('DOMContentLoaded', () => {
  const yearSelect    = document.getElementById('filterYear');
  const quarterSelect = document.getElementById('filterQuarter');
  const semesterSelect= document.getElementById('filterSemester');
  const form          = document.getElementById('filterForm');
  const charts        = {};

  function destroyChart(id) {
    const chart = Chart.getChart(id);
    if (chart) chart.destroy();
  }

  function renderCharts(data) {
    const labels    = data.labels    ?? ['Jan', 'Feb', 'Mar'];
    const created   = data.created   ?? [0, 0, 0];
    const completed = data.completed ?? [0, 0, 0];

    // destruir antes de recriar
    ['chartCreated','chartCompleted','chartComparison','chartBudget','chartStatus']
      .forEach(d => destroyChart(d));

    // Projetos Criados
    charts.chartCreated = new Chart(
      document.getElementById('chartCreated'),
      {
        type: 'bar',
        data: { labels, datasets: [{ label: 'Criados', data: created }] },
        options: {
          responsive: true,
          maintainAspectRatio: false
        }
      }
    );

    // Projetos Concluídos
    charts.chartCompleted = new Chart(
      document.getElementById('chartCompleted'),
      {
        type: 'bar',
        data: { labels, datasets: [{ label: 'Concluídos', data: completed }] },
        options: {
          responsive: true,
          maintainAspectRatio: false
        }
      }
    );

    // Comparação (Linha)
    charts.chartComparison = new Chart(
      document.getElementById('chartComparison'),
      {
        type: 'line',
        data: {
          labels,
          datasets: [
            { label: 'Criados',    data: created,   fill: false, tension: 0.2 },
            { label: 'Finalizados',data: completed, fill: false, tension: 0.2 }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false
        }
      }
    );

    // Orçamento
    charts.chartBudget = new Chart(
      document.getElementById('chartBudget'),
      {
        type: 'bar',
        data: {
          labels: ['Orçamento'],
          datasets: [
            { label: 'Planejado', data: [data.budget_total   ?? 0] },
            { label: 'Usado',     data: [data.budget_used    ?? 0] }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: { y: { beginAtZero: true } }
        }
      }
    );

    // Status (Pizza)
    charts.chartStatus = new Chart(
      document.getElementById('chartStatus'),
      {
        type: 'pie',
        data: {
          labels: Object.keys(data.status ?? {}),
          datasets: [{ data: Object.values(data.status ?? {}) }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false
        }
      }
    );

    // Indicadores
    document.getElementById('totalMaterials').textContent = data.materials ?? 0;
    document.getElementById('totalHours').textContent     = data.hours     ?? 0;
  }

  function buildQueryParams() {
    const params = new URLSearchParams();
    if (yearSelect.value)    params.append('year',    yearSelect.value);
    if (quarterSelect.value) params.append('quarter', quarterSelect.value);
    if (semesterSelect.value)params.append('semester',semesterSelect.value);
    return params.toString();
  }

  function loadData() {
    fetch(`/ams-malergeschaft/public/analytics/stats?${buildQueryParams()}`)
      .then(res => res.json())
      .then(renderCharts)
      .catch(err => console.error('Erro ao carregar dados:', err));
  }

  form.addEventListener('submit', e => { e.preventDefault(); loadData(); });
  document.getElementById('btnExportPdf')
    .addEventListener('click', e => {
      e.preventDefault();
      window.open(`/ams-malergeschaft/public/analytics/exportPdf?${buildQueryParams()}`);
    });
  document.getElementById('btnExportExcel')
    .addEventListener('click', e => {
      e.preventDefault();
      window.open(`/ams-malergeschaft/public/analytics/exportExcel?${buildQueryParams()}`);
    });
  document.getElementById('btnSendEmail')
    .addEventListener('click', e => {
      e.preventDefault();
      fetch(`/ams-malergeschaft/public/analytics/sendEmail`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          year:    yearSelect.value,
          quarter: quarterSelect.value,
          semester:semesterSelect.value
        })
      })
      .then(res => res.json())
      .then(res => {
        alert(res.success
          ? 'Relatório enviado com sucesso!'
          : 'Erro ao enviar relatório: ' + res.message
        );
      })
      .catch(err => console.error('Erro no envio de e-mail:', err));
    });

  loadData();
});
