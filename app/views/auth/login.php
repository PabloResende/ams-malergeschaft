<?php
// app/views/auth/login.php
require __DIR__ . '/../layout/header.php';
?>

<div class="flex items-center justify-center h-screen">
  <div class="bg-white p-6 rounded shadow-md w-96">
    <h2 class="text-2xl mb-4"><?= htmlspecialchars($langText['login'] ?? 'Login') ?></h2>

    <?php if (! empty($error)): ?>
      <div class="text-red-500 mb-4"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form action="<?= url('auth') ?>" method="POST">
      <div class="mb-4">
        <label class="block mb-1"><?= htmlspecialchars($langText['email'] ?? 'Email') ?></label>
        <input type="email" name="email" class="border p-2 w-full rounded" required>
      </div>
      <div class="mb-4">
        <label class="block mb-1"><?= htmlspecialchars($langText['password'] ?? 'Password') ?></label>
        <input type="password" name="password" class="border p-2 w-full rounded" required>
      </div>
      <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white p-2 w-full rounded">
        <?= htmlspecialchars($langText['login'] ?? 'Entrar') ?>
      </button>
    </form>
  </div>
</div>

<?php
require __DIR__ . '/../layout/footer.php';
