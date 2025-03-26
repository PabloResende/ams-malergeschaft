<?php require __DIR__ . '/../layout/header.php'; ?>

<div class="flex items-center justify-center min-h-screen">
    <div class="bg-white shadow-lg rounded-lg p-6 w-96 text-center">
        <img src="<?php echo !empty($_SESSION['user']['profile_picture']) ? '/ams-malergeschaft/public/uploads/' . $_SESSION['user']['profile_picture'] : 'https://via.placeholder.com/100'; ?>" 
             class="rounded-full mx-auto mb-4" alt="Avatar" width="100">

        <h2 class="text-2xl font-semibold"><?php echo htmlspecialchars($_SESSION['user']['name']); ?></h2>
        <p class="text-gray-500"><?php echo htmlspecialchars($_SESSION['user']['email']); ?></p>

        <form action="/ams-malergeschaft/public/update_profile" method="POST" enctype="multipart/form-data" class="mt-4">
            <div class="mb-3">
                <label class="block text-left">Endereço</label>
                <input type="text" name="address" class="border rounded p-2 w-full" value="<?php echo htmlspecialchars($_SESSION['user']['address'] ?? ''); ?>">
            </div>

            <div class="mb-3">
                <label class="block text-left">Sobre Mim</label>
                <textarea name="about" class="border rounded p-2 w-full"><?php echo htmlspecialchars($_SESSION['user']['about'] ?? ''); ?></textarea>
            </div>

            <div class="mb-3">
                <label class="block text-left">Telefone</label>
                <input type="text" name="phone" class="border rounded p-2 w-full" id="phone" value="<?php echo htmlspecialchars($_SESSION['user']['phone'] ?? ''); ?>">
            </div>

            <div class="mb-3">
                <label class="block text-left">CPF</label>
                <input type="text" name="cpf" class="border rounded p-2 w-full" id="cpf" value="<?php echo htmlspecialchars($_SESSION['user']['cpf'] ?? ''); ?>">
            </div>

            <div class="mb-3">
                <label class="block text-left">Foto de Perfil</label>
                <input type="file" name="profile_picture" class="border rounded p-2 w-full">
            </div>

            <button type="submit" class="bg-blue-600 text-white p-2 w-full rounded">Salvar Alterações</button>
        </form>

        <a href="/ams-malergeschaft/public/logout" class="block bg-red-600 text-white p-2 w-full rounded mt-3">Logout</a>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const phone = document.getElementById('phone');
    const cpf = document.getElementById('cpf');

    phone.addEventListener("input", function() {
        this.value = this.value.replace(/\D/g, "").replace(/^(\d{2})(\d)/g, "($1) $2").replace(/(\d{5})(\d)/, "$1-$2");
    });

    cpf.addEventListener("input", function() {
        this.value = this.value.replace(/\D/g, "").replace(/(\d{3})(\d)/, "$1.$2")
                              .replace(/(\d{3})(\d)/, "$1.$2")
                              .replace(/(\d{3})(\d{1,2})$/, "$1-$2");
    });
});
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
