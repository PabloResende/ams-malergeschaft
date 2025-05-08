<?php
// app/views/payments/pos.php
require __DIR__ . '/../layout/header.php';
?>

<div class="content-wrapper p-6 bg-gray-100 min-h-screen">
  <div class="max-w-4xl mx-auto bg-white shadow rounded-lg">
    <div class="px-6 py-4 border-b flex justify-between items-center">
      <h1 class="text-2xl font-semibold">Caixa Registradora</h1>
      <button id="new-transaction" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">Nova Cobrança</button>
    </div>

    <div id="pos-form" class="p-6">
      <form id="transaction-form" class="space-y-6">
        <!-- Cliente e Email -->
        <div class="grid md:grid-cols-2 gap-4">
          <div>
            <label for="client" class="block text-sm font-medium">Cliente</label>
            <select id="client" name="client" required class="mt-1 block w-full border rounded p-2">
              <option value="">Selecione...</option>
              <?php foreach ($clients as $c): ?>
                <option 
                  value="<?= $c['id'] ?>" 
                  data-email="<?= htmlspecialchars($c['email']) ?>"
                >
                  <?= htmlspecialchars($c['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label for="client_email" class="block text-sm font-medium">E-mail</label>
            <input
              type="email"
              id="client_email"
              name="client_email"
              readonly
              class="mt-1 block w-full border rounded p-2 bg-gray-100"
              placeholder="Será preenchido automaticamente"
            />
          </div>
        </div>

        <!-- Valor e IVA -->
        <div class="grid md:grid-cols-2 gap-4">
          <div>
            <label for="amount" class="block text-sm font-medium">Valor (<?= $currency ?>)</label>
            <input
              type="number"
              id="amount"
              name="amount"
              step="0.01"
              min="0"
              required
              class="mt-1 block w-full border rounded p-2"
              placeholder="0,00"
            />
          </div>
          <div>
            <label for="vat" class="block text-sm font-medium">IVA (%)</label>
            <select id="vat" name="vat" class="mt-1 block w-full border rounded p-2">
              <?php foreach ($vatRates as $rate): ?>
                <option value="<?= $rate ?>"><?= $rate ?>%</option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- Nº Fatura e Vencimento -->
        <div class="grid md:grid-cols-2 gap-4">
          <div>
            <label for="invoice_number" class="block text-sm font-medium">Nº da Fatura</label>
            <input
              type="text"
              id="invoice_number"
              name="invoice_number"
              readonly
              value="<?= generateInvoiceNumber() ?>"
              class="mt-1 block w-full border rounded p-2 bg-gray-100"
            />
          </div>
          <div>
            <label for="due_date" class="block text-sm font-medium">Vencimento</label>
            <input
              type="date"
              id="due_date"
              name="due_date"
              required
              class="mt-1 block w-full border rounded p-2"
            />
          </div>
        </div>

        <!-- Método de Pagamento -->
        <fieldset>
          <legend class="text-sm font-medium">Método</legend>
          <div class="mt-2 flex items-center space-x-6">
            <label class="flex items-center">
              <input type="radio" name="method" value="card" checked class="form-radio">
              <span class="ml-2">Cartão</span>
            </label>
            <label class="flex items-center">
              <input type="radio" name="method" value="twint" class="form-radio">
              <span class="ml-2">TWINT</span>
            </label>
            <label class="flex items-center">
              <input type="radio" name="method" value="qr" class="form-radio">
              <span class="ml-2">QR Invoice</span>
            </label>
          </div>
        </fieldset>

        <!-- Ações -->
        <div class="flex space-x-4">
          <button id="generate-link" type="button" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">Gerar Link</button>
          <button id="generate-invoice" type="button" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded">Gerar Fatura</button>
          <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">Enviar E-mail</button>
        </div>
      </form>

      <!-- Output -->
      <div id="output-area" class="mt-6 space-y-4 hidden">
        <div>
          <label class="block text-sm font-medium">Link de Pagamento</label>
          <input id="payment-link" readonly class="mt-1 block w-full border rounded p-2 bg-gray-100">
        </div>
        <div id="qr-code" class="flex justify-center"></div>
      </div>
    </div>
  </div>
</div>

<script src="https://js.stripe.com/v3/"></script>
<script src="<?= $baseUrl ?>/js/pos.js"></script>
<script>
// popula email ao mudar cliente
document.getElementById('client').addEventListener('change', function(){
  const selected = this.selectedOptions[0];
  document.getElementById('client_email').value = selected.dataset.email || '';
});
</script>

