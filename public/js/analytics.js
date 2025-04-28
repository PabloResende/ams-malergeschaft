// public/js/analytics.js

document.addEventListener('DOMContentLoaded', () => {
  const yearSel     = document.getElementById('filterYear');
  const quarterSel  = document.getElementById('filterQuarter');
  const semesterSel = document.getElementById('filterSemester');
  const form        = document.getElementById('filterForm');

  let chartCreated, chartCompleted, chartComparison, chartStatus;

  function destroyAll() {
    [chartCreated, chartCompleted, chartComparison, chartStatus]
      .forEach(c => c && c.destroy());
  }

  function fetchStats(y, q, s) {
    const params = new URLSearchParams();
    if (y) params.set('year', y);
    if (q) params.set('quarter', q);
    if (s) params.set('semester', s);
    return fetch(`${apiBase}/stats?${params}`)
      .then(r => r.json());
  }

  function render(d) {
    destroyAll();

    // Projects Created
    chartCreated = new Chart(
      document.getElementById('chartCreated'),
      {
        type: 'bar',
        data: { labels: d.labels, datasets: [{ label: 'Created', data: d.created }] },
        options: { responsive: true, maintainAspectRatio: false }
      }
    );

    // Projects Completed
    chartCompleted = new Chart(
      document.getElementById('chartCompleted'),
      {
        type: 'bar',
        data: { labels: d.labels, datasets: [{ label: 'Completed', data: d.completed }] },
        options: { responsive: true, maintainAspectRatio: false }
      }
    );

    // Created vs Completed
    chartComparison = new Chart(
      document.getElementById('chartComparison'),
      {
        type: 'line',
        data: {
          labels: d.labels,
          datasets: [
            { label: 'Created', data: d.created, fill: false, tension: 0.2 },
            { label: 'Completed', data: d.completed, fill: false, tension: 0.2 }
          ]
        },
        options: { responsive: true, maintainAspectRatio: false }
      }
    );

    // Project Status
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
    const stats = await fetchStats(
      yearSel.value,
      quarterSel.value,
      semesterSel.value
    );
    render(stats);
  }

  form.addEventListener('submit', e => {
    e.preventDefault();
    loadAll();
  });

  document.getElementById('btnExportPdf').addEventListener('click', e => {
    e.preventDefault();
    const p = new URLSearchParams({
      year: yearSel.value,
      quarter: quarterSel.value,
      semester: semesterSel.value
    });
    window.open(`${apiBase}/exportPdf?${p}`);
  });

  document.getElementById('btnExportExcel').addEventListener('click', e => {
    e.preventDefault();
    const p = new URLSearchParams({
      year: yearSel.value,
      quarter: quarterSel.value,
      semester: semesterSel.value
    });
    window.open(`${apiBase}/exportExcel?${p}`);
  });

  document.getElementById('btnSendEmail').addEventListener('click', async e => {
    e.preventDefault();
    const d = await fetchStats(
      yearSel.value,
      quarterSel.value,
      semesterSel.value
    );
    const summary = [
      `Projects created: ${d.created.reduce((a,b)=>a+b,0)}`,
      `Projects completed: ${d.completed.reduce((a,b)=>a+b,0)}`
    ];
    const res = await fetch(`${apiBase}/sendEmail`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ summary })
    });
    const json = await res.json();
    alert(json.success
      ? 'Email sent!'
      : 'Error: ' + json.message);
  });

  loadAll();
});
