// public/js/analytics.js

document.addEventListener('DOMContentLoaded', () => {
  const langText        = window.langText || {};
  const createdLabel    = langText['chart_label_created']   || 'Created';
  const completedLabel  = langText['chart_label_completed'] || 'Completed';

  const yearSel         = document.getElementById('filterYear');
  const quarterSel      = document.getElementById('filterQuarter');
  const semesterSel     = document.getElementById('filterSemester');
  const form            = document.getElementById('filterForm');
  const apiBase         = window.apiBase;

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
    const qs  = buildQuery(y, q, s);
    const res = await fetch(`${apiBase}/stats?${qs}`);
    return res.json();
  }

  function render(d) {
    destroyAll();

    // 1) Projects Created
    chartCreated = new Chart(
      document.getElementById('chartCreated'),
      {
        type: 'bar',
        data: {
          labels:   d.labels,
          datasets: [{
            label: createdLabel,
            data:  d.created
          }]
        },
        options: { responsive: true, maintainAspectRatio: false }
      }
    );

    // 2) Projects Completed
    chartCompleted = new Chart(
      document.getElementById('chartCompleted'),
      {
        type: 'bar',
        data: {
          labels:   d.labels,
          datasets: [{
            label: completedLabel,
            data:  d.completed
          }]
        },
        options: { responsive: true, maintainAspectRatio: false }
      }
    );

    // 3) Created vs Completed
    chartComparison = new Chart(
      document.getElementById('chartComparison'),
      {
        type: 'line',
        data: {
          labels: d.labels,
          datasets: [
            { label: createdLabel,   data: d.created,   fill: false, tension: 0.2 },
            { label: completedLabel, data: d.completed, fill: false, tension: 0.2 }
          ]
        },
        options: { responsive: true, maintainAspectRatio: false }
      }
    );

    // 4) Status Pie — traduzindo cada chave
    const statusKeys    = Object.keys(d.status);
    const statusLabels  = statusKeys.map(k => langText['status_'+k] || k);
    const statusData    = statusKeys.map(k => d.status[k]);

    chartStatus = new Chart(
      document.getElementById('chartStatus'),
      {
        type: 'pie',
        data: {
          labels:   statusLabels,
          datasets: [{ data: statusData }]
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

  loadAll();
});
