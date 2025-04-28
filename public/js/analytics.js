// public/js/analytics.js

document.addEventListener('DOMContentLoaded', () => {
  const yearSel     = document.getElementById('filterYear');
  const quarterSel  = document.getElementById('filterQuarter');
  const semesterSel = document.getElementById('filterSemester');
  const form        = document.getElementById('filterForm');
  const apiBase     = window.apiBase;  // definido no view

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

  function getPrevPeriod(y, q, s) {
    let py = parseInt(y), pq = q, ps = s;
    if (s) {
      if (s === '1') { py -= 1; ps = '2'; }
      else           { ps = '1';       }
    } else if (q) {
      if (q === '1') { py -= 1; pq = '4'; }
      else            pq = String(parseInt(q) - 1);
    } else {
      py -= 1;
    }
    return [String(py), pq, ps];
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

  document.getElementById('btnExportPdf').addEventListener('click', e => {
    e.preventDefault();
    window.open(`${apiBase}/exportPdf?${buildQuery(yearSel.value, quarterSel.value, semesterSel.value)}`);
  });

  document.getElementById('btnExportExcel').addEventListener('click', e => {
    e.preventDefault();
    window.open(`${apiBase}/exportExcel?${buildQuery(yearSel.value, quarterSel.value, semesterSel.value)}`);
  });

  document.getElementById('btnSendEmail').addEventListener('click', async e => {
    e.preventDefault();
    const y = yearSel.value, q = quarterSel.value, s = semesterSel.value;

    // busca dados atuais e do período anterior
    const [curr, prev] = await Promise.all([
      fetchStats(y, q, s),
      (async () => {
        const [py, pq, ps] = getPrevPeriod(y, q, s);
        return await fetchStats(py, pq, ps);
      })()
    ]);

    // helper para porcentagem
    const pct = (n, o) => o ? ((n - o) / o * 100).toFixed(1) + '%' : '—';

    // totais
    const totCreated   = curr.created.reduce((a, b) => a + b, 0);
    const totCompleted = curr.completed.reduce((a, b) => a + b, 0);

    // monta resumo
    const summary = [
      `Report Period: Year ${y}${q ? ' Q' + q : ''}${s ? ' S' + s : ''}`,
      '',
      `Budget Total: ${curr.budget_total} (${pct(curr.budget_total, prev.budget_total)} vs prev)`,
      `Total Hours: ${curr.hours} (${pct(curr.hours, prev.hours)} vs prev)`,
      `Projects Created: ${totCreated} (${pct(totCreated, prev.created.reduce((a,b)=>a+b,0))} vs prev)`,
      `Projects Completed: ${totCompleted} (${pct(totCompleted, prev.completed.reduce((a,b)=>a+b,0))} vs prev)`
    ];

    const res = await fetch(`${apiBase}/sendEmail`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ summary })
    });
    const json = await res.json();
    alert(json.success
      ? 'Email sent with detailed analysis!'
      : 'Error sending email: ' + json.message
    );
  });

  // inicializa
  loadAll();
});
