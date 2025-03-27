<?php require __DIR__ . '/../layout/header.php'; ?>

<div class="flex items-center justify-center min-h-screen bg-gray-100 p-4">
    <div class="bg-white shadow-lg rounded-lg p-6 w-full max-w-sm text-center">
        <img src="<?= !empty($_SESSION['user']['profile_picture']) ? $baseUrl . '/uploads/' . $_SESSION['user']['profile_picture'] : 'https://via.placeholder.com/100'; ?>" 
             class="rounded-full mx-auto mb-4 border border-gray-300 shadow-sm" alt="Avatar" width="100">

        <h2 class="text-2xl font-semibold text-gray-800"><?= htmlspecialchars($_SESSION['user']['name']); ?></h2>
        <p class="text-gray-500"><?= htmlspecialchars($_SESSION['user']['email']); ?></p>

        <form action="<?= $baseUrl ?>/update_profile" method="POST" enctype="multipart/form-data" class="mt-4 space-y-4">
            <div>
                <label class="block text-left text-gray-700 font-medium"><?= $langText['address'] ?></label>
                <input type="text" name="address" class="border rounded p-2 w-full focus:outline-none focus:ring focus:ring-blue-300" value="<?= htmlspecialchars($_SESSION['user']['address'] ?? ''); ?>">
            </div>

            <div>
                <label class="block text-left text-gray-700 font-medium"><?= $langText['about_me'] ?></label>
                <textarea name="about" class="border rounded p-2 w-full focus:outline-none focus:ring focus:ring-blue-300"><?= htmlspecialchars($_SESSION['user']['about'] ?? ''); ?></textarea>
            </div>

            <div>
                <label class="block text-left text-gray-700 font-medium"><?= $langText['phone'] ?></label>
                <input type="text" name="phone" class="border rounded p-2 w-full focus:outline-none focus:ring focus:ring-blue-300" id="phone" value="<?= htmlspecialchars($_SESSION['user']['phone'] ?? ''); ?>">
            </div>
            <div>
                <label class="block text-left text-gray-700 font-medium"><?= $langText['profile_picture'] ?></label>
                <input type="file" name="profile_picture" class="border rounded p-2 w-full focus:outline-none focus:ring focus:ring-blue-300">
            </div>

            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white p-2 w-full rounded transition">
                <?= $langText['save_changes'] ?>
            </button>
        </form>

        <a href="<?= $baseUrl ?>/logout" class="block bg-red-600 hover:bg-red-700 text-white p-2 w-full rounded mt-3 transition">
            <?= $langText['logout_button'] ?>
        </a>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const phone = document.getElementById('phone');
    const cpf = document.getElementById('cpf');

    phone.addEventListener("input", function() {
        this.value = this.value.replace(/\D/g, "").replace(/^(\d{2})(\d)/g, "($1) $2").replace(/(\d{5})(\d)/, "$1-$2");
    });

    if(cpf){
      cpf.addEventListener("input", function() {
          this.value = this.value.replace(/\D/g, "").replace(/(\d{3})(\d)/, "$1.$2")
                                .replace(/(\d{3})(\d)/, "$1.$2")
                                .replace(/(\d{3})(\d{1,2})$/, "$1-$2");
      });
    }
});
</script>