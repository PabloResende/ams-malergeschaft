// public/js/pos.js
document.getElementById('new-transaction').addEventListener('click', () => {
    document.getElementById('pos-form').classList.toggle('hidden');
  });
  
  const form = document.getElementById('transaction-form');
  form.addEventListener('submit', async e => {
    e.preventDefault();
    const data = new FormData(form);
    // primeiro gerar link
    const linkRes = await fetch('/ams-malergeschaft/public/finance/generate-link', {
      method: 'POST', body: data
    }).then(r => r.json());
    document.getElementById('payment-link').value = linkRes.url;
    document.getElementById('output-area').classList.remove('hidden');
  });
  
  // gerar fatura PDF
  document.getElementById('generate-invoice').addEventListener('click', async () => {
    const data = new FormData(form);
    const invRes = await fetch('/ams-malergeschaft/public/finance/generate-invoice', {
      method: 'POST', body: data
    }).then(r => r.json());
    window.open(invRes.invoice_url, '_blank');
  });
  
  // enviar e-mail
  document.getElementById('transaction-form').addEventListener('click', async e => {
    if (e.target.id === 'generate-link') return; // pulamos
    if (e.target.id === 'generate-invoice') return;
    if (e.target.type === 'submit') {
      const data = new FormData(form);
      data.append('payment_link', document.getElementById('payment-link').value);
      const mailRes = await fetch('/ams-malergeschaft/public/finance/send-email', {
        method: 'POST', body: data
      }).then(r => r.json());
      alert(mailRes.success ? 'Enviado!' : 'Erro: ' + mailRes.error);
    }
  });
  