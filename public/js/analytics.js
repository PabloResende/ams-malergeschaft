// public/js/analytics.js

document.addEventListener('DOMContentLoaded', () => {
  const yearSel     = document.getElementById('filterYear');
  const quarterSel  = document.getElementById('filterQuarter');
  const semesterSel = document.getElementById('filterSemester');
  const form        = document.getElementById('filterForm');
  const apiBase     = window.apiBase;

  let chartCreated, chartCompleted, chartComparison, chartStatus;

  function destroyAll() {
    [chartCreated, chartCompleted, chartComparison, chartStatus]
      .forEach(c => c && c.destroy());
  }

  function buildQuery(y, q, s) {
    const params = new URLSearchParams();
    if (y) params.set('year', y);
    if (q) params.set('quarter', q);
    if (s) params.set('semester', s);
    return params.toString();
  }

  async function fetchStats(y, q, s) {
    const qs = buildQuery(y, q, s);
    const res = await fetch(`${apiBase}/stats?${qs}`);
    return res.json();
  }

  function render(d) {
    destroyAll();

    chartCreated = new Chart(
      document.getElementById('chartCreated'),
      {
        type: 'bar',
        data: { labels: d.labels, datasets: [{ label: 'Created', data: d.created }] },
        options: { responsive: true, maintainAspectRatio: false }
      }
    );

    chartCompleted = new Chart(
      document.getElementById('chartCompleted'),
      {
        type: 'bar',
        data: { labels: d.labels, datasets: [{ label: 'Completed', data: d.completed }] },
        options: { responsive: true, maintainAspectRatio: false }
      }
    );

    chartComparison = new Chart(
      document.getElementById('chartComparison'),
      {
        type: 'line',
        data: {
          labels: d.labels,
          datasets: [
            { label: 'Created',   data: d.created,   fill: false, tension: 0.2 },
            { label: 'Completed', data: d.completed, fill: false, tension: 0.2 }
          ]
        },
        options: { responsive: true, maintainAspectRatio: false }
      }
    );

    chartStatus = new Chart(
      document.getElementById('chartStatus'),
      {
        type: 'pie',
        data: {
          labels: Object.keys(d.status),
          datasets: [{ data: Object.values(d.status) }]
        },
        options: { responsive: true, maintainAspectRatio: false }
      }
    );
  }

  async function loadAll() {
    const y = yearSel.value, q = quarterSel.value, s = semesterSel.value;
    const stats = await fetchStats(y, q, s);
    render(stats);
  }

  form.addEventListener('submit', e => {
    e.preventDefault();
    loadAll();
  });

  // inicializa
  loadAll();
});
