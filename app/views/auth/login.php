<?php require __DIR__ . '/../layout/header.php'; ?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <title><?= $langText['login'] ?></title>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const toast = document.getElementById('toast-success');

            if (toast) {
                // Remove ?success=1 da URL imediatamente
                const url = new URL(window.location);
                if (url.searchParams.has('success')) {
                    url.searchParams.delete('success');
                    window.history.replaceState({}, document.title, url.pathname);
                }

                // Espera 3 segundos, depois aplica fade-out
                setTimeout(() => {
                    toast.classList.add('opacity-0');
                    setTimeout(() => toast.remove(), 500); // espera transição terminar
                }, 3000);
            }
        });
    </script>
</head>
<body>

    <!-- Toast de sucesso após cadastro -->
    <?php if (isset($_GET['success'])): ?>
        <div id="toast-success" class="fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded shadow-lg z-50 transition-opacity duration-500 opacity-100">
            Cadastro realizado com sucesso!
        </div>
    <?php endif; ?>

    <div class="flex items-center justify-center h-screen">
        <div class="bg-white p-6 rounded shadow-md w-96">
            <h2 class="text-2xl mb-4"><?= $langText['login'] ?></h2>

            <?php if (isset($error)): ?>
                <div class="text-red-500 mb-4"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form action="$basePath/auth" method="POST">
                <div class="mb-4">
                    <label>Email</label>
                    <input type="email" name="email" class="border p-2 w-full" required>
                </div>
                <div class="mb-4">
                    <label>Password</label>
                    <input type="password" name="password" class="border p-2 w-full" required>
                </div>
                <button type="submit" class="bg-blue-600 text-white p-2 w-full"><?= $langText['login'] ?></button>
                <div class="mt-2 text-center">
                    <a class="text-blue-600 text-sm" href="$basePath/register">
                        <?= $langText['dont_register'] ?? 'Não tem conta? Cadastre-se' ?>
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
