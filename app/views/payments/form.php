<?php
require __DIR__ . '/../layout/header.php';
?>

<div class="content-wrapper flex justify-center items-start bg-gray-100 min-h-screen">
  <div class="w-full max-w-md mt-12 p-6 bg-white rounded-lg shadow-lg">
    <h2 class="text-2xl font-semibold mb-6 text-gray-800">Pagamento (<?= htmlspecialchars($currency) ?>)</h2>
    <form id="payment-form" class="space-y-5">
      <!-- Valor -->
      <div>
        <label for="amount" class="block text-sm font-medium text-gray-700">Valor em <?= htmlspecialchars($currency) ?></label>
        <input
          type="number"
          name="amount"
          id="amount"
          required
          class="mt-1 block w-full p-2 border border-gray-300 rounded-md focus:ring focus:ring-blue-200"
          step="0.01"
          min="0.01"
          placeholder="0,00"
        />
      </div>

      <!-- Cartão -->
      <div>
        <label for="card-element" class="block text-sm font-medium text-gray-700">Dados do Cartão</label>
        <div id="card-element" class="mt-1 p-3 border border-gray-300 rounded-md"></div>
      </div>

      <!-- Botão -->
      <button
        type="submit"
        class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-md font-medium transition"
      >
        Pagar
      </button>

      <!-- Erros -->
      <div id="error-message" class="text-red-600 text-sm mt-2"></div>
    </form>
  </div>
</div>

<!-- Inclui o Stripe.js -->
<script src="https://js.stripe.com/v3/"></script>
<script>
  const stripe = Stripe('<?= $publishableKey ?>');
  const elements = stripe.elements();
  const card = elements.create('card', { hidePostalCode: true });
  card.mount('#card-element');

  const form = document.getElementById('payment-form');
  form.addEventListener('submit', async e => {
    e.preventDefault();
    document.getElementById('error-message').textContent = '';

    const { clientSecret, error } = await fetch('<?= $baseUrl ?>/finance/checkout', {
      method: 'POST',
      body: new FormData(form)
    }).then(r => r.json());

    if (error) {
      document.getElementById('error-message').textContent = error.message;
      return;
    }

    const { paymentIntent, error: confirmError } = await stripe.confirmCardPayment(clientSecret, {
      payment_method: { card }
    });

    if (confirmError) {
      document.getElementById('error-message').textContent = confirmError.message;
    } else if (paymentIntent.status === 'succeeded') {
      window.location.href = '<?= $baseUrl ?>/finance/success';
    }
  });
</script>

<?php
?>
