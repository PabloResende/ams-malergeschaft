<?php
// app/views/auth/register.php
require __DIR__ . '/../layout/header.php';
?>

<div class="flex items-center justify-center h-screen">
  <div class="bg-white p-6 rounded shadow-md w-96">
    <h2 class="text-2xl mb-4">
      <?= htmlspecialchars($langText['register'] ?? 'Registrar', ENT_QUOTES) ?>
    </h2>

    <?php if (! empty($_SESSION['error'])): ?>
      <div class="text-red-500 mb-4">
        <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
      </div>
    <?php endif; ?>

    <form action="<?= url('store') ?>" method="POST" enctype="multipart/form-data">
      <div class="mb-4">
        <label class="block mb-1"><?= htmlspecialchars($langText['name'] ?? 'Nome', ENT_QUOTES) ?></label>
        <input type="text" name="name" class="border p-2 w-full rounded" required>
      </div>

      <div class="mb-4">
        <label class="block mb-1"><?= htmlspecialchars($langText['email'] ?? 'E-mail', ENT_QUOTES) ?></label>
        <input type="email" name="email" class="border p-2 w-full rounded" required>
      </div>

      <div class="mb-4">
        <label class="block mb-1"><?= htmlspecialchars($langText['password'] ?? 'Senha', ENT_QUOTES) ?></label>
        <input type="password" name="password" class="border p-2 w-full rounded" required>
      </div>

      <div class="mb-4">
        <label class="block mb-1"><?= htmlspecialchars($langText['user_role'] ?? 'Nível de Usuário', ENT_QUOTES) ?></label>
        <select name="role" class="border p-2 w-full rounded" required>
          <option value="admin"><?= htmlspecialchars($langText['role_admin'] ?? 'Administrador', ENT_QUOTES) ?></option>
          <option value="finance"><?= htmlspecialchars($langText['role_finance'] ?? 'Financeiro', ENT_QUOTES) ?></option>
        </select>
      </div>

      <button type="submit"
              class="bg-green-600 hover:bg-green-700 text-white p-2 w-full rounded"
              onclick="this.disabled=true; this.form.submit();">
        <?= htmlspecialchars($langText['register'] ?? 'Registrar', ENT_QUOTES) ?>
      </button>

      <div class="mt-4 text-center">
        <a href="<?= url('login') ?>" class="text-blue-600 hover:underline text-sm">
          <?= htmlspecialchars($langText['dont_register'] ?? '← Voltar para o login', ENT_QUOTES) ?>
        </a>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
